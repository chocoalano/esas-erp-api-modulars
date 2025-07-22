<?php

namespace App\Notifications;

use App\Console\Support\FcmHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class Approval extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected ?string $url;

    public function __construct(string $title, string $message, ?string $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        // Kirim FCM jika token tersedia
        if (!empty($notifiable->fcm_token)) {
            $this->sendFcmNotification($notifiable);
        }

        return ['database', 'mail'];
    }

    protected function sendFcmNotification(object $notifiable): void
    {
        try {
            $fcm = app(FcmHandler::class);
            $fcm->send(
                token: $notifiable->fcm_token,
                title: $this->title,
                body: $this->message,
                data: [
                    'url' => $this->url,
                    'model_type' => 'permit',
                ]
            );
        } catch (\Throwable $e) {
            \Log::error('FCM send failed: ' . $e->getMessage());
        }
    }

    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('Lihat Detail', $this->url ?? url('/'))
            ->line('Terima kasih telah menggunakan aplikasi kami.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'created_by' => auth()->check() ? auth()->user()->name : null,
        ];
    }
}
