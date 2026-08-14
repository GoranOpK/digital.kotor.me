<?php

namespace App\Services\Newsletter;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\NewsletterPendingPriorityChange;

/**
 * Registers Promjena na čekanju after a persisted business transition.
 * Does not send mail. Does not write delivery ledger.
 */
final class NewsletterPriorityChangeRecorder
{
    public function recordEventCancelled(CulturalEventEntry $event): void
    {
        $this->supersedeOpenForEvent((int) $event->id);

        $this->upsertPending(
            $event,
            null,
            NewsletterPendingPriorityChange::KIND_EVENT_CANCELLED,
            $this->eventCancelledKey((int) $event->id),
            $this->eventState($event, NewsletterPendingPriorityChange::KIND_EVENT_CANCELLED)
        );
    }

    public function recordOccurrenceCancelled(CulturalOccurrence $occurrence): void
    {
        $event = $occurrence->eventEntry;
        if (! $this->isPublishedSubject($event)) {
            return;
        }

        $this->upsertPending(
            $event,
            $occurrence,
            NewsletterPendingPriorityChange::KIND_OCCURRENCE_CANCELLED,
            $this->occurrenceKindKey(
                NewsletterPendingPriorityChange::KIND_OCCURRENCE_CANCELLED,
                (int) $event->id,
                (int) $occurrence->id
            ),
            $this->occurrenceState($event, $occurrence, NewsletterPendingPriorityChange::KIND_OCCURRENCE_CANCELLED)
        );
    }

    public function recordPostponed(CulturalOccurrence $occurrence): void
    {
        $event = $occurrence->eventEntry;
        if (! $this->isPublishedSubject($event)) {
            return;
        }

        $this->upsertPending(
            $event,
            $occurrence,
            NewsletterPendingPriorityChange::KIND_POSTPONED,
            $this->occurrenceKindKey(
                NewsletterPendingPriorityChange::KIND_POSTPONED,
                (int) $event->id,
                (int) $occurrence->id
            ),
            $this->occurrenceState($event, $occurrence, NewsletterPendingPriorityChange::KIND_POSTPONED)
        );
    }

    public function recordDatetimeChanged(CulturalOccurrence $occurrence): void
    {
        $event = $occurrence->eventEntry;
        if (! $this->isPublishedSubject($event)) {
            return;
        }

        $this->upsertPending(
            $event,
            $occurrence,
            NewsletterPendingPriorityChange::KIND_DATETIME_CHANGED,
            $this->datetimeKey($event, $occurrence),
            $this->occurrenceState($event, $occurrence, NewsletterPendingPriorityChange::KIND_DATETIME_CHANGED)
        );
    }

    public function recordLocationChanged(CulturalOccurrence $occurrence): void
    {
        $event = $occurrence->eventEntry;
        if (! $this->isPublishedSubject($event)) {
            return;
        }

        $this->upsertPending(
            $event,
            $occurrence,
            NewsletterPendingPriorityChange::KIND_LOCATION_CHANGED,
            $this->locationKey($event, $occurrence),
            $this->occurrenceState($event, $occurrence, NewsletterPendingPriorityChange::KIND_LOCATION_CHANGED)
        );
    }

    /**
     * Proposal OCC update: one pending row with last significant kind.
     */
    public function recordPublishedOccurrenceFieldChanges(
        CulturalOccurrence $before,
        CulturalOccurrence $after
    ): void {
        $event = $after->eventEntry;
        if (! $this->isPublishedSubject($event)) {
            return;
        }

        $datetimeChanged = $this->datetimeFingerprint($before) !== $this->datetimeFingerprint($after);
        $locationChanged = $this->locationFingerprint($before) !== $this->locationFingerprint($after);

        if (! $datetimeChanged && ! $locationChanged) {
            return;
        }

        if ($datetimeChanged) {
            $this->recordDatetimeChanged($after);

            return;
        }

        $this->recordLocationChanged($after);
    }

    private function upsertPending(
        CulturalEventEntry $event,
        ?CulturalOccurrence $occurrence,
        string $kind,
        string $changeControlKey,
        array $effectiveState
    ): void {
        $eventId = (int) $event->id;
        $occurrenceId = $occurrence !== null ? (int) $occurrence->id : null;

        /** @var NewsletterPendingPriorityChange|null $existing */
        $existing = NewsletterPendingPriorityChange::query()
            ->where('cultural_event_entry_id', $eventId)
            ->where('status', NewsletterPendingPriorityChange::STATUS_PENDING)
            ->where(function ($query) use ($occurrenceId): void {
                if ($occurrenceId === null) {
                    $query->whereNull('cultural_occurrence_id');
                } else {
                    $query->where('cultural_occurrence_id', $occurrenceId);
                }
            })
            ->lockForUpdate()
            ->first();

        if ($existing !== null && $existing->change_control_key === $changeControlKey) {
            $existing->effective_state = $effectiveState;
            $existing->detected_at = now();
            $existing->save();

            return;
        }

        if ($existing !== null) {
            $existing->status = NewsletterPendingPriorityChange::STATUS_SUPERSEDED;
            $existing->save();
        }

        NewsletterPendingPriorityChange::query()->create([
            'cultural_event_entry_id' => $eventId,
            'cultural_occurrence_id' => $occurrenceId,
            'change_kind' => $kind,
            'change_control_key' => $changeControlKey,
            'effective_state' => $effectiveState,
            'detected_at' => now(),
            'status' => NewsletterPendingPriorityChange::STATUS_PENDING,
        ]);
    }

    private function supersedeOpenForEvent(int $eventId): void
    {
        NewsletterPendingPriorityChange::query()
            ->where('cultural_event_entry_id', $eventId)
            ->where('status', NewsletterPendingPriorityChange::STATUS_PENDING)
            ->lockForUpdate()
            ->get()
            ->each(function (NewsletterPendingPriorityChange $row): void {
                $row->status = NewsletterPendingPriorityChange::STATUS_SUPERSEDED;
                $row->save();
            });
    }

    private function isPublishedSubject(?CulturalEventEntry $event): bool
    {
        return $event instanceof CulturalEventEntry && $event->isPublished();
    }

    private function eventCancelledKey(int $eventId): string
    {
        return NewsletterPendingPriorityChange::KIND_EVENT_CANCELLED.':'.$eventId;
    }

    private function occurrenceKindKey(string $kind, int $eventId, int $occurrenceId): string
    {
        return $kind.':'.$eventId.':'.$occurrenceId;
    }

    private function datetimeKey(CulturalEventEntry $event, CulturalOccurrence $occurrence): string
    {
        return NewsletterPendingPriorityChange::KIND_DATETIME_CHANGED
            .':'.(int) $event->id
            .':'.(int) $occurrence->id
            .':'.sha1($this->datetimeFingerprint($occurrence));
    }

    private function locationKey(CulturalEventEntry $event, CulturalOccurrence $occurrence): string
    {
        return NewsletterPendingPriorityChange::KIND_LOCATION_CHANGED
            .':'.(int) $event->id
            .':'.(int) $occurrence->id
            .':'.sha1($this->locationFingerprint($occurrence));
    }

    private function datetimeFingerprint(CulturalOccurrence $occurrence): string
    {
        $datum = $occurrence->datum;
        $date = $datum instanceof \DateTimeInterface
            ? $datum->format('Y-m-d')
            : (string) $datum;

        return implode('|', [
            $date,
            (string) ($occurrence->vrijeme_od ?? ''),
            (string) ($occurrence->vrijeme_do ?? ''),
            $occurrence->cjelodnevno ? '1' : '0',
        ]);
    }

    private function locationFingerprint(CulturalOccurrence $occurrence): string
    {
        return implode('|', [
            (string) ($occurrence->location_id ?? ''),
            trim((string) ($occurrence->location_manual_name ?? '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventState(CulturalEventEntry $event, string $kind): array
    {
        return [
            'change_kind' => $kind,
            'event_id' => (int) $event->id,
            'naslov' => (string) $event->naslov,
            'occurrence_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function occurrenceState(
        CulturalEventEntry $event,
        CulturalOccurrence $occurrence,
        string $kind
    ): array {
        $datum = $occurrence->datum;

        return [
            'change_kind' => $kind,
            'event_id' => (int) $event->id,
            'naslov' => (string) $event->naslov,
            'occurrence_id' => (int) $occurrence->id,
            'datum' => $datum instanceof \DateTimeInterface ? $datum->format('Y-m-d') : (string) $datum,
            'vrijeme_od' => $occurrence->vrijeme_od,
            'cjelodnevno' => (bool) $occurrence->cjelodnevno,
            'location' => $occurrence->publicLocationDisplayName(),
            'occurrence_status' => $occurrence->status,
        ];
    }
}
