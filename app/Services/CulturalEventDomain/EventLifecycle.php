<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use App\Services\Newsletter\NewsletterPriorityChangeRecorder;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle Događaja (TS-003). Odvojen od statusa Održavanja.
 */
final class EventLifecycle
{
    public function __construct(
        private readonly NewsletterPriorityChangeRecorder $priorityChangeRecorder,
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    /**
     * Nacrt → Na odobrenju.
     * PO-EV-WF-01 / BM-ST-04 — Događaj bez registrovanog Organizatora ne šalje se na odobrenje
     * (urednički tok = direktna objava).
     */
    public function submitForApproval(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        if ($entry->organizer_id === null) {
            throw new CulturalEventDomainException(
                'Događaj bez Organizatora ne šalje se na odobrenje; koristite direktnu objavu.'
            );
        }

        $this->assertTransition($entry, CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $this->assertReadyForPublishGate($entry);

        $isResubmit = $entry->first_submitted_at !== null;
        $fromStatus = $entry->status;
        $persistAt = now();
        $updated = $this->apply($entry, CulturalEventEntry::STATUS_PENDING_APPROVAL, $actor, markSubmitted: true, persistAt: $persistAt);
        $catalogId = $isResubmit ? CulturalActivityCatalog::EV_04 : CulturalActivityCatalog::EV_02;
        $eventId = $isResubmit
            ? CulturalActivityEventId::repeatable($catalogId, (int) $updated->id, ['from' => $fromStatus], $persistAt)
            : CulturalActivityEventId::once($catalogId, (int) $updated->id);
        $this->activityEmitter->emitUser(
            $catalogId,
            $eventId,
            $actor,
            (int) $updated->id,
            $isResubmit ? ($updated->updated_at ?? now()) : ($updated->first_submitted_at ?? now()),
            ['entry_id' => (int) $updated->id],
            $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
        );

        return $updated;
    }

    /**
     * Na odobrenju → Objavljen.
     */
    public function approve(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_PUBLISHED);
        $this->assertReadyForPublishGate($entry);

        $updated = $this->apply($entry, CulturalEventEntry::STATUS_PUBLISHED, $actor);
        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_05,
            CulturalActivityEventId::once(CulturalActivityCatalog::EV_05, (int) $updated->id),
            $actor,
            (int) $updated->id,
            $updated->first_published_at ?? now(),
            ['entry_id' => (int) $updated->id],
            $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
        );

        return $updated;
    }

    /**
     * Na odobrenju → Nacrt (BR-040: razlog obavezan).
     */
    public function returnToDraft(CulturalEventEntry $entry, User $actor, string $reason): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_DRAFT);

        $reason = trim($reason);
        if ($reason === '') {
            throw new CulturalEventDomainException('Razlog vraćanja na doradu je obavezan.');
        }

        $persistAt = now();
        $updated = DB::transaction(function () use ($entry, $actor, $reason, &$persistAt) {
            $entry->status = CulturalEventEntry::STATUS_DRAFT;
            $entry->return_reason = $reason;
            $entry->last_modified_by = $actor->id;
            $entry->save();
            $persistAt = $entry->updated_at?->copy() ?? now();

            return $entry->fresh();
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_03,
            CulturalActivityEventId::repeatable(
                CulturalActivityCatalog::EV_03,
                (int) $updated->id,
                ['reason_digest' => hash('sha256', $reason)],
                $persistAt
            ),
            $actor,
            (int) $updated->id,
            $updated->updated_at ?? now(),
            ['entry_id' => (int) $updated->id],
            $updated->organizer_id !== null ? (int) $updated->organizer_id : null,
        );

        return $updated;
    }

    /**
     * Nacrt → Objavljen (samo bez Organizatora).
     * Lock order: Event only (nema Proposal/Occurrence u ovom toku).
     * Fail-fast van TX; konačne odluke unutar TX nad zaključanim redom.
     * Ulaz mora biti Nacrt (ne Pending) — Pending → Objavljen ide preko approve.
     */
    public function publishDirectly(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        $this->assertIsDraftForDirectPublish($entry);

        if ($entry->organizer_id !== null) {
            throw new CulturalEventDomainException(
                'Direktna objava dozvoljena je samo Događaju bez Organizatora.'
            );
        }

        $this->assertReadyForPublishGate($entry);

        $published = DB::transaction(function () use ($entry, $actor) {
            /** @var CulturalEventEntry $locked */
            $locked = CulturalEventEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertIsDraftForDirectPublish($locked);

            if ($locked->organizer_id !== null) {
                throw new CulturalEventDomainException(
                    'Direktna objava dozvoljena je samo Događaju bez Organizatora.'
                );
            }

            $this->assertReadyForPublishGate($locked);

            $locked->status = CulturalEventEntry::STATUS_PUBLISHED;
            $locked->last_modified_by = $actor->id;
            if ($locked->first_submitted_at === null) {
                $locked->first_submitted_at = now();
            }
            $this->stampFirstPublishedAt($locked);
            $locked->save();

            return $locked->fresh();
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_06,
            CulturalActivityEventId::once(CulturalActivityCatalog::EV_06, (int) $published->id),
            $actor,
            (int) $published->id,
            $published->first_published_at ?? now(),
            ['entry_id' => (int) $published->id],
        );

        return $published;
    }

    /**
     * Objavljen → Otkazan (terminalan za republish). Razlog je opcion (PATCH-063 / BR-295).
     * PO-AUTO-01: Planiran/Odgođen Održavanja → Otkazan u istoj atomskoj operaciji.
     * Lock order: aktivni Proposal-i → Event → Occurrence-i (id ASC).
     */
    public function cancel(CulturalEventEntry $entry, User $actor, ?string $reason = null): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_CANCELLED);

        $reason = $reason !== null ? trim($reason) : null;
        if ($reason === '') {
            $reason = null;
        }

        $cancelled = DB::transaction(function () use ($entry, $actor, $reason) {
            // BR-012 slot — isti predikat kao createFromPublished (UNIQUE active_for_event_id).
            $lockedProposals = CulturalEventChangeProposal::query()
                ->where('active_for_event_id', $entry->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /** @var CulturalEventEntry $locked */
            $locked = CulturalEventEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransition($locked, CulturalEventEntry::STATUS_CANCELLED);

            /** @var \Illuminate\Support\Collection<int, CulturalOccurrence> $lockedOccurrences */
            $lockedOccurrences = CulturalOccurrence::query()
                ->where('event_entry_id', $locked->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $locked->status = CulturalEventEntry::STATUS_CANCELLED;
            $locked->cancellation_reason = $reason;
            $locked->last_modified_by = $actor->id;
            $locked->featured = false;
            $locked->save();

            app(EventChangeProposalLifecycle::class)
                ->markLockedProposalsInoperableForCancelledEvent($lockedProposals);

            foreach ($lockedOccurrences as $occurrence) {
                if (! in_array($occurrence->status, [
                    CulturalOccurrence::STATUS_PLANNED,
                    CulturalOccurrence::STATUS_POSTPONED,
                ], true)) {
                    continue;
                }

                $occurrence->status = CulturalOccurrence::STATUS_CANCELLED;
                $occurrence->save();
            }

            $cancelled = $locked->fresh(['occurrences']);
            $this->priorityChangeRecorder->recordEventCancelled($cancelled);

            return $cancelled;
        });

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::EV_09,
            CulturalActivityEventId::once(CulturalActivityCatalog::EV_09, (int) $cancelled->id),
            $actor,
            (int) $cancelled->id,
            $cancelled->updated_at ?? now(),
            ['entry_id' => (int) $cancelled->id],
            $cancelled->organizer_id !== null ? (int) $cancelled->organizer_id : null,
        );

        return $cancelled;
    }

    /**
     * Pokušaj republish-a — uvijek odbijen na domenskom nivou.
     */
    public function republish(CulturalEventEntry $entry, User $actor): never
    {
        throw new CulturalEventDomainException(
            'Prelaz Otkazan → Objavljen nije dozvoljen (terminalni status; nema republish).'
        );
    }

    /**
     * Sistemsko arhiviranje: Objavljen|Otkazan → Arhiviran kada nema otvorenih održavanja.
     * G2: transakcija + lockForUpdate + ponovna provjera predikata nad zaključanim stanjem.
     * Lock order: Event → Occurrence-i (id ASC). Bez Proposal (arhiva ih ne dira).
     * Sistemska tranzicija: `last_modified_by` se ne mijenja (nema sistemskog User naloga).
     */
    public function archiveIfEligible(CulturalEventEntry $entry): CulturalEventEntry
    {
        $archived = DB::transaction(function () use ($entry) {
            /** @var CulturalEventEntry $locked */
            $locked = CulturalEventEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, [
                CulturalEventEntry::STATUS_PUBLISHED,
                CulturalEventEntry::STATUS_CANCELLED,
            ], true)) {
                throw new CulturalEventDomainException(
                    'Arhiviranje je dozvoljeno samo iz statusa Objavljen ili Otkazan.'
                );
            }

            $lockedOccurrences = CulturalOccurrence::query()
                ->where('event_entry_id', $locked->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $hasOpen = $lockedOccurrences->contains(function (CulturalOccurrence $occurrence): bool {
                return in_array($occurrence->status, [
                    CulturalOccurrence::STATUS_PLANNED,
                    CulturalOccurrence::STATUS_POSTPONED,
                ], true);
            });

            if ($hasOpen) {
                throw new CulturalEventDomainException(
                    'Događaj se ne može arhivirati dok postoji Održavanje u statusu Planiran ili Odgođen.'
                );
            }

            // 6A-09 / PO-6A09-02: sačuvaj izvorni javni status prije promjene.
            $locked->archived_from_status = $locked->status;
            $locked->status = CulturalEventEntry::STATUS_ARCHIVED;
            $locked->featured = false;
            $locked->save();

            return $locked->fresh(['occurrences']);
        });

        $this->activityEmitter->emitSystem(
            CulturalActivityCatalog::EV_18,
            CulturalActivityEventId::once(CulturalActivityCatalog::EV_18, (int) $archived->id),
            (int) $archived->id,
            $archived->updated_at ?? now(),
            ['entry_id' => (int) $archived->id],
            $archived->organizer_id !== null ? (int) $archived->organizer_id : null,
        );

        return $archived;
    }

    /**
     * Generički prelaz sa validacijom dozvoljenog skupa (za testove invalidnih vrijednosti).
     */
    public function transitionTo(
        CulturalEventEntry $entry,
        string $target,
        User $actor,
        ?string $returnReason = null,
    ): CulturalEventEntry {
        if (! in_array($target, CulturalEventEntry::STATUSES, true)) {
            throw new CulturalEventDomainException('Nepoznat status Događaja: '.$target);
        }

        if ($entry->status === CulturalEventEntry::STATUS_CANCELLED
            && $target === CulturalEventEntry::STATUS_PUBLISHED) {
            $this->republish($entry, $actor);
        }

        if ($target === CulturalEventEntry::STATUS_PENDING_APPROVAL) {
            return $this->submitForApproval($entry, $actor);
        }

        if ($target === CulturalEventEntry::STATUS_PUBLISHED
            && $entry->status === CulturalEventEntry::STATUS_DRAFT) {
            return $this->publishDirectly($entry, $actor);
        }

        if ($target === CulturalEventEntry::STATUS_PUBLISHED
            && $entry->status === CulturalEventEntry::STATUS_PENDING_APPROVAL) {
            return $this->approve($entry, $actor);
        }

        if ($target === CulturalEventEntry::STATUS_DRAFT
            && $entry->status === CulturalEventEntry::STATUS_PENDING_APPROVAL) {
            return $this->returnToDraft($entry, $actor, (string) $returnReason);
        }

        if ($target === CulturalEventEntry::STATUS_CANCELLED) {
            return $this->cancel($entry, $actor, (string) $returnReason);
        }

        if ($target === CulturalEventEntry::STATUS_ARCHIVED) {
            return $this->archiveIfEligible($entry);
        }

        $this->assertTransition($entry, $target);

        return $this->apply($entry, $target, $actor);
    }

    private function assertTransition(CulturalEventEntry $entry, string $target): void
    {
        if (! $entry->canTransitionTo($target)) {
            throw new CulturalEventDomainException(sprintf(
                'Prelaz %s → %s nije dozvoljen.',
                $entry->statusLabel(),
                CulturalEventEntry::STATUS_LABELS[$target] ?? $target
            ));
        }
    }

    private function assertIsDraftForDirectPublish(CulturalEventEntry $entry): void
    {
        if (! $entry->isDraft()) {
            throw new CulturalEventDomainException(sprintf(
                'Direktna objava je dozvoljena samo iz statusa Nacrt (trenutno: %s).',
                $entry->statusLabel()
            ));
        }

        $this->assertTransition($entry, CulturalEventEntry::STATUS_PUBLISHED);
    }

    /**
     * Publish/submit gate (TS-003): naslov, aktivna kategorija, aktivan Org ako postoji, ≥1 održavanje.
     */
    public function assertReadyForPublishGate(CulturalEventEntry $entry): void
    {
        $naslov = trim((string) $entry->naslov);
        if ($naslov === '') {
            throw new CulturalEventDomainException('Naslov je obavezan za slanje/objavu.');
        }

        if ($entry->category_id === null) {
            throw new CulturalEventDomainException('Primarna kategorija je obavezna za slanje/objavu.');
        }

        $category = CulturalCategory::query()->find($entry->category_id);
        if ($category === null || ! $category->isActive()) {
            throw new CulturalEventDomainException(
                'Za slanje/objavu je potrebna aktivna Kategorija.'
            );
        }

        if ($entry->organizer_id !== null) {
            $organizer = CulturalOrganizer::query()->find($entry->organizer_id);
            if ($organizer === null || ! $organizer->isActive()) {
                throw new CulturalEventDomainException(
                    'Za slanje/objavu Organizator mora biti aktivan.'
                );
            }
        }

        if ($entry->occurrences()->count() < 1) {
            throw new CulturalEventDomainException(
                'Za slanje/objavu je potrebno najmanje jedno Održavanje.'
            );
        }
    }

    private function stampFirstPublishedAt(CulturalEventEntry $entry): void
    {
        if ($entry->first_published_at === null) {
            $entry->first_published_at = now();
        }
    }

    private function apply(
        CulturalEventEntry $entry,
        string $status,
        User $actor,
        bool $markSubmitted = false,
        ?\Carbon\CarbonInterface &$persistAt = null,
    ): CulturalEventEntry {
        return DB::transaction(function () use ($entry, $status, $actor, $markSubmitted, &$persistAt) {
            $entry->status = $status;
            $entry->last_modified_by = $actor->id;

            if ($markSubmitted && $entry->first_submitted_at === null) {
                $entry->first_submitted_at = now();
            }

            if ($status === CulturalEventEntry::STATUS_PUBLISHED) {
                $this->stampFirstPublishedAt($entry);
            }

            $entry->save();
            $persistAt = $entry->updated_at?->copy() ?? now();

            return $entry->fresh();
        });
    }
}
