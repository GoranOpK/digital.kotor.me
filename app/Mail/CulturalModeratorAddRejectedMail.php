<?php

namespace App\Mail;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CulturalModeratorAddRejectedMail extends Mailable
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
            subject: 'Zahtjev za Moderatora nije odobren — Kalendar kulture',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cultural-moderator-add-rejected',
            with: [
                'organizerName' => $this->organizer->naziv,
                'decisionNote' => (string) $this->moderatorRequest->decision_note,
            ],
        );
    }
}
