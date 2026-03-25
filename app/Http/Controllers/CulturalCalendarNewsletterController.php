<?php

namespace App\Http\Controllers;

use App\Mail\CulturalCalendarNewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CulturalCalendarNewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'unsubscribe' => ['nullable', 'boolean'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $unsubscribe = (bool) ($validated['unsubscribe'] ?? false);

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);

        if ($unsubscribe) {
            if ($subscriber->exists && $subscriber->is_subscribed) {
                $subscriber->forceFill([
                    'is_subscribed' => false,
                    'unsubscribed_at' => now(),
                ])->save();

                return redirect()
                    ->route('cultural-calendar.index')
                    ->with('newsletter_status', 'Vaša e-mail adresa je uspješno odjavljena sa newslettera.');
            }

            return redirect()
                ->route('cultural-calendar.index')
                ->with('newsletter_status', 'Ova e-mail adresa nije aktivno prijavljena na newsletter.');
        }

        $wasSubscribedBefore = $subscriber->exists && $subscriber->is_subscribed;

        $subscriber->forceFill([
            'is_subscribed' => true,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
            'unsubscribe_token' => $subscriber->unsubscribe_token ?: Str::random(64),
        ])->save();

        // Welcome poruku šaljemo nakon uspješne prijave/ponovne aktivacije.
        Mail::to($subscriber->email)->send(new CulturalCalendarNewsletterWelcomeMail($subscriber));

        $statusMessage = $wasSubscribedBefore
            ? 'Vaša e-mail adresa je već prijavljena. Poslali smo vam potvrdu newsletter prijave.'
            : 'Uspješno ste prijavljeni na newsletter Kalendara kulture.';

        return redirect()
            ->route('cultural-calendar.index')
            ->with('newsletter_status', $statusMessage);
    }
}

