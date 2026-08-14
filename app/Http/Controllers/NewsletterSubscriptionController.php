<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscriptionPreferencesRequest;
use App\Http\Requests\NewsletterUnsubscribeRequest;
use App\Models\CulturalOrganizer;
use App\Models\NewsletterSubscription;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NewsletterSubscriptionController extends Controller
{
    public function __construct(
        private readonly NewsletterSubscriptionManager $manager
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user()->fresh();
        abort_unless(CulturalPortalAccess::isPlatformUserActive($user), 403);

        $subscription = NewsletterSubscription::query()->where('user_id', $user->id)->first();
        $selectedIds = $subscription?->organizers()->pluck('cultural_organizers.id')->map(fn ($id) => (int) $id)->all() ?? [];

        $selectableOrganizers = CulturalOrganizer::query()
            ->where('status', CulturalOrganizer::STATUS_ACTIVE)
            ->orderBy('naziv')
            ->get();

        $preservedInactiveOrganizers = CulturalOrganizer::query()
            ->where('status', CulturalOrganizer::STATUS_DEACTIVATED)
            ->whereIn('id', $selectedIds)
            ->orderBy('naziv')
            ->get();

        return view('newsletter.settings', [
            'subscription' => $subscription,
            'selectableOrganizers' => $selectableOrganizers,
            'preservedInactiveOrganizers' => $preservedInactiveOrganizers,
            'selectedIds' => $selectedIds,
        ]);
    }

    public function subscribe(NewsletterSubscriptionPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user()->fresh();
        $existing = NewsletterSubscription::query()->where('user_id', $user->id)->first();

        if ($existing !== null && $existing->isActive()) {
            return redirect()
                ->route('newsletter.settings')
                ->with('success', NewsletterSubscriptionManager::MESSAGE_ALREADY_ACTIVE);
        }

        try {
            $this->manager->activate(
                $user,
                $request->validated('scope_mode'),
                $request->organizerIds(),
                $request->includeWithoutOrganizer()
            );
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_active_subscription') {
                return redirect()
                    ->route('newsletter.settings')
                    ->with('success', NewsletterSubscriptionManager::MESSAGE_ALREADY_ACTIVE);
            }

            throw $e;
        }

        return redirect()
            ->route('newsletter.settings')
            ->with('success', NewsletterSubscriptionManager::MESSAGE_SUBSCRIBED);
    }

    public function update(NewsletterSubscriptionPreferencesRequest $request): RedirectResponse
    {
        $subscription = NewsletterSubscription::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($subscription === null || ! $subscription->isActive()) {
            return redirect()
                ->route('newsletter.settings')
                ->withErrors(['scope_mode' => 'Newsletter pretplata nije aktivna.']);
        }

        $this->manager->updatePreferences(
            $subscription,
            $request->validated('scope_mode'),
            $request->organizerIds(),
            $request->includeWithoutOrganizer()
        );

        return redirect()
            ->route('newsletter.settings')
            ->with('success', NewsletterSubscriptionManager::MESSAGE_UPDATED);
    }

    public function unsubscribe(NewsletterUnsubscribeRequest $request): RedirectResponse
    {
        $subscription = NewsletterSubscription::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($subscription === null || ! $subscription->isActive()) {
            return redirect()->route('newsletter.settings');
        }

        $this->manager->unsubscribe($subscription);

        return redirect()
            ->route('newsletter.settings')
            ->with('success', NewsletterSubscriptionManager::MESSAGE_UNSUBSCRIBED);
    }
}
