<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikacija e-mail adrese – Digital Kotor')
            ->view('emails.verify-email', [
                'url' => $verificationUrl,
            ]);
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        try {
            $expires = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60));

            return URL::temporarySignedRoute(
                'verification.verify',
                $expires,
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->email),
                ],
                true
            );
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $e) {
            Log::error('URL generation exception in VerifyEmailNotification: '.$e->getMessage());
            throw new \RuntimeException('Ne može se generisati URL za verifikaciju e-maila. Provjerite APP_URL u .env fajlu.', 0, $e);
        } catch (\Exception $e) {
            Log::error('Exception in VerifyEmailNotification: '.$e->getMessage());
            throw new \RuntimeException('Greška pri slanju e-mail verifikacije: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
