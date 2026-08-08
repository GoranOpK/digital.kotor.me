<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use Carbon\CarbonInterface;

/**
 * Sistemsko izvršavanje PO-AUTO-02 + automatsko arhiviranje (BR-065).
 * Redoslijed: 1) završi istekla Planirana Održavanja; 2) arhiviraj podobne Događaje.
 */
final class EventLifecycleMaintenance
{
    public function __construct(
        private readonly OccurrenceLifecycle $occurrenceLifecycle,
        private readonly EventLifecycle $eventLifecycle,
    ) {}

    /**
     * @return array{finished: int, archived: int, skipped_finish: int, skipped_archive: int}
     */
    public function process(?CarbonInterface $now = null, int $chunkSize = 100): array
    {
        $now ??= now((string) config('app.timezone'));

        $finished = $this->finishExpiredOccurrences($now, $chunkSize);
        $archived = $this->archiveEligibleEvents($chunkSize);

        return [
            'finished' => $finished['done'],
            'archived' => $archived['done'],
            'skipped_finish' => $finished['skipped'],
            'skipped_archive' => $archived['skipped'],
        ];
    }

    /**
     * @return array{done: int, skipped: int}
     */
    public function finishExpiredOccurrences(CarbonInterface $now, int $chunkSize = 100): array
    {
        $done = 0;
        $skipped = 0;
        $tz = (string) config('app.timezone');
        $today = $now->copy()->timezone($tz)->toDateString();
        $nowTime = $now->copy()->timezone($tz)->format('H:i:s');

        CulturalOccurrence::query()
            ->where('status', CulturalOccurrence::STATUS_PLANNED)
            ->where(function ($query) use ($today, $nowTime): void {
                $query->where('datum', '<', $today)
                    ->orWhere(function ($sameDay) use ($today, $nowTime): void {
                        $sameDay->where('datum', '=', $today)
                            ->whereNotNull('vrijeme_do')
                            ->whereTime('vrijeme_do', '<', $nowTime);
                    });
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($occurrences) use ($now, &$done, &$skipped): void {
                foreach ($occurrences as $occurrence) {
                    /** @var CulturalOccurrence $occurrence */
                    $result = $this->occurrenceLifecycle->finishIfExpiredAt($occurrence, $now);
                    if ($result !== null) {
                        $done++;
                    } else {
                        $skipped++;
                    }
                }
            });

        return ['done' => $done, 'skipped' => $skipped];
    }

    /**
     * @return array{done: int, skipped: int}
     */
    public function archiveEligibleEvents(int $chunkSize = 100): array
    {
        $done = 0;
        $skipped = 0;

        CulturalEventEntry::query()
            ->whereIn('status', [
                CulturalEventEntry::STATUS_PUBLISHED,
                CulturalEventEntry::STATUS_CANCELLED,
            ])
            ->whereDoesntHave('occurrences', function ($query): void {
                $query->whereIn('status', [
                    CulturalOccurrence::STATUS_PLANNED,
                    CulturalOccurrence::STATUS_POSTPONED,
                ]);
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($entries) use (&$done, &$skipped): void {
                foreach ($entries as $entry) {
                    /** @var CulturalEventEntry $entry */
                    try {
                        $this->eventLifecycle->archiveIfEligible($entry);
                        $done++;
                    } catch (CulturalEventDomainException) {
                        $skipped++;
                    }
                }
            });

        return ['done' => $done, 'skipped' => $skipped];
    }
}
