<?php

namespace Database\Seeders;

use App\GeneralModule\Models\Permission;
use App\GeneralModule\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $actions = [
        //     'view',
        //     'view_any',
        //     'create',
        //     'update',
        //     'delete',
        //     'delete_any',
        //     'forcedelete',
        //     'forcedelete_any',
        //     'restore',
        //     'export',
        //     'import',
        // ];

        // $tables = [
        //     'announcements',
        //     'bug_reports',
        //     'companies',
        //     'documentations',
        //     'roles',
        //     'users',
        //     'departements',
        //     'job_levels',
        //     'job_positions',
        //     'permits',
        //     'permit_types',
        //     'time_workes',
        //     'user_attendances',
        // ];

        // $allPermissions = [];

        // foreach ($tables as $table) {
        //     foreach ($actions as $action) {
        //         $permissionName = "{$action}_{$table}";

        //         $permission = Permission::firstOrCreate([
        //             'name' => $permissionName,
        //             'guard_name' => 'web'
        //         ]);

        //         $allPermissions[] = $permission;
        //     }
        // }

        // // Assign all permissions to all roles
        // $roles = Role::all();
        // foreach ($roles as $role) {
        //     $role->syncPermissions($allPermissions);
        // }

        // $user = \App\GeneralModule\Models\User::find(48);
        // $user->assignRole('super_admin');
        $this->call(penyesuaian_absensi::class);
    }
}
