<?php

namespace App\GeneralModule\Services;

use App\GeneralModule\Repositories\Contracts\BugReportRepositoryInterface;
use Illuminate\Http\UploadedFile;

class BugReportService
{
    public function __construct(protected BugReportRepositoryInterface $repo) {}

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    public function form(): mixed
    {
        return $this->repo->form();
    }

    public function create(array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $upload = $this->repo->imageUpload($file);
            $data['image'] = $upload;
        }
        $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN);
        return $this->repo->create($data);
    }

    public function update(int|string $id, array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $this->repo->imageDelete($id);
            $upload = $this->repo->imageUpload($file);
            $data['image'] = $upload;
        }
        $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN);
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
