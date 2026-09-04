<?php

namespace App\Mail;

use App\Services\Payments\PaymentConfirmation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessfulConfirmationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly PaymentConfirmation $confirmation,
        public readonly ?string $pdfBinary,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@kotor.me',
            subject: 'Potvrda o uspješnoj transakciji — Digital Kotor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payments.successful-confirmation',
            with: [
                'confirmation' => $this->confirmation,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->pdfBinary === null || $this->pdfBinary === '') {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->confirmation->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
