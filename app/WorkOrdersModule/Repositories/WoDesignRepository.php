<?php

namespace App\WorkOrdersModule\Repositories;

use App\Console\Support\FcmHandler;
use App\GeneralModule\Models\FcmModel;
use App\GeneralModule\Models\User;
use App\WorkOrdersModule\Enums\DesignApprovalStatus;
use App\WorkOrdersModule\Models\DesignRequest;
use App\WorkOrdersModule\Repositories\Contracts\WoDesignRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WoDesignRepository implements WoDesignRepositoryInterface
{
    protected $notif;
    public function __construct(protected DesignRequest $model, FcmHandler $notif)
    {
        $this->notif = $notif;
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->newQuery();
        $query->with(['items', 'approvals']);
        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach ($search as $field => $value) {
                    if ($value) {
                        $q->orWhere($field, 'like', '%' . $value . '%');
                    }
                }
            });
        }
        // Penerapan sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order'] ?? 'asc');
            }
        } else {
            // Default sorting jika tidak ada sortBy
            $query->latest();
        }
        // Return hasil pagination
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function paginateTrashed(int $page, int $limit, array|null $filter): mixed
    {
        $query = $this->model->onlyTrashed();

        if (!empty($filter)) {
            foreach ($filter as $field => $value) {
                if ($value) {
                    $query->where($field, 'like', '%' . $value . '%');
                }
            }
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function form(): mixed
    {
        $users = Cache::remember('form:user_submitted_to', 600, function () {
            return User::query()
                ->with([
                    'employee:id,user_id,departement_id',
                    'employee.departement:id,name',
                ])
                ->whereHas('employee.departement', fn($q) => $q->where('name', 'DESIGN'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($u) => [
                    'value' => $u->id,
                    'label' => $u->name,
                    'departement' => $u->employee?->departement?->name,
                ])
                ->filter(fn($row) => !empty($row['value'])) // jaga-jaga jika user tak punya employee
                ->values();
        });

        return [
            'user_submitted_to' => $users,
        ];
    }
    public function generate_request_numbers(int $division_id)
    {

    }
    public function create(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $employee = $user->employee;                // pastikan relasi eager di middleware bila perlu
            $divisionId = $employee->departement_id ?? null;
            $submittedTo = $data['submitted_to_id'] ?? null;

            // Pakai satu timestamp immutable agar konsisten dalam 1 transaksi
            $now = CarbonImmutable::now();

            // Set default value yang ringkas & konsisten
            $data['request_no'] ??= DesignRequest::generateRequestNumber($divisionId);
            $data['request_date'] ??= $now->toDateString();
            $data['need_by_date'] ??= $now->toDateString();
            $data['pic_id'] ??= $user->id;
            $data['division_id'] ??= $divisionId;
            $data['acknowledged_by_id'] ??= $submittedTo;
            $data['notes'] ??= 'Tanpa keterangan';

            $payload = Arr::only($data, [
                'request_no',
                'request_date',
                'need_by_date',
                'priority',
                'pic_id',
                'division_id',
                'submitted_to_id',
                'acknowledged_by_id',
                'notes',
            ]);
            $model = $this->model->create($payload);
            // Simpan detail items (sanitize per kolom yg diizinkan)
            if (!empty($data['items']) && is_array($data['items'])) {
                $items = collect($data['items'])
                    ->filter(fn($row) => is_array($row))
                    ->map(fn($row) => Arr::only($row, [
                        'name',
                        'description',
                        'qty',
                        'uom',
                        'notes',
                        // kolom item yang valid
                    ]))
                    ->values()
                    ->all();

                if (!empty($items)) {
                    $model->items()->createMany($items);
                }
            }

            // Ambil approval line: atasan pengaju & atasan tujuan (jika ada)
            $approvalSubmitter = $employee->approval_line_id ?? null;

            $approvalTarget = null;
            if ($submittedTo) {
                // Ambil employee atasannya secara hemat (hindari N+1)
                $targetUser = User::with([
                    'employee:id,user_id,approval_line_id'
                ])->find($submittedTo);

                $approvalTarget = $targetUser?->employee?->approval_line_id;
            }
            // Kumpulkan unik & non-null
            $approverIds = collect([$approvalSubmitter, $approvalTarget])
                ->filter(fn($id) => !empty($id))
                ->unique()
                ->values();
            if ($approverIds->isNotEmpty()) {
                $model->approvals()->createMany(
                    $approverIds->map(fn($id) => ['approver_id' => $id])->all()
                );
            }
            // Kirim notif FCM ke para approver (distinct token)
            if ($approverIds->isNotEmpty()) {
                $fcmTokens = FcmModel::query()
                    ->whereIn('user_id', $approverIds)     // <- pakai langsung IDs, tidak pakai array_column
                    ->pluck('device_token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($fcmTokens)) {
                    $title = "Permohonan Permintaan design/content/video";
                    $body = "Pengguna {$user->name}-{$user->nip} mengajukan permintaan, silakan periksa.";
                    $this->notif->sendToMultiple($fcmTokens, $title, $body, $model->toArray());
                }
            }
            // Kembalikan model fresh dengan relasi penting agar siap dipakai di layer atas
            return $model->fresh(['items', 'approvals']);
        });
    }
    public function find(int|string $id): mixed
    {
        $model = $this->model->with(['items', 'approvals'])
            ->findOrFail($id);

        return $model;
    }

    public function update(int|string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $model = $this->model->findOrFail($id);
            $userAuth = Auth::user();
            $divisionAuthId = optional($userAuth->employee)->departement_id;

            // Default values untuk update
            $data = array_merge([
                'request_no' => $model->request_no ?? DesignRequest::generateRequestNumber($divisionAuthId),
                'request_date' => $model->request_date ?? now()->format('Y-m-d'),
                'need_by_date' => $model->need_by_date ?? now()->format('Y-m-d'),
                'pic_id' => $model->pic_id ?? $userAuth->id,
                'division_id' => $model->division_id ?? $divisionAuthId,
                'acknowledged_by_id' => $model->acknowledged_by_id ?? $data['submitted_to_id'] ?? null,
                'notes' => $model->notes ?? 'Tanpa keterangan',
            ], $data);

            // Update data utama
            $model->update($data);

            // Update detail items
            if (isset($data['items']) && is_array($data['items'])) {
                $model->items()->delete();
                $model->items()->createMany($data['items']);
            }

            return $model;
        });
    }

    public function approve(int|string $id, array $data = []): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            // Kunci baris agar aman dari parallel update
            $model = $this->model->newQuery()->lockForUpdate()->findOrFail($id);

            $userAuth = Auth::user();

            // Ambil atasan yang mengajukan & atasan tujuan
            $approvalAtasanMengajukan = optional($userAuth->employee)->approval_line_id;
            $approvalAtasanDiajukan = optional(
                optional(User::find($model->submitted_to_id))->employee
            )->approval_line_id;

            // Kumpulkan approver yang valid & unik
            $approverIds = collect([$approvalAtasanMengajukan, $approvalAtasanDiajukan])
                ->filter()   // buang null
                ->unique()
                ->values();

            // Pastikan baris approval ada (idempotent)
            // Gunakan firstOrCreate agar tidak terjadi duplikasi walau dipanggil berulang
            foreach ($approverIds as $approverId) {
                $model->approvals()->firstOrCreate(
                    ['approver_id' => $approverId],
                    ['status' => DesignApprovalStatus::PENDING]
                );
            }

            // Normalisasi & validasi status (fallback ke PENDING
            // jika tidak valid / tidak dikirim)
            $status = $data['status'] ?? null;
            if ($status instanceof DesignApprovalStatus === false) {
                if (is_string($status)) {
                    $statusUpper = strtoupper($status);
                    $status = DesignApprovalStatus::tryFrom($statusUpper)
                        ?? DesignApprovalStatus::tryFrom(strtolower($status));
                }
            }
            if (!($status instanceof DesignApprovalStatus)) {
                $status = DesignApprovalStatus::PENDING;
            }

            // Update status pada approval yang relevan
            // (default: atasan yang mengajukan; jika ingin semua approver, gunakan whereIn)
            if ($approverIds->isNotEmpty()) {
                $model->approvals()
                    ->where('approver_id', $approvalAtasanMengajukan)
                    ->update([
                        'status' => $status,
                        'decided_at' => now(),
                    ]);
            }

            // Kirim notifikasi ke pemohon (requested_by_id)
            $fcmTokens = FcmModel::query()
                ->where('user_id', $model->requested_by_id)
                ->whereNotNull('device_token')
                ->pluck('device_token')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($fcmTokens)) {
                $title = 'Permohonan Permintaan design/content/video';
                $body = sprintf(
                    'Permohonan kamu sudah ditindaklanjuti oleh %s, silakan periksa.',
                    $userAuth->name
                );

                // Pastikan $this->notif->sendToMultiple menangani payload array
                $this->notif->sendToMultiple($fcmTokens, $title, $body, $model->toArray());
            }

            return $model->load('approvals');
        });
    }

    public function delete(int|string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function forceDelete(int|string $id): bool
    {
        return $this->model->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int|string $id): mixed
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed {
        ini_set('memory_limit', '512M');
        // Build query
        $query = $this->model;

        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if (!empty($createdAt)) {
            $query->whereDate('created_at', $createdAt);
        }
        if (!empty($updatedAt)) {
            $query->whereDate('updated_at', $updatedAt);
        }
        if (!empty($startRange) && !empty($endRange)) {
            $query->whereBetween('created_at', [$startRange, $endRange]);
        }
        $data = $query->get();
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data User yang ditemukan.'], 404);
        }
        return $data;
    }

    public function import($file): mixed
    {
        // Implement logic for importing data
        return true;
    }
}
