<?php

namespace App\Mail;

use App\Models\CulturalOrganizerCreationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PO-ORG-06 Package 2 — invitation when proposed Moderator is not eligible at Org-create submit.
 */
class CulturalOrganizerModeratorInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CulturalOrganizerCreationRequest $creationRequest)
    {
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
                'organizerName' => $this->creationRequest->proposed_naziv,
                'registrationUrl' => route('register'),
            ],
        );
    }
}
