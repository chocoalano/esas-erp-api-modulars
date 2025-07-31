<?php

namespace App\HrisModule\Repositories;

use App\Console\Support\DoSpaces;
use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\QrPresence;
use App\HrisModule\Models\QrPresenceTransaction;
use App\HrisModule\Models\UserAttendance;
use App\HrisModule\Models\UserTimeworkSchedule;
use App\HrisModule\Repositories\Contracts\UserAttendanceRepositoryInterface;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class UserAttendanceRepository implements UserAttendanceRepositoryInterface
{
    public function __construct(protected UserAttendance $model)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        $query = $this->model->query()->with([
            'user',
            'user.company',
            'user.employee.departement',
            'schedule'
        ]);
        // Filter Search
        if (!empty($search)) {
            if (!empty($search['company_id'])) {
                $query->whereHas('user', fn($u) => $u->where('company_id', $search['company_id']));
            }
            if (!empty($search['departement_id'])) {
                $query->whereHas(
                    'user',
                    fn($u) =>
                    $u->whereHas(
                        'employee',
                        fn($emp) =>
                        $emp->where('departement_id', $search['departement_id'])
                    )
                );
            }
            if (!empty($search['user_id'])) {
                $query->where('user_id', $search['user_id']);
            }
            if (!empty($search['status_in'])) {
                $query->where('status_in', $search['status_in']);
            }
            if (!empty($search['status_out'])) {
                $query->where('status_out', $search['status_out']);
            }
            if (!empty($search['createdAt'])) {
                $query->whereDate('created_at', $search['createdAt']);
            }
            if (!empty($search['start']) && !empty($search['end'])) {
                $query->whereBetween('created_at', [$search['start'], $search['end']]);
            }
        }
        // Sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $key = $sort['key'] ?? null;
                $order = strtolower($sort['order'] ?? 'asc');

                if ($key && in_array($order, ['asc', 'desc'])) {
                    $query->orderBy($key, $order);
                }
            }
        } else {
            $query->latest();
        }
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function form(?int $companyId, ?int $deptId, ?int $userId): array
    {
        // 1. Fetch Company Data
        $companyData = Company::query()
            ->when($companyId, fn($query) => $query->where('id', $companyId))
            ->when($companyId, fn($query) => $query->get(), fn($query) => $query->get());

        // 2. Fetch Department Data
        $departementData = Departement::query()
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when($deptId, fn($query) => $query->where('id', $deptId))
            ->when($deptId, fn($query) => $query->get(), fn($query) => $query->get());

        // 3. Fetch User Data
        $userQuery = User::with('timeworkSchedules')
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when($deptId, fn($query) => $query->whereHas('employee', fn($emp) => $emp->where('departement_id', $deptId)))
            ->when($userId, fn($query) => $query->where('id', $userId));

        $userData = $userId ? $userQuery->get() : $userQuery->get();

        // 4. Fetch Schedule Data (depends directly on userId)
        $scheduleData = null;
        $userId ?
            $scheduleData = UserTimeworkSchedule::with('timework')
                ->where('user_id', $userId)
                ->when($userId, fn($query) => $query->get(), fn($query) => $query->get())
            :
            $scheduleData = UserTimeworkSchedule::with('timework')->get();


        // Prepare static options
        $attendanceTypes = [
            ['name' => 'QR', 'value' => 'qrcode'],
            ['name' => 'Face Device Recognition', 'value' => 'face-device'],
            ['name' => 'Face Device Geolocation', 'value' => 'face-geolocation']
        ];

        $attendanceStatuses = [
            ['name' => 'Late', 'value' => 'late'],
            ['name' => 'Unlate', 'value' => 'unlate'],
            ['name' => 'Normal', 'value' => 'normal']
        ];

        return [
            'company' => $companyData,
            'departement' => $departementData,
            'users' => $userData,
            'schedule' => $scheduleData,
            'type' => $attendanceTypes,
            'status' => $attendanceStatuses,
        ];
    }
    public function create(array $data): mixed
    {
        $data['created_by'] = !Auth::user()->hasRole('super_admin') ? Auth::id() : $data['user_id'];
        $data['updated_by'] = !Auth::user()->hasRole('super_admin') ? Auth::id() : $data['user_id'];
        return $this->model->create($data);
    }

    public function registration_and_generate_qrcode(int $departement_id, int $shift_id, string $type_presence): mixed
    {
        $timezone = config('app.timezone');
        $currentTime = now($timezone);
        $expiresAt = $currentTime->copy()->addSeconds(10);
        $token = Crypt::encryptString($currentTime->format('Y-m-d H:i:s'));

        return QrPresence::firstOrCreate(['token' => $token], [
            'type' => $type_presence,
            'departement_id' => $departement_id,
            'timework_id' => $shift_id,
            'for_presence' => $currentTime->copy()->timezone($timezone)->toDateTimeString(),
            'expires_at' => $expiresAt->copy()->timezone($timezone)->toDateTimeString(),
        ]);
    }

    public function attendance_inout_qrcode(string $type_presence, int $id_token): mixed
    {
        $userId = Auth::id();
        $currentTime = now(config('app.timezone'));
        $currentDate = today(config('app.timezone'));

        $qrPresence = DB::table('qr_presences as qrp')
            ->join('departements as d', 'qrp.departement_id', '=', 'd.id')
            ->join('time_workes as tw', 'qrp.timework_id', '=', 'tw.id')
            ->select([
                'qrp.id',
                'qrp.type',
                'qrp.token',
                'qrp.expires_at',
                'qrp.departement_id',
                'qrp.timework_id',
                'd.name as departement_name',
                'tw.in as work_start_time',
                'tw.company_id'
            ])
            ->where([
                ['qrp.type', '=', $type_presence],
                ['qrp.id', '=', $id_token]
            ])
            ->first();

        if (!$qrPresence)
            throw new Exception('Token tidak ditemukan!');

        if (DB::table('qr_presence_transactions')->where('qr_presence_id', $qrPresence->id)->exists())
            throw new Exception('Kode QR sudah digunakan!');

        if ($currentTime->gt(Carbon::parse($qrPresence->expires_at)->setTimezone(config("app.timezone"))))
            throw new Exception('Kode QR sudah kadaluarsa!');

        $isUserInDepartment = DB::table('users')
            ->where('id', $userId)
            ->whereExists(fn($query) => $query->select(DB::raw(1))
                ->from('user_employes')
                ->whereColumn('user_employes.user_id', 'users.id')
                ->where('user_employes.departement_id', $qrPresence->departement_id))
            ->exists();

        if (!$isUserInDepartment)
            throw new Exception('User tidak terdaftar di departemen ini.');

        $scheduleId = DB::table('user_timework_schedules')
            ->where('user_id', $userId)
            ->where('work_day', $currentDate)
            ->value('id');

        // Validasi absen pulang harus setelah absen masuk
        if (
            $type_presence === 'out' && !DB::table('user_attendances')
                ->where('user_id', $userId)
                ->whereDate('created_at', $currentDate)
                ->whereNotNull('time_in')
                ->exists()
        ) {
            throw new Exception('Anda harus melakukan absensi masuk sebelum absensi pulang!');
        }

        $status = $currentTime->lt($qrPresence->work_start_time) ? 'normal' : 'late';
        $statusInOut = $type_presence === 'in' ? $status : ($currentTime->lt($qrPresence->work_start_time) ? 'unlate' : 'normal');

        $company = DB::table('companies')->where('id', $qrPresence->company_id)->select('latitude', 'longitude')->first();

        return DB::transaction(function () use ($userId, $currentTime, $currentDate, $type_presence, $qrPresence, $scheduleId, $statusInOut, $company) {
            $attendance = UserAttendance::where('user_id', $userId)->whereDate('created_at', $currentDate)->first();

            if ($attendance) {
                // Cegah update absen masuk jika sudah ada
                if ($type_presence === 'in' && !is_null($attendance->time_in)) {
                    throw new Exception('Anda sudah melakukan absensi masuk hari ini.');
                }

                // Cegah update absen pulang jika sudah ada
                // if ($type_presence === 'out' && !is_null($attendance->time_out)) {
                //     throw new Exception('Anda sudah melakukan absensi pulang hari ini.');
                // }

                // Update jika absen sesuai dengan status yang belum terisi
                $attendance->update($this->buildAttendanceData($attendance, $type_presence, $currentTime, $company, $statusInOut, $userId));
            } else {
                // Buat data baru jika belum ada absen hari ini
                UserAttendance::create($this->buildAttendanceData(null, $type_presence, $currentTime, $company, $statusInOut, $userId, $scheduleId));
            }

            QrPresenceTransaction::create([
                'qr_presence_id' => $qrPresence->id,
                'user_attendance_id' => $attendance->id ?? DB::getPdo()->lastInsertId(),
                'token' => $qrPresence->token,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);
        });
    }

    private function buildAttendanceData($attendance, $type_presence, $currentTime, $company, $statusInOut, $userId, $scheduleId = null)
    {
        $data = [
            'updated_at' => $currentTime,
            'created_by' => $userId,
            'updated_by' => $type_presence === 'out' ? $userId : null,
        ];

        if ($attendance) {
            if ($type_presence === 'in') {
                $data = array_merge($data, [
                    'time_in' => $currentTime,
                    'status_in' => $statusInOut,
                    'lat_in' => $company->latitude,
                    'long_in' => $company->longitude,
                    'type_in' => 'qrcode',
                ]);
            } else {
                $data = array_merge($data, [
                    'time_out' => $currentTime,
                    'status_out' => $statusInOut,
                    'lat_out' => $company->latitude,
                    'long_out' => $company->longitude,
                    'type_out' => 'qrcode',
                ]);
            }
        } else {
            $data = array_merge($data, [
                'user_id' => $userId,
                'user_timework_schedule_id' => $scheduleId,
                'created_at' => $currentTime,
                'time_in' => $type_presence === 'in' ? $currentTime : null,
                'status_in' => $type_presence === 'in' ? $statusInOut : 'normal',
                'lat_in' => $type_presence === 'in' ? $company->latitude : null,
                'long_in' => $type_presence === 'in' ? $company->longitude : null,
                'type_in' => $type_presence === 'in' ? 'qrcode' : null,
                'time_out' => $type_presence === 'out' ? $currentTime : null,
                'status_out' => $type_presence === 'out' ? $statusInOut : 'normal',
                'lat_out' => $type_presence === 'out' ? $company->latitude : null,
                'long_out' => $type_presence === 'out' ? $company->longitude : null,
                'type_out' => $type_presence === 'out' ? 'qrcode' : null,
            ]);
        }

        return $data;
    }

    public function in(array $data): mixed
    {
        $userid = $data['user_id'] ?? Auth::id();
        $cek = $this
            ->model
            ->where('user_id', $userid)
            ->whereNotNull('time_in')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->exists();
        if ($cek) {
            throw new Exception("Anda sudah melakukan absen masuk hari ini!", 1);
        } else {
            return $this->callAttendanceProcedure('UpdateAttendanceIn', $data);
        }
    }

    public function out(array $data): mixed
    {
        return $this->callAttendanceProcedure('UpdateAttendanceOut', $data);
    }

    private function callAttendanceProcedure(string $procedure, array $data): mixed
    {
        $requiredFields = ['time_id', 'lat', 'long', 'type', 'image', 'time'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]))
                return false;
        }

        $userId = $data['user_id'] ?? Auth::id();
        $exec = DB::select("CALL {$procedure}(?,?,?,?,?,?)", [
            (int) $userId,
            (int) $data['time_id'],
            (float) $data['lat'],
            (float) $data['long'],
            (string) $data['image'],
            (string) $data['time']
        ]);
        return $exec[0]->success === 1;
    }

    public function fileDelete($id, string $type): mixed
    {
        try {
            $model = $this->model->findOrFail($id);
            DoSpaces::remove($type === 'in' ? $model->image_in : $model->image_out);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function fileUpload(UploadedFile $file): mixed
    {
        $filename = now()->format('YmdHis');
        $upload = DoSpaces::upload($file, 'attendances', $filename);
        return $upload['path'];
    }

    public function find(int|string $id): mixed
    {
        $model = $this->model->findOrFail($id);
        return [
            "company_id" => $model->user->company_id,
            "departement_id" => $model->user->employee->departement_id,
            "user_id" => $model->user_id,
            "user_timework_schedule_id" => $model->user_timework_schedule_id,
            "time_in" => $model->time_in,
            "time_out" => $model->time_out,
            "type_in" => $model->type_in,
            "type_out" => $model->type_out,
            "lat_in" => $model->lat_in,
            "lat_out" => $model->lat_out,
            "long_in" => $model->long_in,
            "long_out" => $model->long_out,
            "image_in" => $model->image_in,
            "image_out" => $model->image_out,
            "status_in" => $model->status_in,
            "status_out" => $model->status_out,
        ];
    }

    public function update(int|string $id, array $data): mixed
    {
        $model = $this->model->findOrFail($id);
        $data['updated_by'] = !Auth::user()->hasRole('super_admin') ? Auth::id() : $data['user_id'];
        $model->update($data);
        return $model;
    }

    public function delete(int|string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function forceDelete(int|string $id): bool
    {
        return $this->model->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int|string $id): mixed
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): mixed {
        $query = $this->model->newQuery();
        $query->with(['user', 'schedule', 'qrPresenceTransactions']);
        $query
            ->when($name, fn($q) => $q->where('name', 'like', "%{$name}%"))
            ->when($createdAt, fn($q) => $q->whereDate('created_at', $createdAt))
            ->when($updatedAt, fn($q) => $q->whereDate('updated_at', $updatedAt))
            ->when($startRange, fn($q) => $q->whereDate('created_at', '>=', $startRange))
            ->when($endRange, fn($q) => $q->whereDate('created_at', '<=', $endRange));

        $data = $query->get();

        if ($data->isEmpty()) {
            return [];
        }

        return $data;
    }

    public function import($file): mixed
    {
        return true;
    }
    public function report(
        ?int $company_id,
        ?int $departement_id,
        ?array $user_id,
        ?string $status_in,
        ?string $status_out,
        ?string $start,
        ?string $end
    ): mixed {
        // Validasi dan parsing tanggal awal & akhir
        $startDate = filled($start) && strtotime($start)
            ? Carbon::parse($start)->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = filled($end) && strtotime($end)
            ? Carbon::parse($end)->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        // Buat koleksi tanggal-tanggal
        $dates = collect(CarbonPeriod::create($startDate, $endDate));

        // Mulai query
        $query = DB::table('users AS u')
            ->select([
                'u.nip AS employee_id',
                'u.name AS first_name',
                'd.name AS departement',
                'jp.name AS position',
                'jl.name AS level',
                'ue.join_date',
            ])

            // Tambahkan kolom dinamis harian dari hasil CarbonPeriod
            ->addSelect($dates->map(function ($date) {
                $formatted = $date->format('Y-m-d');
                return DB::raw("
        MAX(CASE
            WHEN DATE(ua.created_at) = '{$formatted}'
            THEN CONCAT(COALESCE(ua.time_in, '-'), ' - ', COALESCE(ua.time_out, '-'))
            ELSE NULL
        END) AS `{$formatted}`");
            })->toArray())

            // Tambahkan kolom statistik tambahan
            ->addSelect([
                DB::raw("SEC_TO_TIME(SUM(
        CASE
            WHEN ua.time_in IS NOT NULL AND tw.in IS NOT NULL AND ua.time_in > tw.in
            THEN TIME_TO_SEC(TIMEDIFF(ua.time_in, tw.in))
            ELSE 0
        END
    )) AS total_jam_terlambat"),

                DB::raw("SUM(CASE WHEN pt.type IN (
        'Dispensasi Menikah', 'Dispensasi menikahkan anak',
        'Dispensasi khitan/baptis anak', 'Dispensasi Keluarga/Anggota Keluarga Dalam Satu Rumah Meninggal',
        'Dispensasi Melahirkan/Keguguran', 'Dispensasi Ibadah Agama',
        'Dispensasi Wisuda (anak/pribadi)', 'Dispensasi Lain-lain',
        'Dispensasi Tugas Kantor (dalam/luar kota)'
    ) THEN 1 ELSE 0 END) AS dispensasi"),

                DB::raw("SUM(CASE WHEN pt.type IN (
        'Izin Sakit (surat dokter & resep)', 'Izin Sakit (tanpa surat dokter)',
        'Izin Sakit Kecelakaan Kerja (surat dokter & resep)', 'Izin Sakit (rawat inap)',
        'Izin Koreksi Absen', 'izin perubahan jam kerja'
    ) THEN 1 ELSE 0 END) AS izin"),

                DB::raw("SUM(CASE WHEN pt.type IN (
        'Cuti Tahunan', 'Unpaid Leave (Cuti Tidak Dibayar)'
    ) THEN 1 ELSE 0 END) AS cuti"),
            ])

            // Join tabel
            ->join('user_employes AS ue', 'u.id', '=', 'ue.user_id')
            ->join('departements AS d', 'ue.departement_id', '=', 'd.id')
            ->join('job_positions AS jp', 'ue.job_position_id', '=', 'jp.id')
            ->join('job_levels AS jl', 'ue.job_level_id', '=', 'jl.id')

            // Perbaikan utama di bagian ini 👇
            ->leftJoin('user_attendances AS ua', function ($join) use ($startDate, $endDate) {
                $join->on('ua.user_id', '=', 'u.id')
                    ->where('ua.created_at', '>=', $startDate)
                    ->where('ua.created_at', '<=', $endDate)
                    ->whereNotNull('ua.created_at'); // ← Mencegah error DATE(null)
            })

            // Join tambahan
            ->leftJoin('permits AS p', 'p.user_id', '=', 'u.id')
            ->leftJoin('permit_types AS pt', 'p.permit_type_id', '=', 'pt.id')
            ->leftJoin('user_timework_schedules AS uts', 'uts.id', '=', 'ua.user_timework_schedule_id')
            ->leftJoin('time_workes AS tw', 'uts.time_work_id', '=', 'tw.id')

            // Grouping
            ->groupBy([
                'u.id',
                'u.nip',
                'u.name',
                'd.name',
                'jp.name',
                'jl.name',
                'ue.join_date',
                'ue.sign_date',
                'ue.resign_date',
            ])

            ->orderBy('u.name', 'ASC')

            // Filter dinamis
            ->when($company_id, fn($q) => $q->where('u.company_id', $company_id))
            ->when($departement_id, fn($q) => $q->where('d.id', $departement_id))
            ->when(!empty($user_id), fn($q) => $q->whereIn('u.id', $user_id))
            ->when($status_in, fn($q) => $q->where('ua.status_in', $status_in))
            ->when($status_out, fn($q) => $q->where('ua.status_out', $status_out))

            // Hindari user yang soft-deleted
            ->whereNull('u.deleted_at');

        // Hasil
        return $query->get();
    }
}
