<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Models\Competition;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class YouthDecisionProposalBuilder
{
    public function __construct(
        private readonly YouthDecisionApplicantIdentity $identity,
    ) {}

    /**
     * @param  array{decision_number: string, first_session_date: string, second_session_date: string, third_session_date: string, oral_presentation_date: string}  $input
     */
    public function build(Competition $competition, array $input, Carbon $generatedAt): YouthDecisionProposal
    {
        $competition->loadMissing('commission');

        if (! $competition->isOmladinskoProfile()) {
            throw ValidationException::withMessages([
                'decision' => YouthDecisionProposalGate::NOT_YOUTH_MESSAGE,
            ]);
        }

        $decisionNumber = trim($input['decision_number']);
        if ($decisionNumber === '') {
            throw ValidationException::withMessages([
                'decision_number' => 'Unesite broj Odluke.',
            ]);
        }

        $publishedOn = $competition->published_at;
        $endedOn = $competition->deadline;
        if ($publishedOn === null) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer konkurs nema datum objavljivanja.',
            ]);
        }
        if ($endedOn === null) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer konkurs nema datum završetka.',
            ]);
        }

        $winners = Application::query()
            ->where('competition_id', $competition->id)
            ->where('commission_decision', 'podrzava_potpuno')
            ->where('approved_amount', '>', 0)
            ->with(['businessPlan', 'user.identity.legalEntity'])
            ->orderBy('ranking_position')
            ->orderBy('id')
            ->get();

        if ($winners->isEmpty()) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer nema podržanih biznis planova.',
            ]);
        }

        $rows = [];
        $awarded = '0.00';
        foreach ($winners as $application) {
            $rows[] = $this->winnerRow($application);
            $awarded = bcadd($awarded, $this->money((string) $application->approved_amount), 2);
        }

        $received = $this->receivedApplications($competition);
        $incomplete = $received->filter(fn (Application $application) => $application->isEliminatedFromScoring())->count();
        $appeals = $this->appealCounts($competition);

        return new YouthDecisionProposal(
            competitionId: (int) $competition->id,
            decisionNumber: $decisionNumber,
            decisionDate: $generatedAt->timezone(config('app.timezone'))->format('d.m.Y.'),
            competitionYear: (string) $competition->year,
            publishedOn: $publishedOn->timezone(config('app.timezone'))->format('d.m.Y.'),
            endedOn: $endedOn->timezone(config('app.timezone'))->format('d.m.Y.'),
            firstSessionDate: $this->documentDate($input['first_session_date']),
            secondSessionDate: $this->documentDate($input['second_session_date']),
            thirdSessionDate: $this->documentDate($input['third_session_date']),
            oralDate: $this->documentDate($input['oral_presentation_date']),
            winners: $rows,
            receivedCount: $received->count(),
            incompleteCount: $incomplete,
            appealsAccepted: $appeals['accepted'],
            appealsRejected: $appeals['rejected'],
            proceedCount: $received->count() - $incomplete,
            awardedTotal: $this->formatMoney($awarded),
            commissionMemberCount: (int) $competition->commission?->activeMembers()->count(),
        );
    }

    /**
     * @return array{identity: string, plan: string, score: string, approved: string, total_value: string}
     */
    private function winnerRow(Application $application): array
    {
        $plan = trim((string) $application->business_plan_name);
        $label = $plan !== '' ? $plan : 'prijava '.$application->id;
        $identity = $this->identity->line($application);
        if ($identity === null) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi „'.$label.'“ nedostaju identifikacioni podaci. Nedostajući podaci se ne dopisuju.',
            ]);
        }
        if ($plan === '') {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi nedostaje naziv biznis plana.',
            ]);
        }
        if ($application->final_score === null) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi „'.$plan.'“ nedostaje konačan broj bodova.',
            ]);
        }

        $required = $application->businessPlan?->required_amount;
        if ($required === null) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi „'.$plan.'“ nedostaje ukupna vrijednost biznis plana.',
            ]);
        }

        return [
            'identity' => $identity,
            'plan' => $plan,
            'score' => $this->formatMoney((string) $application->final_score),
            'approved' => $this->formatMoney((string) $application->approved_amount),
            'total_value' => $this->formatMoney((string) $required),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Application>
     */
    private function receivedApplications(Competition $competition)
    {
        return Application::query()
            ->where('competition_id', $competition->id)
            ->whereIn('status', ['submitted', 'evaluated', 'approved', 'rejected'])
            ->with(['eliminatoryCheck', 'prigovor'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{accepted: int, rejected: int}
     */
    private function appealCounts(Competition $competition): array
    {
        $base = ApplicationPrigovor::query()
            ->whereHas('application', function ($query) use ($competition) {
                $query->where('competition_id', $competition->id);
            });

        return [
            'accepted' => (clone $base)->where('status', ApplicationPrigovor::STATUS_PRIHVACEN)->count(),
            'rejected' => (clone $base)->where('status', ApplicationPrigovor::STATUS_ODBIJEN)->count(),
        ];
    }

    private function documentDate(string $value): string
    {
        return Carbon::createFromFormat('Y-m-d', $value)->format('d.m.Y');
    }

    private function money(string $value): string
    {
        return bcadd($value, '0', 2);
    }

    private function formatMoney(string $value): string
    {
        return number_format((float) $this->money($value), 2, ',', '.');
    }
}
