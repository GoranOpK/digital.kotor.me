<?php

namespace App\Services\Competitions\Profile;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Thin profile-policy contract.
 *
 * Publication metadata is profile data. The publication service asks
 * these methods; it does not own quarter, slot, or duration constants.
 */
abstract class CompetitionProfilePolicy
{
    public const PUBLICATION_UNRESTRICTED = 'unrestricted';

    public const PUBLICATION_GUARDED = 'guarded';

    public const PUBLICATION_INACTIVE = 'inactive';

    public const DAY_COUNT_CALENDAR_AFTER_START = 'calendar_days_after_start';

    /** Deadline = start instant + N days (Women/Youth Obrazac 3 / Prigovor precedent). */
    public const PROCESS_DEADLINE_EXACT_TIME = 'exact_time';

    /** Deadline = 23:59:59.999999 of the Nth calendar day after the start instant. */
    public const PROCESS_DEADLINE_END_OF_DAY = 'end_of_day';
    abstract public function key(): string;

    abstract public function displayName(): string;

    /**
     * True only when this profile's runtime behavior is already active.
     * Identity can exist before later implementation waves.
     */
    public function isImplemented(): bool
    {
        return false;
    }

    public function isFailClosed(): bool
    {
        return false;
    }

    /**
     * Territorial Kotor check at FINAL. Not a global default.
     * Fail-closed profiles do not inherit it.
     */
    public function requiresKotorAddress(): bool
    {
        return false;
    }

    /**
     * Adopted size for new competitions of this profile.
     * Null means the profile does not supply a commission rule.
     */
    public function commissionSize(): ?int
    {
        return null;
    }

    /**
     * Readable historical commission size. Not a rule for new competitions
     * and not a universal KN rule.
     */
    public function historicalCommissionSize(): ?int
    {
        return null;
    }

    /**
     * True when this profile's scoring/ranking runtime is active.
     * Youth stays inactive even though its policy already records Commission = 3.
     */
    public function scoringRuntimeActive(): bool
    {
        return false;
    }

    /**
     * Completeness/complaint runtime. Youth values can exist before this is active.
     */
    public function completenessRuntimeActive(): bool
    {
        return false;
    }

    public function complaintWindowDays(): ?int
    {
        return null;
    }

    public function complaintDecisionWindowDays(): ?int
    {
        return null;
    }

    /**
     * Complaint window and complaint-decision deadlines. Women, Youth and canonical share the same
     * legal wording ("within 3 days from sending", "within 7 days from receipt"): exact timestamp + N days.
     */
    public function processDeadlineSemantics(): string
    {
        return self::PROCESS_DEADLINE_EXACT_TIME;
    }

    /**
     * Commission governance "event + N days" deadlines (replacement, third session).
     */
    public function commissionGovernanceDeadlineSemantics(): string
    {
        return self::PROCESS_DEADLINE_EXACT_TIME;
    }

    /**
     * Last valid instant (inclusive) for a complaint.
     */
    public function complaintWindowEndsAt(\DateTimeInterface $noticeSentAt): ?\Carbon\CarbonImmutable
    {
        return $this->processDeadline($noticeSentAt, $this->complaintWindowDays(), $this->processDeadlineSemantics());
    }

    /**
     * Last on-time instant (inclusive) for the Commission decision on a complaint.
     */
    public function complaintDecisionDueAt(\DateTimeInterface $complaintSubmittedAt): ?\Carbon\CarbonImmutable
    {
        return $this->processDeadline($complaintSubmittedAt, $this->complaintDecisionWindowDays(), $this->processDeadlineSemantics());
    }

    public function commissionOrdinaryQuorum(): ?int
    {
        return null;
    }

    public function commissionFullDecisionQuorum(): ?int
    {
        return null;
    }

    public function commissionMandateYears(): ?int
    {
        return null;
    }

    public function commissionReplacementDueDays(): ?int
    {
        return null;
    }

    public function thirdSessionDueDays(): ?int
    {
        return null;
    }

    /**
     * Last on-time instant (inclusive) for filling a vacated Commission seat.
     */
    public function commissionReplacementDueAt(\DateTimeInterface $vacancyTriggeredAt): ?\Carbon\CarbonImmutable
    {
        return $this->processDeadline($vacancyTriggeredAt, $this->commissionReplacementDueDays(), $this->commissionGovernanceDeadlineSemantics());
    }

    /**
     * Last on-time instant (inclusive) for holding the third (final-decision) session.
     */
    public function thirdSessionDueAt(\DateTimeInterface $previousSessionConfirmedAt): ?\Carbon\CarbonImmutable
    {
        return $this->processDeadline($previousSessionConfirmedAt, $this->thirdSessionDueDays(), $this->commissionGovernanceDeadlineSemantics());
    }

    /**
     * Last valid instant (inclusive) of a Commission mandate: the exact anniversary of the start instant.
     * The mandate belongs to the Commission, so replacement members inherit this end.
     * A 29 February start with no anniversary day ends on the last day of February, never in March.
     */
    public function commissionMandateEndsAt(\DateTimeInterface $mandateStartsAt): ?\Carbon\CarbonImmutable
    {
        $years = $this->commissionMandateYears();
        if ($years === null) {
            return null;
        }

        return \Carbon\CarbonImmutable::parse($mandateStartsAt)
            ->timezone($this->publicationTimezone())
            ->addYearsNoOverflow($years);
    }

    private function processDeadline(\DateTimeInterface $start, ?int $days, string $semantics): ?\Carbon\CarbonImmutable
    {
        if ($days === null) {
            return null;
        }

        $deadline = \Carbon\CarbonImmutable::parse($start)->timezone($this->publicationTimezone())->addDays($days);

        return $semantics === self::PROCESS_DEADLINE_END_OF_DAY
            ? $deadline->endOfDay()
            : $deadline;
    }

    public function criterionCount(): int
    {
        return 10;
    }

    public function scoreMinimum(): int
    {
        return 1;
    }

    public function scoreMaximum(): int
    {
        return 5;
    }

    /**
     * Later support threshold. Not an engine constant and not an automatic rejection.
     */
    public function supportScoreThreshold(): ?int
    {
        return null;
    }

    /**
     * Historical women runtime only. Policy-regime scoring must not use this to set rejected.
     */
    public function rejectsScoreBelowThresholdAsStatus(): bool
    {
        return false;
    }

    public function rankingSharesEqualScores(): bool
    {
        return true;
    }

    public function bonusPointsFromFlags(
        bool $infoDay,
        bool $newBusiness,
        bool $longTermUnemployed,
        bool $greenInnovative,
    ): int {
        if (! $this->allowsWomenBonus()) {
            return 0;
        }

        $components = $this->bonusComponents();
        $points = 0;
        if ($infoDay) {
            $points += (int) ($components['info_day'] ?? 0);
        }
        if ($newBusiness) {
            $points += (int) ($components['new_business'] ?? 0);
        }
        if ($longTermUnemployed) {
            $points += (int) ($components['zavod_nezaposleni'] ?? 0);
        }
        if ($greenInnovative) {
            $points += (int) ($components['green_innovative'] ?? 0);
        }

        return $points;
    }

    public function publicationQuarter(): ?int
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function publicationSlots(): array
    {
        return [];
    }

    public function allowsSecondCall(): bool
    {
        return false;
    }

    public function allowsResidualFundsReopen(): bool
    {
        return false;
    }

    /**
     * Once published, the call's identity and application window (profile, year, budget,
     * reference number, start date, status) are fixed for the generic admin edit/delete paths.
     */
    public function locksPublishedCall(): bool
    {
        return false;
    }

    /**
     * Whether the legacy Women/Youth commission flows (Obrazac 3, legacy Prigovor,
     * admin-entered member declarations) may process competitions of this profile.
     */
    public function allowsLegacyCommissionFlows(): bool
    {
        return true;
    }

    public function deadlineDays(): ?int
    {
        return null;
    }

    /**
     * unrestricted: existing profile has no adopted slot/window guard.
     * guarded: publish must satisfy this policy's window, slot, and duration.
     * inactive: profile must not be published or auto-opened.
     */
    public function publicationMode(): string
    {
        return self::PUBLICATION_INACTIVE;
    }

    /**
     * Whether published competitions of this profile may appear on the public competitions index
     * when Catalog is not consulted. Public listing/show MUST use CompetitionProgramCatalog.
     * Fail-closed profiles stay undiscoverable. Inactive publicationMode alone is not the
     * public-visibility SSOT (Catalog active/development is).
     */
    public function isPubliclyDiscoverable(): bool
    {
        if ($this->isFailClosed()) {
            return false;
        }

        return $this->publicationMode() !== self::PUBLICATION_INACTIVE;
    }

    public function publicationTimezone(): string
    {
        return 'Europe/Belgrade';
    }

    public function deadlineDayCounting(): ?string
    {
        return $this->deadlineDays() === null
            ? null
            : self::DAY_COUNT_CALENDAR_AFTER_START;
    }

    public function publicationSlotFor(string $intent): ?string
    {
        $slots = array_values($this->publicationSlots());

        if ($intent === 'primary') {
            return $slots[0] ?? null;
        }

        if ($intent === 'residual' && $this->allowsResidualFundsReopen()) {
            return $slots[1] ?? null;
        }

        return null;
    }

    public function isPublicationAllowedAt(DateTimeInterface $instant): bool
    {
        if ($this->publicationMode() === self::PUBLICATION_UNRESTRICTED) {
            return true;
        }

        if ($this->publicationMode() !== self::PUBLICATION_GUARDED) {
            return false;
        }

        $quarter = $this->publicationQuarter();
        if ($quarter === null || $quarter < 1 || $quarter > 4) {
            return false;
        }

        $month = (int) CarbonImmutable::parse($instant)
            ->timezone($this->publicationTimezone())
            ->month;
        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $quarter * 3;

        return $month >= $startMonth && $month <= $endMonth;
    }

    public function publicationCalendarYear(DateTimeInterface $instant): int
    {
        return (int) CarbonImmutable::parse($instant)
            ->timezone($this->publicationTimezone())
            ->year;
    }

    public function publicationCalendarDate(DateTimeInterface $instant): CarbonImmutable
    {
        return CarbonImmutable::parse($instant)
            ->timezone($this->publicationTimezone())
            ->startOfDay();
    }

    public function publicationEndDate(DateTimeInterface $start): ?CarbonImmutable
    {
        $days = $this->deadlineDays();
        if ($days === null || $this->deadlineDayCounting() !== self::DAY_COUNT_CALENDAR_AFTER_START) {
            return null;
        }

        return CarbonImmutable::parse(
            CarbonImmutable::parse($start)->toDateString()
        )->addDays($days);
    }

    public function allowsWomenBonus(): bool
    {
        return false;
    }

    public function allowsYouthBonus(): bool
    {
        return false;
    }

    /**
     * @return array<string, int>
     */
    public function bonusComponents(): array
    {
        return [];
    }

    public function bonusMaximum(): ?int
    {
        return null;
    }

    /**
     * @return list<int>
     */
    public function financialCaps(): array
    {
        return [];
    }

    public function financialProfileHook(): ?string
    {
        return null;
    }

    public function outcomeRuntimeActive(): bool
    {
        return false;
    }

    /**
     * Authoritative stored business-stage code for business-start priority under
     * the closed PO equal-score / scarce-funds rule (Women + Youth precedent).
     * This is not an MMSP Odluka-prescribed term. Do not substitute other spellings.
     */
    public function businessStartStoredValue(): string
    {
        return 'započinjanje';
    }

    public function budgetShareCapPercent(): ?int
    {
        $caps = $this->financialCaps();

        return $caps[0] ?? null;
    }

    public function investmentCapPercent(?string $subsidyTypeKey): ?int
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function subsidyTypeKeys(): array
    {
        return [];
    }

    public function odlukaPublicationWindowDays(): ?int
    {
        return null;
    }

    public function documentCatalogHook(): ?string
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'profile' => $this->displayName(),
        ];
    }

    public function minimumApplicantAge(): ?int
    {
        return null;
    }

    public function maximumApplicantAge(): ?int
    {
        return null;
    }
}
