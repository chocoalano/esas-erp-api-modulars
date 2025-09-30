<?php

namespace App\Jobs;

use App\HrisModule\Models\UserAttendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PenyesuaianAbsensiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Pengaturan Queue (Baik)
    public int $timeout = 120;
    public int $tries   = 3;
    public $backoff     = [5, 30, 120];

    /**
     * Data yang akan diproses (array of associative array)
     * Data harus sudah berisi semua kolom yang dibutuhkan UserAttendance.
     */
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        // Tetapkan limit memori hanya jika diperlukan, tetapi
        // pastikan PHP CLI memiliki memori yang cukup.
        @ini_set('memory_limit', '512M');

        $attendanceDataCollection = collect($this->data);

        // Kolom yang pasti akan diupdate jika data sudah ada.
        // Kolom 'date_presence' akan dihitung di bawah.
        $updateColumns = [
            'user_timework_schedule_id', 'time_in', 'time_out',
            'type_in', 'type_out', 'lat_in', 'lat_out', 'long_in', 'long_out',
            'image_in', 'image_out', 'status_in', 'status_out',
            'created_by', 'updated_by', 'created_at', 'updated_at'
        ];

        // Memecah data menjadi chunk kecil
        $attendanceDataCollection
            ->chunk(50)
            ->each(function (Collection $chunk) use ($updateColumns) {

                $dataToUpsert = $chunk->map(function ($row) {
                    // 1. Parsing tanggal dan waktu (Diperlukan untuk date_presence)
                    $createdAtCarbon = Carbon::parse($row['created_at']);
                    $datePresence = $createdAtCarbon->format('Y-m-d');

                    // 2. Buat array data final yang akan di-upsert
                    return [
                        'user_id' => $row['user_id'],
                        'user_timework_schedule_id' => $row['user_timework_schedule_id'] ?? null,
                        'time_in' => $row['time_in'],
                        'time_out' => $row['time_out'],
                        'type_in' => $row['type_in'],
                        'type_out' => $row['type_out'],
                        'lat_in' => $row['lat_in'],
                        'lat_out' => $row['lat_out'],
                        'long_in' => $row['long_in'],
                        'long_out' => $row['long_out'],
                        'image_in' => $row['image_in'] ?? null,
                        'image_out' => $row['image_out'] ?? null,
                        'status_in' => $row['status_in'],
                        'status_out' => $row['status_out'],
                        'created_by' => $row['created_by'],
                        'updated_by' => $row['updated_by'],
                        'date_presence' => $datePresence, // 👈 Kolom Kunci Unik
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ];
                })->toArray(); // Konversi kembali ke array untuk upsert

                // 3. Jalankan operasi UPSERT tunggal untuk chunk ini
                // Kunci unik: ['user_id', 'date_presence']
                UserAttendance::upsert(
                    $dataToUpsert,
                    ['user_id', 'date_presence'],
                    $updateColumns
                );
            });
    }

    /**
     * Menangani pekerjaan yang gagal.
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // Opsional: Kirim notifikasi, log, atau lakukan penanganan kegagalan khusus
        // Contoh: Log::error("Absensi Job Gagal: " . $exception->getMessage());
    }
}
