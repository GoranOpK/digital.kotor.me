<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalFinalRankingSignature;
use App\Models\CanonicalOutcome;
use App\Models\Competition;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use InvalidArgumentException;

/**
 * SHA-256 identitet konačne MMSP rang-liste.
 * Čita postojeće bodove, ishode i potpise. Ne upisuje ocjene, rang ni dokument.
 */
class MmspDecisionRankingIdentity
{
    public function rankingSha256(Competition $competition): string
    {
        return hash('sha256', $this->canonicalJson($competition));
    }

    public function canonicalJson(Competition $competition): string
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            throw new InvalidArgumentException('Identitet MMSP rang-liste važi samo za MMSP konkurs.');
        }

        $ranking = CanonicalFinalRanking::query()
            ->where('competition_id', $competition->id)
            ->first();

        $outcomes = CanonicalOutcome::query()
            ->where('competition_id', $competition->id)
            ->orderBy('application_id')
            ->get();

        $applications = Application::query()
            ->where('competition_id', $competition->id)
            ->with(['canonicalApplicantForm', 'canonicalInvestmentPlan'])
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($outcomes as $outcome) {
            $application = $applications->get($outcome->application_id);
            $form = $application?->canonicalApplicantForm;
            $plan = $application?->canonicalInvestmentPlan;
            $rows[] = [
                'application_id' => (int) $outcome->application_id,
                'ranking_position' => $application?->ranking_position === null ? '' : (string) $application->ranking_position,
                'final_score' => $this->textOrEmpty($application?->final_score),
                'decision' => (string) $outcome->decision,
                'approved_amount' => $this->moneyOrEmpty($outcome->approved_amount),
                'rejection_reason' => $this->textOrEmpty($outcome->rejection_reason),
                'confirmed_at' => $outcome->confirmed_at?->toIso8601String() ?? '',
                'subsidy_type' => $this->textOrEmpty($plan?->subsidy_type),
                'total_investment' => $this->moneyOrEmpty($plan?->total_investment),
                'legal_form' => $this->textOrEmpty($form?->legal_form),
                'person_name' => $this->textOrEmpty($form?->person_name),
                'company_name' => $this->textOrEmpty($form?->company_name),
            ];
        }

        $signatures = CanonicalFinalRankingSignature::query()
            ->where('final_ranking_id', $ranking?->id ?? 0)
            ->orderBy('canonical_seat_no')
            ->get()
            ->map(fn (CanonicalFinalRankingSignature $signature) => [
                'seat' => (int) $signature->canonical_seat_no,
                'signed_at' => $signature->signed_at?->toIso8601String() ?? '',
            ])
            ->all();

        $payload = [
            'finalized_at' => $ranking?->finalized_at?->toIso8601String() ?? '',
            'total_approved' => $this->moneyOrEmpty($ranking?->total_approved),
            'rows' => $rows,
            'signatures' => $signatures,
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function textOrEmpty(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function moneyOrEmpty(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return '';
        }

        return bcadd($value, '0', 2);
    }
}
