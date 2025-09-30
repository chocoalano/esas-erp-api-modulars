<?php

namespace Database\Seeders;

use App\HrisModule\Models\UserAttendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Jobs\PenyesuaianAbsensiJob;

class penyesuaian_absensi extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user_attendances = [];

        dispatch(new PenyesuaianAbsensiJob($user_attendances));

        $this->command->info('✅ Data absensi telah di-update atau dibuat (upsert) dengan sukses!');
    }
}
