<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\ApplicationEliminatoryNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationEliminatoryRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Application $application;

    public ApplicationEliminatoryNotice $notice;

    public string $recipientName;

    public string $competitionTitle;

    public string $businessPlanName;

    public string $applicationNumber;

    public string $applicationUrl;

    public string $deadlineFormatted;

    /** @var list<string> */
    public array $failedReasons;

    public ?string $noteSnapshot;

    public function __construct(Application $application, ApplicationEliminatoryNotice $notice)
    {
        $application->loadMissing(['competition.upNumber', 'user']);

        $this->application = $application;
        $this->notice = $notice;
        $this->recipientName = $application->user?->name ?? 'podnositeljko';
        $this->competitionTitle = $application->competition?->title ?? '—';
        $this->businessPlanName = $application->business_plan_name ?? '—';

        $upBroj = $application->competition?->upNumber?->number ?? '—';
        $redniBroj = $application->redni_broj ?? '—';
        $this->applicationNumber = $upBroj.'/'.$redniBroj;
        $this->applicationUrl = route('applications.show', $application);
        $this->deadlineFormatted = $notice->prigovorDeadlineAt()->format('d.m.Y. H:i');
        $this->failedReasons = $notice->reasons_snapshot ?? [];
        $this->noteSnapshot = $notice->note_snapshot;
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Obavještenje o odbijanju prijave po eliminatornoj provjeri – konkurs „'.$this->competitionTitle.'“')
            ->view('emails.applications.eliminatory_rejection');
    }
}
