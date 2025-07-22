<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\PermitType;
use Illuminate\Support\Facades\Auth;

class PermitTypleObserver
{
    /**
     * Handle the PermitType "created" event.
     */
    public function created(PermitType $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the PermitType "updated" event.
     */
    public function updated(PermitType $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the PermitType "deleted" event.
     */
    public function deleted(PermitType $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the PermitType "restored" event.
     */
    public function restored(PermitType $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the PermitType "force deleted" event.
     */
    public function forceDeleted(PermitType $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
