<?php

namespace App\Services\Newsletter;

use App\Mail\CulturalCalendarPriorityNewsletterMail;
use App\Models\NewsletterPendingPriorityChange;
use App\Models\NewsletterSubscription;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NewsletterPriorityDeliveryService
{
    public function __construct(
        private readonly NewsletterPriorityRecipientResolver $recipients,
        private readonly FirstIncludeEligibilityService $eligibility,
        private readonly NewsletterPriorityComposer $composer,
        private readonly NewsletterFirstIncludeLedgerWriter $ledgerWriter,
        private readonly NewsletterOutboundMailer $mailer,
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    /**
     * @return array{inspected: int, sent: int, changes: int, skipped_empty: int, skipped_ineligible: int, failed: int, processed: int}
     */
    public function flushDueChanges(): array
    {
        $due = $this->duePending();
        $stats = [
            'inspected' => $due->count(),
            'sent' => 0,
            'changes' => 0,
            'skipped_empty' => 0,
            'skipped_ineligible' => 0,
            'failed' => 0,
            'processed' => 0,
        ];

        $bySubscription = [];
        foreach ($due as $pending) {
            foreach ($this->recipients->matchingSubscriptions($pending) as $subscription) {
                $id = (int) $subscription->id;
                $bySubscription[$id]['subscription'] = $subscription;
                $bySubscription[$id]['pending'][] = $pending;
            }
        }

        foreach ($bySubscription as $pack) {
            /** @var NewsletterSubscription $subscription */
            $subscription = $pack['subscription'];
            /** @var list<NewsletterPendingPriorityChange> $pendingRows */
            $pendingRows = $pack['pending'];

            $result = $this->deliverForSubscription($subscription, collect($pendingRows));

            if ($result->wasSent()) {
                $stats['sent']++;
                $stats['changes'] += $result->changesDelivered;
                continue;
            }

            if ($result->wasFailed()) {
                $stats['failed']++;
                continue;
            }

            if ($result->wasSkippedEmpty()) {
                $stats['skipped_empty']++;
                continue;
            }

            $stats['skipped_ineligible']++;
        }

        foreach ($due as $pending) {
            if ($this->markProcessedIfExhausted($pending->fresh() ?? $pending)) {
                $stats['processed']++;
            }
        }

        return $stats;
    }

    /**
     * @param  Collection<int, NewsletterPendingPriorityChange>  $pendingRows
     */
    public function deliverForSubscription(
        NewsletterSubscription $subscription,
        Collection $pendingRows
    ): NewsletterPriorityDeliveryResult {
        $prepared = $this->prepareLocked($subscription, $pendingRows);
        if (($prepared['result'] ?? null) instanceof NewsletterPriorityDeliveryResult) {
            return $prepared['result'];
        }

        /** @var NewsletterPriorityMailPayload $payload */
        $payload = $prepared['payload'];
        $lockedSubscription = $prepared['subscription'];

        try {
            $this->mailer->send(
                $payload->recipientEmail,
                new CulturalCalendarPriorityNewsletterMail($payload)
            );
        } catch (\Throwable $e) {
            return new NewsletterPriorityDeliveryResult(
                NewsletterPriorityDeliveryResult::FAILED,
                0,
                $e->getMessage()
            );
        }

        $sentAt = now();
        $cycleId = $this->ledgerWriter->newDeliveryCycleId();
        $ledgerItems = array_map(function (array $item): array {
            return [
                'cultural_event_entry_id' => (int) $item['cultural_event_entry_id'],
                'cultural_occurrence_id' => $item['cultural_occurrence_id'],
                'change_control_key' => $item['change_control_key'],
                'payload_snapshot' => [
                    'change_kind' => $item['change_kind'],
                    'naslov' => $item['naslov'],
                ],
            ];
        }, $payload->items);

        try {
            $this->ledgerWriter->recordSuccessfulPriorityChanges(
                $lockedSubscription,
                $ledgerItems,
                $cycleId,
                $sentAt
            );
        } catch (\Throwable $e) {
            return new NewsletterPriorityDeliveryResult(
                NewsletterPriorityDeliveryResult::FAILED,
                0,
                'ledger_write_failed: '.$e->getMessage()
            );
        }

        $this->activityEmitter->emitSystem(
            CulturalActivityCatalog::NL_06,
            CulturalActivityEventId::once(CulturalActivityCatalog::NL_06, $cycleId),
            null,
            $sentAt,
            ['cycle_id' => $cycleId],
        );

        return new NewsletterPriorityDeliveryResult(
            NewsletterPriorityDeliveryResult::SENT,
            count($payload->items)
        );
    }

    /**
     * @param  Collection<int, NewsletterPendingPriorityChange>  $pendingRows
     * @return array{result?: NewsletterPriorityDeliveryResult, payload?: NewsletterPriorityMailPayload, subscription?: NewsletterSubscription}
     */
    private function prepareLocked(NewsletterSubscription $subscription, Collection $pendingRows): array
    {
        return DB::transaction(function () use ($subscription, $pendingRows): array {
            /** @var NewsletterSubscription|null $locked */
            $locked = NewsletterSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return [
                    'result' => new NewsletterPriorityDeliveryResult(
                        NewsletterPriorityDeliveryResult::SKIPPED_INELIGIBLE
                    ),
                ];
            }

            $locked->load(['user', 'organizers']);

            if (! $this->eligibility->subscriptionIsDeliveryEligible($locked)) {
                return [
                    'result' => new NewsletterPriorityDeliveryResult(
                        NewsletterPriorityDeliveryResult::SKIPPED_INELIGIBLE
                    ),
                ];
            }

            $stillDue = $pendingRows->filter(function (NewsletterPendingPriorityChange $pending) use ($locked): bool {
                $fresh = $pending->fresh();
                if ($fresh === null || ! $fresh->isPending()) {
                    return false;
                }

                return $this->recipients->matchingSubscriptions($fresh)
                    ->contains(fn (NewsletterSubscription $candidate) => (int) $candidate->id === (int) $locked->id);
            })->values();

            if ($stillDue->isEmpty()) {
                return [
                    'result' => new NewsletterPriorityDeliveryResult(
                        NewsletterPriorityDeliveryResult::SKIPPED_EMPTY
                    ),
                ];
            }

            $this->ensureUnsubscribeToken($locked);
            $unsubscribeUrl = route('newsletter.unsubscribe.public.show', [
                'token' => $locked->unsubscribe_token,
            ]);

            $payload = $this->composer->compose($locked, $stillDue, $unsubscribeUrl);
            if ($payload->items === []) {
                return [
                    'result' => new NewsletterPriorityDeliveryResult(
                        NewsletterPriorityDeliveryResult::SKIPPED_EMPTY
                    ),
                ];
            }

            return [
                'payload' => $payload,
                'subscription' => $locked->fresh(['user', 'organizers']) ?? $locked,
            ];
        });
    }

    /**
     * @return Collection<int, NewsletterPendingPriorityChange>
     */
    private function duePending(): Collection
    {
        $aggregationMinutes = max(0, (int) config('newsletter.priority_aggregation_minutes', 15));
        $cutoff = now()->subMinutes($aggregationMinutes);

        return NewsletterPendingPriorityChange::query()
            ->where('status', NewsletterPendingPriorityChange::STATUS_PENDING)
            ->where('detected_at', '<=', $cutoff)
            ->with(['event.organizer', 'occurrence.location'])
            ->orderBy('id')
            ->get();
    }

    private function markProcessedIfExhausted(NewsletterPendingPriorityChange $pending): bool
    {
        if (! $pending->isPending()) {
            return false;
        }

        if ($this->recipients->matchingSubscriptions($pending)->isNotEmpty()) {
            return false;
        }

        $pending->status = NewsletterPendingPriorityChange::STATUS_PROCESSED;
        $pending->save();

        return true;
    }

    private function ensureUnsubscribeToken(NewsletterSubscription $subscription): void
    {
        if (is_string($subscription->unsubscribe_token) && $subscription->unsubscribe_token !== '') {
            return;
        }

        do {
            $token = Str::random(64);
        } while (
            NewsletterSubscription::query()->where('unsubscribe_token', $token)->exists()
        );

        $subscription->unsubscribe_token = $token;
        $subscription->save();
    }
}
