<?php

namespace App\Services\Newsletter;

use App\Models\CulturalEventEntry;
use App\Models\NewsletterPendingPriorityChange;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Collection;

/**
 * Audience for priority_change: first_include ∩ active ∩ current organizer scope.
 * Does not use first_published_at as a priority gate.
 */
final class NewsletterPriorityRecipientResolver
{
    public function __construct(
        private readonly NewsletterFirstIncludeDeliveryReader $firstIncludeReader,
        private readonly NewsletterFirstIncludeLedgerWriter $ledgerWriter,
        private readonly FirstIncludeEligibilityService $eligibility,
    ) {}

    /**
     * @return Collection<int, NewsletterSubscription>
     */
    public function matchingSubscriptions(NewsletterPendingPriorityChange $pending): Collection
    {
        $event = $pending->event;
        if (! $event instanceof CulturalEventEntry) {
            $event = CulturalEventEntry::query()->find($pending->cultural_event_entry_id);
        }

        if (! $event instanceof CulturalEventEntry) {
            return collect();
        }

        $event->loadMissing(['organizer']);

        return NewsletterSubscription::query()
            ->where('status', NewsletterSubscription::STATUS_ACTIVE)
            ->with(['user', 'organizers'])
            ->orderBy('id')
            ->get()
            ->filter(function (NewsletterSubscription $subscription) use ($event, $pending): bool {
                if (! $this->firstIncludeReader->hasSuccessfulFirstInclude($subscription, $event)) {
                    return false;
                }

                if ($this->ledgerWriter->hasSuccessfulPriorityChange($subscription, $pending->change_control_key)) {
                    return false;
                }

                return $this->eligibility->matchesCurrentScope($event, $subscription);
            })
            ->values();
    }
}
