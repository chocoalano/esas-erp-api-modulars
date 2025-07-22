<?php

namespace App\DashboardModule\Repositories\Contracts;

interface HrisRepositoryInterface
{
    public function userCount(?int $companyId, ?string $startDate, ?string $endDate): mixed;
    public function departemenCount(?int $companyId, ?string $startDate, ?string $endDate): mixed;
    public function positionCount(?int $companyId, ?string $startDate, ?string $endDate): mixed;
    public function attendanceCount(?int $companyId, ?string $startDate, ?string $endDate): mixed;
    public function permitCount(?int $companyId, ?string $startDate, ?string $endDate): mixed;
    public function AttendanceNowCount(?int $companyId): mixed;
    public function AttendanceAlphaNowCount(?int $companyId): mixed;
    public function AttendanceLateCount(?int $companyId): mixed;
    public function AttendanceChart(?int $companyId, ?string $startDate, ?string $endDate, ?bool $now): mixed;
}
