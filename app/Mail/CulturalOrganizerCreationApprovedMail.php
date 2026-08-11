<?php

namespace App\Mail;

use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PO-ORG-06 Package 4 — approval outcome for FIRST Moderator (Organizer creation).
 */
class CulturalOrganizerCreationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CulturalOrganizerCreationRequest $creationRequest,
        public CulturalOrganizer $organizer,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@kotor.me',
            subject: 'Zahtjev za Organizatora je odobren — Kalendar kulture',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-organizer-creation-approved',
            with: [
                'organizerName' => $this->organizer->naziv,
                'workspaceUrl' => route('cultural-moderator-workspace.index'),
            ],
        );
    }
}
