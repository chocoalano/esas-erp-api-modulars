<?php
use App\HrisModule\Controllers\DepartementController;
use App\HrisModule\Controllers\JobLevelController;
use App\HrisModule\Controllers\JobPositionController;
use App\HrisModule\Controllers\PermitController;
use App\HrisModule\Controllers\PermitTypeController;
use App\HrisModule\Controllers\TimeUserScheduleController;
use App\HrisModule\Controllers\TimeWorkeController;
use App\HrisModule\Controllers\UserAttendanceController;
use Illuminate\Support\Facades\Route;


Route::prefix('hris-module')->group(function(){
    Route::prefix('user-attendances')
        ->controller(UserAttendanceController::class)
        ->group(function () {
            Route::get('presence/form-frd-attendance', 'presence_form_frd');
            Route::post('presence/form-frd-attendance', 'presence_form_attendance_frd');

            Route::get('presence/form-qr', 'presence_form_qr');
            Route::post('presence/form-qr', 'presence_form_qr_submit');
        });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('hris-module')->group(function () {
        Route::prefix('departement')
            ->controller(DepartementController::class)
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
        Route::prefix('job-positions')
            ->controller(JobPositionController::class)
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
        Route::prefix('job-levels')
            ->controller(JobLevelController::class)
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
        Route::prefix('permits')
            ->controller(PermitController::class)
            ->group(function () {
                Route::get('/', 'index')->name('permits.index');
                Route::get('/list/{typeId}', 'list_type')->name('permits.list');
                Route::get('create', 'create')->name('permits.create');
                Route::post('/', 'store')->name('permits.store');
                Route::get('deleted', 'deleted')->name('permits.deleted');
                Route::get('export', 'xlsx')->name('permits.xlsx');
                Route::get('print', 'print')->name('permits.print');
                Route::post('import', 'import')->name('permits.import');
                Route::get('{id}', 'show')->name('permits.show');
                Route::get('{id}/edit', 'edit')->name('permits.edit');
                Route::put('{id}', 'update')->name('permits.update');
                Route::put('{id}/approval', 'approval')->name('permits.approval');
                Route::delete('{id}', 'destroy')->name('permits.destroy');
                Route::post('{id}/restore', 'restore')->name('permits.restore');
                Route::delete('{id}/force', 'forceDelete')->name('permits.forceDelete');
            });
        Route::prefix('permit-types')
            ->controller(PermitTypeController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/list', 'list');
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
        Route::prefix('time-workes')
            ->controller(TimeWorkeController::class)
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
        Route::prefix('time-user-schedules')
            ->controller(TimeUserScheduleController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/filter-paginate', 'filter_paginate');
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
        Route::prefix('user-attendances')
            ->controller(UserAttendanceController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/filter-paginate', 'filter_table');
                Route::get('create', 'create');
                Route::post('/', 'store');
                // Route::get('presence/form-frd-attendance', 'presence_form_frd');
                // Route::post('presence/form-frd-attendance', 'presence_form_attendance_frd');
                // Route::get('presence/form-qr', 'presence_form_qr');
                // Route::post('presence/form-qr', 'presence_form_qr_submit');
                Route::post('presence/form-qr-attendance', 'presence_form_attendance_qr');
                Route::get('deleted', 'deleted');
                Route::get('export', 'xlsx');
                Route::get('report', 'xlsx_report');
                Route::get('print', 'print');
                Route::post('import', 'import');
                Route::get('{id}', 'show');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
                Route::post('{id}/restore', 'restore');
                Route::delete('{id}/force', 'forceDelete');
            });
    });
});
