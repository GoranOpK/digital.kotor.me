<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
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
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public function approve(CulturalEventChangeProposal $proposal, User $editor): CulturalEventChangeProposal
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new CulturalEventDomainException('Samo Urednik može odobriti prijedlog izmjene.');
        }

        $occEffects = [];
        $approved = DB::transaction(function () use ($proposal, $editor, &$occEffects) {
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
            $this->applyOccurrenceOps($locked, $entry, $occEffects);

            $locked->status = CulturalEventChangeProposal::STATUS_APPROVED;
            $locked->active_for_event_id = null;
            $locked->decision_user_id = $editor->id;
            $locked->decision_at = now();
            $locked->last_modified_by = $editor->id;
            $locked->save();

            return $locked->fresh(['tags', 'eventEntry', 'occurrenceOps']);
        });

        $decisionAt = $approved->decision_at?->copy() ?? now();
        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_16,
            CulturalActivityEventId::once(CulturalActivityCatalog::EV_16, (int) $approved->id),
            $editor,
            (int) $approved->id,
            $decisionAt,
            [
                'proposal_id' => (int) $approved->id,
                'entry_id' => (int) $approved->event_entry_id,
            ],
            $approved->eventEntry?->organizer_id !== null ? (int) $approved->eventEntry->organizer_id : null,
        );

        foreach ($occEffects as $effect) {
            $occurrence = CulturalOccurrence::query()->find($effect['occurrence_id']);
            if ($occurrence === null) {
                continue;
            }
            if ($effect['datetime']) {
                $this->activityEmitter->emitUser(
                    CulturalActivityCatalog::EV_13,
                    CulturalActivityEventId::of(
                        CulturalActivityCatalog::EV_13,
                        (int) $occurrence->id,
                        'proposal',
                        (int) $effect['proposal_id'],
                        (int) $effect['op_id']
                    ),
                    $editor,
                    (int) $occurrence->id,
                    $occurrence->updated_at ?? now(),
                    [
                        'occurrence_id' => (int) $occurrence->id,
                        'entry_id' => (int) $occurrence->event_entry_id,
                    ],
                    $approved->eventEntry?->organizer_id !== null ? (int) $approved->eventEntry->organizer_id : null,
                );
            }
            if ($effect['location']) {
                $this->activityEmitter->emitUser(
                    CulturalActivityCatalog::EV_14,
                    CulturalActivityEventId::of(
                        CulturalActivityCatalog::EV_14,
                        (int) $occurrence->id,
                        'proposal',
                        (int) $effect['proposal_id'],
                        (int) $effect['op_id']
                    ),
                    $editor,
                    (int) $occurrence->id,
                    $occurrence->updated_at ?? now(),
                    [
                        'occurrence_id' => (int) $occurrence->id,
                        'entry_id' => (int) $occurrence->event_entry_id,
                    ],
                    $approved->eventEntry?->organizer_id !== null ? (int) $approved->eventEntry->organizer_id : null,
                );
            }
        }

        return $approved;
    }

    /**
     * @param  list<array{occurrence_id: int, proposal_id: int, op_id: int, datetime: bool, location: bool}>  $occEffects
     */
    private function applyOccurrenceOps(
        CulturalEventChangeProposal $proposal,
        CulturalEventEntry $entry,
        array &$occEffects,
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

                if ($op->terminConflictsWithCanonical($occurrence)) {
                    throw new CulturalEventDomainException(
                        'Održavanje je u međuvremenu promijenjeno (termin). Prijedlog mora biti usklađen prije odobravanja.'
                    );
                }

                $datetimeChanged = ! CulturalEventChangeProposalOccurrence::terminFieldsEqual(
                    $occurrence->datum?->toDateString() ?? (string) $occurrence->datum,
                    $occurrence->vrijeme_od,
                    $occurrence->vrijeme_do,
                    (bool) $occurrence->cjelodnevno,
                    $op->proposed_datum?->toDateString() ?? (string) $op->proposed_datum,
                    $op->proposed_vrijeme_od,
                    $op->proposed_vrijeme_do,
                    (bool) $op->proposed_cjelodnevno,
                );
                $locationChanged = (int) ($occurrence->location_id ?? 0) !== (int) ($op->proposed_location_id ?? 0)
                    || (string) ($occurrence->location_manual_name ?? '') !== (string) ($op->proposed_location_manual_name ?? '');

                $this->occurrenceWriter->applyUpdateFromApprovedProposal(
                    $occurrence,
                    $op->toOccurrencePayload()
                );

                $occEffects[] = [
                    'occurrence_id' => (int) $occurrence->id,
                    'proposal_id' => (int) $proposal->id,
                    'op_id' => (int) $op->id,
                    'datetime' => $datetimeChanged,
                    'location' => $locationChanged,
                ];

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
