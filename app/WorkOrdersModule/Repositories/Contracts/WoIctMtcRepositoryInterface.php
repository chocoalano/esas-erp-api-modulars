<?php

namespace App\WorkOrdersModule\Repositories\Contracts;

interface WoIctMtcRepositoryInterface
{
    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed;
    public function paginateTrashed(int $page, int $limit, array|null $filter): mixed;
    public function create(array $data): mixed;
    public function form(): mixed;
    public function find(int|string $id): mixed;
    public function update(int|string $id, array $data): mixed;
    public function service(int|string $id, array $data): mixed;
    public function signoff(int|string $id, array $data): mixed;
    public function clearance(int|string $id, array $data): mixed;
    public function delete(int|string $id): bool;
    public function forceDelete(int|string $id): bool;
    public function restore(int|string $id): mixed;
    public function import($file): mixed;
    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed;
}
