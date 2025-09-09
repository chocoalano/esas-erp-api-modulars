<?php

namespace App\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InsertUpdateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Atur sesuai kebutuhan
    public int $timeout = 120;          // detik
    public int $tries   = 3;
    public $backoff     = [5, 30, 120]; // retry jeda 5s, 30s, 120s

    /**
     * Data yang akan diproses (array of associative array)
     * Contoh item: ['user_id'=>53, 'work_day'=>'2025-09-09', 'time_work_id'=>7]
     */
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        // Hindari OOM
        // dd($this->data);
        @ini_set('memory_limit', '512M');

        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = now();

        collect($this->data)
            ->chunk(10) // boleh 300–1000; sesuaikan
            ->each(function ($chunk) use ($tz, $now) {

                foreach ($chunk as $row) {
                    try {
                        // Validasi minimal & normalisasi
                        foreach (['user_id','work_day','time_work_id'] as $key) {
                            if (!Arr::has($row, $key)) {
                                throw new \InvalidArgumentException("Missing key: {$key}");
                            }
                        }

                        $userId     = (int) $row['user_id'];
                        $workDay    = CarbonImmutable::parse($row['work_day'])->timezone($tz)->toDateString(); // Y-m-d
                        $timeWorkId = (int) $row['time_work_id'];

                        // Panggil SP. Pakai SELECT agar result set (jika ada) dikonsumsi.
                        // Jika SP TIDAK mengembalikan SELECT apapun, DB::statement juga boleh.
                        DB::select(
                            'CALL UpsertUserTimeworkSchedule(?, ?, ?)',
                            [$userId, $workDay, $timeWorkId]
                        );

                        // Jika SP kamu MENGEMBALIKAN lebih dari 1 result set,
                        // pertimbangkan untuk menghapus SELECT di dalam SP untuk job ini
                        // atau konsumsi semua result set via PDO (kompleks). Paling aman: hapus SELECT.

                    } catch (Throwable $e) {
                        Log::error('InsertUpdateScheduleJob error', [
                            'row'     => $row,
                            'message' => $e->getMessage(),
                        ]);
                        // biarkan loop lanjut ke item berikutnya
                    }
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('InsertUpdateScheduleJob failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
