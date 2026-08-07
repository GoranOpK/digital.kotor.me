<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;

/**
 * TS-010.3a — atomski approve prijedloga (bez Održavanja; TS-010.3b).
 * Ne koristi EventWriter::updateContent na Published (G2).
 */
final class EventChangeProposalApplicator
{
    public function __construct(
        private readonly EventChangeProposalLifecycle $lifecycle,
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

            $locked->load('tags');
            $this->lifecycle->assertReadyForSubmitOrApprove($locked, $entry);

            $entry->naslov = $locked->proposed_naslov;
            $entry->opis = $locked->proposed_opis;
            $entry->category_id = $locked->proposed_category_id;
            $entry->cover_media_id = $locked->proposed_cover_media_id;
            $entry->last_modified_by = $editor->id;
            $entry->save();

            $tagIds = $locked->tags->pluck('id')->map(fn ($id) => (int) $id)->all();
            $entry->tags()->sync($tagIds);

            // TS-010.3a: Occurrences se ne primjenjuju.

            $locked->status = CulturalEventChangeProposal::STATUS_APPROVED;
            $locked->active_for_event_id = null;
            $locked->decision_user_id = $editor->id;
            $locked->decision_at = now();
            $locked->last_modified_by = $editor->id;
            $locked->save();

            return $locked->fresh(['tags', 'eventEntry']);
        });
    }
}
