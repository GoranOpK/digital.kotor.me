<?php

namespace Tests\Feature;

use App\Models\CulturalActivityRecord;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\CulturalOrganizer;
use App\Models\NewsletterSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * F8-03 — TS12-MF / TS12-NL emitters.
 */
class CulturalActivityEmitterMfNlTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private User $subscriber;

    private CulturalCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.mf.mod@example.com',
        ]);
        $this->subscriber = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->category = CulturalCategory::create([
            'naziv' => 'MF Cat',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    public function test_manifestation_catalog_lifecycle(): void
    {
        $organizer = $this->makeOrganizer();
        $eventA = $this->publishedEvent('A');
        $eventB = $this->publishedEvent('B');
        $eventC = $this->publishedEvent('C');

        $writer = app(ManifestationWriter::class);
        $lifecycle = app(ManifestationLifecycle::class);

        $mf = $writer->createDraft($this->moderator, [
            'naziv' => 'MF',
            'organizer_id' => $organizer->id,
        ]);
        $this->assertActivity('mf.create', [
            'source_module' => 'TS-005',
            'actor_user_id' => $this->moderator->id,
            'target_id' => $mf->id,
        ]);

        $writer->linkEvent($mf, $eventA->id, $this->moderator);
        $this->assertActivity('mf.event.add', ['target_id' => $mf->id]);
        $writer->linkEvent($mf->fresh(), $eventC->id, $this->moderator);

        $cover = CulturalMedia::create([
            'naziv' => 'Cover MF',
            'namjena' => CulturalMedia::PURPOSE_MANIFESTATION_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'mf.jpg',
            'interni_naziv' => 'mf.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 120,
            'storage_path' => 'cultural-media/mf.jpg',
            'creator_id' => $this->editor->id,
        ]);
        $writer->updateContent($mf->fresh(), $this->editor, [
            'cover_media_id' => $cover->id,
            'web_stranica' => 'https://example.com/info',
            'organizer_id' => null,
        ]);
        $this->assertActivity('mf.cover.change', ['target_id' => $mf->id]);
        $this->assertActivity('mf.webinfo.change', ['target_id' => $mf->id]);
        $this->assertActivity('mf.organizer.change', ['target_id' => $mf->id]);

        $target = $writer->createDraft($this->editor, [
            'naziv' => 'Target MF',
            'event_entry_ids' => [$eventB->id],
        ]);
        $writer->moveEvent($target, $eventC->id, $this->editor);
        $this->assertActivity('mf.event.move', ['target_id' => $target->id]);

        $writer->unlinkEvent($target->fresh(), $eventB->id, $this->editor);
        $this->assertActivity('mf.event.remove', ['target_id' => $target->id]);

        $lifecycle->submitForApproval($mf->fresh(), $this->moderator);
        $this->assertActivity('mf.submit', ['target_id' => $mf->id]);

        $lifecycle->returnToRevision($mf->fresh(), $this->editor);
        $this->assertActivity('mf.return', ['target_id' => $mf->id]);

        $lifecycle->submitForApproval($mf->fresh(), $this->moderator);
        $lifecycle->publish($mf->fresh(), $this->editor);
        $this->assertActivity('mf.publish', ['actor_user_id' => $this->editor->id]);

        $direct = $writer->createDraft($this->editor, [
            'naziv' => 'Direct MF',
            'event_entry_ids' => [$eventB->id],
        ]);
        $lifecycle->publishDirectly($direct->fresh(), $this->editor);
        $this->assertSame(2, CulturalActivityRecord::query()->where('event_type', 'mf.publish')->count());

        $lifecycle->cancel($mf->fresh(), $this->editor);
        $this->assertActivity('mf.cancel', ['target_id' => $mf->id]);
    }

    public function test_newsletter_user_actions_and_privacy(): void
    {
        $manager = app(NewsletterSubscriptionManager::class);
        $sub = $manager->activate(
            $this->subscriber,
            NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $activate = $this->assertActivity('nl.activate', [
            'source_module' => 'TS-011',
            'actor_user_id' => $this->subscriber->id,
            'target_id' => $sub->id,
        ]);
        $this->assertArrayNotHasKey('email', $activate->context ?? []);
        $this->assertArrayNotHasKey('unsubscribe_token', $activate->context ?? []);

        $organizer = $this->makeOrganizer();
        $manager->updatePreferences(
            $sub->fresh(),
            NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            [$organizer->id],
            false
        );
        $this->assertActivity('nl.preferences.change', ['target_id' => $sub->id]);

        $manager->unsubscribe($sub->fresh());
        $this->assertActivity('nl.unsubscribe', ['target_id' => $sub->id]);

        $manager->activate($this->subscriber, NewsletterSubscription::SCOPE_ALL_EVENTS, [], false);
        $this->assertActivity('nl.reactivate', ['target_id' => $sub->id]);
    }

    public function test_regular_and_priority_send_emit_system_cycle_records(): void
    {
        $manager = app(NewsletterSubscriptionManager::class);
        $manager->activate($this->subscriber, NewsletterSubscription::SCOPE_ALL_EVENTS, [], false);
        $this->publishedEvent('NL Send');

        $this->artisan('cultural-calendar:send-newsletter')->assertSuccessful();
        $regular = CulturalActivityRecord::query()->where('event_type', 'nl.send.regular')->first();
        $this->assertNotNull($regular);
        $this->assertSame('system', $regular->actor_type);
        $this->assertNull($regular->actor_user_id);
        $this->assertArrayHasKey('cycle_id', $regular->context ?? []);
        $this->assertArrayNotHasKey('ledger', $regular->context ?? []);
        $this->assertStringStartsWith('TS12-NL-05:', $regular->event_id);

        $entry = CulturalEventEntry::query()->where('naslov', 'NL Send')->firstOrFail();
        app(EventLifecycle::class)->cancel($entry->fresh(), $this->editor);
        config(['newsletter.priority_aggregation_minutes' => 0]);
        Mail::fake();
        $this->artisan('cultural-calendar:send-newsletter-priority')->assertSuccessful();
        $priority = CulturalActivityRecord::query()->where('event_type', 'nl.send.priority')->first();
        $this->assertNotNull($priority);
        $this->assertSame('system', $priority->actor_type);
        $this->assertNull($priority->actor_user_id);
        $this->assertSame('TS-011', $priority->source_module);
        $this->assertSame('newsletter_cycle', $priority->target_type);
        $this->assertNull($priority->target_id);
        $this->assertArrayHasKey('cycle_id', $priority->context ?? []);
        $this->assertArrayNotHasKey('email', $priority->context ?? []);
        $this->assertStringStartsWith('TS12-NL-06:', $priority->event_id);
        $this->assertNotSame($regular->event_id, $priority->event_id);
    }

    private function publishedEvent(string $naslov): CulturalEventEntry
    {
        $writer = app(EventWriter::class);
        $entry = $writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);
        app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(9)->toDateString(),
            'cjelodnevno' => true,
        ]);
        app(EventLifecycle::class)->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh();
    }

    private function makeOrganizer(): CulturalOrganizer
    {
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->editor, [
            'naziv' => 'MF Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);

        return app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function assertActivity(string $eventType, array $attrs = []): CulturalActivityRecord
    {
        $query = CulturalActivityRecord::query()->where('event_type', $eventType);
        foreach ($attrs as $key => $value) {
            $query->where($key, $value);
        }
        $record = $query->latest('id')->first();
        $this->assertNotNull($record, 'Expected activity '.$eventType);

        return $record;
    }
}
