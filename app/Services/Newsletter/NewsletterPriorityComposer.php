<?php

namespace App\Services\Newsletter;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\NewsletterPendingPriorityChange;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Collection;

final class NewsletterPriorityComposer
{
    /**
     * @param  Collection<int, NewsletterPendingPriorityChange>  $pendingRows
     */
    public function compose(
        NewsletterSubscription $subscription,
        Collection $pendingRows,
        string $unsubscribeUrl
    ): NewsletterPriorityMailPayload {
        $user = $subscription->user;
        $recipient = (string) $user->email;

        $items = $pendingRows
            ->map(fn (NewsletterPendingPriorityChange $pending) => $this->changeItem($pending))
            ->filter()
            ->sort(function (array $a, array $b): int {
                $orgCmp = ($a['organizer_sort'] ?? 0) <=> ($b['organizer_sort'] ?? 0);
                if ($orgCmp !== 0) {
                    return $orgCmp;
                }

                $nameCmp = strcmp((string) $a['organizer_name'], (string) $b['organizer_name']);
                if ($nameCmp !== 0) {
                    return $nameCmp;
                }

                return $a['cultural_event_entry_id'] <=> $b['cultural_event_entry_id'];
            })
            ->values();

        $groups = [];
        foreach ($items->groupBy(fn (array $item) => $item['organizer_id'] ?? 0) as $bucket) {
            $first = $bucket->first();
            $groups[] = [
                'organizer_id' => $first['organizer_id'] ?? null,
                'organizer_name' => (string) ($first['organizer_name'] ?? 'Bez organizatora'),
                'organizer_url' => null,
                'changes' => $bucket
                    ->map(function (array $item): array {
                        unset($item['organizer_id'], $item['organizer_name'], $item['organizer_sort']);

                        return $item;
                    })
                    ->values()
                    ->all(),
            ];
        }

        $payloadItems = $items
            ->map(function (array $item): array {
                unset($item['organizer_id'], $item['organizer_name'], $item['organizer_sort']);

                return $item;
            })
            ->values()
            ->all();

        return new NewsletterPriorityMailPayload(
            subscriptionId: (int) $subscription->id,
            recipientEmail: $recipient,
            unsubscribeUrl: $unsubscribeUrl,
            groups: array_values($groups),
            items: $payloadItems,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function changeItem(NewsletterPendingPriorityChange $pending): ?array
    {
        $event = $pending->event;
        if (! $event instanceof CulturalEventEntry) {
            $event = CulturalEventEntry::query()
                ->with(['organizer', 'occurrences.location'])
                ->find($pending->cultural_event_entry_id);
        } else {
            $event->loadMissing(['organizer', 'occurrences.location']);
        }

        if (! $event instanceof CulturalEventEntry) {
            return null;
        }

        $occurrence = null;
        if ($pending->cultural_occurrence_id !== null) {
            $occurrence = $event->occurrences->firstWhere('id', (int) $pending->cultural_occurrence_id)
                ?? CulturalOccurrence::query()->with('location')->find($pending->cultural_occurrence_id);
        }

        $termDate = null;
        $termTime = null;
        $location = null;
        $occurrenceStatus = null;
        if ($occurrence instanceof CulturalOccurrence) {
            $datum = $occurrence->datum;
            $termDate = $datum instanceof \DateTimeInterface
                ? $datum->format('d.m.Y')
                : (string) $datum;
            $rawTime = trim((string) ($occurrence->vrijeme_od ?? ''));
            if ($rawTime !== '' && ! $occurrence->cjelodnevno) {
                $termTime = $rawTime;
            }
            $location = $occurrence->publicLocationDisplayName();
            $occurrenceStatus = $occurrence->status;
        }

        $organizerId = $event->organizer_id !== null ? (int) $event->organizer_id : null;

        return [
            'pending_id' => (int) $pending->id,
            'change_kind' => $pending->change_kind,
            'change_label' => $this->label($pending->change_kind),
            'change_control_key' => $pending->change_control_key,
            'cultural_event_entry_id' => (int) $event->id,
            'cultural_occurrence_id' => $occurrence instanceof CulturalOccurrence ? (int) $occurrence->id : null,
            'naslov' => (string) $event->naslov,
            'detail_url' => route('cultural-calendar.show', $event->id),
            'term_date' => $termDate,
            'term_time' => $termTime,
            'location' => $location,
            'occurrence_status' => $occurrenceStatus,
            'organizer_id' => $organizerId,
            'organizer_name' => $organizerId === null
                ? 'Bez organizatora'
                : (string) $event->organizer?->naziv,
            'organizer_sort' => $organizerId === null ? 1 : 0,
        ];
    }

    private function label(string $kind): string
    {
        return match ($kind) {
            NewsletterPendingPriorityChange::KIND_EVENT_CANCELLED => 'Događaj je otkazan',
            NewsletterPendingPriorityChange::KIND_OCCURRENCE_CANCELLED => 'Termin je otkazan',
            NewsletterPendingPriorityChange::KIND_POSTPONED => 'Termin je odgođen',
            NewsletterPendingPriorityChange::KIND_DATETIME_CHANGED => 'Promijenjen je datum ili vrijeme održavanja',
            NewsletterPendingPriorityChange::KIND_LOCATION_CHANGED => 'Promijenjena je lokacija održavanja',
            default => 'Važna izmjena događaja',
        };
    }
}
