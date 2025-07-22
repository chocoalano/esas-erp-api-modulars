<?php

namespace App\GeneralModule\Services;

use App\GeneralModule\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(protected NotificationRepositoryInterface $repo) {}

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    public function find($id): mixed
    {
        return $this->repo->find($id);
    }
}
