<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE PROCEDURE InsertQrPresence(
                IN p_type           VARCHAR(10),
                IN p_departement_id INT,
                IN p_timework_id    INT,
                IN p_token          VARCHAR(255)
            )
            BEGIN
                DECLARE v_now_utc DATETIME;
                DECLARE v_now_wib DATETIME;
                DECLARE v_exp_wib DATETIME;

                -- Handler: rollback & lempar pesan asli
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                -- Ambil UTC, konversi ke WIB (+07:00) tanpa tergantung table time zone
                SET v_now_utc = UTC_TIMESTAMP();
                SET v_now_wib = CONVERT_TZ(v_now_utc, '+00:00', '+07:00');
                SET v_exp_wib = DATE_ADD(v_now_wib, INTERVAL 10 SECOND);

                INSERT INTO `qr_presences`(
                    `type`, departement_id, timework_id, token,
                    for_presence, expires_at, created_at, updated_at
                ) VALUES (
                    p_type, p_departement_id, p_timework_id, p_token,
                    v_now_wib, v_exp_wib, v_now_wib, v_now_wib
                );

                COMMIT;
            END;
        ");
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS `InsertQrPresence`');
    }
};
