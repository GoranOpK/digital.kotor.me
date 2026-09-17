<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Support\EliminatoryProfileConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationPrigovorDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Application $application;

    public ApplicationPrigovor $prigovor;

    public string $recipientName;

    public string $decisionLabel;

    public string $applicationUrl;

    public bool $isYouth;

    public string $commissionName;

    public string $decidedAtFormatted;

    public bool $deadlineExceeded;

    public string $decisionNote;

    /** @var list<array{statement: string, outcome: string, contested: bool}> */
    public array $criterionOutcomes = [];

    /** @var list<string> */
    public array $remainingReasons = [];

    public function __construct(Application $application, ApplicationPrigovor $prigovor)
    {
        $application->loadMissing(['user', 'competition', 'eliminatoryCheck']);

        $this->application = $application;
        $this->prigovor = $prigovor;
        $this->isYouth = $application->competition?->isOmladinskoProfile() === true;
        $this->recipientName = $application->user?->name ?? ($this->isYouth ? 'podnosioče' : 'podnositeljko');
        $this->decisionLabel = $prigovor->statusLabel();
        $this->applicationUrl = route('applications.show', $application);

        $profile = EliminatoryProfileConfig::for($application->competition?->type);
        $this->commissionName = $profile->commissionName();
        $this->decidedAtFormatted = $prigovor->decided_at?->format('d.m.Y. H:i') ?? '';
        $this->deadlineExceeded = $prigovor->wasDecidedAfterKomisijaDeadline();
        $this->decisionNote = (string) $prigovor->decision_note;

        if ($this->isYouth) {
            $this->criterionOutcomes = $this->youthCriterionOutcomes($profile, $application, $prigovor);
            $this->remainingReasons = $prigovor->remainingReasonStatements();
        }
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Obavještenje o odluci Komisije po Prigovoru')
            ->view($this->isYouth
                ? 'emails.applications.omladinsko_prigovor_decision'
                : 'emails.applications.prigovor_decision');
    }

    /**
     * @return list<array{statement: string, outcome: string, contested: bool}>
     */
    private function youthCriterionOutcomes(
        EliminatoryProfileConfig $profile,
        Application $application,
        ApplicationPrigovor $prigovor,
    ): array {
        $check = $application->eliminatoryCheck;
        $rows = [];

        foreach ([1, 2, 3] as $number) {
            $isActivated = $check?->criterionIsFalse($check->{"criterion_{$number}"}) === true;
            if (! $isActivated) {
                continue;
            }

            $contested = $prigovor->criterionIsContested($number);
            $rows[] = [
                'statement' => $profile->statement($number),
                'outcome' => $prigovor->criterionOutcomeLabel($number) ?? 'Ostaje',
                'contested' => $contested,
            ];
        }

        return $rows;
    }
}
