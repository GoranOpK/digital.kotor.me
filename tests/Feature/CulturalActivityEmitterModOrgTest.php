<?php

namespace Tests\Feature;

use App\Models\CulturalActivityRecord;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityRecorder;
use App\Services\CulturalActivity\CulturalActivityStore;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalOrganizer\ModeratorEligibilityResolver;
use App\Services\CulturalOrganizer\ModeratorRequestDecisionService;
use App\Services\CulturalOrganizer\ModeratorRequestSubmissionService;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * F8-03 — TS12-MOD / TS12-ORG emitters.
 */
class CulturalActivityEmitterModOrgTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $submitter;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->submitter = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.submitter@example.com',
        ]);
        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.mod@example.com',
            'name' => 'F803 Mod',
        ]);
    }

    public function test_org_submit_approve_emits_two_br179_records(): void
    {
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->submitter, [
            'naziv' => 'F803 Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);

        $this->assertActivity('org.request.submit', [
            'source_module' => 'TS-001',
            'actor_type' => 'user',
            'actor_user_id' => $this->submitter->id,
            'target_type' => 'organizer_request',
            'target_id' => $request->id,
        ]);

        $organizer = app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);

        $approve = $this->assertActivity('org.request.approve', [
            'actor_user_id' => $this->editor->id,
            'target_type' => 'organizer',
            'target_id' => $organizer->id,
        ]);
        $this->assertSame($request->id, $approve->context['request_id']);
        $this->assertSame($organizer->id, $approve->context['organizer_id']);

        $grant = $this->assertActivity('org.initial_moderator.grant', [
            'actor_user_id' => $this->editor->id,
            'target_type' => 'moderator_grant',
        ]);
        $this->assertSame($organizer->id, $grant->context['organizer_id']);
        $this->assertSame($this->moderator->id, $grant->context['user_id']);
        $this->assertSame(2, CulturalActivityRecord::query()->whereIn('event_type', [
            'org.request.approve',
            'org.initial_moderator.grant',
        ])->count());
    }

    public function test_org_reject_and_deactivate_and_profile(): void
    {
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->submitter, [
            'naziv' => 'Reject Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);
        app(OrganizerCreationDecisionService::class)->reject($request, $this->editor);
        $this->assertActivity('org.request.reject', [
            'actor_user_id' => $this->editor->id,
            'target_id' => $request->id,
        ]);

        $organizer = $this->approvedOrganizer('Profile Org');
        $this->actingAs($this->editor)->put(route('cultural-organizers.update', $organizer), [
            'naziv' => 'Profile Org renamed',
            'opis' => 'x',
            'contact_email' => 'a@example.com',
            'contact_phone' => null,
            'website' => null,
        ])->assertRedirect();
        $this->assertActivity('org.profile.significant', [
            'actor_user_id' => $this->editor->id,
            'target_id' => $organizer->id,
        ]);

        $this->actingAs($this->editor)
            ->post(route('cultural-organizers.deactivate', $organizer))
            ->assertRedirect();
        $this->assertActivity('org.deactivate', [
            'actor_user_id' => $this->editor->id,
            'target_id' => $organizer->id,
        ]);
    }

    public function test_moderator_add_remove_lifecycle_and_system_resolver(): void
    {
        $organizer = $this->approvedOrganizer('Mod Org');
        $second = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.second@example.com',
        ]);

        $add = app(ModeratorRequestSubmissionService::class)->submit($this->moderator, $organizer, [
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'proposed_moderator_name' => $second->name,
            'proposed_moderator_email' => $second->email,
        ]);
        $this->assertActivity('mod.add.submit', [
            'actor_user_id' => $this->moderator->id,
            'target_id' => $add->id,
        ]);

        app(ModeratorRequestDecisionService::class)->approve($add, $this->editor);
        $this->assertActivity('mod.add.approve', [
            'actor_user_id' => $this->editor->id,
            'target_id' => $add->id,
        ]);

        $remove = app(ModeratorRequestSubmissionService::class)->submit($this->moderator, $organizer, [
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'target_user_id' => $second->id,
        ]);
        $this->assertActivity('mod.remove.submit', ['target_id' => $remove->id]);
        app(ModeratorRequestDecisionService::class)->reject($remove, $this->editor);
        $this->assertActivity('mod.remove.reject', [
            'actor_user_id' => $this->editor->id,
            'target_id' => $remove->id,
        ]);

        $remove2 = app(ModeratorRequestSubmissionService::class)->submit($this->moderator, $organizer, [
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'target_user_id' => $second->id,
        ]);
        app(ModeratorRequestDecisionService::class)->approve($remove2, $this->editor);
        $this->assertActivity('mod.remove.approve', ['target_id' => $remove2->id]);

        $pending = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.await@example.com',
        ]);
        $awaiting = app(ModeratorRequestSubmissionService::class)->submit($this->moderator, $organizer, [
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'proposed_moderator_name' => 'Await',
            'proposed_moderator_email' => 'f803.await@example.com',
        ]);
        $this->assertTrue($awaiting->isSubmitted() || $awaiting->isAwaitingModeratorEligibility());

        $awaitingOrg = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_name' => 'Await Org',
            'proposed_moderator_email' => 'f803.orgawait@example.com',
            'proposed_naziv' => 'Await Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);
        $awaitUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.orgawait@example.com',
        ]);
        app(ModeratorEligibilityResolver::class)->resolveForUser($awaitUser);
        $eligible = $this->assertActivity('mod.request.eligible', [
            'actor_type' => 'system',
            'actor_user_id' => null,
            'target_id' => $awaitingOrg->id,
        ]);
        $this->assertNull($eligible->actor_user_id);
    }

    public function test_mod_reject_and_idempotent_org_submit_event_id(): void
    {
        $organizer = $this->approvedOrganizer('Reject Mod Org');
        $target = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.rejectmod@example.com',
        ]);
        $add = app(ModeratorRequestSubmissionService::class)->submit($this->moderator, $organizer, [
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'proposed_moderator_name' => $target->name,
            'proposed_moderator_email' => $target->email,
        ]);
        app(ModeratorRequestDecisionService::class)->reject($add, $this->editor);
        $this->assertActivity('mod.add.reject', ['target_id' => $add->id]);

        $before = CulturalActivityRecord::query()->where('event_type', 'org.request.submit')->count();
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->submitter, [
            'naziv' => 'Idem Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);
        $this->assertSame($before + 1, CulturalActivityRecord::query()->where('event_type', 'org.request.submit')->count());
        app(CulturalActivityRecorder::class)->record(
            new \App\Services\CulturalActivity\CulturalActivityRecordInput(
                sourceModule: 'TS-001',
                eventId: 'TS12-ORG-01:'.$request->id,
                eventType: 'org.request.submit',
                occurredAt: $request->created_at,
                actor: \App\Services\CulturalActivity\CulturalActivityActor::user($this->submitter),
                targetType: 'organizer_request',
                targetId: (int) $request->id,
                context: ['request_id' => (int) $request->id],
            )
        );
        $this->assertSame($before + 1, CulturalActivityRecord::query()->where('event_type', 'org.request.submit')->count());
    }

    public function test_audit_failure_does_not_rollback_organizer_approve(): void
    {
        Event::fake([MessageLogged::class]);
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->submitter, [
            'naziv' => 'Failsafe Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);

        $store = $this->createMock(CulturalActivityStore::class);
        $store->method('write')->willThrowException(new \RuntimeException('forced audit'));
        $recorder = new CulturalActivityRecorder($store);
        $this->app->instance(CulturalActivityRecorder::class, $recorder);
        $this->app->instance(
            \App\Services\CulturalActivity\CulturalActivityEmitter::class,
            new \App\Services\CulturalActivity\CulturalActivityEmitter($recorder)
        );

        $organizer = app(OrganizerCreationDecisionService::class)->approve($request->fresh(), $this->editor);
        $this->assertNotNull($organizer->id);
        $this->assertDatabaseHas('cultural_organizers', ['id' => $organizer->id]);
    }

    private function approvedOrganizer(string $name): CulturalOrganizer
    {
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->submitter, [
            'naziv' => $name,
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
