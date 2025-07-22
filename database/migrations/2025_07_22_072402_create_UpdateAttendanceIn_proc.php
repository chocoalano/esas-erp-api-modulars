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
        DB::unprepared("CREATE PROCEDURE `UpdateAttendanceIn`(IN `p_user_id` INT, IN `p_time_id` INT, IN `p_lat` DECIMAL(10,8), IN `p_long` DECIMAL(11,8), IN `p_image` VARCHAR(255), IN `p_time` TIME)
BEGIN
    DECLARE v_attendance_id INT DEFAULT NULL;
    DECLARE v_schedule_id INT DEFAULT NULL;
    DECLARE v_in_time TIME;
    DECLARE v_status VARCHAR(10);
    DECLARE exit_code INT DEFAULT 0;

    -- Menangani error agar transaksi rollback jika terjadi kesalahan
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Terjadi kesalahan dalam transaksi';
    END;

    -- Start a transaction
    START TRANSACTION;

    -- Cek apakah semua parameter yang dibutuhkan ada
    IF p_user_id IS NULL OR p_time_id IS NULL OR p_lat IS NULL
        OR p_long IS NULL OR p_image IS NULL OR p_time IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Required fields are missing';
    END IF;

    -- Mendapatkan attendance berdasarkan user dan tanggal sekarang
    SELECT id INTO v_attendance_id
    FROM user_attendances
    WHERE user_id = p_user_id
    AND DATE(created_at) = CURDATE()
    LIMIT 1;

    -- Dapatkan jadwal kerja sesuai waktu dan hari
    SELECT id INTO v_schedule_id
    FROM user_timework_schedules
    WHERE user_id = p_user_id
    AND work_day = CURDATE()
    AND time_work_id = p_time_id
    LIMIT 1;

    -- Dapatkan waktu masuk dari time_work
    SELECT `in` INTO v_in_time
    FROM time_workes
    WHERE id = p_time_id
    LIMIT 1;

    -- Jika waktu masuk tidak ditemukan, beri pesan error
    IF v_in_time IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid time_id: Time In not found';
    END IF;

    -- Cek status (late atau normal)
    SET v_status = IF(p_time > v_in_time, 'late', 'normal');

    -- Jika attendance tidak ditemukan, buat baru
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
            'p_user_id',
            CURRENT_TIMESTAMP()
        );
    ELSE
        -- Perbarui data attendance yang ada
        UPDATE user_attendances
        SET
            user_timework_schedule_id = v_schedule_id,
            time_in = p_time,
            lat_in = p_lat,
            long_in = p_long,
            image_in = p_image,
            status_in = v_status,
            type_in = 'face-device',
            updated_by = p_user_id,
            updated_at = CURRENT_TIMESTAMP()
        WHERE id = v_attendance_id;
    END IF;

    -- Commit the transaction jika tidak ada error
    COMMIT;

    -- Set exit code to true (sukses)
    SET exit_code = 1;
    SELECT exit_code AS success;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS UpdateAttendanceIn");
    }
};
