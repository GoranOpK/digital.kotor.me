<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CulturalCalendarNewsletterWeeklyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public Carbon $weekStart,
        public Carbon $weekEnd,
        public string $weekEventsLink
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@kotor.me',
            subject: 'Kalendar kulture: događaji za narednu sedmicu',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-calendar-newsletter-weekly',
        );
    }
}

