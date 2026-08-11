<?php

namespace App\Mail;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CulturalModeratorRemoveApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CulturalModeratorRequest $moderatorRequest,
        public CulturalOrganizer $organizer,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@kotor.me',
            subject: 'Ovlašćenje Moderatora je uklonjeno — Kalendar kulture',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-moderator-remove-approved',
            with: [
                'organizerName' => $this->organizer->naziv,
            ],
        );
    }
}
