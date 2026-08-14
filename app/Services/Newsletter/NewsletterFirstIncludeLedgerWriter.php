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

    public function newDeliveryCycleId(): string
    {
        return (string) Str::uuid();
    }
}
