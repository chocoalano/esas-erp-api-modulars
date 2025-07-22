<?php

namespace App\GeneralModule\Services;

use App\GeneralModule\Models\ActivityLog;
use App\GeneralModule\Repositories\Contracts\AuthRepositoryInterface;
use App\HrisModule\Models\UserAttendance;
use App\HrisModule\Models\UserTimeworkSchedule;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(protected AuthRepositoryInterface $repo)
    {
    }

    public function login(array $input): mixed
    {
        return $this->repo->login($input);
    }
    public function profileAuth(): mixed
    {
        return $this->repo->profile();
    }
    public function profileAuthUpdate(array $input, UploadedFile|null $file): mixed
    {
        return $this->repo->profile_update($input, $file);
    }
    public function refresh_token(): mixed
    {
        return $this->repo->refresh_token();
    }
    public function logout(): mixed
    {
        return $this->repo->logout();
    }
    public function store_device_token(string $deviceToken, int $userId): mixed
    {
        return $this->repo->store_device_token($deviceToken, $userId);
    }
    public function update_password(int $userId, array $input): mixed
    {
        return $this->repo->update_password(
            $userId,
            $input['password'],
            $input['new_password'],
            $input['confirmation_new_password'],
        );
    }
    public function schedule(int $userId): mixed
    {
        $schedule = UserTimeworkSchedule::with('timework')
            ->where([
                'user_id' => $userId,
                'work_day' => Carbon::now()->format('Y-m-d'),
            ])
            ->first();
        if ($schedule) {
            return $schedule;
        }
        return null;
    }
    public function attendanceToday(int $userId): ?UserAttendance
    {
        $today = Carbon::today();

        return UserAttendance::query()
            ->where('user_id', $userId)
            ->where(function (Builder $query) use ($today) {
                $query
                    // Created_at hari ini
                    ->whereDate('created_at', $today)
                    // ATAU jadwal (relasi schedule.timework) hari ini
                    ->orWhereHas('schedule', function (Builder $q) use ($today) {
                        $q->whereDate('work_day', $today);
                    });
            })
            ->first();
    }
    public function activity(): Collection
    {
        return ActivityLog::query()
            ->where('user_id', Auth::id())
            ->whereDate('created_at', Carbon::today())
            ->latest('id')
            ->limit(10)
            ->get();
    }
    public function updateProfile(UploadedFile $file): mixed
    {
        return $this->repo->profile_avatar_update($file);
    }
    public function summary_absen(): mixed
    {
        $userId = Auth::id();
        $result = DB::table('users as u')
            ->join('user_attendances as ua', 'u.id', '=', 'ua.user_id')
            ->where('u.id', $userId)
            ->selectRaw('
        COUNT(*) AS total_absensi,
        SUM(CASE WHEN ua.status_in = "late" THEN 1 ELSE 0 END) AS total_terlambat,
        SUM(CASE WHEN ua.status_in = "normal" THEN 1 ELSE 0 END) AS total_normal,
        ROUND(SUM(CASE WHEN ua.status_in = "late" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS persen_terlambat,
        ROUND(SUM(CASE WHEN ua.status_in = "normal" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS persen_normal,
        ROUND((
            (SUM(CASE WHEN ua.status_in = "normal" THEN 100 ELSE 0 END) +
            SUM(CASE WHEN ua.status_in = "late" THEN 50 ELSE 0 END)
            ) / COUNT(*)
            ), 2) AS persen_point
            ')->first();
        return $result;
    }
    public function setup_token(string $token): mixed
    {
        $user = Auth::user();

        // Pastikan user ada dan relasi fcm_token tidak null
        if ($user && $user->fcm_token) {
            return $user->fcm_token->update([
                'device_token' => $token,
            ]);
        }

        // Jika belum ada token, buat baru
        return $user->fcm_token()->create([
            'device_token' => $token,
        ]);
    }
}
