<?php

namespace Tests\Feature;

use App\Console\Commands\SendCulturalCalendarNewsletter;
use App\Mail\CulturalCalendarFirstIncludeNewsletterMail;
use App\Mail\CulturalCalendarNewsletterWeeklyMail;
use App\Mail\CulturalCalendarNewsletterWelcomeMail;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\NewsletterDeliveryLedger;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\Newsletter\FirstIncludeEligibilityService;
use App\Services\Newsletter\NewsletterFirstIncludeDeliveryReader;
use App\Services\Newsletter\NewsletterFirstIncludeDeliveryService;
use App\Services\Newsletter\NewsletterOutboundMailer;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NewsletterNl04FirstIncludeDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $subscriber;

    private CulturalCategory $category;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private NewsletterSubscriptionManager $subscriptions;

    private FirstIncludeEligibilityService $eligibility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Mail::fake();

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->subscriber = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::query()->create([
            'naziv' => 'NL-04 Kategorija',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->subscriptions = app(NewsletterSubscriptionManager::class);
        $this->eligibility = app(FirstIncludeEligibilityService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_one_eligible_event_sends_one_mail_and_one_ledger_row(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $entry = $this->publishWithoutOrganizer('Jedan dogadjaj');
        $before = $this->eventSnapshot($entry);

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, 1);
        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, function ($mail) use ($entry): bool {
            return $mail->hasTo($this->subscriber->email)
                && str_contains($mail->render(), 'Jedan dogadjaj')
                && str_contains($mail->render(), route('cultural-calendar.show', $entry->id))
                && str_contains($mail->render(), 'odjavite se')
                && ! str_contains($mail->render(), 'narednu sedmicu')
                && ! str_contains($mail->render(), 'Manifestacija');
        });
        Mail::assertNotSent(CulturalCalendarNewsletterWeeklyMail::class);
        Mail::assertNotSent(CulturalCalendarNewsletterWelcomeMail::class);

        $this->assertSame(1, NewsletterDeliveryLedger::query()->count());
        $row = NewsletterDeliveryLedger::query()->first();
        $this->assertSame(NewsletterDeliveryLedger::TYPE_FIRST_INCLUDE, $row->entry_type);
        $this->assertNull($row->cultural_occurrence_id);
        $this->assertNull($row->change_control_key);
        $this->assertNotNull($row->sent_at);
        $this->assertSame($entry->id, $row->cultural_event_entry_id);

        $this->assertEventUnchanged($entry, $before);
    }

    public function test_multiple_events_one_mail_same_cycle_id(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $a = $this->publishWithoutOrganizer('Dogadjaj A');
        $b = $this->publishWithoutOrganizer('Dogadjaj B');
        $c = $this->publishWithoutOrganizer('Dogadjaj C');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, 1);
        $this->assertSame(3, NewsletterDeliveryLedger::query()->count());
        $cycleIds = NewsletterDeliveryLedger::query()->pluck('delivery_cycle_id')->unique()->all();
        $this->assertCount(1, $cycleIds);
        $this->assertEqualsCanonicalizing(
            [$a->id, $b->id, $c->id],
            NewsletterDeliveryLedger::query()->pluck('cultural_event_entry_id')->all()
        );
        $sentAts = NewsletterDeliveryLedger::query()->pluck('sent_at')->unique()->count();
        $this->assertSame(1, $sentAts);
    }

    public function test_zero_eligible_sends_nothing(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->count());
    }

    public function test_inactive_user_sends_nothing(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Neaktivni');
        $this->subscriber->forceFill(['activation_status' => 'deactivated'])->save();

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->count());
    }

    public function test_unverified_user_sends_nothing(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Neverifikovan');
        $this->subscriber->forceFill(['email_verified_at' => null])->save();

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->count());
    }

    public function test_unsubscribed_sends_nothing(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Odjavljen');
        $this->subscriptions->unsubscribe($subscription);

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->count());
    }

    public function test_nonmatching_preference_is_omitted(): void
    {
        $orgA = $this->makeOrganizer('Org A');
        $orgB = $this->makeOrganizer('Org B');
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );
        $this->publishOrganizerEvent('Samo B', $orgB);

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->count());
    }

    public function test_already_delivered_event_is_not_resent(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Vec dostavljen');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, 1);

        Mail::fake();
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(1, NewsletterDeliveryLedger::query()->count());
    }

    public function test_failed_send_writes_no_ledger_and_later_cycle_sends(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Posle greske');

        $this->app->instance(NewsletterOutboundMailer::class, new class extends NewsletterOutboundMailer
        {
            public function send(string $recipientEmail, $mailable): void
            {
                throw new \RuntimeException('smtp-fail');
            }
        });

        $this->artisan('cultural-calendar:send-newsletter')->assertFailed();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->count());

        $this->app->forgetInstance(NewsletterOutboundMailer::class);
        $this->app->forgetInstance(NewsletterFirstIncludeDeliveryService::class);
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, 1);
        $this->assertSame(1, NewsletterDeliveryLedger::query()->count());
    }

    public function test_ledger_unique_protection(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $entry = $this->publishWithoutOrganizer('Unikat');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        $this->expectException(\Illuminate\Database\QueryException::class);
        NewsletterDeliveryLedger::query()->create([
            'newsletter_subscription_id' => $subscription->id,
            'cultural_event_entry_id' => $entry->id,
            'cultural_occurrence_id' => null,
            'entry_type' => NewsletterDeliveryLedger::TYPE_FIRST_INCLUDE,
            'change_control_key' => null,
            'delivery_cycle_id' => '00000000-0000-0000-0000-000000000099',
            'payload_snapshot' => null,
            'sent_at' => now(),
        ]);
    }

    public function test_current_user_email_used_and_legacy_ignored(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Adresa');
        NewsletterSubscriber::query()->create([
            'email' => 'legacy-nl04@example.com',
            'is_subscribed' => true,
            'subscribed_at' => now(),
            'unsubscribe_token' => 'legacy-token-nl04',
        ]);
        $legacyCount = NewsletterSubscriber::query()->count();

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, function ($mail): bool {
            return $mail->hasTo($this->subscriber->email)
                && ! $mail->hasTo('legacy-nl04@example.com');
        });
        $this->assertSame($legacyCount, NewsletterSubscriber::query()->count());
        $this->assertTrue(Schema::hasTable('newsletter_subscribers'));
    }

    public function test_public_unsubscribe_flow(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Odjava token');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        $token = $subscription->fresh()->unsubscribe_token;
        $url = route('newsletter.unsubscribe.public.show', ['token' => $token]);

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, function ($mail) use ($url): bool {
            return str_contains($mail->render(), $url);
        });

        $this->get($url)->assertOk()->assertSee('Potvrđujem odjavu', false);
        $this->assertTrue($subscription->fresh()->isActive());

        $this->post(route('newsletter.unsubscribe.public.consume', ['token' => $token]), [
            'confirm_unsubscribe' => '1',
        ])->assertRedirect(route('newsletter.unsubscribe.public.done'));

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->isUnsubscribed());
        $this->assertNull($fresh->unsubscribe_token);
        $this->assertNull($fresh->scope_mode);
        $this->assertSame(0, $fresh->organizers()->count());

        $this->get($url)->assertOk()->assertSee('Link za odjavu nije važeći', false);
        $this->post(route('newsletter.unsubscribe.public.consume', ['token' => $token]), [
            'confirm_unsubscribe' => '1',
        ])->assertRedirect();
        $this->assertTrue($fresh->fresh()->isUnsubscribed());
    }

    public function test_invalid_token_is_safe(): void
    {
        $this->get(route('newsletter.unsubscribe.public.show', ['token' => 'x'.str_repeat('a', 63)]))
            ->assertOk()
            ->assertSee('Link za odjavu nije važeći', false);

        $this->post(route('newsletter.unsubscribe.public.consume', ['token' => 'x'.str_repeat('a', 63)]), [
            'confirm_unsubscribe' => '1',
        ])->assertRedirect();

        $this->assertSame(0, NewsletterSubscription::query()->where('status', NewsletterSubscription::STATUS_UNSUBSCRIBED)->count());
    }

    public function test_old_token_invalid_after_reactivation(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $oldToken = $subscription->unsubscribe_token;
        $this->subscriptions->unsubscribe($subscription->fresh());
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $this->get(route('newsletter.unsubscribe.public.show', ['token' => $oldToken]))
            ->assertOk()
            ->assertSee('Link za odjavu nije važeći', false);
        $this->assertTrue($subscription->fresh()->isActive());
        $this->assertNotSame($oldToken, $subscription->fresh()->unsubscribe_token);
    }

    public function test_grouping_by_organizer_and_without_organizer(): void
    {
        $orgA = $this->makeOrganizer('Alpha Org');
        $orgB = $this->makeOrganizer('Beta Org');
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishOrganizerEvent('Alpha dogadjaj', $orgA);
        $this->publishOrganizerEvent('Beta dogadjaj', $orgB);
        $this->publishWithoutOrganizer('Slobodan dogadjaj');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, function ($mail): bool {
            $html = $mail->render();

            return str_contains($html, 'Alpha Org')
                && str_contains($html, 'Beta Org')
                && str_contains($html, 'Bez organizatora')
                && str_contains($html, 'Alpha dogadjaj')
                && str_contains($html, 'Slobodan dogadjaj');
        });
        $this->assertSame(3, NewsletterDeliveryLedger::query()->count());
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', NewsletterDeliveryLedger::TYPE_PRIORITY_CHANGE)->count());
    }

    public function test_multiple_future_planned_terms_are_listed(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $draft = $this->makeDraftWithoutOrganizer('Vise termina');
        $this->occurrenceWriter->create($draft, [
            'datum' => now()->addDays(20)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->eventLifecycle->publishDirectly($draft->fresh(), $this->editor);

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, function ($mail): bool {
            return str_contains($mail->render(), 'Budući termini');
        });
    }

    public function test_reader_and_eligibility_exclude_delivered_event(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $entry = $this->publishWithoutOrganizer('Reader');
        $this->assertTrue($this->eligibility->isEligible($entry, $subscription->fresh()));

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        $reader = app(NewsletterFirstIncludeDeliveryReader::class);
        $this->assertTrue($reader->hasSuccessfulFirstInclude($subscription->fresh(), $entry->fresh()));
        $this->assertFalse($this->eligibility->isEligible($entry->fresh(), $subscription->fresh()));
    }

    public function test_overlap_guard_skips_second_cycle(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Overlap');

        $lock = Cache::lock(SendCulturalCalendarNewsletter::LOCK_KEY, 1800);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
            Mail::assertNothingSent();
            $this->assertSame(0, NewsletterDeliveryLedger::query()->count());
        } finally {
            $lock->release();
        }
    }

    public function test_missing_token_is_generated_before_send(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $subscription->forceFill(['unsubscribe_token' => null])->saveQuietly();
        $this->publishWithoutOrganizer('Token popravka');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        $fresh = $subscription->fresh();
        $this->assertNotNull($fresh->unsubscribe_token);
        $this->assertSame(NewsletterSubscription::SCOPE_ALL_EVENTS, $fresh->scope_mode);
        Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, function ($mail) use ($fresh): bool {
            return str_contains(
                $mail->render(),
                route('newsletter.unsubscribe.public.show', ['token' => $fresh->unsubscribe_token])
            );
        });
    }

    public function test_command_does_not_invoke_weekly_and_does_not_write_priority(): void
    {
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Bez weekly');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNotSent(CulturalCalendarNewsletterWeeklyMail::class);
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $this->assertFalse(Schema::hasTable('newsletter_pending_changes'));
    }

    public function test_sent_at_uses_test_now(): void
    {
        Carbon::setTestNow('2026-08-14 15:00:00');
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->publishWithoutOrganizer('Vrijeme');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        $this->assertSame(
            '2026-08-14 15:00:00',
            NewsletterDeliveryLedger::query()->first()->sent_at->format('Y-m-d H:i:s')
        );
    }

    private function eventSnapshot(CulturalEventEntry $entry): array
    {
        $entry = $entry->fresh(['occurrences']);

        return [
            'naslov' => $entry->naslov,
            'status' => $entry->status,
            'first_published_at' => optional($entry->first_published_at)?->format('Y-m-d H:i:s'),
            'occurrences' => $entry->occurrences->map(fn (CulturalOccurrence $occ) => [
                'id' => $occ->id,
                'status' => $occ->status,
                'datum' => $occ->datum?->format('Y-m-d') ?? (string) $occ->datum,
            ])->all(),
        ];
    }

    private function assertEventUnchanged(CulturalEventEntry $entry, array $before): void
    {
        $this->assertSame($before, $this->eventSnapshot($entry));
    }

    private function publishWithoutOrganizer(string $naslov): CulturalEventEntry
    {
        return $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer($naslov),
            $this->editor
        );
    }

    private function publishOrganizerEvent(string $naslov, CulturalOrganizer $organizer): CulturalEventEntry
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $submitted = $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);

        return $this->eventLifecycle->approve($submitted, $this->editor);
    }

    private function makeDraftWithoutOrganizer(string $naslov): CulturalEventEntry
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::query()->create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
