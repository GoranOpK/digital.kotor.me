<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Services\Newsletter\NewsletterPriorityChangeRecorder;

/**
 * Kreiranje / ažuriranje / fizičko uklanjanje Održavanja (TS-004).
 */
final class OccurrenceWriter
{
    public function __construct(
        private readonly NewsletterPriorityChangeRecorder $priorityChangeRecorder,
    ) {}

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
        if ($entry->isPendingApproval()) {
            throw new CulturalEventDomainException(
                'Događaj na odobrenju je zaključan; Održavanje se ne može dodati.'
            );
        }

        if ($entry->isPublished()) {
            throw new CulturalEventDomainException(
                'Na objavljenom Događaju Održavanje se dodaje isključivo kroz prijedlog izmjene.'
            );
        }

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
        $entry = $occurrence->eventEntry;
        if ($entry !== null && $entry->isPendingApproval()) {
            throw new CulturalEventDomainException(
                'Događaj na odobrenju je zaključan; Održavanje se ne može mijenjati.'
            );
        }

        if ($entry !== null && $entry->isPublished()) {
            throw new CulturalEventDomainException(
                'Na objavljenom Događaju podaci Održavanja se mijenjaju isključivo kroz prijedlog izmjene.'
            );
        }

        if ($entry !== null && $entry->isCancelled()) {
            throw new CulturalEventDomainException(
                'Otkazan Događaj je istorijski zapis; Održavanje se ne može mijenjati.'
            );
        }

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

        if ($entry->isPendingApproval()) {
            throw new CulturalEventDomainException(
                'Događaj na odobrenju je zaključan; Održavanje se ne može ukloniti.'
            );
        }

        if ($entry->isCancelled()) {
            throw new CulturalEventDomainException(
                'Otkazan Događaj je istorijski zapis; Održavanje se ne može ukloniti.'
            );
        }

        if (! $entry->isDraft() || $entry->hasEnteredEditorialFlow()) {
            throw new CulturalEventDomainException(
                'Fizičko uklanjanje Održavanja dozvoljeno je samo dok je Događaj Nacrt i nije bio u uredničkom postupku.'
            );
        }

        $occurrence->delete();
    }

    /**
     * TS-010.3b — primjena add iz odobrenog prijedloga (Preskače Published lock).
     *
     * @param  array{
     *     datum: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool,
     *     location_id?: ?int,
     *     location_manual_name?: ?string
     * }  $data
     */
    public function applyCreateFromApprovedProposal(CulturalEventEntry $entry, array $data): CulturalOccurrence
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
     * Statusni tok Odgođen → Planiran: samo termin. Ne dira Lokaciju ni status.
     * Preskače Published lock (izuzev BR-061 statusnog toka); generalni update() ostaje zaključan.
     *
     * @param  array{
     *     datum: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool
     * }  $termin
     */
    public function applyTerminFromLifecycle(CulturalOccurrence $occurrence, array $termin): CulturalOccurrence
    {
        $entry = $occurrence->eventEntry;
        if ($entry !== null && $entry->isCancelled()) {
            throw new CulturalEventDomainException(
                'Otkazan Događaj je istorijski zapis; Održavanje se ne može mijenjati.'
            );
        }

        if ($entry !== null && $entry->status === CulturalEventEntry::STATUS_ARCHIVED) {
            throw new CulturalEventDomainException(
                'Arhiviran Događaj; Održavanje se ne može mijenjati.'
            );
        }

        if (array_key_exists('location_id', $termin) || array_key_exists('location_manual_name', $termin)) {
            throw new CulturalEventDomainException(
                'Lokacija se ne mijenja kroz statusni tok Održavanja.'
            );
        }

        if (array_key_exists('status', $termin)) {
            throw new CulturalEventDomainException(
                'Status Održavanja se ne postavlja kroz upis termina.'
            );
        }

        $merged = [
            'datum' => $termin['datum'] ?? $occurrence->datum,
            'vrijeme_od' => array_key_exists('vrijeme_od', $termin) ? $termin['vrijeme_od'] : $occurrence->vrijeme_od,
            'vrijeme_do' => array_key_exists('vrijeme_do', $termin) ? $termin['vrijeme_do'] : $occurrence->vrijeme_do,
            'cjelodnevno' => array_key_exists('cjelodnevno', $termin)
                ? (bool) $termin['cjelodnevno']
                : $occurrence->cjelodnevno,
            'location_id' => $occurrence->location_id,
            'location_manual_name' => $occurrence->location_manual_name,
        ];

        $normalized = $this->normalizeAndValidate($merged, validateNewLocation: false);

        $occurrence->datum = $normalized['datum'];
        $occurrence->vrijeme_od = $normalized['vrijeme_od'];
        $occurrence->vrijeme_do = $normalized['vrijeme_do'];
        $occurrence->cjelodnevno = $normalized['cjelodnevno'];
        $occurrence->save();

        return $occurrence->fresh(['location', 'eventEntry']);
    }

    /**
     * TS-010.3b — primjena update podataka iz odobrenog prijedloga (status ostaje; Preskače Published lock).
     *
     * @param  array{
     *     datum?: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool,
     *     location_id?: ?int,
     *     location_manual_name?: ?string
     * }  $data
     */
    public function applyUpdateFromApprovedProposal(CulturalOccurrence $occurrence, array $data): CulturalOccurrence
    {
        $entry = $occurrence->eventEntry;
        if ($entry !== null && $entry->isCancelled()) {
            throw new CulturalEventDomainException(
                'Otkazan Događaj je istorijski zapis; Održavanje se ne može mijenjati.'
            );
        }

        $occurrence->loadMissing('eventEntry');

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
            && (int) ($data['location_id'] ?? 0) !== (int) $occurrence->location_id;

        $normalized = $this->normalizeAndValidate($merged, validateNewLocation: $locationChanging);

        $before = $occurrence->replicate();
        $before->id = $occurrence->id;
        $before->event_entry_id = $occurrence->event_entry_id;
        $before->setRelation('eventEntry', $occurrence->eventEntry);

        $occurrence->fill($normalized);
        $occurrence->save();

        $fresh = $occurrence->fresh(['location', 'eventEntry']);
        $this->priorityChangeRecorder->recordPublishedOccurrenceFieldChanges($before, $fresh);

        return $fresh;
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
