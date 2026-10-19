<?php

namespace App\Services\Competitions\Commission;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\CommissionSessionAttendance;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\ProcessWriteResult;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Canonical sessions on the shared CommissionSession/attendance tables:
 * ordinary (first, 2/3), interview (second, 3/3), final decision (third, 3/3, due 7 calendar days
 * after the confirmed interview session; late is recordable with evidence).
 * Does not use the Youth first/second-session or oral-presentation workflow.
 */
class CanonicalCommissionSessionService
{
    public const CHAIR_ONLY_MESSAGE = 'Sjednicu kanonske Komisije evidentira i potvrđuje samo predsjednik spreman za radnje Komisije.';

    public const PURPOSE_INVALID_MESSAGE = 'Vrsta kanonske sjednice nije dozvoljena.';

    public const SESSION_EXISTS_MESSAGE = 'Ova vrsta sjednice za Poziv je već evidentirana.';

    public const ATTENDANCE_MODE_INVALID_MESSAGE = 'Način prisustva nije dozvoljen (lično, daljinski, elektronski).';

    public const INTERVIEW_BEFORE_FINAL_MESSAGE = 'Treća sjednica zahtijeva prethodno potvrđenu sjednicu intervjua.';

    public const RANKING_BEFORE_FINAL_MESSAGE = 'Treća sjednica zahtijeva formiranu i zaključanu preliminarnu rang-listu (završeni intervjui i bodovanje).';

    public const SESSION_LOCKED_MESSAGE = 'Potvrđena sjednica i prisustvo ne mogu se mijenjati.';

    public const LATE_THIRD_SESSION_WARNING = 'Treća sjednica održana je nakon roka od 7 dana. Stvarni datum sjednice je sačuvan.';

    public const STALE_ATTENDANCE_MESSAGE = 'Prisustvo sjednice uključuje člana koji više nije važeći (zamijenjen ili razriješen). Ažurirajte prisustvo trenutnih članova prije potvrde.';

    public const SESSION_COMMISSION_MISMATCH_MESSAGE = 'Sjednica nije evidentirana za Komisiju dodijeljenu ovom Pozivu. Potvrda nije moguća.';

    public const ATTENDANCE_MEMBER_INVALID_MESSAGE = 'Prisustvo se evidentira samo za trenutne članove kanonske Komisije ovog Poziva. Zamijenjeni, razriješeni i članovi druge Komisije nijesu dozvoljeni.';

    /** @var array<string, string> */
    public const PURPOSE_LABELS = [
        CanonicalCommissionGovernance::PURPOSE_ORDINARY => 'Redovna sjednica',
        CanonicalCommissionGovernance::PURPOSE_INTERVIEW => 'Sjednica intervjua',
        CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION => 'Treća sjednica – konačna rang-lista i predlog',
    ];

    /** @var array<string, string> */
    public const ATTENDANCE_MODE_LABELS = [
        CanonicalCommissionGovernance::ATTENDANCE_IN_PERSON => 'Lično',
        CanonicalCommissionGovernance::ATTENDANCE_REMOTE => 'Daljinski',
        CanonicalCommissionGovernance::ATTENDANCE_ELECTRONIC => 'Elektronski',
    ];

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalAdministrativeGate $gate,
    ) {
    }

    /**
     * @param  array<int, string|null>  $attendance  commission_member_id => attendance mode, null = absent
     */
    public function record(
        Competition $competition,
        User $actor,
        string $purpose,
        \DateTimeInterface $heldAt,
        array $attendance,
        ?string $notes = null,
    ): ProcessWriteResult {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
        }
        if (! array_key_exists($purpose, CanonicalCommissionGovernance::PURPOSE_SESSION_TYPES)) {
            return ProcessWriteResult::failed(self::PURPOSE_INVALID_MESSAGE);
        }
        foreach ($attendance as $mode) {
            if ($mode !== null && ! in_array($mode, CanonicalCommissionGovernance::ATTENDANCE_MODES, true)) {
                return ProcessWriteResult::failed(self::ATTENDANCE_MODE_INVALID_MESSAGE);
            }
        }

        $chairReason = $this->chairBlockReason($actor, $competition);
        if ($chairReason !== null) {
            return ProcessWriteResult::failed($chairReason);
        }
        if (! $this->onlyCurrentMembers($competition, array_keys($attendance))) {
            return ProcessWriteResult::failed(self::ATTENDANCE_MEMBER_INVALID_MESSAGE);
        }

        $policy = $this->governance->policy();
        $held = CarbonImmutable::parse($heldAt)->timezone($policy->publicationTimezone());
        $dueAt = null;
        $late = false;
        $warning = null;

        if ($purpose === CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION) {
            $interview = CommissionSession::query()
                ->where('competition_id', $competition->id)
                ->where('session_type', CommissionSession::TYPE_SECOND)
                ->where('canonical_purpose', CanonicalCommissionGovernance::PURPOSE_INTERVIEW)
                ->first();
            if ($interview === null || ! $interview->isConfirmed()) {
                return ProcessWriteResult::failed(self::INTERVIEW_BEFORE_FINAL_MESSAGE);
            }
            if (! $this->governance->canonicalRankingFormed($competition)) {
                return ProcessWriteResult::failed(self::RANKING_BEFORE_FINAL_MESSAGE);
            }

            $dueAt = $policy->thirdSessionDueAt($interview->completed_at);
            $late = $dueAt !== null && $held->greaterThan($dueAt);
            $warning = $late ? self::LATE_THIRD_SESSION_WARNING : null;
        }

        try {
            return DB::transaction(function () use ($competition, $actor, $purpose, $held, $attendance, $notes, $dueAt, $late, $warning) {
                $session = CommissionSession::query()->create([
                    'competition_id' => $competition->id,
                    'commission_id' => $competition->commission_id,
                    'session_type' => CanonicalCommissionGovernance::PURPOSE_SESSION_TYPES[$purpose],
                    'canonical_purpose' => $purpose,
                    'held_at' => $held,
                    'due_at' => $dueAt,
                    'late' => $late,
                    'compliance_warning' => $warning,
                    'recorded_by_user_id' => $actor->id,
                    'notes' => $notes,
                ]);

                $commission = Commission::query()->findOrFail($competition->commission_id);
                foreach ($this->governance->activeMembers($commission) as $member) {
                    $mode = $attendance[$member->id] ?? null;
                    CommissionSessionAttendance::query()->create([
                        'commission_session_id' => $session->id,
                        'commission_member_id' => $member->id,
                        'present' => $mode !== null,
                        'attendance_mode' => $mode,
                    ]);
                }

                return ProcessWriteResult::ok($session->id, $warning);
            });
        } catch (UniqueConstraintViolationException) {
            return ProcessWriteResult::failed(self::SESSION_EXISTS_MESSAGE);
        }
    }

    public function confirm(CommissionSession $session, User $actor): ProcessWriteResult
    {
        $competition = $session->competition()->firstOrFail();
        if (! $this->governance->isCanonicalCompetition($competition) || $session->canonical_purpose === null) {
            return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
        }

        $chairReason = $this->chairBlockReason($actor, $competition);
        if ($chairReason !== null) {
            return ProcessWriteResult::failed($chairReason);
        }

        return DB::transaction(function () use ($session, $competition, $actor) {
            $locked = CommissionSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->isConfirmed()) {
                return ProcessWriteResult::failed(self::SESSION_LOCKED_MESSAGE);
            }

            $lockedCompetition = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            if ((int) $locked->competition_id !== (int) $lockedCompetition->id
                || (int) $locked->commission_id !== (int) $lockedCompetition->commission_id) {
                return ProcessWriteResult::failed(self::SESSION_COMMISSION_MISMATCH_MESSAGE);
            }

            // Quorum below counts only active members; a present row of a vacated member would still
            // enter the historical (confirmed) attendance, so it must be cleared before confirmation.
            if ($this->nonCurrentPresentAttendance($locked)->exists()) {
                return ProcessWriteResult::failed(self::STALE_ATTENDANCE_MESSAGE);
            }

            $reason = $this->governance->sessionQuorumBlockReason($locked);
            if ($reason !== null) {
                return ProcessWriteResult::failed($reason);
            }

            $locked->update(['completed_at' => now()]);

            $present = $this->governance->validPresentMembers($locked->fresh());
            $this->gate->audit($competition, CanonicalAdministrativeEvent::COMMISSION_SESSION_CONFIRMED, $actor, $locked, [
                'purpose' => $locked->canonical_purpose,
                'held_at' => $locked->held_at?->toIso8601String(),
                'due_at' => $locked->due_at?->toIso8601String(),
                'late' => (bool) $locked->late,
                'present_member_ids' => $present->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'present_seat_nos' => $present->pluck('canonical_seat_no')->map(fn ($seat) => (int) $seat)->all(),
            ]);

            return ProcessWriteResult::ok($locked->id, $locked->compliance_warning);
        });
    }

    /**
     * Amends attendance of a recorded, not yet confirmed session (e.g. a seat filled by a replacement
     * after recording). Only current members; a confirmed session is locked.
     *
     * @param  array<int, string|null>  $attendance  commission_member_id => attendance mode, null = absent
     */
    public function updateAttendance(CommissionSession $session, User $actor, array $attendance): ProcessWriteResult
    {
        $competition = $session->competition()->firstOrFail();
        if (! $this->governance->isCanonicalCompetition($competition) || $session->canonical_purpose === null) {
            return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
        }
        foreach ($attendance as $mode) {
            if ($mode !== null && ! in_array($mode, CanonicalCommissionGovernance::ATTENDANCE_MODES, true)) {
                return ProcessWriteResult::failed(self::ATTENDANCE_MODE_INVALID_MESSAGE);
            }
        }

        $chairReason = $this->chairBlockReason($actor, $competition);
        if ($chairReason !== null) {
            return ProcessWriteResult::failed($chairReason);
        }
        if (! $this->onlyCurrentMembers($competition, array_keys($attendance))) {
            return ProcessWriteResult::failed(self::ATTENDANCE_MEMBER_INVALID_MESSAGE);
        }

        return DB::transaction(function () use ($session, $competition, $actor, $attendance) {
            $locked = CommissionSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->isConfirmed()) {
                return ProcessWriteResult::failed(self::SESSION_LOCKED_MESSAGE);
            }

            $commission = Commission::query()->findOrFail($locked->commission_id);
            foreach ($this->governance->activeMembers($commission) as $member) {
                $key = ['commission_session_id' => $locked->id, 'commission_member_id' => $member->id];
                if (array_key_exists($member->id, $attendance)) {
                    $mode = $attendance[$member->id];
                    CommissionSessionAttendance::query()->updateOrCreate($key, ['present' => $mode !== null, 'attendance_mode' => $mode]);
                } else {
                    CommissionSessionAttendance::query()->firstOrCreate($key, ['present' => false, 'attendance_mode' => null]);
                }
            }

            $stale = $this->nonCurrentPresentAttendance($locked)->get();
            foreach ($stale as $row) {
                $row->update(['present' => false]);
            }

            $this->gate->audit($competition, CanonicalAdministrativeEvent::COMMISSION_SESSION_ATTENDANCE_UPDATED, $actor, $locked, [
                'purpose' => $locked->canonical_purpose,
                'attendance' => $attendance,
                'no_longer_counted_member_ids' => $stale->pluck('commission_member_id')->map(fn ($id) => (int) $id)->all(),
            ]);

            return ProcessWriteResult::ok($locked->id, $locked->compliance_warning);
        });
    }

    /**
     * Read model of the canonical session stage for one Poziv.
     *
     * @return array<string, mixed>
     */
    public function overview(Competition $competition, ?User $viewer): array
    {
        $policy = $this->governance->policy();
        $commission = $competition->commission_id ? Commission::query()->find($competition->commission_id) : null;
        $current = $commission !== null ? $this->governance->activeMembers($commission) : collect();

        $seats = [];
        $config = $this->governance->commissionConfig();
        foreach (range(1, $config->seatCount) as $seatNo) {
            $seats[$seatNo] = $current->first(fn (CommissionMember $member) => (int) $member->canonical_seat_no === $seatNo);
        }

        $sessions = CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('canonical_purpose')
            ->with(['attendances.member', 'recordedBy'])
            ->get()
            ->keyBy('canonical_purpose');

        $confirmations = CanonicalAdministrativeEvent::query()
            ->where('competition_id', $competition->id)
            ->where('action', CanonicalAdministrativeEvent::COMMISSION_SESSION_CONFIRMED)
            ->get()
            ->keyBy('subject_id');
        $confirmers = User::query()->whereIn('id', $confirmations->pluck('actor_user_id'))->pluck('name', 'id');

        $stages = [];
        foreach (array_keys(CanonicalCommissionGovernance::PURPOSE_SESSION_TYPES) as $purpose) {
            $session = $sessions->get($purpose);
            $present = $session !== null ? $this->governance->validPresentMembers($session) : collect();
            $presentSeats = $present->pluck('canonical_seat_no')->map(fn ($seat) => (int) $seat)->all();
            $confirmation = $session !== null ? $confirmations->get($session->id) : null;

            $stages[$purpose] = [
                'label' => self::PURPOSE_LABELS[$purpose],
                'required' => $purpose === CanonicalCommissionGovernance::PURPOSE_ORDINARY
                    ? (int) $config->firstSessionQuorum
                    : $config->allMembersRequiredCount,
                'session' => $session,
                'presentCount' => $present->count(),
                'missingSeats' => array_values(array_diff(array_keys($seats), $presentSeats)),
                'quorumBlock' => $session !== null && ! $session->isConfirmed() ? $this->governance->sessionQuorumBlockReason($session) : null,
                'prerequisiteBlock' => $session === null ? $this->prerequisiteBlockReason($competition, $purpose) : null,
                'confirmedBy' => $confirmation !== null ? ($confirmers[$confirmation->actor_user_id] ?? null) : null,
            ];
        }

        $interview = $sessions->get(CanonicalCommissionGovernance::PURPOSE_INTERVIEW);
        $finalDueAt = $interview?->isConfirmed() ? $policy->thirdSessionDueAt($interview->completed_at) : null;
        $now = CarbonImmutable::now($policy->publicationTimezone());

        return [
            'commission' => $commission,
            'seats' => $seats,
            'stages' => $stages,
            'finalDueAt' => $finalDueAt,
            'finalOverdueNow' => $finalDueAt !== null
                && $sessions->get(CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION) === null
                && $now->greaterThan($finalDueAt),
            'actionBlock' => $viewer !== null ? $this->chairBlockReason($viewer, $competition) : self::CHAIR_ONLY_MESSAGE,
            'attendanceModes' => self::ATTENDANCE_MODE_LABELS,
            'timezone' => $policy->publicationTimezone(),
        ];
    }

    private function prerequisiteBlockReason(Competition $competition, string $purpose): ?string
    {
        if ($purpose !== CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION) {
            return null;
        }

        $interview = CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('session_type', CommissionSession::TYPE_SECOND)
            ->where('canonical_purpose', CanonicalCommissionGovernance::PURPOSE_INTERVIEW)
            ->first();
        if ($interview === null || ! $interview->isConfirmed()) {
            return self::INTERVIEW_BEFORE_FINAL_MESSAGE;
        }

        return $this->governance->canonicalRankingFormed($competition) ? null : self::RANKING_BEFORE_FINAL_MESSAGE;
    }

    /**
     * Present rows of a draft session whose member is no longer an active member of the session's Commission.
     */
    private function nonCurrentPresentAttendance(CommissionSession $session): \Illuminate\Database\Eloquent\Builder
    {
        return CommissionSessionAttendance::query()
            ->where('commission_session_id', $session->id)
            ->where('present', true)
            ->whereNotIn('commission_member_id', $this->currentMemberIds($session->commission_id));
    }

    /**
     * @return list<int>
     */
    private function currentMemberIds(?int $commissionId): array
    {
        $commission = $commissionId !== null ? Commission::query()->find($commissionId) : null;
        if ($commission === null) {
            return [];
        }

        return $this->governance->activeMembers($commission)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<int|string>  $memberIds
     */
    private function onlyCurrentMembers(Competition $competition, array $memberIds): bool
    {
        $current = $this->currentMemberIds($competition->commission_id);

        foreach ($memberIds as $id) {
            if (! in_array((int) $id, $current, true)) {
                return false;
            }
        }

        return true;
    }

    private function chairBlockReason(User $actor, Competition $competition): ?string
    {
        $member = $this->governance->activeMemberFor($actor, $competition);
        if ($member === null || $member->canonical_seat !== CanonicalCommissionGovernance::ROLE_PREDSJEDNIK) {
            return self::CHAIR_ONLY_MESSAGE;
        }

        return $this->governance->memberActionBlockReason($member, $competition);
    }
}
