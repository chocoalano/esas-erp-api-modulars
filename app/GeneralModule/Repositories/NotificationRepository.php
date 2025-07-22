<?php

namespace App\GeneralModule\Repositories;

use App\GeneralModule\Models\Notification;
use App\GeneralModule\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(protected Notification $model)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->query();

        // Eager loading relasi
        $query->with('notifiable');
        $query->where('notifiable_id', Auth::id());
        // Penerapan search multi-field
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach ($search as $field => $value) {
                    if (!is_null($value) && $value !== '') {
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

    public function find(int|string $id): mixed
    {
        $model = $this->model
            ->findOrFail($id);
        $model->update(['read_at' => Carbon::now()]);

        return $model;
    }

    public function delete(int|string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }
}
