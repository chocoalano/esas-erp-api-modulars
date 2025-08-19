<?php

namespace App\WorkOrdersModule\Providers;

use App\WorkOrdersModule\Repositories\Contracts\WoDesignRepositoryInterface;
use App\WorkOrdersModule\Repositories\Contracts\WoIctMtcRepositoryInterface;
use App\WorkOrdersModule\Repositories\WoDesignRepository;
use App\WorkOrdersModule\Repositories\WoIctMtcRepository;
use Illuminate\Support\ServiceProvider;

class WorkOrdersModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            WoDesignRepositoryInterface::class,
            WoDesignRepository::class
        );
        $this->app->bind(
            WoIctMtcRepositoryInterface::class,
            WoIctMtcRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
