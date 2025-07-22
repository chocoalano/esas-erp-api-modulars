<?php

namespace App\HrisModule\Repositories;

use App\GeneralModule\Models\Company;
use App\HrisModule\Models\Departement;
use App\HrisModule\Repositories\Contracts\DepartementRepositoryInterface;

class DepartementRepository implements DepartementRepositoryInterface
{
    public function __construct(protected Departement $model)
    {
    }

    public function paginate(int $page, int $limit, array|string|null $search = null, array|null $sortBy = null): mixed
    {
        $query = $this->model->newQuery();

        $query->with([
            'company',
            'timeWorks',
            'jobPositions',
            'jobLevels',
            'employees.user'
        ]);

        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach ($search as $field => $value) {
                    if ($value !== null && $value !== '') { // Periksa juga string kosong
                        $q->orWhere($field, 'like', '%' . $value . '%');
                    }
                }
            });
        }

        // Penerapan sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                if (isset($sort['key'])) { // Pastikan kunci 'key' ada
                    $order = strtolower($sort['order'] ?? 'asc'); // Case-insensitive order
                    $query->orderBy($sort['key'], ($order === 'desc') ? 'desc' : 'asc');
                }
            }
        } else {
            $query->latest(); // Mengurutkan berdasarkan 'created_at' DESC secara default
        }
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function paginateTrashed(int $page, int $limit, array|string|null $search = null, array|null $sortBy = null): mixed
    {
        $query = $this->model->onlyTrashed();

        $query->with([
            'company',
            'timeWorks',
            'jobPositions',
            'jobLevels',
            'employees.user'
        ]);

        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach ($search as $field => $value) {
                    if ($value !== null && $value !== '') { // Periksa juga string kosong
                        $q->orWhere($field, 'like', '%' . $value . '%');
                    }
                }
            });
        }

        // Penerapan sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                if (isset($sort['key'])) { // Pastikan kunci 'key' ada
                    $order = strtolower($sort['order'] ?? 'asc'); // Case-insensitive order
                    $query->orderBy($sort['key'], ($order === 'desc') ? 'desc' : 'asc');
                }
            }
        } else {
            $query->latest(); // Mengurutkan berdasarkan 'created_at' DESC secara default
        }
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function form(): mixed
    {
        //siapkan keperluan form untuk resources ini disini
        return [
            "company"=>Company::all()
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
