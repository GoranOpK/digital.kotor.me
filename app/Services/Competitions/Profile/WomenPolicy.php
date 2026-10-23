<?php

namespace App\Services\Competitions\Profile;

final class WomenPolicy extends CompetitionProfilePolicy
{
    public function key(): string
    {
        return CompetitionProfileResolver::TYPE_WOMEN;
    }

    public function displayName(): string
    {
        return 'Žensko preduzetništvo';
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function requiresKotorAddress(): bool
    {
        return true;
    }

    /**
     * Adopted size for new competitions. Current women runtime still reads
     * historical 5-member commissions; that behavior is not migrated here.
     */
    public function commissionSize(): ?int
    {
        return 3;
    }

    public function historicalCommissionSize(): ?int
    {
        return 5;
    }

    public function scoringRuntimeActive(): bool
    {
        return true;
    }

    public function supportScoreThreshold(): ?int
    {
        return 30;
    }

    public function rejectsScoreBelowThresholdAsStatus(): bool
    {
        return true;
    }

    /**
     * No adopted women publication window or slot. Do not inherit canonical Q3
     * or once-per-year uniqueness merely because the same engine is used.
     */
    public function publicationMode(): string
    {
        return self::PUBLICATION_UNRESTRICTED;
    }

    public function deadlineDays(): ?int
    {
        return 20;
    }

    public function allowsWomenBonus(): bool
    {
        return true;
    }

    public function bonusComponents(): array
    {
        return [
            'info_day' => 1,
            'new_business' => 2,
            'zavod_nezaposleni' => 2,
            'green_innovative' => 3,
        ];
    }

    public function financialProfileHook(): ?string
    {
        return 'women_specific';
    }

    public function documentCatalogHook(): ?string
    {
        return 'women_document_catalog';
    }
}
