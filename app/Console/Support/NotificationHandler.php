<?php

namespace App\Console\Support;

use App\Console\Support\FcmHandler;
use App\GeneralModule\Models\Notification as NotificationModel;
use App\GeneralModule\Models\User;
use App\Mail\SendEmail;                 // ⬅️ pakai Mailable kamu
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;     // ⬅️ Mail facade

class NotificationHandler
{
    /**
     * Kirim notifikasi ke DB, FCM, dan Email.
     *
     * @param string $title
     * @param string $body
     * @param int|null $recipientId
     * @param string $recipientType
     * @return bool True jika minimal satu kanal terkirim
     */
    public static function sendNotification(string $title, string $body, ?int $recipientId = null, string $recipientType = User::class): bool
    {
        try {
            $sender = Auth::user();
            if (!$sender) {
                Log::warning("Sender user is not authenticated in NotificationService.");
                return false;
            }

            /** @var \Illuminate\Database\Eloquent\Model|User|null $recipient */
            $recipient = $recipientType::find($recipientId);
            if (!$recipient) {
                Log::warning("Recipient user not found: {$recipientId}");
                return false;
            }

            // Simpan notifikasi ke database (tetap disimpan walau pengiriman gagal)
            NotificationModel::create([
                'type'            => 'message',
                'notifiable_type' => $recipientType,
                'notifiable_id'   => $recipientId,
                'data' => [
                    'title'       => $title,
                    'body'        => $body,
                    'sender_id'   => $sender->id,
                    'sender_name' => $sender->name,
                ],
            ]);

            // --- Kirim FCM (opsional)
            $sentFcm = false;
            try {
                if (!empty($recipient->fcm_token)) {
                    $fcm = new FcmHandler();
                    $sentFcm = (bool) $fcm->send($recipient->fcm_token, $title, $body, [
                        'sender_id'   => $sender->id,
                        'sender_name' => $sender->name,
                    ]);

                    if (!$sentFcm) {
                        Log::error("Notification saved but failed to send FCM to user ID: {$recipientId}");
                    }
                } else {
                    Log::info("Recipient has no FCM token: {$recipientId}");
                }
            } catch (\Throwable $e) {
                Log::error("FCM send error for user {$recipientId}: " . $e->getMessage());
            }

            // --- Kirim Email (opsional)
            $sentMail = false;
            try {
                $email = $recipient->email ?? null;
                if (!empty($email)) {
                    // Subject default di Mailable adalah "Send Email"; override pakai judul notifikasi
                    Mail::to($email)->send(
                        (new SendEmail([
                            'name'=>$recipient->name,
                            'message'=>$body,
                        ]))->subject($title)
                    );
                    $sentMail = true;
                } else {
                    Log::info("Recipient has no email address: {$recipientId}");
                }
            } catch (\Throwable $e) {
                Log::error("Email send error for user {$recipientId}: " . $e->getMessage());
            }

            if ($sentFcm || $sentMail) {
                Log::info("Notification delivered (fcm={$sentFcm}, mail={$sentMail}) for user ID: {$recipientId}");
                return true;
            }

            Log::warning("Notification saved but no delivery channel succeeded for user ID: {$recipientId}");
            return false;

        } catch (\Throwable $e) {
            Log::error("Error sending notification: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }
}
