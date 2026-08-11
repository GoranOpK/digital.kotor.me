<?php

namespace App\Mail;

use App\Models\CulturalModeratorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PO-ORG-06 Package 5 — invitation when subsequent ADD Moderator is not eligible.
 */
class CulturalModeratorAddInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CulturalModeratorRequest $moderatorRequest,
        public string $organizerName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@kotor.me',
            subject: 'Poziv za Moderatora — Kalendar kulture Opštine Kotor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-organizer-moderator-invitation',
            with: [
                'organizerName' => $this->organizerName,
                'registrationUrl' => route('register'),
            ],
        );
    }
}
