<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle Događaja (TS-003). Odvojen od statusa Održavanja.
 */
final class EventLifecycle
{
    /**
     * Nacrt → Na odobrenju.
     */
    public function submitForApproval(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $this->assertReadyForPublishGate($entry);

        return $this->apply($entry, CulturalEventEntry::STATUS_PENDING_APPROVAL, $actor, markSubmitted: true);
    }

    /**
     * Na odobrenju → Objavljen.
     */
    public function approve(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_PUBLISHED);
        $this->assertReadyForPublishGate($entry);

        return $this->apply($entry, CulturalEventEntry::STATUS_PUBLISHED, $actor);
    }

    /**
     * Na odobrenju → Nacrt.
     */
    public function returnToDraft(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_DRAFT);

        return $this->apply($entry, CulturalEventEntry::STATUS_DRAFT, $actor);
    }

    /**
     * Nacrt → Objavljen (samo bez Organizatora).
     */
    public function publishDirectly(CulturalEventEntry $entry, User $actor): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_PUBLISHED);

        if ($entry->organizer_id !== null) {
            throw new CulturalEventDomainException(
                'Direktna objava dozvoljena je samo Događaju bez Organizatora.'
            );
        }

        $this->assertReadyForPublishGate($entry);

        return $this->apply($entry, CulturalEventEntry::STATUS_PUBLISHED, $actor, markSubmitted: true);
    }

    /**
     * Objavljen → Otkazan (terminalan za republish).
     */
    public function cancel(CulturalEventEntry $entry, User $actor, ?string $reason = null): CulturalEventEntry
    {
        $this->assertTransition($entry, CulturalEventEntry::STATUS_CANCELLED);

        return DB::transaction(function () use ($entry, $actor, $reason) {
            $entry->status = CulturalEventEntry::STATUS_CANCELLED;
            $entry->cancellation_reason = $reason;
            $entry->last_modified_by = $actor->id;
            $entry->featured = false;
            $entry->save();

            return $entry->fresh();
        });
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
     */
    public function archiveIfEligible(CulturalEventEntry $entry): CulturalEventEntry
    {
        if (! in_array($entry->status, [
            CulturalEventEntry::STATUS_PUBLISHED,
            CulturalEventEntry::STATUS_CANCELLED,
        ], true)) {
            throw new CulturalEventDomainException(
                'Arhiviranje je dozvoljeno samo iz statusa Objavljen ili Otkazan.'
            );
        }

        $hasOpen = $entry->occurrences()
            ->whereIn('status', [
                CulturalOccurrence::STATUS_PLANNED,
                CulturalOccurrence::STATUS_POSTPONED,
            ])
            ->exists();

        if ($hasOpen) {
            throw new CulturalEventDomainException(
                'Događaj se ne može arhivirati dok postoji Održavanje u statusu Planiran ili Odgođen.'
            );
        }

        $entry->status = CulturalEventEntry::STATUS_ARCHIVED;
        $entry->featured = false;
        $entry->save();

        return $entry->fresh();
    }

    /**
     * Generički prelaz sa validacijom dozvoljenog skupa (za testove invalidnih vrijednosti).
     */
    public function transitionTo(CulturalEventEntry $entry, string $target, User $actor): CulturalEventEntry
    {
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
            return $this->returnToDraft($entry, $actor);
        }

        if ($target === CulturalEventEntry::STATUS_CANCELLED) {
            return $this->cancel($entry, $actor);
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

    private function assertReadyForPublishGate(CulturalEventEntry $entry): void
    {
        $naslov = trim((string) $entry->naslov);
        if ($naslov === '') {
            throw new CulturalEventDomainException('Naslov je obavezan za slanje/objavu.');
        }

        if ($entry->category_id === null) {
            throw new CulturalEventDomainException('Primarna kategorija je obavezna za slanje/objavu.');
        }

        if ($entry->occurrences()->count() < 1) {
            throw new CulturalEventDomainException(
                'Za slanje/objavu je potrebno najmanje jedno Održavanje.'
            );
        }
    }

    private function apply(
        CulturalEventEntry $entry,
        string $status,
        User $actor,
        bool $markSubmitted = false,
    ): CulturalEventEntry {
        return DB::transaction(function () use ($entry, $status, $actor, $markSubmitted) {
            $entry->status = $status;
            $entry->last_modified_by = $actor->id;

            if ($markSubmitted && $entry->first_submitted_at === null) {
                $entry->first_submitted_at = now();
            }

            $entry->save();

            return $entry->fresh();
        });
    }
}
