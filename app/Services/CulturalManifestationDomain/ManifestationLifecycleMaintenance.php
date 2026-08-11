<?php

namespace App\Services\CulturalManifestationDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalManifestation;

final class ManifestationLifecycleMaintenance
{
    public function __construct(
        private readonly ManifestationLifecycle $lifecycle,
        private readonly ManifestationPeriodCalculator $periodCalculator,
    ) {}

    /**
     * @return array{archived: int, skipped: int}
     */
    public function archiveEligibleManifestations(int $chunkSize = 100): array
    {
        $archived = 0;
        $skipped = 0;

        CulturalManifestation::query()
            ->whereIn('status', [
                CulturalManifestation::STATUS_PUBLISHED,
                CulturalManifestation::STATUS_CANCELLED,
            ])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($manifestations) use (&$archived, &$skipped): void {
                foreach ($manifestations as $manifestation) {
                    /** @var CulturalManifestation $manifestation */
                    if (! $this->periodCalculator->hasExpired($manifestation)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $this->lifecycle->archiveIfEligible($manifestation);
                        $archived++;
                    } catch (CulturalEventDomainException) {
                        $skipped++;
                    }
                }
            });

        return [
            'archived' => $archived,
            'skipped' => $skipped,
        ];
    }
}

