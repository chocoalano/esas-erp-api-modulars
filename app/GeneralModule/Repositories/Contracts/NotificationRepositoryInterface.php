<?php

namespace App\GeneralModule\Repositories\Contracts;

interface NotificationRepositoryInterface
{
    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed;
    public function find(int|string $id): mixed;
    public function delete(int|string $id): bool;
}
