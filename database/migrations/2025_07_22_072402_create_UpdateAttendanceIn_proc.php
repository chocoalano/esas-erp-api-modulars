<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop dulu kalau sudah ada
        DB::unprepared("DROP PROCEDURE IF EXISTS `sp_attendance_in`;");

        // Buat prosedur (pakai NOWDOC biar aman dari interpolasi)
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE `sp_attendance_in`(
  IN p_user_id  INT,
  IN p_lat      DECIMAL(10,7),
  IN p_long     DECIMAL(10,7),
  IN p_image    TEXT,
  IN p_time     DATETIME,
  IN p_time_id  INT
)
BEGIN
  DECLARE v_attendance_id     INT DEFAULT NULL;
  DECLARE v_schedule_id       INT DEFAULT NULL;
  DECLARE v_selected_tw_id    INT DEFAULT NULL;
  DECLARE v_in_time           TIME;
  DECLARE v_out_time          TIME;
  DECLARE v_status            VARCHAR(10);
  DECLARE v_now               DATETIME;
  DECLARE v_today             DATE;
  DECLARE v_old_tz            VARCHAR(64) DEFAULT @@session.time_zone;
  DECLARE v_err               VARCHAR(255) DEFAULT NULL;
  DECLARE v_company_id        INT DEFAULT NULL;
  DECLARE v_departement_id    INT DEFAULT NULL;
  DECLARE v_not_found         TINYINT(1) DEFAULT 0;
  DECLARE v_existing_time_in  TIME DEFAULT NULL;
  DECLARE v_existing_sched_id INT DEFAULT NULL;
  DECLARE v_existing_tw_id    INT DEFAULT NULL;
  DECLARE v_phase             VARCHAR(100) DEFAULT 'init';

  /* Handler NOT FOUND untuk SELECT ... INTO kosong */
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_not_found = 1;

  /* Handler umum: rollback + pesan spesifik (pakai v_phase sebagai fallback) */
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    DECLARE v_mysql_msg TEXT DEFAULT NULL;
    DECLARE v_sqlstate  CHAR(5) DEFAULT NULL;
    DECLARE v_final_msg VARCHAR(500);
    ROLLBACK;
    SET time_zone = v_old_tz;
    IF v_err IS NULL OR v_err = '' THEN
      GET DIAGNOSTICS CONDITION 1 v_mysql_msg = MESSAGE_TEXT, v_sqlstate = RETURNED_SQLSTATE;
      IF v_mysql_msg IS NULL OR v_mysql_msg = '' THEN
        SET v_final_msg = CONCAT('Kesalahan tak terduga pada tahap: ', v_phase);
      ELSE
        SET v_final_msg = CONCAT('[', IFNULL(v_sqlstate, ''), '] ', v_mysql_msg);
      END IF;
    ELSE
      SET v_final_msg = v_err;
    END IF;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_final_msg;
  END;

  /* ===== Validasi parameter ===== */
  SET v_phase = 'validasi parameter';
  IF p_user_id IS NULL OR p_user_id <= 0
     OR p_lat IS NULL OR p_long IS NULL
     OR p_image IS NULL OR TRIM(p_image) = ''
     OR p_time IS NULL THEN
    SET v_err = 'Field wajib tidak lengkap';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
  END IF;
  IF p_lat < -90 OR p_lat > 90 THEN
    SET v_err = 'Latitude di luar rentang (-90..90)';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
  END IF;
  IF p_long < -180 OR p_long > 180 THEN
    SET v_err = 'Longitude di luar rentang (-180..180)';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
  END IF;

  /* ===== Waktu referensi ===== */
  SET v_phase = 'set zona waktu & waktu referensi';
  SET time_zone = '+07:00';
  SET v_now   = NOW();
  SET v_today = CURRENT_DATE();

  /* ===== Ambil konteks user ===== */
  SET v_phase = 'ambil company user';
  SET v_not_found = 0;
  SET v_company_id = NULL;
  SELECT u.company_id
    INTO v_company_id
  FROM `esas-app`.users u
  WHERE u.id = p_user_id
  LIMIT 1;
  IF v_not_found = 1 OR v_company_id IS NULL THEN
    SET v_err = 'User tidak ditemukan';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
  END IF;

  SET v_phase = 'ambil departemen user';
  SET v_not_found = 0;
  SET v_departement_id = NULL;
  SELECT ue.departement_id
    INTO v_departement_id
  FROM `esas-app`.user_employes ue
  WHERE ue.user_id = p_user_id
  ORDER BY ue.id DESC
  LIMIT 1;
  IF v_not_found = 1 OR v_departement_id IS NULL THEN
    SET v_err = 'Departemen user tidak ditemukan';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
  END IF;

  START TRANSACTION;

  /* ===== 1) Pilih shift (time_workes) ===== */
  IF p_time_id IS NOT NULL AND p_time_id > 0 THEN
    SET v_phase = CONCAT(
      'validasi time_work_id=', p_time_id,
      ' company_id=', v_company_id,
      ' departement_id=', v_departement_id
    );

    SET v_not_found = 0;
    SET v_selected_tw_id = NULL; SET v_in_time = NULL; SET v_out_time = NULL;
    SELECT tw.id, tw.`in`, tw.`out`
      INTO v_selected_tw_id, v_in_time, v_out_time
    FROM `esas-app`.time_workes tw
    WHERE tw.id            = p_time_id
      AND tw.company_id    = v_company_id
      AND tw.departemen_id = v_departement_id
      AND (
            (tw.`in` <= tw.`out` AND TIME(p_time) BETWEEN tw.`in` AND tw.`out`)
            OR (tw.`in` > tw.`out`  AND (TIME(p_time) >= tw.`in` OR TIME(p_time) <= tw.`out`))
            OR (MOD(TIME_TO_SEC(tw.`in`) - TIME_TO_SEC(TIME(p_time)) + 86400, 86400) BETWEEN 0 AND 7200)
          )
    ORDER BY
      CASE
        WHEN (tw.`in` <= tw.`out` AND TIME(p_time) BETWEEN tw.`in` AND tw.`out`)
          OR (tw.`in` > tw.`out` AND (TIME(p_time) >= tw.`in` OR TIME(p_time) <= tw.`out`))
        THEN 0 ELSE 1 END,
      MOD(TIME_TO_SEC(tw.`in`) - TIME_TO_SEC(TIME(p_time)) + 86400, 86400) ASC
    LIMIT 1;

    IF v_not_found = 1 OR v_selected_tw_id IS NULL THEN
      SET v_err = CONCAT('time_work_id ', p_time_id, ' tidak valid untuk company/departemen user atau tidak mencakup waktu presensi');
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
    END IF;
  END IF;

  IF v_selected_tw_id IS NULL THEN
    /* coba yang mencakup p_time (termasuk lintas malam in>out) */
    SET v_phase = 'autopick shift yang mencakup p_time';
    SET v_not_found = 0;
    SET v_selected_tw_id = NULL; SET v_in_time = NULL; SET v_out_time = NULL;
    SELECT tw.id, tw.`in`, tw.`out`
      INTO v_selected_tw_id, v_in_time, v_out_time
    FROM `esas-app`.time_workes tw
    WHERE tw.company_id    = v_company_id
      AND tw.departemen_id = v_departement_id
      AND (
            (tw.`in` <= tw.`out` AND TIME(p_time) BETWEEN tw.`in` AND tw.`out`)
            OR (tw.`in` > tw.`out`  AND (TIME(p_time) >= tw.`in` OR TIME(p_time) <= tw.`out`))
            OR (MOD(TIME_TO_SEC(tw.`in`) - TIME_TO_SEC(TIME(p_time)) + 86400, 86400) BETWEEN 0 AND 7200)
          )
    ORDER BY
      CASE
        WHEN (tw.`in` <= tw.`out` AND TIME(p_time) BETWEEN tw.`in` AND tw.`out`)
          OR (tw.`in` > tw.`out` AND (TIME(p_time) >= tw.`in` OR TIME(p_time) <= tw.`out`))
        THEN 0 ELSE 1 END,
      MOD(TIME_TO_SEC(tw.`in`) - TIME_TO_SEC(TIME(p_time)) + 86400, 86400) ASC
    LIMIT 1;

    IF v_not_found = 1 OR v_selected_tw_id IS NULL THEN
      /* ambil in terdekat (berdasarkan jarak detik antar TIME) */
      SET v_phase = 'autopick shift terdekat berdasarkan jam in';
      SET v_not_found = 0;
      SET v_selected_tw_id = NULL; SET v_in_time = NULL; SET v_out_time = NULL;
      SELECT tw.id, tw.`in`, tw.`out`
        INTO v_selected_tw_id, v_in_time, v_out_time
      FROM `esas-app`.time_workes tw
      WHERE tw.company_id    = v_company_id
        AND tw.departemen_id = v_departement_id
      ORDER BY ABS(TIME_TO_SEC(TIME(p_time)) - TIME_TO_SEC(tw.`in`)) ASC
      LIMIT 1;

      IF v_not_found = 1 OR v_selected_tw_id IS NULL THEN
        SET v_err = 'Shift kerja (time_workes) tidak ditemukan untuk company/departemen user';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
      END IF;
    END IF;
  END IF;

  /* ===== 2) Pastikan/siapkan schedule hari ini ===== */
  SET v_phase = 'cek/siapkan schedule hari ini';
  SET v_not_found = 0;
  SET v_existing_sched_id = NULL; SET v_existing_tw_id = NULL;
  SELECT uts.id, uts.time_work_id
    INTO v_existing_sched_id, v_existing_tw_id
  FROM `esas-app`.user_timework_schedules uts
  WHERE uts.user_id   = p_user_id
    AND uts.work_day  = v_today
  LIMIT 1
  FOR UPDATE;

  IF v_not_found = 1 OR v_existing_sched_id IS NULL THEN
    INSERT INTO `esas-app`.user_timework_schedules (user_id, work_day, time_work_id, created_at, updated_at)
    VALUES (p_user_id, v_today, v_selected_tw_id, v_now, v_now);
    SET v_schedule_id = LAST_INSERT_ID();
  ELSE
    SET v_schedule_id = v_existing_sched_id;
    IF v_existing_tw_id <> v_selected_tw_id THEN
      UPDATE `esas-app`.user_timework_schedules
         SET time_work_id = v_selected_tw_id,
             updated_at   = v_now
       WHERE id = v_existing_sched_id;
    END IF;
  END IF;

  /* ===== 3) Cek sudah absen masuk hari ini ===== */
  SET v_phase = 'cek record attendance hari ini';
  SET v_not_found = 0;
  SET v_attendance_id = NULL; SET v_existing_time_in = NULL;
  SELECT ua.id, ua.time_in
    INTO v_attendance_id, v_existing_time_in
  FROM `esas-app`.user_attendances ua
  WHERE ua.user_id       = p_user_id
    AND ua.date_presence = v_today
  ORDER BY ua.id DESC
  LIMIT 1
  FOR UPDATE;

  IF v_not_found = 0 AND v_existing_time_in IS NOT NULL THEN
    SET v_err = 'Sudah absen masuk untuk hari ini';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err;
  END IF;

  /* ===== 4) Simpan attendance ===== */
  SET v_phase = 'menentukan status_in & simpan attendance';
  SET v_status = CASE
                   WHEN TIME(p_time) <  v_in_time THEN 'early'
                   WHEN TIME(p_time) =  v_in_time THEN 'on-time'
                   ELSE 'late'
                 END;

  IF v_attendance_id IS NOT NULL AND v_not_found = 0 THEN
    UPDATE `esas-app`.user_attendances
       SET time_in                    = p_time,
           lat_in                     = p_lat,
           long_in                    = p_long,
           image_in                   = p_image,
           status_in                  = v_status,
           type_in                    = 'face-device',
           user_timework_schedule_id  = v_schedule_id,
           updated_at                 = v_now
     WHERE id = v_attendance_id;
  ELSE
    INSERT INTO `esas-app`.user_attendances (
      user_id,
      user_timework_schedule_id,
      time_in, lat_in, long_in, image_in,
      status_in, type_in,
      date_presence,
      created_by, created_at, updated_at
    ) VALUES (
      p_user_id,
      v_schedule_id,
      p_time, p_lat, p_long, p_image,
      v_status, 'face-device',
      v_today,
      p_user_id, v_now, v_now
    );
    SET v_attendance_id = LAST_INSERT_ID();
  END IF;

  /* ===== 5) Update flag user ===== */
  SET v_phase = 'update flag user.is_attendance';
  UPDATE `esas-app`.users
     SET is_attendance = 1
   WHERE id = p_user_id;

  COMMIT;
  SET time_zone = v_old_tz;

  SELECT
    1                AS success,
    'Absensi masuk tersimpan' AS message,
    v_attendance_id  AS user_attendance_id,
    v_schedule_id    AS user_timework_schedule_id,
    v_selected_tw_id AS time_work_id,
    v_in_time        AS shift_in,
    v_out_time       AS shift_out,
    v_status         AS status_in;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS `sp_attendance_in`;");
    }
};
