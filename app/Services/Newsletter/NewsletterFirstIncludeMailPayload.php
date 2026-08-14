<?php

namespace App\Services\Newsletter;

/**
 * Immutable composed first_include mail. No eligibility. No DB writes.
 *
 * @phpstan-type Term array{date: string, time: ?string, location: ?string}
 * @phpstan-type EventItem array{
 *     id: int,
 *     naslov: string,
 *     detail_url: string,
 *     primary: Term,
 *     additional_terms: list<Term>
 * }
 * @phpstan-type Group array{
 *     organizer_id: ?int,
 *     organizer_name: string,
 *     organizer_url: ?string,
 *     events: list<EventItem>
 * }
 */
final class NewsletterFirstIncludeMailPayload
{
    /**
     * @param  list<Group>  $groups
     * @param  list<int>  $eventIds
     * @param  list<array{id: int, naslov: string}>  $snapshotEvents
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $recipientEmail,
        public readonly string $unsubscribeUrl,
        public readonly array $groups,
        public readonly array $eventIds,
        public readonly array $snapshotEvents,
    ) {}
}
