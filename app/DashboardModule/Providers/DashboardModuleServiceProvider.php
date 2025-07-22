<?php

namespace App\DashboardModule\Providers;

use App\DashboardModule\Repositories\Contracts\HrisRepositoryInterface;
use App\DashboardModule\Repositories\HrisRepository;
use Illuminate\Support\ServiceProvider;

class DashboardModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            HrisRepositoryInterface::class,
            HrisRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
