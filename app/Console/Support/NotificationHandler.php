<?php

namespace App\Console\Support;

use App\Console\Support\FcmHandler;
use App\GeneralModule\Models\Notification as NotificationModel;
use App\GeneralModule\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationHandler
{
    /**
     * Kirim notifikasi ke notifiable model dan push ke FCM.
     *
     * @param string $title Judul notifikasi
     * @param string $body Isi notifikasi
     * @param int|null $recipientId ID penerima
     * @param string $recipientType Tipe model notifiable (default: User::class)
     * @return bool
     */
    public static function sendNotification(string $title, string $body, ?int $recipientId = null, string $recipientType = User::class): bool
    {
        try {
            $sender = Auth::user();
            if (!$sender) {
                Log::warning("Sender user is not authenticated in NotificationService.");
                return false;
            }

            // Ambil penerima
            $recipient = $recipientType::find($recipientId);
            if (!$recipient) {
                Log::warning("Recipient user not found: {$recipientId}");
                return false;
            }

            // Cek apakah user punya token FCM
            if (empty($recipient->fcm_token)) {
                Log::warning("Recipient user does not have an FCM token: {$recipientId}");
                return false;
            }

            // Simpan notifikasi ke database
            NotificationModel::create([
                'type' => 'message',
                'notifiable_type' => $recipientType,
                'notifiable_id' => $recipientId,
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'sender_id' => $sender->id,
                    'sender_name' => $sender->name,
                ],
            ]);

            // Kirim notifikasi ke FCM
            $fcm = new FcmHandler();
            $sendFcm = $fcm->send($recipient->fcm_token, $title, $body, [
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
            ]);

            if ($sendFcm) {
                Log::info("Notification successfully sent and saved for user ID: {$recipientId}");
                return true;
            } else {
                Log::error("Notification saved but failed to send FCM to user ID: {$recipientId}");
                return false;
            }

        } catch (\Throwable $e) {
            Log::error("Error sending notification: " . $e->getMessage());
            return false;
        }
    }
}
