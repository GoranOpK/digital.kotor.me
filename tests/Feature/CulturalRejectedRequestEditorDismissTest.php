<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalRejectedRequestEditorDismissTest extends TestCase
{
    use RefreshDatabase;

    private User $kkAdmin;

    private User $ordinary;

    private User $moderator;

    private User $submitter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->ordinary = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->submitter = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_kk_admin_can_dismiss_rejected_org_request_without_deleting_row(): void
    {
        $decisionAt = now()->subHour();
        $request = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Odbijeni Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => $decisionAt,
            'decision_note' => 'Nedovoljno dokumentacije',
        ]);

        $response = $this->actingAs($this->kkAdmin)
            ->post(route('cultural-organizer-creation-requests.dismiss', $request));

        $response->assertRedirect(route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']));
        $response->assertSessionHas('status', 'Odbijeni zahtjev je uklonjen iz prikaza.');

        $fresh = $request->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $fresh->status);
        $this->assertSame($this->kkAdmin->id, $fresh->decision_user_id);
        $this->assertSame(
            $decisionAt->format('Y-m-d H:i:s'),
            $fresh->decision_at->format('Y-m-d H:i:s')
        );
        $this->assertSame('Nedovoljno dokumentacije', $fresh->decision_note);
        $this->assertNotNull($fresh->editor_dismissed_at);
        $this->assertSame($this->kkAdmin->id, $fresh->editor_dismissed_by_user_id);

        $this->assertDatabaseHas('users', ['id' => $this->submitter->id]);
        $this->assertDatabaseHas('users', ['id' => $this->moderator->id]);
        $this->assertSame(0, CulturalOrganizer::query()->count());
    }

    public function test_dismissed_org_request_absent_from_unified_list_but_show_reachable(): void
    {
        $visible = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Vidljiv Odbijen',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Razlog A',
        ]);

        $dismissed = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Uklonjen Odbijen',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Razlog B',
            'editor_dismissed_at' => now(),
            'editor_dismissed_by_user_id' => $this->kkAdmin->id,
        ]);

        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Vidljiv Odbijen', $html);
        $this->assertStringNotContainsString('Uklonjen Odbijen', $html);

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizer-creation-requests.show', $dismissed))
            ->assertOk()
            ->assertSee('Uklonjen iz uredničkog prikaza');

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizer-creation-requests.show', $visible))
            ->assertOk()
            ->assertSee('Ukloni');
    }

    public function test_org_non_rejected_and_unauthorized_cannot_dismiss(): void
    {
        $submitted = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Podnesen',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
        ]);

        $this->actingAs($this->kkAdmin)
            ->from(route('cultural-organizer-creation-requests.show', $submitted))
            ->post(route('cultural-organizer-creation-requests.dismiss', $submitted))
            ->assertRedirect(route('cultural-organizer-creation-requests.show', $submitted))
            ->assertSessionHasErrors('decision');

        $this->assertNull($submitted->fresh()->editor_dismissed_at);

        $rejected = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Odbijen Auth',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
        ]);

        $this->actingAs($this->ordinary)
            ->post(route('cultural-organizer-creation-requests.dismiss', $rejected))
            ->assertForbidden();

        $this->actingAs($this->moderator)
            ->post(route('cultural-organizer-creation-requests.dismiss', $rejected))
            ->assertForbidden();

        $this->actingAs($this->submitter)
            ->post(route('cultural-organizer-creation-requests.dismiss', $rejected))
            ->assertForbidden();

        $this->assertNull($rejected->fresh()->editor_dismissed_at);
    }

    public function test_org_repeated_dismiss_is_idempotent(): void
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Dupli Dismiss',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now()->subDay(),
            'decision_note' => 'Note',
        ]);

        $this->actingAs($this->kkAdmin)
            ->post(route('cultural-organizer-creation-requests.dismiss', $request))
            ->assertRedirect(route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']));

        $firstAt = $request->fresh()->editor_dismissed_at;

        $this->actingAs($this->kkAdmin)
            ->post(route('cultural-organizer-creation-requests.dismiss', $request))
            ->assertRedirect(route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']))
            ->assertSessionHas('status', 'Odbijeni zahtjev je uklonjen iz prikaza.');

        $this->assertTrue($request->fresh()->editor_dismissed_at->equalTo($firstAt));
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $request->fresh()->status);
    }

    public function test_dismissed_rejected_org_does_not_block_new_submission(): void
    {
        CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Stari Odbijen',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
            'editor_dismissed_at' => now(),
            'editor_dismissed_by_user_id' => $this->kkAdmin->id,
        ]);

        $this->actingAs($this->submitter)
            ->post(route('cultural-organizer-creation-requests.store'), [
                'naziv' => 'Novi Org Nakon Dismiss',
                'proposed_moderator_name' => $this->moderator->name,
                'proposed_moderator_email' => $this->moderator->email,
            ])
            ->assertRedirect(route('cultural-organizer-creation-requests.create'));

        $this->assertDatabaseHas('cultural_organizer_creation_requests', [
            'proposed_naziv' => 'Novi Org Nakon Dismiss',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_kk_admin_can_dismiss_rejected_mod_add_and_remove(): void
    {
        $organizer = $this->createActiveOrganizer();

        $add = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->moderator->id,
            'proposed_moderator_name' => $this->ordinary->name,
            'proposed_moderator_email' => $this->ordinary->email,
            'target_user_id' => $this->ordinary->id,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now()->subHour(),
            'decision_note' => 'ADD reject',
        ]);

        $remove = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->moderator->id,
            'target_user_id' => $this->moderator->id,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now()->subHour(),
            'decision_note' => 'REMOVE reject',
        ]);

        $activeGrant = CulturalModeratorAuthorization::query()->where('organizer_id', $organizer->id)->firstOrFail();

        $this->actingAs($this->kkAdmin)
            ->post(route('cultural-moderator-requests.dismiss', $add))
            ->assertRedirect(route('cultural-editorial-requests.index', ['sekcija' => 'moderatori']))
            ->assertSessionHas('status', 'Odbijeni zahtjev je uklonjen iz prikaza.');

        $this->actingAs($this->kkAdmin)
            ->post(route('cultural-moderator-requests.dismiss', $remove))
            ->assertRedirect(route('cultural-editorial-requests.index', ['sekcija' => 'moderatori']));

        $this->assertNotNull($add->fresh());
        $this->assertSame(CulturalModeratorRequest::STATUS_REJECTED, $add->fresh()->status);
        $this->assertSame('ADD reject', $add->fresh()->decision_note);
        $this->assertNotNull($add->fresh()->editor_dismissed_at);

        $this->assertNotNull($remove->fresh());
        $this->assertSame(CulturalModeratorRequest::STATUS_REJECTED, $remove->fresh()->status);
        $this->assertNotNull($remove->fresh()->editor_dismissed_at);

        $this->assertSame(CulturalModeratorAuthorization::STATUS_ACTIVE, $activeGrant->fresh()->status);
        $this->assertSame(0, CulturalModeratorAuthorization::query()
            ->where('user_id', $this->ordinary->id)
            ->where('organizer_id', $organizer->id)
            ->count());
    }

    public function test_dismissed_mod_requests_absent_from_unified_list_show_reachable(): void
    {
        $organizer = $this->createActiveOrganizer();

        $visible = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->moderator->id,
            'proposed_moderator_email' => 'visible@example.com',
            'proposed_moderator_name' => 'Visible',
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
        ]);

        $dismissed = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->moderator->id,
            'proposed_moderator_email' => 'hidden@example.com',
            'proposed_moderator_name' => 'Hidden Mod',
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
            'editor_dismissed_at' => now(),
            'editor_dismissed_by_user_id' => $this->kkAdmin->id,
        ]);

        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index', ['sekcija' => 'moderatori']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-requests.show', $visible)).'"',
            $html
        );
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-moderator-requests.show', $dismissed)).'"',
            $html
        );

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-moderator-requests.show', $dismissed))
            ->assertOk()
            ->assertSee('Uklonjen iz uredničkog prikaza');
    }

    public function test_mod_non_rejected_and_unauthorized_cannot_dismiss(): void
    {
        $organizer = $this->createActiveOrganizer();

        $submitted = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
            'submitter_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->ordinary->email,
            'proposed_moderator_name' => $this->ordinary->name,
            'target_user_id' => $this->ordinary->id,
        ]);

        $this->actingAs($this->kkAdmin)
            ->from(route('cultural-moderator-requests.show', $submitted))
            ->post(route('cultural-moderator-requests.dismiss', $submitted))
            ->assertRedirect(route('cultural-moderator-requests.show', $submitted))
            ->assertSessionHasErrors('decision');

        $rejected = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->moderator->id,
            'target_user_id' => $this->moderator->id,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
        ]);

        $this->actingAs($this->ordinary)
            ->post(route('cultural-moderator-requests.dismiss', $rejected))
            ->assertForbidden();

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-requests.dismiss', $rejected))
            ->assertForbidden();

        $this->assertNull($rejected->fresh()->editor_dismissed_at);
    }

    public function test_dismissed_rejected_mod_add_does_not_block_new_add(): void
    {
        $organizer = $this->createActiveOrganizer();

        CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->ordinary->email,
            'proposed_moderator_name' => $this->ordinary->name,
            'target_user_id' => $this->ordinary->id,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
            'editor_dismissed_at' => now(),
            'editor_dismissed_by_user_id' => $this->kkAdmin->id,
        ]);

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-requests.store', $organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => $this->ordinary->name,
                'proposed_moderator_email' => $this->ordinary->email,
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'));

        $this->assertDatabaseHas('cultural_moderator_requests', [
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'proposed_moderator_email' => strtolower($this->ordinary->email),
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
            'editor_dismissed_at' => null,
        ]);
    }

    private function createActiveOrganizer(): CulturalOrganizer
    {
        $creation = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->kkAdmin->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Org Dismiss',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => 'Org Dismiss',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $creation->id,
        ]);

        CulturalModeratorAuthorization::query()->create([
            'user_id' => $this->moderator->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
            'activated_at' => now(),
        ]);

        return $organizer;
    }
}
