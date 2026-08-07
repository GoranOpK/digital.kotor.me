<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TS-001 Korak 1 — Organizator / Moderator / zahtjevi (PO-ORG-01–04).
 */
class CulturalOrganizerDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private User $proposedModerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->proposedModerator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
    }

    public function test_user_can_submit_creation_request_without_creating_organizer(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Kulturni Centar',
            'opis' => 'Opis',
            'contact_email' => 'info@example.com',
            'contact_phone' => '067000000',
            'website' => 'https://example.com',
            'proposed_moderator_user_id' => $this->proposedModerator->id,
        ]);

        $response->assertRedirect(route('cultural-organizer-creation-requests.create'));
        $this->assertDatabaseCount('cultural_organizer_creation_requests', 1);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);

        $request = CulturalOrganizerCreationRequest::query()->first();
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_SUBMITTED, $request->status);
        $this->assertFalse($request->proposed_moderator_is_submitter);
    }

    public function test_proposed_moderator_must_be_active_existing_user(): void
    {
        $inactive = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'deactivated',
        ]);

        $this->actingAs($this->regularUser)
            ->from(route('cultural-organizer-creation-requests.create'))
            ->post(route('cultural-organizer-creation-requests.store'), [
                'naziv' => 'Org',
                'proposed_moderator_user_id' => $inactive->id,
            ])
            ->assertSessionHasErrors('proposed_moderator_user_id');

        $this->actingAs($this->regularUser)
            ->from(route('cultural-organizer-creation-requests.create'))
            ->post(route('cultural-organizer-creation-requests.store'), [
                'naziv' => 'Org',
                'proposed_moderator_user_id' => 999999,
            ])
            ->assertSessionHasErrors('proposed_moderator_user_id');

        $this->assertDatabaseCount('cultural_organizers', 0);
    }

    public function test_editor_approve_creates_organizer_and_initial_moderator_atomically(): void
    {
        $request = $this->makeSubmittedCreationRequest();

        $response = $this->actingAs($this->editor)->post(
            route('cultural-organizer-creation-requests.approve', $request),
            ['decision_note' => 'OK']
        );

        $response->assertRedirect(route('cultural-organizers.index'));

        $this->assertDatabaseCount('cultural_organizers', 1);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 1);

        $organizer = CulturalOrganizer::query()->first();
        $this->assertSame('Kulturni Centar', $organizer->naziv);
        $this->assertSame(CulturalOrganizer::STATUS_ACTIVE, $organizer->status);
        $this->assertSame($request->id, $organizer->approved_creation_request_id);

        $auth = CulturalModeratorAuthorization::query()->first();
        $this->assertSame($this->proposedModerator->id, $auth->user_id);
        $this->assertSame(CulturalModeratorAuthorization::STATUS_ACTIVE, $auth->status);
        $this->assertSame(CulturalModeratorAuthorization::SOURCE_INITIAL, $auth->source);

        $request->refresh();
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($this->editor->id, $request->decision_user_id);
        $this->assertNotNull($request->decision_at);
        $this->assertSame('OK', $request->decision_note);
    }

    public function test_approve_is_atomic_on_failure(): void
    {
        $request = $this->makeSubmittedCreationRequest();

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into `cultural_moderator_authorizations`')) {
                throw new \RuntimeException('forced failure');
            }
        });

        try {
            app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);
            $this->fail('Expected exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure', $e->getMessage());
        }

        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);
        $this->assertSame(
            CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
            $request->fresh()->status
        );
    }

    public function test_reject_does_not_create_organizer(): void
    {
        $request = $this->makeSubmittedCreationRequest();

        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.reject', $request), [
                'decision_note' => 'Nedovoljno',
            ])
            ->assertRedirect(route('cultural-organizer-creation-requests.index'));

        $this->assertDatabaseCount('cultural_organizers', 0);
        $request->refresh();
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $request->status);
        $this->assertSame($this->editor->id, $request->decision_user_id);
    }

    public function test_ordinary_user_cannot_approve_or_reject(): void
    {
        $request = $this->makeSubmittedCreationRequest();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-organizer-creation-requests.approve', $request))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-organizer-creation-requests.reject', $request))
            ->assertForbidden();

        $this->assertDatabaseCount('cultural_organizers', 0);
    }

    public function test_editor_can_list_update_and_deactivate_organizer(): void
    {
        $organizer = $this->approveOrganizer();

        $this->actingAs($this->editor)
            ->get(route('cultural-organizers.index'))
            ->assertOk()
            ->assertSee('Kulturni Centar');

        $this->actingAs($this->editor)
            ->put(route('cultural-organizers.update', $organizer), [
                'naziv' => 'Novi Naziv',
                'opis' => 'x',
                'contact_email' => null,
                'contact_phone' => null,
                'website' => null,
            ])
            ->assertRedirect(route('cultural-organizers.index'));

        $this->assertSame('Novi Naziv', $organizer->fresh()->naziv);

        $this->actingAs($this->editor)
            ->post(route('cultural-organizers.deactivate', $organizer))
            ->assertRedirect(route('cultural-organizers.index'));

        $this->assertTrue($organizer->fresh()->isDeactivated());
    }

    public function test_deactivated_organizer_does_not_grant_portal_access(): void
    {
        $organizer = $this->approveOrganizer();

        $this->actingAs($this->proposedModerator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk();

        $organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->actingAs($this->proposedModerator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertForbidden();
    }

    public function test_active_moderator_can_submit_add_and_remove_requests(): void
    {
        $organizer = $this->approveOrganizer();
        $newMod = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->actingAs($this->proposedModerator)
            ->post(route('cultural-moderator-requests.store', $organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'target_user_id' => $newMod->id,
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'));

        $addRequest = CulturalModeratorRequest::query()->where('type', 'add')->first();
        $this->assertNotNull($addRequest);

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.approve', $addRequest))
            ->assertRedirect(route('cultural-moderator-requests.index'));

        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $newMod->id)
                ->where('organizer_id', $organizer->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists()
        );

        $this->actingAs($this->proposedModerator)
            ->post(route('cultural-moderator-requests.store', $organizer), [
                'type' => CulturalModeratorRequest::TYPE_REMOVE,
                'target_user_id' => $newMod->id,
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'));

        $removeRequest = CulturalModeratorRequest::query()->where('type', 'remove')->first();

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.approve', $removeRequest))
            ->assertRedirect(route('cultural-moderator-requests.index'));

        $this->assertSame(
            CulturalModeratorAuthorization::STATUS_REMOVED,
            CulturalModeratorAuthorization::query()
                ->where('user_id', $newMod->id)
                ->where('organizer_id', $organizer->id)
                ->value('status')
        );
    }

    public function test_duplicate_active_authorization_is_rejected_on_add_approve(): void
    {
        $organizer = $this->approveOrganizer();

        $dupRequest = CulturalModeratorRequest::create([
            'organizer_id' => $organizer->id,
            'submitter_user_id' => $this->proposedModerator->id,
            'target_user_id' => $this->proposedModerator->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-moderator-requests.show', $dupRequest))
            ->post(route('cultural-moderator-requests.approve', $dupRequest))
            ->assertRedirect(route('cultural-moderator-requests.show', $dupRequest))
            ->assertSessionHasErrors('decision');

        $this->assertSame(1, CulturalModeratorAuthorization::query()->where('organizer_id', $organizer->id)->count());
    }

    public function test_last_active_moderator_cannot_be_removed(): void
    {
        $organizer = $this->approveOrganizer();

        $removeRequest = CulturalModeratorRequest::create([
            'organizer_id' => $organizer->id,
            'submitter_user_id' => $this->proposedModerator->id,
            'target_user_id' => $this->proposedModerator->id,
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-moderator-requests.show', $removeRequest))
            ->post(route('cultural-moderator-requests.approve', $removeRequest))
            ->assertSessionHasErrors('decision');

        $this->assertSame(
            CulturalModeratorAuthorization::STATUS_ACTIVE,
            CulturalModeratorAuthorization::query()->first()->status
        );
    }

    public function test_user_without_authorization_cannot_submit_moderator_request(): void
    {
        $organizer = $this->approveOrganizer();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-moderator-requests.store', $organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'target_user_id' => $this->regularUser->id,
            ])
            ->assertForbidden();
    }

    public function test_portal_access_rules(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk();

        $this->actingAs($this->regularUser)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertForbidden();

        $this->approveOrganizer();

        $this->actingAs($this->proposedModerator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk();

        $konkursAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        // RestrictRoleModuleAccess preusmjerava konkurs_admin van KK (postojeće ponašanje).
        $this->actingAs($konkursAdmin)
            ->get(route('cultural-organizers.index'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_unique_user_organizer_authorization_constraint(): void
    {
        $organizer = $this->approveOrganizer();

        $this->expectException(\Illuminate\Database\QueryException::class);

        CulturalModeratorAuthorization::create([
            'user_id' => $this->proposedModerator->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
            'activated_at' => now(),
        ]);
    }

    /**
     * Moderator Organizatora A ne smije izvršavati moderatorske akcije nad Organizatorom B.
     */
    public function test_moderator_of_organizer_a_cannot_act_on_organizer_b(): void
    {
        $organizerA = $this->approveOrganizer();

        $moderatorOfB = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $requestB = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->regularUser->id,
            'proposed_moderator_user_id' => $moderatorOfB->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Organizator B',
            'proposed_opis' => null,
            'proposed_contact_email' => null,
            'proposed_contact_phone' => null,
            'proposed_website' => null,
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);
        app(OrganizerCreationDecisionService::class)->approve($requestB, $this->editor);
        $organizerB = CulturalOrganizer::query()->where('naziv', 'Organizator B')->firstOrFail();

        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $this->proposedModerator->id)
                ->where('organizer_id', $organizerA->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists()
        );
        $this->assertFalse(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $this->proposedModerator->id)
                ->where('organizer_id', $organizerB->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists()
        );

        // Portal pristup (ima ovlašćenje nad A), ali ne i pravo nad B.
        $this->actingAs($this->proposedModerator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk();

        $this->actingAs($this->proposedModerator)
            ->get(route('cultural-moderator-requests.create', $organizerB))
            ->assertForbidden();

        $requestCountBefore = CulturalModeratorRequest::query()->count();

        $this->actingAs($this->proposedModerator)
            ->post(route('cultural-moderator-requests.store', $organizerB), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'target_user_id' => $this->regularUser->id,
            ])
            ->assertForbidden();

        $this->assertSame($requestCountBefore, CulturalModeratorRequest::query()->count());
        $this->assertDatabaseMissing('cultural_moderator_requests', [
            'organizer_id' => $organizerB->id,
            'submitter_user_id' => $this->proposedModerator->id,
        ]);
    }

    private function makeSubmittedCreationRequest(): CulturalOrganizerCreationRequest
    {
        return CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->regularUser->id,
            'proposed_moderator_user_id' => $this->proposedModerator->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Kulturni Centar',
            'proposed_opis' => 'Opis',
            'proposed_contact_email' => 'info@example.com',
            'proposed_contact_phone' => null,
            'proposed_website' => null,
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);
    }

    private function approveOrganizer(): CulturalOrganizer
    {
        $request = $this->makeSubmittedCreationRequest();
        app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);

        return CulturalOrganizer::query()->where('naziv', 'Kulturni Centar')->firstOrFail();
    }
}
