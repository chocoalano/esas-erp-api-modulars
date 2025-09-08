<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DROP PROCEDURE IF EXISTS `UpdateAttendanceIn`;
            CREATE PROCEDURE `UpdateAttendanceIn`(
                IN `p_user_id` INT,
                IN `p_time_id` INT,
                IN `p_lat` DECIMAL(10,8),
                IN `p_long` DECIMAL(11,8),
                IN `p_image` VARCHAR(255),
                IN `p_time` TIME
            )
            BEGIN
                DECLARE v_attendance_id INT DEFAULT NULL;
                DECLARE v_schedule_id   INT DEFAULT NULL;
                DECLARE v_in_time       TIME;
                DECLARE v_status        VARCHAR(10);
                DECLARE v_now           DATETIME;
                DECLARE v_today         DATE;
                DECLARE v_old_tz        VARCHAR(64) DEFAULT @@session.time_zone;
                DECLARE exit_code       INT DEFAULT 0;

                -- Handler error
                DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SET time_zone = v_old_tz;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Terjadi kesalahan dalam transaksi';
                END;

                -- Lock timezone ke Asia/Jakarta
                SET time_zone = 'Asia/Jakarta';
                SET v_now   = NOW();          -- WIB
                SET v_today = CURRENT_DATE(); -- WIB

                START TRANSACTION;

                -- Validasi parameter
                IF p_user_id IS NULL OR p_time_id IS NULL OR p_lat IS NULL
                OR p_long IS NULL OR p_image IS NULL OR p_time IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Required fields are missing';
                END IF;

                -- Cari attendance hari ini
                SELECT id INTO v_attendance_id
                FROM user_attendances
                WHERE user_id = p_user_id
                AND created_at >= v_today
                AND created_at <  v_today + INTERVAL 1 DAY
                LIMIT 1;

                -- Cari jadwal kerja hari ini
                SELECT id INTO v_schedule_id
                FROM user_timework_schedules
                WHERE user_id = p_user_id
                AND work_day = v_today
                AND time_work_id = p_time_id
                LIMIT 1;

                -- Ambil jam masuk dari master time_work
                SELECT `in` INTO v_in_time
                FROM time_workes
                WHERE id = p_time_id
                LIMIT 1;

                IF v_in_time IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid time_id: Time In not found';
                END IF;

                -- Tentukan status (late / normal)
                SET v_status = IF(p_time > v_in_time, 'late', 'normal');

                -- Jika belum ada attendance hari ini → insert baru
                IF v_attendance_id IS NULL THEN
                    INSERT INTO user_attendances (
                        user_id,
                        user_timework_schedule_id,
                        time_in,
                        lat_in,
                        long_in,
                        image_in,
                        status_in,
                        type_in,
                        created_by,
                        created_at
                    ) VALUES (
                        p_user_id,
                        v_schedule_id,
                        p_time,
                        p_lat,
                        p_long,
                        p_image,
                        v_status,
                        'face-device',
                        p_user_id,
                        v_now
                    );

                    UPDATE users SET is_attendance = 1 WHERE id = p_user_id;
                ELSE
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Attendance already exists for today';
                END IF;

                COMMIT;

                -- Kembalikan timezone lama
                SET time_zone = v_old_tz;

                SET exit_code = 1;
                SELECT exit_code AS success;
            END;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS `UpdateAttendanceIn`");
    }
};
