<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DROP PROCEDURE IF EXISTS `UpdateAttendanceOut`;
            CREATE PROCEDURE `UpdateAttendanceOut`(
                IN `p_user_id` INT,
                IN `p_time_id` INT,
                IN `p_lat` DECIMAL(10,8),
                IN `p_long` DECIMAL(11,8),
                IN `p_image` VARCHAR(255),
                IN `p_time` TIME
            )
            BEGIN
                DECLARE v_attendance_id INT DEFAULT NULL;
                DECLARE v_image_out     VARCHAR(255);
                DECLARE v_out_time      TIME;
                DECLARE v_status        VARCHAR(10);
                DECLARE v_now           DATETIME;
                DECLARE v_today         DATE;
                DECLARE v_old_tz        VARCHAR(64) DEFAULT @@session.time_zone;
                DECLARE exit_code       INT DEFAULT 0;

                -- Error handler: rollback & kembalikan TZ
                DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SET time_zone = v_old_tz;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Terjadi kesalahan dalam transaksi';
                END;

                -- Kunci session timezone ke Asia/Jakarta
                SET time_zone = 'Asia/Jakarta';
                SET v_now   = NOW();          -- WIB
                SET v_today = CURRENT_DATE(); -- WIB

                START TRANSACTION;

                -- Validasi parameter
                IF p_user_id IS NULL OR p_time_id IS NULL OR p_lat IS NULL
                OR p_long IS NULL OR p_image IS NULL OR p_time IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing required input parameters';
                END IF;

                -- Ambil record attendance hari ini (WIB) yang sudah punya time_in
                SELECT id, image_out
                INTO v_attendance_id, v_image_out
                FROM user_attendances
                WHERE user_id = p_user_id
                AND created_at >= v_today
                AND created_at <  v_today + INTERVAL 1 DAY
                AND time_in IS NOT NULL
                LIMIT 1
                FOR UPDATE;

                IF v_attendance_id IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Attendance not found for the given user and date';
                END IF;

                -- (Opsional) Larang absen keluar dua kali
                IF EXISTS (
                    SELECT 1 FROM user_attendances
                    WHERE id = v_attendance_id AND time_out IS NOT NULL
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Attendance out already recorded for today';
                END IF;

                -- Ambil jam out dari master time_work
                SELECT `out`
                INTO v_out_time
                FROM time_workes
                WHERE id = p_time_id
                LIMIT 1;

                IF v_out_time IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Out time not found for the specified time ID';
                END IF;

                -- Tentukan status (ikuti logika semula)
                -- Jika p_time < v_out_time → 'normal', else 'unlate'
                SET v_status = IF(p_time < v_out_time, 'normal', 'unlate');

                -- Update attendance (WIB timestamps)
                UPDATE user_attendances
                SET time_out  = p_time,
                    lat_out   = p_lat,
                    long_out  = p_long,
                    image_out = p_image,
                    status_out= v_status,
                    type_out  = 'face-device',
                    updated_by= p_user_id,
                    updated_at= v_now
                WHERE id = v_attendance_id;

                -- Reset flag user
                UPDATE users SET is_attendance = 0 WHERE id = p_user_id;

                COMMIT;

                -- Kembalikan timezone session
                SET time_zone = v_old_tz;

                SET exit_code = 1;
                SELECT exit_code AS success;
            END;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS `UpdateAttendanceOut`");
    }
};
