<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalEditorialRequestsUnifiedTest extends TestCase
{
    use RefreshDatabase;

    private User $kkAdmin;

    private User $ordinary;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->ordinary = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_kk_admin_can_open_unified_zahtjevi_with_both_sections(): void
    {
        $response = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index'))
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('>Zahtjevi<', $html);
        $this->assertStringContainsString('Zahtjevi za organizatore', $html);
        $this->assertStringContainsString('Zahtjevi za moderatore', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-editorial-requests.index', ['sekcija' => 'moderatori'])).'"',
            $html
        );
    }

    public function test_moderator_section_is_reachable_and_shows_existing_detail_links(): void
    {
        $creation = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->kkAdmin->id,
            'proposed_moderator_user_id' => $this->kkAdmin->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org Zahtjevi',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => 'Org Zahtjevi',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $creation->id,
        ]);

        $modRequest = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_email' => $this->moderator->email,
            'target_user_id' => $this->moderator->id,
        ]);

        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index', ['sekcija' => 'moderatori']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Zahtjevi za moderatore', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-requests.show', $modRequest)).'"',
            $html
        );
    }

    public function test_organizer_section_links_to_existing_org_decision_show(): void
    {
        $orgRequest = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Novi Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
        ]);

        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Novi Org', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-organizer-creation-requests.show', $orgRequest)).'"',
            $html
        );
    }

    public function test_ordinary_user_cannot_open_unified_zahtjevi(): void
    {
        $this->actingAs($this->ordinary)
            ->get(route('cultural-editorial-requests.index'))
            ->assertForbidden();
    }

    public function test_moderator_without_kk_admin_cannot_open_unified_zahtjevi(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('cultural-editorial-requests.index'))
            ->assertForbidden();
    }

    public function test_legacy_org_and_mod_index_routes_remain_reachable_for_kk_admin(): void
    {
        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizer-creation-requests.index'))
            ->assertOk();

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-moderator-requests.index'))
            ->assertOk();
    }

    public function test_dismissed_rejected_requests_absent_from_default_unified_lists(): void
    {
        $orgVisible = CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Org Visible Rejected',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
        ]);

        CulturalOrganizerCreationRequest::query()->create([
            'proposed_naziv' => 'Org Dismissed Rejected',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
            'editor_dismissed_at' => now(),
            'editor_dismissed_by_user_id' => $this->kkAdmin->id,
        ]);

        $creation = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->kkAdmin->id,
            'proposed_moderator_user_id' => $this->kkAdmin->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org Zahtjevi Base',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => 'Org Zahtjevi Base',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $creation->id,
        ]);

        $modVisible = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_email' => 'visible-mod@example.com',
            'proposed_moderator_name' => 'Visible Mod',
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
        ]);

        $modDismissed = CulturalModeratorRequest::query()->create([
            'organizer_id' => $organizer->id,
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'status' => CulturalModeratorRequest::STATUS_REJECTED,
            'submitter_user_id' => $this->ordinary->id,
            'target_user_id' => $this->moderator->id,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Note',
            'editor_dismissed_at' => now(),
            'editor_dismissed_by_user_id' => $this->kkAdmin->id,
        ]);

        $orgHtml = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Org Visible Rejected', $orgHtml);
        $this->assertStringNotContainsString('Org Dismissed Rejected', $orgHtml);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-organizer-creation-requests.show', $orgVisible)).'"',
            $orgHtml
        );

        $modHtml = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-editorial-requests.index', ['sekcija' => 'moderatori']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-requests.show', $modVisible)).'"',
            $modHtml
        );
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-moderator-requests.show', $modDismissed)).'"',
            $modHtml
        );
    }
}
