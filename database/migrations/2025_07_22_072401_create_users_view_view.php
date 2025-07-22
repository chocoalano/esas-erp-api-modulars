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
        DB::statement("CREATE VIEW `users_view` AS select `u`.`id` AS `id`,`u`.`company_id` AS `company_id`,`u`.`name` AS `name`,`u`.`nip` AS `nip`,`u`.`email` AS `email`,`u`.`email_verified_at` AS `email_verified_at`,`u`.`password` AS `password`,`u`.`avatar` AS `avatar`,`u`.`status` AS `status`,`u`.`remember_token` AS `remember_token`,`u`.`device_id` AS `device_id`,`u`.`created_at` AS `created_at`,`u`.`updated_at` AS `updated_at`,`c`.`name` AS `company`,`c`.`latitude` AS `company_lat`,`c`.`longitude` AS `company_long`,`c`.`radius` AS `company_radius`,`c`.`full_address` AS `company_address`,`us`.`basic_salary` AS `basic_salary`,`us`.`payment_type` AS `payment_type`,`ue`.`bank_name` AS `bank_name`,`ue`.`bank_number` AS `bank_number`,`ue`.`bank_holder` AS `bank_holder`,`ud`.`phone` AS `phone`,`ud`.`placebirth` AS `placebirth`,`ud`.`datebirth` AS `datebirth`,`ud`.`gender` AS `gender`,`ud`.`blood` AS `blood`,`ud`.`marital_status` AS `marital_status`,`ud`.`religion` AS `religion`,`ua`.`identity_type` AS `identity_type`,`ua`.`identity_numbers` AS `identity_numbers`,`ua`.`province` AS `province`,`ua`.`city` AS `city`,`ua`.`citizen_address` AS `citizen_address`,`ua`.`residential_address` AS `residential_address`,`ue`.`departement_id` AS `departement_id`,`ue`.`job_position_id` AS `job_position_id`,`ue`.`job_level_id` AS `job_level_id`,`ue`.`approval_line_id` AS `approval_line_id`,`ue`.`approval_manager_id` AS `approval_manager_id`,`ue`.`join_date` AS `join_date`,`ue`.`sign_date` AS `sign_date`,`ue`.`resign_date` AS `resign_date`,`d`.`name` AS `departement`,`jp`.`name` AS `position`,`jl`.`name` AS `level` from ((((((((`users` `u` join `user_salaries` `us` on((`u`.`id` = `us`.`user_id`))) join `user_details` `ud` on((`u`.`id` = `ud`.`user_id`))) join `user_address` `ua` on((`u`.`id` = `ua`.`user_id`))) join `user_employes` `ue` on((`u`.`id` = `ue`.`user_id`))) join `departements` `d` on((`ue`.`departement_id` = `d`.`id`))) join `job_positions` `jp` on((`ue`.`job_position_id` = `jp`.`id`))) join `job_levels` `jl` on((`ue`.`job_level_id` = `jl`.`id`))) join `companies` `c` on((`u`.`company_id` = `c`.`id`)))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `users_view`");
    }
};
