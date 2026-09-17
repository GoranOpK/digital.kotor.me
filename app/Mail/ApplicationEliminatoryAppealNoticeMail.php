<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\ApplicationEliminatoryNotice;
use App\Support\EliminatoryProfileConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationEliminatoryAppealNoticeMail extends Mailable
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

    /** @var list<array{statement: string, explanation: string}> */
    public array $activatedReasons;

    public string $commissionName;

    public function __construct(Application $application, ApplicationEliminatoryNotice $notice)
    {
        $application->loadMissing(['competition.upNumber', 'user', 'eliminatoryCheck']);

        $this->application = $application;
        $this->notice = $notice;
        $this->recipientName = $application->user?->name ?? 'podnosioče';
        $this->competitionTitle = $application->competition?->title ?? '—';
        $this->businessPlanName = $application->business_plan_name ?? '—';

        $upBroj = $application->competition?->upNumber?->number ?? '—';
        $redniBroj = $application->redni_broj ?? '—';
        $this->applicationNumber = $upBroj.'/'.$redniBroj;
        $this->applicationUrl = route('applications.show', $application);
        $this->deadlineFormatted = $notice->prigovorDeadlineAt()->format('d.m.Y. H:i');

        $profile = EliminatoryProfileConfig::for($application->competition?->type);
        $this->commissionName = $profile->commissionName();
        $this->activatedReasons = $profile->activatedReasonDetails($application->eliminatoryCheck);
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Obavještenje o aktiviranim eliminatornim kriterijumima – konkurs „'.$this->competitionTitle.'“')
            ->view('emails.applications.omladinsko_eliminatory_notice');
    }
}
