<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterDeliveryLedger;
use App\Models\NewsletterSubscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NewsletterFirstIncludeLedgerWriter
{
    /**
     * @param  list<int>  $eventIds
     * @param  list<array{id: int, naslov: string}>  $snapshotEvents
     */
    public function recordSuccessfulFirstInclude(
        NewsletterSubscription $subscription,
        array $eventIds,
        string $deliveryCycleId,
        CarbonInterface $sentAt,
        array $snapshotEvents
    ): void {
        $eventIds = array_values(array_unique(array_map('intval', $eventIds)));
        if ($eventIds === []) {
            throw new \InvalidArgumentException('Cannot write first_include ledger without events.');
        }

        DB::transaction(function () use ($subscription, $eventIds, $deliveryCycleId, $sentAt, $snapshotEvents): void {
            foreach ($eventIds as $eventId) {
                $itemSnapshot = collect($snapshotEvents)->firstWhere('id', $eventId);

                NewsletterDeliveryLedger::query()->create([
                    'newsletter_subscription_id' => $subscription->id,
                    'cultural_event_entry_id' => $eventId,
                    'cultural_occurrence_id' => null,
                    'entry_type' => NewsletterDeliveryLedger::TYPE_FIRST_INCLUDE,
                    'change_control_key' => null,
                    'delivery_cycle_id' => $deliveryCycleId,
                    'payload_snapshot' => $itemSnapshot !== null ? ['event' => $itemSnapshot] : null,
                    'sent_at' => $sentAt,
                ]);
            }
        });
    }

    /**
     * @param  list<array{
     *     cultural_event_entry_id: int,
     *     cultural_occurrence_id: ?int,
     *     change_control_key: string,
     *     payload_snapshot: array<string, mixed>|null
     * }>  $items
     */
    public function recordSuccessfulPriorityChanges(
        NewsletterSubscription $subscription,
        array $items,
        string $deliveryCycleId,
        CarbonInterface $sentAt
    ): void {
        if ($items === []) {
            throw new \InvalidArgumentException('Cannot write priority_change ledger without items.');
        }

        DB::transaction(function () use ($subscription, $items, $deliveryCycleId, $sentAt): void {
            foreach ($items as $item) {
                NewsletterDeliveryLedger::query()->create([
                    'newsletter_subscription_id' => $subscription->id,
                    'cultural_event_entry_id' => (int) $item['cultural_event_entry_id'],
                    'cultural_occurrence_id' => $item['cultural_occurrence_id'],
                    'entry_type' => NewsletterDeliveryLedger::TYPE_PRIORITY_CHANGE,
                    'change_control_key' => $item['change_control_key'],
                    'delivery_cycle_id' => $deliveryCycleId,
                    'payload_snapshot' => $item['payload_snapshot'],
                    'sent_at' => $sentAt,
                ]);
            }
        });
    }

    public function hasSuccessfulPriorityChange(
        NewsletterSubscription $subscription,
        string $changeControlKey
    ): bool {
        return NewsletterDeliveryLedger::query()
            ->where('newsletter_subscription_id', $subscription->id)
            ->where('entry_type', NewsletterDeliveryLedger::TYPE_PRIORITY_CHANGE)
            ->where('change_control_key', $changeControlKey)
            ->exists();
    }

    public function newDeliveryCycleId(): string
    {
        return (string) Str::uuid();
    }
}
