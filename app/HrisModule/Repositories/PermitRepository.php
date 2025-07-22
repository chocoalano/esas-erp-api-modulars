<?php

namespace App\HrisModule\Repositories;

use App\Console\Support\DoSpaces;
use App\Console\Support\FcmHandler;
use App\Console\Support\StringSupport;
use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\FcmModel;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\Permit;
use App\HrisModule\Models\PermitType;
use App\HrisModule\Models\TimeWorke;
use App\HrisModule\Models\UserTimeworkSchedule;
use App\HrisModule\Repositories\Contracts\PermitRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PermitRepository implements PermitRepositoryInterface
{
    protected $notif;
    public function __construct(protected Permit $model, FcmHandler $notif)
    {
        $this->notif = $notif;
    }

    public function generate_unique_numbers(int $permit_type_id)
    {
        $permit_type = PermitType::find($permit_type_id);
        if (!$permit_type) {
            return null;
        }

        // Pastikan inisial hanya mengandung huruf kapital tanpa karakter aneh
        $inisial = preg_replace('/[^A-Z]/', '', strtoupper(StringSupport::inisial($permit_type->type, 3)));

        // Jika inisial kosong setelah pembersihan, berikan default
        if (empty($inisial)) {
            $inisial = 'PRM'; // Bisa Anda ganti sesuai kebutuhan
        }

        $tahun_bulan = Carbon::now()->format('Ym');

        // Ambil record terakhir dengan format yang tepat
        $lastRecord = $this->model
            ->where('permit_numbers', 'like', "$inisial/$tahun_bulan/%")
            ->whereRaw("permit_numbers REGEXP ?", ["^$inisial/$tahun_bulan/[0-9]{3}$"])
            ->orderBy('permit_numbers', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastRecord) {
            $lastReference = explode('/', $lastRecord->permit_numbers);
            $lastNumber = intval(end($lastReference));
        }

        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        $reference = "$inisial/$tahun_bulan/$nextNumber";

        return $reference;
    }


    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->newQuery();
        $query->with([
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule',
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
    public function paginateListType(int $typeId, int $page, int $limit): mixed
    {
        $query = $this->model->newQuery();
        $query->with([
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule',
        ]);
        $query->where('permit_type_id', $typeId);
        $query->latest();
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->onlyTrashed()->newQuery();
        $query->with([
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule',
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

    public function form(
        ?int $companyId,
        ?int $deptId,
        ?int $userId,
        ?int $typeId,
        ?int $scheduleId
    ): array {
        $permitTypes = PermitType::all();
        $companies = Company::query()
            ->when($companyId, fn($q, $c) => $q->where('id', $c))
            ->get();
        $departments = Departement::query()
            ->when($companyId, fn($q, $c) => $q->where('company_id', $c))
            ->get();
        $users = User::query()
            ->when($companyId, fn($q, $c) => $q->where('company_id', $c))
            ->when(
                $deptId,
                fn($q, $d) =>
                $q->whereHas(
                    'employee',
                    fn($emp) =>
                    $emp->where('departement_id', $d)
                )
            )
            ->get();
        $targetUser = $userId ?: auth()->id();
        $schedules = UserTimeworkSchedule::with('timework')
            ->where('user_id', $targetUser)
            ->when($scheduleId, fn($q, $s) => $q->where('id', $s))
            ->get();
        $timeworks = TimeWorke::query()
            ->when($companyId, fn($q, $c) => $q->where('company_id', $c))
            ->when($deptId, fn($q, $d) => $q->where('departemen_id', $d))
            ->get();
        $permitNumber = $typeId
            ? $this->generate_unique_numbers($typeId)
            : null;

        return [
            'permit_types' => $permitTypes,
            'companies' => $companies,
            'departments' => $departments,
            'users' => $users,
            'schedules' => $schedules,
            'timeworks' => $timeworks,
            'permit_numbers' => $permitNumber,
        ];
    }

    public function fileDelete($id): mixed
    {
        try {
            $model = $this->model->findOrFail($id);
            DoSpaces::remove($model->file);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function fileUpload(UploadedFile $file): mixed
    {
        $filename = now()->format('YmdHis');
        $upload = DoSpaces::upload($file, 'permits', $filename);
        return $upload['path'];
    }

    public function create(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            // Buat model permit
            $model = $this->model->create($data);
            // Ambil data permit type
            $permitType = PermitType::find($data['permit_type_id']);
            if (!$permitType) {
                throw new \Exception('Permit Type not found.');
            }
            // Ambil user pemohon
            $user = User::find($data['user_id']);
            if (!$user || !$user->employee) {
                throw new \Exception('User or employee data not found.');
            }
            // Ambil user HR
            $authorizedHr = User::where('nip', '24020001')->first();
            // Inisialisasi approval list
            $approvals = [];

            if ($permitType->approve_line && $user->employee->approval_line_id) {
                $approvals[] = [
                    'user_id' => $user->employee->approval_line_id,
                    'user_type' => 'line',
                ];
            }
            if ($permitType->approve_manager && $user->employee->approval_manager_id) {
                $approvals[] = [
                    'user_id' => $user->employee->approval_manager_id,
                    'user_type' => 'manager',
                ];
            }
            if ($permitType->approve_hr && $authorizedHr) {
                $approvals[] = [
                    'user_id' => $authorizedHr->id,
                    'user_type' => 'hrga',
                ];
            }
            // Simpan approval jika ada
            if (!empty($approvals)) {
                $model->approvals()->createMany($approvals);
            }
            $userIds = array_column($approvals, 'user_id');
            $fcmTokens = FcmModel::whereIn('user_id', $userIds)->get()->pluck('device_token');
            $this->notif->sendToMultiple($fcmTokens->toArray(), "Permohonan Izin/Cuti/Dispensasi", "Pengguna {$user->name}-{$user->nip} mengajukan permohonan, silahkan periksa.", $model->toArray());
            return $model;
        });
    }

    public function find(int|string $id): mixed
    {
        $model = $this->model
            ->with([
                'user',
                'user.employee',
                'permitType',
                'approvals',
                'userTimeworkSchedule'
            ])
            ->findOrFail($id);

        return $model;
    }
    public function approval_process(?int $id, ?int $userId, array $data): mixed
    {
        $model = $this->model
            ->with('approvals')
            ->where('id', $id)
            ->whereHas('approvals', function ($app) use ($userId) {
                $app->where('user_id', $userId);
            })
            ->first();

        if (!$model) {
            return null; // atau throw jika lebih sesuai
        }

        // Ambil approval milik user tertentu
        $approval = $model->approvals->firstWhere('user_id', $userId);

        if ($approval) {
            $approval->update($data);
        }

        return $model;
    }

    public function update(int|string $id, array $data): mixed
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);
        return $model;
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
