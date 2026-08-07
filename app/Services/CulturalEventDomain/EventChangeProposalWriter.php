<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\User;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * TS-010.3a — kreiranje / ažuriranje sadržaja prijedloga izmjene.
 */
final class EventChangeProposalWriter
{
    public function __construct(
        private readonly EventCatalogGuard $catalogGuard,
    ) {}

    public function createFromPublished(CulturalEventEntry $entry, User $actor): CulturalEventChangeProposal
    {
        if (! $entry->isPublished()) {
            throw new CulturalEventDomainException(
                'Prijedlog izmjene može se pokrenuti samo za objavljeni Događaj.'
            );
        }

        if ($entry->organizer_id === null) {
            throw new CulturalEventDomainException(
                'Prijedlog izmjene zahtijeva Događaj sa Organizatorom.'
            );
        }

        CulturalModeratorEventAccess::assertCanAccessEntry($actor, $entry);

        try {
            return DB::transaction(function () use ($entry, $actor) {
                // BR-012 slot lock first (UNIQUE active_for_event_id) — Proposal → Event.
                $slotHolders = CulturalEventChangeProposal::query()
                    ->where('active_for_event_id', $entry->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($slotHolders->isNotEmpty()) {
                    throw new CulturalEventDomainException(
                        'Za ovaj Događaj već postoji aktivan prijedlog izmjene.'
                    );
                }

                /** @var CulturalEventEntry $locked */
                $locked = CulturalEventEntry::query()
                    ->whereKey($entry->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $locked->isPublished()) {
                    throw new CulturalEventDomainException(
                        'Prijedlog izmjene može se pokrenuti samo za objavljeni Događaj.'
                    );
                }

                if ($locked->organizer_id === null) {
                    throw new CulturalEventDomainException(
                        'Prijedlog izmjene zahtijeva Događaj sa Organizatorom.'
                    );
                }

                CulturalModeratorEventAccess::assertCanAccessEntry($actor, $locked);

                $locked->loadMissing('tags');

                $proposal = CulturalEventChangeProposal::create([
                    'event_entry_id' => $locked->id,
                    'organizer_id' => $locked->organizer_id,
                    'created_by' => $actor->id,
                    'last_modified_by' => $actor->id,
                    'status' => CulturalEventChangeProposal::STATUS_DRAFT,
                    'proposed_naslov' => $locked->naslov,
                    'proposed_opis' => $locked->opis,
                    'proposed_category_id' => $locked->category_id,
                    'proposed_cover_media_id' => $locked->cover_media_id,
                    'active_for_event_id' => $locked->id,
                ]);

                $tagIds = $locked->tags->pluck('id')->map(fn ($id) => (int) $id)->all();
                if ($tagIds !== []) {
                    $proposal->tags()->sync($tagIds);
                }

                return $proposal->fresh(['tags', 'proposedCategory', 'proposedCoverMedia', 'eventEntry']);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueActiveViolation($e)) {
                throw new CulturalEventDomainException(
                    'Za ovaj Događaj već postoji aktivan prijedlog izmjene.'
                );
            }

            throw $e;
        }
    }

    /**
     * @param  array{
     *     proposed_naslov?: ?string,
     *     proposed_opis?: ?string,
     *     proposed_category_id?: ?int,
     *     proposed_cover_media_id?: ?int,
     *     tag_ids?: list<int>
     * }  $data
     */
    public function updateDraftContent(
        CulturalEventChangeProposal $proposal,
        User $actor,
        array $data,
        bool $asEditor = false,
    ): CulturalEventChangeProposal {
        $proposal->refresh();

        if ($proposal->isInoperable() || $proposal->isApproved()) {
            throw new CulturalEventDomainException(
                'Prijedlog više nije operativan za uređivanje.'
            );
        }

        if ($asEditor) {
            if (! $proposal->isPendingReview() || $proposal->review_started_at === null) {
                throw new CulturalEventDomainException(
                    'Urednik može uređivati prijedlog tek nakon početka pregleda.'
                );
            }
        } else {
            if (! $proposal->isDraft()) {
                throw new CulturalEventDomainException(
                    'Moderator može uređivati samo nacrt prijedloga.'
                );
            }
            CulturalModeratorEventAccess::assertCanAccessEntry($actor, $proposal->eventEntry);
        }

        $categoryChanging = array_key_exists('proposed_category_id', $data)
            && (int) ($data['proposed_category_id'] ?? 0) !== (int) $proposal->proposed_category_id;
        $coverChanging = array_key_exists('proposed_cover_media_id', $data)
            && (int) ($data['proposed_cover_media_id'] ?? 0) !== (int) $proposal->proposed_cover_media_id;

        if ($categoryChanging) {
            $this->catalogGuard->assertCategoryAllowedForNewLink($data['proposed_category_id'] ?? null);
        }
        if ($coverChanging) {
            $this->catalogGuard->assertCoverMediaAllowedForNewLink($data['proposed_cover_media_id'] ?? null);
        }

        if (array_key_exists('tag_ids', $data) && is_array($data['tag_ids'])) {
            $currentIds = $proposal->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();
            $newIds = array_values(array_unique(array_map('intval', $data['tag_ids'])));
            $added = array_values(array_diff($newIds, $currentIds));
            if ($added !== []) {
                $this->catalogGuard->assertTagsAllowedForNewLinks($added);
            }
        }

        return DB::transaction(function () use ($proposal, $actor, $data) {
            foreach (['proposed_naslov', 'proposed_opis', 'proposed_category_id', 'proposed_cover_media_id'] as $field) {
                if (array_key_exists($field, $data)) {
                    $proposal->{$field} = $data[$field];
                }
            }

            $proposal->last_modified_by = $actor->id;
            $proposal->save();

            if (array_key_exists('tag_ids', $data) && is_array($data['tag_ids'])) {
                $proposal->tags()->sync(array_values(array_unique(array_map('intval', $data['tag_ids']))));
            }

            return $proposal->fresh(['tags', 'proposedCategory', 'proposedCoverMedia', 'eventEntry']);
        });
    }

    private function isUniqueActiveViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'cecp_active_for_event_unique')
            || str_contains($message, 'active_for_event_id');
    }
}
