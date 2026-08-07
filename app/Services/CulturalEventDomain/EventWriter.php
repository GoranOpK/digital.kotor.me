<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kreiranje / ažuriranje kanonskog Događaja (TS-003 Korak 1).
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
            $this->assertFeaturedEligibility(CulturalEventEntry::STATUS_DRAFT);
        }

        return DB::transaction(function () use ($creator, $data, $organizerId, $categoryId, $coverMediaId, $tagIds, $featured) {
            if ($featured) {
                $this->clearOtherFeatured(null);
            }

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
            $this->assertFeaturedEligibility($entry->status);
        }

        return DB::transaction(function () use ($entry, $actor, $data, $featured) {
            if ($featured && ! $entry->featured) {
                $this->clearOtherFeatured($entry->id);
            }

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

    private function assertFeaturedEligibility(string $status): void
    {
        if ($status !== CulturalEventEntry::STATUS_PUBLISHED) {
            throw new CulturalEventDomainException(
                'Istaknut može biti samo javno objavljen Događaj.'
            );
        }
    }

    private function clearOtherFeatured(?int $exceptId): void
    {
        $query = CulturalEventEntry::query()->where('featured', true);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['featured' => false]);
    }
}
