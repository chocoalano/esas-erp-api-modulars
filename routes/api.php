<?php

use Illuminate\Support\Facades\Route;

require base_path('app/GeneralModule/routes.php');
require base_path('app/HrisModule/routes.php');
require base_path('app/DashboardModule/routes.php');
require base_path('app/WorkOrdersModule/routes.php');

Route::get('unauthorized', function () {
    return response()->json(["access" => "unauthorized"], 401);
})->name('unauthorized');
