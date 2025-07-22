<?php

namespace App\HrisModule\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface UserAttendanceRepositoryInterface
{
    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed;
    public function create(array $data): mixed;
    public function registration_and_generate_qrcode(
        int $departement_id,
        int $shift_id,
        string $type_presence
    ): mixed;
    public function attendance_inout_qrcode(
        string $type_presence,
        int $id_token
    ): mixed;
    public function in(array $data): mixed;
    public function out(array $data): mixed;
    public function fileDelete(int|string $id, string $type): mixed;
    public function fileUpload(UploadedFile $file): mixed;
    public function form(?int $companyId, ?int $deptId, ?int $userId): mixed;
    public function find(int|string $id): mixed;
    public function update(int|string $id, array $data): mixed;
    public function delete(int|string $id): bool;
    public function forceDelete(int|string $id): bool;
    public function restore(int|string $id): mixed;
    public function import($file): mixed;
    public function report(?int $company_id, ?int $departement_id, ?array $user_id, ?string $status_in, ?string $status_out, ?string $start, ?string $end): mixed;
    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed;
}
