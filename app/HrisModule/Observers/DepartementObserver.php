<?php

namespace App\HrisModule\Observers;

use App\Console\Support\Logger;
use App\HrisModule\Models\Departement;
use Illuminate\Support\Facades\Auth;

class DepartementObserver
{
    /**
     * Handle the Departement "created" event.
     */
    public function created(Departement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Departement "updated" event.
     */
    public function updated(Departement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the Departement "deleted" event.
     */
    public function deleted(Departement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Departement "restored" event.
     */
    public function restored(Departement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Departement "force deleted" event.
     */
    public function forceDeleted(Departement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
