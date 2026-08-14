<?php

namespace App\Services\Newsletter;

use App\Models\CulturalEventEntry;
use App\Models\NewsletterDeliveryLedger;
use App\Models\NewsletterSubscription;

/**
 * Read-only adapter for successful first_include delivery evidence.
 */
final class NewsletterFirstIncludeDeliveryReader
{
    public function hasSuccessfulFirstInclude(
        NewsletterSubscription $subscription,
        CulturalEventEntry $event
    ): bool {
        return NewsletterDeliveryLedger::query()
            ->where('newsletter_subscription_id', $subscription->id)
            ->where('cultural_event_entry_id', $event->id)
            ->where('entry_type', NewsletterDeliveryLedger::TYPE_FIRST_INCLUDE)
            ->exists();
    }
}
