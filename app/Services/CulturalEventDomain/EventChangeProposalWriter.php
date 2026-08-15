<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\User;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * TS-010.3a/3b — kreiranje / ažuriranje sadržaja prijedloga (+ occurrence ops).
 */
final class EventChangeProposalWriter
{
    public function __construct(
        private readonly EventCatalogGuard $catalogGuard,
        private readonly OccurrenceWriter $occurrenceWriter,
        private readonly EventCoverService $coverService,
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

                return $proposal->fresh(['tags', 'proposedCategory', 'proposedCoverMedia', 'eventEntry', 'occurrenceOps']);
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
        $proposal->loadMissing('eventEntry');

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

        $previousProposedCoverId = $proposal->proposed_cover_media_id !== null
            ? (int) $proposal->proposed_cover_media_id
            : null;
        $liveCoverId = $proposal->eventEntry?->cover_media_id !== null
            ? (int) $proposal->eventEntry->cover_media_id
            : null;

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

        $updated = DB::transaction(function () use ($proposal, $actor, $data) {
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

            return $proposal->fresh(['tags', 'proposedCategory', 'proposedCoverMedia', 'eventEntry', 'occurrenceOps']);
        });

        $nextProposedCoverId = $updated->proposed_cover_media_id !== null
            ? (int) $updated->proposed_cover_media_id
            : null;
        if (
            $previousProposedCoverId !== null
            && $previousProposedCoverId !== $nextProposedCoverId
            && $previousProposedCoverId !== $liveCoverId
        ) {
            $this->coverService->deleteUnreferenced($previousProposedCoverId);
        }

        return $updated;
    }

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
    public function addOccurrenceOp(
        CulturalEventChangeProposal $proposal,
        User $actor,
        array $data,
        bool $asEditor = false,
    ): CulturalEventChangeProposalOccurrence {
        $this->assertCanEditProposalContent($proposal, $actor, $asEditor);

        $normalized = $this->occurrenceWriter->normalizeAndValidate($data);

        return DB::transaction(function () use ($proposal, $actor, $normalized, $asEditor) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanEditProposalContent($locked, $actor, $asEditor);

            $op = CulturalEventChangeProposalOccurrence::create([
                'proposal_id' => $locked->id,
                'operation' => CulturalEventChangeProposalOccurrence::OPERATION_ADD,
                'source_occurrence_id' => null,
                'proposed_datum' => $normalized['datum'],
                'proposed_vrijeme_od' => $normalized['vrijeme_od'],
                'proposed_vrijeme_do' => $normalized['vrijeme_do'],
                'proposed_cjelodnevno' => $normalized['cjelodnevno'],
                'proposed_location_id' => $normalized['location_id'],
                'proposed_location_manual_name' => $normalized['location_manual_name'],
            ]);

            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $op->fresh(['proposedLocation', 'sourceOccurrence']);
        });
    }

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
    public function upsertOccurrenceUpdateOp(
        CulturalEventChangeProposal $proposal,
        User $actor,
        CulturalOccurrence $source,
        array $data,
        bool $asEditor = false,
    ): CulturalEventChangeProposalOccurrence {
        $this->assertCanEditProposalContent($proposal, $actor, $asEditor);

        $entry = $proposal->eventEntry;
        if ($entry === null || (int) $source->event_entry_id !== (int) $entry->id) {
            throw new CulturalEventDomainException(
                'Održavanje ne pripada Događaju ovog prijedloga.'
            );
        }

        $locationChanging = array_key_exists('location_id', $data)
            && (int) ($data['location_id'] ?? 0) !== (int) $source->location_id;
        $normalized = $this->occurrenceWriter->normalizeAndValidate($data, validateNewLocation: $locationChanging);

        return DB::transaction(function () use ($proposal, $actor, $source, $normalized, $asEditor) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanEditProposalContent($locked, $actor, $asEditor);

            $entry = CulturalEventEntry::query()
                ->whereKey($locked->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $source->event_entry_id !== (int) $entry->id) {
                throw new CulturalEventDomainException(
                    'Održavanje ne pripada Događaju ovog prijedloga.'
                );
            }

            /** @var CulturalOccurrence|null $lockedSource */
            $lockedSource = CulturalOccurrence::query()
                ->whereKey($source->id)
                ->lockForUpdate()
                ->first();

            if ($lockedSource === null || (int) $lockedSource->event_entry_id !== (int) $entry->id) {
                throw new CulturalEventDomainException(
                    'Održavanje ne pripada Događaju ovog prijedloga.'
                );
            }

            /** @var CulturalEventChangeProposalOccurrence|null $existing */
            $existing = CulturalEventChangeProposalOccurrence::query()
                ->where('proposal_id', $locked->id)
                ->where('source_occurrence_id', $lockedSource->id)
                ->where('operation', CulturalEventChangeProposalOccurrence::OPERATION_UPDATE)
                ->lockForUpdate()
                ->first();

            $attrs = [
                'operation' => CulturalEventChangeProposalOccurrence::OPERATION_UPDATE,
                'source_occurrence_id' => $lockedSource->id,
                'proposed_datum' => $normalized['datum'],
                'proposed_vrijeme_od' => $normalized['vrijeme_od'],
                'proposed_vrijeme_do' => $normalized['vrijeme_do'],
                'proposed_cjelodnevno' => $normalized['cjelodnevno'],
                'proposed_location_id' => $normalized['location_id'],
                'proposed_location_manual_name' => $normalized['location_manual_name'],
            ];

            if ($existing !== null) {
                // Baseline se ne mijenja pri naknadnom uređivanju prijedloga.
                $existing->fill($attrs);
                $existing->save();
                $op = $existing;
            } else {
                $op = CulturalEventChangeProposalOccurrence::create(array_merge(
                    ['proposal_id' => $locked->id],
                    $attrs,
                    CulturalEventChangeProposalOccurrence::baselineFromOccurrence($lockedSource)
                ));
            }

            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $op->fresh(['proposedLocation', 'sourceOccurrence']);
        });
    }

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
    public function updateOccurrenceOp(
        CulturalEventChangeProposalOccurrence $op,
        User $actor,
        array $data,
        bool $asEditor = false,
    ): CulturalEventChangeProposalOccurrence {
        $op->loadMissing('proposal.eventEntry', 'sourceOccurrence');
        $proposal = $op->proposal;
        if ($proposal === null) {
            throw new CulturalEventDomainException('Operacija nema prijedlog.');
        }

        $this->assertCanEditProposalContent($proposal, $actor, $asEditor);

        $source = $op->sourceOccurrence;
        $validateNewLocation = true;
        if ($op->isUpdate() && $source !== null) {
            $validateNewLocation = array_key_exists('location_id', $data)
                && (int) ($data['location_id'] ?? 0) !== (int) $source->location_id;
        }

        $normalized = $this->occurrenceWriter->normalizeAndValidate($data, validateNewLocation: $validateNewLocation);

        return DB::transaction(function () use ($op, $actor, $normalized, $asEditor) {
            /** @var CulturalEventChangeProposalOccurrence $lockedOp */
            $lockedOp = CulturalEventChangeProposalOccurrence::query()
                ->whereKey($op->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($lockedOp->proposal_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanEditProposalContent($locked, $actor, $asEditor);

            $lockedOp->fill([
                'proposed_datum' => $normalized['datum'],
                'proposed_vrijeme_od' => $normalized['vrijeme_od'],
                'proposed_vrijeme_do' => $normalized['vrijeme_do'],
                'proposed_cjelodnevno' => $normalized['cjelodnevno'],
                'proposed_location_id' => $normalized['location_id'],
                'proposed_location_manual_name' => $normalized['location_manual_name'],
            ]);
            $lockedOp->save();

            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $lockedOp->fresh(['proposedLocation', 'sourceOccurrence']);
        });
    }

    public function removeOccurrenceOp(
        CulturalEventChangeProposalOccurrence $op,
        User $actor,
        bool $asEditor = false,
    ): void {
        $op->loadMissing('proposal.eventEntry');
        $proposal = $op->proposal;
        if ($proposal === null) {
            throw new CulturalEventDomainException('Operacija nema prijedlog.');
        }

        $this->assertCanEditProposalContent($proposal, $actor, $asEditor);

        DB::transaction(function () use ($op, $actor, $asEditor) {
            /** @var CulturalEventChangeProposalOccurrence $lockedOp */
            $lockedOp = CulturalEventChangeProposalOccurrence::query()
                ->whereKey($op->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($lockedOp->proposal_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanEditProposalContent($locked, $actor, $asEditor);

            $lockedOp->delete();

            $locked->last_modified_by = $actor->id;
            $locked->save();
        });
    }

    private function assertCanEditProposalContent(
        CulturalEventChangeProposal $proposal,
        User $actor,
        bool $asEditor,
    ): void {
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
    }

    private function isUniqueActiveViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'cecp_active_for_event_unique')
            || str_contains($message, 'active_for_event_id');
    }
}
