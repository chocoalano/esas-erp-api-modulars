<?php

namespace App\HrisModule\Repositories\Contracts;

interface JobLevelRepositoryInterface
{
    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed;
    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): mixed;
    public function create(array $data): mixed;
    public function form(?int $companyId): mixed;
    public function find(int|string $id): mixed;
    public function update(int|string $id, array $data): mixed;
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
