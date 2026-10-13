<?php

namespace App\Services\Competitions\Commission;

use App\Models\Commission;
use App\Models\CommissionGovernanceConflict;
use App\Models\CommissionMember;
use App\Models\CommissionMemberReplacement;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\ProcessWriteResult;
use App\Support\Pib;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records evidence of canonical Commission appointments made outside the platform
 * (the Secretary appoints; the platform does not run the legal appointment workflow).
 */
class CanonicalCommissionMembershipService
{
    public const INVALID_PIB_MESSAGE = 'PIB privrednog društva koje član predstavlja nije ispravan (8 cifara).';

    public const VACANCY_EXISTS_MESSAGE = 'Upražnjenost ovog mjesta je već evidentirana.';

    public const VACANCY_REQUIRED_MESSAGE = 'Zamjena zahtijeva prethodno evidentiranu upražnjenost mjesta sa stvarnim datumom nastanka.';

    public const LATE_REPLACEMENT_WARNING = 'Zamjena je evidentirana nakon roka od 15 dana od nastanka upražnjenosti. Stvarni datum zamjene je sačuvan.';

    /** @var list<string> */
    public const VACANCY_REASONS = ['resigned', 'dismissed', 'inactive'];

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
    ) {
    }

    public function establish(
        string $name,
        \DateTimeInterface $mandateStartsAt,
        string $appointmentReference,
        ?\DateTimeInterface $appointedAt = null,
    ): Commission {
        $policy = $this->governance->policy();
        $start = CarbonImmutable::parse($mandateStartsAt)->timezone($policy->publicationTimezone());
        $end = $policy->commissionMandateEndsAt($start);

        return Commission::query()->create([
            'name' => $name,
            'year' => (int) $start->year,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'active',
            'profile_type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'appointment_reference' => $appointmentReference,
            'appointed_at' => $appointedAt ?? $start,
            'mandate_starts_at' => $start,
            'mandate_ends_at' => $end,
        ]);
    }

    public function appoint(
        Commission $commission,
        User $user,
        int $seatNo,
        string $role,
        ?string $externalCategory,
        string $appointmentReference,
        \DateTimeInterface $appointedAt,
        User $actor,
        ?string $representedCompanyPib = null,
    ): ProcessWriteResult {
        try {
            return DB::transaction(fn () => $this->createMemberLocked(
                $commission, $user, $seatNo, $role, $externalCategory, $appointmentReference,
                $appointedAt, $actor, $representedCompanyPib, null,
            )[0]);
        } catch (UniqueConstraintViolationException $exception) {
            return ProcessWriteResult::failed($this->uniqueViolationMessage($exception));
        }
    }

    public function vacate(CommissionMember $member, string $reason, \DateTimeInterface $triggeredAt, User $actor): ProcessWriteResult
    {
        if (! in_array($reason, self::VACANCY_REASONS, true)) {
            return ProcessWriteResult::failed('Razlog upražnjenosti nije dozvoljen.');
        }

        return DB::transaction(function () use ($member, $reason, $triggeredAt, $actor) {
            $locked = CommissionMember::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            $commission = Commission::query()->whereKey($locked->commission_id)->lockForUpdate()->firstOrFail();

            if (! $commission->isCanonicalProfile()) {
                return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
            }
            if ($locked->status !== 'active' || $locked->replaced_at !== null) {
                return ProcessWriteResult::failed(CanonicalCommissionGovernance::MEMBER_INACTIVE_MESSAGE);
            }
            if (CommissionMemberReplacement::query()->where('vacated_member_id', $locked->id)->exists()) {
                return ProcessWriteResult::failed(self::VACANCY_EXISTS_MESSAGE);
            }

            $policy = $this->governance->policy();
            $triggered = CarbonImmutable::parse($triggeredAt)->timezone($policy->publicationTimezone());

            $locked->update([
                'status' => $reason,
                'canonical_active_user_id' => null,
                'canonical_active_seat' => null,
                'canonical_active_seat_no' => null,
            ]);

            $vacancy = CommissionMemberReplacement::query()->create([
                'commission_id' => $commission->id,
                'seat_no' => $locked->canonical_seat_no,
                'canonical_seat' => $locked->canonical_seat,
                'vacated_member_id' => $locked->id,
                'vacancy_reason' => $reason,
                'vacancy_triggered_at' => $triggered,
                'due_at' => $policy->commissionReplacementDueAt($triggered),
                'recorded_by_user_id' => $actor->id,
            ]);

            return ProcessWriteResult::ok($vacancy->id);
        });
    }

    /**
     * Fills a vacated seat. The replacement inherits structural seat and semantic role prospectively;
     * the vacated member's history (scores, attendance) stays attached to the vacated row.
     */
    public function replace(
        CommissionMember $vacated,
        User $user,
        string $appointmentReference,
        \DateTimeInterface $appointedAt,
        User $actor,
        ?string $externalCategory = null,
        ?string $representedCompanyPib = null,
    ): ProcessWriteResult {
        try {
            return DB::transaction(function () use ($vacated, $user, $appointmentReference, $appointedAt, $actor, $externalCategory, $representedCompanyPib) {
                $lockedVacated = CommissionMember::query()->whereKey($vacated->id)->lockForUpdate()->firstOrFail();
                $vacancy = CommissionMemberReplacement::query()
                    ->where('vacated_member_id', $lockedVacated->id)
                    ->whereNull('replacement_member_id')
                    ->lockForUpdate()
                    ->first();

                if ($vacancy === null || $lockedVacated->status === 'active' || $lockedVacated->replaced_at !== null) {
                    return ProcessWriteResult::failed(self::VACANCY_REQUIRED_MESSAGE);
                }

                $category = $lockedVacated->canonical_seat === CanonicalCommissionGovernance::ROLE_SPOLJASNJI
                    ? ($externalCategory ?? $lockedVacated->external_category)
                    : null;

                [$result, $member] = $this->createMemberLocked(
                    $lockedVacated->commission()->firstOrFail(),
                    $user,
                    (int) $lockedVacated->canonical_seat_no,
                    (string) $lockedVacated->canonical_seat,
                    $category,
                    $appointmentReference,
                    $appointedAt,
                    $actor,
                    $representedCompanyPib,
                    $lockedVacated,
                );

                if (! $result->succeeded) {
                    return $result;
                }

                $policy = $this->governance->policy();
                $now = CarbonImmutable::now($policy->publicationTimezone());
                $due = $policy->commissionReplacementDueAt($vacancy->vacancy_triggered_at);
                $late = $due !== null && $now->greaterThan($due);
                $warning = $late ? self::LATE_REPLACEMENT_WARNING : null;

                $lockedVacated->update([
                    'replaced_at' => $now,
                    'replaced_by_member_id' => $member->id,
                ]);

                $vacancy->update([
                    'replacement_member_id' => $member->id,
                    'replaced_at' => $now,
                    'late' => $late,
                    'compliance_warning' => $warning,
                ]);

                return ProcessWriteResult::ok($member->id, $warning);
            });
        } catch (UniqueConstraintViolationException $exception) {
            return ProcessWriteResult::failed($this->uniqueViolationMessage($exception));
        }
    }

    /**
     * Persists represented-company PIB conflict evidence for active members (idempotent).
     */
    public function recordPibConflicts(Commission $commission, User $actor): int
    {
        $recorded = 0;

        foreach ($this->governance->activeMembers($commission) as $member) {
            foreach ($this->governance->pibCollisions($commission, $member->represented_company_pib) as $collision) {
                $conflict = CommissionGovernanceConflict::query()->firstOrCreate(
                    [
                        'commission_member_id' => $member->id,
                        'application_id' => $collision['application_id'],
                        'conflict_type' => CommissionGovernanceConflict::TYPE_REPRESENTED_COMPANY_PIB,
                    ],
                    [
                        'commission_id' => $commission->id,
                        'user_id' => $member->user_id,
                        'competition_id' => $collision['competition_id'],
                        'normalized_value' => $collision['normalized'],
                        'reason' => CanonicalCommissionGovernance::PIB_CONFLICT_MESSAGE,
                        'detected_at' => now(),
                        'detected_by_user_id' => $actor->id,
                    ],
                );
                $recorded += $conflict->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $recorded;
    }

    /**
     * @return array{0: ProcessWriteResult, 1: ?CommissionMember}
     */
    private function createMemberLocked(
        Commission $commission,
        User $user,
        int $seatNo,
        string $role,
        ?string $externalCategory,
        string $appointmentReference,
        \DateTimeInterface $appointedAt,
        User $actor,
        ?string $representedCompanyPib,
        ?CommissionMember $replacing,
    ): array {
        $locked = Commission::query()->whereKey($commission->id)->lockForUpdate()->firstOrFail();

        if (! $locked->isCanonicalProfile()) {
            return [ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE), null];
        }

        if (! in_array($seatNo, [1, 2, 3], true) || ! in_array($role, CanonicalCommissionGovernance::ROLES, true)) {
            return [ProcessWriteResult::failed(CanonicalCommissionGovernance::SEAT_INVALID_MESSAGE), null];
        }

        $categoryReason = $this->governance->externalCategoryBlockReason($role, $externalCategory);
        if ($categoryReason !== null) {
            return [ProcessWriteResult::failed($categoryReason), null];
        }

        $active = CommissionMember::query()
            ->where('commission_id', $locked->id)
            ->where('status', 'active')
            ->whereNull('replaced_at')
            ->lockForUpdate()
            ->get();

        if ($active->count() >= (int) $this->governance->policy()->commissionSize()) {
            return [ProcessWriteResult::failed(CanonicalCommissionGovernance::FOURTH_SEAT_MESSAGE), null];
        }

        if ($active->contains(fn (CommissionMember $member) => (int) $member->user_id === (int) $user->id)) {
            return [ProcessWriteResult::failed(CanonicalCommissionGovernance::SAME_USER_MESSAGE), null];
        }

        if ($active->contains(fn (CommissionMember $member) => (int) $member->canonical_seat_no === $seatNo
            || $member->canonical_seat === $role)) {
            return [ProcessWriteResult::failed(CanonicalCommissionGovernance::SEAT_DUPLICATE_MESSAGE), null];
        }

        $normalizedPib = null;
        if ($representedCompanyPib !== null && trim($representedCompanyPib) !== '') {
            $normalizedPib = Pib::normalize($representedCompanyPib);
            if ($normalizedPib === null) {
                return [ProcessWriteResult::failed(self::INVALID_PIB_MESSAGE), null];
            }
        }

        $collisions = $this->governance->pibCollisions($locked, $normalizedPib);
        if ($collisions->isNotEmpty()) {
            foreach ($collisions as $collision) {
                CommissionGovernanceConflict::query()->create([
                    'commission_id' => $locked->id,
                    'user_id' => $user->id,
                    'commission_member_id' => null,
                    'competition_id' => $collision['competition_id'],
                    'application_id' => $collision['application_id'],
                    'conflict_type' => CommissionGovernanceConflict::TYPE_REPRESENTED_COMPANY_PIB,
                    'normalized_value' => $collision['normalized'],
                    'reason' => CanonicalCommissionGovernance::PIB_CONFLICT_MESSAGE,
                    'detected_at' => now(),
                    'detected_by_user_id' => $actor->id,
                ]);
            }

            return [ProcessWriteResult::failed(CanonicalCommissionGovernance::PIB_CONFLICT_MESSAGE), null];
        }

        $member = CommissionMember::query()->create([
            'commission_id' => $locked->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $role === CanonicalCommissionGovernance::ROLE_PREDSJEDNIK ? 'predsjednik' : 'clan',
            'member_type' => null,
            'is_substitute' => $replacing !== null,
            'replaces_member_number' => $replacing !== null ? $seatNo : null,
            'canonical_seat_no' => $seatNo,
            'canonical_seat' => $role,
            'external_category' => $externalCategory,
            'represented_company_pib' => $normalizedPib,
            'appointment_reference' => $appointmentReference,
            'appointed_at' => $appointedAt,
            'status' => 'active',
            'canonical_active_user_id' => $user->id,
            'canonical_active_seat' => $role,
            'canonical_active_seat_no' => $seatNo,
        ]);

        return [ProcessWriteResult::ok($member->id), $member];
    }

    private function uniqueViolationMessage(UniqueConstraintViolationException $exception): string
    {
        return str_contains($exception->getMessage(), 'commission_members_canonical_active_user_unique')
            ? CanonicalCommissionGovernance::SAME_USER_MESSAGE
            : CanonicalCommissionGovernance::SEAT_DUPLICATE_MESSAGE;
    }
}
