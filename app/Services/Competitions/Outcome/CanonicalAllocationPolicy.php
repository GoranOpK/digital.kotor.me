<?php

namespace App\Services\Competitions\Outcome;

use App\Models\CanonicalOutcome;
use App\Models\CanonicalTieResolution;
use App\Services\CanonicalIndividualScoringService;
use App\Support\CompetitionRanking;

/**
 * Canonical MMSP outcome rules over the frozen preliminary ranking (čl.19, čl.22).
 *
 * - final_score_calc < 30 is never supported (30.0000000000 is eligible); rank and score are untouched.
 * - Supported amount: > 0, ≤ čl.19 legal maximum, Σ supported ≤ Poziv budget.
 * - One supported application per normalized PIB; the other stays ranked with duplicate_beneficiary_support.
 * - Funding follows the frozen rank: an unfunded contender blocks support of any worse rank.
 * - Shared rank at the funding boundary when funds do not cover the whole group: an adopted Commission
 *   majority resolution over the exact tied set decides. Ranks are never renumbered.
 *
 * Contenders are ranked applications with calc ≥ 30 that are not rejected by the Commission or as a
 * duplicate beneficiary. Neither the application id nor business_stage is a funding criterion: the
 * canonical Odluka defines no business stage and no stage priority (PO decision).
 */
final class CanonicalAllocationPolicy
{
    public const DECISION_INVALID_MESSAGE = 'Ishod mora biti "podržava" ili "odbija".';

    public const REASON_INVALID_MESSAGE = 'Razlog odbijanja nije dozvoljen.';

    public const REJECT_AMOUNT_MESSAGE = 'Odbijena prijava ne može imati iznos podrške.';

    public const BELOW_30_SUPPORT_MESSAGE = 'Prijava sa konačnom ocjenom ispod 30 bodova ne može biti podržana (čl.22).';

    public const BELOW_30_REASON_REQUIRED_MESSAGE = 'Prijava sa konačnom ocjenom ispod 30 bodova odbija se uz razlog "ispod 30 bodova".';

    public const BELOW_30_REASON_INVALID_MESSAGE = 'Razlog "ispod 30 bodova" važi samo za konačnu ocjenu ispod 30.';

    public const JUSTIFICATION_REQUIRED_MESSAGE = 'Odbijanje odlukom Komisije zahtijeva detaljno obrazloženje.';

    public const PIB_MISSING_MESSAGE = 'PIB podnosioca se ne može utvrditi. Podrška nije dozvoljena.';

    public const BUDGET_EXCEEDED_MESSAGE = 'Ukupan iznos podrške prelazi raspoloživi budžet Poziva.';

    public const NO_RANKED_MESSAGE = 'Rang lista nema prijava za utvrđivanje ishoda.';

    public const INCOMPLETE_MESSAGE = 'Svaka rangirana prijava mora imati kompletan ishod prije konačne potvrde.';

    public const DUPLICATE_SUPPORT_MESSAGE = 'Isti podnosilac (PIB) može biti podržan najviše po jednoj prijavi (čl.19).';

    public const DUPLICATE_REASON_INVALID_MESSAGE = 'Razlog "isti podnosilac podržan" važi samo kada je podržana druga prijava istog PIB-a.';

    public const DUPLICATE_REASON_REQUIRED_MESSAGE = 'Druga prijava istog PIB-a je podržana. Ova prijava se odbija uz razlog "isti podnosilac podržan" ili odlukom Komisije.';

    public const RANK_SKIPPED_MESSAGE = 'Redoslijed finansiranja prati rang listu: prijava lošijeg ranga ne može biti podržana dok bolje rangirana prijava ostaje nefinansirana.';

    public const INSUFFICIENT_FUNDS_INVALID_MESSAGE = 'Razlog "nedovoljno sredstava" nije opravdan: preostala sredstva pokrivaju zakonski maksimum prijave.';

    public const TIE_REASON_REQUIRED_MESSAGE = 'Nepodržana prijava sa jednakim rangom na granici sredstava odbija se uz razlog "nije izabrano pri jednakom rangu".';

    public const TIE_REASON_INVALID_MESSAGE = 'Razlog "nije izabrano pri jednakom rangu" važi samo kada je podržana druga prijava istog ranga, a sredstva ne pokrivaju cijelu grupu.';

    public const MAJORITY_REQUIRED_MESSAGE = 'Pri jednakom rangu na granici sredstava potrebna je usvojena odluka Komisije većinom glasova za tačan skup prijava.';

    public const MAJORITY_MISMATCH_MESSAGE = 'Podržane prijave ne odgovaraju usvojenoj odluci Komisije o jednakom rangu.';

    public function __construct(
        private readonly CanonicalAmountCapPolicy $caps,
    ) {
    }

    public static function belowThreshold(string $calc): bool
    {
        return bccomp($calc, CanonicalIndividualScoringService::CANONICAL_SUPPORT_THRESHOLD, CompetitionRanking::SCALE) < 0;
    }

    /**
     * Normalizes a raw outcome input into the canonical shape. Unknown values stay as-is so validation rejects them.
     *
     * @param  array<string, mixed>  $input
     * @return array{decision: mixed, amount: ?string, amount_raw: mixed, reason: ?string, justification: ?string, remarks: ?string}
     */
    public function normalizeOutcome(array $input): array
    {
        $text = fn (mixed $value) => is_string($value) && trim($value) !== '' ? trim($value) : null;
        $rawAmount = $input['amount'] ?? null;
        $decision = $input['decision'] ?? null;

        return [
            'decision' => $decision,
            'amount' => $this->caps->normalizeAmount($rawAmount),
            'amount_raw' => is_string($rawAmount) ? (trim($rawAmount) === '' ? null : $rawAmount) : $rawAmount,
            'reason' => $decision === CanonicalOutcome::SUPPORT ? null : $text($input['reason'] ?? null),
            'justification' => $text($input['justification'] ?? null),
            'remarks' => $text($input['remarks'] ?? null),
        ];
    }

    /**
     * Row-level validation (draft and confirmation).
     *
     * @param  array{decision: mixed, amount: ?string, amount_raw?: mixed, reason: ?string, justification: ?string}  $outcome
     */
    public function rowBlockReason(CanonicalRankedRow $row, array $outcome, ?string $remainingBudget = null): ?string
    {
        $decision = $outcome['decision'];
        if ($decision === CanonicalOutcome::SUPPORT) {
            if ($row->belowThreshold()) {
                return self::BELOW_30_SUPPORT_MESSAGE;
            }
            if ($row->pib === null) {
                return self::PIB_MISSING_MESSAGE;
            }

            return $this->caps->supportedAmountBlockReason($outcome['amount'], $row->legalMaximum, $remainingBudget);
        }

        if ($decision !== CanonicalOutcome::REJECT) {
            return self::DECISION_INVALID_MESSAGE;
        }

        $reason = $outcome['reason'];
        if (! in_array($reason, CanonicalOutcome::reasons(), true)) {
            return self::REASON_INVALID_MESSAGE;
        }
        if (($outcome['amount_raw'] ?? null) !== null || $outcome['amount'] !== null) {
            return self::REJECT_AMOUNT_MESSAGE;
        }
        if ($row->belowThreshold()) {
            return $reason === CanonicalOutcome::REASON_BELOW_30 ? null : self::BELOW_30_REASON_REQUIRED_MESSAGE;
        }

        return match ($reason) {
            CanonicalOutcome::REASON_BELOW_30 => self::BELOW_30_REASON_INVALID_MESSAGE,
            CanonicalOutcome::REASON_COMMISSION_REJECTED => $outcome['justification'] === null ? self::JUSTIFICATION_REQUIRED_MESSAGE : null,
            CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT => $row->pib === null ? self::PIB_MISSING_MESSAGE : null,
            default => $row->legalMaximum === null ? CanonicalAmountCapPolicy::BASIS_MISSING_MESSAGE : null,
        };
    }

    /**
     * Complete-set validation for atomic final confirmation.
     *
     * @param  list<CanonicalRankedRow>  $rows
     * @param  array<int, array{decision: mixed, amount: ?string, reason: ?string, justification: ?string}>  $outcomes  application id => outcome
     * @param  iterable<CanonicalTieResolution>  $adoptedResolutions
     */
    public function setBlockReason(string $budget, array $rows, array $outcomes, iterable $adoptedResolutions): ?string
    {
        if ($rows === []) {
            return self::NO_RANKED_MESSAGE;
        }

        foreach ($rows as $row) {
            if (! isset($outcomes[$row->id()])) {
                return self::INCOMPLETE_MESSAGE.' (prijava #'.$row->id().')';
            }
        }

        foreach ($rows as $row) {
            $reason = $this->rowBlockReason($row, $outcomes[$row->id()]);
            if ($reason !== null) {
                return $reason.' (prijava #'.$row->id().')';
            }
        }

        $total = $this->totalSupported($rows, $outcomes);
        if (bccomp($total, $budget, CanonicalAmountCapPolicy::SCALE) > 0) {
            return self::BUDGET_EXCEEDED_MESSAGE;
        }
        $remaining = bcsub($budget, $total, CanonicalAmountCapPolicy::SCALE);

        $supportedByPib = [];
        foreach ($rows as $row) {
            if ($this->isSupported($outcomes[$row->id()])) {
                if (isset($supportedByPib[$row->pib])) {
                    return self::DUPLICATE_SUPPORT_MESSAGE;
                }
                $supportedByPib[$row->pib] = $row->id();
            }
        }

        foreach ($rows as $row) {
            $outcome = $outcomes[$row->id()];
            if ($this->isSupported($outcome) || $row->belowThreshold()) {
                continue;
            }
            $otherSupported = $row->pib !== null && isset($supportedByPib[$row->pib]) && $supportedByPib[$row->pib] !== $row->id();
            if ($outcome['reason'] === CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT && ! $otherSupported) {
                return self::DUPLICATE_REASON_INVALID_MESSAGE.' (prijava #'.$row->id().')';
            }
            if ($otherSupported && ! in_array($outcome['reason'], [CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT, CanonicalOutcome::REASON_COMMISSION_REJECTED], true)) {
                return self::DUPLICATE_REASON_REQUIRED_MESSAGE.' (prijava #'.$row->id().')';
            }
        }

        $contenders = array_values(array_filter($rows, fn (CanonicalRankedRow $row) => $this->isContender($row, $outcomes[$row->id()])));

        foreach ($contenders as $unfunded) {
            if ($this->isSupported($outcomes[$unfunded->id()])) {
                continue;
            }
            foreach ($contenders as $funded) {
                if ($funded->rank > $unfunded->rank && $this->isSupported($outcomes[$funded->id()])) {
                    return self::RANK_SKIPPED_MESSAGE.' (prijava #'.$funded->id().')';
                }
            }
        }

        $groups = [];
        foreach ($contenders as $row) {
            $groups[$row->rank][] = $row;
        }
        ksort($groups);

        $resolutions = collect($adoptedResolutions)
            ->filter(fn (CanonicalTieResolution $resolution) => $resolution->status === CanonicalTieResolution::ADOPTED)
            ->values();

        foreach ($groups as $rank => $members) {
            $reason = $this->groupBlockReason((int) $rank, $members, $outcomes, $remaining, $resolutions);
            if ($reason !== null) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * @param  list<CanonicalRankedRow>  $rows
     * @param  array<int, array{decision: mixed, amount: ?string}>  $outcomes
     */
    public function totalSupported(array $rows, array $outcomes): string
    {
        $total = '0.00';
        foreach ($rows as $row) {
            $outcome = $outcomes[$row->id()] ?? null;
            if ($outcome !== null && $this->isSupported($outcome) && $outcome['amount'] !== null) {
                $total = bcadd($total, $outcome['amount'], CanonicalAmountCapPolicy::SCALE);
            }
        }

        return $total;
    }

    /**
     * @param  list<CanonicalRankedRow>  $members
     * @param  array<int, array{decision: mixed, reason: ?string}>  $outcomes
     * @param  \Illuminate\Support\Collection<int, CanonicalTieResolution>  $resolutions
     */
    private function groupBlockReason(int $rank, array $members, array $outcomes, string $remaining, $resolutions): ?string
    {
        $supported = array_values(array_filter($members, fn (CanonicalRankedRow $row) => $this->isSupported($outcomes[$row->id()])));
        $unsupported = array_values(array_filter($members, fn (CanonicalRankedRow $row) => ! $this->isSupported($outcomes[$row->id()])));

        foreach ($unsupported as $row) {
            $reason = $outcomes[$row->id()]['reason'];
            $expected = $supported === [] ? CanonicalOutcome::REASON_INSUFFICIENT_FUNDS : CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED;
            if ($reason !== $expected) {
                return ($supported === [] ? self::TIE_REASON_INVALID_MESSAGE : self::TIE_REASON_REQUIRED_MESSAGE).' (prijava #'.$row->id().')';
            }
            if ($row->legalMaximum !== null && bccomp($remaining, $row->legalMaximum, CanonicalAmountCapPolicy::SCALE) >= 0) {
                return ($supported === [] ? self::INSUFFICIENT_FUNDS_INVALID_MESSAGE : self::TIE_REASON_INVALID_MESSAGE).' (prijava #'.$row->id().')';
            }
        }

        if ($supported === [] || $unsupported === []) {
            return null;
        }

        $tiedIds = CanonicalTieResolution::normalizeIds(array_map(fn (CanonicalRankedRow $row) => $row->id(), $members));
        $selectedIds = CanonicalTieResolution::normalizeIds(array_map(fn (CanonicalRankedRow $row) => $row->id(), $supported));

        $resolution = $resolutions->first(fn (CanonicalTieResolution $resolution) => (int) $resolution->shared_rank === $rank
            && $resolution->tiedIds() === $tiedIds);
        if ($resolution === null) {
            return self::MAJORITY_REQUIRED_MESSAGE.' (rang '.$rank.')';
        }

        return $resolution->selectedIds() === $selectedIds ? null : self::MAJORITY_MISMATCH_MESSAGE.' (rang '.$rank.')';
    }

    /**
     * @param  array{decision: mixed}  $outcome
     */
    private function isSupported(array $outcome): bool
    {
        return $outcome['decision'] === CanonicalOutcome::SUPPORT;
    }

    /**
     * @param  array{decision: mixed, reason: ?string}  $outcome
     */
    private function isContender(CanonicalRankedRow $row, array $outcome): bool
    {
        if ($row->belowThreshold()) {
            return false;
        }

        return $this->isSupported($outcome) || ! in_array($outcome['reason'], [
            CanonicalOutcome::REASON_COMMISSION_REJECTED,
            CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT,
        ], true);
    }
}
