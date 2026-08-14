<?php

namespace App\Services\Newsletter;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\NewsletterSubscription;
use App\Services\CulturalCalendar\CulturalPublicCardOccurrenceCriteria;
use Illuminate\Support\Collection;

final class NewsletterFirstIncludeComposer
{
    /**
     * @param  Collection<int, CulturalEventEntry>  $events
     */
    public function compose(
        NewsletterSubscription $subscription,
        Collection $events,
        string $unsubscribeUrl
    ): NewsletterFirstIncludeMailPayload {
        $user = $subscription->user;
        $recipient = (string) $user->email;

        $sorted = $events
            ->unique(fn (CulturalEventEntry $event) => (int) $event->id)
            ->sort(function (CulturalEventEntry $a, CulturalEventEntry $b): int {
                $orgCmp = $this->organizerSortKey($a) <=> $this->organizerSortKey($b);
                if ($orgCmp !== 0) {
                    return $orgCmp;
                }

                $nameCmp = strcmp($this->organizerName($a), $this->organizerName($b));
                if ($nameCmp !== 0) {
                    return $nameCmp;
                }

                $nextCmp = strcmp($this->nextOccurrenceSortKey($a), $this->nextOccurrenceSortKey($b));
                if ($nextCmp !== 0) {
                    return $nextCmp;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        $groups = [];
        foreach ($sorted->groupBy(fn (CulturalEventEntry $event) => $event->organizer_id ?? 0) as $bucket) {
            /** @var Collection<int, CulturalEventEntry> $bucket */
            $first = $bucket->first();
            $organizerId = $first?->organizer_id !== null ? (int) $first->organizer_id : null;

            $eventItems = $bucket
                ->sort(function (CulturalEventEntry $a, CulturalEventEntry $b): int {
                    $nextCmp = strcmp($this->nextOccurrenceSortKey($a), $this->nextOccurrenceSortKey($b));
                    if ($nextCmp !== 0) {
                        return $nextCmp;
                    }

                    return $a->id <=> $b->id;
                })
                ->values()
                ->map(fn (CulturalEventEntry $event) => $this->eventItem($event))
                ->all();

            $groups[] = [
                'organizer_id' => $organizerId,
                'organizer_name' => $organizerId === null
                    ? 'Bez organizatora'
                    : (string) $first->organizer?->naziv,
                'organizer_url' => null,
                'events' => $eventItems,
            ];
        }

        $eventIds = $sorted->map(fn (CulturalEventEntry $event) => (int) $event->id)->values()->all();
        $snapshot = $sorted
            ->map(fn (CulturalEventEntry $event) => [
                'id' => (int) $event->id,
                'naslov' => (string) $event->naslov,
            ])
            ->all();

        return new NewsletterFirstIncludeMailPayload(
            subscriptionId: (int) $subscription->id,
            recipientEmail: $recipient,
            unsubscribeUrl: $unsubscribeUrl,
            groups: array_values($groups),
            eventIds: $eventIds,
            snapshotEvents: $snapshot,
        );
    }

    /**
     * @return array{
     *     id: int,
     *     naslov: string,
     *     detail_url: string,
     *     primary: array{date: string, time: ?string, location: ?string},
     *     additional_terms: list<array{date: string, time: ?string, location: ?string}>
     * }
     */
    private function eventItem(CulturalEventEntry $event): array
    {
        $terms = CulturalPublicCardOccurrenceCriteria::filterAndSortCollection(
            $event->occurrences
        )->map(fn (CulturalOccurrence $occurrence) => $this->term($occurrence))->values();

        $primary = $terms->first() ?? [
            'date' => '',
            'time' => null,
            'location' => null,
        ];
        $additional = $terms->slice(1)->values()->all();

        return [
            'id' => (int) $event->id,
            'naslov' => (string) $event->naslov,
            'detail_url' => route('cultural-calendar.show', $event->id),
            'primary' => $primary,
            'additional_terms' => $additional,
        ];
    }

    /**
     * @return array{date: string, time: ?string, location: ?string}
     */
    private function term(CulturalOccurrence $occurrence): array
    {
        $datum = $occurrence->datum;
        $date = $datum instanceof \DateTimeInterface
            ? $datum->format('d.m.Y')
            : (string) $datum;

        $time = null;
        $rawTime = trim((string) ($occurrence->vrijeme_od ?? ''));
        if ($rawTime !== '' && ! $occurrence->cjelodnevno) {
            $time = $rawTime;
        }

        $location = $occurrence->publicLocationDisplayName();

        return [
            'date' => $date,
            'time' => $time,
            'location' => $location,
        ];
    }

    private function organizerSortKey(CulturalEventEntry $event): int
    {
        return $event->organizer_id === null ? 1 : 0;
    }

    private function organizerName(CulturalEventEntry $event): string
    {
        if ($event->organizer_id === null) {
            return 'Bez organizatora';
        }

        return (string) $event->organizer?->naziv;
    }

    private function nextOccurrenceSortKey(CulturalEventEntry $event): string
    {
        $next = CulturalPublicCardOccurrenceCriteria::filterAndSortCollection(
            $event->occurrences
        )->first();

        if (! $next instanceof CulturalOccurrence) {
            return '9999-99-99|99:99:99|'.$event->id;
        }

        $datum = $next->datum;
        $date = $datum instanceof \DateTimeInterface
            ? $datum->format('Y-m-d')
            : (string) $datum;
        $time = trim((string) ($next->vrijeme_od ?? ''));
        if ($time === '') {
            $time = '00:00:00';
        }

        return $date.'|'.$time.'|'.$event->id;
    }
}
