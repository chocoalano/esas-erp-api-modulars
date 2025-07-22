<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE VIEW `attendance_view` AS select `ua`.`id` AS `id`,`u`.`id` AS `user_id`,`u`.`company_id` AS `company_id`,`u`.`name` AS `name`,`u`.`nip` AS `nip`,`u`.`avatar` AS `avatar`,`ue`.`departement_id` AS `departement_id`,`ue`.`job_position_id` AS `job_position_id`,`ue`.`job_level_id` AS `job_level_id`,`ue`.`approval_line_id` AS `approval_line_id`,`ue`.`approval_manager_id` AS `approval_manager_id`,`ue`.`join_date` AS `join_date`,`ue`.`sign_date` AS `sign_date`,`d`.`name` AS `departement`,`jp`.`name` AS `position`,`jl`.`name` AS `level`,`uts`.`work_day` AS `work_day`,`tw`.`name` AS `shiftname`,`tw`.`in` AS `in`,`tw`.`out` AS `out`,`ua`.`user_timework_schedule_id` AS `user_timework_schedule_id`,`ua`.`time_in` AS `time_in`,`ua`.`lat_in` AS `lat_in`,`ua`.`long_in` AS `long_in`,`ua`.`image_in` AS `image_in`,`ua`.`status_in` AS `status_in`,`ua`.`time_out` AS `time_out`,`ua`.`lat_out` AS `lat_out`,`ua`.`long_out` AS `long_out`,`ua`.`image_out` AS `image_out`,`ua`.`status_out` AS `status_out`,`ua`.`created_at` AS `created_at`,`ua`.`updated_at` AS `updated_at` from ((((((((`users` `u` join `user_employes` `ue` on((`u`.`id` = `ue`.`user_id`))) join `departements` `d` on((`ue`.`departement_id` = `d`.`id`))) join `job_positions` `jp` on((`ue`.`job_position_id` = `jp`.`id`))) join `job_levels` `jl` on((`ue`.`job_level_id` = `jl`.`id`))) join `user_attendances` `ua` on((`u`.`id` = `ua`.`user_id`))) join `companies` `c` on((`u`.`company_id` = `c`.`id`))) left join `user_timework_schedules` `uts` on((`uts`.`id` = `ua`.`user_timework_schedule_id`))) left join `time_workes` `tw` on((`tw`.`id` = `uts`.`time_work_id`)))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `attendance_view`");
    }
};
