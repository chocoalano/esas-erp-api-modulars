<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as Roles;
use Spatie\Permission\Models\Permission;

/**
 * Class Role
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Role extends Roles
{
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_has_permissions',       // pivot table yang benar
            'role_id',          // foreign key pada pivot untuk Role
            'permission_id'     // foreign key pada pivot untuk Permission
        );
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'model_has_roles',       // pivot table yang benar
            'role_id',     // foreign key pada pivot untuk Role
            'model_id'     // foreign key pada pivot untuk Permission
        );
    }

    public function getPermissionIdsAttribute(): array
    {
        // Pastikan relasi sudah dimuat sebelum diakses
        // Jika belum dimuat, ini akan memuatnya secara lazy
        return $this->permissions->pluck('id')->toArray();
    }

    /**
     * Mendapatkan daftar ID user.
     */
    public function getUserIdsAttribute(): array
    {
        // Pastikan relasi sudah dimuat sebelum diakses
        return $this->users->pluck('id')->toArray();
    }

    /**
     * Menambahkan atribut ke array/JSON representasi model.
     * Sesuaikan 'permission_ids' dan 'user_ids' dengan nama yang Anda inginkan di respons JSON.
     */
    protected $appends = ['permission_ids', 'user_ids'];

    /**
     * Menyembunyikan atribut asli 'permissions' dan 'users' dari respons JSON.
     */
    protected $hidden = ['permissions', 'users'];
}
