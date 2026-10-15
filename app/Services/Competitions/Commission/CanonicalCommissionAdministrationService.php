<?php

namespace App\Services\Competitions\Commission;

use App\Mail\CommissionAppointmentMailCopy;
use App\Mail\CommissionMemberAdded;
use App\Models\Application;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\Commission;
use App\Models\CommissionGovernanceConflict;
use App\Models\CommissionMember;
use App\Models\CommissionMemberReplacement;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\ProcessWriteResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Admin-side orchestration of canonical Commission governance for one canonical Poziv.
 * Membership mutations stay in CanonicalCommissionMembershipService; this layer adds
 * Poziv scoping, Poziv state, mandate, member eligibility, write-once declarations and mail.
 */
class CanonicalCommissionAdministrationService
{
    public const NOT_CANONICAL_CALL_MESSAGE = 'Poziv nije kanonski MMSP Poziv.';

    public const CALL_CLOSED_MESSAGE = 'Poziv je zatvoren. Sastav Komisije se više ne mijenja.';

    public const ALREADY_ASSIGNED_MESSAGE = 'Poziv već ima dodijeljenu Komisiju. Nova Komisija se ne uspostavlja za isti Poziv.';

    public const MEMBER_SCOPE_MESSAGE = 'Član ne pripada Komisiji ovog Poziva.';

    public const USER_NOT_FOUND_MESSAGE = 'Korisnik sa ovom e-mail adresom ne postoji na platformi. Nalog člana Komisije mora postojati prije evidentiranja imenovanja.';

    public const USER_ROLE_MESSAGE = 'Član kanonske Komisije mora imati aktivan nalog sa ulogom Komisija.';

    public const APPLICANT_MESSAGE = 'Korisnik ima prijavu na Poziv ove Komisije. Član Komisije nema pravo učešća u Javnom pozivu.';

    public const FUTURE_DATE_MESSAGE = 'Datum ne može biti u budućnosti.';

    public const APPOINTMENT_OUTSIDE_MANDATE_MESSAGE = 'Datum imenovanja mora biti unutar mandata Komisije.';

    public const MANDATE_ALREADY_EXPIRED_MESSAGE = 'Mandat sa ovim datumom početka je već istekao. Komisija se ne može uspostaviti.';

    public const DATE_BEFORE_APPOINTMENT_MESSAGE = 'Datum ne može biti prije datuma imenovanja člana.';

    public const DECLARATION_IMMUTABLE_MESSAGE = 'Izjave ovog člana su već evidentirane i ne mogu se mijenjati ni dopunjavati.';

    public const DECLARATION_CONFIDENTIALITY_TEXT = 'Član Komisije je potpisao izjavu o povjerljivosti podataka iz postupka javnog konkursa.';

    public const DECLARATION_CONFLICT_TEXT = 'Član Komisije je potpisao izjavu o nepostojanju sukoba interesa u postupku javnog konkursa.';

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalCommissionMembershipService $membership,
        private readonly CanonicalAdministrativeGate $gate,
    ) {
    }

    public function establish(
        Competition $competition,
        User $actor,
        string $name,
        string $mandateStartsOn,
        string $appointmentReference,
        string $appointedOn,
    ): ProcessWriteResult {
        $reason = $this->callBlockReason($competition);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        $start = $this->day($mandateStartsOn);
        $appointedAt = $this->day($appointedOn);
        if ($appointedAt->greaterThan($this->now())) {
            return ProcessWriteResult::failed(self::FUTURE_DATE_MESSAGE);
        }
        $end = $this->governance->policy()->commissionMandateEndsAt($start);
        if ($end === null || $end->lessThan($this->now())) {
            return ProcessWriteResult::failed(self::MANDATE_ALREADY_EXPIRED_MESSAGE);
        }

        return DB::transaction(function () use ($competition, $actor, $name, $start, $appointmentReference, $appointedAt) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            if ($locked->commission_id !== null) {
                return ProcessWriteResult::failed(self::ALREADY_ASSIGNED_MESSAGE);
            }

            $commission = $this->membership->establish($name, $start, $appointmentReference, $appointedAt);
            $locked->forceFill(['commission_id' => $commission->id])->save();

            $this->gate->audit($locked, CanonicalAdministrativeEvent::COMMISSION_ESTABLISHED, $actor, $commission, [
                'commission_id' => $commission->id,
                'appointment_reference' => $appointmentReference,
                'mandate_starts_at' => $commission->mandate_starts_at?->toIso8601String(),
                'mandate_ends_at' => $commission->mandate_ends_at?->toIso8601String(),
            ]);

            return ProcessWriteResult::ok($commission->id);
        });
    }

    public function appoint(
        Competition $competition,
        User $actor,
        string $email,
        int $seatNo,
        string $role,
        ?string $externalCategory,
        string $appointmentReference,
        string $appointedOn,
        ?string $representedCompanyPib,
    ): ProcessWriteResult {
        [$commission, $reason] = $this->commissionFor($competition);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        $appointedAt = $this->day($appointedOn);
        $reason = $this->appointmentDateBlockReason($commission, $appointedAt);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        [$user, $reason] = $this->eligibleUser($commission, $email);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        $result = DB::transaction(function () use ($competition, $commission, $user, $seatNo, $role, $externalCategory, $appointmentReference, $appointedAt, $actor, $representedCompanyPib) {
            $result = $this->membership->appoint(
                $commission, $user, $seatNo, $role, $externalCategory, $appointmentReference,
                $appointedAt, $actor, $representedCompanyPib,
            );

            if ($result->succeeded) {
                $this->auditMember($competition, CanonicalAdministrativeEvent::COMMISSION_APPOINTMENT_RECORDED, $actor,
                    CommissionMember::query()->findOrFail($result->recordId));
            } elseif ($result->error === CanonicalCommissionGovernance::PIB_CONFLICT_MESSAGE) {
                $this->gate->audit($competition, CanonicalAdministrativeEvent::COMMISSION_APPOINTMENT_BLOCKED, $actor, $commission, [
                    'candidate_user_id' => $user->id,
                    'seat_no' => $seatNo,
                    'role' => $role,
                    'reason' => $result->error,
                ]);
            }

            return $result;
        });

        if ($result->succeeded) {
            $this->sendAppointmentMail(CommissionMember::query()->findOrFail($result->recordId), $competition, null);
        }

        return $result;
    }

    public function vacate(
        Competition $competition,
        CommissionMember $member,
        User $actor,
        string $reason,
        string $triggeredAt,
    ): ProcessWriteResult {
        $block = $this->memberScopeBlockReason($competition, $member);
        if ($block !== null) {
            return ProcessWriteResult::failed($block);
        }

        $triggered = $this->moment($triggeredAt);
        $block = $this->notFutureNorBeforeAppointment($member, $triggered);
        if ($block !== null) {
            return ProcessWriteResult::failed($block);
        }

        return DB::transaction(function () use ($competition, $member, $reason, $triggered, $actor) {
            $result = $this->membership->vacate($member, $reason, $triggered, $actor);
            if ($result->succeeded) {
                $vacancy = CommissionMemberReplacement::query()->findOrFail($result->recordId);
                $this->auditMember($competition, CanonicalAdministrativeEvent::COMMISSION_MEMBER_VACATED, $actor, $member->fresh(), [
                    'vacancy_reason' => $reason,
                    'vacancy_triggered_at' => $vacancy->vacancy_triggered_at->toIso8601String(),
                    'replacement_due_at' => $vacancy->due_at->toIso8601String(),
                ]);
            }

            return $result;
        });
    }

    public function replace(
        Competition $competition,
        CommissionMember $vacated,
        User $actor,
        string $email,
        string $appointmentReference,
        string $appointedOn,
        ?string $externalCategory,
        ?string $representedCompanyPib,
    ): ProcessWriteResult {
        $block = $this->memberScopeBlockReason($competition, $vacated);
        if ($block !== null) {
            return ProcessWriteResult::failed($block);
        }

        $commission = $vacated->commission()->firstOrFail();
        $appointedAt = $this->day($appointedOn);
        $block = $this->appointmentDateBlockReason($commission, $appointedAt);
        if ($block !== null) {
            return ProcessWriteResult::failed($block);
        }

        [$user, $block] = $this->eligibleUser($commission, $email);
        if ($block !== null) {
            return ProcessWriteResult::failed($block);
        }

        $result = DB::transaction(function () use ($competition, $vacated, $user, $appointmentReference, $appointedAt, $actor, $externalCategory, $representedCompanyPib) {
            $result = $this->membership->replace(
                $vacated, $user, $appointmentReference, $appointedAt, $actor, $externalCategory, $representedCompanyPib,
            );
            if ($result->succeeded) {
                $vacancy = CommissionMemberReplacement::query()->where('vacated_member_id', $vacated->id)->firstOrFail();
                $this->auditMember($competition, CanonicalAdministrativeEvent::COMMISSION_MEMBER_REPLACED, $actor,
                    CommissionMember::query()->findOrFail($result->recordId), [
                        'vacated_member_id' => $vacated->id,
                        'replacement_due_at' => $vacancy->due_at->toIso8601String(),
                        'late' => (bool) $vacancy->late,
                    ]);
            }

            return $result;
        });

        if ($result->succeeded) {
            $this->sendAppointmentMail(CommissionMember::query()->findOrFail($result->recordId), $competition, $vacated->fresh());
        }

        return $result;
    }

    /**
     * Records evidence of the two signed declarations for the member's own assignment row. Write-once:
     * amendment is not supported, so a second write fails closed; a replaced member keeps its record.
     */
    public function recordDeclarations(
        Competition $competition,
        CommissionMember $member,
        User $actor,
        string $signedOn,
    ): ProcessWriteResult {
        $block = $this->memberScopeBlockReason($competition, $member);
        if ($block !== null) {
            return ProcessWriteResult::failed($block);
        }

        $signedAt = $this->day($signedOn);

        return DB::transaction(function () use ($competition, $member, $actor, $signedAt) {
            $locked = CommissionMember::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'active' || $locked->replaced_at !== null) {
                return ProcessWriteResult::failed(CanonicalCommissionGovernance::MEMBER_INACTIVE_MESSAGE);
            }
            if ($locked->declarations_signed_at !== null
                || filled($locked->confidentiality_declaration)
                || filled($locked->conflict_of_interest_declaration)) {
                return ProcessWriteResult::failed(self::DECLARATION_IMMUTABLE_MESSAGE);
            }

            $block = $this->notFutureNorBeforeAppointment($locked, $signedAt, compareDays: true);
            if ($block !== null) {
                return ProcessWriteResult::failed($block);
            }

            $locked->update([
                'confidentiality_declaration' => self::DECLARATION_CONFIDENTIALITY_TEXT,
                'conflict_of_interest_declaration' => self::DECLARATION_CONFLICT_TEXT,
                'declarations_signed_at' => $signedAt,
            ]);

            $this->auditMember($competition, CanonicalAdministrativeEvent::COMMISSION_DECLARATIONS_RECORDED, $actor, $locked, [
                'signed_on' => $signedAt->toDateString(),
                'recorded_on_behalf_of_user_id' => $locked->user_id,
            ]);

            return ProcessWriteResult::ok($locked->id);
        });
    }

    public function refreshConflicts(Competition $competition, User $actor): ProcessWriteResult
    {
        [$commission, $reason] = $this->commissionFor($competition);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        return DB::transaction(function () use ($competition, $commission, $actor) {
            $recorded = $this->membership->recordPibConflicts($commission, $actor);
            $this->gate->audit($competition, CanonicalAdministrativeEvent::COMMISSION_CONFLICTS_CHECKED, $actor, $commission, [
                'new_conflicts' => $recorded,
            ]);

            return ProcessWriteResult::ok($recorded);
        });
    }

    public function memberScopeBlockReason(Competition $competition, CommissionMember $member): ?string
    {
        [$commission, $reason] = $this->commissionFor($competition);
        if ($reason !== null) {
            return $reason;
        }

        return (int) $member->commission_id === (int) $commission->id ? null : self::MEMBER_SCOPE_MESSAGE;
    }

    /**
     * Read model for the governance screen. Represented-company PIBs are masked; applicants are
     * referenced by Prijava number only.
     *
     * @return array<string, mixed>
     */
    public function overview(Competition $competition): array
    {
        $commission = $competition->commission_id ? Commission::query()->find($competition->commission_id) : null;
        $canonicalCommission = $commission?->isCanonicalProfile() ? $commission : null;
        $policy = $this->governance->policy();
        $events = CanonicalAdministrativeEvent::query()
            ->where('competition_id', $competition->id)
            ->whereIn('action', CanonicalAdministrativeEvent::COMMISSION_ACTIONS)
            ->orderBy('id')
            ->get();

        $base = [
            'commission' => $canonicalCommission,
            'foreignCommission' => $commission !== null && $canonicalCommission === null,
            'callBlock' => $this->callBlockReason($competition, requireUnassigned: false),
            'establishBlock' => $commission === null ? $this->callBlockReason($competition) : self::ALREADY_ASSIGNED_MESSAGE,
            'commissionSize' => $this->governance->commissionConfig()->seatCount,
            'replacementDueDays' => (int) $policy->commissionReplacementDueDays(),
            'mandateYears' => (int) $policy->commissionMandateYears(),
            'roles' => CanonicalCommissionGovernance::ROLE_LABELS,
            'externalCategories' => CanonicalCommissionGovernance::EXTERNAL_CATEGORY_LABELS,
            'vacancyReasons' => CanonicalCommissionMembershipService::VACANCY_REASONS,
            'events' => $events,
            'eventActors' => User::query()->whereIn('id', $events->pluck('actor_user_id')->unique())->pluck('name', 'id'),
        ];

        if ($canonicalCommission === null) {
            return $base + ['seats' => [], 'history' => collect(), 'replacements' => collect(), 'conflicts' => collect()];
        }

        $members = CommissionMember::query()->where('commission_id', $canonicalCommission->id)->with('user')->orderBy('id')->get();
        $replacements = CommissionMemberReplacement::query()->where('commission_id', $canonicalCommission->id)->orderBy('id')->get();
        $conflicts = CommissionGovernanceConflict::query()->where('commission_id', $canonicalCommission->id)->orderBy('id')->get();
        $active = $this->governance->activeMembers($canonicalCommission);

        $seats = [];
        foreach (range(1, $this->governance->commissionConfig()->seatCount) as $seatNo) {
            $member = $active->first(fn (CommissionMember $m) => (int) $m->canonical_seat_no === $seatNo);
            $openVacancy = $replacements->first(fn (CommissionMemberReplacement $r) => (int) $r->seat_no === $seatNo && $r->replacement_member_id === null);
            $seats[$seatNo] = [
                'member' => $member,
                'state' => $member !== null ? 'active' : ($openVacancy !== null ? 'vacant' : 'empty'),
                'vacancy' => $openVacancy,
                'vacatedMember' => $openVacancy ? $members->firstWhere('id', $openVacancy->vacated_member_id) : null,
                'declarationsSigned' => $member?->hasSignedDeclarations() ?? false,
                'pibConflict' => $member !== null && $this->governance->memberHasPibConflict($canonicalCommission, $member),
                'maskedPib' => $this->maskPib($member?->represented_company_pib),
                'actionBlock' => $member !== null ? $this->governance->memberActionBlockReason($member, $competition) : null,
            ];
        }

        return array_merge($base, [
            'seats' => $seats,
            'usedRoles' => $active->pluck('canonical_seat')->all(),
            'mandateValid' => $this->governance->mandateIsValid($canonicalCommission),
            'structuralBlock' => $this->governance->structuralCompletenessBlockReason($canonicalCommission),
            'history' => $members,
            'replacements' => $replacements,
            'conflicts' => $conflicts->map(fn (CommissionGovernanceConflict $conflict) => [
                'detected_at' => $conflict->detected_at,
                'blocked_appointment' => $conflict->commission_member_id === null,
                'member_name' => $members->firstWhere('id', $conflict->commission_member_id)?->name
                    ?? User::query()->whereKey($conflict->user_id)->value('name'),
                'application_id' => $conflict->application_id,
                'competition_id' => $conflict->competition_id,
                'masked_pib' => $this->maskPib($conflict->normalized_value),
                'reason' => $conflict->reason,
            ]),
        ]);
    }

    /**
     * @return array{0: ?Commission, 1: ?string}
     */
    private function commissionFor(Competition $competition): array
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return [null, self::NOT_CANONICAL_CALL_MESSAGE];
        }
        if (CanonicalAdministrativeGate::isClosed($competition)) {
            return [null, self::CALL_CLOSED_MESSAGE];
        }

        $commission = $competition->commission_id ? Commission::query()->find($competition->commission_id) : null;
        if ($commission === null) {
            return [null, CanonicalCommissionGovernance::NO_COMMISSION_MESSAGE];
        }
        if (! $commission->isCanonicalProfile()) {
            return [null, CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE];
        }

        return [$commission, null];
    }

    private function callBlockReason(Competition $competition, bool $requireUnassigned = true): ?string
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return self::NOT_CANONICAL_CALL_MESSAGE;
        }
        if (CanonicalAdministrativeGate::isClosed($competition)) {
            return self::CALL_CLOSED_MESSAGE;
        }
        if ($requireUnassigned && $competition->commission_id !== null) {
            return self::ALREADY_ASSIGNED_MESSAGE;
        }

        return null;
    }

    private function appointmentDateBlockReason(Commission $commission, CarbonImmutable $appointedAt): ?string
    {
        if (! $this->governance->mandateIsValid($commission)) {
            return CanonicalCommissionGovernance::MANDATE_MESSAGE;
        }
        if ($appointedAt->greaterThan($this->now())) {
            return self::FUTURE_DATE_MESSAGE;
        }

        $start = CarbonImmutable::parse($commission->mandate_starts_at)->timezone($this->tz())->startOfDay();
        $end = $this->governance->policy()->commissionMandateEndsAt(CarbonImmutable::parse($commission->mandate_starts_at));

        return $appointedAt->lessThan($start) || ($end !== null && $appointedAt->greaterThan($end))
            ? self::APPOINTMENT_OUTSIDE_MANDATE_MESSAGE
            : null;
    }

    /**
     * @return array{0: ?User, 1: ?string}
     */
    private function eligibleUser(Commission $commission, string $email): array
    {
        $user = User::query()->with('role')->where('email', strtolower(trim($email)))->first();
        if ($user === null) {
            return [null, self::USER_NOT_FOUND_MESSAGE];
        }
        if ($user->role?->name !== 'komisija' || $user->activation_status !== 'active') {
            return [null, self::USER_ROLE_MESSAGE];
        }

        $isApplicant = Application::query()
            ->where('user_id', $user->id)
            ->whereHas('competition', fn ($query) => $query->where('commission_id', $commission->id))
            ->exists();

        return $isApplicant ? [null, self::APPLICANT_MESSAGE] : [$user, null];
    }

    private function notFutureNorBeforeAppointment(CommissionMember $member, CarbonImmutable $at, bool $compareDays = false): ?string
    {
        if ($at->greaterThan($this->now())) {
            return self::FUTURE_DATE_MESSAGE;
        }
        if ($member->appointed_at === null) {
            return null;
        }

        $appointed = CarbonImmutable::parse($member->appointed_at)->timezone($this->tz());
        if ($compareDays) {
            $appointed = $appointed->startOfDay();
        }

        return $at->lessThan($appointed) ? self::DATE_BEFORE_APPOINTMENT_MESSAGE : null;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function auditMember(Competition $competition, string $action, User $actor, CommissionMember $member, array $extra = []): void
    {
        $this->gate->audit($competition, $action, $actor, $member, array_merge([
            'commission_id' => $member->commission_id,
            'member_user_id' => $member->user_id,
            'seat_no' => $member->canonical_seat_no,
            'role' => $member->canonical_seat,
            'appointment_reference' => $member->appointment_reference,
        ], $extra));
    }

    private function sendAppointmentMail(CommissionMember $member, Competition $competition, ?CommissionMember $replaced): void
    {
        $member->loadMissing(['user', 'commission']);
        if (! $member->user?->email) {
            return;
        }

        try {
            Mail::to($member->user->email)->send(new CommissionMemberAdded(
                $member,
                CommissionAppointmentMailCopy::forCommission($member->commission),
                $replaced,
                $competition,
            ));
        } catch (\Throwable $exception) {
            Log::warning('Canonical Commission appointment mail failed', [
                'commission_member_id' => $member->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function maskPib(?string $pib): ?string
    {
        return $pib === null || $pib === '' ? null : str_repeat('•', max(strlen($pib) - 3, 0)).substr($pib, -3);
    }

    private function day(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, $this->tz())->startOfDay();
    }

    private function moment(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, $this->tz());
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->tz());
    }

    private function tz(): string
    {
        return $this->governance->policy()->publicationTimezone();
    }
}
