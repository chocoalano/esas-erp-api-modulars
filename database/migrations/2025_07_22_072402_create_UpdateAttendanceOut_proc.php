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
        DB::unprepared("CREATE PROCEDURE `UpdateAttendanceOut`(IN `p_user_id` INT, IN `p_time_id` INT, IN `p_lat` DECIMAL(10,8), IN `p_long` DECIMAL(11,8), IN `p_image` VARCHAR(255), IN `p_time` TIME)
BEGIN
                DECLARE v_attendance_id INT DEFAULT NULL;
                DECLARE v_image_out VARCHAR(255);
                DECLARE v_out_time TIME;
                DECLARE v_status VARCHAR(10);
                DECLARE exit_code INT DEFAULT 0; -- Variabel untuk menandakan status eksekusi
                -- Start a transaction
                START TRANSACTION;
                -- Validate required fields
                IF p_user_id IS NULL OR p_time_id IS NULL OR p_lat IS NULL OR p_long IS NULL OR p_image IS NULL OR p_time IS NULL THEN
                    SET exit_code = 0; -- Set exit code to false
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing required input parameters';
                END IF;
                -- Retrieve attendance record for the user (check if attendance exists)
                SELECT id, image_out INTO v_attendance_id, v_image_out
                FROM user_attendances
                WHERE user_id = p_user_id
                AND time_in IS NOT NULL
                AND DATE(created_at) = CURDATE()
                LIMIT 1;
                -- Check if the attendance record exists
                IF v_attendance_id IS NULL THEN
                    SET exit_code = 0; -- Set exit code to false
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Attendance not found for the given user and date';
                END IF;
                -- Retrieve the scheduled 'out' time from time_work
                SELECT `out` INTO v_out_time
                FROM time_workes
                WHERE id = p_time_id
                LIMIT 1;
                -- Ensure 'out' time is valid
                IF v_out_time IS NULL THEN
                    SET exit_code = 0; -- Set exit code to false
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Out time not found for the specified time ID';
                END IF;
                -- Determine the status based on the comparison of 'out' time and provided 'p_time'
                IF p_time < v_out_time THEN
                    SET v_status = 'normal';
                ELSE
                    SET v_status = 'unlate';
                END IF;
                -- Update attendance record with time out and status
                UPDATE user_attendances
                SET
                    time_out = p_time,
                    lat_out = p_lat,
                    long_out = p_long,
                    image_out = p_image,
                    status_out = v_status,
                    type_out = 'face-device',
                    updated_by = p_user_id,
                    updated_at = CURRENT_TIMESTAMP()
                WHERE id = v_attendance_id;
                UPDATE users SET is_attendance = 0 WHERE id=p_user_id;
                -- Commit the transaction
                COMMIT;
                -- Set exit code to true
                SET exit_code = 1; -- Set exit code to true
                -- Return the exit code
                SELECT exit_code AS success;
            END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS UpdateAttendanceOut");
    }
};
