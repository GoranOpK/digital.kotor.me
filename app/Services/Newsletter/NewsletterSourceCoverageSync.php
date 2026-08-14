<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscription;
use App\Models\NewsletterSubscriptionSourceCoverage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * NL-03 — maintain source coverage intervals. Not preference SSOT.
 */
final class NewsletterSourceCoverageSync
{
    /**
     * @param  list<int>  $organizerIds
     */
    public function sameEffectivePreferences(
        NewsletterSubscription $subscription,
        string $scopeMode,
        array $organizerIds,
        bool $includeWithoutOrganizer
    ): bool {
        if ($subscription->scope_mode !== $scopeMode) {
            return false;
        }

        if ($scopeMode === NewsletterSubscription::SCOPE_ALL_EVENTS) {
            return true;
        }

        $currentIds = $subscription->organizers()
            ->pluck('cultural_organizers.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
        $submittedIds = collect($organizerIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $currentIds === $submittedIds
            && (bool) $subscription->include_without_organizer === $includeWithoutOrganizer;
    }

    public function closeAllOpen(NewsletterSubscription $subscription, CarbonInterface $at): void
    {
        NewsletterSubscriptionSourceCoverage::query()
            ->where('newsletter_subscription_id', $subscription->id)
            ->whereNull('covered_until')
            ->update(['covered_until' => $at]);
    }

    /**
     * @param  list<int>  $organizerIds
     */
    public function openInitial(
        NewsletterSubscription $subscription,
        string $scopeMode,
        array $organizerIds,
        bool $includeWithoutOrganizer
    ): void {
        $at = $subscription->subscribed_at ?? Carbon::now();

        if ($scopeMode === NewsletterSubscription::SCOPE_ALL_EVENTS) {
            $this->ensureOpen(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS,
                null,
                $at
            );

            return;
        }

        foreach (array_unique($organizerIds) as $organizerId) {
            $this->ensureOpen(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_ORGANIZER,
                (int) $organizerId,
                $at
            );
        }

        if ($includeWithoutOrganizer) {
            $this->ensureOpen(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_WITHOUT_ORGANIZER,
                null,
                $at
            );
        }
    }

    /**
     * @param  list<int>  $organizerIds
     */
    public function syncToMatchCurrentPreferences(
        NewsletterSubscription $subscription,
        string $scopeMode,
        array $organizerIds,
        bool $includeWithoutOrganizer
    ): void {
        $at = Carbon::now();
        $openAll = $this->openRow(
            $subscription,
            NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS,
            null
        );
        $inheritSince = $openAll?->covered_since;

        if ($scopeMode === NewsletterSubscription::SCOPE_ALL_EVENTS) {
            $this->ensureOpen(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS,
                null,
                $at
            );

            return;
        }

        if ($openAll !== null) {
            $openAll->covered_until = $at;
            $openAll->save();
        }

        $selectedIds = array_values(array_unique(array_map('intval', $organizerIds)));
        $openOrganizerRows = NewsletterSubscriptionSourceCoverage::query()
            ->where('newsletter_subscription_id', $subscription->id)
            ->where('source_type', NewsletterSubscriptionSourceCoverage::SOURCE_ORGANIZER)
            ->whereNull('covered_until')
            ->get();

        foreach ($openOrganizerRows as $row) {
            if (! in_array((int) $row->cultural_organizer_id, $selectedIds, true)) {
                $row->covered_until = $at;
                $row->save();
            }
        }

        foreach ($selectedIds as $organizerId) {
            $this->ensureOpen(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_ORGANIZER,
                $organizerId,
                $inheritSince ?? $at
            );
        }

        $openWithout = $this->openRow(
            $subscription,
            NewsletterSubscriptionSourceCoverage::SOURCE_WITHOUT_ORGANIZER,
            null
        );

        if ($includeWithoutOrganizer) {
            $this->ensureOpen(
                $subscription,
                NewsletterSubscriptionSourceCoverage::SOURCE_WITHOUT_ORGANIZER,
                null,
                $inheritSince ?? $at
            );
        } elseif ($openWithout !== null) {
            $openWithout->covered_until = $at;
            $openWithout->save();
        }
    }

    private function ensureOpen(
        NewsletterSubscription $subscription,
        string $sourceType,
        ?int $organizerId,
        CarbonInterface $since
    ): void {
        if ($this->openRow($subscription, $sourceType, $organizerId) !== null) {
            return;
        }

        NewsletterSubscriptionSourceCoverage::query()->create([
            'newsletter_subscription_id' => $subscription->id,
            'source_type' => $sourceType,
            'cultural_organizer_id' => $organizerId,
            'covered_since' => $since,
            'covered_until' => null,
        ]);
    }

    private function openRow(
        NewsletterSubscription $subscription,
        string $sourceType,
        ?int $organizerId
    ): ?NewsletterSubscriptionSourceCoverage {
        $query = NewsletterSubscriptionSourceCoverage::query()
            ->where('newsletter_subscription_id', $subscription->id)
            ->where('source_type', $sourceType)
            ->whereNull('covered_until');

        if ($organizerId === null) {
            $query->whereNull('cultural_organizer_id');
        } else {
            $query->where('cultural_organizer_id', $organizerId);
        }

        return $query->first();
    }
}
