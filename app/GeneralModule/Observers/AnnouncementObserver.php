<?php

namespace App\GeneralModule\Observers;

use App\Console\Support\Logger;
use App\GeneralModule\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class AnnouncementObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Announcement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('created', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Announcement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('updated', $model, $model->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Announcement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('deleted', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Announcement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('restored', $model);
        } else {
            return;
        }
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Announcement $model): void
    {
        if (!Auth::user()->hasRole('super_admin')) {
            Logger::log('force_deleted', $model);
        } else {
            return;
        }
    }
}
