<?php

use App\WorkOrdersModule\Controllers\WoDesignController;
use App\WorkOrdersModule\Controllers\WoIctMtcController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('workorder-module')
            ->group(function () {
                Route::prefix('design')
                    ->controller(WoDesignController::class)
                    ->group(function () {
                        // Route kustom atau tambahan
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
                        Route::put('{id}/approval', 'approval');
                        Route::delete('{id}', 'destroy');
                        Route::post('{id}/restore', 'restore');
                        Route::delete('{id}/force', 'forceDelete');
                    });
                    Route::prefix('wo-ict-mtcs')
                        ->controller(WoIctMtcController::class)
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
                            Route::put('{id}/services', 'services');
                            Route::put('{id}/signoff', 'signoff');
                            Route::put('{id}/clearance', 'clearance');
                            Route::delete('{id}', 'destroy');
                            Route::post('{id}/restore', 'restore');
                            Route::delete('{id}/force', 'forceDelete');
                        });
            });
    });
