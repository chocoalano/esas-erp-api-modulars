<?php

namespace App\WorkOrdersModule\Repositories;

use App\Console\Support\NotificationHandler;
use App\GeneralModule\Models\FcmModel;
use App\GeneralModule\Models\User;
use App\WorkOrdersModule\Models\WorkOrder;
use App\WorkOrdersModule\Repositories\Contracts\WoIctMtcRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WoIctMtcRepository implements WoIctMtcRepositoryInterface
{
    protected $notif;
    public function __construct(protected WorkOrder $model, NotificationHandler $notif)
    {
        $this->notif = $notif;
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->newQuery();
        $query->with([
            'requestedBy',
            'department',
            'services',
            'spareparts',
            'clearance',
            'signoff',
        ]);
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
        //siapkan keperluan form untuk resources ini disini
        return [];
    }

    public function create(array $data): mixed
    {
        $user = Auth::user()->loadMissing('employee');
        $division = $user->employee->departement_id ?? null;

        // Validasi awal di luar transaksi (lebih cepat & jelas)
        $divisionTarget = $data['division_target'] ?? null;
        if (empty($divisionTarget)) {
            throw ValidationException::withMessages([
                'division_target' => 'division_target wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($data, $user, $division, $divisionTarget) {
            // Set default & nomor WO
            $payload = $data;
            $payload['requested_by_id'] ??= $user->id;
            $payload['department_id'] ??= $division;
            $payload['department_provides'] = $divisionTarget;
            $payload['wo_no'] ??= WorkOrder::generateRequestNumber($divisionTarget, $division);

            // Whitelist berdasarkan fillable agar aman
            if (method_exists($this->model, 'getFillable')) {
                $payload = Arr::only($payload, $this->model->getFillable());
            }

            // Create WO
            $model = $this->model->create($payload);

            // ---- Notifikasi setelah commit agar tidak mengganggu transaksi
            $notif = $this->notif;
            $woNo = $model->wo_no;
            $title = 'Permohonan Permintaan pemeliharaan/perbaikan/improvement';
            $body = "Pengguna {$user->name}-{$user->nip} mengajukan permintaan {$woNo}, silakan periksa.";

            DB::afterCommit(function () use ($divisionTarget, $notif, $title, $body) {
                User::query()
                    ->whereRelation('employee', 'departement_id', $divisionTarget)
                    ->select('id')
                    ->chunkById(500, function ($users) use ($notif, $title, $body) {
                        foreach ($users as $u) {
                            $notif->sendNotification($title, $body, $u->id);
                        }
                    });
            });
            return $model;
        });
    }

    public function find(int|string $id): mixed
    {
        $model = $this->model
            ->findOrFail($id);

        return $model;
    }

    public function update(int|string $id, array $data): mixed
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);
        return $model;
    }
    public function service(int|string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            // Kunci baris untuk mencegah race condition
            $model = $this->model->lockForUpdate()->findOrFail($id);
            // ----- 1) Update status hanya jika perlu
            $newStatus = $data['status'] ?? 'IN_PROGRESS';
            if ($model->status !== $newStatus) {
                $model->forceFill(['status' => $newStatus])->save();
            }
            // ----- 2) Upsert service (stabil untuk relasi hasOne/hasMany)
            $servicePayload = Arr::except($data, ['status', 'sparepart_change']);
            // Jika 1 WO hanya punya 1 service, ini aman:
            $service = $model->services()->firstOrNew(); // dibatasi oleh FK relasi
            $servicePayload['created_by_id']=auth()->id();
            $service->fill($servicePayload);
            // Pastikan FK terisi saat create (kalau fillable tidak otomatis)
            if (!$service->exists) {
                $service->{$model->services()->getForeignKeyName()} = $model->getKey();
            }
            $service->save();

            // ----- 3) Upsert spareparts (bulk, sargable)
            if (!empty($data['sparepart_change']) && is_array($data['sparepart_change'])) {
                $now = now();

                $rows = collect($data['sparepart_change'])
                    ->filter(fn($r) => !empty($r['part_name']))
                    ->map(function ($r) use ($model, $now) {
                        return [
                            'work_order_id' => $model->id,
                            'part_name' => $r['part_name'],
                            'quantity' => isset($r['quantity']) ? (int) $r['quantity'] : 1,
                            'remarks' => $r['remarks'] ?? null,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ];
                    })
                    ->values()
                    ->all();

                if (!empty($rows)) {
                    $model->spareparts()->upsert(
                        $rows,
                        ['work_order_id', 'part_name'],
                        ['quantity', 'remarks', 'updated_at']
                    );
                }
            }

            // ----- 4) Notifikasi dikirim setelah commit agar tidak double/kirim saat rollback
            $notif = $this->notif; // bind ke closure
            DB::afterCommit(function () use ($notif, $model) {
                $title = 'Permohonan pemeliharaan/perbaikan/improvement sedang diproses';
                $body = "Permohonan pemeliharaan/perbaikan/improvement {$model->wo_no} telah diproses, silakan diperiksa.";
                $notif->sendNotification($title, $body, $model->requested_by_id);
            });
            return $model->load(['services', 'spareparts']);
        });
    }
    public function signoff(int|string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $model = $this->model->lockForUpdate()->findOrFail($id);
            $user = Auth::user()->loadMissing('employee');
            $now = now();
            $payload = [
                'done_by_id' => $data['done_by_id'] ?? $user->id,
                'head_maintenance_id' => $data['head_maintenance_id'] ?? optional($user->employee)->approval_line_id,
                'requester_verify_id' => $data['requester_verify_id'] ?? $model->requested_by_id,
                'notes' => $data['notes'] ?? 'Tanpa keterangan.',
                'signed_at' => $data['signed_at'] ?? $now,
            ];
            $model->signoff()->updateOrCreate(
                ['work_order_id' => $model->id],
                $payload
            );
            if (isset($data['status'])) {
                $model->update(['status' => $data['status']]);
            }
            DB::afterCommit(function () use ($model, $data) {
                $statusText = match ($data['status'] ?? null) {
                    'OPEN' => 'dibuka',
                    'IN_PROGRESS' => 'dalam proses pengerjaan',
                    'ON_HOLD' => 'ditunda',
                    'DONE' => 'diselesaikan',
                    'CANCELED' => 'dibatalkan',
                    default => 'berubah',
                };
                $title = "Informasi permohonan WO {$model->wo_no}";
                $body = "Permohonan pemeliharaan/perbaikan/improvement {$model->wo_no} telah {$statusText}. Silakan diperiksa.";
                $this->notif->sendNotification($title, $body, $model->requested_by_id);
            });
            return $model->load('signoff');
        });
    }

    public function clearance(int|string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $model = $this->model->lockForUpdate()->findOrFail($id);

            // Helper bool yang ringan (mendukung variasi teks)
            $toBool = static function ($v): bool {
                if (is_bool($v))
                    return $v;
                if (is_int($v))
                    return $v === 1;
                if (is_numeric($v))
                    return ((int) $v) === 1;

                if (is_string($v)) {
                    $v = trim(mb_strtolower($v));
                    if (in_array($v, ['ya', 'y', 'yes', 'true', 'on', '1'], true))
                        return true;
                    if (in_array($v, ['tidak', 'tdk', 't', 'no', 'false', 'off', '0', '-'], true))
                        return false;
                }
                return (bool) $v;
            };

            // Ambil record clearance (hasOne)
            $clearance = $model->clearance()->firstOrNew();

            // Nilai baru (default true bila tidak dikirim)
            $newHygiene = array_key_exists('hygiene_clearance', $data) ? $toBool($data['hygiene_clearance']) : true;
            $newMaintenance = array_key_exists('maintenance_clearance', $data) ? $toBool($data['maintenance_clearance']) : true;

            // Cek perubahan untuk short-circuit
            $changed =
                (!$clearance->exists) ||
                ($clearance->hygiene_clearance !== $newHygiene) ||
                ($clearance->maintenance_clearance !== $newMaintenance);

            if (!$changed) {
                // Tidak ada perubahan → tidak ada write
                return $model->load('clearance');
            }

            // Isi hanya saat ada perubahan
            $clearance->fill([
                'hygiene_clearance' => $newHygiene,
                'maintenance_clearance' => $newMaintenance,
                'verified_by_id' => Auth::id(),
                'verified_at' => now(),
            ]);

            // Pastikan FK terisi saat create (jika tidak otomatis)
            if (!$clearance->exists) {
                $clearance->{$model->clearance()->getForeignKeyName()} = $model->getKey();
            }

            $clearance->save();

            // --- Notifikasi setelah commit
            $notif = $this->notif;
            $woNo = $model->wo_no;
            // Hindari lazy load di afterCommit: ambil nama sekarang
            $requesterName = optional($model->requestedBy)->name; // pastikan relasi ada di model
            $recipientId = $model->requested_by_id;

            DB::afterCommit(function () use ($notif, $woNo, $requesterName, $recipientId) {
                $title = "Informasi permohonan WO {$woNo}";
                $body = "Permohonan pemeliharaan/perbaikan/improvement {$woNo} telah diverifikasi dan dilakukan clearance oleh {$requesterName}. Silakan diperiksa.";
                $notif->sendNotification($title, $body, $recipientId);
            });

            return $model->load('clearance');
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
