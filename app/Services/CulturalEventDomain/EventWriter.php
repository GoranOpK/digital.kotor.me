<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kreiranje / ažuriranje kanonskog Događaja (TS-003).
 * Bez fizičkog destroy (brisanje nije V1 tok).
 */
final class EventWriter
{
    public function __construct(
        private readonly EventCatalogGuard $catalogGuard,
    ) {}

    /**
     * @param  array{
     *     naslov?: ?string,
     *     opis?: ?string,
     *     organizer_id?: ?int,
     *     category_id?: ?int,
     *     cover_media_id?: ?int,
     *     featured?: bool,
     *     tag_ids?: list<int>
     * }  $data
     */
    public function createDraft(User $creator, array $data): CulturalEventEntry
    {
        $organizerId = $data['organizer_id'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        $coverMediaId = $data['cover_media_id'] ?? null;
        $tagIds = $data['tag_ids'] ?? [];
        $featured = (bool) ($data['featured'] ?? false);

        $this->catalogGuard->assertOrganizerAllowedForNewLink($organizerId);
        $this->catalogGuard->assertCategoryAllowedForNewLink($categoryId);
        $this->catalogGuard->assertCoverMediaAllowedForNewLink($coverMediaId);
        $this->catalogGuard->assertTagsAllowedForNewLinks($tagIds);

        if ($featured) {
            $this->assertCanSetFeatured(CulturalEventEntry::STATUS_DRAFT, null, hasAktuelnoOccurrence: false);
        }

        return DB::transaction(function () use ($creator, $data, $organizerId, $categoryId, $coverMediaId, $tagIds, $featured) {
            $entry = CulturalEventEntry::create([
                'naslov' => $data['naslov'] ?? null,
                'opis' => $data['opis'] ?? null,
                'status' => CulturalEventEntry::STATUS_DRAFT,
                'organizer_id' => $organizerId,
                'category_id' => $categoryId,
                'cover_media_id' => $coverMediaId,
                'featured' => $featured,
                'created_by' => $creator->id,
                'last_modified_by' => $creator->id,
            ]);

            if ($tagIds !== []) {
                $entry->tags()->sync($tagIds);
            }

            return $entry->fresh(['organizer', 'category', 'coverMedia', 'tags', 'occurrences']);
        });
    }

    /**
     * Ažuriranje sadržaja Događaja (ne status). Otkazan = read-only osim razloga.
     *
     * @param  array{
     *     naslov?: ?string,
     *     opis?: ?string,
     *     organizer_id?: ?int,
     *     category_id?: ?int,
     *     cover_media_id?: ?int,
     *     featured?: bool,
     *     tag_ids?: list<int>|null,
     *     cancellation_reason?: ?string
     * }  $data
     */
    public function updateContent(CulturalEventEntry $entry, User $actor, array $data): CulturalEventEntry
    {
        if ($entry->isPendingApproval()) {
            throw new CulturalEventDomainException(
                'Događaj na odobrenju je zaključan; sadržaj se ne može mijenjati.'
            );
        }

        if ($entry->isCancelled()) {
            if (array_key_exists('cancellation_reason', $data) && count($data) === 1) {
                $entry->cancellation_reason = $data['cancellation_reason'];
                $entry->save();

                return $entry->fresh();
            }

            throw new CulturalEventDomainException(
                'Otkazan Događaj je read-only; dozvoljen je samo razlog otkazivanja.'
            );
        }

        if ($entry->status === CulturalEventEntry::STATUS_ARCHIVED) {
            throw new CulturalEventDomainException('Arhiviran Događaj se ne može uređivati.');
        }

        // G2 / BR-025: Objavljen = sadržajno read-only do Prijedloga izmjene.
        // Izuzetak: isticanje (featured) ostaje urednička radnja van sadržajnog prijedloga.
        if ($entry->isPublished()) {
            if (array_key_exists('featured', $data) && count($data) === 1) {
                return $this->applyFeaturedOnly($entry, $actor, (bool) $data['featured']);
            }

            throw new CulturalEventDomainException(
                'Objavljen Događaj je sadržajno read-only; direktna izmjena nije dozvoljena.'
            );
        }

        $organizerChanging = array_key_exists('organizer_id', $data)
            && (int) $data['organizer_id'] !== (int) $entry->organizer_id;
        $categoryChanging = array_key_exists('category_id', $data)
            && (int) $data['category_id'] !== (int) $entry->category_id;
        $coverChanging = array_key_exists('cover_media_id', $data)
            && (int) $data['cover_media_id'] !== (int) $entry->cover_media_id;

        if ($organizerChanging) {
            $this->catalogGuard->assertOrganizerAllowedForNewLink($data['organizer_id']);
        }
        if ($categoryChanging) {
            $this->catalogGuard->assertCategoryAllowedForNewLink($data['category_id']);
        }
        if ($coverChanging) {
            $this->catalogGuard->assertCoverMediaAllowedForNewLink($data['cover_media_id']);
        }

        if (array_key_exists('tag_ids', $data) && is_array($data['tag_ids'])) {
            $currentIds = $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();
            $newIds = array_values(array_unique(array_map('intval', $data['tag_ids'])));
            $added = array_values(array_diff($newIds, $currentIds));
            if ($added !== []) {
                $this->catalogGuard->assertTagsAllowedForNewLinks($added);
            }
        }

        $featured = array_key_exists('featured', $data) ? (bool) $data['featured'] : $entry->featured;
        if ($featured && ! $entry->featured) {
            $this->assertCanSetFeatured(
                $entry->status,
                $entry->id,
                hasAktuelnoOccurrence: $entry->isAktuelan()
            );
        }

        return DB::transaction(function () use ($entry, $actor, $data, $featured) {
            foreach (['naslov', 'opis', 'organizer_id', 'category_id', 'cover_media_id'] as $field) {
                if (array_key_exists($field, $data)) {
                    $entry->{$field} = $data[$field];
                }
            }

            if (array_key_exists('featured', $data)) {
                $entry->featured = $featured;
            }

            $entry->last_modified_by = $actor->id;
            $entry->save();

            if (array_key_exists('tag_ids', $data) && is_array($data['tag_ids'])) {
                $entry->tags()->sync(array_values(array_unique(array_map('intval', $data['tag_ids']))));
            }

            return $entry->fresh(['organizer', 'category', 'coverMedia', 'tags', 'occurrences']);
        });
    }

    /**
     * BR-052 / PO-DG-08 / PO-DG-09 — jednokratno naknadno povezivanje Objavljenog Događaja bez Organizatora.
     * Lock order: Event only. Fail-fast van TX; konačne odluke unutar TX nad zaključanim redom.
     * Ne otvara opšti update Objavljenog sadržaja.
     */
    public function linkOrganizer(CulturalEventEntry $entry, User $actor, int $organizerId): CulturalEventEntry
    {
        $this->assertEligibleForOrganizerLink($entry);
        $this->catalogGuard->assertOrganizerAllowedForNewLink($organizerId);

        return DB::transaction(function () use ($entry, $actor, $organizerId) {
            /** @var CulturalEventEntry $locked */
            $locked = CulturalEventEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertEligibleForOrganizerLink($locked);
            $this->catalogGuard->assertOrganizerAllowedForNewLink($organizerId);

            $locked->organizer_id = $organizerId;
            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh(['organizer', 'category', 'coverMedia', 'tags', 'occurrences']);
        });
    }

    /**
     * Polazno stanje BR-052: Objavljen + organizer_id null.
     */
    private function assertEligibleForOrganizerLink(CulturalEventEntry $entry): void
    {
        if (! $entry->isPublished()) {
            throw new CulturalEventDomainException(
                'Naknadno povezivanje dozvoljeno je samo Objavljenom Događaju bez Organizatora.'
            );
        }

        if ($entry->organizer_id !== null) {
            throw new CulturalEventDomainException(
                'Događaj je već povezan sa Organizatorom; ponovno povezivanje nije dozvoljeno.'
            );
        }
    }

    /**
     * Uredničko isticanje na Objavljenom (BR-117) — nije sadržajna izmjena.
     */
    private function applyFeaturedOnly(CulturalEventEntry $entry, User $actor, bool $featured): CulturalEventEntry
    {
        if ($featured && ! $entry->featured) {
            $this->assertCanSetFeatured(
                $entry->status,
                $entry->id,
                hasAktuelnoOccurrence: $entry->isAktuelan()
            );
        }

        return DB::transaction(function () use ($entry, $actor, $featured) {
            $entry->featured = $featured;
            $entry->last_modified_by = $actor->id;
            $entry->save();

            return $entry->fresh(['organizer', 'category', 'coverMedia', 'tags', 'occurrences']);
        });
    }

    /**
     * Isticanje: samo Objavljen + aktuelan; max 3; bez auto-clear drugih (BM-PK-15 / BR-117).
     */
    private function assertCanSetFeatured(
        string $status,
        ?int $exceptId,
        bool $hasAktuelnoOccurrence,
    ): void {
        if ($status !== CulturalEventEntry::STATUS_PUBLISHED) {
            throw new CulturalEventDomainException(
                'Istaknut može biti samo javno objavljen Događaj.'
            );
        }

        if (! $hasAktuelnoOccurrence) {
            throw new CulturalEventDomainException(
                'Istaknut može biti samo aktuelan Događaj.'
            );
        }

        $current = CulturalEventEntry::currentFeaturedAktuelniCount($exceptId);
        if ($current >= CulturalEventEntry::MAX_FEATURED) {
            throw new CulturalEventDomainException(
                'Najviše tri događaja mogu biti istaknuta istovremeno.'
            );
        }
    }
}
