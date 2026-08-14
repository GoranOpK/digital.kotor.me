<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\User;
use App\Support\CulturalModeratorEventAccess;
use App\Support\CulturalPortalAccess;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use Illuminate\Support\Facades\DB;

/**
 * TS-010.3a/3b — lifecycle prijedloga izmjene (submit / withdraw / review / return / G-W02).
 */
final class EventChangeProposalLifecycle
{
    public function __construct(
        private readonly EventCatalogGuard $catalogGuard,
        private readonly OccurrenceWriter $occurrenceWriter,
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public function submit(CulturalEventChangeProposal $proposal, User $actor): CulturalEventChangeProposal
    {
        $proposal->refresh();
        $entry = $proposal->eventEntry;
        CulturalModeratorEventAccess::assertCanAccessEntry($actor, $entry);

        if (! $proposal->isDraft()) {
            throw new CulturalEventDomainException(
                'Na odobrenje se može poslati samo nacrt prijedloga.'
            );
        }

        $this->assertProposalOperableAgainstEvent($proposal, $entry);
        $this->assertReadyForSubmitOrApprove($proposal, $entry);

        $isFirstSubmit = $proposal->first_submitted_at === null;
        $priorReturn = (string) ($proposal->return_reason ?? '');
        $persistAt = now();
        $submitted = DB::transaction(function () use ($proposal, $actor, &$persistAt) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isDraft()) {
                throw new CulturalEventDomainException(
                    'Na odobrenje se može poslati samo nacrt prijedloga.'
                );
            }

            $entry = CulturalEventEntry::query()
                ->whereKey($locked->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertProposalOperableAgainstEvent($locked, $entry);
            $this->assertReadyForSubmitOrApprove($locked, $entry);

            $locked->status = CulturalEventChangeProposal::STATUS_PENDING_REVIEW;
            $locked->last_modified_by = $actor->id;
            $locked->last_submitted_at = now();
            if ($locked->first_submitted_at === null) {
                $locked->first_submitted_at = $locked->last_submitted_at;
            }
            $locked->withdrawn_at = null;
            $locked->return_reason = null;
            $locked->active_for_event_id = $entry->id;
            $locked->save();
            $persistAt = $locked->last_submitted_at?->copy() ?? now();

            return $locked->fresh(['tags', 'eventEntry']);
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_15,
            $isFirstSubmit
                ? CulturalActivityEventId::once(CulturalActivityCatalog::EV_15, (int) $submitted->id)
                : CulturalActivityEventId::repeatable(
                    CulturalActivityCatalog::EV_15,
                    (int) $submitted->id,
                    ['prior_return_digest' => hash('sha256', $priorReturn)],
                    $persistAt
                ),
            $actor,
            (int) $submitted->id,
            $submitted->last_submitted_at ?? now(),
            [
                'proposal_id' => (int) $submitted->id,
                'entry_id' => (int) $submitted->event_entry_id,
            ],
            $submitted->eventEntry?->organizer_id !== null ? (int) $submitted->eventEntry->organizer_id : null,
        );

        return $submitted;
    }

    public function withdraw(CulturalEventChangeProposal $proposal, User $actor): CulturalEventChangeProposal
    {
        $proposal->refresh();
        CulturalModeratorEventAccess::assertCanAccessEntry($actor, $proposal->eventEntry);

        if (! $proposal->canBeWithdrawn()) {
            throw new CulturalEventDomainException(
                'Prijedlog se može povući samo prije početka uredničkog pregleda.'
            );
        }

        return DB::transaction(function () use ($proposal, $actor) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->canBeWithdrawn()) {
                throw new CulturalEventDomainException(
                    'Prijedlog se može povući samo prije početka uredničkog pregleda.'
                );
            }

            $locked->status = CulturalEventChangeProposal::STATUS_DRAFT;
            $locked->withdrawn_at = now();
            $locked->last_modified_by = $actor->id;
            $locked->active_for_event_id = $locked->event_entry_id;
            $locked->save();

            return $locked->fresh(['tags', 'eventEntry']);
        });
    }

    public function startReview(CulturalEventChangeProposal $proposal, User $editor): CulturalEventChangeProposal
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new CulturalEventDomainException('Samo Urednik može pokrenuti pregled prijedloga.');
        }

        $proposal->refresh();

        if (! $proposal->isPendingReview()) {
            throw new CulturalEventDomainException(
                'Pregled se može pokrenuti samo za prijedlog na pregledu.'
            );
        }

        if ($proposal->review_started_at !== null) {
            return $proposal->fresh(['tags', 'eventEntry']);
        }

        return DB::transaction(function () use ($proposal, $editor) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $entry = CulturalEventEntry::query()
                ->whereKey($locked->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertProposalOperableAgainstEvent($locked, $entry);

            if (! $locked->isPendingReview()) {
                throw new CulturalEventDomainException(
                    'Pregled se može pokrenuti samo za prijedlog na pregledu.'
                );
            }

            if ($locked->review_started_at === null) {
                $locked->review_started_at = now();
                $locked->review_started_by = $editor->id;
                $locked->last_modified_by = $editor->id;
                $locked->save();
            }

            return $locked->fresh(['tags', 'eventEntry']);
        });
    }

    public function returnToDraft(
        CulturalEventChangeProposal $proposal,
        User $editor,
        string $reason,
    ): CulturalEventChangeProposal {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new CulturalEventDomainException('Samo Urednik može vratiti prijedlog na doradu.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new CulturalEventDomainException('Razlog vraćanja na doradu je obavezan.');
        }

        $proposal->refresh();

        if (! $proposal->isPendingReview()) {
            throw new CulturalEventDomainException(
                'Na doradu se može vratiti samo prijedlog na pregledu.'
            );
        }

        $persistAt = now();
        $returned = DB::transaction(function () use ($proposal, $editor, $reason, &$persistAt) {
            /** @var CulturalEventChangeProposal $locked */
            $locked = CulturalEventChangeProposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $entry = CulturalEventEntry::query()
                ->whereKey($locked->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertProposalOperableAgainstEvent($locked, $entry);

            if (! $locked->isPendingReview()) {
                throw new CulturalEventDomainException(
                    'Na doradu se može vratiti samo prijedlog na pregledu.'
                );
            }

            $locked->status = CulturalEventChangeProposal::STATUS_DRAFT;
            $locked->return_reason = $reason;
            $locked->decision_user_id = $editor->id;
            $locked->decision_at = now();
            $locked->review_started_at = null;
            $locked->review_started_by = null;
            $locked->last_modified_by = $editor->id;
            $locked->active_for_event_id = $locked->event_entry_id;
            $locked->save();
            $persistAt = $locked->decision_at?->copy() ?? now();

            return $locked->fresh(['tags', 'eventEntry']);
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_17,
            CulturalActivityEventId::repeatable(
                CulturalActivityCatalog::EV_17,
                (int) $returned->id,
                ['reason_digest' => hash('sha256', $reason)],
                $persistAt
            ),
            $editor,
            (int) $returned->id,
            $returned->decision_at ?? now(),
            [
                'proposal_id' => (int) $returned->id,
                'entry_id' => (int) $returned->event_entry_id,
            ],
            $returned->eventEntry?->organizer_id !== null ? (int) $returned->eventEntry->organizer_id : null,
        );

        return $returned;
    }

    /**
     * G-W02 — primjena inoperable na već zaključane aktivne prijedloge
     * (poziva se unutar EventLifecycle::cancel nakon Proposal → Event lock-a).
     *
     * @param  iterable<int, CulturalEventChangeProposal>  $lockedProposals
     */
    public function markLockedProposalsInoperableForCancelledEvent(iterable $lockedProposals): void
    {
        foreach ($lockedProposals as $proposal) {
            if (! $proposal->isActive()) {
                continue;
            }

            $proposal->status = CulturalEventChangeProposal::STATUS_INOPERABLE;
            $proposal->inoperable_at = now();
            $proposal->inoperable_reason = CulturalEventChangeProposal::INOPERABLE_REASON_EVENT_CANCELLED;
            $proposal->active_for_event_id = null;
            $proposal->review_started_at = null;
            $proposal->review_started_by = null;
            $proposal->save();
        }
    }

    public function assertProposalOperableAgainstEvent(
        CulturalEventChangeProposal $proposal,
        CulturalEventEntry $entry,
    ): void {
        if ($proposal->isInoperable()) {
            throw new CulturalEventDomainException(
                'Prijedlog je neoperativan i ne može se obrađivati.'
            );
        }

        if (! $entry->isPublished()) {
            throw new CulturalEventDomainException(
                'Događaj više nije objavljen; prijedlog se ne može obrađivati.'
            );
        }
    }

    public function assertReadyForSubmitOrApprove(
        CulturalEventChangeProposal $proposal,
        CulturalEventEntry $entry,
        bool $withOccurrenceOps = true,
    ): void {
        $naslov = trim((string) $proposal->proposed_naslov);
        if ($naslov === '') {
            throw new CulturalEventDomainException('Naslov je obavezan za slanje/odobrenje prijedloga.');
        }

        if ($proposal->proposed_category_id === null) {
            throw new CulturalEventDomainException(
                'Primarna kategorija je obavezna za slanje/odobrenje prijedloga.'
            );
        }

        $this->catalogGuard->assertCategoryAllowedForNewLink($proposal->proposed_category_id);
        $this->catalogGuard->assertCoverMediaAllowedForNewLink($proposal->proposed_cover_media_id);

        $tagIds = $proposal->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();
        $this->catalogGuard->assertTagsAllowedForNewLinks($tagIds);

        if ($entry->organizer_id !== null) {
            $this->catalogGuard->assertOrganizerAllowedForNewLink((int) $entry->organizer_id);
        }

        if ($withOccurrenceOps) {
            $this->assertOccurrenceOpsReady($proposal, $entry);
        }
    }

    public function assertOccurrenceOpsReady(
        CulturalEventChangeProposal $proposal,
        CulturalEventEntry $entry,
    ): void {
        $proposal->loadMissing('occurrenceOps.sourceOccurrence');

        $canonicalCount = $entry->occurrences()->count();
        $addCount = 0;

        foreach ($proposal->occurrenceOps as $op) {
            if ($op->isAdd()) {
                $this->occurrenceWriter->normalizeAndValidate($op->toOccurrencePayload());
                $addCount++;

                continue;
            }

            if ($op->isUpdate()) {
                $source = $op->sourceOccurrence;
                if ($source === null || (int) $source->event_entry_id !== (int) $entry->id) {
                    throw new CulturalEventDomainException(
                        'Predložena izmjena Održavanja ne pripada Događaju.'
                    );
                }

                $payload = $op->toOccurrencePayload();
                $locationChanging = (int) ($payload['location_id'] ?? 0) !== (int) $source->location_id;
                $this->occurrenceWriter->normalizeAndValidate(
                    $payload,
                    validateNewLocation: $locationChanging
                );

                continue;
            }

            throw new CulturalEventDomainException('Nepoznata operacija Održavanja u prijedlogu.');
        }

        if (($canonicalCount + $addCount) < 1) {
            throw new CulturalEventDomainException(
                'Za slanje/odobrenje prijedloga Događaj mora imati najmanje jedno Održavanje.'
            );
        }
    }
}
