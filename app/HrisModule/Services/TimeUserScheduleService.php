<?php

namespace App\HrisModule\Services;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\TimeWorke;
use App\HrisModule\Repositories\Contracts\TimeUserScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TimeUserScheduleService
{
    public function __construct(protected TimeUserScheduleRepositoryInterface $repo)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    public function get_table_filter_attribute(?int $company_id, ?int $dept_id): mixed
    {
        $companies = Company::all();

        // Initialize collections
        $departements = new Collection();
        $time = new Collection();
        $users = new Collection();

        $departementQuery = Departement::query();
        if ($company_id) {
            $departementQuery->where('company_id', $company_id);
        }
        $departements = $departementQuery->get();

        $timeQuery = TimeWorke::query();
        if ($company_id || $dept_id) {
            $timeQuery->where([
                'company_id' => $company_id,
                'departemen_id' => $dept_id
            ]);
        }
        $time = $timeQuery->get();

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
        $users = $userQuery->get(); // Get only the 'nip' values as a flat array

        return [
            'companies' => $companies,
            'departements' => $departements,
            'timeworkes' => $time,
            'users' => $users,
        ];
    }
    public function form(?int $companyId, ?int $deptId): mixed
    {
        return $this->repo->form($companyId, $deptId);
    }

    public function create(array $data): mixed
    {
        return $this->repo->create($data);
    }

    public function update(int|string $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    public function export(
        $name,
        $createdAt,
        $updatedAt,
        $startRange,
        $endRange,
    ): mixed {
        return $this->repo->export(
            $name,
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
        $query = $this->repo->find($id);
        return [
            "company_id"=>$query->user->company_id,
            "departement_id"=>$query->user->employee->departement_id,
            "user_id"=>$query->user_id,
            "time_work_id"=>$query->time_work_id,
            "work_day"=>$query->work_day,
        ];
    }
}
