<?php

namespace App\Services\Newsletter;

/**
 * @phpstan-type ChangeItem array{
 *     pending_id: int,
 *     change_kind: string,
 *     change_label: string,
 *     change_control_key: string,
 *     cultural_event_entry_id: int,
 *     cultural_occurrence_id: ?int,
 *     naslov: string,
 *     detail_url: string,
 *     term_date: ?string,
 *     term_time: ?string,
 *     location: ?string,
 *     occurrence_status: ?string
 * }
 * @phpstan-type Group array{
 *     organizer_id: ?int,
 *     organizer_name: string,
 *     organizer_url: ?string,
 *     changes: list<ChangeItem>
 * }
 */
final class NewsletterPriorityMailPayload
{
    /**
     * @param  list<Group>  $groups
     * @param  list<ChangeItem>  $items
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $recipientEmail,
        public readonly string $unsubscribeUrl,
        public readonly array $groups,
        public readonly array $items,
    ) {}
}
