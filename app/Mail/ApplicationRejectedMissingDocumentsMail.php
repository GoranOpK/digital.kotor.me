<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationRejectedMissingDocumentsMail extends Mailable
{
    use Queueable, SerializesModels;

    public Application $application;
    public string $recipientName;
    public ?string $chairmanNotes;
    /** @var list<string> */
    public array $missingDocumentLabels;
    public string $applicationUrl;
    public string $competitionTitle;
    public string $businessPlanName;
    public string $applicationNumber;
    public string $submittedAtFormatted;

    /**
     * @param list<string> $missingDocumentLabels
     */
    public function __construct(
        Application $application,
        array $missingDocumentLabels,
        ?string $chairmanNotes = null,
    ) {
        $application->loadMissing(['competition.upNumber', 'user']);

        $this->application = $application;
        $this->recipientName = $application->user?->name ?? 'podnositeljko';
        $this->chairmanNotes = $chairmanNotes && trim($chairmanNotes) !== '' ? trim($chairmanNotes) : null;
        $this->missingDocumentLabels = $missingDocumentLabels;

        $upBroj = $application->competition?->upNumber?->number ?? '—';
        $redniBroj = $application->redni_broj ?? '—';
        $this->applicationNumber = $upBroj . '/' . $redniBroj;
        $this->competitionTitle = $application->competition?->title ?? '—';
        $this->businessPlanName = $application->business_plan_name ?? '—';
        $this->submittedAtFormatted = $application->submitted_at
            ? $application->submitted_at->format('d.m.Y H:i')
            : '—';
        $this->applicationUrl = route('applications.show', $application);
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Obavještenje o nepotpunoj dokumentaciji – konkurs „' . $this->competitionTitle . '“')
            ->view('emails.applications.rejected_missing_documents');
    }
}
