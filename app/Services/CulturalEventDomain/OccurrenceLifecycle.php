<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use App\Services\Newsletter\NewsletterPriorityChangeRecorder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle Održavanja (TS-004). Ne mijenja status Događaja (BR-134).
 * Lock order statusnih akcija: Event → Occurrence (bez Proposal).
 */
final class OccurrenceLifecycle
{
    public function __construct(
        private readonly OccurrenceWriter $writer,
        private readonly NewsletterPriorityChangeRecorder $priorityChangeRecorder,
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public function postpone(CulturalOccurrence $occurrence, ?string $reason = null, ?User $actor = null): CulturalOccurrence
    {
        $reason = $this->normalizeOptionalReason($reason);

        $persistAt = now();
        $updated = $this->withLockedOccurrence(
            $occurrence,
            CulturalOccurrence::STATUS_POSTPONED,
            function (CulturalOccurrence $locked) use ($reason, &$persistAt): CulturalOccurrence {
                $locked->status = CulturalOccurrence::STATUS_POSTPONED;
                $locked->postponement_reason = $reason;
                $locked->save();
                $persistAt = $locked->updated_at?->copy() ?? now();

                $fresh = $locked->fresh(['eventEntry']);
                $this->priorityChangeRecorder->recordPostponed($fresh);

                return $fresh;
            }
        );

        if ($actor !== null) {
            $this->emitOccurrenceUser($updated, $actor, CulturalActivityCatalog::EV_11, $persistAt);
        }

        return $updated;
    }

    /**
     * Odgođen → Planiran uz novi termin (isti zapis). Bez Lokacije.
     *
     * @param  array{
     *     datum: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool
     * }  $newTermin
     */
    public function resumeWithNewTermin(CulturalOccurrence $occurrence, array $newTermin, ?User $actor = null): CulturalOccurrence
    {
        $persistAt = now();
        $updated = $this->withLockedOccurrence(
            $occurrence,
            CulturalOccurrence::STATUS_PLANNED,
            function (CulturalOccurrence $locked) use ($newTermin, &$persistAt): CulturalOccurrence {
                $this->writer->applyTerminFromLifecycle($locked, $newTermin);
                $locked->refresh();
                $locked->status = CulturalOccurrence::STATUS_PLANNED;
                $locked->save();
                $persistAt = $locked->updated_at?->copy() ?? now();

                $fresh = $locked->fresh(['eventEntry', 'location']);
                $this->priorityChangeRecorder->recordDatetimeChanged($fresh);

                return $fresh;
            }
        );

        if ($actor !== null) {
            $this->emitOccurrenceUser($updated, $actor, CulturalActivityCatalog::EV_13, $persistAt);
        }

        return $updated;
    }

    public function cancel(CulturalOccurrence $occurrence, ?string $reason = null, ?User $actor = null): CulturalOccurrence
    {
        $reason = $this->normalizeOptionalReason($reason);

        $updated = $this->withLockedOccurrence(
            $occurrence,
            CulturalOccurrence::STATUS_CANCELLED,
            function (CulturalOccurrence $locked) use ($reason): CulturalOccurrence {
                $locked->status = CulturalOccurrence::STATUS_CANCELLED;
                $locked->cancellation_reason = $reason;
                $locked->save();

                $fresh = $locked->fresh(['eventEntry']);
                $this->priorityChangeRecorder->recordOccurrenceCancelled($fresh);

                return $fresh;
            }
        );

        if ($actor !== null) {
            $this->emitOccurrenceUser($updated, $actor, CulturalActivityCatalog::EV_12);
        }

        return $updated;
    }

    /**
     * Sistem: Planiran → Završen (eksplicitni prelaz; testovi / interni pozivi).
     * Lock order: Event → Occurrence.
     */
    public function markFinished(CulturalOccurrence $occurrence): CulturalOccurrence
    {
        $updated = $this->withLockedOccurrence(
            $occurrence,
            CulturalOccurrence::STATUS_FINISHED,
            function (CulturalOccurrence $locked): CulturalOccurrence {
                if (! $locked->isPlanned()) {
                    throw new CulturalEventDomainException(
                        'Završen je dozvoljen samo iz statusa Planiran (Sistem).'
                    );
                }

                $locked->status = CulturalOccurrence::STATUS_FINISHED;
                $locked->save();

                return $locked->fresh();
            }
        );

        $this->emitOccurrenceSystem($updated);

        return $updated;
    }

    /**
     * PO-AUTO-02: završi Planirano Održavanje samo ako je i dalje Planiran i isteklo u `$now`.
     * Race-safe: ako je u međuvremenu Odgođen/Otkazan ili Event Otkazan/Arhiviran — bez promjene (null).
     * Lock order: Event → Occurrence.
     */
    public function finishIfExpiredAt(
        CulturalOccurrence $occurrence,
        ?CarbonInterface $now = null,
    ): ?CulturalOccurrence {
        $now ??= now((string) config('app.timezone'));

        $finished = DB::transaction(function () use ($occurrence, $now) {
            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($occurrence->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->isCancelled() || $entry->status === CulturalEventEntry::STATUS_ARCHIVED) {
                return null;
            }

            /** @var CulturalOccurrence $locked */
            $locked = CulturalOccurrence::query()
                ->whereKey($occurrence->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->event_entry_id !== (int) $entry->id) {
                return null;
            }

            if (! $locked->isPlanned()) {
                return null;
            }

            if (! $locked->isExpiredAt($now)) {
                return null;
            }

            $locked->status = CulturalOccurrence::STATUS_FINISHED;
            $locked->save();

            return $locked->fresh();
        });

        if ($finished !== null) {
            $this->emitOccurrenceSystem($finished);
        }

        return $finished;
    }

    public function transitionTo(CulturalOccurrence $occurrence, string $target): CulturalOccurrence
    {
        if (! in_array($target, CulturalOccurrence::STATUSES, true)) {
            throw new CulturalEventDomainException('Nepoznat status Održavanja: '.$target);
        }

        return match ($target) {
            CulturalOccurrence::STATUS_POSTPONED => $this->postpone($occurrence),
            CulturalOccurrence::STATUS_CANCELLED => $this->cancel($occurrence),
            CulturalOccurrence::STATUS_FINISHED => $this->markFinished($occurrence),
            CulturalOccurrence::STATUS_PLANNED => throw new CulturalEventDomainException(
                'Povratak u Planiran zahtijeva novi termin (resumeWithNewTermin).'
            ),
            default => throw new CulturalEventDomainException('Nepodržan prelaz.'),
        };
    }

    /**
     * Potvrda: otkaz jednog Održavanja ne mijenja status Događaja.
     *
     * @return array{occurrence: CulturalOccurrence, event_status_before: ?string, event_status_after: ?string}
     */
    public function cancelWithoutAffectingEvent(
        CulturalOccurrence $occurrence,
        ?string $reason = null,
        ?User $actor = null,
    ): array {
        $entry = $occurrence->eventEntry;
        $eventStatusBefore = $entry?->status;

        $updated = $this->cancel($occurrence, $reason, $actor);

        $entry?->refresh();

        return [
            'occurrence' => $updated,
            'event_status_before' => $eventStatusBefore,
            'event_status_after' => $entry?->status,
        ];
    }

    private function normalizeOptionalReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        $trimmed = trim($reason);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  callable(CulturalOccurrence): CulturalOccurrence  $mutator
     */
    private function withLockedOccurrence(
        CulturalOccurrence $occurrence,
        string $targetStatus,
        callable $mutator,
    ): CulturalOccurrence {
        return DB::transaction(function () use ($occurrence, $targetStatus, $mutator) {
            /** @var CulturalEventEntry $entry */
            $entry = CulturalEventEntry::query()
                ->whereKey($occurrence->event_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertParentAllowsLifecycleFromEntry($entry);

            /** @var CulturalOccurrence $locked */
            $locked = CulturalOccurrence::query()
                ->whereKey($occurrence->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->event_entry_id !== (int) $entry->id) {
                throw new CulturalEventDomainException('Održavanje ne pripada Događaju.');
            }

            $this->assertTransition($locked, $targetStatus);

            return $mutator($locked);
        });
    }

    private function assertParentAllowsLifecycleFromEntry(CulturalEventEntry $entry): void
    {
        if ($entry->isCancelled()) {
            throw new CulturalEventDomainException(
                'Otkazan Događaj je istorijski zapis; status Održavanja se ne može mijenjati.'
            );
        }

        if ($entry->status === CulturalEventEntry::STATUS_ARCHIVED) {
            throw new CulturalEventDomainException(
                'Arhiviran Događaj; status Održavanja se ne može mijenjati.'
            );
        }
    }

    private function assertTransition(CulturalOccurrence $occurrence, string $target): void
    {
        if (! $occurrence->canTransitionTo($target)) {
            throw new CulturalEventDomainException(sprintf(
                'Prelaz Održavanja %s → %s nije dozvoljen.',
                $occurrence->statusLabel(),
                CulturalOccurrence::STATUS_LABELS[$target] ?? $target
            ));
        }
    }

    private function emitOccurrenceUser(
        CulturalOccurrence $occurrence,
        User $actor,
        string $catalogId,
        ?CarbonInterface $persistAt = null,
    ): void {
        $eventId = $catalogId === CulturalActivityCatalog::EV_12
            ? CulturalActivityEventId::once($catalogId, (int) $occurrence->id)
            : CulturalActivityEventId::repeatable(
                $catalogId,
                (int) $occurrence->id,
                $this->occurrenceTerminIdentity($occurrence),
                $persistAt ?? $occurrence->updated_at ?? now()
            );

        $this->activityEmitter->emitUser(
            $catalogId,
            $eventId,
            $actor,
            (int) $occurrence->id,
            $persistAt ?? $occurrence->updated_at ?? now(),
            [
                'occurrence_id' => (int) $occurrence->id,
                'entry_id' => (int) $occurrence->event_entry_id,
            ],
            $occurrence->eventEntry?->organizer_id !== null ? (int) $occurrence->eventEntry->organizer_id : null,
        );
    }

    private function emitOccurrenceSystem(CulturalOccurrence $occurrence): void
    {
        $this->activityEmitter->emitSystem(
            CulturalActivityCatalog::EV_19,
            CulturalActivityEventId::once(CulturalActivityCatalog::EV_19, (int) $occurrence->id),
            (int) $occurrence->id,
            $occurrence->updated_at ?? now(),
            [
                'occurrence_id' => (int) $occurrence->id,
                'entry_id' => (int) $occurrence->event_entry_id,
            ],
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    private function occurrenceTerminIdentity(CulturalOccurrence $occurrence): array
    {
        $datum = $occurrence->datum;
        $datumString = $datum instanceof \DateTimeInterface
            ? $datum->format('Y-m-d')
            : (string) $datum;

        return [
            'datum' => $datumString,
            'vrijeme_od' => $occurrence->vrijeme_od,
            'vrijeme_do' => $occurrence->vrijeme_do,
            'cjelodnevno' => (bool) $occurrence->cjelodnevno ? 1 : 0,
            'location_id' => $occurrence->location_id !== null ? (int) $occurrence->location_id : null,
        ];
    }
}
