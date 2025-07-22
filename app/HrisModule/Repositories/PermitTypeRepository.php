<?php

namespace App\HrisModule\Repositories;

use App\HrisModule\Models\PermitType;
use App\HrisModule\Repositories\Contracts\PermitTypeRepositoryInterface;

class PermitTypeRepository implements PermitTypeRepositoryInterface
{
    public function __construct(protected PermitType $model)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        // Selalu mulai dengan instance query builder baru dari model
        $query = $this->model->query();
        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach ($search as $field => $value) {
                    // Pastikan $value tidak kosong sebelum menerapkan where clause
                    if (!empty($value)) {
                        $q->orWhere($field, 'like', '%' . $value . '%');
                    }
                }
            });
        }
        // Penerapan sorting
        if (!empty($sortBy)) {
            // Iterasi setiap kriteria sorting yang diberikan
            foreach ($sortBy as $sort) {
                // Pastikan 'key' ada. 'order' default ke 'asc' jika tidak ada atau tidak valid.
                if (isset($sort['key'])) {
                    $sortOrder = $sort['order'] ?? 'asc'; // Default ke 'asc' jika 'order' tidak ada
                    // Pastikan order adalah 'asc' atau 'desc'
                    $sortOrder = (strtolower($sortOrder) === 'desc') ? 'desc' : 'asc';
                    $query->orderBy($sort['key'], $sortOrder);
                }
            }
        } else {
            // Default sorting jika tidak ada kriteria sortBy yang diberikan
            // Gunakan primary key (biasanya 'id') untuk default yang konsisten
            $query->orderBy('id', 'desc');
        }
        // Return hasil pagination
        return $query->paginate($limit, ['*'], 'page', $page);
    }
    public function list(): mixed
    {
        return $this->model
            ->newQuery()                   // mulai query baru
            ->where('show_mobile', true)   // filter
            ->get();
    }

    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->onlyTrashed();

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

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function form(): mixed
    {
        //siapkan keperluan form untuk resources ini disini
        return [];
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
