<?php

namespace App\Mail;

use App\Models\Competition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpisakKandidataMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $body;

    public function __construct(public Competition $competition)
    {
        $competition->load(['upNumber', 'applications' => fn ($q) => $q->whereNotNull('redni_broj')->orderBy('redni_broj')]);
        $upBroj = $competition->upNumber?->number ?? '—';
        $applications = $competition->applications;

        $blocks = [];
        foreach ($applications as $app) {
            $blocks[] = $upBroj . '/' . $app->redni_broj . "\n" . $app->getObrazacTextForEmail();
        }
        $this->body = implode("\n\n---\n\n", $blocks);
    }

    public function envelope(): Envelope
    {
        $upBroj = $this->competition->upNumber?->number ?? '—';
        return new Envelope(
            from: 'noreply@kotor.me',
            to: ['rada.radenovic@kotor.me'],
            subject: 'Spisak kandidata za konkurs pod brojem "' . $upBroj . '"',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.spisak-kandidata-text',
        );
    }
}
