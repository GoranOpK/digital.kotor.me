<?php

namespace App\Services\CulturalManifestationDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ManifestationWriter
{
    public function __construct(
        private readonly ManifestationCatalogGuard $catalogGuard,
    ) {}

    /**
     * @param  array{
     *     naziv: string,
     *     opis?: ?string,
     *     organizer_id?: ?int,
     *     cover_media_id?: ?int,
     *     web_stranica?: ?string,
     *     event_entry_ids?: list<int>
     * }  $data
     */
    public function createDraft(User $creator, array $data): CulturalManifestation
    {
        $naziv = trim((string) ($data['naziv'] ?? ''));
        if ($naziv === '') {
            throw new CulturalEventDomainException('Naziv Manifestacije je obavezan.');
        }

        $organizerId = $data['organizer_id'] ?? null;
        $coverMediaId = $data['cover_media_id'] ?? null;
        $eventIds = array_values(array_unique(array_map('intval', $data['event_entry_ids'] ?? [])));

        $this->catalogGuard->assertOrganizerAllowedForNewLink($organizerId);
        $this->catalogGuard->assertCoverMediaAllowedForNewLink($coverMediaId);

        return DB::transaction(function () use ($creator, $data, $naziv, $organizerId, $coverMediaId, $eventIds) {
            $manifestation = CulturalManifestation::create([
                'naziv' => $naziv,
                'opis' => $this->normalizeNullableText($data['opis'] ?? null),
                'status' => CulturalManifestation::STATUS_DRAFT,
                'organizer_id' => $organizerId,
                'cover_media_id' => $coverMediaId,
                'web_stranica' => $this->normalizeNullableText($data['web_stranica'] ?? null),
                'created_by' => $creator->id,
                'last_modified_by' => $creator->id,
            ]);

            if ($eventIds !== []) {
                $this->linkEventsInternal($manifestation, $eventIds, $creator->id);
            }

            return $manifestation->fresh(['events', 'organizer', 'coverMedia']);
        });
    }

    /**
     * @param  array{
     *     naziv?: ?string,
     *     opis?: ?string,
     *     organizer_id?: ?int,
     *     cover_media_id?: ?int,
     *     web_stranica?: ?string
     * }  $data
     */
    public function updateContent(CulturalManifestation $manifestation, User $actor, array $data): CulturalManifestation
    {
        if ($manifestation->isArchived()) {
            throw new CulturalEventDomainException('Arhivirana Manifestacija se ne može uređivati.');
        }

        $organizerId = array_key_exists('organizer_id', $data) ? $data['organizer_id'] : $manifestation->organizer_id;
        $coverMediaId = array_key_exists('cover_media_id', $data) ? $data['cover_media_id'] : $manifestation->cover_media_id;

        $this->catalogGuard->assertOrganizerAllowedForNewLink($organizerId);
        $this->catalogGuard->assertCoverMediaAllowedForNewLink($coverMediaId);

        return DB::transaction(function () use ($manifestation, $actor, $data, $organizerId, $coverMediaId) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isArchived()) {
                throw new CulturalEventDomainException('Arhivirana Manifestacija se ne može uređivati.');
            }

            if (array_key_exists('naziv', $data)) {
                $naziv = trim((string) $data['naziv']);
                if ($naziv === '') {
                    throw new CulturalEventDomainException('Naziv Manifestacije je obavezan.');
                }
                $locked->naziv = $naziv;
            }

            if (array_key_exists('opis', $data)) {
                $locked->opis = $this->normalizeNullableText($data['opis']);
            }

            if (array_key_exists('web_stranica', $data)) {
                $locked->web_stranica = $this->normalizeNullableText($data['web_stranica']);
            }

            if (array_key_exists('organizer_id', $data)) {
                $locked->organizer_id = $organizerId;
            }

            if (array_key_exists('cover_media_id', $data)) {
                $locked->cover_media_id = $coverMediaId;
            }

            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh(['events', 'organizer', 'coverMedia']);
        });
    }

    public function linkEvent(CulturalManifestation $manifestation, int $eventEntryId, User $actor): CulturalManifestation
    {
        return DB::transaction(function () use ($manifestation, $eventEntryId, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->linkEventsInternal($locked, [$eventEntryId], $actor->id);
            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh(['events']);
        });
    }

    public function unlinkEvent(CulturalManifestation $manifestation, int $eventEntryId, User $actor): CulturalManifestation
    {
        return DB::transaction(function () use ($manifestation, $eventEntryId, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($eventEntryId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $entry->manifestation_id !== (int) $locked->id) {
                throw new CulturalEventDomainException('Događaj nije povezan sa traženom Manifestacijom.');
            }

            if ($locked->isPublished() && $entry->status === CulturalEventEntry::STATUS_PUBLISHED) {
                $remainingPublished = CulturalEventEntry::query()
                    ->where('manifestation_id', $locked->id)
                    ->where('status', CulturalEventEntry::STATUS_PUBLISHED)
                    ->where('id', '!=', $entry->id)
                    ->count();

                if ($remainingPublished < 1) {
                    throw new CulturalEventDomainException(
                        'Objavljena Manifestacija mora zadržati najmanje jedan Objavljeni Događaj.'
                    );
                }
            }

            $entry->manifestation_id = null;
            $entry->save();

            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh(['events']);
        });
    }

    /**
     * @param  list<int>  $eventIds
     */
    private function linkEventsInternal(CulturalManifestation $manifestation, array $eventIds, int $actorId): void
    {
        foreach ($eventIds as $eventId) {
            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->manifestation_id !== null && (int) $entry->manifestation_id !== (int) $manifestation->id) {
                throw new CulturalEventDomainException(
                    'Događaj već pripada drugoj Manifestaciji i ne može biti višestruko povezan.'
                );
            }

            $entry->manifestation_id = $manifestation->id;
            $entry->last_modified_by = $actorId;
            $entry->save();
        }
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}

