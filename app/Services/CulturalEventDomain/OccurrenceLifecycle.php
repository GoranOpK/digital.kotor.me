<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
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
    ) {}

    public function postpone(CulturalOccurrence $occurrence, ?string $reason = null): CulturalOccurrence
    {
        $reason = $this->normalizeOptionalReason($reason);

        return $this->withLockedOccurrence(
            $occurrence,
            CulturalOccurrence::STATUS_POSTPONED,
            function (CulturalOccurrence $locked) use ($reason): CulturalOccurrence {
                $locked->status = CulturalOccurrence::STATUS_POSTPONED;
                $locked->postponement_reason = $reason;
                $locked->save();

                $fresh = $locked->fresh(['eventEntry']);
                $this->priorityChangeRecorder->recordPostponed($fresh);

                return $fresh;
            }
        );
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
    public function resumeWithNewTermin(CulturalOccurrence $occurrence, array $newTermin): CulturalOccurrence
    {
        return $this->withLockedOccurrence(
            $occurrence,
            CulturalOccurrence::STATUS_PLANNED,
            function (CulturalOccurrence $locked) use ($newTermin): CulturalOccurrence {
                $this->writer->applyTerminFromLifecycle($locked, $newTermin);
                $locked->refresh();
                $locked->status = CulturalOccurrence::STATUS_PLANNED;
                $locked->save();

                $fresh = $locked->fresh(['eventEntry', 'location']);
                $this->priorityChangeRecorder->recordDatetimeChanged($fresh);

                return $fresh;
            }
        );
    }

    public function cancel(CulturalOccurrence $occurrence, ?string $reason = null): CulturalOccurrence
    {
        $reason = $this->normalizeOptionalReason($reason);

        return $this->withLockedOccurrence(
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
    }

    /**
     * Sistem: Planiran → Završen (eksplicitni prelaz; testovi / interni pozivi).
     * Lock order: Event → Occurrence.
     */
    public function markFinished(CulturalOccurrence $occurrence): CulturalOccurrence
    {
        return $this->withLockedOccurrence(
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

        return DB::transaction(function () use ($occurrence, $now) {
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
    ): array {
        $entry = $occurrence->eventEntry;
        $eventStatusBefore = $entry?->status;

        $updated = $this->cancel($occurrence, $reason);

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
}
