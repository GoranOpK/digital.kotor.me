<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\ApplicationPrigovor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationPrigovorSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $competitionTitle;

    public string $applicationNumber;

    public string $submittedAtFormatted;

    public ?string $reviewUrl;

    public function __construct(
        Application $application,
        ApplicationPrigovor $prigovor,
        string $applicationNumber,
        ?string $reviewUrl,
    ) {
        $application->loadMissing('competition');

        $this->competitionTitle = $application->competition?->title ?? '—';
        $this->applicationNumber = $applicationNumber;
        $this->submittedAtFormatted = $prigovor->submitted_at?->format('d.m.Y. H:i') ?? '—';
        $this->reviewUrl = $reviewUrl;
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Podnesen je eliminatorni prigovor – konkurs „'.$this->competitionTitle.'“')
            ->view('emails.applications.prigovor_submitted');
    }
}
