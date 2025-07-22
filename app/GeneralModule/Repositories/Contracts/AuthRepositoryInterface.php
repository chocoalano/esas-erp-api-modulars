<?php

namespace App\GeneralModule\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface AuthRepositoryInterface
{
    public function login(array $input): mixed;
    public function profile(): mixed;
    public function profile_update(array $input, UploadedFile|null $file): mixed;
    public function profile_avatar_update(UploadedFile $file): mixed;
    public function logout(): mixed;
    public function refresh_token(): mixed;
    public function store_device_token(string $deviceToken, int $userId): mixed;
    public function update_password(
        int $userId,
        string $password,
        string $new_password,
        string $confirmation_new_password,
    ): mixed;
}
