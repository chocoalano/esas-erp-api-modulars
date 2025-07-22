<?php

namespace App\HrisModule\Repositories;

use App\GeneralModule\Models\Company;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\JobLevel;
use App\HrisModule\Repositories\Contracts\JobLevelRepositoryInterface;

class JobLevelRepository implements JobLevelRepositoryInterface
{
    public function __construct(protected JobLevel $model)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->newQuery();
        $query->with([
            'company',
            'departement',
            'employees.user',
        ]);
        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                if (isset($search['createdAt']) && $search['name']) {
                    $q->where('created_at', $search['createdAt']);
                }
                if (isset($search['updatedAt']) && $search['name']) {
                    $q->where('created_at', $search['updatedAt']);
                }
                // Pencarian di relasi 'company'
                if (isset($search['company']) && $search['company']) {
                    $q->whereHas('company', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search['company_name'] . '%');
                    });
                }
                // Pencarian di relasi 'departement'
                if (isset($search['name']) && $search['name']) {
                    $q->whereHas('departement', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search['name'] . '%');
                    });
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

    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->onlyTrashed()->newQuery();
        $query->with([
            'company',
            'departement',
            'employees.user',
        ]);
        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                if (isset($search['createdAt']) && $search['name']) {
                    $q->where('created_at', $search['createdAt']);
                }
                if (isset($search['updatedAt']) && $search['name']) {
                    $q->where('created_at', $search['updatedAt']);
                }
                // Pencarian di relasi 'company'
                if (isset($search['company']) && $search['company']) {
                    $q->whereHas('company', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search['company_name'] . '%');
                    });
                }
                // Pencarian di relasi 'departement'
                if (isset($search['name']) && $search['name']) {
                    $q->whereHas('departement', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search['name'] . '%');
                    });
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

    public function form(?int $companyId): mixed
    {
        return [
            'company' => Company::all(),
            'departemen' => $companyId ? Departement::where('company_id', $companyId)->get() : Departement::all()
        ];
    }

    public function create(array $data): mixed
    {
        return $this->model->create($data);
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
