<?php

namespace App\Jobs;

use App\HrisModule\Models\UserTimeworkSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class InsertUpdateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Data yang akan diproses.
     *
     * @var array
     */
    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Atur memory_limit khusus di job ini agar tidak cepat OOM
        ini_set('memory_limit', '512M');

        // Gunakan chunk agar tidak berat
        collect($this->data)
            ->chunk(300)
            ->each(function ($chunk) {
                foreach ($chunk as $k) {
                    try {
                        UserTimeworkSchedule::updateOrCreate(
                            [
                                'user_id'  => $k['user_id'],
                                'work_day' => $k['work_day'],
                            ],
                            [
                                'time_work_id' => $k['time_work_id'],
                            ]
                        );
                    } catch (Throwable $e) {
                        Log::error('InsertUpdateScheduleJob error', [
                            'user_id'   => $k['user_id'] ?? null,
                            'work_day'  => $k['work_day'] ?? null,
                            'message'   => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    /**
     * Menangani job yang gagal permanen (setelah retry habis).
     */
    public function failed(Throwable $exception): void
    {
        Log::error('InsertUpdateScheduleJob failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
