<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalOrganizerEditActiveModeratorsDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $kkAdmin;

    private User $ordinary;

    private User $moderatorA;

    private User $moderatorB;

    private User $foreignModerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Urednik Test',
        ]);

        $this->ordinary = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Obicni Korisnik',
        ]);

        $this->moderatorA = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Ana Moderator',
            'email' => 'ana.moderator@example.com',
        ]);

        $this->moderatorB = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Boris Moderator',
            'email' => 'boris.moderator@example.com',
        ]);

        $this->foreignModerator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Strani Moderator',
            'email' => 'strani.moderator@example.com',
        ]);
    }

    public function test_edit_form_shows_single_active_moderator_name_and_email(): void
    {
        $organizer = $this->createOrganizerWithModerators([$this->moderatorA]);

        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizers.edit', $organizer))
            ->assertOk()
            ->assertSee('Osnovni podaci', false)
            ->assertSee('Moderatori Organizatora', false)
            ->assertSee('Ana Moderator', false)
            ->assertSee('ana.moderator@example.com', false)
            ->assertSee('Aktivan', false)
            ->assertSee('Status Organizatora', false)
            ->getContent();

        $this->assertStringContainsString('data-section="moderatori-organizatora"', $html);
        $this->assertStringContainsString('data-moderator-name', $html);
        $this->assertStringContainsString('data-moderator-email', $html);
        $this->assertStringContainsString('data-moderator-status', $html);
        $this->assertStringNotContainsString('Dodaj Moderatora', $html);
        $this->assertStringNotContainsString('Ukloni Moderatora', $html);
        $this->assertStringNotContainsString(route('cultural-moderator-requests.store', $organizer), $html);
    }

    public function test_edit_form_shows_all_active_moderators(): void
    {
        $organizer = $this->createOrganizerWithModerators([$this->moderatorA, $this->moderatorB]);

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizers.edit', $organizer))
            ->assertOk()
            ->assertSee('Ana Moderator', false)
            ->assertSee('ana.moderator@example.com', false)
            ->assertSee('Boris Moderator', false)
            ->assertSee('boris.moderator@example.com', false);
    }

    public function test_removed_moderator_is_not_listed_and_foreign_organizer_is_isolated(): void
    {
        $organizer = $this->createOrganizerWithModerators([$this->moderatorA]);

        CulturalModeratorAuthorization::query()->create([
            'user_id' => $this->moderatorB->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
            'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
            'activated_at' => now()->subDay(),
            'removed_at' => now(),
        ]);

        $other = $this->createOrganizerWithModerators([$this->foreignModerator], 'Drugi Org');

        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizers.edit', $organizer))
            ->assertOk()
            ->assertSee('Ana Moderator', false)
            ->assertDontSee('Boris Moderator', false)
            ->assertDontSee('boris.moderator@example.com', false)
            ->assertDontSee('Strani Moderator', false)
            ->assertDontSee('strani.moderator@example.com', false)
            ->getContent();

        $this->assertStringContainsString('Ana Moderator', $html);
        $this->assertDatabaseHas('cultural_organizers', ['id' => $other->id]);
    }

    public function test_zero_active_moderators_shows_invariant_warning_not_neutral_empty_copy(): void
    {
        $creation = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->kkAdmin->id,
            'proposed_moderator_user_id' => $this->moderatorA->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Org Bez Mod',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => 'Org Bez Mod',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $creation->id,
        ]);

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-organizers.edit', $organizer))
            ->assertOk()
            ->assertSee('Upozorenje: Organizator nema aktivnog Moderatora.', false)
            ->assertDontSee('Nema aktivnih moderatora.', false);
    }

    public function test_ordinary_user_and_moderator_cannot_access_organizer_edit(): void
    {
        $organizer = $this->createOrganizerWithModerators([$this->moderatorA]);

        $this->actingAs($this->ordinary)
            ->get(route('cultural-organizers.edit', $organizer))
            ->assertForbidden();

        $this->actingAs($this->moderatorA)
            ->get(route('cultural-organizers.edit', $organizer))
            ->assertForbidden();
    }

    /**
     * @param  list<User>  $activeModerators
     */
    private function createOrganizerWithModerators(array $activeModerators, string $naziv = 'Org Edit'): CulturalOrganizer
    {
        $first = $activeModerators[0];

        $creation = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->kkAdmin->id,
            'proposed_moderator_user_id' => $first->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $creation->id,
        ]);

        foreach ($activeModerators as $index => $user) {
            CulturalModeratorAuthorization::query()->create([
                'user_id' => $user->id,
                'organizer_id' => $organizer->id,
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => $index === 0
                    ? CulturalModeratorAuthorization::SOURCE_INITIAL
                    : CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
            ]);
        }

        return $organizer;
    }
}
