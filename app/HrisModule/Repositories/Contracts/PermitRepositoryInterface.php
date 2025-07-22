<?php

namespace App\HrisModule\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface PermitRepositoryInterface
{
    public function generate_unique_numbers(int $permit_type_id);
    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed;
    public function paginateListType(int $typeId, int $page, int $limit): mixed;
    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): mixed;
    public function create(array $data): mixed;
    public function form(
        ?int $companyId,
        ?int $deptId,
        ?int $userId,
        ?int $typeId,
        ?int $scheduleId
    ): mixed;
    public function fileDelete(int|string $id): mixed;
    public function fileUpload(UploadedFile $file): mixed;
    public function find(int|string $id): mixed;
    public function approval_process(?int $id, ?int $userId, array $data): mixed;
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
