<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;

/**
 * TS-010.3a/3b — atomski approve prijedloga (Event pa Occurrence ops).
 * Ne koristi EventWriter::updateContent na Published (G2).
 */
final class EventChangeProposalApplicator
{
    public function __construct(
        private readonly EventChangeProposalLifecycle $lifecycle,
        private readonly OccurrenceWriter $occurrenceWriter,
    ) {}

    public function approve(CulturalEventChangeProposal $proposal, User $editor): CulturalEventChangeProposal
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new CulturalEventDomainException('Samo Urednik može odobriti prijedlog izmjene.');
        }

        return DB::transaction(function () use ($proposal, $editor) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($locked->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->lifecycle->assertProposalOperableAgainstEvent($locked, $entry);

            if (! $locked->isPendingReview()) {
                throw new CulturalEventDomainException(
                    'Odobriti se može samo prijedlog u statusu Na pregledu.'
                );
            }

            $locked->load(['tags', 'occurrenceOps']);
            // Event snapshot gate first; occurrence ops revalidate immediately before apply
            // (same transaction) so Event→Occurrence order stays atomic without partial commit.
            $this->lifecycle->assertReadyForSubmitOrApprove($locked, $entry, withOccurrenceOps: false);

            $entry->naslov = $locked->proposed_naslov;
            $entry->opis = $locked->proposed_opis;
            $entry->category_id = $locked->proposed_category_id;
            $entry->cover_media_id = $locked->proposed_cover_media_id;
            $entry->last_modified_by = $editor->id;
            $entry->save();

            $tagIds = $locked->tags->pluck('id')->map(fn ($id) => (int) $id)->all();
            $entry->tags()->sync($tagIds);

            $this->lifecycle->assertOccurrenceOpsReady($locked, $entry);
            $this->applyOccurrenceOps($locked, $entry);

            $locked->status = CulturalEventChangeProposal::STATUS_APPROVED;
            $locked->active_for_event_id = null;
            $locked->decision_user_id = $editor->id;
            $locked->decision_at = now();
            $locked->last_modified_by = $editor->id;
            $locked->save();

            return $locked->fresh(['tags', 'eventEntry', 'occurrenceOps']);
        });
    }

    private function applyOccurrenceOps(
        CulturalEventChangeProposal $proposal,
        CulturalEventEntry $entry,
    ): void {
        $ops = $proposal->occurrenceOps
            ->sortBy('id')
            ->values();

        if ($ops->isEmpty()) {
            return;
        }

        $updateIds = $ops
            ->filter(fn (CulturalEventChangeProposalOccurrence $op) => $op->isUpdate())
            ->map(fn (CulturalEventChangeProposalOccurrence $op) => (int) $op->source_occurrence_id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        /** @var \Illuminate\Support\Collection<int, CulturalOccurrence> $lockedOccurrences */
        $lockedOccurrences = collect();
        if ($updateIds !== []) {
            $lockedOccurrences = CulturalOccurrence::query()
                ->whereIn('id', $updateIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
        }

        foreach ($ops as $op) {
            if ($op->isUpdate()) {
                $sourceId = (int) $op->source_occurrence_id;
                /** @var CulturalOccurrence|null $occurrence */
                $occurrence = $lockedOccurrences->get($sourceId);
                if ($occurrence === null) {
                    throw new CulturalEventDomainException(
                        'Predloženo Održavanje za izmjenu više ne postoji.'
                    );
                }
                if ((int) $occurrence->event_entry_id !== (int) $entry->id) {
                    throw new CulturalEventDomainException(
                        'Održavanje ne pripada Događaju ovog prijedloga.'
                    );
                }

                $this->occurrenceWriter->applyUpdateFromApprovedProposal(
                    $occurrence,
                    $op->toOccurrencePayload()
                );

                continue;
            }

            if ($op->isAdd()) {
                $this->occurrenceWriter->applyCreateFromApprovedProposal(
                    $entry,
                    $op->toOccurrencePayload()
                );

                continue;
            }

            throw new CulturalEventDomainException('Nepoznata operacija Održavanja u prijedlogu.');
        }
    }
}
