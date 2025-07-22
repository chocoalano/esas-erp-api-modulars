<?php

namespace App\GeneralModule\Repositories;

use App\Console\Support\DoSpaces;
use App\GeneralModule\Models\ActivityLog;
use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\Role;
use App\GeneralModule\Models\User;
use App\GeneralModule\Repositories\Contracts\UserRepositoryInterface;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\JobLevel;
use App\HrisModule\Models\JobPosition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class UserRepository implements UserRepositoryInterface
{
    public function __construct(protected User $model, protected ActivityLog $activityLogModel)
    {
    }

    public function paginate(int $page, int $limit, array|null $search, array|null $sortBy, int|string|null $departement_id): mixed
    {
        $query = $this->model->with([
            'company',
            'details',
            'employee',
            'employee.departement',
            'employee.jobPosition',
            'employee.jobLevel',
        ]);

        // Filter langsung pada User
        $query->when($search['company'] ?? null, fn($q, $value) => $q->where('company_id', $value))
            ->when($search['nip'] ?? null, fn($q, $value) => $q->where('nip', $value))
            ->when($search['name'] ?? null, fn($q, $value) => $q->where('name', $value))
            ->when($search['status'] ?? null, fn($q, $value) => $q->where('status', $value))
            ->when($search['createdAt'] ?? null, fn($q, $value) => $q->whereDate('created_at', $value))
            ->when($search['updatedAt'] ?? null, fn($q, $value) => $q->whereDate('updated_at', $value))
            ->when(
                isset($search['startRange'], $search['endRange']),
                fn($q) => $q->whereBetween('updated_at', [$search['startRange'], $search['endRange']])
            );

        // Filter berdasarkan relasi employee
        if (!empty($search['departemen']) || !empty($search['position']) || !empty($search['level'])) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->when($search['departemen'] ?? null, fn($q, $val) => $q->where('departement_id', $val))
                    ->when($search['position'] ?? null, fn($q, $val) => $q->where('job_position_id', $val))
                    ->when($search['level'] ?? null, fn($q, $val) => $q->where('job_level_id', $val));
            });
        }

        // Sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginate
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function paginateTrashed(int $page, int $limit, array|null $search, array|null $sortBy): mixed
    {
        $query = $this->model->onlyTrashed();
        $query->with([
            'company',
            'details',
            'employee',
            'employee.departement',
            'employee.jobPosition',
            'employee.jobLevel',
        ]);

        $query->when($search['company'] ?? null, fn($q, $value) => $q->where('company_id', $value))
            ->when($search['nip'] ?? null, fn($q, $value) => $q->where('nip', $value))
            ->when($search['name'] ?? null, fn($q, $value) => $q->where('name', $value))
            ->when($search['status'] ?? null, fn($q, $value) => $q->where('status', $value))
            ->when($search['createdAt'] ?? null, fn($q, $value) => $q->whereDate('created_at', $value))
            ->when($search['updatedAt'] ?? null, fn($q, $value) => $q->whereDate('updated_at', $value))
            ->when(
                isset($search['startRange'], $search['endRange']),
                fn($q) => $q->whereBetween('updated_at', [$search['startRange'], $search['endRange']])
            );

        // Filter berdasarkan relasi employee
        if (!empty($search['departemen']) || !empty($search['position']) || !empty($search['level'])) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->when($search['departemen'] ?? null, fn($q, $val) => $q->where('departement_id', $val))
                    ->when($search['position'] ?? null, fn($q, $val) => $q->where('job_position_id', $val))
                    ->when($search['level'] ?? null, fn($q, $val) => $q->where('job_level_id', $val));
            });
        }
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function form(int|string|null $company_id, int|string|null $dept_id, int|string|null $post_id, int|string|null $lvl_id): mixed
    {
        // Ambil semua roles
        $roles = Role::all();

        // Ambil semua companies
        $companies = Company::all();

        // Filter departement
        $departements = Departement::query()
            ->when($company_id, fn($query) => $query->where('company_id', $company_id))
            ->get();

        // Filter job positions
        $jobPositions = JobPosition::query()
            ->when($company_id, fn($query) => $query->where('company_id', $company_id))
            ->when($dept_id, fn($query) => $query->where('departement_id', $dept_id))
            ->get();

        // Filter job levels
        $jobLevels = JobLevel::query()
            ->when($company_id, fn($query) => $query->where('company_id', $company_id))
            ->when($dept_id, fn($query) => $query->where('departement_id', $dept_id))
            ->get();

        // Filter users
        $users = User::query()
            ->when($company_id, fn($query) => $query->where('company_id', $company_id))
            ->when($dept_id, fn($query) => $query->whereHas('employee', fn($emp) => $emp->where('departement_id', $dept_id)))
            ->when($post_id, fn($query) => $query->whereHas('employee', fn($emp) => $emp->where('job_position_id', $post_id)))
            ->when($lvl_id, fn($query) => $query->whereHas('employee', fn($emp) => $emp->where('job_level_id', $lvl_id)))
            ->get();

        return [
            'roles' => $roles,
            'companies' => $companies,
            'departements' => $departements,
            'job_positions' => $jobPositions,
            'job_levels' => $jobLevels,
            'users' => $users,
        ];
    }

    public function create(array $data): mixed
    {
        DB::beginTransaction();
        try {
            $model = new $this->model;
            $model->company_id = $data['company_id'];
            $model->name = $data['name'];
            $model->nip = $data['nip'];
            $model->email = $data['email'];
            $model->status = $data['status'];
            $model->password = Hash::make($data['password']);
            if (!empty($data['avatar'])) {
                $model->avatar = $data['avatar'];
            }
            $model->save();
            // Buat relasi jika tersedia
            if (isset($data['details'])) {
                $model->details()->updateOrCreate([], $data['details']);
            }
            if (isset($data['address'])) {
                $model->address()->updateOrCreate([], $data['address']);
            }
            if (isset($data['salaries'])) {
                $model->salaries()->updateOrCreate([], $data['salaries']);
            }
            if (isset($data['employee'])) {
                $model->employee()->updateOrCreate([], $data['employee']);
            }
            DB::commit();
            return $model;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function find(int|string $id): mixed
    {
        $model = $this->model
            ->with([
                'company',
                'details',
                'address',
                'salaries',
                'families',
                'formalEducations',
                'informalEducations',
                'workExperiences',
                'employee',
                'employee.approval_line',
                'employee.approval_manager',
                'employee.departement',
                'employee.jobPosition',
                'employee.jobLevel'
            ])
            ->findOrFail($id);

        return $model;
    }
    public function simple_update(int|string $id, array $data): mixed
    {
        return $this->model->find($id)->update($data);
    }
    public function update(int|string $id, array $data): mixed
    {
        DB::beginTransaction();
        try {
            $model = $this->model->findOrFail($id);
            $model->company_id = $data['company_id'];
            $model->name = $data['name'];
            $model->nip = $data['nip'];
            $model->email = $data['email'];
            $model->status = $data['status'];
            $model->password = Hash::make($data['password']);
            if (!empty($data['avatar'])) {
                $model->avatar = $data['avatar'];
            }
            $model->save();
            // Buat relasi jika tersedia
            if (isset($data['details'])) {
                $model->details()->updateOrCreate([], $data['details']);
            }
            if (isset($data['address'])) {
                $model->address()->updateOrCreate([], $data['address']);
            }
            if (isset($data['salaries'])) {
                $model->salaries()->updateOrCreate([], $data['salaries']);
            }
            if (isset($data['employee'])) {
                $model->employee()->updateOrCreate([], $data['employee']);
            }
            DB::commit();
            return $model;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
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

    public function import($file): mixed
    {
        // Implement logic for importing data
        return true;
    }
    public function avatarDelete($id): mixed
    {
        try {
            $user = $this->model->findOrFail($id);
            DoSpaces::remove($user->avatar);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function avatarUpload(UploadedFile $file): mixed
    {
        $filename = now()->format('YmdHis');
        $upload = DoSpaces::upload($file, 'avatars', $filename);
        return $upload['path'];
    }
    public function export(
        ?string $name = null,
        ?int $company = null,
        ?int $departemen = null,
        ?int $position = null,
        ?int $level = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed {
        ini_set('memory_limit', '512M');
        // Build query
        $query = User::with([
            'company',
            'employee.departement',
            'employee.jobPosition',
            'employee.jobLevel',
        ]);

        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if (!empty($company)) {
            $query->where('company_id', $company);
        }
        if (!empty($departemen)) {
            $query->whereHas('employee', function ($emp) use ($departemen) {
                $emp->where('departement_id', $departemen);
            });
        }
        if (!empty($position)) {
            $query->whereHas('employee', function ($emp) use ($position) {
                $emp->where('job_position_id', $position);
            });
        }
        if (!empty($level)) {
            $query->whereHas('employee', function ($emp) use ($level) {
                $emp->where('job_level_id', $level);
            });
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

    public function history_logs(
        ?int $userId,
        int $page,
        int $limit,
        ?array $search,
        ?array $sortBy
    ): array {
        // Start a new query from the model instance
        $query = $this->activityLogModel->newQuery();

        // Always eager load the user relationship if needed for display
        $query->with('user');

        // Apply user_id filter if provided
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Apply filters dynamically from the $search array
        // Use a loop for more generic filtering based on array keys
        if (!empty($search)) {
            foreach (['method', 'action', 'model_type', 'user_agent'] as $field) {
                $query->when(isset($search[$field]) && !empty($search[$field]), function ($q) use ($field, $search) {
                    $q->where($field, $search[$field]);
                });
            }
        }

        // Apply date range filter
        $query->when(
            isset($search['startRange']) && !empty($search['startRange']) &&
            isset($search['endRange']) && !empty($search['endRange']),
            function ($q) use ($search) {
                // Ensure dates are correctly formatted/parsed if coming from frontend
                // For Carbon, it will attempt to parse, but explicitly casting might be safer
                $start = \Carbon\Carbon::parse($search['startRange'])->startOfDay();
                $end = \Carbon\Carbon::parse($search['endRange'])->endOfDay();
                $q->whereBetween('created_at', [$start, $end]); // Often activity logs are sorted by created_at
            }
        );

        // Sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                // Add validation for 'key' and 'order'
                if (isset($sort['key']) && isset($sort['order'])) {
                    $order = strtolower($sort['order']) === 'asc' ? 'asc' : 'desc'; // Sanitize order
                    $query->orderBy($sort['key'], $order);
                }
            }
        } else {
            // Default sorting if no sortBy is provided
            $query->orderBy('created_at', 'desc');
        }

        // Execute pagination
        $paginate = $query->paginate($limit, ['*'], 'page', $page);

        // Fetch distinct filter options
        // Optimize these queries if your table is very large.
        // Consider caching these values if they don't change often.
        $distinctMethods = $this->activityLogModel->distinct('method')->pluck('method');
        $distinctActions = $this->activityLogModel->distinct('action')->pluck('action');
        // Use 'model_type' directly, as the frontend expects it for v-model.
        $distinctModelTypes = $this->activityLogModel->distinct('model_type')->pluck('model_type');


        return [
            "data" => $paginate->items(), // Return items directly
            "current_page" => $paginate->currentPage(),
            "per_page" => $paginate->perPage(),
            "total" => $paginate->total(),
            "last_page" => $paginate->lastPage(),
            "methodOptions" => $distinctMethods->toArray(), // Convert to array for consistent frontend handling
            "actionOptions" => $distinctActions->toArray(),
            "modelTypeOptions" => $distinctModelTypes->toArray(),
        ];
    }
}
