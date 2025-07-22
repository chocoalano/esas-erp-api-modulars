<?php

namespace App\GeneralModule\Services;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\GeneralModule\Repositories\Contracts\UserRepositoryInterface;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\JobLevel;
use App\HrisModule\Models\JobPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class UserService
{
    public function __construct(protected UserRepositoryInterface $repo)
    {
    }

    public function paginateAdmin(int $page, int $limit, array|null $search, array|null $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy, null);
    }
    public function paginateMember(int $page, int $limit, array|null $search, array|null $sortBy, int|string $departement_id): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy, $departement_id);
    }
    public function get_table_filter_attribute(
        int|null $company_id,
        int|null $dept_id,
        int|null $post_id,
        int|null $lvl_id
    ): array {
        $companies = Company::all();

        // Initialize collections
        $departements = new Collection();
        $positions = new Collection();
        $levels = new Collection();
        $users = new Collection();

        $departementQuery = Departement::query();
        if ($company_id) {
            $departementQuery->where('company_id', $company_id);
        }
        $departements = $departementQuery->get();

        $positionQuery = JobPosition::query();
        if ($company_id || $dept_id) {
            $positionQuery->whereHas('departement', function ($query) use ($company_id, $dept_id) {
                $query->where([
                    'company_id' => $company_id,
                    'departement_id' => $dept_id
                ]);
            });
        }
        $positions = $positionQuery->get();

        $levelQuery = JobLevel::query();
        if ($post_id) {
            $levelQuery->whereHas('departement', function ($query) use ($dept_id) {
                $query->where('id', $dept_id);
            });
        }
        $levels = $levelQuery->get();

        // Filter User NIPs by Company, Department, Position, and Level
        $userQuery = User::query();
        if ($company_id) {
            $userQuery->where('company_id', $company_id);
        }
        if ($dept_id) {
            $userQuery->whereHas('employee', function ($empQuery) use ($dept_id) {
                $empQuery->where('departement_id', $dept_id);
            });
        }
        if ($post_id) {
            $userQuery->whereHas('employee', function ($empQuery) use ($post_id) {
                $empQuery->where('job_position_id', $post_id);
            });
        }
        if ($lvl_id) {
            $$userQuery->whereHas('employee', function ($empQuery) use ($lvl_id) {
                $empQuery->where('job_level_id', $lvl_id);
            });
        }
        $users = $userQuery->get(); // Get only the 'nip' values as a flat array

        return [
            'companies' => $companies,
            'departements' => $departements,
            'positions' => $positions,
            'levels' => $levels,
            'users' => $users,
        ];
    }

    public function paginateTrashed(int $page, int $limit, array|null $search, array|null $sortBy): mixed
    {
        return $this->repo->paginateTrashed($page, $limit, $search, $sortBy);
    }

    public function form(?int $companyId, ?int $deptId, ?int $postId, ?int $lvlId): mixed
    {
        return $this->repo->form(
            $companyId,
            $deptId,
            $postId,
            $lvlId
        );
    }

    public function create(array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $upload = $this->repo->avatarUpload($file);
            $data['avatar'] = $upload;
        }
        return $this->repo->create($data);
    }

    public function simple_update(int|string $id, array $data): mixed
    {
        return $this->repo->simple_update($id, $data);
    }
    public function update(int|string $id, array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $this->repo->avatarDelete($id);
            $upload = $this->repo->avatarUpload($file);
            $data['avatar'] = $upload;
        }
        return $this->repo->update($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    public function forceDelete(int|string $id): bool
    {
        $this->repo->avatarDelete($id);
        return $this->repo->forceDelete($id);
    }

    public function restore(int|string $id): mixed
    {
        return $this->repo->restore($id);
    }

    public function export(
        $name,
        $company,
        $departemen,
        $position,
        $level,
        $createdAt,
        $updatedAt,
        $startRange,
        $endRange,
    ): mixed {
        return $this->repo->export(
            $name,
            $company,
            $departemen,
            $position,
            $level,
            $createdAt,
            $updatedAt,
            $startRange,
            $endRange,
        );
    }

    public function import($file): mixed
    {
        return $this->repo->import($file);
    }

    public function find($id): mixed
    {
        return $this->repo->find($id);
    }
    public function logs(int $id, int $page, int $limit, ?array $search, ?array $sortBy): mixed
    {
        return $this->repo->history_logs($id, $page, $limit, $search, $sortBy);
    }
}
