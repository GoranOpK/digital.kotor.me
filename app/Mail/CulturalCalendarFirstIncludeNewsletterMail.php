<?php

namespace App\Mail;

use App\Services\Newsletter\NewsletterFirstIncludeMailPayload;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

final class CulturalCalendarFirstIncludeNewsletterMail extends Mailable
{
    public function __construct(
        public readonly NewsletterFirstIncludeMailPayload $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('newsletter.from.address'),
                (string) config('newsletter.from.name')
            ),
            subject: (string) config('newsletter.first_include_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-calendar-newsletter-first-include',
            with: [
                'payload' => $this->payload,
            ],
        );
    }
}
