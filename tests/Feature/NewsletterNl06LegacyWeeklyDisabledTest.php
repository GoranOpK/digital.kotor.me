<?php

namespace Tests\Feature;

use App\Mail\CulturalCalendarNewsletterWeeklyMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterNl06LegacyWeeklyDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_weekly_command_does_not_send_mail(): void
    {
        Mail::fake();

        NewsletterSubscriber::query()->create([
            'email' => 'legacy-nl06@example.com',
            'is_subscribed' => true,
            'subscribed_at' => now(),
            'unsubscribe_token' => 'legacy-token-nl06',
        ]);

        $this->artisan('cultural-calendar:send-weekly-newsletter')
            ->expectsOutputToContain('isključen')
            ->assertSuccessful();

        $this->artisan('cultural-calendar:send-weekly-newsletter', ['--dry-run' => true])
            ->assertSuccessful();

        Mail::assertNotSent(CulturalCalendarNewsletterWeeklyMail::class);
        Mail::assertNothingSent();
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'legacy-nl06@example.com',
            'is_subscribed' => true,
        ]);
    }
}
