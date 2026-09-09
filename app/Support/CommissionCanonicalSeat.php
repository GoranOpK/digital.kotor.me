<?php

namespace App\Support;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\EvaluationScore;

class CommissionCanonicalSeat
{
    public const SEATS = [1, 2, 3, 4, 5];

    /**
     * Infer canonical seat 1–5 using the same rules as AdminController::resolveMemberByReplacementSlot.
     *
     * Regular members are ordered by id. Substitutes use replaces_member_number.
     *
     * @return int|null seat 1–5, or null when the member cannot be mapped
     */
    public static function inferForMember(CommissionMember $member, bool $activeOnlyRegulars = false): ?int
    {
        if ($member->is_substitute) {
            $slot = (int) ($member->replaces_member_number ?? 0);

            return ($slot >= 1 && $slot <= 5) ? $slot : null;
        }

        $member->loadMissing('commission.members');
        $map = self::regularSeatMap($member->commission, $activeOnlyRegulars);

        return $map[$member->id] ?? null;
    }

    /**
     * @return array<int, int> commission_member_id => seat 1–5
     */
    public static function regularSeatMap(?Commission $commission, bool $activeOnlyRegulars = false): array
    {
        if ($commission === null) {
            return [];
        }

        $commission->loadMissing('members');

        $regulars = $commission->members
            ->filter(fn (CommissionMember $m) => ! $m->is_substitute)
            ->when($activeOnlyRegulars, fn ($col) => $col->where('status', 'active'))
            ->sortBy('id')
            ->values();

        $map = [];

        $president = $regulars->first(fn (CommissionMember $m) => $m->position === 'predsjednik');
        if ($president) {
            $map[$president->id] = 1;
        }

        $opstina = $regulars
            ->filter(fn (CommissionMember $m) => $m->position === 'clan' && $m->member_type === 'opstina')
            ->values();
        if (isset($opstina[0])) {
            $map[$opstina[0]->id] = 2;
        }
        if (isset($opstina[1])) {
            $map[$opstina[1]->id] = 3;
        }

        $udruzenje = $regulars->first(fn (CommissionMember $m) => $m->position === 'clan' && $m->member_type === 'udruzenje');
        if ($udruzenje) {
            $map[$udruzenje->id] = 4;
        }

        $zeneMreza = $regulars->first(fn (CommissionMember $m) => $m->position === 'clan' && $m->member_type === 'zene_mreza');
        if ($zeneMreza) {
            $map[$zeneMreza->id] = 5;
        }

        return $map;
    }

    public static function resolvePersistedOrInfer(CommissionMember $member): ?int
    {
        if ($member->canonical_seat_no !== null) {
            $seat = (int) $member->canonical_seat_no;

            return ($seat >= 1 && $seat <= 5) ? $seat : null;
        }

        return self::inferForMember($member, false);
    }

    public static function isHistoricallyCompleted(EvaluationScore $score): bool
    {
        for ($i = 1; $i <= 10; $i++) {
            if ($score->{"criterion_{$i}"} === null) {
                return false;
            }
        }

        return true;
    }

    public static function isPlaceholder(EvaluationScore $score): bool
    {
        for ($i = 1; $i <= 10; $i++) {
            if ($score->{"criterion_{$i}"} !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Legacy partial row: some but not all of the 10 criterion fields are set.
     * These rows are not placeholders and not completed finals.
     */
    public static function isPartial(EvaluationScore $score): bool
    {
        return ! self::isPlaceholder($score) && ! self::isHistoricallyCompleted($score);
    }

    /**
     * Legacy completed evaluations have no dedicated completion timestamp.
     * Do not invent now()/updated_at as completed_at.
     */
    public static function legacyCompletedAtForBackfill(EvaluationScore $score): mixed
    {
        return null;
    }

    /**
     * Map a historically completed row to a canonical seat without rewriting
     * criteria, notes, final_score, created_at, updated_at or completed_at.
     */
    public static function assignSeatToCompletedLegacyRow(EvaluationScore $score): void
    {
        if (! self::isHistoricallyCompleted($score)) {
            return;
        }

        $member = $score->commissionMember;
        if ($member === null) {
            throw new \RuntimeException(
                "Fail-closed scoring seat backfill: completed score {$score->id} has no commission member."
            );
        }

        $seat = self::resolvePersistedOrInfer($member);
        if ($seat === null) {
            throw new \RuntimeException(
                "Fail-closed scoring seat backfill: unmapped completed evaluation_score {$score->id}."
            );
        }

        \Illuminate\Support\Facades\DB::table('evaluation_scores')->where('id', $score->id)->update([
            'canonical_seat_no' => $seat,
        ]);
        $score->canonical_seat_no = $seat;
    }

    public static function persistForCommission(Commission $commission): void
    {
        $commission->loadMissing('members');
        $regularMap = self::regularSeatMap($commission, false);

        foreach ($commission->members as $member) {
            $seat = $member->is_substitute
                ? ((((int) $member->replaces_member_number >= 1) && (int) $member->replaces_member_number <= 5)
                    ? (int) $member->replaces_member_number
                    : null)
                : ($regularMap[$member->id] ?? null);

            if ($seat === null) {
                continue;
            }

            if ((int) $member->canonical_seat_no !== $seat) {
                $member->canonical_seat_no = $seat;
                $member->save();
            }
        }
    }
}
