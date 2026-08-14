<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NewsletterPublicUnsubscribeController extends Controller
{
    public const INVALID_TOKEN_MESSAGE = 'Link za odjavu nije važeći.';

    public function __construct(
        private readonly NewsletterSubscriptionManager $manager,
    ) {}

    public function show(string $token): View
    {
        $subscription = $this->activeByToken($token);

        return view('newsletter.unsubscribe-confirm', [
            'token' => $token,
            'valid' => $subscription !== null,
            'message' => $subscription === null ? self::INVALID_TOKEN_MESSAGE : null,
        ]);
    }

    public function unsubscribe(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'confirm_unsubscribe' => ['required', 'accepted'],
        ], [
            'confirm_unsubscribe.required' => 'Odjava zahtijeva potvrdu.',
            'confirm_unsubscribe.accepted' => 'Odjava zahtijeva potvrdu.',
        ]);

        $subscription = $this->activeByToken($token);
        if ($subscription === null) {
            return redirect()
                ->route('newsletter.unsubscribe.public.show', ['token' => $token])
                ->with('error', self::INVALID_TOKEN_MESSAGE);
        }

        try {
            $this->manager->unsubscribe($subscription);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'subscription_not_active') {
                return redirect()
                    ->route('newsletter.unsubscribe.public.show', ['token' => $token])
                    ->with('error', self::INVALID_TOKEN_MESSAGE);
            }

            throw $e;
        }

        return redirect()
            ->route('newsletter.unsubscribe.public.done');
    }

    public function done(): View
    {
        return view('newsletter.unsubscribe-done');
    }

    private function activeByToken(string $token): ?NewsletterSubscription
    {
        if ($token === '') {
            return null;
        }

        $subscription = NewsletterSubscription::query()
            ->where('unsubscribe_token', $token)
            ->first();

        if ($subscription === null || ! $subscription->isActive()) {
            return null;
        }

        return $subscription;
    }
}
