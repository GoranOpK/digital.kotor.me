<?php

namespace App\Services\Competitions\Profile;

final class CanonicalMmspPolicy extends CompetitionProfilePolicy
{
    /** Document catalog key reserved for a later wave. Not wired in KN-SYNC-002. */
    public const DOCUMENT_CATALOG_HOOK = 'canonical_mmsp_document_catalog';

    public function key(): string
    {
        return CompetitionProfileResolver::TYPE_CANONICAL;
    }

    public function displayName(): string
    {
        return 'Preduzetnici mikro, mala i srednja preduzeća';
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
     * Individual scoring, aggregate and preliminary ranking (KN-SYNC-007). Third-session outcome,
     * amounts and the private Predlog foundation run in App\Services\Competitions\Outcome (KN-SYNC-008).
     */
    public function scoringRuntimeActive(): bool
    {
        return true;
    }

    public function commissionOrdinaryQuorum(): ?int
    {
        return 2;
    }

    public function commissionFullDecisionQuorum(): ?int
    {
        return 3;
    }

    public function commissionMandateYears(): ?int
    {
        return 1;
    }

    public function commissionReplacementDueDays(): ?int
    {
        return 15;
    }

    public function thirdSessionDueDays(): ?int
    {
        return 7;
    }

    public function completenessRuntimeActive(): bool
    {
        return true;
    }

    public function outcomeRuntimeActive(): bool
    {
        return true;
    }

    public const SUBSIDY_AUTHENTIC_LOCAL = 'autenticni_lokalni_proizvodi_usluge';

    public const SUBSIDY_INNOVATIVE = 'inovativni_proizvodi_usluge';

    public const SUBSIDY_DIGITALIZATION = 'digitalizacija';

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

    public function publicationMode(): string
    {
        return self::PUBLICATION_GUARDED;
    }

    public function publicationQuarter(): ?int
    {
        return 3;
    }

    public function publicationSlots(): array
    {
        return ['annual_call'];
    }

    public function locksPublishedCall(): bool
    {
        return true;
    }

    public function allowsLegacyCommissionFlows(): bool
    {
        return false;
    }

    public function deadlineDays(): ?int
    {
        return 20;
    }

    public function financialCaps(): array
    {
        return [20, 50, 80];
    }

    public function financialProfileHook(): ?string
    {
        return 'canonical_20_50_80';
    }

    public function investmentCapPercent(?string $subsidyTypeKey): ?int
    {
        $caps = $this->financialCaps();
        $general = $caps[1] ?? null;
        $authentic = $caps[2] ?? null;

        return match ($subsidyTypeKey) {
            self::SUBSIDY_AUTHENTIC_LOCAL => $authentic,
            self::SUBSIDY_INNOVATIVE, self::SUBSIDY_DIGITALIZATION => $general,
            default => null,
        };
    }

    public function subsidyTypeKeys(): array
    {
        return [
            self::SUBSIDY_AUTHENTIC_LOCAL,
            self::SUBSIDY_INNOVATIVE,
            self::SUBSIDY_DIGITALIZATION,
        ];
    }

    public function odlukaPublicationWindowDays(): ?int
    {
        return 45;
    }

    public function documentCatalogHook(): ?string
    {
        return self::DOCUMENT_CATALOG_HOOK;
    }
}
