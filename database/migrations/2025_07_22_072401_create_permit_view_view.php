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
        DB::statement("CREATE VIEW `permit_view` AS select `p`.`id` AS `id`,`p`.`permit_numbers` AS `permit_numbers`,`p`.`user_id` AS `user_id`,`p`.`permit_type_id` AS `permit_type_id`,`p`.`user_timework_schedule_id` AS `user_timework_schedule_id`,`p`.`timein_adjust` AS `timein_adjust`,`p`.`timeout_adjust` AS `timeout_adjust`,`p`.`current_shift_id` AS `current_shift_id`,`p`.`adjust_shift_id` AS `adjust_shift_id`,`p`.`start_date` AS `start_date`,`p`.`end_date` AS `end_date`,`p`.`start_time` AS `start_time`,`p`.`end_time` AS `end_time`,`p`.`notes` AS `notes`,`p`.`file` AS `file`,`p`.`created_at` AS `created_at`,`p`.`updated_at` AS `updated_at`,`pt`.`type` AS `type`,`pt`.`is_payed` AS `is_payed`,`pt`.`approve_line` AS `approve_line`,`pt`.`approve_manager` AS `approve_manager`,`pt`.`approve_hr` AS `approve_hr`,`u`.`name` AS `user_name`,`u`.`nip` AS `nip`,`d`.`name` AS `departement_name` from ((((`permits` `p` join `permit_types` `pt` on((`p`.`permit_type_id` = `pt`.`id`))) join `users` `u` on((`p`.`user_id` = `u`.`id`))) join `user_employes` `ue` on((`u`.`id` = `ue`.`user_id`))) join `departements` `d` on((`ue`.`departement_id` = `d`.`id`)))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `permit_view`");
    }
};
