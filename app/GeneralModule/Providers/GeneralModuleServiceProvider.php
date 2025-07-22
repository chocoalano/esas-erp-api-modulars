<?php

namespace App\GeneralModule\Providers;

use App\GeneralModule\Models\Announcement;
use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\GeneralModule\Observers\AnnouncementObserver;
use App\GeneralModule\Observers\CompanyObserver;
use App\GeneralModule\Observers\UserObserver;
use App\GeneralModule\Repositories\AnnouncementRepository;
use App\GeneralModule\Repositories\AuthRepository;
use App\GeneralModule\Repositories\BugReportRepository;
use App\GeneralModule\Repositories\CompanyRepository;
use App\GeneralModule\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\AuthRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\BugReportRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\CompanyRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\DocumentationRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\NotificationRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\RoleRepositoryInterface;
use App\GeneralModule\Repositories\Contracts\UserRepositoryInterface;
use App\GeneralModule\Repositories\DocumentationRepository;
use App\GeneralModule\Repositories\NotificationRepository;
use App\GeneralModule\Repositories\RoleRepository;
use App\GeneralModule\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class GeneralModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuthRepositoryInterface::class,
            AuthRepository::class
        );
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );
        $this->app->bind(
            CompanyRepositoryInterface::class,
            CompanyRepository::class
        );
        $this->app->bind(
            AnnouncementRepositoryInterface::class,
            AnnouncementRepository::class
        );
        $this->app->bind(
            BugReportRepositoryInterface::class,
            BugReportRepository::class
        );
        $this->app->bind(
            DocumentationRepositoryInterface::class,
            DocumentationRepository::class
        );
        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Company::observe(CompanyObserver::class);
        Announcement::observe(AnnouncementObserver::class);
    }
}
