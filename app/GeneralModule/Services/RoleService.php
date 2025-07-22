<?php

namespace App\GeneralModule\Services;

use App\GeneralModule\Repositories\Contracts\RoleRepositoryInterface;

class RoleService
{
    public function __construct(protected RoleRepositoryInterface $repo) {}

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    public function form(): mixed
    {
        return $this->repo->form();
    }
    public function edit(int $id): mixed
    {
        return $this->repo->find($id);
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
        return $this->repo->find($id);
    }
}
