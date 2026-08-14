<?php

namespace App\Services\Newsletter;

use App\Mail\CulturalCalendarFirstIncludeNewsletterMail;
use App\Models\NewsletterSubscription;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NewsletterFirstIncludeDeliveryService
{
    public function __construct(
        private readonly FirstIncludeEligibilityService $eligibility,
        private readonly NewsletterFirstIncludeComposer $composer,
        private readonly NewsletterFirstIncludeLedgerWriter $ledgerWriter,
        private readonly NewsletterOutboundMailer $mailer,
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public function deliverForSubscription(NewsletterSubscription $subscription): NewsletterFirstIncludeDeliveryResult
    {
        $prepared = $this->prepareLocked($subscription);
        if (($prepared['result'] ?? null) instanceof NewsletterFirstIncludeDeliveryResult) {
            return $prepared['result'];
        }

        /** @var NewsletterFirstIncludeMailPayload $payload */
        $payload = $prepared['payload'];
        $lockedSubscription = $prepared['subscription'];

        try {
            $this->mailer->send(
                $payload->recipientEmail,
                new CulturalCalendarFirstIncludeNewsletterMail($payload)
            );
        } catch (\Throwable $e) {
            return new NewsletterFirstIncludeDeliveryResult(
                NewsletterFirstIncludeDeliveryResult::FAILED,
                0,
                $e->getMessage()
            );
        }

        $sentAt = now();
        $cycleId = $this->ledgerWriter->newDeliveryCycleId();

        try {
            $this->ledgerWriter->recordSuccessfulFirstInclude(
                $lockedSubscription,
                $payload->eventIds,
                $cycleId,
                $sentAt,
                $payload->snapshotEvents
            );
        } catch (\Throwable $e) {
            return new NewsletterFirstIncludeDeliveryResult(
                NewsletterFirstIncludeDeliveryResult::FAILED,
                0,
                'ledger_write_failed: '.$e->getMessage()
            );
        }

        $this->activityEmitter->emitSystem(
            CulturalActivityCatalog::NL_05,
            CulturalActivityEventId::once(CulturalActivityCatalog::NL_05, $cycleId),
            null,
            $sentAt,
            ['cycle_id' => $cycleId],
        );

        return new NewsletterFirstIncludeDeliveryResult(
            NewsletterFirstIncludeDeliveryResult::SENT,
            count($payload->eventIds)
        );
    }

    /**
     * @return array{result?: NewsletterFirstIncludeDeliveryResult, payload?: NewsletterFirstIncludeMailPayload, subscription?: NewsletterSubscription}
     */
    private function prepareLocked(NewsletterSubscription $subscription): array
    {
        return DB::transaction(function () use ($subscription): array {
            /** @var NewsletterSubscription|null $locked */
            $locked = NewsletterSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return [
                    'result' => new NewsletterFirstIncludeDeliveryResult(
                        NewsletterFirstIncludeDeliveryResult::SKIPPED_INELIGIBLE
                    ),
                ];
            }

            $locked->load(['user', 'organizers']);

            if (! $this->eligibility->subscriptionIsDeliveryEligible($locked)) {
                return [
                    'result' => new NewsletterFirstIncludeDeliveryResult(
                        NewsletterFirstIncludeDeliveryResult::SKIPPED_INELIGIBLE
                    ),
                ];
            }

            $candidates = $this->eligibility->eligibleEventsFor($locked);
            if ($candidates->isEmpty()) {
                return [
                    'result' => new NewsletterFirstIncludeDeliveryResult(
                        NewsletterFirstIncludeDeliveryResult::SKIPPED_EMPTY
                    ),
                ];
            }

            $this->ensureUnsubscribeToken($locked);
            $unsubscribeUrl = route('newsletter.unsubscribe.public.show', [
                'token' => $locked->unsubscribe_token,
            ]);

            $payload = $this->composer->compose($locked, $candidates, $unsubscribeUrl);
            if ($payload->eventIds === []) {
                return [
                    'result' => new NewsletterFirstIncludeDeliveryResult(
                        NewsletterFirstIncludeDeliveryResult::SKIPPED_EMPTY
                    ),
                ];
            }

            return [
                'payload' => $payload,
                'subscription' => $locked->fresh(['user', 'organizers']) ?? $locked,
            ];
        });
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
