<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Jednokratni generator Održavanja na Nacrtu (PO-N-TR-02-04 / TS-004 §3.5).
 * Orkestracija + kalendarska matematika; SSOT validacija/kreiranje = OccurrenceWriter.
 */
final class OccurrenceGenerator
{
    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_MONTHLY = 'monthly';

    public const TYPES = [
        self::TYPE_DAILY,
        self::TYPE_WEEKLY,
        self::TYPE_MONTHLY,
    ];

    public const MAX_COUNT = 100;

    public function __construct(
        private readonly OccurrenceWriter $occurrenceWriter,
    ) {}

    /**
     * @param  array{
     *     recurrence_type: string,
     *     start_date: string,
     *     count?: ?int,
     *     end_date?: ?string,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool,
     *     location_id?: ?int,
     *     location_manual_name?: ?string
     * }  $input
     * @return Collection<int, CulturalOccurrence>
     */
    public function generate(CulturalEventEntry $entry, array $input): Collection
    {
        $type = (string) ($input['recurrence_type'] ?? '');
        if (! in_array($type, self::TYPES, true)) {
            throw new CulturalEventDomainException(
                'Tip ponavljanja mora biti dnevno, sedmično ili mjesečno.'
            );
        }

        $hasCount = array_key_exists('count', $input) && $input['count'] !== null && $input['count'] !== '';
        $hasEnd = array_key_exists('end_date', $input) && $input['end_date'] !== null && $input['end_date'] !== '';

        if ($hasCount === $hasEnd) {
            throw new CulturalEventDomainException(
                'Zadajte tačno jedan završetak: broj Održavanja ili krajnji datum.'
            );
        }

        $tz = (string) config('app.timezone');

        try {
            $start = Carbon::parse((string) $input['start_date'], $tz)->startOfDay();
        } catch (\Throwable) {
            throw new CulturalEventDomainException('Početni datum nije validan.');
        }

        $count = null;
        $end = null;
        if ($hasCount) {
            $count = (int) $input['count'];
            if ($count < 1 || $count > self::MAX_COUNT) {
                throw new CulturalEventDomainException(
                    'Broj Održavanja mora biti između 1 i '.self::MAX_COUNT.'.'
                );
            }
        } else {
            try {
                $end = Carbon::parse((string) $input['end_date'], $tz)->startOfDay();
            } catch (\Throwable) {
                throw new CulturalEventDomainException('Krajnji datum nije validan.');
            }
            if ($end->lt($start)) {
                throw new CulturalEventDomainException(
                    'Krajnji datum ne može biti prije početnog datuma.'
                );
            }
        }

        $dates = $this->computeDates($type, $start, $count, $end);
        if ($dates === []) {
            throw new CulturalEventDomainException('Generator nije proizveo nijedan termin.');
        }
        if (count($dates) > self::MAX_COUNT) {
            throw new CulturalEventDomainException(
                'Jedna operacija generatora može kreirati najviše '.self::MAX_COUNT.' Održavanja.'
            );
        }

        $template = $this->occurrenceWriter->normalizeAndValidate([
            'datum' => $start->toDateString(),
            'vrijeme_od' => $input['vrijeme_od'] ?? null,
            'vrijeme_do' => $input['vrijeme_do'] ?? null,
            'cjelodnevno' => (bool) ($input['cjelodnevno'] ?? false),
            'location_id' => $input['location_id'] ?? null,
            'location_manual_name' => $input['location_manual_name'] ?? null,
        ]);

        $batchFingerprints = [];
        foreach ($dates as $datum) {
            $fp = $this->fingerprint(
                $datum,
                $template['vrijeme_od'],
                $template['vrijeme_do'],
                $template['cjelodnevno'],
                $template['location_id'],
                $template['location_manual_name'],
            );
            if (isset($batchFingerprints[$fp])) {
                throw new CulturalEventDomainException(
                    'Generator ne smije kreirati potpuno identična Održavanja u istoj operaciji.'
                );
            }
            $batchFingerprints[$fp] = true;
        }

        return DB::transaction(function () use ($entry, $dates, $template, $batchFingerprints) {
            /** @var CulturalEventEntry|null $locked */
            $locked = CulturalEventEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw new CulturalEventDomainException('Događaj ne postoji.');
            }

            if (! $locked->isDraft()) {
                throw new CulturalEventDomainException(
                    'Generator Održavanja je dostupan isključivo dok je Događaj Nacrt.'
                );
            }

            /** @var Collection<int, CulturalOccurrence> $existing */
            $existing = CulturalOccurrence::query()
                ->where('event_entry_id', $locked->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($existing as $occurrence) {
                $fp = $this->fingerprintFromOccurrence($occurrence);
                if (isset($batchFingerprints[$fp])) {
                    throw new CulturalEventDomainException(
                        'Generator ne smije kreirati potpuno identično postojeće Održavanje.'
                    );
                }
            }

            $created = collect();
            foreach ($dates as $datum) {
                $created->push($this->occurrenceWriter->create($locked, [
                    'datum' => $datum,
                    'vrijeme_od' => $template['vrijeme_od'],
                    'vrijeme_do' => $template['vrijeme_do'],
                    'cjelodnevno' => $template['cjelodnevno'],
                    'location_id' => $template['location_id'],
                    'location_manual_name' => $template['location_manual_name'],
                ]));
            }

            return $created;
        });
    }

    /**
     * @return list<string> Y-m-d
     */
    public function computeDates(string $type, Carbon $start, ?int $count, ?Carbon $end): array
    {
        $dates = [];

        if ($count !== null) {
            for ($i = 0; $i < $count; $i++) {
                $dates[] = $this->nthDate($type, $start, $i)->toDateString();
            }

            return $dates;
        }

        /** @var Carbon $end */
        for ($i = 0; ; $i++) {
            $cursor = $this->nthDate($type, $start, $i);
            if ($cursor->gt($end)) {
                break;
            }
            $dates[] = $cursor->toDateString();
            if (count($dates) > self::MAX_COUNT) {
                return $dates;
            }
        }

        return $dates;
    }

    private function nthDate(string $type, Carbon $start, int $index): Carbon
    {
        return match ($type) {
            self::TYPE_DAILY => $start->copy()->addDays($index),
            self::TYPE_WEEKLY => $start->copy()->addWeeks($index),
            self::TYPE_MONTHLY => $this->nthMonthlyDate($start, $index),
            default => throw new CulturalEventDomainException('Nepoznat tip ponavljanja.'),
        };
    }

    private function nthMonthlyDate(Carbon $start, int $index): Carbon
    {
        $originalDay = (int) $start->day;
        $monthStart = $start->copy()->startOfMonth()->addMonths($index);
        $day = min($originalDay, $monthStart->daysInMonth);

        return $monthStart->day($day)->startOfDay();
    }

    private function fingerprintFromOccurrence(CulturalOccurrence $occurrence): string
    {
        $datum = $occurrence->datum instanceof Carbon
            ? $occurrence->datum->toDateString()
            : Carbon::parse((string) $occurrence->datum)->toDateString();

        return $this->fingerprint(
            $datum,
            $occurrence->vrijeme_od,
            $occurrence->vrijeme_do,
            (bool) $occurrence->cjelodnevno,
            $occurrence->location_id !== null ? (int) $occurrence->location_id : null,
            $occurrence->location_manual_name,
        );
    }

    private function fingerprint(
        string $datum,
        ?string $vrijemeOd,
        ?string $vrijemeDo,
        bool $cjelodnevno,
        ?int $locationId,
        ?string $manualName,
    ): string {
        $manual = $manualName !== null ? trim($manualName) : '';
        $manual = $manual === '' ? null : $manual;

        $locationKey = $locationId !== null
            ? 'id:'.$locationId
            : 'manual:'.(string) $manual;

        return implode('|', [
            $datum,
            $vrijemeOd ?? '',
            $vrijemeDo ?? '',
            $cjelodnevno ? '1' : '0',
            $locationKey,
        ]);
    }
}
