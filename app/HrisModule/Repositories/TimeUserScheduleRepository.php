<?php

namespace App\HrisModule\Repositories;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\TimeWorke;
use App\HrisModule\Models\UserTimeworkSchedule;
use App\HrisModule\Repositories\Contracts\TimeUserScheduleRepositoryInterface;
use App\Jobs\InsertUpdateScheduleJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder; // Import Builder untuk type-hinting query
use Illuminate\Pagination\LengthAwarePaginator; // Untuk return type paginate
use Illuminate\Database\Eloquent\Collection; // Untuk return type export
use Illuminate\Database\Eloquent\ModelNotFoundException; // Exception spesifik
use Illuminate\Foundation\Bus\PendingDispatch; // Untuk return type create
use InvalidArgumentException;

class TimeUserScheduleRepository implements TimeUserScheduleRepositoryInterface
{
    public function __construct(protected UserTimeworkSchedule $model)
    {
        // Model di-inject melalui constructor, ini adalah praktik yang baik.
    }

    /**
     * Mengambil data jadwal pengguna dengan paginasi.
     *
     * @param int $page Nomor halaman yang diminta.
     * @param int $limit Jumlah item per halaman.
     * @param array $search Array asosiatif untuk kriteria pencarian (field => value).
     * @param array $sortBy Array asosiatif untuk pengurutan ([['key' => 'field', 'order' => 'asc|desc']]).
     * @return LengthAwarePaginator
     */
    public function paginate(int $page, int $limit, array $search, array $sortBy): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        $query->with([
            'timework',
            'employee.user.company',
            'employee.user',
            'employee.departement',
        ]);
        if (!empty($search['company_id'])) {
            $query->whereHas('user', function ($user) use ($search) {
                $user->where('company_id', $search['company_id']);
            });
        }
        if (!empty($search['departement_id'])) {
            $query->whereHas('user', function ($user) use ($search) {
                $user->whereHas('employee', function ($emp) use ($search) {
                    $emp->where('departement_id', $search['departement_id']);
                });
            });
        }
        if (!empty($search['timework_id'])) {
            $query->whereHas('timework', function ($time) use ($search) {
                $time->where('id', $search['timework_id']);
            });
        }
        if (!empty($search['workday'])) {
            $query->whereDate('work_day', $search['workday']);
        }
        if (!empty($search['user_id'])) {
            $query->whereHas('user', function ($user) use ($search) {
                $user->where('id', $search['user_id']);
            });
        }
        if (!empty($search['createdAt'])) {
            $query->whereDate('created_at', $search['createdAt']);
        }
        if (!empty($search['updatedAt'])) {
            $query->whereDate('updated_at', $search['updatedAt']);
        }
        if (!empty($search['startRange']) && !empty($search['endRange'])) {
            $query->whereBetween('updated_at', [$search['startRange'], $search['endRange']]);
        }
        // Penerapan sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $sortKey = $sort['key'] ?? null;
                $sortOrder = $sort['order'] ?? 'asc';

                if ($sortKey) {
                    $query->orderBy($sortKey, $sortOrder);
                }
            }
        } else {
            // Default sorting jika tidak ada sortBy
            $query->latest(); // Mengurutkan berdasarkan 'created_at' DESC
        }
        // Return hasil pagination
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Menyiapkan data atau opsi yang diperlukan untuk form resource ini.
     *
     * @return array
     */
    public function form(?int $companyId, ?int $deptId): array
    {
        return [
            "company" => $companyId ? Company::where('id', $companyId)->get() : Company::all(),
            "departement" => $companyId && $deptId ? Departement::where([
                'company_id' => $companyId,
                'id' => $deptId
            ])->get() : Departement::all(),
            "timeworks" => $companyId && $deptId ? TimeWorke::where([
                'company_id' => $companyId,
                'departemen_id' => $deptId
            ])->get() : TimeWorke::all(),
            "users" => $companyId && $deptId ? User::where('company_id', $companyId)->whereHas('employee', function ($emp) use ($deptId) {
                $emp->where('departement_id', $deptId);
            })->get() : User::all()
        ];
    }

    /**
     * Membuat entri jadwal kerja untuk banyak pengguna dalam rentang tanggal.
     *
     * @param array $data Data untuk membuat jadwal.
     * @return PendingDispatch|null Mengembalikan instance PendingDispatch jika job didispatch, null jika tidak.
     * @throws InvalidArgumentException Jika data yang diperlukan hilang atau tidak valid.
     */
    public function create(array $data): ?PendingDispatch
    {
        // Validasi Input Dasar
        $requiredKeys = ['work_day_start', 'work_day_finish', 'user_id', 'time_work_id'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required data: '{$key}' is not provided.");
            }
        }

        if (!is_array($data['user_id']) || empty($data['user_id'])) {
            throw new InvalidArgumentException("'user_id' must be a non-empty array of user IDs.");
        }

        // Parsing dan Validasi Tanggal
        try {
            $workDayStart = Carbon::parse($data['work_day_start'])->timezone(config('app.timezone'));
            $workDayFinish = Carbon::parse($data['work_day_finish'])->timezone(config('app.timezone'));
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                "Invalid date format for 'work_day_start' or 'work_day_finish'. Details: " . $e->getMessage()
            );
        }

        if ($workDayStart->greaterThan($workDayFinish)) {
            throw new InvalidArgumentException('Start date cannot be greater than finish date.');
        }

        $scheduleEntries = [];
        $skipDays = $data['dayoff'] ?? [];

        $currentDay = $workDayStart->clone();
        while ($currentDay->lte($workDayFinish)) {
            $dayName = $currentDay->format('l');

            if (!in_array($dayName, $skipDays)) {
                foreach ($data['user_id'] as $userId) {
                    $scheduleEntries[] = [
                        'user_id' => $userId,
                        'time_work_id' => $data['time_work_id'],
                        'work_day' => $currentDay->toDateString(),
                        'created_at' => Carbon::now(), // Tambahkan timestamp
                        'updated_at' => Carbon::now(), // Tambahkan timestamp
                    ];
                }
            }
            $currentDay->addDay();
        }

        if (!empty($scheduleEntries)) {
            return InsertUpdateScheduleJob::dispatch($scheduleEntries);
        }
        return null;
    }

    /**
     * Mencari model berdasarkan ID.
     *
     * @param int|string $id ID model.
     * @return UserTimeworkSchedule
     * @throws ModelNotFoundException Jika model tidak ditemukan.
     */
    public function find(int|string $id): UserTimeworkSchedule
    {
        // findOrFail akan otomatis melempar ModelNotFoundException jika tidak ditemukan
        return $this->model->findOrFail($id);
    }

    /**
     * Memperbarui model berdasarkan ID.
     *
     * @param int|string $id ID model yang akan diperbarui.
     * @param array $data Data yang akan diperbarui.
     * @return PendingDispatch|null
     * @throws ModelNotFoundException Jika model tidak ditemukan.
     */
    public function update(int|string $id, array $data): ?PendingDispatch
    {
        $requiredKeys = ['work_day_start', 'work_day_finish', 'user_id', 'time_work_id'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required data: '{$key}' is not provided.");
            }
        }

        if (!is_array($data['user_id']) || empty($data['user_id'])) {
            throw new InvalidArgumentException("'user_id' must be a non-empty array of user IDs.");
        }

        // Parsing dan Validasi Tanggal
        try {
            $workDayStart = Carbon::parse($data['work_day_start'])->timezone(config('app.timezone'));
            $workDayFinish = Carbon::parse($data['work_day_finish'])->timezone(config('app.timezone'));
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                "Invalid date format for 'work_day_start' or 'work_day_finish'. Details: " . $e->getMessage()
            );
        }

        if ($workDayStart->greaterThan($workDayFinish)) {
            throw new InvalidArgumentException('Start date cannot be greater than finish date.');
        }

        $scheduleEntries = [];
        $skipDays = $data['dayoff'] ?? [];

        $currentDay = $workDayStart->clone();
        while ($currentDay->lte($workDayFinish)) {
            $dayName = $currentDay->format('l');

            if (!in_array($dayName, $skipDays)) {
                foreach ($data['user_id'] as $userId) {
                    $scheduleEntries[] = [
                        'user_id' => $userId,
                        'time_work_id' => $data['time_work_id'],
                        'work_day' => $currentDay->toDateString(),
                        'created_at' => Carbon::now(), // Tambahkan timestamp
                        'updated_at' => Carbon::now(), // Tambahkan timestamp
                    ];
                }
            }
            $currentDay->addDay();
        }

        if (!empty($scheduleEntries)) {
            return InsertUpdateScheduleJob::dispatch($scheduleEntries);
        }
        return null;
    }

    /**
     * Menghapus (soft delete) model berdasarkan ID.
     *
     * @param int|string $id ID model yang akan dihapus.
     * @return bool True jika berhasil dihapus, false jika tidak.
     * @throws ModelNotFoundException Jika model tidak ditemukan.
     */
    public function delete(int|string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }

    /**
     * Mengekspor data jadwal pengguna berdasarkan kriteria filter.
     *
     * @param string|null $name Filter berdasarkan nama.
     * @param string|null $createdAt Filter berdasarkan tanggal dibuat.
     * @param string|null $updatedAt Filter berdasarkan tanggal diperbarui.
     * @param string|null $startRange Filter berdasarkan rentang awal tanggal dibuat.
     * @param string|null $endRange Filter berdasarkan rentang akhir tanggal dibuat.
     * @return Collection|array Mengembalikan koleksi model atau array jika tidak ada data ditemukan.
     */
    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null
    ): Collection|array {
        ini_set('memory_limit', '512M');

        $query = $this->model->newQuery();

        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if (!empty($createdAt)) {
            $query->whereDate('created_at', $createdAt);
        }
        if (!empty($updatedAt)) {
            $query->whereDate('updated_at', $updatedAt);
        }
        // Pastikan $startRange dan $endRange adalah tanggal yang valid untuk whereBetween
        if (!empty($startRange) && !empty($endRange)) {
            // Gunakan Carbon::parse untuk memastikan format yang benar jika input tidak standar 'YYYY-MM-DD HH:MM:SS'
            $query->whereBetween('created_at', [
                Carbon::parse($startRange)->startOfDay(),
                Carbon::parse($endRange)->endOfDay()
            ]);
        }
        $data = $query->get();

        if ($data->isEmpty()) {
            // Dalam repository, lebih baik mengembalikan koleksi kosong atau melempar exception
            // daripada membuat respons HTTP. Controller yang harus menangani respons HTTP.
            return []; // Kembalikan array kosong atau throw new ModelNotFoundException("Tidak ada data ditemukan.");
        }
        return $data;
    }

    /**
     * Mengimpor data dari file.
     * Logika ini sebaiknya berada di Service atau Import Class, bukan Repository.
     *
     * @param mixed $file File yang akan diimpor.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function import($file): bool
    {
        // PENTING: Logika import file (misalnya parsing CSV/Excel)
        // sangat kompleks dan tidak cocok di Repository.
        // Pindahkan ini ke Service Layer atau sebuah kelas Importer yang terpisah.
        // Contoh: return (new UserTimeworkScheduleImport)->import($file);
        return true;
    }
}
