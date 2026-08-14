<?php

namespace Tests\Feature;

use App\Models\CulturalActivityRecord;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityRecorder;
use App\Services\CulturalActivity\CulturalActivityStore;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * F8-03 — failure isolation + §7.2 exclusions.
 */
class CulturalActivityEmitterIsolationExclusionTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_event_create_survives_audit_throw(): void
    {
        Event::fake([MessageLogged::class]);
        $this->forceFailingEmitter();

        $category = CulturalCategory::create([
            'naziv' => 'Iso',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = app(EventWriter::class)->createDraft($this->editor, [
            'naslov' => 'Iso Event',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('cultural_event_entries', ['id' => $entry->id]);
    }

    public function test_manifestation_create_survives_audit_throw(): void
    {
        $this->forceFailingEmitter();
        $mf = app(ManifestationWriter::class)->createDraft($this->editor, ['naziv' => 'Iso MF']);
        $this->assertDatabaseHas('cultural_manifestations', ['id' => $mf->id]);
    }

    public function test_newsletter_activate_survives_audit_throw(): void
    {
        $this->forceFailingEmitter();
        $sub = app(NewsletterSubscriptionManager::class)->activate(
            $this->user,
            \App\Models\NewsletterSubscription::SCOPE_ALL_EVENTS,
            [],
            false
        );
        $this->assertDatabaseHas('newsletter_subscriptions', ['id' => $sub->id]);
    }

    public function test_dismiss_does_not_emit_audit(): void
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Dismiss me',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->user->id,
            'proposed_moderator_user_id' => $this->user->id,
            'proposed_moderator_email' => $this->user->email,
            'proposed_moderator_is_submitter' => true,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        $before = CulturalActivityRecord::query()->count();
        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.dismiss', $request))
            ->assertRedirect();
        $this->assertSame($before, CulturalActivityRecord::query()->count());
    }

    public function test_occurrence_writer_draft_update_is_not_reschedule_or_location_audit(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Draft occ',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = app(EventWriter::class)->createDraft($this->editor, [
            'naslov' => 'Draft occ event',
            'category_id' => $category->id,
        ]);
        $occ = app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(2)->toDateString(),
            'cjelodnevno' => true,
        ]);
        app(OccurrenceWriter::class)->update($occ, [
            'datum' => now()->addDays(4)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->assertSame(0, CulturalActivityRecord::query()->where('event_type', 'occ.reschedule')->count());
        $this->assertSame(0, CulturalActivityRecord::query()->where('event_type', 'occ.location_change')->count());
    }

    private function forceFailingEmitter(): void
    {
        $store = $this->createMock(CulturalActivityStore::class);
        $store->method('write')->willThrowException(new \RuntimeException('forced audit'));
        $recorder = new CulturalActivityRecorder($store);
        $this->app->instance(CulturalActivityRecorder::class, $recorder);
        $this->app->instance(CulturalActivityEmitter::class, new CulturalActivityEmitter($recorder));
    }
}
