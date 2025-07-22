<?php

namespace App\GeneralModule\Repositories;

use App\GeneralModule\Models\Permission;
use App\GeneralModule\Models\Role;
use App\GeneralModule\Models\User;
use App\GeneralModule\Repositories\Contracts\RoleRepositoryInterface;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(protected Role $model, protected Permission $permission, protected User $user)
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

    public function create(array $data): mixed
    {
        $model = $this->model->create([
            "name" => $data['name'],
            "guard_name" => 'web',
        ]);
        $model->permissions()->sync($data['permission']);
        $model->users()->sync($data['user_id']);
        return $model;
    }

    public function form(): mixed
    {
        return [
            'users' => $this->user->all(),
            'permissions' => $this->permission->getGroupedPermissions(),
        ];
    }

    public function find(int|string $id): mixed
    {
        $model = $this
            ->model
            ->with(['permissions', 'users']) // Muat relasi
            ->findOrFail($id);

        return $model;
    }

    public function update(int|string $id, array $data): mixed
    {
        $model = $this->model->findOrFail($id);
        $model->update([
            "name" => $data['name'],
            "guard_name" => 'web',
        ]);
        $model->permissions()->sync($data['permission']);
        $model->users()->sync($data['user_id']);
        return $model;
    }

    public function delete(int|string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
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
