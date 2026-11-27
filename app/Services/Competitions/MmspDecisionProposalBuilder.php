<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CanonicalOutcome;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MmspDecisionProposalBuilder
{
    /**
     * @param  array{first_session_date: string, second_session_date: string, third_session_date: string}  $input
     */
    public function build(Competition $competition, array $input, Carbon $generatedAt): MmspDecisionProposal
    {
        if ($competition->type !== \App\Services\Competitions\Profile\CompetitionProfileResolver::TYPE_CANONICAL) {
            throw ValidationException::withMessages([
                'decision' => MmspDecisionProposalGate::NOT_MMSP_MESSAGE,
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

        $outcomes = CanonicalOutcome::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('confirmed_at')
            ->get()
            ->keyBy('application_id');

        $applications = Application::query()
            ->where('competition_id', $competition->id)
            ->whereIn('id', $outcomes->keys())
            ->with(['canonicalApplicantForm', 'canonicalInvestmentPlan'])
            ->get()
            ->sort(fn (Application $left, Application $right): int => [(int) $left->ranking_position, (int) $left->id] <=> [(int) $right->ranking_position, (int) $right->id])
            ->values();

        $rows = [];
        $awarded = '0.00';
        foreach ($applications as $application) {
            $outcome = $outcomes->get($application->id);
            if (! $outcome instanceof CanonicalOutcome || ! $outcome->isSupport()) {
                continue;
            }

            $rows[] = $this->winnerRow($application, $outcome);
            $awarded = bcadd($awarded, $this->money((string) $outcome->approved_amount), 2);
        }

        $received = $this->receivedApplications($competition);
        $review = $this->reviewCounts($competition, $received);
        $appeals = $this->appealCounts($competition);

        return new MmspDecisionProposal(
            competitionId: (int) $competition->id,
            decisionNumber: '',
            decisionDate: $generatedAt->timezone(config('app.timezone'))->format('d.m.Y.'),
            competitionYear: (string) $competition->year,
            publishedOn: $publishedOn->timezone(config('app.timezone'))->format('d.m.Y.'),
            endedOn: $endedOn->timezone(config('app.timezone'))->format('d.m.Y.'),
            firstSessionDate: $this->documentDate($input['first_session_date']),
            secondSessionDate: $this->documentDate($input['second_session_date']),
            thirdSessionDate: $this->documentDate($input['third_session_date']),
            winners: $rows,
            receivedCount: $received->count(),
            incompleteCount: $review['original_incomplete'],
            appealsAccepted: $appeals['accepted'],
            appealsRejected: $appeals['rejected'],
            proceedCount: $review['proceed'],
            awardedTotal: $this->formatMoney($awarded),
            callDurationDays: (int) ($competition->deadline_days ?? 20),
            participantCount: $this->participantCount($received),
            commissionMemberCount: $this->commissionMemberCount($competition),
        );
    }

    /**
     * @return array{identity: string, subsidy: string, score: string, approved: string, total_value: string}
     */
    private function winnerRow(Application $application, CanonicalOutcome $outcome): array
    {
        $amount = $outcome->approved_amount;
        if ($amount === null || bccomp($this->money((string) $amount), '0.00', 2) !== 1) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer podržanoj prijavi nedostaje dodijeljeni iznos.',
            ]);
        }

        if ($application->final_score === null || $application->final_score === '') {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi nedostaje konačan broj bodova.',
            ]);
        }

        $plan = $application->canonicalInvestmentPlan;
        $subsidy = $plan?->subsidy_type;
        if (! is_string($subsidy) || ! CanonicalSubsidyType::isValid($subsidy)) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi nedostaje vrsta subvencije.',
            ]);
        }

        $total = $plan?->total_investment;
        if ($total === null || $total === '') {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi nedostaje ukupna vrijednost plana ulaganja.',
            ]);
        }

        return [
            'identity' => $this->identityLine($application),
            'subsidy' => CanonicalSubsidyType::label($subsidy),
            'score' => $this->formatMoney((string) $application->final_score),
            'approved' => $this->formatMoney((string) $amount),
            'total_value' => $this->formatMoney((string) $total),
        ];
    }

    private function identityLine(Application $application): string
    {
        $form = $application->canonicalApplicantForm;
        if ($form === null) {
            throw ValidationException::withMessages([
                'decision' => 'Odluka se ne može generisati jer prijavi nedostaje obrazac podnosioca.',
            ]);
        }

        $person = trim((string) $form->person_name);
        if ($form->legal_form === CanonicalLegalForm::DRUSTVO) {
            $company = trim((string) $form->company_name);
            if ($company === '' || $person === '') {
                throw ValidationException::withMessages([
                    'decision' => 'Odluka se ne može generisati jer prijavi nedostaje naziv društva ili odgovorno lice.',
                ]);
            }

            return $company.' i '.$person;
        }

        if ($form->legal_form === CanonicalLegalForm::PREDUZETNIK) {
            if ($person === '') {
                throw ValidationException::withMessages([
                    'decision' => 'Odluka se ne može generisati jer prijavi nedostaje ime i prezime preduzetnika.',
                ]);
            }

            return $person;
        }

        throw ValidationException::withMessages([
            'decision' => 'Odluka se ne može generisati jer prijavi nedostaje pravna forma podnosioca.',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Application>
     */
    private function receivedApplications(Competition $competition)
    {
        return Application::query()
            ->where('competition_id', $competition->id)
            ->whereIn('status', ['submitted', 'evaluated', 'approved', 'rejected'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Brojevi za obrazloženje odluke. Ne mijenja ocjenu kompletnosti ni prigovor.
     * Prvobitno nepotpuna prijava ostaje u tom broju i kada je prigovor kasnije prihvaćen.
     * Prihvaćen prigovor vraća tu prijavu u dalje razmatranje.
     *
     * @param  \Illuminate\Support\Collection<int, Application>  $received
     * @return array{original_incomplete: int, proceed: int}
     */
    private function reviewCounts(Competition $competition, $received): array
    {
        $ids = $received->pluck('id')->map(fn ($id) => (int) $id)->all();
        $acceptedIds = ApplicationComplaint::query()
            ->where('competition_id', $competition->id)
            ->where('decision', ApplicationComplaint::ACCEPTED)
            ->whereIn('application_id', $ids)
            ->pluck('application_id')
            ->map(fn ($id) => (int) $id)
            ->unique();
        $assessments = ApplicationCompletenessAssessment::query()
            ->whereIn('application_id', $ids)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ApplicationCompletenessAssessment $row) => (int) $row->application_id);

        $originalIncomplete = 0;
        $proceed = 0;
        foreach ($ids as $id) {
            $rows = $assessments->get($id);
            $hadIncomplete = $rows !== null && $rows->contains(
                fn (ApplicationCompletenessAssessment $row) => $row->outcome === ApplicationCompletenessAssessment::INCOMPLETE
            );
            $latest = $rows?->last();
            $stillIncomplete = $latest instanceof ApplicationCompletenessAssessment
                && $latest->outcome === ApplicationCompletenessAssessment::INCOMPLETE;
            if ($hadIncomplete) {
                $originalIncomplete++;
            }
            if ($stillIncomplete && ! $acceptedIds->contains($id)) {
                continue;
            }
            $proceed++;
        }

        return [
            'original_incomplete' => $originalIncomplete,
            'proceed' => $proceed,
        ];
    }

    /**
     * Učesnici iz člana 5. Isti PIB je jedan podnosilac i kada ima više prijava.
     * Prijava bez PIB-a ne spaja se sa drugom prijavom.
     *
     * @param  \Illuminate\Support\Collection<int, Application>  $received
     */
    private function participantCount($received): int
    {
        $seen = [];
        foreach ($received as $application) {
            $pib = trim((string) $application->pib);
            $seen[$pib !== '' ? 'pib:'.$pib : 'application:'.$application->id] = true;
        }

        return count($seen);
    }

    private function commissionMemberCount(Competition $competition): int
    {
        if (! $competition->commission_id) {
            return 0;
        }

        return CommissionMember::query()
            ->where('commission_id', $competition->commission_id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * @return array{accepted: int, rejected: int}
     */
    private function appealCounts(Competition $competition): array
    {
        $base = ApplicationComplaint::query()->where('competition_id', $competition->id);

        return [
            'accepted' => (clone $base)->where('decision', ApplicationComplaint::ACCEPTED)->count(),
            'rejected' => (clone $base)->where('decision', ApplicationComplaint::REJECTED)->count(),
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
