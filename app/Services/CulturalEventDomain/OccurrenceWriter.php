<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use Illuminate\Support\Facades\DB;

/**
 * Kreiranje / ažuriranje / fizičko uklanjanje Održavanja (TS-004).
 */
final class OccurrenceWriter
{
    /**
     * @param  array{
     *     datum: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool,
     *     location_id?: ?int,
     *     location_manual_name?: ?string
     * }  $data
     */
    public function create(CulturalEventEntry $entry, array $data): CulturalOccurrence
    {
        if ($entry->isCancelled() || $entry->status === CulturalEventEntry::STATUS_ARCHIVED) {
            throw new CulturalEventDomainException(
                'Održavanje se ne može dodati na otkazan ili arhiviran Događaj.'
            );
        }

        $normalized = $this->normalizeAndValidate($data);

        return CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => $normalized['datum'],
            'vrijeme_od' => $normalized['vrijeme_od'],
            'vrijeme_do' => $normalized['vrijeme_do'],
            'cjelodnevno' => $normalized['cjelodnevno'],
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_id' => $normalized['location_id'],
            'location_manual_name' => $normalized['location_manual_name'],
        ]);
    }

    /**
     * @param  array{
     *     datum?: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool,
     *     location_id?: ?int,
     *     location_manual_name?: ?string
     * }  $data
     */
    public function update(CulturalOccurrence $occurrence, array $data): CulturalOccurrence
    {
        $merged = [
            'datum' => $data['datum'] ?? $occurrence->datum,
            'vrijeme_od' => array_key_exists('vrijeme_od', $data) ? $data['vrijeme_od'] : $occurrence->vrijeme_od,
            'vrijeme_do' => array_key_exists('vrijeme_do', $data) ? $data['vrijeme_do'] : $occurrence->vrijeme_do,
            'cjelodnevno' => array_key_exists('cjelodnevno', $data) ? (bool) $data['cjelodnevno'] : $occurrence->cjelodnevno,
            'location_id' => array_key_exists('location_id', $data) ? $data['location_id'] : $occurrence->location_id,
            'location_manual_name' => array_key_exists('location_manual_name', $data)
                ? $data['location_manual_name']
                : $occurrence->location_manual_name,
        ];

        $locationChanging = array_key_exists('location_id', $data)
            && (int) $data['location_id'] !== (int) $occurrence->location_id;

        $normalized = $this->normalizeAndValidate($merged, validateNewLocation: $locationChanging);

        $occurrence->fill($normalized);
        $occurrence->save();

        return $occurrence->fresh(['location', 'eventEntry']);
    }

    /**
     * Fizičko uklanjanje samo dok je Događaj Nacrt i nije ušao u urednički tok (N-TR-04).
     */
    public function deletePhysically(CulturalOccurrence $occurrence): void
    {
        $entry = $occurrence->eventEntry;
        if ($entry === null) {
            throw new CulturalEventDomainException('Održavanje nema Događaj.');
        }

        if (! $entry->isDraft() || $entry->hasEnteredEditorialFlow()) {
            throw new CulturalEventDomainException(
                'Fizičko uklanjanje Održavanja dozvoljeno je samo dok je Događaj Nacrt i nije bio u uredničkom postupku.'
            );
        }

        $occurrence->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     datum: string,
     *     vrijeme_od: ?string,
     *     vrijeme_do: ?string,
     *     cjelodnevno: bool,
     *     location_id: ?int,
     *     location_manual_name: ?string
     * }
     */
    public function normalizeAndValidate(array $data, bool $validateNewLocation = true): array
    {
        if (! isset($data['datum']) || $data['datum'] === null || $data['datum'] === '') {
            throw new CulturalEventDomainException('Datum Održavanja je obavezan.');
        }

        try {
            $datum = \Illuminate\Support\Carbon::parse($data['datum'])->toDateString();
        } catch (\Throwable) {
            throw new CulturalEventDomainException('Datum Održavanja nije validan.');
        }

        $cjelodnevno = (bool) ($data['cjelodnevno'] ?? false);
        $vrijemeOd = $this->normalizeTime($data['vrijeme_od'] ?? null);
        $vrijemeDo = $this->normalizeTime($data['vrijeme_do'] ?? null);

        if ($cjelodnevno) {
            if ($vrijemeOd !== null || $vrijemeDo !== null) {
                throw new CulturalEventDomainException(
                    'Cjelodnevno Održavanje ne smije imati vrijeme od/do.'
                );
            }
        } else {
            if ($vrijemeDo !== null && $vrijemeOd === null) {
                throw new CulturalEventDomainException(
                    'Vrijeme završetka ne može postojati bez vremena početka.'
                );
            }

            if ($vrijemeOd !== null && $vrijemeDo !== null && $vrijemeDo <= $vrijemeOd) {
                throw new CulturalEventDomainException(
                    'Vrijeme završetka mora biti nakon vremena početka.'
                );
            }
        }

        $locationId = isset($data['location_id']) && $data['location_id'] !== null && $data['location_id'] !== ''
            ? (int) $data['location_id']
            : null;
        $manual = isset($data['location_manual_name'])
            ? trim((string) $data['location_manual_name'])
            : '';
        $manual = $manual === '' ? null : $manual;

        if ($locationId !== null && $manual !== null) {
            throw new CulturalEventDomainException(
                'Lokacija može biti kataloška ili ručni naziv, ne oboje.'
            );
        }

        if ($locationId !== null && $validateNewLocation) {
            $location = CulturalLocation::query()->find($locationId);
            if ($location === null) {
                throw new CulturalEventDomainException('Lokacija ne postoji.');
            }
            if (! $location->isActive()) {
                throw new CulturalEventDomainException(
                    'Deaktivirana Lokacija ne može biti izabrana za novu vezu.'
                );
            }
        }

        return [
            'datum' => $datum,
            'vrijeme_od' => $vrijemeOd,
            'vrijeme_do' => $vrijemeDo,
            'cjelodnevno' => $cjelodnevno,
            'location_id' => $locationId,
            'location_manual_name' => $manual,
        ];
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
            throw new CulturalEventDomainException('Vrijeme nije u validnom formatu.');
        }

        $h = (int) $m[1];
        $i = (int) $m[2];
        $s = isset($m[3]) ? (int) $m[3] : 0;

        if ($h > 23 || $i > 59 || $s > 59) {
            throw new CulturalEventDomainException('Vrijeme nije validno.');
        }

        return sprintf('%02d:%02d:%02d', $h, $i, $s);
    }
}
