<?php

namespace App\DashboardModule\Services;
use App\DashboardModule\Repositories\Contracts\HrisRepositoryInterface;

class HrisService
{
    public function __construct(protected HrisRepositoryInterface $repo)
    {
    }
    public function index(?int $companyId, ?string $startDate, ?string $endDate): mixed
    {
        $userCount = $this->repo->userCount($companyId, $startDate, $endDate);
        $departemenCount = $this->repo->departemenCount($companyId, $startDate, $endDate);
        $positionCount = $this->repo->positionCount($companyId, $startDate, $endDate);
        $attendanceCount = $this->repo->attendanceCount($companyId, $startDate, $endDate);
        $permitCount = $this->repo->permitCount($companyId, $startDate, $endDate);
        $AttendanceNowCount = $this->repo->AttendanceNowCount($companyId);
        $AttendanceAlphaNowCount = $this->repo->AttendanceAlphaNowCount($companyId);
        $AttendanceLateCount = $this->repo->AttendanceLateCount($companyId);
        return [
            'userCount' => $userCount,
            'departemenCount' => $departemenCount,
            'positionCount' => $positionCount,
            'attendanceCount' => $attendanceCount,
            'permitCount' => $permitCount,
            'AttendanceNowCount' => $AttendanceNowCount,
            'AttendanceAlphaNowCount' => $AttendanceAlphaNowCount,
            'AttendanceLateCount' => $AttendanceLateCount,
        ];
    }
    public function AttendanceChart(?int $companyId, ?string $startDate, ?string $endDate, ?bool $now): mixed
    {
        return $this->repo->AttendanceChart($companyId, $startDate, $endDate, $now);
    }
}
