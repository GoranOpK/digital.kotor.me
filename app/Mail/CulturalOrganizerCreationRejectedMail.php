<?php

namespace App\Mail;

use App\Models\CulturalOrganizerCreationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PO-ORG-06 Package 4 — rejection outcome for FIRST Moderator (Organizer creation).
 */
class CulturalOrganizerCreationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CulturalOrganizerCreationRequest $creationRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@kotor.me',
            subject: 'Zahtjev za Organizatora nije odobren — Kalendar kulture',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-organizer-creation-rejected',
            with: [
                'organizerName' => $this->creationRequest->proposed_naziv,
                'decisionNote' => (string) $this->creationRequest->decision_note,
            ],
        );
    }
}
