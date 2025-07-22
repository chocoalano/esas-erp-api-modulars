<?php

namespace App\DashboardModule\Repositories;

use App\DashboardModule\Repositories\Contracts\HrisRepositoryInterface;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\JobPosition;
use App\HrisModule\Models\Permit;
use App\HrisModule\Models\UserAttendance;
use Illuminate\Support\Carbon;

class HrisRepository implements HrisRepositoryInterface
{
    public function userCount(?int $companyId, ?string $startDate, ?string $endDate): int
    {
        $model = User::query();
        if ($companyId !== null) { // Use strict comparison
            $model->where('company_id', $companyId);
        }
        // if ($startDate !== null) {
        //     $model->whereDate('created_at', '>=', $startDate); // Pass Carbon object directly
        // }
        // if ($endDate !== null) {
        //     $model->whereDate('created_at', '<=', $endDate); // Pass Carbon object directly
        // }
        return $model->count();
    }

    public function departemenCount(?int $companyId, ?string $startDate, ?string $endDate): int
    {
        $model = Departement::query();
        if ($companyId !== null) {
            $model->where('company_id', $companyId);
        }
        // if ($startDate !== null) {
        //     $model->whereDate('created_at', '>=', $startDate);
        // }
        // if ($endDate !== null) {
        //     $model->whereDate('created_at', '<=', $endDate);
        // }
        return $model->count();
    }

    public function positionCount(?int $companyId, ?string $startDate, ?string $endDate): int
    {
        $model = JobPosition::query();
        if ($companyId !== null) {
            $model->where('company_id', $companyId);
        }
        // if ($startDate !== null) {
        //     $model->whereDate('created_at', '>=', $startDate);
        // }
        // if ($endDate !== null) {
        //     $model->whereDate('created_at', '<=', $endDate);
        // }
        return $model->count();
    }

    public function attendanceCount(?int $companyId, ?string $startDate, ?string $endDate): int
    {
        $model = UserAttendance::query();
        if ($companyId !== null) {
            $model->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        }
        // if ($startDate !== null) {
        //     $model->whereDate('created_at', '>=', $startDate);
        // }
        // if ($endDate !== null) {
        //     $model->whereDate('created_at', '<=', $endDate);
        // }
        return $model->count();
    }

    public function permitCount(?int $companyId, ?string $startDate, ?string $endDate): int
    {
        $model = Permit::query();
        if ($companyId !== null) {
            $model->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        }
        // if ($startDate !== null) {
        //     $model->whereDate('created_at', '>=', $startDate);
        // }
        // if ($endDate !== null) {
        //     $model->whereDate('created_at', '<=', $endDate);
        // }
        return $model->count();
    }

    public function AttendanceNowCount(?int $companyId): int
    {
        $model = UserAttendance::query();
        if ($companyId !== null) {
            $model->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        }
        $model->whereDate('created_at', now()->toDateString());
        return $model->count();
    }

    public function AttendanceAlphaNowCount(?int $companyId): int
    {
        $model = UserAttendance::query();
        if ($companyId !== null) {
            $model->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        }
        $model->where('status_in', 'alpha');
        $model->whereDate('created_at', now()->toDateString());
        return $model->count();
    }

    public function AttendanceLateCount(?int $companyId): int
    {
        $model = UserAttendance::query();
        if ($companyId !== null) {
            $model->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        }
        $model->where('status_in', 'late');
        $model->whereDate('created_at', now()->toDateString());
        return $model->count();
    }

    /**
     * Retrieves attendance chart data.
     *
     * @param int|null $companyId The ID of the company, or null.
     * @param DateTimeInterface|null $startDate The start date for the chart.
     * @param DateTimeInterface|null $endDate The end date for the chart.
     * @param bool|null $now Flag to indicate if data for 'today' is requested.
     * @return array The chart data in a structured array.
     */
    public function AttendanceChart(
        ?int $companyId,
        ?string $startDate, // These are strings coming from the controller
        ?string $endDate,   // These are strings coming from the controller
        ?bool $now
    ): array {
        // --- FIX: Parse date strings into Carbon objects ---
        // Only parse if the string is not null
        $start = $startDate !== null ? Carbon::parse($startDate ?? Carbon::now()) : null;
        $end = $endDate !== null ? Carbon::parse($endDate ?? Carbon::now()) : null;

        $query = UserAttendance::query();

        // Filter berdasarkan company_id jika ada
        if ($companyId !== null) {
            $query->whereHas('user', fn($q) => $q->where('company_id', $companyId));
        }

        // Filter tanggal
        if ($now === true) {
            // Jika request minta data hari ini
            $query->whereDate('created_at', Carbon::now()->toDateString());
        } elseif ($start !== null && $end !== null) { // Use the parsed Carbon objects $start and $end
            // Jika request minta data berdasarkan range
            $query->whereBetween('created_at', [
                $start->startOfDay(), // Ensure full day range
                $end->endOfDay()      // Ensure full day range
            ]);
        } else {
            // Default range: 26th of last month to 26th of this month (or today if before 26th)
            $defaultStartDate = Carbon::now()->subMonthNoOverflow()->day(26)->startOfDay();
            $defaultEndDate = Carbon::now()->day >= 26 ? Carbon::now()->day(26)->endOfDay() : Carbon::now()->endOfDay();

            $query->whereBetween('created_at', [
                $defaultStartDate,
                $defaultEndDate
            ]);
        }

        // Query data dan kelompokkan
        $attendances = $query
            ->selectRaw("
            DATE(created_at) as date,
            SUM(CASE WHEN status_in = 'normal' OR status_in = 'unlate' THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN status_in = 'late' THEN 1 ELSE 0 END) as telat
        ")
            ->groupByRaw("DATE(created_at)")
            ->orderByRaw("DATE(created_at)")
            ->get();

        // Format hasil untuk chart
        $labels = $attendances->pluck('date');
        $normal = $attendances->pluck('normal')->map(fn($val) => (int) $val);
        $telat = $attendances->pluck('telat')->map(fn($val) => (int) $val);

        return [
            'labels' => $labels,
            'normal' => $normal,
            'telat' => $telat,
        ];
    }
}
