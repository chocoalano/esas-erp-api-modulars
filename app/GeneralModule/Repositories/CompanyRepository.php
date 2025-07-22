<?php

namespace App\GeneralModule\Repositories;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(protected Company $model)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model;
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

    public function paginateTrashed(int $page, int $limit, array|string|null $search = null, array|null $sortBy = null): mixed
    {
        // Memulai query dengan hanya data yang dihapus lunak
        $query = $this->model->onlyTrashed();

        // --- Penanganan Pencarian (Search) ---
        if (!empty($search)) {
            if (is_string($search)) {
                $searchableFields = ['name', 'radius', 'full_address'];
                $query->where(function (Builder $q) use ($search, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', '%' . $search . '%');
                    }
                });
            }
            // Jika $search adalah array, asumsikan itu adalah filter spesifik per kolom
            else if (is_array($search)) {
                foreach ($search as $field => $value) {
                    if ($value !== null && $value !== '') {
                        if (in_array($field, ['name', 'full_address'])) { // Contoh kolom string
                            $query->where($field, 'like', '%' . $value . '%');
                        } elseif (in_array($field, ['latitude', 'longitude', 'radius'])) { // Contoh kolom numerik
                            $query->where($field, $value); // Atau gunakan >=, <= jika itu rentang
                        } elseif (in_array($field, ['created_at', 'updated_at', 'start', 'end'])) {
                            if ($field === 'start') {
                                $query->whereDate('created_at', '>=', $value); // Atau kolom tanggal yang sesuai
                            } elseif ($field === 'end') {
                                $query->whereDate('created_at', '<=', $value); // Atau kolom tanggal yang sesuai
                            } else {
                                $query->whereDate($field, $value);
                            }
                        }
                    }
                }
            }
        }
        if (!empty($sortBy) && is_array($sortBy)) {
            if (isset($sortBy[0]['key']) && isset($sortBy[0]['order'])) {
                foreach ($sortBy as $sortItem) {
                    $field = $sortItem['key'];
                    $direction = strtolower($sortItem['order']) === 'desc' ? 'desc' : 'asc';
                    $query->orderBy($field, $direction);
                }
            }
            else {
                foreach ($sortBy as $field => $direction) {
                    $direction = is_string($direction) && strtolower($direction) === 'desc' ? 'desc' : 'asc';
                    $query->orderBy($field, $direction);
                }
            }
        } else {
            $query->latest('deleted_at');
        }
        // --- Paginasi ---
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
