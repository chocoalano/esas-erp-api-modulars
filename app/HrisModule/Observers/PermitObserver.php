<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\Permit;
use Illuminate\Support\Facades\Auth;

class PermitObserver
{
    /**
     * Handle the Permit "created" event.
     */
    public function created(Permit $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Permit "updated" event.
     */
    public function updated(Permit $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the Permit "deleted" event.
     */
    public function deleted(Permit $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Permit "restored" event.
     */
    public function restored(Permit $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Permit "force deleted" event.
     */
    public function forceDeleted(Permit $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
