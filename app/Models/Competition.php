<?php

namespace App\Models;

use App\Support\CommissionProfileConfig;
use App\Support\RichText;
use App\Services\CanonicalIndividualScoringService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Competition extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'call_number',
        'status',
        'competition_number',
        'year',
        'budget',
        'annual_budget',
        'max_support_percentage',
        'deadline_days',
        'published_at',
        'closed_at',
        'commission_id',
        'candidates_list_email_sent_at',
        'youth_allocation_list_confirmed_at',
        'youth_allocation_list_confirmed_by_user_id',
        'youth_allocation_list_confirmed_by_commission_member_id',
        'youth_allocation_list_confirmed_by_name',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
        'candidates_list_email_sent_at' => 'datetime',
        'youth_allocation_list_confirmed_at' => 'datetime',
        'budget' => 'decimal:2',
        'annual_budget' => 'decimal:2',
        'max_support_percentage' => 'decimal:2',
        'year' => 'integer',
        'call_number' => 'integer',
        'deadline_days' => 'integer',
    ];

    public function isOmladinskoProfile(): bool
    {
        return $this->type === 'omladinsko';
    }

    public function isFirstCall(): bool
    {
        return $this->isOmladinskoProfile() && (int) $this->call_number === 1;
    }

    public function isSecondCall(): bool
    {
        return $this->isOmladinskoProfile() && (int) $this->call_number === 2;
    }

    public function usesAnnualCallSequence(): bool
    {
        return $this->isFirstCall() || $this->isSecondCall();
    }

    public function descriptionHtml(): string
    {
        return RichText::formatLinks($this->description);
    }

    public function descriptionExcerpt(int $limit = 150): string
    {
        return Str::limit(strip_tags($this->description ?? ''), $limit);
    }

    // Veza: jedan konkurs ima više prijava
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // Veza: konkurs ima više kriterijuma
    public function evaluationCriteria()
    {
        return $this->hasMany(EvaluationCriteria::class);
    }

    // Veza: konkurs ima više prioriteta
    public function priorities()
    {
        return $this->hasMany(Priority::class);
    }

    // Veza: konkurs pripada komisiji
    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    public function youthAllocationListConfirmedByUser()
    {
        return $this->belongsTo(User::class, 'youth_allocation_list_confirmed_by_user_id');
    }

    public function youthAllocationListConfirmedByCommissionMember()
    {
        return $this->belongsTo(CommissionMember::class, 'youth_allocation_list_confirmed_by_commission_member_id');
    }

    public function commissionSessions(): HasMany
    {
        return $this->hasMany(CommissionSession::class);
    }

    public function firstCommissionSession(): ?CommissionSession
    {
        if ($this->relationLoaded('commissionSessions')) {
            return $this->commissionSessions
                ->first(fn (CommissionSession $session) => $session->session_type === CommissionSession::TYPE_FIRST);
        }

        return $this->commissionSessions()
            ->where('session_type', CommissionSession::TYPE_FIRST)
            ->first();
    }

    public function secondCommissionSession(): ?CommissionSession
    {
        if ($this->relationLoaded('commissionSessions')) {
            return $this->commissionSessions
                ->first(fn (CommissionSession $session) => $session->session_type === CommissionSession::TYPE_SECOND);
        }

        return $this->commissionSessions()
            ->where('session_type', CommissionSession::TYPE_SECOND)
            ->first();
    }

    // Veza: konkurs ima jedan UP broj
    public function upNumber()
    {
        return $this->hasOne(UpNumber::class);
    }

    // Veza: konkurs ima više istorijskih potpisanih primjeraka zvanične Odluke
    public function officialDecisionCopies()
    {
        return $this->hasMany(CompetitionOfficialDecisionCopy::class);
    }

    /**
     * Izračunava datum i vreme isteka konkursa
     */
    public function getDeadlineAttribute()
    {
        $days = (int) ($this->deadline_days ?? 20); // Rok za prijave (20 dana)

        // 1. Ako je postavljen datum početka, on je baza za rok (npr. 20 dana)
        if ($this->start_date) {
            return $this->start_date->copy()->addDays($days)->endOfDay();
        }

        // 2. Fallback na datum objavljivanja ako nema početka
        if ($this->published_at) {
            return $this->published_at->copy()->addDays($days)->endOfDay();
        }

        return null;
    }

    /**
     * Proverava da li je konkurs trenutno otvoren za prijave
     */
    public function getIsOpenAttribute()
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = now();
        $start = $this->start_date ? $this->start_date->startOfDay() : ($this->published_at ? $this->published_at : null);
        $deadline = $this->deadline;

        if (!$start || !$deadline) {
            return false;
        }

        return $now >= $start && $now <= $deadline;
    }

    /**
     * Proverava da li konkurs tek treba da počne
     */
    public function getIsUpcomingAttribute()
    {
        if ($this->status !== 'published' || !$this->start_date) {
            return false;
        }

        return now() < $this->start_date->startOfDay();
    }

    /**
     * Vraća datum zatvaranja prijava (baza za 45-dnevni rok za odluku).
     * Ako je closed_at postavljen (ručno zatvaranje), koristi ga.
     * Inače, ako je rok za prijave istekao, koristi datum roka za prijave.
     */
    public function getApplicationsClosedAt(): ?\Carbon\Carbon
    {
        if ($this->closed_at) {
            return $this->closed_at;
        }
        if ($this->isApplicationDeadlinePassed() && $this->deadline) {
            return $this->deadline;
        }
        return null;
    }

    /**
     * Proverava da li je prošlo 45 dana od zatvaranja prijava
     * Komisija mora donijeti odluku u roku od 45 dana od dana zatvaranja prijava
     */
    public function isEvaluationDeadlinePassed(): bool
    {
        $closedAt = $this->getApplicationsClosedAt();
        if (!$closedAt) {
            return false;
        }

        $deadline = $closedAt->copy()->addDays(45);
        return now()->isAfter($deadline);
    }

    /**
     * Vraća preostalo vrijeme do isteka roka za ocjenjivanje (u danima)
     */
    public function getDaysUntilEvaluationDeadline(): ?int
    {
        $closedAt = $this->getApplicationsClosedAt();
        if (!$closedAt) {
            return null;
        }

        $deadline = $closedAt->copy()->addDays(45);
        $daysRemaining = now()->diffInDays($deadline, false);
        
        return $daysRemaining >= 0 ? (int) $daysRemaining : 0;
    }

    /**
     * Vraća datum isteka roka za donošenje odluke (45 dana od zatvaranja prijava)
     */
    public function getEvaluationDeadlineDate(): ?\Carbon\Carbon
    {
        $closedAt = $this->getApplicationsClosedAt();
        if (!$closedAt) {
            return null;
        }
        return $closedAt->copy()->addDays(45);
    }

    /**
     * Vraća preostalo vrijeme do isteka roka za prijave (u danima)
     * Rok za prijave je 20 dana od početka konkursa
     */
    public function getDaysUntilApplicationDeadline(): ?int
    {
        if ($this->status !== 'published') {
            return null;
        }

        $deadline = $this->deadline;
        if (!$deadline) {
            return null;
        }

        $daysRemaining = now()->diffInDays($deadline, false);
        return $daysRemaining >= 0 ? $daysRemaining : 0;
    }

    /**
     * Provjerava da li je rok za prijave istekao
     */
    public function isApplicationDeadlinePassed(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $deadline = $this->deadline;
        if (!$deadline) {
            return false;
        }

        return now()->isAfter($deadline);
    }

    /**
     * Profil predviđa Komisiju (`KN-BM-003` za zensko; `KN-BM-002` za omladinsko).
     */
    public function profileProvidesCommission(): bool
    {
        return CommissionProfileConfig::for($this->type)->providesCommission;
    }

    public function commissionProfileConfig(): CommissionProfileConfig
    {
        return CommissionProfileConfig::for($this->type);
    }

    /**
     * Potpuna i valjana Komisija prema profilu Poziva.
     */
    public function hasCompleteValidCommission(): bool
    {
        if (! $this->profileProvidesCommission()) {
            return true;
        }

        return self::commissionIsCompleteAndValidForType($this->commission, (string) $this->type);
    }

    public static function commissionIsCompleteAndValid(?Commission $commission): bool
    {
        return self::commissionIsCompleteAndValidForType($commission, 'zensko');
    }

    public static function commissionIsCompleteAndValidForType(?Commission $commission, string $type): bool
    {
        if (! $commission) {
            return false;
        }

        $config = CommissionProfileConfig::for($type);

        if (! $config->providesCommission) {
            return true;
        }

        $active = $commission->relationLoaded('activeMembers')
            ? $commission->activeMembers
            : $commission->activeMembers()->get();

        if ($type === 'zensko') {
            if ($active->count() !== 5) {
                return false;
            }

            return $active->contains(fn (CommissionMember $member) => $member->position === 'predsjednik');
        }

        if ($type !== 'omladinsko') {
            return true;
        }

        $seats = [];
        $presidents = 0;

        foreach ($active as $member) {
            $seat = $member->canonicalSeatNumber();
            if ($seat === null || ! $config->allowsSeat($seat)) {
                return false;
            }
            if (isset($seats[$seat])) {
                return false;
            }
            $seats[$seat] = true;
            if ($member->position === 'predsjednik') {
                $presidents++;
            }
        }

        if ($presidents !== 1) {
            return false;
        }

        if (count($seats) !== $config->seatCount) {
            return false;
        }

        foreach ($config->allowedSeats as $requiredSeat) {
            if (! isset($seats[$requiredSeat])) {
                return false;
            }
        }

        return true;
    }

    public function hasConfirmedFirstSessionQuorum(): bool
    {
        $config = $this->commissionProfileConfig();
        if ($config->firstSessionQuorum === null) {
            return true;
        }

        $session = $this->firstCommissionSession();
        if ($session === null || ! $session->isConfirmed()) {
            return false;
        }

        return $session->meetsFirstSessionQuorum($this);
    }

    /**
     * Nakon isteka roka (ili zatvaranja) postupak Komisije ostaje blokiran dok Komisija nije potpuna i valjana.
     * Za omladinsko dodatno zahtijeva potvrđenu prvu sjednicu sa kvorumom.
     * Ne uvodi novo lifecycle stanje.
     */
    public function isCommissionProcessingBlocked(): bool
    {
        if (! $this->profileProvidesCommission()) {
            return false;
        }

        $deadlineRelevant = $this->isApplicationDeadlinePassed()
            || in_array($this->status, ['closed', 'completed'], true);

        if (! $deadlineRelevant) {
            return false;
        }

        if (! $this->hasCompleteValidCommission()) {
            return true;
        }

        if ($this->type === 'omladinsko') {
            return ! $this->hasConfirmedFirstSessionQuorum();
        }

        return false;
    }

    public const COMMISSION_PROCESSING_BLOCKED_MESSAGE = 'Pristup Komisije prijavama i dalji konkursni postupak blokirani su dok Komisija nije formalno kompletna, ili dok prva sjednica nije potvrđena sa potrebnim kvorumom.';

    public const WHOLE_COMMISSION_REPLACE_AFTER_DEADLINE_MESSAGE = 'Nakon isteka roka za Prijave nije dozvoljena obična zamjena cijele dodijeljene Komisije.';

    public const WHOLE_COMMISSION_REPLACE_MUST_BE_VALID_MESSAGE = 'Cijela Komisija može se zamijeniti samo drugom potpunom i valjanom Komisijom.';

    /**
     * @return string|null Poruka greške ako dodjela/zamjena nije dozvoljena.
     */
    public function commissionAssignmentChangeError(?int $newCommissionId): ?string
    {
        if (! $this->profileProvidesCommission()) {
            return null;
        }

        $oldId = $this->commission_id ? (int) $this->commission_id : null;
        $newId = $newCommissionId;

        if ($oldId === $newId) {
            return null;
        }

        $deadlinePassed = $this->isApplicationDeadlinePassed()
            || in_array($this->status, ['closed', 'completed'], true);

        if ($deadlinePassed) {
            if ($oldId !== null) {
                return self::WHOLE_COMMISSION_REPLACE_AFTER_DEADLINE_MESSAGE;
            }

            return null;
        }

        if ($oldId !== null && $newId !== null) {
            $newCommission = Commission::with('activeMembers')->find($newId);
            if (! self::commissionIsCompleteAndValidForType($newCommission, (string) $this->type)) {
                return self::WHOLE_COMMISSION_REPLACE_MUST_BE_VALID_MESSAGE;
            }
        }

        return null;
    }

    public function commissionProfileConflictError(?int $commissionId): ?string
    {
        if ($commissionId === null) {
            return null;
        }

        $commission = $this->relationLoaded('commission') && (int) $this->commission_id === $commissionId
            ? $this->commission
            : Commission::with('competitions')->find($commissionId);

        if (! $commission) {
            return null;
        }

        return CommissionProfileConfig::conflictWithAssignedCompetitions(
            $commission,
            (string) $this->type,
            $this->exists ? $this->id : null
        );
    }

    /**
     * Ciklus individualnog bodovanja je završen kada svih pet kanonskih mjesta
     * ima konačnu ocjenu za svaku prijavu koja smije ući u bodovanje.
     */
    public function isIndividualScoringCycleComplete(): bool
    {
        return app(CanonicalIndividualScoringService::class)
            ->isIndividualScoringCycleComplete($this);
    }

    /**
     * Rang lista je formirana tek po završetku cjelokupnog ciklusa individualnog bodovanja.
     */
    public function isRankingFormed(): bool
    {
        if ($this->isOmladinskoProfile()) {
            return app(CanonicalIndividualScoringService::class)
                ->isYouthPreliminaryRankingReady($this);
        }

        return $this->isIndividualScoringCycleComplete();
    }

    /**
     * Provjerava da li je predsjednik komisije donio odluku za sve prijave u rang listi
     * (zaključak komisije za svaku prijavu)
     */
    public function hasChairmanCompletedDecisions(): bool
    {
        if (!$this->isRankingFormed()) {
            return false;
        }

        $commission = $this->commission;
        $chairmanMember = $commission ? $commission->activeMembers()->where('position', 'predsjednik')->first() : null;
        if (!$chairmanMember) {
            return false;
        }

        if ($this->isOmladinskoProfile()) {
            return app(\App\Services\Competitions\YouthAllocationListConfirmationService::class)
                ->confirmedListIsIntact($this);
        }

        $allApplications = $this->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'rejected'])
            ->with(['evaluationScores', 'eliminatoryCheck', 'prigovor'])
            ->get();

        $rankingApplications = $allApplications->filter(function ($application) {
            if ($application->isEliminatedFromScoring()) {
                return false;
            }
            if ($application->evaluationScores->isEmpty() || !$application->meetsMinimumScore()) {
                return false;
            }
            return true;
        });

        // Ako nema prijava u rang listi (sve odbijene), predsjednik je završio
        if ($rankingApplications->isEmpty()) {
            return true;
        }

        return $rankingApplications->every(function ($app) {
            if ($app->commission_decision === 'podrzava_potpuno') {
                return $app->approved_amount !== null && (float) $app->approved_amount > 0;
            }

            if ($app->commission_decision === 'odbija') {
                return trim((string) $app->commission_justification) !== '';
            }

            return false;
        });
    }
}
