<?php

namespace App\Support;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\EvaluationScore;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScoringSeatBackfill
{
    public static function assertPreflightAgainstExistingSchema(): void
    {
        self::assertNoInvalidSubstitutes();
        self::assertNoMemberCommissionMismatch();
        self::assertNoAmbiguousRegularSeats();
        self::assertCompletedScoresAreUnambiguouslyMappable();
    }

    public static function backfillAfterColumnsExist(): void
    {
        foreach (Commission::query()->with('members')->get() as $commission) {
            self::backfillCommissionMembers($commission);
        }

        self::backfillCompletedScores();
    }

    public static function assertPostBackfillInvariants(): void
    {
        self::assertPartialsRemainUnmapped();
        self::assertNoInventedCompletedAt();
        self::assertNoDuplicateCompletedSeats();
        self::assertNoUnmappedCompletedScores();
    }

    private static function assertNoInvalidSubstitutes(): void
    {
        $invalid = CommissionMember::query()
            ->where('is_substitute', true)
            ->where(function ($q) {
                $q->whereNull('replaces_member_number')
                    ->orWhere('replaces_member_number', '<', 1)
                    ->orWhere('replaces_member_number', '>', 5);
            })
            ->pluck('id');

        if ($invalid->isNotEmpty()) {
            throw new RuntimeException(
                'Fail-closed scoring seat backfill: substitute without valid replaces_member_number 1–5. member_ids='
                .$invalid->implode(',')
            );
        }
    }

    private static function assertNoMemberCommissionMismatch(): void
    {
        $mismatched = DB::table('evaluation_scores as es')
            ->join('commission_members as cm', 'cm.id', '=', 'es.commission_member_id')
            ->join('applications as a', 'a.id', '=', 'es.application_id')
            ->join('competitions as c', 'c.id', '=', 'a.competition_id')
            ->where(function ($q) {
                $q->whereNull('c.commission_id')
                    ->orWhereColumn('cm.commission_id', '<>', 'c.commission_id');
            })
            ->pluck('es.id');

        if ($mismatched->isNotEmpty()) {
            throw new RuntimeException(
                'Fail-closed scoring seat backfill: evaluation_score member/commission mismatch. score_ids='
                .$mismatched->implode(',')
            );
        }
    }

    private static function assertNoAmbiguousRegularSeats(): void
    {
        foreach (Commission::query()->with('members')->get() as $commission) {
            $regulars = $commission->members->filter(fn (CommissionMember $m) => ! $m->is_substitute);
            $presidents = $regulars->where('position', 'predsjednik');
            $opstina = $regulars->filter(fn (CommissionMember $m) => $m->position === 'clan' && $m->member_type === 'opstina');
            $udruzenje = $regulars->filter(fn (CommissionMember $m) => $m->position === 'clan' && $m->member_type === 'udruzenje');
            $zene = $regulars->filter(fn (CommissionMember $m) => $m->position === 'clan' && $m->member_type === 'zene_mreza');

            $hasCompleted = EvaluationScore::query()
                ->whereIn('commission_member_id', $commission->members->pluck('id'))
                ->whereNotNull('criterion_1')
                ->whereNotNull('criterion_2')
                ->whereNotNull('criterion_3')
                ->whereNotNull('criterion_4')
                ->whereNotNull('criterion_5')
                ->whereNotNull('criterion_6')
                ->whereNotNull('criterion_7')
                ->whereNotNull('criterion_8')
                ->whereNotNull('criterion_9')
                ->whereNotNull('criterion_10')
                ->exists();

            if (! $hasCompleted) {
                continue;
            }

            if ($presidents->count() !== 1 || $opstina->count() !== 2 || $udruzenje->count() !== 1 || $zene->count() !== 1) {
                throw new RuntimeException(
                    "Fail-closed scoring seat backfill: ambiguous canonical seats on commission {$commission->id} with completed scores."
                );
            }

            $allMap = CommissionCanonicalSeat::regularSeatMap($commission, false);
            $activeMap = CommissionCanonicalSeat::regularSeatMap($commission, true);
            foreach ($activeMap as $memberId => $activeSeat) {
                if (($allMap[$memberId] ?? null) !== $activeSeat) {
                    throw new RuntimeException(
                        "Fail-closed scoring seat backfill: seat 2/3 history ambiguity on commission {$commission->id} member {$memberId}."
                    );
                }
            }
        }
    }

    /**
     * Infer seats from existing member data. Does not require canonical_seat_no columns.
     * Partials and empty placeholders are not mapped and are not a preflight failure.
     */
    private static function assertCompletedScoresAreUnambiguouslyMappable(): void
    {
        $scores = EvaluationScore::query()->with('commissionMember.commission.members')->get();
        $seen = [];

        foreach ($scores as $score) {
            if (CommissionCanonicalSeat::isPartial($score)) {
                continue;
            }

            if (! CommissionCanonicalSeat::isHistoricallyCompleted($score)) {
                continue;
            }

            $member = $score->commissionMember;
            if ($member === null) {
                throw new RuntimeException(
                    "Fail-closed scoring seat backfill: completed score {$score->id} has no commission member."
                );
            }

            $seat = CommissionCanonicalSeat::resolvePersistedOrInfer($member);
            if ($seat === null) {
                throw new RuntimeException(
                    "Fail-closed scoring seat backfill: unmapped completed evaluation_score {$score->id}."
                );
            }

            $key = $score->application_id.':'.$seat;
            if (isset($seen[$key])) {
                throw new RuntimeException(
                    "Fail-closed scoring seat backfill: duplicate completed evaluations for one application+seat. app {$score->application_id} seat {$seat}"
                );
            }
            $seen[$key] = $score->id;
        }
    }

    private static function backfillCommissionMembers(Commission $commission): void
    {
        $regularMap = CommissionCanonicalSeat::regularSeatMap($commission, false);

        foreach ($commission->members as $member) {
            $seat = $member->is_substitute
                ? (int) $member->replaces_member_number
                : ($regularMap[$member->id] ?? null);

            if ($seat !== null) {
                DB::table('commission_members')->where('id', $member->id)->update([
                    'canonical_seat_no' => $seat,
                ]);
                $member->canonical_seat_no = $seat;
            }
        }
    }

    private static function backfillCompletedScores(): void
    {
        $scores = EvaluationScore::query()->with('commissionMember.commission.members')->get();

        foreach ($scores as $score) {
            if (CommissionCanonicalSeat::isPartial($score)) {
                continue;
            }

            if (! CommissionCanonicalSeat::isHistoricallyCompleted($score)) {
                continue;
            }

            CommissionCanonicalSeat::assignSeatToCompletedLegacyRow($score);
        }
    }

    private static function assertPartialsRemainUnmapped(): void
    {
        $mappedPartials = EvaluationScore::query()
            ->whereNotNull('canonical_seat_no')
            ->get()
            ->filter(fn (EvaluationScore $score) => CommissionCanonicalSeat::isPartial($score));

        if ($mappedPartials->isNotEmpty()) {
            throw new RuntimeException(
                'Fail-closed scoring seat backfill: partial evaluation_scores must remain unmapped. score_ids='
                .$mappedPartials->pluck('id')->implode(',')
            );
        }
    }

    private static function assertNoInventedCompletedAt(): void
    {
        $invented = EvaluationScore::query()
            ->whereNotNull('completed_at')
            ->pluck('id');

        if ($invented->isNotEmpty()) {
            throw new RuntimeException(
                'Fail-closed scoring seat backfill: completed_at must stay NULL for legacy rows. score_ids='
                .$invented->implode(',')
            );
        }
    }

    private static function assertNoDuplicateCompletedSeats(): void
    {
        $duplicates = DB::table('evaluation_scores')
            ->whereNotNull('canonical_seat_no')
            ->whereNotNull('criterion_1')
            ->whereNotNull('criterion_2')
            ->whereNotNull('criterion_3')
            ->whereNotNull('criterion_4')
            ->whereNotNull('criterion_5')
            ->whereNotNull('criterion_6')
            ->whereNotNull('criterion_7')
            ->whereNotNull('criterion_8')
            ->whereNotNull('criterion_9')
            ->whereNotNull('criterion_10')
            ->select('application_id', 'canonical_seat_no', DB::raw('COUNT(*) as cnt'))
            ->groupBy('application_id', 'canonical_seat_no')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $sample = $duplicates->map(fn ($row) => "app {$row->application_id} seat {$row->canonical_seat_no}")->implode('; ');
            throw new RuntimeException(
                'Fail-closed scoring seat backfill: duplicate completed evaluations for one application+seat. '.$sample
            );
        }
    }

    private static function assertNoUnmappedCompletedScores(): void
    {
        $unmapped = EvaluationScore::query()
            ->whereNull('canonical_seat_no')
            ->whereNotNull('criterion_1')
            ->whereNotNull('criterion_2')
            ->whereNotNull('criterion_3')
            ->whereNotNull('criterion_4')
            ->whereNotNull('criterion_5')
            ->whereNotNull('criterion_6')
            ->whereNotNull('criterion_7')
            ->whereNotNull('criterion_8')
            ->whereNotNull('criterion_9')
            ->whereNotNull('criterion_10')
            ->pluck('id');

        if ($unmapped->isNotEmpty()) {
            throw new RuntimeException(
                'Fail-closed scoring seat backfill: completed scores without canonical_seat_no. score_ids='
                .$unmapped->implode(',')
            );
        }
    }
}
