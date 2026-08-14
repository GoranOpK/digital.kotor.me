<?php

namespace Tests\Feature;

use App\Mail\CulturalCalendarNewsletterWelcomeMail;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterSubscription;
use App\Models\NewsletterSubscriptionSourceCoverage;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\Newsletter\FirstIncludeEligibilityService;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * NL-03 — first_include eligibility foundation. No mail. No ledger write.
 */
class NewsletterNl03FirstIncludeEligibilityTest extends TestCase
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
            'naziv' => 'NL-03 Kategorija',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->subscriptions = app(NewsletterSubscriptionManager::class);
        $this->eligibility = app(FirstIncludeEligibilityService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_draft_event_has_null_first_published_at(): void
    {
        $entry = $this->makeDraftWithoutOrganizer('Nacrt');

        $this->assertNull($entry->first_published_at);
        Mail::assertNothingSent();
    }

    public function test_approval_first_publish_sets_timestamp_once(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $organizer = $this->makeOrganizer('Org Approve');
        $entry = $this->makeDraftWithOrganizer('Na odobrenju', $organizer);
        $this->eventLifecycle->submitForApproval($entry, $this->editor);
        $this->assertNull($entry->fresh()->first_published_at);

        Carbon::setTestNow('2026-08-14 11:00:00');
        $published = $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        $this->assertNotNull($published->first_published_at);
        $this->assertSame('2026-08-14 11:00:00', $published->first_published_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-08-14 12:00:00');
        $published->naslov = 'Izmijenjen';
        $published->save();
        $this->assertSame('2026-08-14 11:00:00', $published->fresh()->first_published_at->format('Y-m-d H:i:s'));
    }

    public function test_direct_publish_sets_timestamp_once(): void
    {
        Carbon::setTestNow('2026-08-14 09:00:00');
        $entry = $this->makeDraftWithoutOrganizer('Direktno');
        $published = $this->eventLifecycle->publishDirectly($entry, $this->editor);

        $this->assertSame('2026-08-14 09:00:00', $published->first_published_at->format('Y-m-d H:i:s'));

        $published->first_published_at = now()->addDay();
        $published->save();
        $this->assertSame('2026-08-14 09:00:00', $published->fresh()->first_published_at->format('Y-m-d H:i:s'));
    }

    public function test_later_lifecycle_operations_do_not_change_first_published_at(): void
    {
        Carbon::setTestNow('2026-08-14 08:00:00');
        $entry = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Zasticen'),
            $this->editor
        );
        $stamp = $entry->first_published_at->copy();
        $occurrence = $entry->occurrences()->first();

        Carbon::setTestNow('2026-08-14 13:00:00');
        $this->eventWriter->updateContent($entry->fresh(), $this->editor, [
            'naslov' => 'Uredjen',
            'category_id' => $this->category->id,
        ]);
        $this->eventWriter->updateContent($entry->fresh(), $this->editor, ['featured' => true]);

        $this->occurrenceLifecycle->postpone($occurrence->fresh(), 'Odgodjeno');
        $this->occurrenceLifecycle->resumeWithNewTermin($occurrence->fresh(), [
            'datum' => now()->addDays(20)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $organizer = $this->makeOrganizer('Naknadni Org');
        $this->eventWriter->linkOrganizer($entry->fresh(), $this->editor, $organizer->id);

        $cancelled = $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Otkaz');
        $this->assertSame($stamp->format('Y-m-d H:i:s'), $cancelled->first_published_at->format('Y-m-d H:i:s'));

        $archived = $this->eventLifecycle->archiveIfEligible($cancelled->fresh());
        $this->assertSame($stamp->format('Y-m-d H:i:s'), $archived->first_published_at->format('Y-m-d H:i:s'));
    }

    public function test_null_first_published_at_is_not_candidate(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $entry = $this->makeDraftWithoutOrganizer('Bez stamp');
        $this->eventLifecycle->publishDirectly($entry, $this->editor);
        $entry->fresh()->forceFill(['first_published_at' => null])->saveQuietly();

        $this->assertFalse($this->eligibility->isEligible($entry->fresh(), $subscription->fresh()));
        $this->assertNoDeliverySideEffects();
    }

    public function test_future_postponed_occurrence_alone_is_not_first_include_candidate(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $entry = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Samo odgodjeno'),
            $this->editor
        );
        $this->occurrenceLifecycle->postpone($entry->occurrences()->first()->fresh(), 'Odgodjeno');

        $this->assertFalse($this->eligibility->eventIsFirstIncludeCandidate($entry->fresh()));
        $this->assertFalse($this->eligibility->isEligible($entry->fresh(), $subscription->fresh()));
        $this->assertNoDeliverySideEffects();
    }

    public function test_postponed_historical_occurrence_plus_future_planned_can_be_candidate(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $draft = $this->makeDraftWithoutOrganizer('Odgodjeno plus planirano');
        $this->occurrenceWriter->create($draft, [
            'datum' => now()->addDays(20)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $entry = $this->eventLifecycle->publishDirectly($draft->fresh(), $this->editor);
        $original = $entry->occurrences()->orderBy('id')->first();
        $this->occurrenceLifecycle->postpone($original->fresh(), 'Odgodjeno');

        $this->assertTrue($this->eligibility->eventIsFirstIncludeCandidate($entry->fresh()));
        $this->assertTrue($this->eligibility->isEligible($entry->fresh(), $subscription->fresh()));
        $this->assertNoDeliverySideEffects();
    }

    public function test_no_future_valid_occurrence_is_not_first_include_candidate(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $entry = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Bez vazecih termina'),
            $this->editor
        );
        $this->occurrenceLifecycle->cancel($entry->occurrences()->first()->fresh(), 'Otkaz termina');

        $this->assertFalse($this->eligibility->eventIsFirstIncludeCandidate($entry->fresh()));
        $this->assertFalse($this->eligibility->isEligible($entry->fresh(), $subscription->fresh()));
        $this->assertNoDeliverySideEffects();
    }

    public function test_first_subscribe_all_events_opens_coverage(): void
    {
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $this->assertSame(1, $subscription->sourceCoverages()->count());
        $row = $subscription->sourceCoverages()->first();
        $this->assertSame(NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS, $row->source_type);
        $this->assertNull($row->covered_until);
        $this->assertTrue($row->covered_since->equalTo($subscription->subscribed_at));
    }

    public function test_first_subscribe_selected_and_without_organizer_opens_coverage(): void
    {
        $organizer = $this->makeOrganizer('Org A');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$organizer->id],
            true
        );

        $types = $subscription->sourceCoverages()->pluck('source_type')->sort()->values()->all();
        $this->assertSame([
            NewsletterSubscriptionSourceCoverage::SOURCE_ORGANIZER,
            NewsletterSubscriptionSourceCoverage::SOURCE_WITHOUT_ORGANIZER,
        ], $types);
    }

    public function test_add_organizer_does_not_reset_existing_coverage_or_drop_prior_event(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $orgA = $this->makeOrganizer('A');
        $orgB = $this->makeOrganizer('B');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );

        Carbon::setTestNow('2026-08-14 11:00:00');
        $eventA1 = $this->publishOrganizerEvent('A1', $orgA);

        Carbon::setTestNow('2026-08-14 11:30:00');
        $eventBOld = $this->publishOrganizerEvent('B-old', $orgB);

        Carbon::setTestNow('2026-08-14 12:00:00');
        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id, $orgB->id],
            false
        );

        $aCoverage = NewsletterSubscriptionSourceCoverage::query()
            ->where('cultural_organizer_id', $orgA->id)
            ->whereNull('covered_until')
            ->first();
        $this->assertSame('2026-08-14 10:00:00', $aCoverage->covered_since->format('Y-m-d H:i:s'));

        $this->assertTrue($this->eligibility->isEligible($eventA1->fresh(), $subscription->fresh()));
        $this->assertFalse($this->eligibility->isEligible($eventBOld->fresh(), $subscription->fresh()));

        Carbon::setTestNow('2026-08-14 13:00:00');
        $eventBNew = $this->publishOrganizerEvent('B-new', $orgB);
        $this->assertTrue($this->eligibility->isEligible($eventBNew->fresh(), $subscription->fresh()));
        $this->assertNoDeliverySideEffects();
    }

    public function test_remove_organizer_closes_coverage_and_fails_current_match(): void
    {
        $orgA = $this->makeOrganizer('A-remove');
        $orgB = $this->makeOrganizer('B-keep');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id, $orgB->id],
            false
        );
        $eventA1 = $this->publishOrganizerEvent('A1-remove', $orgA);

        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgB->id],
            false
        );

        $closed = NewsletterSubscriptionSourceCoverage::query()
            ->where('cultural_organizer_id', $orgA->id)
            ->first();
        $this->assertNotNull($closed->covered_until);
        $this->assertFalse($this->eligibility->isEligible($eventA1->fresh(), $subscription->fresh()));
    }

    public function test_selected_to_all_keeps_prior_organizer_event_and_excludes_uncovered_old_events(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $orgA = $this->makeOrganizer('A-switch');
        $orgB = $this->makeOrganizer('B-uncovered');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );

        Carbon::setTestNow('2026-08-14 11:00:00');
        $eventA = $this->publishOrganizerEvent('A-during-selected', $orgA);
        $eventB = $this->publishOrganizerEvent('B-before-all', $orgB);

        Carbon::setTestNow('2026-08-14 12:00:00');
        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->usesAllEventsScope());
        $this->assertSame(0, $fresh->organizers()->count());
        $this->assertNotNull(
            $fresh->sourceCoverages()
                ->where('source_type', NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS)
                ->whereNull('covered_until')
                ->first()
        );
        $this->assertNotNull(
            $fresh->sourceCoverages()
                ->where('cultural_organizer_id', $orgA->id)
                ->whereNull('covered_until')
                ->first()
        );

        $this->assertTrue($this->eligibility->isEligible($eventA->fresh(), $fresh));
        $this->assertFalse($this->eligibility->isEligible($eventB->fresh(), $fresh));
    }

    public function test_all_to_selected_inherits_all_events_coverage_for_continuously_covered_organizer(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $orgA = $this->makeOrganizer('A-all');
        $orgB = $this->makeOrganizer('B-all');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        Carbon::setTestNow('2026-08-14 11:00:00');
        $eventA = $this->publishOrganizerEvent('A-under-all', $orgA);
        $eventB = $this->publishOrganizerEvent('B-under-all', $orgB);

        Carbon::setTestNow('2026-08-14 12:00:00');
        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );

        $fresh = $subscription->fresh();
        $allClosed = $fresh->sourceCoverages()
            ->where('source_type', NewsletterSubscriptionSourceCoverage::SOURCE_ALL_EVENTS)
            ->first();
        $this->assertNotNull($allClosed->covered_until);

        $aCoverage = $fresh->sourceCoverages()
            ->where('cultural_organizer_id', $orgA->id)
            ->whereNull('covered_until')
            ->first();
        $this->assertSame('2026-08-14 10:00:00', $aCoverage->covered_since->format('Y-m-d H:i:s'));

        $this->assertTrue($this->eligibility->isEligible($eventA->fresh(), $fresh));
        $this->assertFalse($this->eligibility->isEligible($eventB->fresh(), $fresh));
    }

    public function test_without_organizer_toggle_and_noop_save(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $orgA = $this->makeOrganizer('A-wo');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );

        Carbon::setTestNow('2026-08-14 11:00:00');
        $oldNoOrg = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Stari bez org'),
            $this->editor
        );

        $before = $subscription->sourceCoverages()->get()->map(fn ($row) => [
            $row->source_type,
            $row->cultural_organizer_id,
            $row->covered_since?->format('Y-m-d H:i:s'),
            $row->covered_until?->format('Y-m-d H:i:s'),
        ])->all();

        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );
        $afterNoop = $subscription->fresh()->sourceCoverages()->get()->map(fn ($row) => [
            $row->source_type,
            $row->cultural_organizer_id,
            $row->covered_since?->format('Y-m-d H:i:s'),
            $row->covered_until?->format('Y-m-d H:i:s'),
        ])->all();
        $this->assertSame($before, $afterNoop);

        Carbon::setTestNow('2026-08-14 12:00:00');
        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            true
        );
        $this->assertFalse($this->eligibility->isEligible($oldNoOrg->fresh(), $subscription->fresh()));

        Carbon::setTestNow('2026-08-14 13:00:00');
        $newNoOrg = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Novi bez org'),
            $this->editor
        );
        $this->assertTrue($this->eligibility->isEligible($newNoOrg->fresh(), $subscription->fresh()));

        $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );
        $this->assertFalse($this->eligibility->isEligible($newNoOrg->fresh(), $subscription->fresh()));
        $closed = $subscription->fresh()->sourceCoverages()
            ->where('source_type', NewsletterSubscriptionSourceCoverage::SOURCE_WITHOUT_ORGANIZER)
            ->first();
        $this->assertNotNull($closed->covered_until);
    }

    public function test_unsubscribe_and_reactivate_exclude_pre_reactivation_events(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $orgA = $this->makeOrganizer('A-re');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );

        Carbon::setTestNow('2026-08-14 11:00:00');
        $this->subscriptions->unsubscribe($subscription->fresh());
        $this->assertSame(
            0,
            $subscription->fresh()->sourceCoverages()->whereNull('covered_until')->count()
        );

        Carbon::setTestNow('2026-08-14 12:00:00');
        $duringOff = $this->publishOrganizerEvent('Tokom odjave', $orgA);

        Carbon::setTestNow('2026-08-14 13:00:00');
        $reactivated = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );
        $this->assertSame('2026-08-14 13:00:00', $reactivated->subscribed_at->format('Y-m-d H:i:s'));
        $this->assertFalse($this->eligibility->isEligible($duringOff->fresh(), $reactivated->fresh()));

        Carbon::setTestNow('2026-08-14 14:00:00');
        $after = $this->publishOrganizerEvent('Poslije reaktivacije', $orgA);
        $this->assertTrue($this->eligibility->isEligible($after->fresh(), $reactivated->fresh()));
    }

    public function test_organizer_deactivation_does_not_rewrite_coverage(): void
    {
        $orgA = $this->makeOrganizer('A-deact');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );
        $event = $this->publishOrganizerEvent('Dok aktivan', $orgA);
        $before = $subscription->sourceCoverages()
            ->get()
            ->map(fn ($row) => [
                $row->id,
                $row->source_type,
                $row->cultural_organizer_id,
                $row->covered_since->format('Y-m-d H:i:s'),
                $row->covered_until,
            ])
            ->all();

        $orgA->status = CulturalOrganizer::STATUS_DEACTIVATED;
        $orgA->save();

        $this->assertSame(
            $before,
            $subscription->fresh()->sourceCoverages()->get()->map(fn ($row) => [
                $row->id,
                $row->source_type,
                $row->cultural_organizer_id,
                $row->covered_since->format('Y-m-d H:i:s'),
                $row->covered_until,
            ])->all()
        );
        $this->assertFalse($this->eligibility->isEligible($event->fresh(), $subscription->fresh()));

        $orgA->status = CulturalOrganizer::STATUS_ACTIVE;
        $orgA->save();
        $this->assertTrue($this->eligibility->isEligible($event->fresh(), $subscription->fresh()));
    }

    public function test_user_and_event_eligibility_matrix(): void
    {
        $orgA = $this->makeOrganizer('Matrix A');
        $orgB = $this->makeOrganizer('Matrix B');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );

        $published = $this->publishOrganizerEvent('Objavljen', $orgA);
        $this->assertTrue($this->eligibility->isEligible($published, $subscription->fresh()));

        $this->subscriber->forceFill(['activation_status' => 'deactivated'])->save();
        $this->assertFalse($this->eligibility->isEligible($published->fresh(), $subscription->fresh()));
        $this->subscriber->forceFill(['activation_status' => 'active'])->save();

        $this->subscriber->forceFill(['email_verified_at' => null])->save();
        $this->assertFalse($this->eligibility->isEligible($published->fresh(), $subscription->fresh()));
        $this->subscriber->forceFill(['email_verified_at' => now()])->save();

        $this->subscriptions->unsubscribe($subscription->fresh());
        $this->assertFalse($this->eligibility->isEligible($published->fresh(), $subscription->fresh()));
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $afterReactivate = $this->publishOrganizerEvent('Poslije matrix reaktivacije', $orgA);
        $this->assertTrue($this->eligibility->isEligible($afterReactivate, $subscription->fresh()));

        $draft = $this->makeDraftWithOrganizer('Nacrt matrix', $orgA);
        $this->assertFalse($this->eligibility->isEligible($draft, $subscription->fresh()));

        $pending = $this->eventLifecycle->submitForApproval($draft->fresh(), $this->editor);
        $this->assertFalse($this->eligibility->isEligible($pending, $subscription->fresh()));

        $cancelled = $this->eventLifecycle->cancel($afterReactivate->fresh(), $this->editor, 'x');
        $this->assertFalse($this->eligibility->isEligible($cancelled, $subscription->fresh()));

        $toArchive = $this->publishOrganizerEvent('Za arhivu', $orgA);
        $this->occurrenceLifecycle->cancel($toArchive->occurrences()->first(), 'x');
        $archived = $this->eventLifecycle->archiveIfEligible($toArchive->fresh());
        $this->assertFalse($this->eligibility->isEligible($archived, $subscription->fresh()));

        $past = $this->publishOrganizerEvent('Proslost', $orgA);
        $pastOcc = $past->occurrences()->first();
        $pastOcc->datum = now()->subDay()->toDateString();
        $pastOcc->save();
        $this->assertFalse($this->eligibility->isEligible($past->fresh(), $subscription->fresh()));

        $noOrg = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Bez org matrix'),
            $this->editor
        );
        $manual = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Manual name',
            'category_id' => $this->category->id,
            'organizer_manual_name' => 'Neregistrovan',
        ]);
        $this->occurrenceWriter->create($manual, [
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $manualPublished = $this->eventLifecycle->publishDirectly($manual->fresh(), $this->editor);

        $this->assertTrue($this->eligibility->isEligible($noOrg, $subscription->fresh()));
        $this->assertTrue($this->eligibility->isEligible($manualPublished, $subscription->fresh()));

        $selected = $this->subscriptions->updatePreferences(
            $subscription->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );
        $eventB = $this->publishOrganizerEvent('B selected', $orgB);
        $eventASelected = $this->publishOrganizerEvent('A selected after switch', $orgA);
        $this->assertTrue($this->eligibility->isEligible($eventASelected, $selected->fresh()));
        $this->assertFalse($this->eligibility->isEligible($eventB, $selected->fresh()));
        $this->assertFalse($this->eligibility->isEligible($noOrg->fresh(), $selected->fresh()));

        $withFlag = $this->subscriptions->updatePreferences(
            $selected->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            true
        );
        $newNoOrg = $this->eventLifecycle->publishDirectly(
            $this->makeDraftWithoutOrganizer('Flag yes'),
            $this->editor
        );
        $this->assertTrue($this->eligibility->isEligible($newNoOrg, $withFlag->fresh()));

        NewsletterSubscriber::query()->create([
            'email' => 'legacy@example.test',
            'unsubscribe_token' => 'legacy-token-nl03',
            'is_subscribed' => true,
        ]);
        $this->assertFalse(
            str_contains(
                file_get_contents(app_path('Services/Newsletter/FirstIncludeEligibilityService.php')),
                'CulturalEvent;'
            )
        );
        $this->assertFalse(
            str_contains(
                file_get_contents(app_path('Services/Newsletter/FirstIncludeEligibilityService.php')),
                'NewsletterSubscriber'
            )
        );

        $this->assertNoDeliverySideEffects();
        Mail::assertNotSent(CulturalCalendarNewsletterWelcomeMail::class);
    }

    public function test_coverage_fk_delete_does_not_delete_subscription(): void
    {
        $orgA = $this->makeOrganizer('To delete');
        $subscription = $this->subscriptions->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$orgA->id],
            false
        );

        $orgA->delete();

        $this->assertDatabaseHas('newsletter_subscriptions', ['id' => $subscription->id]);
        $this->assertSame(
            0,
            NewsletterSubscriptionSourceCoverage::query()
                ->where('newsletter_subscription_id', $subscription->id)
                ->where('source_type', NewsletterSubscriptionSourceCoverage::SOURCE_ORGANIZER)
                ->count()
        );
    }

    private function assertNoDeliverySideEffects(): void
    {
        Mail::assertNothingSent();
        $this->assertSame(0, \App\Models\NewsletterDeliveryLedger::query()->count());
        $this->assertDatabaseCount('newsletter_subscribers', NewsletterSubscriber::query()->count());
    }

    private function publishOrganizerEvent(string $naslov, CulturalOrganizer $organizer): CulturalEventEntry
    {
        $entry = $this->makeDraftWithOrganizer($naslov, $organizer);
        $submitted = $this->eventLifecycle->submitForApproval($entry, $this->editor);

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

    private function makeDraftWithOrganizer(string $naslov, CulturalOrganizer $organizer): CulturalEventEntry
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
