<?php

namespace App\Services\Competitions\Commission;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\Commission;
use App\Models\CommissionGovernanceConflict;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Services\Competitions\CanonicalReviewState;
use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CommissionProfileConfig;
use App\Support\Pib;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Canonical MMSP Commission governance gates (read side).
 *
 * Structural completeness = membership, seats, semantic roles, external category, conflicts.
 * Action readiness = structural completeness + active/not replaced + mandate + declarations.
 * Session gates: ordinary 2/3, interview and final decisions 3/3 (remote/electronic counts).
 */
class CanonicalCommissionGovernance
{
    public const ROLE_PREDSJEDNIK = 'predsjednik';

    public const ROLE_SEKRETAR = 'sekretar';

    public const ROLE_SPOLJASNJI = 'spoljasnji';

    /** @var list<string> */
    public const ROLES = [self::ROLE_PREDSJEDNIK, self::ROLE_SEKRETAR, self::ROLE_SPOLJASNJI];

    /** @var array<string, string> institutional labels from Odluka čl.7 */
    public const ROLE_LABELS = [
        self::ROLE_PREDSJEDNIK => 'Predsjednik Komisije (predstavnik Opštine)',
        self::ROLE_SEKRETAR => 'Sekretar Komisije (predstavnik Opštine)',
        self::ROLE_SPOLJASNJI => 'Spoljašnji član Komisije',
    ];

    /** @var list<string> */
    public const EXTERNAL_CATEGORIES = ['udruzenje', 'biznis', 'akademska_zajednica'];

    /** @var array<string, string> */
    public const EXTERNAL_CATEGORY_LABELS = [
        'udruzenje' => 'Udruženje',
        'biznis' => 'Biznis zajednica',
        'akademska_zajednica' => 'Akademska zajednica',
    ];

    public const PURPOSE_ORDINARY = 'ordinary';

    public const PURPOSE_INTERVIEW = 'interview';

    public const PURPOSE_FINAL_DECISION = 'final_decision';

    /** @var array<string, string> canonical purpose => shared session_type */
    public const PURPOSE_SESSION_TYPES = [
        self::PURPOSE_ORDINARY => CommissionSession::TYPE_FIRST,
        self::PURPOSE_INTERVIEW => CommissionSession::TYPE_SECOND,
        self::PURPOSE_FINAL_DECISION => CommissionSession::TYPE_THIRD,
    ];

    public const ATTENDANCE_IN_PERSON = 'in_person';

    public const ATTENDANCE_REMOTE = 'remote';

    public const ATTENDANCE_ELECTRONIC = 'electronic';

    /** @var list<string> */
    public const ATTENDANCE_MODES = [self::ATTENDANCE_IN_PERSON, self::ATTENDANCE_REMOTE, self::ATTENDANCE_ELECTRONIC];

    public const NOT_CANONICAL_MESSAGE = 'Komisija nije evidentirana kao kanonska MMSP Komisija.';

    public const NO_COMMISSION_MESSAGE = 'Kanonski Poziv nema dodijeljenu Komisiju.';

    public const SIZE_MESSAGE = 'Kanonska Komisija mora imati tačno 3 aktivna člana.';

    public const FOURTH_SEAT_MESSAGE = 'Kanonska Komisija ima tačno 3 mjesta. Četvrto aktivno mjesto nije dozvoljeno.';

    public const SEAT_INVALID_MESSAGE = 'Član mora imati važeće strukturno mjesto 1–3 i semantičku ulogu (predsjednik, sekretar, spoljašnji član).';

    public const SEAT_DUPLICATE_MESSAGE = 'Strukturno mjesto ili semantička uloga u kanonskoj Komisiji je već popunjena.';

    public const EXTERNAL_CATEGORY_MESSAGE = 'Spoljašnji član mora imati kategoriju: udruženje, biznis ili akademska zajednica.';

    public const MUNICIPAL_CATEGORY_MESSAGE = 'Predsjednik i sekretar su predstavnici Opštine i nemaju spoljašnju kategoriju.';

    public const USER_REQUIRED_MESSAGE = 'Član kanonske Komisije mora biti korisnik platforme.';

    public const SAME_USER_MESSAGE = 'Isti korisnik ne može zauzimati dva aktivna mjesta u istoj Komisiji.';

    public const PIB_CONFLICT_MESSAGE = 'Član predstavlja privredno društvo čiji PIB se poklapa sa PIB-om podnosioca prijave. Sukob interesa blokira članstvo.';

    public const MEMBER_INACTIVE_MESSAGE = 'Član nije aktivan ili je zamijenjen. Buduće radnje nijesu dozvoljene.';

    public const NOT_MEMBER_MESSAGE = 'Korisnik nije aktivan član kanonske Komisije ovog Poziva.';

    public const COMMISSION_INCOMPLETE_MESSAGE = 'Kanonska Komisija nije strukturno potpuna.';

    public const MANDATE_MESSAGE = 'Mandat Komisije nije važeći (istekao ili nije evidentiran).';

    public const DECLARATIONS_MESSAGE = 'Prije radnji Komisije član mora potpisati izjavu o povjerljivosti i izjavu o sukobu interesa.';

    public const QUORUM_MESSAGE = 'Redovna sjednica zahtijeva kvorum od najmanje 2 od 3 važeća člana.';

    public const FULL_ATTENDANCE_MESSAGE = 'Intervju i konačne odluke Komisije zahtijevaju prisustvo sva 3 važeća člana (lično ili elektronski).';

    public const INTERVIEW_REQUIRED_MESSAGE = 'Bodovanje zahtijeva potvrđenu sjednicu intervjua sa sva 3 važeća člana.';

    public const FINAL_DECISION_SESSION_REQUIRED_MESSAGE = 'Utvrđivanje konačne rang-liste i predloga zahtijeva potvrđenu treću sjednicu sa sva 3 važeća člana.';

    public const SCORING_STAGE_CLOSED_MESSAGE = 'Faza bodovanja nije otvorena za ovu prijavu.';

    public const SEAT_SCORE_COMPLETED_MESSAGE = 'Za ovo mjesto Komisije već postoji završena ocjena. Zamjenski član je ne može prepisati.';

    public const FINAL_SIGNATURES_MESSAGE = 'Konačna rang lista zahtijeva potpise/potvrde sva 3 važeća člana Komisije.';

    public const CHAIR_REQUIRED_MESSAGE = 'Odluku o prigovoru donosi predsjednik kanonske Komisije.';

    public const CHAIR_COMPLETENESS_REQUIRED_MESSAGE = 'Provjeru potpunosti evidentira predsjednik kanonske Komisije.';

    public const APPLICATION_DEADLINE_OPEN_MESSAGE = 'Postupak Komisije (provjera potpunosti i prigovori) počinje tek nakon isteka roka za prijave.';

    public const ORDINARY_SESSION_REQUIRED_MESSAGE = 'Provjera potpunosti i odluka o prigovoru zahtijevaju potvrđenu redovnu (prvu) sjednicu Komisije sa kvorumom od najmanje 2 od 3 važeća člana.';
    public static function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? 'Nepoznata uloga';
    }

    public static function externalCategoryLabel(?string $category): ?string
    {
        return $category === null ? null : (self::EXTERNAL_CATEGORY_LABELS[$category] ?? $category);
    }

    public function policy(): CompetitionProfilePolicy
    {
        return CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);
    }

    /**
     * Seat count and session quorums come from the shared Commission profile config
     * (seatCount 3, firstSessionQuorum 2, allMembersRequiredCount 3), as for Women/Youth.
     */
    public function commissionConfig(): CommissionProfileConfig
    {
        return CommissionProfileConfig::for(CompetitionProfileResolver::TYPE_CANONICAL);
    }

    public function isCanonicalCompetition(?Competition $competition): bool
    {
        return $competition?->type === CompetitionProfileResolver::TYPE_CANONICAL;
    }

    public function structuralCompletenessBlockReason(?Commission $commission): ?string
    {
        if ($commission === null) {
            return self::NO_COMMISSION_MESSAGE;
        }

        if (! $commission->isCanonicalProfile()) {
            return self::NOT_CANONICAL_MESSAGE;
        }

        $active = $this->activeMembers($commission);
        if ($active->count() !== $this->commissionConfig()->seatCount) {
            return self::SIZE_MESSAGE;
        }

        $seatNos = [];
        $roles = [];
        $users = [];

        foreach ($active as $member) {
            $reason = $this->seatBlockReason($member);
            if ($reason !== null) {
                return $reason;
            }

            if (isset($seatNos[$member->canonical_seat_no]) || isset($roles[$member->canonical_seat])) {
                return self::SEAT_DUPLICATE_MESSAGE;
            }
            if ($member->user_id === null) {
                return self::USER_REQUIRED_MESSAGE;
            }
            if (isset($users[$member->user_id])) {
                return self::SAME_USER_MESSAGE;
            }

            $seatNos[$member->canonical_seat_no] = true;
            $roles[$member->canonical_seat] = true;
            $users[$member->user_id] = true;
        }

        foreach ($active as $member) {
            if ($this->memberHasPibConflict($commission, $member)) {
                return self::PIB_CONFLICT_MESSAGE;
            }
        }

        return null;
    }

    public function seatBlockReason(CommissionMember $member): ?string
    {
        if (! in_array((int) $member->canonical_seat_no, [1, 2, 3], true)
            || ! in_array($member->canonical_seat, self::ROLES, true)) {
            return self::SEAT_INVALID_MESSAGE;
        }

        return $this->externalCategoryBlockReason($member->canonical_seat, $member->external_category);
    }

    public function externalCategoryBlockReason(string $role, ?string $category): ?string
    {
        if ($role === self::ROLE_SPOLJASNJI) {
            return in_array($category, self::EXTERNAL_CATEGORIES, true) ? null : self::EXTERNAL_CATEGORY_MESSAGE;
        }

        return $category === null ? null : self::MUNICIPAL_CATEGORY_MESSAGE;
    }

    /**
     * Represented-company PIB collisions with applicants of Pozivi assigned to this Commission.
     *
     * @return Collection<int, array{application_id: int, competition_id: int, normalized: string}>
     */
    public function pibCollisions(Commission $commission, ?string $representedCompanyPib): Collection
    {
        $normalized = Pib::normalize($representedCompanyPib);
        if ($normalized === null) {
            return collect();
        }

        return Application::query()
            ->whereHas('competition', fn ($query) => $query->where('commission_id', $commission->id))
            ->where('status', '!=', 'draft')
            ->with('businessPlan')
            ->orderBy('id')
            ->get()
            ->filter(fn (Application $application) => Pib::normalize($application->pib) === $normalized
                || Pib::normalize($application->businessPlan?->pib) === $normalized)
            ->map(fn (Application $application) => [
                'application_id' => (int) $application->id,
                'competition_id' => (int) $application->competition_id,
                'normalized' => $normalized,
            ])
            ->values();
    }

    public function memberHasPibConflict(Commission $commission, CommissionMember $member): bool
    {
        $recorded = CommissionGovernanceConflict::query()
            ->where('commission_member_id', $member->id)
            ->where('conflict_type', CommissionGovernanceConflict::TYPE_REPRESENTED_COMPANY_PIB)
            ->exists();

        return $recorded || $this->pibCollisions($commission, $member->represented_company_pib)->isNotEmpty();
    }

    public function mandateIsValid(Commission $commission, ?CarbonImmutable $at = null): bool
    {
        if ($commission->mandate_starts_at === null) {
            return false;
        }

        $policy = $this->policy();
        $at ??= CarbonImmutable::now($policy->publicationTimezone());
        $start = CarbonImmutable::parse($commission->mandate_starts_at);
        $end = $policy->commissionMandateEndsAt($start);

        return $end !== null && ! $at->lessThan($start) && ! $at->greaterThan($end);
    }

    public function activeMemberFor(User $user, Competition $competition): ?CommissionMember
    {
        return CommissionMember::activeForCompetition((int) $user->id, $competition);
    }

    public function memberActionBlockReason(
        CommissionMember $member,
        ?Competition $competition = null,
        ?CarbonImmutable $at = null,
    ): ?string {
        $commission = $member->commission()->first();
        if ($commission === null || ! $commission->isCanonicalProfile()) {
            return self::NOT_CANONICAL_MESSAGE;
        }

        if ($competition !== null && (int) $competition->commission_id !== (int) $commission->id) {
            return self::NOT_MEMBER_MESSAGE;
        }

        if ($member->status !== 'active' || $member->replaced_at !== null) {
            return self::MEMBER_INACTIVE_MESSAGE;
        }

        $seatReason = $this->seatBlockReason($member);
        if ($seatReason !== null) {
            return $seatReason;
        }

        $structural = $this->structuralCompletenessBlockReason($commission);
        if ($structural !== null) {
            return $structural === self::PIB_CONFLICT_MESSAGE ? $structural : self::COMMISSION_INCOMPLETE_MESSAGE;
        }

        if (! $this->mandateIsValid($commission, $at)) {
            return self::MANDATE_MESSAGE;
        }

        if (! $member->hasSignedDeclarations()) {
            return self::DECLARATIONS_MESSAGE;
        }

        return null;
    }

    /**
     * Valid present members: active (draft) or historically confirmed, this Commission, unique valid seat.
     * Remote/electronic attendance counts like in-person attendance.
     *
     * @return Collection<int, CommissionMember>
     */
    public function validPresentMembers(CommissionSession $session): Collection
    {
        $competition = $session->competition()->first();
        $members = $session->isConfirmed()
            ? $session->historicalConfirmedPresentMembers($competition)
            : $session->validPresentMembers($competition);

        return $members
            ->filter(fn (CommissionMember $member) => in_array($member->canonical_seat, self::ROLES, true)
                && ($session->isConfirmed() || $member->replaced_at === null))
            ->values();
    }

    public function sessionQuorumBlockReason(CommissionSession $session): ?string
    {
        $competition = $session->competition()->first();

        if ($session->canonical_purpose === self::PURPOSE_ORDINARY) {
            return $session->validPresentCount($competition) >= (int) $this->commissionConfig()->firstSessionQuorum
                ? null
                : self::QUORUM_MESSAGE;
        }

        return $session->meetsSecondSessionAttendance($competition) ? null : self::FULL_ATTENDANCE_MESSAGE;
    }

    /**
     * Commission processing of FINAL applications (completeness, complaint decisions): only after the
     * application deadline, with a structurally valid Commission and a confirmed ordinary (first) session.
     */
    public function commissionProcessBlockReason(Competition $competition): ?string
    {
        if (
            ! $competition->isCanonicalTestMode()
            && ! $competition->isApplicationDeadlinePassed()
            && ! in_array($competition->status, ['closed', 'completed'], true)
        ) {
            return self::APPLICATION_DEADLINE_OPEN_MESSAGE;
        }

        if ($competition->isCommissionProcessingBlocked()) {
            return Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE;
        }

        return $this->ordinarySessionBlockReason($competition);
    }

    /**
     * Confirmed attendance is historical: a later vacancy or replacement does not reopen the quorum.
     */
    public function ordinarySessionBlockReason(Competition $competition): ?string
    {
        $session = $this->purposeSession($competition, self::PURPOSE_ORDINARY);

        return $session !== null && $session->meetsFirstSessionQuorum($competition)
            ? null
            : self::ORDINARY_SESSION_REQUIRED_MESSAGE;
    }

    public function interviewBlockReason(Competition $competition): ?string
    {
        return $this->confirmedFullSessionBlockReason($competition, self::PURPOSE_INTERVIEW)
            ? self::INTERVIEW_REQUIRED_MESSAGE
            : null;
    }

    /**
     * Reusable 3/3 gate for later final Commission decisions (ranking confirmation, outcome, Predlog).
     */
    public function finalDecisionSessionBlockReason(Competition $competition): ?string
    {
        return $this->confirmedFullSessionBlockReason($competition, self::PURPOSE_FINAL_DECISION)
            ? self::FINAL_DECISION_SESSION_REQUIRED_MESSAGE
            : null;
    }

    /**
     * Reusable 3/3 signature gate for the final rank list. Does not form a ranking.
     *
     * @param  iterable<int>  $signingMemberIds
     */
    public function finalRankSignatureBlockReason(
        Competition $competition,
        iterable $signingMemberIds,
        ?CarbonImmutable $at = null,
    ): ?string {
        $ids = collect($signingMemberIds)->map(fn ($id) => (int) $id)->unique()->values();

        $seats = CommissionMember::query()
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (CommissionMember $member) => $this->memberActionBlockReason($member, $competition, $at) === null)
            ->pluck('canonical_seat_no')
            ->unique();

        return $seats->count() === $this->commissionConfig()->allMembersRequiredCount
            ? null
            : self::FINAL_SIGNATURES_MESSAGE;
    }

    /**
     * Governance authorization for a canonical score write.
     */
    public function scoreWriteBlockReason(User $user, Application $application, ?CarbonImmutable $at = null): ?string
    {
        $application->loadMissing(['competition', 'finalClaim']);
        $competition = $application->competition;
        if (! $this->isCanonicalCompetition($competition)) {
            return self::NOT_CANONICAL_MESSAGE;
        }

        $member = $this->activeMemberFor($user, $competition);
        if ($member === null) {
            return self::NOT_MEMBER_MESSAGE;
        }

        $reason = $this->memberActionBlockReason($member, $competition, $at);
        if ($reason !== null) {
            return $reason;
        }

        $phase = $this->commissionProcessBlockReason($competition);
        if ($phase !== null) {
            return $phase;
        }

        $interview = $this->interviewBlockReason($competition);
        if ($interview !== null) {
            return $interview;
        }

        if (! $this->scoringStageOpen($application)) {
            return self::SCORING_STAGE_CLOSED_MESSAGE;
        }

        $seatCompleted = EvaluationScore::query()
            ->where('application_id', $application->id)
            ->where('canonical_seat_no', $member->canonical_seat_no)
            ->whereNotNull('completed_at')
            ->exists();

        return $seatCompleted ? self::SEAT_SCORE_COMPLETED_MESSAGE : null;
    }

    public function scoringStageOpen(Application $application): bool
    {
        $application->loadMissing(['competition', 'finalClaim']);

        if (! $application->finalClaim || $application->status === 'draft') {
            return false;
        }

        if (in_array($application->competition?->status, ['completed'], true)) {
            return false;
        }

        if ($this->canonicalRankingFormed($application->competition)) {
            return false;
        }

        if (! in_array(CanonicalReviewState::resolve($application), [CanonicalReviewState::COMPLETE, CanonicalReviewState::COMPLAINT_RESOLVED], true)) {
            return false;
        }

        return ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->value('outcome') === ApplicationCompletenessAssessment::COMPLETE;
    }

    /**
     * The persisted formation timestamp freezes the preliminary ranking and opens Option D visibility.
     */
    public function canonicalRankingFormed(?Competition $competition): bool
    {
        if (! $this->isCanonicalCompetition($competition)) {
            return false;
        }

        return Competition::query()->whereKey($competition->id)->value('canonical_ranking_formed_at') !== null;
    }

    /**
     * Option D: before ranking, a scorer sees only own scores; peers and admin see none.
     *
     * @return Collection<int, EvaluationScore>
     */
    public function visibleScores(User $viewer, Application $application, ?bool $rankingFormed = null): Collection
    {
        $application->loadMissing('competition');
        $competition = $application->competition;
        if (! $this->isCanonicalCompetition($competition)) {
            return collect();
        }

        $rankingFormed ??= $this->canonicalRankingFormed($competition);
        $scores = EvaluationScore::query()
            ->where('application_id', $application->id)
            ->with('commissionMember')
            ->orderBy('id')
            ->get();

        $isMember = CommissionMember::query()
            ->where('commission_id', $competition->commission_id)
            ->where('user_id', $viewer->id)
            ->exists();

        if ($rankingFormed) {
            return ($isMember || $this->isAdmin($viewer)) ? $scores : collect();
        }

        if (! $isMember) {
            return collect();
        }

        return $scores
            ->filter(fn (EvaluationScore $score) => (int) $score->commissionMember?->user_id === (int) $viewer->id)
            ->values();
    }

    public function aggregatesVisibleTo(User $viewer, Competition $competition, ?bool $rankingFormed = null): bool
    {
        if (! $this->isCanonicalCompetition($competition)) {
            return false;
        }

        return $rankingFormed ?? $this->canonicalRankingFormed($competition);
    }

    public function complaintDecisionBlockReason(User $user, Application $application): ?string
    {
        return $this->chairApplicationBlockReason($user, $application, self::CHAIR_REQUIRED_MESSAGE);
    }

    public function completenessRecordBlockReason(User $user, Application $application): ?string
    {
        return $this->chairApplicationBlockReason($user, $application, self::CHAIR_COMPLETENESS_REQUIRED_MESSAGE);
    }

    /**
     * Read access to the frozen review basis: a current, valid member of this Poziv's canonical Commission.
     * Replaced, vacated, conflicted or undeclared members and members of other Commissions are refused.
     */
    public function reviewAccessBlockReason(User $user, ?Competition $competition): ?string
    {
        if (! $this->isCanonicalCompetition($competition)) {
            return self::NOT_CANONICAL_MESSAGE;
        }

        $member = $this->activeMemberFor($user, $competition);
        if ($member === null) {
            return self::NOT_MEMBER_MESSAGE;
        }

        return $this->memberActionBlockReason($member, $competition);
    }

    private function chairApplicationBlockReason(User $user, Application $application, string $chairMessage): ?string
    {
        $application->loadMissing('competition');
        $competition = $application->competition;
        if (! $this->isCanonicalCompetition($competition)) {
            return self::NOT_CANONICAL_MESSAGE;
        }

        $member = $this->activeMemberFor($user, $competition);
        if ($member === null) {
            return self::NOT_MEMBER_MESSAGE;
        }

        if ($member->canonical_seat !== self::ROLE_PREDSJEDNIK) {
            return $chairMessage;
        }

        return $this->memberActionBlockReason($member, $competition);
    }

    /**
     * @return Collection<int, CommissionMember>
     */
    public function activeMembers(Commission $commission): Collection
    {
        return $commission->activeMembers()->orderBy('canonical_seat_no')->get();
    }

    private function confirmedFullSessionBlockReason(Competition $competition, string $purpose): bool
    {
        $session = $this->purposeSession($competition, $purpose);

        if ($session === null || ! $session->isConfirmed()) {
            return true;
        }

        return ! $session->meetsSecondSessionAttendance($competition);
    }

    private function purposeSession(Competition $competition, string $purpose): ?CommissionSession
    {
        return CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('session_type', self::PURPOSE_SESSION_TYPES[$purpose])
            ->where('canonical_purpose', $purpose)
            ->first();
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role?->name, ['admin', 'superadmin'], true);
    }
}
