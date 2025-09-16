<?php

namespace App\HrisModule\Repositories;

use App\Console\Support\DoSpaces;
use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\QrPresence;
use App\HrisModule\Models\UserAttendance;
use App\HrisModule\Models\UserTimeworkSchedule;
use App\HrisModule\Repositories\Contracts\UserAttendanceRepositoryInterface;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Collection;
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
                $query->whereDate('date_presence', $search['createdAt']);
            }
            if (!empty($search['start']) && !empty($search['end'])) {
                $query->whereBetween('date_presence', [$search['start'], $search['end']]);
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

    public function attendance_inout_qrcode(string $typePresence, int $idToken): Collection
    {
        $userId = Auth::id();

        $results = DB::select("CALL QrAttendance(?, ?, ?)", [
            $userId,
            $typePresence,
            $idToken
        ]);

        return collect($results[0]);
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
        // Normalisasi input
        $name = $name ? trim($name) : null;
        $createdAt = $createdAt ? trim($createdAt) : null;
        $updatedAt = $updatedAt ? trim($updatedAt) : null;
        $startRange = $startRange ? trim($startRange) : null;
        $endRange = $endRange ? trim($endRange) : null;

        // Atur timezone lokal jika diperlukan
        $tz = 'Asia/Jakarta';

        $query = $this->model->newQuery()
            ->with(['user', 'schedule', 'qrPresenceTransactions'])
            // pastikan user tidak soft-deleted
            ->whereHas('user', function ($u) {
                // gunakan nama tabel "users" agar jelas
                $u->whereNull('users.deleted_at');
            });

        // Filter nama pada relasi user
        if (!empty($name)) {
            $query->whereHas('user', function ($u) use ($name) {
                $u->where('users.name', 'like', '%' . $name . '%');
            });
        }

        // Filter tanggal spesifik (tanggal saja)
        if (!empty($createdAt)) {
            // createdAt = YYYY-MM-DD
            $query->whereDate($this->model->getTable() . '.created_at', $createdAt);
        }
        if (!empty($updatedAt)) {
            $query->whereDate($this->model->getTable() . '.updated_at', $updatedAt);
        }

        // Filter rentang tanggal (created_at)
        if (!empty($startRange) && !empty($endRange)) {
            // gunakan awal/akhir hari
            $start = Carbon::parse($startRange, $tz)->startOfDay();
            $end = Carbon::parse($endRange, $tz)->endOfDay();
            $query->whereBetween($this->model->getTable() . '.created_at', [$start, $end]);
        } else {
            if (!empty($startRange)) {
                $start = Carbon::parse($startRange, $tz)->startOfDay();
                $query->where($this->model->getTable() . '.created_at', '>=', $start);
            }
            if (!empty($endRange)) {
                $end = Carbon::parse($endRange, $tz)->endOfDay();
                $query->where($this->model->getTable() . '.created_at', '<=', $end);
            }
        }

        // (opsional) urutkan terbaru dulu agar file export “enak” dilihat
        $query->latest($this->model->getTable() . '.created_at');

        $data = $query->get();

        return $data->isEmpty() ? [] : $data->values();
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
            ? Carbon::parse($start)
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = filled($end) && strtotime($end)
            ? Carbon::parse($end)
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
            WHEN DATE(ua.date_presence) = '{$formatted}'
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
                    ->where('ua.date_presence', '>=', $startDate)
                    ->where('ua.date_presence', '<=', $endDate)
                    ->whereNotNull('ua.date_presence'); // ← Mencegah error DATE(null)
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
