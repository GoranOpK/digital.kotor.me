<?php

namespace App\Services\Competitions\Outcome;

use App\Models\Application;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalFinalRankingSignature;
use App\Models\CanonicalOutcome;
use App\Models\CanonicalPredlog;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalTieResolution;
use App\Models\CanonicalTieVote;
use App\Models\Commission;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\ProcessWriteResult;
use App\Support\CanonicalSubsidyType;
use Illuminate\Support\Facades\DB;

/**
 * Canonical Predlog foundation: readiness gate and an immutable, private dataset for the next
 * wave (Secretariat Predlog → Odluka). Not public, not an Odluka, not a contract or payment,
 * never published automatically. Excludes internal remarks, individual scores and personal identifiers.
 */
final class CanonicalPredlogService
{
    public const SCHEMA = 'kn.canonical.predlog.v2';

    public const NOT_CONFIRMED_MESSAGE = 'Predlog nije spreman: ishod nije atomski potvrđen.';

    public const INCOMPLETE_MESSAGE = 'Predlog nije spreman: svaka rangirana prijava mora imati potvrđen ishod.';

    public const NOT_SIGNED_MESSAGE = 'Predlog nije spreman: konačnu rang listu moraju potpisati sva 3 važeća člana Komisije.';

    public const NOT_PREPARED_MESSAGE = 'Predlog nije pripremljen.';

    public const ALREADY_FINALIZED_MESSAGE = 'Predlog je već finalizovan.';

    public const NOT_FINALIZED_MESSAGE = 'Predlog nije finalizovan.';

    public const INTEGRITY_MESSAGE = 'Snimak Predloga nije netaknut. Radnja je blokirana.';

    public const SOURCE_CHANGED_MESSAGE = 'Izvorni podaci Predloga (bodovi, rang, ishodi, iznosi, vrsta podrške, izjednačenja, potpisi ili Komisija) više se ne poklapaju sa pripremljenim snimkom. Finalizacija je blokirana.';

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalAdministrativeGate $gate,
    ) {
    }

    public function readinessBlockReason(Competition $competition): ?string
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE;
        }

        $ranking = CanonicalFinalRanking::query()->where('competition_id', $competition->id)->first();
        if ($ranking === null || $ranking->confirmed_at === null) {
            return self::NOT_CONFIRMED_MESSAGE;
        }

        $rankedIds = Application::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('ranking_position')
            ->pluck('id');
        $confirmed = CanonicalOutcome::query()
            ->where('final_ranking_id', $ranking->id)
            ->whereNotNull('confirmed_at')
            ->pluck('application_id');
        if ($rankedIds->isEmpty() || $rankedIds->diff($confirmed)->isNotEmpty() || $confirmed->diff($rankedIds)->isNotEmpty()) {
            return self::INCOMPLETE_MESSAGE;
        }

        $quorum = $this->governance->commissionConfig()->allMembersRequiredCount;
        $seats = CanonicalFinalRankingSignature::query()->where('final_ranking_id', $ranking->id)->pluck('canonical_seat_no')->unique();
        if (! $ranking->isFinal() || $seats->count() !== $quorum) {
            return self::NOT_SIGNED_MESSAGE;
        }

        return null;
    }

    /**
     * Idempotent: the first prepared snapshot is the only one.
     */
    public function prepare(CanonicalFinalRanking $ranking): CanonicalPredlog
    {
        $existing = CanonicalPredlog::query()->where('final_ranking_id', $ranking->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $competition = Competition::query()->findOrFail($ranking->competition_id);
        $reason = $this->readinessBlockReason($competition);
        if ($reason !== null) {
            throw new \LogicException($reason);
        }

        $payload = $this->payload($ranking, $competition);

        return CanonicalPredlog::query()->create([
            'competition_id' => $competition->id,
            'final_ranking_id' => $ranking->id,
            'payload' => $payload,
            'fingerprint' => self::fingerprint($payload),
            'prepared_at' => now(),
        ]);
    }

    public function predlogFor(Competition $competition): ?CanonicalPredlog
    {
        return CanonicalPredlog::query()->where('competition_id', $competition->id)->first();
    }

    /**
     * Finalization re-verifies the prepared snapshot against live source facts and never recalculates.
     */
    public function finalizationBlockReason(Competition $competition): ?string
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return $base;
        }

        $readiness = $this->readinessBlockReason($competition);
        if ($readiness !== null) {
            return $readiness;
        }

        $predlog = $this->predlogFor($competition);
        if ($predlog === null) {
            return self::NOT_PREPARED_MESSAGE;
        }

        if ($predlog->isFinalized()) {
            return self::ALREADY_FINALIZED_MESSAGE;
        }

        if (! $predlog->integrityIntact()) {
            return self::INTEGRITY_MESSAGE;
        }

        $ranking = CanonicalFinalRanking::query()->find($predlog->final_ranking_id);
        if ($ranking === null || (int) $ranking->competition_id !== (int) $competition->id) {
            return self::INTEGRITY_MESSAGE;
        }

        if (! hash_equals((string) $predlog->fingerprint, self::fingerprint($this->payload($ranking, $competition)))) {
            return self::SOURCE_CHANGED_MESSAGE;
        }

        return null;
    }

    public function finalize(Competition $competition, User $actor): ProcessWriteResult
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->chairBlockReason($actor, $competition);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        return DB::transaction(function () use ($competition, $actor) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            $predlog = CanonicalPredlog::query()->where('competition_id', $locked->id)->lockForUpdate()->first();

            $reason = $this->finalizationBlockReason($locked);
            if ($reason !== null) {
                return ProcessWriteResult::failed($reason);
            }

            $predlog->forceFill([
                'finalized_at' => now(),
                'finalized_by_user_id' => $actor->id,
            ])->save();

            $this->gate->audit($locked, CanonicalAdministrativeEvent::PREDLOG_FINALIZED, $actor, $predlog, [
                'fingerprint' => $predlog->fingerprint,
                'final_ranking_id' => (int) $predlog->final_ranking_id,
            ]);

            return ProcessWriteResult::ok($predlog->id);
        });
    }

    /**
     * Post-finalization gate for the Secretariat steps: finalized and untampered.
     */
    public function finalizedPredlogBlockReason(Competition $competition): ?string
    {
        $predlog = $this->predlogFor($competition);
        if ($predlog === null) {
            return self::NOT_PREPARED_MESSAGE;
        }

        if (! $predlog->isFinalized()) {
            return self::NOT_FINALIZED_MESSAGE;
        }

        return $predlog->integrityIntact() ? null : self::INTEGRITY_MESSAGE;
    }

    /**
     * Key-order independent SHA-256 (MySQL JSON reorders object keys).
     *
     * @param  array<mixed>  $payload
     */
    public static function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode(self::sortKeys($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CanonicalFinalRanking $ranking, Competition $competition): array
    {
        $session = CommissionSession::query()->findOrFail($ranking->final_decision_session_id);
        $outcomes = CanonicalOutcome::query()->where('final_ranking_id', $ranking->id)->get()->keyBy('application_id');
        $applications = Application::query()
            ->whereIn('id', $outcomes->keys())
            ->with(['finalClaim', 'canonicalInvestmentPlan'])
            ->get()
            ->sort(fn (Application $a, Application $b) => [(int) $a->ranking_position, (int) $a->id] <=> [(int) $b->ranking_position, (int) $b->id])
            ->values();
        $rankCounts = $applications->countBy(fn (Application $application) => (int) $application->ranking_position);

        $rows = $applications->map(function (Application $application) use ($outcomes, $rankCounts) {
            $outcome = $outcomes[$application->id];
            $plan = $application->canonicalInvestmentPlan;
            $subsidyType = $plan?->subsidy_type;

            return [
                'application_id' => (int) $application->id,
                'rank' => (int) $application->ranking_position,
                'shared_rank' => $rankCounts[(int) $application->ranking_position] > 1,
                'final_score_display' => (string) $application->final_score,
                'final_score_calc' => (string) $application->final_score_calc,
                'applicant' => $this->applicantName($application),
                'company' => $this->companyName($application),
                'business_plan_name' => $application->business_plan_name,
                'pib' => $application->finalClaim?->beneficiary_pib ?? $application->pib,
                'subsidy_type' => $subsidyType,
                'subsidy_type_label' => CanonicalSubsidyType::isValid($subsidyType) ? CanonicalSubsidyType::label($subsidyType) : null,
                'total_investment' => $plan ? (string) $plan->total_investment : null,
                'decision' => $outcome->decision,
                'approved_amount' => $outcome->approved_amount === null ? null : (string) $outcome->approved_amount,
                'rejection_reason' => $outcome->rejection_reason,
                'rejection_reason_label' => $outcome->rejection_reason ? CanonicalOutcome::REASON_LABELS[$outcome->rejection_reason] : null,
                'justification' => $outcome->justification,
            ];
        })->all();

        $ties = CanonicalTieResolution::query()
            ->where('competition_id', $competition->id)
            ->where('status', CanonicalTieResolution::ADOPTED)
            ->whereNotNull('locked_at')
            ->orderBy('shared_rank')
            ->orderBy('id')
            ->get()
            ->map(function (CanonicalTieResolution $resolution) {
                $votes = CanonicalTieVote::query()->where('tie_resolution_id', $resolution->id)->get();

                return [
                    'shared_rank' => (int) $resolution->shared_rank,
                    'tied_application_ids' => $resolution->tiedIds(),
                    'selected_application_ids' => $resolution->selectedIds(),
                    'votes_in_favour' => $votes->where('in_favour', true)->count(),
                    'votes_against' => $votes->where('in_favour', false)->count(),
                    'decided_at' => $resolution->decided_at?->toIso8601String(),
                ];
            })->all();

        $signatures = CanonicalFinalRankingSignature::query()
            ->where('final_ranking_id', $ranking->id)
            ->orderBy('canonical_seat_no')
            ->get()
            ->map(fn (CanonicalFinalRankingSignature $signature) => [
                'canonical_seat_no' => (int) $signature->canonical_seat_no,
                'canonical_seat' => $signature->canonical_seat,
                'signer_name' => $signature->signer_name,
                'signed_at' => $signature->signed_at->toIso8601String(),
            ])->all();

        $total = (string) $ranking->total_approved;
        $budget = (string) $ranking->budget;
        $commission = Commission::query()->find($ranking->commission_id);

        return [
            'schema' => self::SCHEMA,
            'is_public' => false,
            'is_odluka' => false,
            'is_contract' => false,
            'is_payment' => false,
            'competition' => [
                'id' => (int) $competition->id,
                'title' => $competition->title,
                'year' => $competition->year,
                'type' => $competition->type,
                'reference_number' => $competition->upNumber()->value('number'),
                'budget' => $budget,
            ],
            'commission' => [
                'id' => (int) $ranking->commission_id,
                'name' => $commission?->name,
                'appointment_reference' => $commission?->appointment_reference,
                'mandate_starts_at' => $commission?->mandate_starts_at?->toIso8601String(),
                'mandate_ends_at' => $commission?->mandate_ends_at?->toIso8601String(),
            ],
            'final_decision_session' => [
                'id' => (int) $session->id,
                'held_at' => $session->held_at?->toIso8601String(),
                'due_at' => $session->due_at?->toIso8601String(),
                'late' => (bool) $session->late,
                'compliance_warning' => $session->compliance_warning,
            ],
            'final_ranking' => [
                'id' => (int) $ranking->id,
                'confirmed_at' => $ranking->confirmed_at->toIso8601String(),
                'finalized_at' => $ranking->finalized_at?->toIso8601String(),
                'total_approved' => $total,
                'residual_unallocated' => bcsub($budget, $total, CanonicalAmountCapPolicy::SCALE),
                'supported_count' => collect($rows)->where('decision', CanonicalOutcome::SUPPORT)->count(),
                'rejected_count' => collect($rows)->where('decision', CanonicalOutcome::REJECT)->count(),
            ],
            'rows' => $rows,
            'tie_resolutions' => $ties,
            'signatures' => $signatures,
        ];
    }

    private function applicantName(Application $application): ?string
    {
        foreach (['founder_name', 'director_name', 'preduzetnik_name', 'physical_person_name'] as $field) {
            $value = trim((string) $application->{$field});
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function companyName(Application $application): ?string
    {
        foreach (['doo_name', 'preduzetnik_name'] as $field) {
            $value = trim((string) $application->{$field});
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function sortKeys(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => is_array($item) ? self::sortKeys($item) : $item, $value);
    }
}
