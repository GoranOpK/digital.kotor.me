<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * NL-02 — transactional subscribe / preference / unsubscribe / reactivate.
 * Identity is always the authenticated User. No mail.
 */
final class NewsletterSubscriptionManager
{
    public const MESSAGE_SUBSCRIBED = 'Uspješno ste se pretplatili na Newsletter.';

    public const MESSAGE_UPDATED = 'Newsletter postavke su uspješno sačuvane.';

    public const MESSAGE_UNSUBSCRIBED = 'Uspješno ste se odjavili sa Newslettera.';

    public const MESSAGE_ALREADY_ACTIVE = 'Newsletter pretplata je već aktivna.';

    /**
     * @param  list<int>  $organizerIds
     */
    public function activate(User $user, string $scopeMode, array $organizerIds, bool $includeWithoutOrganizer): NewsletterSubscription
    {
        return DB::transaction(function () use ($user, $scopeMode, $organizerIds, $includeWithoutOrganizer) {
            /** @var NewsletterSubscription|null $subscription */
            $subscription = NewsletterSubscription::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($subscription !== null && $subscription->isActive()) {
                throw new RuntimeException('duplicate_active_subscription');
            }

            if ($subscription === null) {
                $subscription = new NewsletterSubscription();
                $subscription->user_id = $user->id;
            }

            $subscription->status = NewsletterSubscription::STATUS_ACTIVE;
            $subscription->subscribed_at = now();
            $subscription->unsubscribed_at = null;
            $subscription->unsubscribe_token = Str::random(64);

            $this->applySubmittedScope($subscription, $scopeMode, $organizerIds, $includeWithoutOrganizer);

            return $subscription->fresh(['organizers']);
        });
    }

    /**
     * @param  list<int>  $organizerIds
     */
    public function updatePreferences(NewsletterSubscription $subscription, string $scopeMode, array $organizerIds, bool $includeWithoutOrganizer): NewsletterSubscription
    {
        return DB::transaction(function () use ($subscription, $scopeMode, $organizerIds, $includeWithoutOrganizer) {
            /** @var NewsletterSubscription $locked */
            $locked = NewsletterSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isActive()) {
                throw new RuntimeException('subscription_not_active');
            }

            $this->applySubmittedScope($locked, $scopeMode, $organizerIds, $includeWithoutOrganizer);

            return $locked->fresh(['organizers']);
        });
    }

    public function unsubscribe(NewsletterSubscription $subscription): NewsletterSubscription
    {
        return DB::transaction(function () use ($subscription) {
            /** @var NewsletterSubscription $locked */
            $locked = NewsletterSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isActive()) {
                throw new RuntimeException('subscription_not_active');
            }

            $locked->applyUnsubscribeState();

            return $locked->fresh();
        });
    }

    /**
     * @param  list<int>  $organizerIds
     */
    private function applySubmittedScope(
        NewsletterSubscription $subscription,
        string $scopeMode,
        array $organizerIds,
        bool $includeWithoutOrganizer
    ): void {
        if ($scopeMode === NewsletterSubscription::SCOPE_ALL_EVENTS) {
            $subscription->applyAllEventsScope();

            return;
        }

        $subscription->applySelectedOrganizerScope($organizerIds, $includeWithoutOrganizer);
    }
}
