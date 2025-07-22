<?php

namespace App\GeneralModule\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface BugReportRepositoryInterface
{
    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed;
    public function create(array $data): mixed;
    public function form(): mixed;
    public function find(int|string $id): mixed;
    public function update(int|string $id, array $data): mixed;
    public function delete(int|string $id): bool;
    public function imageDelete($id): mixed;
    public function imageUpload(UploadedFile $file): mixed;
    public function import($file): mixed;
    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed;
}
