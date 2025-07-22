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
        DB::unprepared("CREATE PROCEDURE `StoreOrUpdateUser`(IN `p_nip` VARCHAR(50), IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(100), IN `p_company_id` INT, IN `p_idtype` VARCHAR(50), IN `p_citizen_id_address` TEXT, IN `p_residential_address` TEXT, IN `p_phone` VARCHAR(20), IN `p_placebirth` VARCHAR(100), IN `p_datebirth` DATE, IN `p_gender` CHAR(1), IN `p_blood` VARCHAR(5), IN `p_marital_status` VARCHAR(50), IN `p_religion` VARCHAR(50), IN `p_basic_salary` DECIMAL(20,2), IN `p_salary_type` VARCHAR(20), IN `p_departement` VARCHAR(100), IN `p_levels` VARCHAR(100), IN `p_position` VARCHAR(100), IN `p_approval_line` INT, IN `p_approval_manager` INT, IN `p_join_date` DATE, IN `p_sign_date` DATE, IN `p_bank_name` VARCHAR(100), IN `p_bank_account` VARCHAR(50), IN `p_bank_account_holder` VARCHAR(100))
BEGIN
                DECLARE v_user_id INT;
                DECLARE v_departement_id INT;
                DECLARE v_position_id INT;
                DECLARE v_level_id INT;

                -- Start Transaction
                START TRANSACTION;

                -- Insert or Update User
                INSERT INTO users (nip, company_id, name, email, email_verified_at, password, avatar, status)
                VALUES (
                    p_nip,
                    p_company_id,
                    p_name,
                    p_email,
                    NOW(),
                    p_password,
                    'admin.jpg',
                    'active'
                )
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    company_id = p_company_id,
                    name = p_name,
                    email = p_email,
                    email_verified_at = NOW(),
                    password = p_password,
                    avatar = 'admin.jpg',
                    status = 'active';

                SET v_user_id = LAST_INSERT_ID();

                -- Validate User ID
                IF v_user_id IS NULL THEN
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Failed to retrieve valid user_id.';
                END IF;

                -- Insert or Update Address
                INSERT INTO user_address (user_id, identity_type, identity_numbers, province, city, citizen_address, residential_address)
                VALUES (
                    v_user_id,
                    p_idtype,
                    DATE_FORMAT(NOW(), '%Y%m%d%H%i%s%f'),
                    'Banten',
                    'Tangerang',
                    p_citizen_id_address,
                    p_residential_address
                )
                ON DUPLICATE KEY UPDATE
                    identity_type = p_idtype,
                    citizen_address = p_citizen_id_address,
                    residential_address = p_residential_address;

                -- Insert or Update Details
                INSERT INTO user_details (user_id, phone, placebirth, datebirth, gender, blood, marital_status, religion)
                VALUES (
                    v_user_id,
                    p_phone,
                    p_placebirth,
                    p_datebirth,
                    p_gender,
                    p_blood,
                    p_marital_status,
                    p_religion
                )
                ON DUPLICATE KEY UPDATE
                    phone = p_phone,
                    placebirth = p_placebirth,
                    datebirth = p_datebirth,
                    gender = p_gender,
                    blood = p_blood,
                    marital_status = p_marital_status,
                    religion = p_religion;

                -- Insert or Update Salaries
                INSERT INTO user_salaries (user_id, basic_salary, payment_type)
                VALUES (
                    v_user_id,
                    p_basic_salary,
                    p_salary_type
                )
                ON DUPLICATE KEY UPDATE
                    basic_salary = p_basic_salary,
                    payment_type = p_salary_type;

                -- Find Department, Job Level, and Job Position
                SELECT
                    d.id AS departement_id,
                    jl.id AS level_id,
                    jp.id AS position_id
                INTO
                    v_departement_id,
                    v_level_id,
                    v_position_id
                FROM departements AS d
                JOIN job_levels AS jl ON d.id = jl.departement_id
                JOIN job_positions AS jp ON d.id = jp.departement_id
                WHERE d.name = p_departement COLLATE utf8mb4_unicode_ci
                AND jl.name = p_levels COLLATE utf8mb4_unicode_ci
                AND jp.name = p_position COLLATE utf8mb4_unicode_ci
                LIMIT 1;

                -- Validate Department Data
                IF v_departement_id IS NULL OR v_position_id IS NULL OR v_level_id IS NULL THEN
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid department, position, or level data.';
                END IF;

                -- Insert or Update Employee Details
                INSERT INTO user_employees (user_id, departement_id, job_position_id, job_level_id, approval_line_id, approval_manager_id, join_date, sign_date, resign_date, bank_name, bank_number, bank_holder)
                VALUES (
                    v_user_id,
                    v_departement_id,
                    v_position_id,
                    v_level_id,
                    p_approval_line,
                    p_approval_manager,
                    p_join_date,
                    p_sign_date,
                    NULL,
                    p_bank_name,
                    p_bank_account,
                    p_bank_account_holder
                )
                ON DUPLICATE KEY UPDATE
                    departement_id = v_departement_id,
                    job_position_id = v_position_id,
                    job_level_id = v_level_id,
                    approval_line_id = p_approval_line,
                    approval_manager_id = p_approval_manager,
                    join_date = p_join_date,
                    sign_date = p_sign_date,
                    bank_name = p_bank_name,
                    bank_number = p_bank_account,
                    bank_holder = p_bank_account_holder;

                -- Commit Transaction
                COMMIT;
            END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS StoreOrUpdateUser");
    }
};
