<?php

namespace Tests\Unit;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_kk_admin_is_allowed(): void
    {
        $editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->assertTrue(CulturalPortalAccess::allows($editor));
        $this->assertTrue(CulturalPortalAccess::isKkEditor($editor));
    }

    public function test_active_moderator_of_active_organizer_is_allowed(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $user->id,
            'proposed_moderator_user_id' => $user->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $user->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::create([
            'naziv' => 'Org',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);

        CulturalModeratorAuthorization::create([
            'user_id' => $user->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
            'activated_at' => now(),
        ]);

        $this->assertTrue(CulturalPortalAccess::allows($user));
        $this->assertTrue(CulturalPortalAccess::canModerateOrganizer($user, $organizer));
        $this->assertSame(1, CulturalPortalAccess::activeModeratorCount($organizer));

        $organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);
        $this->assertFalse(CulturalPortalAccess::allows($user->fresh()));
        $this->assertFalse(CulturalPortalAccess::canModerateOrganizer($user, $organizer->fresh()));
    }

    public function test_ordinary_user_without_authorization_is_denied(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->assertFalse(CulturalPortalAccess::allows($user));
    }
}
