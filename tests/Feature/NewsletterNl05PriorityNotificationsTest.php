<?php

namespace Tests\Feature;

use App\Console\Commands\SendCulturalCalendarPriorityNewsletter;
use App\Exceptions\CulturalEventDomainException;
use App\Mail\CulturalCalendarFirstIncludeNewsletterMail;
use App\Mail\CulturalCalendarPriorityNewsletterMail;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\NewsletterDeliveryLedger;
use App\Models\NewsletterPendingPriorityChange;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\Newsletter\NewsletterOutboundMailer;
use App\Services\Newsletter\NewsletterPriorityDeliveryService;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterNl05PriorityNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $subscriber;

    private CulturalCategory $category;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    private NewsletterSubscriptionManager $subscriptions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Mail::fake();
        config(['newsletter.priority_aggregation_minutes' => 0]);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->subscriber = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::query()->create([
            'naziv' => 'NL-05 Kategorija',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->subscriptions = app(NewsletterSubscriptionManager::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_event_cancellation_registers_pending_change(): void
    {
        $entry = $this->publishWithoutOrganizer('Otkaz');
        $this->eventLifecycle->cancel($entry, $this->editor, 'Interni razlog');

        $pending = NewsletterPendingPriorityChange::query()->sole();
        $this->assertSame(NewsletterPendingPriorityChange::KIND_EVENT_CANCELLED, $pending->change_kind);
        $this->assertSame($entry->id, $pending->cultural_event_entry_id);
        $this->assertNull($pending->cultural_occurrence_id);
        $this->assertSame(NewsletterPendingPriorityChange::STATUS_PENDING, $pending->status);
        $this->assertStringStartsWith('event_cancelled:'.$entry->id, $pending->change_control_key);
    }

    public function test_occurrence_cancellation_registers_term_scoped_change(): void
    {
        $draft = $this->makeDraftWithoutOrganizer('Dva termina');
        $second = $this->occurrenceWriter->create($draft->fresh(), [
            'datum' => now()->addDays(12)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $entry = $this->eventLifecycle->publishDirectly($draft->fresh(), $this->editor);
        $this->occurrenceLifecycle->cancel($second->fresh(), 'OCC razlog');

        $pending = NewsletterPendingPriorityChange::query()->sole();
        $this->assertSame(NewsletterPendingPriorityChange::KIND_OCCURRENCE_CANCELLED, $pending->change_kind);
        $this->assertSame($second->id, $pending->cultural_occurrence_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_postponement_registers_pending_change(): void
    {
        $entry = $this->publishWithoutOrganizer('Odlaganje');
        $occ = $entry->occurrences()->first();
        $this->occurrenceLifecycle->postpone($occ, 'Kasni');

        $pending = NewsletterPendingPriorityChange::query()->sole();
        $this->assertSame(NewsletterPendingPriorityChange::KIND_POSTPONED, $pending->change_kind);
        $this->assertSame($occ->id, $pending->cultural_occurrence_id);
    }

    public function test_new_term_after_postponement_registers_datetime_change(): void
    {
        $entry = $this->publishWithoutOrganizer('Novi termin');
        $occ = $entry->occurrences()->first();
        $this->occurrenceLifecycle->postpone($occ);
        $this->occurrenceLifecycle->resumeWithNewTermin($occ->fresh(), [
            'datum' => now()->addDays(30)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->assertSame(1, NewsletterPendingPriorityChange::query()->where('status', 'pending')->count());
        $this->assertSame(1, NewsletterPendingPriorityChange::query()->where('status', 'superseded')->count());
        $pending = NewsletterPendingPriorityChange::query()->where('status', 'pending')->sole();
        $this->assertSame(NewsletterPendingPriorityChange::KIND_DATETIME_CHANGED, $pending->change_kind);
    }

    public function test_ordinary_content_edit_does_not_register_priority_change(): void
    {
        $entry = $this->publishWithoutOrganizer('Opis');
        $this->eventWriter->updateContent($entry->fresh(), $this->editor, [
            'naslov' => 'Novi naslov',
            'category_id' => $this->category->id,
        ]);

        $this->assertSame(0, NewsletterPendingPriorityChange::query()->count());
    }

    public function test_repeated_postpone_does_not_duplicate_change_key(): void
    {
        $entry = $this->publishWithoutOrganizer('Jednom');
        $occ = $entry->occurrences()->first();
        $this->occurrenceLifecycle->postpone($occ);
        $this->assertSame(1, NewsletterPendingPriorityChange::query()->count());
        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceLifecycle->postpone($occ->fresh());
    }

    public function test_matching_subscriber_receives_priority_mail_after_first_include(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Vec dostavljen');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $before = $this->eventSnapshot($entry);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, 1);
        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail) use ($entry): bool {
            $html = $mail->render();

            return $mail->hasTo($this->subscriber->email)
                && str_contains($html, 'Vec dostavljen')
                && str_contains($html, 'Događaj je otkazan')
                && str_contains($html, route('cultural-calendar.show', $entry->id))
                && str_contains($html, 'odjavite se')
                && ! str_contains($html, 'Interni')
                && ! str_contains($html, 'cancellation_reason')
                && ! str_contains($html, 'Manifestacija');
        });
        Mail::assertNotSent(CulturalCalendarFirstIncludeNewsletterMail::class);

        $this->assertSame(1, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $this->assertSame(1, NewsletterDeliveryLedger::query()->where('entry_type', 'first_include')->count());
        $this->assertSame(
            NewsletterPendingPriorityChange::STATUS_PROCESSED,
            NewsletterPendingPriorityChange::query()->value('status')
        );
        $this->assertEventUnchanged($entry, $before);
    }

    public function test_nonmatching_organizer_subscriber_does_not_receive_priority_mail(): void
    {
        $orgA = $this->makeOrganizer('Org A');
        $orgB = $this->makeOrganizer('Org B');
        $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgB->id],
            false
        );
        $entry = $this->publishOrganizerEvent('Tudji', $orgA);
        NewsletterDeliveryLedger::query()->create([
            'newsletter_subscription_id' => NewsletterSubscription::query()->first()->id,
            'cultural_event_entry_id' => $entry->id,
            'cultural_occurrence_id' => null,
            'entry_type' => NewsletterDeliveryLedger::TYPE_FIRST_INCLUDE,
            'change_control_key' => null,
            'delivery_cycle_id' => '00000000-0000-0000-0000-000000000001',
            'payload_snapshot' => null,
            'sent_at' => now(),
        ]);

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertNotSent(CulturalCalendarPriorityNewsletterMail::class);
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
    }

    public function test_unsubscribed_user_does_not_receive_priority_mail(): void
    {
        $subscription = $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Odjava');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $this->subscriptions->unsubscribe($subscription->fresh());
        Mail::fake();

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
    }

    public function test_inactive_user_does_not_receive_priority_mail(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Neaktivan');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $this->subscriber->forceFill(['activation_status' => 'deactivated'])->save();
        Mail::fake();

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $this->assertSame(
            NewsletterPendingPriorityChange::STATUS_PENDING,
            NewsletterPendingPriorityChange::query()->value('status')
        );
    }

    public function test_unverified_user_does_not_receive_priority_mail(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Neverifikovan');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $this->subscriber->forceFill(['email_verified_at' => null])->save();
        Mail::fake();

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
    }

    public function test_priority_requires_prior_first_include(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Nikad poslat');
        $this->eventLifecycle->cancel($entry, $this->editor);

        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $this->assertSame(
            NewsletterPendingPriorityChange::STATUS_PROCESSED,
            NewsletterPendingPriorityChange::query()->value('status')
        );
    }

    public function test_failed_send_writes_no_priority_ledger_and_later_cycle_sends(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Posle greske');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);

        $this->app->instance(NewsletterOutboundMailer::class, new class extends NewsletterOutboundMailer
        {
            public function send(string $recipientEmail, $mailable): void
            {
                throw new \RuntimeException('smtp-fail');
            }
        });

        $this->artisan('cultural-calendar:send-newsletter-priority')->assertFailed();
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $this->assertSame(
            NewsletterPendingPriorityChange::STATUS_PENDING,
            NewsletterPendingPriorityChange::query()->value('status')
        );

        $this->app->forgetInstance(NewsletterOutboundMailer::class);
        $this->app->forgetInstance(NewsletterPriorityDeliveryService::class);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, 1);
        $this->assertSame(1, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
    }

    public function test_same_change_not_resent_after_ledger_success(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Jednom');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();
        Mail::fake();

        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(1, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
    }

    public function test_priority_ledger_unique_protection(): void
    {
        $subscription = $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Unikat');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        $row = NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->first();
        $this->expectException(\Illuminate\Database\QueryException::class);
        NewsletterDeliveryLedger::query()->create([
            'newsletter_subscription_id' => $subscription->id,
            'cultural_event_entry_id' => $entry->id,
            'cultural_occurrence_id' => null,
            'entry_type' => NewsletterDeliveryLedger::TYPE_PRIORITY_CHANGE,
            'change_control_key' => $row->change_control_key,
            'delivery_cycle_id' => '00000000-0000-0000-0000-000000000099',
            'payload_snapshot' => null,
            'sent_at' => now(),
        ]);
    }

    public function test_current_user_email_used_and_legacy_ignored(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Adresa');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        NewsletterSubscriber::query()->create([
            'email' => 'legacy-nl05@example.com',
            'is_subscribed' => true,
            'subscribed_at' => now(),
            'unsubscribe_token' => 'legacy-token-nl05',
        ]);
        $legacyCount = NewsletterSubscriber::query()->count();
        Mail::fake();

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail): bool {
            return $mail->hasTo($this->subscriber->email)
                && ! $mail->hasTo('legacy-nl05@example.com');
        });
        $this->assertSame($legacyCount, NewsletterSubscriber::query()->count());
    }

    public function test_unsubscribe_link_and_invalid_token_unchanged(): void
    {
        $subscription = $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Link');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail) use ($subscription): bool {
            return str_contains(
                $mail->render(),
                route('newsletter.unsubscribe.public.show', ['token' => $subscription->fresh()->unsubscribe_token])
            );
        });

        $this->get(route('newsletter.unsubscribe.public.show', ['token' => str_repeat('b', 64)]))
            ->assertOk()
            ->assertSee('Link za odjavu nije važeći', false);
        $this->assertTrue($subscription->fresh()->isActive());
    }

    public function test_postponement_and_new_term_mail_content(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Termin sadrzaj');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();
        $occ = $entry->occurrences()->first();
        $this->occurrenceLifecycle->postpone($occ);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail): bool {
            return str_contains($mail->render(), 'Termin je odgođen')
                && ! str_contains($mail->render(), 'Kasni razlog');
        });

        Mail::fake();
        $this->occurrenceLifecycle->resumeWithNewTermin($occ->fresh(), [
            'datum' => now()->addDays(40)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail): bool {
            $html = $mail->render();

            return str_contains($html, 'Promijenjen je datum ili vrijeme')
                && str_contains($html, now()->addDays(40)->format('d.m.Y'));
        });
    }

    public function test_location_change_from_approved_proposal_apply_registers_and_sends(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Lokacija');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();
        $occ = $entry->occurrences()->first();
        $this->occurrenceWriter->applyUpdateFromApprovedProposal($occ, [
            'datum' => $occ->datum->format('Y-m-d'),
            'cjelodnevno' => true,
            'location_manual_name' => 'Nova dvorana',
        ]);

        $pending = NewsletterPendingPriorityChange::query()->where('status', 'pending')->sole();
        $this->assertSame(NewsletterPendingPriorityChange::KIND_LOCATION_CHANGED, $pending->change_kind);

        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();
        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail): bool {
            return str_contains($mail->render(), 'Promijenjena je lokacija')
                && str_contains($mail->render(), 'Nova dvorana');
        });
    }

    public function test_coalescing_postpone_then_event_cancel_sends_only_cancel(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Konačno stanje');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();
        $this->occurrenceLifecycle->postpone($entry->occurrences()->first());
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, 1);
        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, function ($mail): bool {
            $html = $mail->render();

            return str_contains($html, 'Događaj je otkazan')
                && ! str_contains($html, 'Termin je odgođen');
        });
        $this->assertSame(1, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
    }

    public function test_multiple_events_grouped_in_one_priority_mail(): void
    {
        $this->subscribeAll();
        $a = $this->publishWithoutOrganizer('Prvi');
        $b = $this->publishWithoutOrganizer('Drugi');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();
        $this->eventLifecycle->cancel($a->fresh(), $this->editor);
        $this->eventLifecycle->cancel($b->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, 1);
        $this->assertSame(2, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $cycleIds = NewsletterDeliveryLedger::query()
            ->where('entry_type', 'priority_change')
            ->pluck('delivery_cycle_id')
            ->unique()
            ->all();
        $this->assertCount(1, $cycleIds);
    }

    public function test_regular_first_include_command_unaffected_and_writes_no_priority(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Redovni');
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();

        Mail::assertNotSent(CulturalCalendarPriorityNewsletterMail::class);
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'first_include')->count());
        $this->assertSame(0, NewsletterDeliveryLedger::query()->where('entry_type', 'priority_change')->count());
        $this->assertSame(1, NewsletterPendingPriorityChange::query()->count());
    }

    public function test_priority_command_does_not_write_first_include(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Bez first');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $firstCount = NewsletterDeliveryLedger::query()->where('entry_type', 'first_include')->count();
        Mail::fake();
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();

        $this->assertSame(
            $firstCount,
            NewsletterDeliveryLedger::query()->where('entry_type', 'first_include')->count()
        );
    }

    public function test_overlap_guard_skips_second_priority_cycle(): void
    {
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Overlap');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);
        Mail::fake();

        $lock = Cache::lock(SendCulturalCalendarPriorityNewsletter::LOCK_KEY, 1800);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();
            Mail::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_regular_and_priority_locks_are_independent(): void
    {
        $this->subscribeAll();
        $this->publishWithoutOrganizer('Nezavisno');

        $lock = Cache::lock(SendCulturalCalendarPriorityNewsletter::LOCK_KEY, 1800);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
            Mail::assertSent(CulturalCalendarFirstIncludeNewsletterMail::class, 1);
        } finally {
            $lock->release();
        }
    }

    public function test_aggregation_window_holds_pending_until_due(): void
    {
        config(['newsletter.priority_aggregation_minutes' => 15]);
        Carbon::setTestNow('2026-08-14 12:00:00');
        $this->subscribeAll();
        $entry = $this->publishWithoutOrganizer('Prozor');
        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        Mail::fake();
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor);

        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();
        Mail::assertNothingSent();

        Carbon::setTestNow('2026-08-14 12:16:00');
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();
        Mail::assertSent(CulturalCalendarPriorityNewsletterMail::class, 1);
    }

    public function test_draft_occurrence_postpone_does_not_register(): void
    {
        $draft = $this->makeDraftWithoutOrganizer('Nacrt');
        $this->occurrenceLifecycle->postpone($draft->occurrences()->first());

        $this->assertSame(0, NewsletterPendingPriorityChange::query()->count());
    }

    private function subscribeAll(): NewsletterSubscription
    {
        return $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
    }

    private function eventSnapshot(CulturalEventEntry $entry): array
    {
        $entry = $entry->fresh(['occurrences']);

        return [
            'naslov' => $entry->naslov,
            'status' => $entry->status,
            'cancellation_reason' => $entry->cancellation_reason,
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
