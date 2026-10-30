<?php

namespace App\Services\Competitions\Profile;

/**
 * Policy inputs from KN-BM-002 v1.0.2. Runtime stays in development.
 */
final class YouthPolicy extends CompetitionProfilePolicy
{
    public function key(): string
    {
        return CompetitionProfileResolver::TYPE_YOUTH;
    }

    public function displayName(): string
    {
        return 'Preduzetništvo mladih';
    }

    public function requiresKotorAddress(): bool
    {
        return true;
    }

    public function commissionSize(): ?int
    {
        return 3;
    }

    /**
     * Policy records Commission = 3. Runtime scoring and ranking stay inactive.
     */
    public function scoringRuntimeActive(): bool
    {
        return false;
    }

    /**
     * Youth outcome, caps, and catalog stay inactive. Do not import them into canonical.
     */
    public function outcomeRuntimeActive(): bool
    {
        return false;
    }

    /**
     * Youth BM records 3/7 day windows. Completeness runtime stays inactive.
     */

    public function complaintWindowDays(): ?int
    {
        return 3;
    }

    public function complaintDecisionWindowDays(): ?int
    {
        return 7;
    }

    public function supportScoreThreshold(): ?int
    {
        return 30;
    }

    /**
     * Youth slots and Q2 stay profile data. Publication runtime is not active.
     * A residual second call must not be created or published from the primary path.
     */
    public function publicationMode(): string
    {
        return self::PUBLICATION_INACTIVE;
    }

    public function publicationQuarter(): ?int
    {
        return 2;
    }

    public function publicationSlots(): array
    {
        return ['first_call', 'second_call'];
    }

    public function allowsSecondCall(): bool
    {
        return true;
    }

    public function allowsResidualFundsReopen(): bool
    {
        return true;
    }

    public function deadlineDays(): ?int
    {
        return 20;
    }

    public function allowsYouthBonus(): bool
    {
        return true;
    }

    public function bonusComponents(): array
    {
        return [
            'info_day_and_training' => 1,
            'new_business' => 2,
            'green_innovative' => 3,
        ];
    }

    public function bonusMaximum(): ?int
    {
        return 6;
    }

    public function financialCaps(): array
    {
        return [30, 20, 15];
    }

    public function financialProfileHook(): ?string
    {
        return 'youth_30_20_15';
    }

    public function documentCatalogHook(): ?string
    {
        return 'youth_document_catalog';
    }

    public function minimumApplicantAge(): ?int
    {
        return 18;
    }

    public function maximumApplicantAge(): ?int
    {
        return 30;
    }
}
