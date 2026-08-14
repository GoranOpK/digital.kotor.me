<?php

namespace App\Mail;

use App\Services\Newsletter\NewsletterPriorityMailPayload;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class CulturalCalendarPriorityNewsletterMail extends Mailable
{
    public function __construct(
        public readonly NewsletterPriorityMailPayload $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('newsletter.from.address'),
                (string) config('newsletter.from.name')
            ),
            subject: (string) config('newsletter.priority_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-calendar-newsletter-priority',
            with: [
                'payload' => $this->payload,
            ],
        );
    }
}
