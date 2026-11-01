<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\Competition;
use App\Services\CanonicalIndividualScoringService;
use InvalidArgumentException;

/**
 * SHA-256 identitet konačne PM rang-liste.
 * Čita postojeće bodove i rang. Ne upisuje ocjene, rang ni dokument.
 */
class YouthDecisionRankingIdentity
{
    public function __construct(
        private readonly CanonicalIndividualScoringService $scoring,
        private readonly YouthDecisionApplicantIdentity $identity,
    ) {}

    public function rankingSha256(Competition $competition): string
    {
        return hash('sha256', $this->canonicalJson($competition));
    }

    public function canonicalJson(Competition $competition): string
    {
        if (! $competition->isOmladinskoProfile()) {
            throw new InvalidArgumentException('Identitet PM rang-liste važi samo za omladinski konkurs.');
        }

        $applications = $this->scoring->youthScoringCycleApplications($competition)
            ->sortBy(fn (Application $application) => (int) $application->id)
            ->values();
        $positions = $this->scoring->expectedYouthRankingPositions($applications);

        $rows = [];
        foreach ($applications as $application) {
            $aggregate = $this->scoring->aggregateYouthApplication($application);
            $supported = $this->entersDecision($application);
            $rows[] = [
                'application_id' => (int) $application->id,
                'final_score_full' => is_array($aggregate) ? (string) $aggregate['final_score_full'] : '',
                'ranking_position' => isset($positions[$application->id]) ? (string) $positions[$application->id] : '',
                'status' => (string) $application->status,
                'commission_decision' => $this->textOrEmpty($application->commission_decision, false),
                'approved_amount' => $this->moneyOrEmpty($application->approved_amount),
                'commission_justification' => $this->textOrEmpty($application->commission_justification, true),
                'applicant_identity' => $supported ? ($this->identity->line($application) ?? '') : '',
                'plan_name' => $supported ? $this->textOrEmpty($application->business_plan_name, true) : '',
                'required_amount' => $supported ? $this->moneyOrEmpty($application->businessPlan?->required_amount) : '',
            ];
        }

        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function entersDecision(Application $application): bool
    {
        if ($application->commission_decision !== 'podrzava_potpuno') {
            return false;
        }

        $amount = $this->moneyOrEmpty($application->approved_amount);

        return $amount !== '' && bccomp($amount, '0.00', 2) === 1;
    }

    private function textOrEmpty(mixed $value, bool $trim): string
    {
        if (! is_string($value)) {
            return '';
        }

        $text = $trim ? trim($value) : $value;

        return $text;
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
