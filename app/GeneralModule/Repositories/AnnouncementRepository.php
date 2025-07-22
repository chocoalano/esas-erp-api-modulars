<?php

namespace App\GeneralModule\Repositories;

use App\GeneralModule\Models\Announcement;
use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\GeneralModule\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function __construct(protected Announcement $model)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->newQuery();
        $query->with('company');
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

    public function getAllActive(): mixed{
        return $this->model->where('status', 1)->get();
    }

    public function form(): mixed
    {
        //siapkan keperluan form untuk resources ini disini
        return [
            "company" => Company::all()
        ];
    }

    public function create(array $data): mixed
    {
        $hrd = User::whereHas('employee.departement', function ($query) {
            $query->where('name', 'like', '%HRGA%');
        })->firstOrFail();

        $data['user_id'] = !Auth::user()->hasRole('super_admin') ? Auth::id() : $hrd->id;
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
