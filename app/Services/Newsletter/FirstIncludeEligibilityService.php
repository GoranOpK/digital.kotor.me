<?php

namespace App\Services\Newsletter;

use App\Models\CulturalEventEntry;
use App\Models\NewsletterSubscription;
use App\Models\NewsletterSubscriptionSourceCoverage;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicCardOccurrenceCriteria;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * NL-03 first_include eligibility / candidate foundation.
 * No mail. No ledger write. No queue.
 */
final class FirstIncludeEligibilityService
{
    public function __construct(
        private readonly NewsletterFirstIncludeDeliveryReader $deliveryReader,
    ) {}

    public function isEligible(CulturalEventEntry $event, NewsletterSubscription $subscription): bool
    {
        $event = $event->fresh(['organizer', 'occurrences']) ?? $event;
        $subscription = $subscription->fresh(['user', 'organizers']) ?? $subscription;

        if (! $this->eventIsFirstIncludeCandidate($event)) {
            return false;
        }

        if (! $this->subscriptionIsDeliveryEligible($subscription)) {
            return false;
        }

        if (! $this->matchesCurrentScope($event, $subscription)) {
            return false;
        }

        $coverageStart = $this->coverageStartFor($event, $subscription);
        if ($coverageStart === null) {
            return false;
        }

        if ($event->first_published_at->lt($coverageStart)) {
            return false;
        }

        $userId = (int) $subscription->user_id;
        if ($this->deliveryReader->hasSuccessfulFirstInclude($userId, (int) $event->id)) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, NewsletterSubscription>
     */
    public function eligibleSubscriptionsFor(CulturalEventEntry $event): Collection
    {
        return NewsletterSubscription::query()
            ->where('status', NewsletterSubscription::STATUS_ACTIVE)
            ->with(['user', 'organizers'])
            ->get()
            ->filter(fn (NewsletterSubscription $subscription) => $this->isEligible($event, $subscription))
            ->values();
    }

    public function eventIsFirstIncludeCandidate(CulturalEventEntry $event): bool
    {
        if (! $event->isPublished()) {
            return false;
        }

        if (! $event->isPubliclyVisible()) {
            return false;
        }

        if ($event->first_published_at === null) {
            return false;
        }

        return $this->hasFutureRelevantOccurrence($event);
    }

    public function subscriptionIsDeliveryEligible(NewsletterSubscription $subscription): bool
    {
        if (! $subscription->isActive()) {
            return false;
        }

        $user = $subscription->user;
        if (! $user instanceof User) {
            return false;
        }

        if ($user->activation_status !== 'active') {
            return false;
        }

        return $user->hasVerifiedEmail();
    }

    public function matchesCurrentScope(CulturalEventEntry $event, NewsletterSubscription $subscription): bool
    {
        if ($subscription->usesAllEventsScope()) {
            if ($event->organizer_id === null) {
                return true;
            }

            $organizer = $event->organizer;

            return $organizer !== null && $organizer->isActive();
        }

        if (! $subscription->usesSelectedOrganizerScope()) {
            return false;
        }

        if ($event->organizer_id === null) {
            return (bool) $subscription->include_without_organizer;
        }

        $organizer = $event->organizer;
        if ($organizer === null || ! $organizer->isActive()) {
            return false;
        }

        return $subscription->organizers->contains(
            fn ($followed) => (int) $followed->id === (int) $event->organizer_id
        );
    }

    public function coverageStartFor(
        CulturalEventEntry $event,
        NewsletterSubscription $subscription
    ): ?CarbonInterface {
        $periodStart = $subscription->subscribed_at;
        if ($periodStart === null || $event->first_published_at === null) {
            return null;
        }

        $starts = [];

        if ($event->organizer_id === null) {
            $without = $this->openCoverageSince(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_WITHOUT_ORGANIZER,
                null
            );
            if ($without !== null) {
                $starts[] = $without;
            }
        } else {
            $organizerSince = $this->openCoverageSince(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_ORGANIZER,
                (int) $event->organizer_id
            );
            if ($organizerSince !== null) {
                $starts[] = $organizerSince;
            }
        }

        $allEventsSince = $this->openCoverageSince(
            $subscription,
            NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS,
            null
        );
        if ($allEventsSince !== null) {
            $starts[] = $allEventsSince;
        }

        if ($starts === []) {
            return null;
        }

        $effective = collect($starts)
            ->map(function (CarbonInterface $since) use ($periodStart): CarbonInterface {
                return $since->lt($periodStart) ? $periodStart : $since;
            })
            ->sort()
            ->first();

        return $effective;
    }

    private function openCoverageSince(
        NewsletterSubscription $subscription,
        string $sourceType,
        ?int $organizerId
    ): ?CarbonInterface {
        $query = NewsletterSubscriptionSourceCoverage::query()
            ->where('newsletter_subscription_id', $subscription->id)
            ->where('source_type', $sourceType)
            ->whereNull('covered_until');

        if ($organizerId === null) {
            $query->whereNull('cultural_organizer_id');
        } else {
            $query->where('cultural_organizer_id', $organizerId);
        }

        $row = $query->first();

        return $row?->covered_since;
    }

    private function hasFutureRelevantOccurrence(CulturalEventEntry $event): bool
    {
        return CulturalPublicCardOccurrenceCriteria::constrain(
            $event->occurrences()->getQuery()
        )->exists();
    }
}
