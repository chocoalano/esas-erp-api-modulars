<?php

namespace App\GeneralModule\Observers;

use App\Console\Support\Logger;
use App\GeneralModule\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if (!$user->hasRole('super_admin')) {
            Logger::log('created', $user);
        } else {
            return;
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (!$user->hasRole('super_admin')) {
            Logger::log('updated', $user, $user->getChanges());
        } else {
            return;
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if (!$user->hasRole('super_admin')) {
            Logger::log('deleted', $user);
        } else {
            return;
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        if (!$user->hasRole('super_admin')) {
            Logger::log('restored', $user);
        } else {
            return;
        }
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        if (!$user->hasRole('super_admin')) {
            Logger::log('force_deleted', $user);
        } else {
            return;
        }
    }
}
