<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

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
        $firstName = $notifiable->first_name ?? $notifiable->name ?? 'korisnik';

        return (new MailMessage)
            ->subject('Verifikacija email adrese - Digital Kotor')
            ->greeting('Poštovani/a ' . $firstName . ',')
            ->line('Hvala vam što ste se registrovali na Digital Kotor portal.')
            ->line('Molimo vas da kliknete na dugme ispod da biste verifikovali svoju email adresu:')
            ->action('Verifikuj email adresu', $verificationUrl)
            ->line('Ovaj link za verifikaciju će isteći za ' . Config::get('auth.verification.expire', 60) . ' minuta.')
            ->line('Ako niste kreirali nalog, ignorišite ovu poruku.')
            ->salutation('Srdačan pozdrav,<br>Tim Digital Kotor');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        $expires = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60));
        
        return URL::temporarySignedRoute(
            'verification.verify',
            $expires,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->email),
            ],
            true // absolute URL
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

