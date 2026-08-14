<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscription;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
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

    public function __construct(
        private readonly NewsletterSourceCoverageSync $coverageSync,
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    /**
     * @param  list<int>  $organizerIds
     */
    public function activate(User $user, string $scopeMode, array $organizerIds, bool $includeWithoutOrganizer): NewsletterSubscription
    {
        $kind = 'activate';
        $persistAt = now();
        $subscription = DB::transaction(function () use ($user, $scopeMode, $organizerIds, $includeWithoutOrganizer, &$kind, &$persistAt) {
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

            $isReactivation = $subscription !== null && $subscription->isUnsubscribed();

            $subscription->status = NewsletterSubscription::STATUS_ACTIVE;
            $subscription->subscribed_at = now();
            $subscription->unsubscribed_at = null;
            $subscription->unsubscribe_token = Str::random(64);

            if ($isReactivation) {
                $kind = 'reactivate';
                $this->coverageSync->closeAllOpen($subscription, $subscription->subscribed_at);
            }

            $this->applySubmittedScope($subscription, $scopeMode, $organizerIds, $includeWithoutOrganizer);
            $this->coverageSync->openInitial($subscription, $scopeMode, $organizerIds, $includeWithoutOrganizer);
            $persistAt = $subscription->subscribed_at?->copy() ?? now();

            return $subscription->fresh(['organizers', 'sourceCoverages']);
        });

        $catalogId = $kind === 'reactivate'
            ? CulturalActivityCatalog::NL_03
            : CulturalActivityCatalog::NL_01;
        $this->activityEmitter->emitUser(
            $catalogId,
            $kind === 'reactivate'
                ? CulturalActivityEventId::repeatable(
                    $catalogId,
                    (int) $subscription->id,
                    ['kind' => 'reactivate'],
                    $persistAt
                )
                : CulturalActivityEventId::once($catalogId, (int) $subscription->id),
            $user,
            (int) $subscription->id,
            $subscription->subscribed_at ?? now(),
            ['subscription_id' => (int) $subscription->id],
        );

        return $subscription;
    }

    /**
     * @param  list<int>  $organizerIds
     */
    public function updatePreferences(NewsletterSubscription $subscription, string $scopeMode, array $organizerIds, bool $includeWithoutOrganizer): NewsletterSubscription
    {
        $changed = true;
        $beforePrefs = $this->preferenceIdentity($subscription);
        $persistAt = now();
        $updated = DB::transaction(function () use ($subscription, $scopeMode, $organizerIds, $includeWithoutOrganizer, &$changed, &$persistAt) {
            /** @var NewsletterSubscription $locked */
            $locked = NewsletterSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isActive()) {
                throw new RuntimeException('subscription_not_active');
            }

            $locked->load('organizers');

            if ($this->coverageSync->sameEffectivePreferences(
                $locked,
                $scopeMode,
                $organizerIds,
                $includeWithoutOrganizer
            )) {
                $changed = false;
                $this->applySubmittedScope($locked, $scopeMode, $organizerIds, $includeWithoutOrganizer);

                return $locked->fresh(['organizers', 'sourceCoverages']);
            }

            $this->applySubmittedScope($locked, $scopeMode, $organizerIds, $includeWithoutOrganizer);
            $this->coverageSync->syncToMatchCurrentPreferences(
                $locked,
                $scopeMode,
                $organizerIds,
                $includeWithoutOrganizer
            );
            $persistAt = $locked->updated_at?->copy() ?? now();

            return $locked->fresh(['organizers', 'sourceCoverages']);
        });

        if ($changed) {
            $user = $updated->user;
            if ($user instanceof User) {
                $this->activityEmitter->emitUser(
                    CulturalActivityCatalog::NL_04,
                    CulturalActivityEventId::repeatable(
                        CulturalActivityCatalog::NL_04,
                        (int) $updated->id,
                        [
                            'from' => $beforePrefs,
                            'to' => $this->preferenceIdentity($updated, $scopeMode, $organizerIds, $includeWithoutOrganizer),
                        ],
                        $persistAt
                    ),
                    $user,
                    (int) $updated->id,
                    $updated->updated_at ?? now(),
                    ['subscription_id' => (int) $updated->id],
                );
            }
        }

        return $updated;
    }

    public function unsubscribe(NewsletterSubscription $subscription): NewsletterSubscription
    {
        $persistAt = now();
        $unsubscribed = DB::transaction(function () use ($subscription, &$persistAt) {
            /** @var NewsletterSubscription $locked */
            $locked = NewsletterSubscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isActive()) {
                throw new RuntimeException('subscription_not_active');
            }

            $locked->applyUnsubscribeState();
            $this->coverageSync->closeAllOpen($locked, $locked->unsubscribed_at ?? now());
            $persistAt = $locked->unsubscribed_at?->copy() ?? now();

            return $locked->fresh(['organizers', 'sourceCoverages']);
        });

        $user = $unsubscribed->user;
        if ($user instanceof User) {
            $this->activityEmitter->emitUser(
                CulturalActivityCatalog::NL_02,
                CulturalActivityEventId::repeatable(
                    CulturalActivityCatalog::NL_02,
                    (int) $unsubscribed->id,
                    ['kind' => 'unsubscribe'],
                    $persistAt
                ),
                $user,
                (int) $unsubscribed->id,
                $unsubscribed->unsubscribed_at ?? now(),
                ['subscription_id' => (int) $unsubscribed->id],
            );
        }

        return $unsubscribed;
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

    /**
     * @param  list<int>  $organizerIds
     * @return array<string, scalar|list<int>>
     */
    private function preferenceIdentity(
        NewsletterSubscription $subscription,
        ?string $scopeMode = null,
        ?array $organizerIds = null,
        ?bool $includeWithoutOrganizer = null,
    ): array {
        $scope = $scopeMode ?? (string) $subscription->scope_mode;
        $without = $includeWithoutOrganizer ?? (bool) $subscription->include_without_organizer;
        if ($organizerIds === null) {
            $organizerIds = $subscription->organizers()
                ->pluck('cultural_organizers.id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
        } else {
            $organizerIds = collect($organizerIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        }

        return [
            'scope' => $scope,
            'without_organizer' => $without ? 1 : 0,
            'organizer_ids' => $scope === NewsletterSubscription::SCOPE_ALL_EVENTS ? [] : $organizerIds,
        ];
    }
}
