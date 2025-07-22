<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\TimeWorke;
use Illuminate\Support\Facades\Auth;

class TimeWorkeObserver
{
    /**
     * Handle the TimeWorke "created" event.
     */
    public function created(TimeWorke $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the TimeWorke "updated" event.
     */
    public function updated(TimeWorke $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the TimeWorke "deleted" event.
     */
    public function deleted(TimeWorke $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the TimeWorke "restored" event.
     */
    public function restored(TimeWorke $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the TimeWorke "force deleted" event.
     */
    public function forceDeleted(TimeWorke $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
