<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MOD-UX-01 — Moderator UX / navigation corrective regression.
 */
class CulturalModeratorUxNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $ordinary;

    private User $moderator;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->ordinary = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->orgA = $this->makeOrganizer('UX Org A');
        $this->orgB = $this->makeOrganizer('UX Org B');
        $this->grant($this->moderator, $this->orgA);
    }

    public function test_ordinary_user_does_not_see_moderator_nav_block(): void
    {
        $html = $this->actingAs($this->ordinary)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-kk-nav-moderator-block="1"', $html);
        $this->assertStringNotContainsString('data-kk-nav="kontrolna-tabla-moderator"', $html);
        $this->assertStringNotContainsString('data-kk-nav="moderiranje"', $html);
        $this->assertStringNotContainsString('>Radna tabla<', $html);
        $this->assertStringNotContainsString('>Mod rad<', $html);
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-moderator-dashboard.index')).'"',
            $html
        );
    }

    public function test_active_moderator_sees_kontrolna_tabla_and_moderiranje_branches(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-kk-nav-moderator-block="1"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="kontrolna-tabla-moderator"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="moderiranje"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="mod-events"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="mod-manifestations"'));
        $this->assertStringContainsString('>Kontrolna tabla<', $html);
        $this->assertStringContainsString('>Moderiranje<', $html);
        $this->assertStringContainsString('Organizator: UX Org A', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-events.index')).'"',
            $html
        );
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-manifestations.index')).'"',
            $html
        );
        $this->assertStringNotContainsString('>Radna tabla<', $html);
        $this->assertStringNotContainsString('>Mod rad<', $html);
        $this->assertStringNotContainsString('>Workspace<', $html);

        // Public KK remains available.
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.events')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.archive')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.manifestations')).'"', $html);
    }

    public function test_multi_org_moderator_sees_promijeni_organizatora_action(): void
    {
        $this->grant($this->moderator, $this->orgB);
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Promijeni organizatora<', $html);
        $this->assertStringContainsString('data-kk-nav="promijeni-organizatora"', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-workspace.index')).'"',
            $html
        );
    }

    public function test_dashboard_and_workspace_use_new_user_facing_labels(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('Kontrolna tabla', false)
            ->assertSee('Organizator: UX Org A', false)
            ->assertSee('Izbor organizatora', false)
            ->assertDontSee('Radna tabla', false)
            ->assertDontSee('Workspace', false);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk()
            ->assertSee('Izbor organizatora', false)
            ->assertSee('Događaji', false)
            ->assertSee('Manifestacije', false)
            ->assertDontSee('Moderatorski workspace', false)
            ->assertDontSee('>Workspace<', false);
    }

    public function test_context_switch_uses_session_and_lands_on_kontrolna_tabla(): void
    {
        $this->grant($this->moderator, $this->orgB);
        CulturalOrganizerContext::clear();

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgB->id])
            ->assertRedirect(route('cultural-moderator-dashboard.index'));

        $this->assertSame($this->orgB->id, CulturalOrganizerContext::get($this->moderator)?->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('Organizator: UX Org B', false);
    }

    public function test_cross_organizer_isolation_remains_forbidden(): void
    {
        $other = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->grant($other, $this->orgB);
        CulturalOrganizerContext::set($other, $this->orgB->id);
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgB->id])
            ->assertForbidden();
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_naziv' => $naziv,
            'proposed_moderator_is_submitter' => false,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
                'activation_status' => 'active',
            ])->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::query()->create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    private function grant(User $user, CulturalOrganizer $organizer): void
    {
        CulturalModeratorAuthorization::query()->create([
            'user_id' => $user->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
            'activated_at' => now(),
        ]);
    }
}
