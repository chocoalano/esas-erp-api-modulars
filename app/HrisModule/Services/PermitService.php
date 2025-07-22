<?php

namespace App\HrisModule\Services;

use App\HrisModule\Repositories\Contracts\PermitRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class PermitService
{
    public function __construct(protected PermitRepositoryInterface $repo)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }
    public function paginateListType(int $typeId, int $page, int $limit): mixed
    {
        return $this->repo->paginateListType($typeId, $page, $limit);
    }

    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginateTrashed($page, $limit, $search, $sortBy);
    }

    public function form(?int $companyId, ?int $deptId, ?int $userId, ?int $typeId, ?int $scheduleId): mixed
    {
        return $this->repo->form($companyId, $deptId, $userId, $typeId, $scheduleId);
    }

    public function create(array $data, UploadedFile|null $file): mixed
    {
        // Proses upload file jika ada
        if ($file) {
            $uploadPath = $this->repo->fileUpload($file);
            $data['file'] = $uploadPath;
        }
        // Proses create data
        return $this->repo->create($data);
    }

    public function update(int|string $id, array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $this->repo->fileDelete($id);
            $upload = $this->repo->fileUpload($file);
            $data['file'] = $upload;
        }
        return $this->repo->update($id, $data);
    }

    public function approve(?int $id, ?int $userId, ?array $data): mixed
    {
        return $this->repo->approval_process($id, $userId, $data);
    }
    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    public function forceDelete(int|string $id): bool
    {
        $this->repo->fileDelete($id);
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
