<?php
use Illuminate\Support\Facades\Route;
use App\DashboardModule\Controllers\HrisController;

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('hris-dashboard')
            ->controller(HrisController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/attendance-chart', 'absen_chart');
                Route::get('/company', 'company');
            });
    });
