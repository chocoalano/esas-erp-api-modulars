<?php
use App\GeneralModule\Controllers\AnnouncementController;
use App\GeneralModule\Controllers\AuthController;
use App\GeneralModule\Controllers\BugReportController;
use App\GeneralModule\Controllers\CompanyController;
use App\GeneralModule\Controllers\DocumentationController;
use App\GeneralModule\Controllers\NotificationController;
use App\GeneralModule\Controllers\RoleController;
use App\GeneralModule\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('general-module')->group(function () {
    Route::prefix('auth')
        ->controller(AuthController::class)
        ->group(function () {
            Route::post('/login', 'store');
            Route::get('/', 'profile')->middleware('auth:sanctum');
            Route::put('/', 'profile_update')->middleware('auth:sanctum');
            Route::post('/', 'profile_update_avatar')->middleware('auth:sanctum');
            Route::post('/change-password', 'profile_update_password')->middleware('auth:sanctum');
            Route::post('/set-token', 'set_token')->middleware('auth:sanctum');
            Route::get('logout', 'logout')->middleware('auth:sanctum');
            Route::get('refresh-token', 'refresh_token')->middleware('auth:sanctum');
            Route::get('setting', 'setting')->middleware('auth:sanctum');

            Route::post('/store-device-token', 'store_device_token')->middleware('auth:sanctum');
            Route::post('/set-imei', 'set_imei')->middleware('auth:sanctum');
            Route::get('/schedule', 'schedule')->middleware('auth:sanctum');
            Route::get('/current-attendance/{userId}', 'current_attendance')->middleware('auth:sanctum');
            Route::get('/activity', 'activity')->middleware('auth:sanctum');
            Route::get('/summary-absen', 'summary_absen')->middleware('auth:sanctum');
        });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('general-module')->group(function () {
        Route::prefix('users')
            ->controller(UserController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/filter-paginate', 'filter_paginate');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('deleted', 'deleted');
                Route::get('export', 'xlsx');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/logs', 'logs');
                Route::get('{id}/edit', 'edit');
                Route::get('{id}/password-reset', 'password_reset');
                Route::get('{id}/device-reset', 'device_reset');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
                Route::post('{id}/restore', 'restore');
                Route::delete('{id}/force', 'forceDelete');
            });
        Route::prefix('roles')
            ->controller(RoleController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('export', 'xlsx');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
            });
        Route::prefix('companies')
            ->controller(CompanyController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('deleted', 'deleted');
                Route::get('export', 'xlsx');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
                Route::post('{id}/restore', 'restore');
                Route::delete('{id}/force', 'forceDelete');
            });
        Route::prefix('announcements')
            ->controller(AnnouncementController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/active', 'indexActive');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('export', 'xlsx');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
            });
        Route::prefix('bug-reports')
            ->controller(BugReportController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('export', 'xlsx');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
            });
        Route::prefix('documentations')
            ->controller(DocumentationController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/public', 'public');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('export', 'xlsx');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
            });
        Route::prefix('notifications')
            ->controller(NotificationController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('{id}', 'show');
                Route::delete('{id}', 'destroy');
            });
    });
});
