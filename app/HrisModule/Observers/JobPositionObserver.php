<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\JobPosition;
use Illuminate\Support\Facades\Auth;

class JobPositionObserver
{
    /**
     * Handle the JobPosition "created" event.
     */
    public function created(JobPosition $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the JobPosition "updated" event.
     */
    public function updated(JobPosition $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the JobPosition "deleted" event.
     */
    public function deleted(JobPosition $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the JobPosition "restored" event.
     */
    public function restored(JobPosition $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the JobPosition "force deleted" event.
     */
    public function forceDeleted(JobPosition $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
