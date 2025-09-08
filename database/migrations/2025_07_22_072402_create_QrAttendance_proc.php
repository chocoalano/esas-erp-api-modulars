<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        DROP PROCEDURE IF EXISTS `QrAttendance`;
        CREATE PROCEDURE `QrAttendance`(
            IN `p_user_id` INT,
            IN `p_type` VARCHAR(10),
            IN `p_token` VARCHAR(255)
        )
        BEGIN
            -- =============================
            -- Timezone handling (WIB)
            -- =============================
            DECLARE v_old_tz VARCHAR(64) DEFAULT @@session.time_zone;
            DECLARE v_current_time DATETIME;  -- now di WIB
            DECLARE v_current_date DATE;      -- today di WIB

            -- =============================
            -- Variabel kerja
            -- =============================
            DECLARE v_qr_id INT;
            DECLARE v_departement_id INT;
            DECLARE v_departement_name VARCHAR(255);
            DECLARE v_time TIME;
            DECLARE v_expires_at DATETIME;
            DECLARE v_schedule_id INT;
            DECLARE v_has_check_in BOOLEAN;
            DECLARE v_existing_attendance_id INT;
            DECLARE v_lat DOUBLE;
            DECLARE v_long DOUBLE;
            DECLARE v_status VARCHAR(10);
            DECLARE v_status_in_out VARCHAR(10);
            DECLARE v_error_message VARCHAR(255);
            DECLARE v_company_id INT;
            DECLARE v_transaction INT;

            -- Error handler
            DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
            BEGIN
                ROLLBACK;
                SET time_zone = v_old_tz;
                IF v_error_message IS NULL OR v_error_message = '' THEN
                    SET v_error_message = 'Terjadi kesalahan tidak terduga dalam transaksi';
                END IF;
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END;

            -- Lock TZ ke Asia/Jakarta
            SET time_zone = 'Asia/Jakarta';
            SET v_current_time = NOW();          -- WIB
            SET v_current_date = CURRENT_DATE(); -- WIB

            -- Validasi dasar
            IF p_type NOT IN ('in', 'out') THEN
                SET v_error_message = 'Tipe absen tidak valid!';
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END IF;

            IF p_user_id IS NULL OR p_token IS NULL OR p_token = '' THEN
                SET v_error_message = 'Parameter tidak lengkap';
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END IF;

            -- Ambil data QR via token
            SELECT qp.id, qp.departement_id, d.name, tw.`in`, qp.expires_at, qpt.id, tw.company_id
            INTO v_qr_id, v_departement_id, v_departement_name, v_time, v_expires_at, v_transaction, v_company_id
            FROM qr_presences qp
            JOIN departements d     ON qp.departement_id = d.id
            JOIN time_workes  tw    ON qp.timework_id = tw.id
        LEFT JOIN qr_presence_transactions qpt ON qp.id = qpt.qr_presence_id
            WHERE qp.token = p_token COLLATE utf8mb4_unicode_ci
            LIMIT 1;

            IF v_qr_id IS NULL THEN
                SET v_error_message = 'Token tidak ditemukan!';
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END IF;

            IF v_transaction IS NOT NULL THEN
                SET v_error_message = 'Kode QR sudah digunakan!';
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END IF;

            IF v_current_time > v_expires_at THEN
                SET v_error_message = 'Kode QR sudah kadaluarsa!';
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END IF;

            -- User harus terdaftar di departemen QR
            IF NOT EXISTS (
                SELECT 1
                FROM users u
                JOIN user_employes ue ON u.id = ue.user_id
                WHERE u.id = p_user_id
                AND ue.departement_id = v_departement_id
            ) THEN
                SET v_error_message = 'User tidak terdaftar di departemen ini.';
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
            END IF;

            -- Jadwal hari ini (WIB)
            SELECT id
            INTO v_schedule_id
            FROM user_timework_schedules
            WHERE user_id = p_user_id
            AND work_day = v_current_date
            LIMIT 1;

            -- **VALIDASI BARU**: Larang absen masuk dua kali di hari yang sama (WIB)
            IF p_type = 'in' THEN
                IF EXISTS (
                    SELECT 1
                    FROM user_attendances
                    WHERE user_id = p_user_id
                    AND created_at >= v_current_date
                    AND created_at <  v_current_date + INTERVAL 1 DAY
                    AND time_in IS NOT NULL
                ) THEN
                    SET v_error_message = 'Anda sudah melakukan absensi masuk hari ini.';
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
                END IF;
            END IF;

            -- Validasi absen keluar: harus sudah check-in
            IF p_type = 'out' THEN
                SELECT EXISTS (
                    SELECT 1
                    FROM user_attendances
                    WHERE user_id = p_user_id
                    AND created_at >= v_current_date
                    AND created_at <  v_current_date + INTERVAL 1 DAY
                    AND time_in IS NOT NULL
                ) INTO v_has_check_in;

                IF v_has_check_in = FALSE THEN
                    SET v_error_message = 'Anda harus melakukan absensi masuk sebelum absensi pulang!';
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
                END IF;
            END IF;

            -- Status (bandingkan TIME lokal)
            SET v_status = IF(TIME(v_current_time) <= v_time, 'normal', 'late');
            SET v_status_in_out = CASE WHEN p_type = 'in' THEN v_status ELSE 'normal' END;

            -- Koordinat perusahaan
            SELECT latitude, longitude
            INTO v_lat, v_long
            FROM companies
            WHERE id = v_company_id
            LIMIT 1;

            -- Cari record absensi hari ini & kunci
            SELECT id
            INTO v_existing_attendance_id
            FROM user_attendances
            WHERE user_id = p_user_id
            AND created_at >= v_current_date
            AND created_at <  v_current_date + INTERVAL 1 DAY
            LIMIT 1
            FOR UPDATE;

            START TRANSACTION;

            IF v_existing_attendance_id IS NOT NULL THEN
                UPDATE user_attendances
                SET updated_at = v_current_time,
                    time_in   = IF(p_type = 'in',  v_current_time, time_in),
                    status_in = IF(p_type = 'in',  v_status_in_out, status_in),
                    lat_in    = IF(p_type = 'in',  v_lat, lat_in),
                    long_in   = IF(p_type = 'in',  v_long, long_in),
                    time_out  = IF(p_type = 'out', v_current_time, time_out),
                    status_out= IF(p_type = 'out', v_status_in_out, COALESCE(status_out, 'normal')),
                    lat_out   = IF(p_type = 'out', v_lat, lat_out),
                    long_out  = IF(p_type = 'out', v_long, long_out)
                WHERE id = v_existing_attendance_id;
            ELSE
                INSERT INTO user_attendances (
                    user_id, user_timework_schedule_id, created_at, updated_at,
                    time_in, status_in, lat_in, long_in,
                    time_out, status_out, lat_out, long_out
                ) VALUES (
                    p_user_id, v_schedule_id, v_current_time, v_current_time,
                    IF(p_type = 'in',  v_current_time, NULL), IF(p_type = 'in',  v_status_in_out, 'normal'), IF(p_type = 'in',  v_lat, NULL), IF(p_type = 'in',  v_long, NULL),
                    IF(p_type = 'out', v_current_time, NULL), IF(p_type = 'out', v_status_in_out, 'normal'), IF(p_type = 'out', v_lat, NULL), IF(p_type = 'out', v_long, NULL)
                );

                SET v_existing_attendance_id = LAST_INSERT_ID();
            END IF;

            INSERT INTO qr_presence_transactions (qr_presence_id, user_attendance_id, token, created_at, updated_at)
            VALUES (v_qr_id, v_existing_attendance_id, p_token, v_current_time, v_current_time);

            COMMIT;

            SET time_zone = v_old_tz;

            SELECT 'success' AS message, CONCAT('Absensi ', p_type, ' berhasil disimpan.') AS result;
        END;
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS QrAttendance");
    }
};
