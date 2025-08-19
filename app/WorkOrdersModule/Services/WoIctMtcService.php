<?php

namespace App\WorkOrdersModule\Services;

use App\WorkOrdersModule\Repositories\Contracts\WoIctMtcRepositoryInterface;

class WoIctMtcService
{
    public function __construct(protected WoIctMtcRepositoryInterface $repo) {}

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    public function paginateTrashed(int $page, int $limit, array|null $filter): mixed
    {
        return $this->repo->paginateTrashed($page, $limit, $filter);
    }

    public function form(): mixed
    {
        return $this->repo->form();
    }

    public function create(array $data): mixed
    {
        return $this->repo->create($data);
    }

    public function update(int|string $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }
    public function service(int|string $id, array $data): mixed
    {
        return $this->repo->service($id, $data);
    }
    public function signoff(int|string $id, array $data): mixed
    {
        return $this->repo->signoff($id, $data);
    }
    public function clearance(int|string $id, array $data): mixed
    {
        return $this->repo->clearance($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    public function forceDelete(int|string $id): bool
    {
        return $this->repo->forceDelete($id);
    }

    public function restore(int|string $id): mixed
    {
        return $this->repo->restore($id);
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
