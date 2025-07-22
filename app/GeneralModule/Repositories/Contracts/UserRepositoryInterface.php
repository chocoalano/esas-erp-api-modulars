<?php

namespace App\GeneralModule\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface UserRepositoryInterface
{
    public function paginate(int $page, int $limit, array|null $search, array|null $sortBy, int|string|null $departement_id): mixed;
    public function paginateTrashed(int $page, int $limit, array|null $search, array|null $sortBy): mixed;
    public function create(array $data): mixed;
    public function form(int|string|null $company_id, int|string|null $dept_id, int|string|null $post_id, int|string|null $lvl_id): mixed;
    public function find(int|string $id): mixed;
    public function simple_update(int|string $id, array $data): mixed;
    public function update(int|string $id, array $data): mixed;
    public function delete(int|string $id): bool;
    public function forceDelete(int|string $id): bool;
    public function restore(int|string $id): mixed;
    public function import($file): mixed;
    public function avatarDelete(int|string $id): mixed;
    public function avatarUpload(UploadedFile $file): mixed;
    public function history_logs(
        ?int $userId,
        int $page,
        int $limit,
        ?array $search,
        ?array $sortBy
    ): mixed;
    public function export(
        ?string $name = null,
        ?int $company = null,
        ?int $departemen = null,
        ?int $position = null,
        ?int $level = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed;
}
