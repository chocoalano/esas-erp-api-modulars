<?php

namespace App\HrisModule\Providers;

use App\HrisModule\Models\Departement;
use App\HrisModule\Models\JobLevel;
use App\HrisModule\Models\JobPosition;
use App\HrisModule\Models\Permit;
use App\HrisModule\Models\PermitType;
use App\HrisModule\Models\TimeWorke;
use App\HrisModule\Models\UserAttendance;

use App\HrisModule\Observers\DepartementObserver;
use App\HrisModule\Observers\JobLevelObserver;
use App\HrisModule\Observers\JobPositionObserver;
use App\HrisModule\Observers\PermitObserver;
use App\HrisModule\Observers\PermitTypleObserver;
use App\HrisModule\Observers\TimeWorkeObserver;
use App\HrisModule\Observers\UserAttendanceObserver;

use App\HrisModule\Repositories\Contracts\DepartementRepositoryInterface;
use App\HrisModule\Repositories\Contracts\JobLevelRepositoryInterface;
use App\HrisModule\Repositories\Contracts\JobPositionRepositoryInterface;
use App\HrisModule\Repositories\Contracts\PermitRepositoryInterface;
use App\HrisModule\Repositories\Contracts\PermitTypeRepositoryInterface;
use App\HrisModule\Repositories\Contracts\TimeUserScheduleRepositoryInterface;
use App\HrisModule\Repositories\Contracts\TimeWorkeRepositoryInterface;
use App\HrisModule\Repositories\Contracts\UserAttendanceRepositoryInterface;

use App\HrisModule\Repositories\DepartementRepository;
use App\HrisModule\Repositories\JobLevelRepository;
use App\HrisModule\Repositories\JobPositionRepository;
use App\HrisModule\Repositories\PermitRepository;
use App\HrisModule\Repositories\PermitTypeRepository;
use App\HrisModule\Repositories\TimeUserScheduleRepository;
use App\HrisModule\Repositories\TimeWorkeRepository;
use App\HrisModule\Repositories\UserAttendanceRepository;

use Illuminate\Support\ServiceProvider;

class HrisModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DepartementRepositoryInterface::class,
            DepartementRepository::class
        );
        $this->app->bind(
            JobPositionRepositoryInterface::class,
            JobPositionRepository::class
        );
        $this->app->bind(
            JobLevelRepositoryInterface::class,
            JobLevelRepository::class
        );
        $this->app->bind(
            PermitRepositoryInterface::class,
            PermitRepository::class
        );
        $this->app->bind(
            PermitTypeRepositoryInterface::class,
            PermitTypeRepository::class
        );
        $this->app->bind(
            TimeWorkeRepositoryInterface::class,
            TimeWorkeRepository::class
        );
        $this->app->bind(
            UserAttendanceRepositoryInterface::class,
            UserAttendanceRepository::class
        );
        $this->app->bind(
            TimeUserScheduleRepositoryInterface::class,
            TimeUserScheduleRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Departement::observe(DepartementObserver::class);
        JobLevel::observe(JobLevelObserver::class);
        JobPosition::observe(JobPositionObserver::class);
        Permit::observe(PermitObserver::class);
        PermitType::observe(PermitTypleObserver::class);
        TimeWorke::observe(TimeWorkeObserver::class);
        UserAttendance::observe(UserAttendanceObserver::class);
    }
}
