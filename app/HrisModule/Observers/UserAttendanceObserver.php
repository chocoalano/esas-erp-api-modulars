<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\UserAttendance;
use Illuminate\Support\Facades\Auth;

class UserAttendanceObserver
{
    /**
     * Handle the UserAttendance "created" event.
     */
    public function created(UserAttendance $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the UserAttendance "updated" event.
     */
    public function updated(UserAttendance $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the UserAttendance "deleted" event.
     */
    public function deleted(UserAttendance $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the UserAttendance "restored" event.
     */
    public function restored(UserAttendance $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the UserAttendance "force deleted" event.
     */
    public function forceDeleted(UserAttendance $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
