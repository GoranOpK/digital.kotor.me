<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle Održavanja (TS-004). Ne mijenja status Događaja (BR-134).
 */
final class OccurrenceLifecycle
{
    public function __construct(
        private readonly OccurrenceWriter $writer,
    ) {}

    public function postpone(CulturalOccurrence $occurrence): CulturalOccurrence
    {
        $this->assertParentAllowsLifecycle($occurrence);
        $this->assertTransition($occurrence, CulturalOccurrence::STATUS_POSTPONED);

        $occurrence->status = CulturalOccurrence::STATUS_POSTPONED;
        $occurrence->save();

        return $occurrence->fresh();
    }

    /**
     * Odgođen → Planiran uz novi termin (isti zapis).
     *
     * @param  array{
     *     datum: string|\DateTimeInterface,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool,
     *     location_id?: ?int,
     *     location_manual_name?: ?string
     * }  $newTermin
     */
    public function resumeWithNewTermin(CulturalOccurrence $occurrence, array $newTermin): CulturalOccurrence
    {
        $this->assertParentAllowsLifecycle($occurrence);
        $this->assertTransition($occurrence, CulturalOccurrence::STATUS_PLANNED);

        return DB::transaction(function () use ($occurrence, $newTermin) {
            $this->writer->update($occurrence, $newTermin);
            $occurrence->refresh();
            $occurrence->status = CulturalOccurrence::STATUS_PLANNED;
            $occurrence->save();

            return $occurrence->fresh();
        });
    }

    public function cancel(CulturalOccurrence $occurrence): CulturalOccurrence
    {
        $this->assertParentAllowsLifecycle($occurrence);
        $this->assertTransition($occurrence, CulturalOccurrence::STATUS_CANCELLED);

        $occurrence->status = CulturalOccurrence::STATUS_CANCELLED;
        $occurrence->save();

        return $occurrence->fresh(['eventEntry']);
    }

    /**
     * Sistem: Planiran → Završen.
     */
    public function markFinished(CulturalOccurrence $occurrence): CulturalOccurrence
    {
        $this->assertParentAllowsLifecycle($occurrence);
        $this->assertTransition($occurrence, CulturalOccurrence::STATUS_FINISHED);

        if (! $occurrence->isPlanned()) {
            throw new CulturalEventDomainException(
                'Završen je dozvoljen samo iz statusa Planiran (Sistem).'
            );
        }

        $occurrence->status = CulturalOccurrence::STATUS_FINISHED;
        $occurrence->save();

        return $occurrence->fresh();
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
     */
    public function cancelWithoutAffectingEvent(CulturalOccurrence $occurrence): array
    {
        $entry = $occurrence->eventEntry;
        $eventStatusBefore = $entry?->status;

        $updated = $this->cancel($occurrence);

        $entry?->refresh();

        return [
            'occurrence' => $updated,
            'event_status_before' => $eventStatusBefore,
            'event_status_after' => $entry?->status,
        ];
    }

    private function assertParentAllowsLifecycle(CulturalOccurrence $occurrence): void
    {
        $entry = $occurrence->eventEntry;
        if ($entry === null) {
            throw new CulturalEventDomainException('Održavanje nema Događaj.');
        }

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
