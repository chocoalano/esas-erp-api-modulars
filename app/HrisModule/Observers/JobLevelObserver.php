<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\JobLevel;
use Illuminate\Support\Facades\Auth;

class JobLevelObserver
{
    /**
     * Handle the JobLevel "created" event.
     */
    public function created(JobLevel $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the JobLevel "updated" event.
     */
    public function updated(JobLevel $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the JobLevel "deleted" event.
     */
    public function deleted(JobLevel $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the JobLevel "restored" event.
     */
    public function restored(JobLevel $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the JobLevel "force deleted" event.
     */
    public function forceDeleted(JobLevel $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
