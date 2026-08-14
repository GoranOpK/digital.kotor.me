<?php

namespace App\Services\CulturalManifestationDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use Illuminate\Support\Facades\DB;

final class ManifestationWriter
{
    /**
     * PO-6B-07 — statusi Događaja dozvoljeni za NEW LINK / move u MF.
     * Event „vraćen na doradu“ je u kanonskom modelu draft (nema zasebnog returned statusa).
     *
     * @var list<string>
     */
    public const NEW_LINK_ELIGIBLE_EVENT_STATUSES = [
        CulturalEventEntry::STATUS_DRAFT,
        CulturalEventEntry::STATUS_PENDING_APPROVAL,
        CulturalEventEntry::STATUS_PUBLISHED,
    ];

    public function __construct(
        private readonly ManifestationCatalogGuard $catalogGuard,
        private readonly CulturalActivityEmitter $activityEmitter,
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

        $created = DB::transaction(function () use ($creator, $data, $naziv, $organizerId, $coverMediaId, $eventIds) {
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

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::MF_01,
            CulturalActivityEventId::once(CulturalActivityCatalog::MF_01, (int) $created->id),
            $creator,
            (int) $created->id,
            $created->created_at ?? now(),
            ['manifestation_id' => (int) $created->id],
            $created->organizer_id !== null ? (int) $created->organizer_id : null,
        );

        return $created;
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
        $this->assertCanMutateContent($manifestation);

        $organizerChanging = array_key_exists('organizer_id', $data)
            && (int) ($data['organizer_id'] ?? 0) !== (int) ($manifestation->organizer_id ?? 0);
        $coverChanging = array_key_exists('cover_media_id', $data)
            && (int) ($data['cover_media_id'] ?? 0) !== (int) ($manifestation->cover_media_id ?? 0);

        $organizerId = array_key_exists('organizer_id', $data) ? $data['organizer_id'] : $manifestation->organizer_id;
        $coverMediaId = array_key_exists('cover_media_id', $data) ? $data['cover_media_id'] : $manifestation->cover_media_id;

        if ($organizerChanging) {
            $this->catalogGuard->assertOrganizerAllowedForNewLink($organizerId);
        }
        if ($coverChanging) {
            $this->catalogGuard->assertCoverMediaAllowedForNewLink($coverMediaId);
        }

        $webBefore = (string) ($manifestation->web_stranica ?? '');
        $organizerBefore = $manifestation->organizer_id !== null ? (int) $manifestation->organizer_id : null;
        $coverBefore = $manifestation->cover_media_id !== null ? (int) $manifestation->cover_media_id : null;

        $persistAt = now();
        $updated = DB::transaction(function () use ($manifestation, $actor, $data, $organizerId, $coverMediaId, &$persistAt) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanMutateContent($locked);

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
            $persistAt = $locked->updated_at?->copy() ?? now();

            return $locked->fresh(['events', 'organizer', 'coverMedia']);
        });

        if ($organizerChanging) {
            $this->activityEmitter->emitUser(
                CulturalActivityCatalog::MF_10,
                CulturalActivityEventId::repeatable(
                    CulturalActivityCatalog::MF_10,
                    (int) $updated->id,
                    [
                        'from' => $organizerBefore,
                        'to' => $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
                    ],
                    $persistAt
                ),
                $actor,
                (int) $updated->id,
                $persistAt,
                [
                    'manifestation_id' => (int) $updated->id,
                    'organizer_id' => $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
                ],
                $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
            );
        }
        if ($coverChanging) {
            $this->activityEmitter->emitUser(
                CulturalActivityCatalog::MF_11,
                CulturalActivityEventId::repeatable(
                    CulturalActivityCatalog::MF_11,
                    (int) $updated->id,
                    [
                        'from' => $coverBefore,
                        'to' => $updated->cover_media_id !== null ? (int) $updated->cover_media_id : null,
                    ],
                    $persistAt
                ),
                $actor,
                (int) $updated->id,
                $persistAt,
                ['manifestation_id' => (int) $updated->id],
                $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
            );
        }
        if (array_key_exists('web_stranica', $data)
            && $webBefore !== (string) ($updated->web_stranica ?? '')
        ) {
            $this->activityEmitter->emitUser(
                CulturalActivityCatalog::MF_12,
                CulturalActivityEventId::repeatable(
                    CulturalActivityCatalog::MF_12,
                    (int) $updated->id,
                    [
                        'from' => $webBefore,
                        'to' => (string) ($updated->web_stranica ?? ''),
                    ],
                    $persistAt
                ),
                $actor,
                (int) $updated->id,
                $persistAt,
                ['manifestation_id' => (int) $updated->id],
                $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
            );
        }

        return $updated;
    }

    public function linkEvent(CulturalManifestation $manifestation, int $eventEntryId, User $actor): CulturalManifestation
    {
        $persistAt = now();
        $linked = DB::transaction(function () use ($manifestation, $eventEntryId, $actor, &$persistAt) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanMutateLinks($locked);
            $this->linkEventsInternal($locked, [$eventEntryId], $actor->id);
            $locked->last_modified_by = $actor->id;
            $locked->save();
            $persistAt = $locked->updated_at?->copy() ?? now();

            return $locked->fresh(['events', 'events.organizer']);
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::MF_07,
            CulturalActivityEventId::repeatable(
                CulturalActivityCatalog::MF_07,
                (int) $linked->id,
                ['entry_id' => $eventEntryId],
                $persistAt
            ),
            $actor,
            (int) $linked->id,
            $linked->updated_at ?? now(),
            [
                'manifestation_id' => (int) $linked->id,
                'entry_id' => $eventEntryId,
            ],
            $linked->organizer_id !== null ? (int) $linked->organizer_id : null,
        );

        return $linked;
    }

    public function unlinkEvent(CulturalManifestation $manifestation, int $eventEntryId, User $actor): CulturalManifestation
    {
        $persistAt = now();
        $unlinked = DB::transaction(function () use ($manifestation, $eventEntryId, $actor, &$persistAt) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanMutateLinks($locked);

            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($eventEntryId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $entry->manifestation_id !== (int) $locked->id) {
                throw new CulturalEventDomainException('Događaj nije povezan sa traženom Manifestacijom.');
            }

            $this->assertUnlinkKeepsPublishedInvariant($locked, $entry);

            $entry->manifestation_id = null;
            $entry->save();

            $locked->last_modified_by = $actor->id;
            $locked->save();
            $persistAt = $locked->updated_at?->copy() ?? now();

            return $locked->fresh(['events', 'events.organizer']);
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::MF_08,
            CulturalActivityEventId::repeatable(
                CulturalActivityCatalog::MF_08,
                (int) $unlinked->id,
                ['entry_id' => $eventEntryId],
                $persistAt
            ),
            $actor,
            (int) $unlinked->id,
            $unlinked->updated_at ?? now(),
            [
                'manifestation_id' => (int) $unlinked->id,
                'entry_id' => $eventEntryId,
            ],
            $unlinked->organizer_id !== null ? (int) $unlinked->organizer_id : null,
        );

        return $unlinked;
    }

    /**
     * BR-201 — atomsko premještanje Događaja iz MF A u MF B.
     */
    public function moveEvent(
        CulturalManifestation $targetManifestation,
        int $eventEntryId,
        User $actor,
    ): CulturalManifestation {
        $fromId = 0;
        $persistAt = now();
        $moved = DB::transaction(function () use ($targetManifestation, $eventEntryId, $actor, &$fromId, &$persistAt) {
            /** @var CulturalManifestation $target */
            $target = CulturalManifestation::query()
                ->whereKey($targetManifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanMutateLinks($target);

            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($eventEntryId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertEventEligibleForNewLink($entry);

            if ($entry->manifestation_id === null) {
                throw new CulturalEventDomainException(
                    'Događaj nije povezan sa Manifestacijom; koristite povezivanje umjesto premještanja.'
                );
            }

            if ((int) $entry->manifestation_id === (int) $target->id) {
                throw new CulturalEventDomainException('Događaj je već povezan sa ovom Manifestacijom.');
            }

            $fromId = (int) $entry->manifestation_id;

            /** @var CulturalManifestation $source */
            $source = CulturalManifestation::query()
                ->whereKey($entry->manifestation_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertUnlinkKeepsPublishedInvariant($source, $entry);

            $entry->manifestation_id = $target->id;
            $entry->last_modified_by = $actor->id;
            $entry->save();

            $source->last_modified_by = $actor->id;
            $source->save();

            $target->last_modified_by = $actor->id;
            $target->save();
            $persistAt = $target->updated_at?->copy() ?? now();

            return $target->fresh(['events', 'events.organizer']);
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::MF_09,
            CulturalActivityEventId::repeatable(
                CulturalActivityCatalog::MF_09,
                (int) $moved->id,
                [
                    'from' => $fromId,
                    'to' => (int) $moved->id,
                    'entry_id' => $eventEntryId,
                ],
                $persistAt
            ),
            $actor,
            (int) $moved->id,
            $moved->updated_at ?? now(),
            [
                'from_id' => $fromId,
                'to_id' => (int) $moved->id,
                'entry_id' => $eventEntryId,
            ],
            $moved->organizer_id !== null ? (int) $moved->organizer_id : null,
        );

        return $moved;
    }

    public static function isEventEligibleForNewLink(CulturalEventEntry $entry): bool
    {
        return in_array($entry->status, self::NEW_LINK_ELIGIBLE_EVENT_STATUSES, true);
    }

    public function assertEventEligibleForNewLink(CulturalEventEntry $entry): void
    {
        if (! self::isEventEligibleForNewLink($entry)) {
            throw new CulturalEventDomainException(
                'Događaj u statusu '.$entry->statusLabel().' ne može biti novo povezan ili premješten u Manifestaciju.'
            );
        }
    }

    public function assertCanMutateContent(CulturalManifestation $manifestation): void
    {
        if ($manifestation->isPendingApproval()) {
            throw new CulturalEventDomainException(
                'Manifestacija na odobrenju je zaključana i ne može se uređivati.'
            );
        }

        if ($manifestation->isCancelled()) {
            throw new CulturalEventDomainException('Otkazana Manifestacija se ne može uređivati.');
        }

        if ($manifestation->isArchived()) {
            throw new CulturalEventDomainException('Arhivirana Manifestacija se ne može uređivati.');
        }
    }

    public function assertCanMutateLinks(CulturalManifestation $manifestation): void
    {
        if ($manifestation->isPendingApproval()) {
            throw new CulturalEventDomainException(
                'Manifestacija na odobrenju je zaključana; povezivanje Događaja nije dozvoljeno.'
            );
        }

        if ($manifestation->isCancelled()) {
            throw new CulturalEventDomainException(
                'Otkazana Manifestacija ne dozvoljava izmjenu povezanih Događaja.'
            );
        }

        if ($manifestation->isArchived()) {
            throw new CulturalEventDomainException(
                'Arhivirana Manifestacija ne dozvoljava izmjenu povezanih Događaja.'
            );
        }
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
                    'Događaj već pripada drugoj Manifestaciji. Koristite eksplicitno premještanje.'
                );
            }

            if ((int) $entry->manifestation_id === (int) $manifestation->id) {
                continue;
            }

            $this->assertEventEligibleForNewLink($entry);

            $entry->manifestation_id = $manifestation->id;
            $entry->last_modified_by = $actorId;
            $entry->save();
        }
    }

    private function assertUnlinkKeepsPublishedInvariant(
        CulturalManifestation $manifestation,
        CulturalEventEntry $entry,
    ): void {
        if (! $manifestation->isPublished()) {
            return;
        }

        if ($entry->status !== CulturalEventEntry::STATUS_PUBLISHED) {
            return;
        }

        $remainingPublished = CulturalEventEntry::query()
            ->where('manifestation_id', $manifestation->id)
            ->where('status', CulturalEventEntry::STATUS_PUBLISHED)
            ->where('id', '!=', $entry->id)
            ->count();

        if ($remainingPublished < 1) {
            throw new CulturalEventDomainException(
                'Objavljena Manifestacija mora zadržati najmanje jedan Objavljeni Događaj.'
            );
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
