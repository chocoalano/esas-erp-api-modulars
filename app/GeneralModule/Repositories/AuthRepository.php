<?php

namespace App\GeneralModule\Repositories;

use App\GeneralModule\Models\FcmModel;
use App\GeneralModule\Models\User;
use App\GeneralModule\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\GeneralModule\Repositories\Contracts\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    protected $model;
    protected $userRepo;
    public function __construct(
        User $model,
        UserRepositoryInterface $userRepo,
    ) {
        $this->model = $model;
        $this->userRepo = $userRepo;
    }

    public function login(array $input): mixed
    {
        $user = $this->model
            ->with([
                'company',
                'details',
                'address',
                'salaries',
                'employee',
            ])
            ->where('nip', $input['nip'])
            ->first();

        if (!$user || !Hash::check($input['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Cek device info jika dikirim
        if (isset($input['device_info'])) { // Check if 'device_info' key exists in $input
            if (!empty($input['device_info']) && !is_null($user->device_id)) {
                if ($user->device_id !== $input['device_info']) {
                    return response()->json([
                        'message' => 'Unrecognized device. Access denied.',
                    ], 403);
                }
            } else {
                // This block will only execute if 'device_info' exists but is empty or user->device_id is null
                if (!empty($input['device_info'])) { // Double-check if it's not empty before updating
                    $user->update(['device_id' => $input['device_info']]);
                }
            }
        }

        // Buat token
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function profile(): mixed
    {
        $id = Auth::id();
        $permissions = auth()->user()->getAllPermissions()->pluck('name');
        $user = $this->userRepo->find($id);
        return [
            'user' => $user,
            'permissions' => $permissions
        ];
    }
    public function profile_update(array $input, UploadedFile|null $file): mixed
    {
        $id = Auth::id();
        if ($file) {
            $this->userRepo->avatarDelete($id);
            $upload = $this->userRepo->avatarUpload($file);
            $input['avatar'] = $upload;
        }
        return $this->userRepo->update($id, $input);
    }
    public function profile_avatar_update(UploadedFile $file): mixed
    {
        $user = Auth::user();
        $this->userRepo->avatarDelete($user->id);
        $avatarPath = $this->userRepo->avatarUpload($file);
        $user->update([
            'avatar' => $avatarPath,
        ]);
        return $user->refresh();
    }
    public function logout(): mixed
    {
        $user = Auth::user();
        return $user->currentAccessToken()->delete();
    }
    public function refresh_token(): mixed
    {
        $user = Auth::user();
        $user->load([
            'company',
            'details',
            'address',
            'salaries',
            'employee',
        ]);
        // Hapus token yang sedang digunakan
        $user->currentAccessToken()->delete();
        // Generate token baru
        $newToken = $user->createToken('api-token')->plainTextToken;
        return [
            'user' => $user,
            'token' => $newToken,
        ];
    }
    public function store_device_token(string $deviceToken, int $userId): mixed
    {
        return FcmModel::updateOrCreate(
            ['user_id' => $userId],
            ['device_token' => $deviceToken]
        );
    }
    public function update_password(
        int $userId,
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ): array {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User tidak ditemukan.',
                ];
            }
            if (!Hash::check($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Password saat ini salah.',
                ];
            }

            if ($newPassword !== $confirmPassword) {
                return [
                    'success' => false,
                    'message' => 'Konfirmasi password baru tidak cocok.',
                ];
            }

            // Optional: Tambahkan validasi strength password minimal 8 karakter, dsb.

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            return [
                'success' => true,
                'message' => 'Password berhasil diperbarui.',
                'user' => $user->only(['id', 'name', 'email']), // hindari kirim hash password
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ];
        }
    }

}
