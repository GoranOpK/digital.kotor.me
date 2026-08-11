<?php

namespace Tests\Feature;

use App\Listeners\ResolveModeratorEligibilityOnVerified;
use App\Mail\CulturalOrganizerModeratorInvitationMail;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalOrganizer\ModeratorEligibilityResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * PO-ORG-06 Package 3 — Moderator eligibility resolver (Verified + active catch-up).
 */
class CulturalPoOrg06Package3EligibilityResolverTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $submitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->submitter = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'org.submitter@example.com',
        ]);
    }

    public function test_verified_event_binds_active_user_waiting_requests_to_submitted(): void
    {
        Mail::fake();

        $pendingUser = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'Pending.Moderator@Example.com',
            'name' => 'Pending Moderator',
        ]);

        $request = $this->makeAwaitingRequest('Org A', 'pending.moderator@example.com', 'Any Name');
        $this->assertTrue($request->isAwaitingModeratorEligibility());
        $this->assertNull($request->proposed_moderator_user_id);

        $pendingUser->forceFill(['email_verified_at' => now()])->save();
        event(new Verified($pendingUser));

        $fresh = $request->fresh();
        $this->assertTrue($fresh->isSubmitted());
        $this->assertSame($pendingUser->id, $fresh->proposed_moderator_user_id);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);
        Mail::assertNotSent(CulturalOrganizerModeratorInvitationMail::class);
    }

    public function test_verified_inactive_user_does_not_resolve_until_activation_catch_up(): void
    {
        Mail::fake();

        $inactive = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'deactivated',
            'email' => 'inactive.moderator@example.com',
            'email_verified_at' => now(),
        ]);

        $request = $this->makeAwaitingRequest('Inactive Org', 'inactive.moderator@example.com', 'Inactive Name');

        event(new Verified($inactive));
        $this->assertTrue($request->fresh()->isAwaitingModeratorEligibility());
        $this->assertNull($request->fresh()->proposed_moderator_user_id);

        $inactive->activation_status = 'active';
        $inactive->save();
        $resolved = app(ModeratorEligibilityResolver::class)->resolveForUser($inactive);

        $this->assertSame(1, $resolved);
        $this->assertTrue($request->fresh()->isSubmitted());
        $this->assertSame($inactive->id, $request->fresh()->proposed_moderator_user_id);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);
        Mail::assertNothingSent();
    }

    public function test_admin_activate_user_catch_up_resolves_waiting_request(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $inactive = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'deactivated',
            'email' => 'admin.activate.mod@example.com',
            'email_verified_at' => now(),
        ]);

        $request = $this->makeAwaitingRequest('Admin Activate Org', 'admin.activate.mod@example.com', 'Catch Up');

        $this->actingAs($admin)
            ->post(route('admin.users.activate', $inactive))
            ->assertRedirect();

        $this->assertSame('active', $inactive->fresh()->activation_status);
        $this->assertTrue($request->fresh()->isSubmitted());
        $this->assertSame($inactive->id, $request->fresh()->proposed_moderator_user_id);
    }

    public function test_unrelated_user_verification_leaves_other_email_awaiting(): void
    {
        $waiting = $this->makeAwaitingRequest('Keep Waiting', 'keep.waiting@example.com', 'Keep');
        $other = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'other.person@example.com',
        ]);

        $other->forceFill(['email_verified_at' => now()])->save();
        event(new Verified($other));

        $this->assertTrue($waiting->fresh()->isAwaitingModeratorEligibility());
        $this->assertNull($waiting->fresh()->proposed_moderator_user_id);
    }

    public function test_multiple_awaiting_requests_for_same_email_all_resolve(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'multi.mod@example.com',
        ]);

        $a = $this->makeAwaitingRequest('Org Multi A', 'multi.mod@example.com', 'Name A');
        $b = $this->makeAwaitingRequest('Org Multi B', 'multi.mod@example.com', 'Name B');
        $c = $this->makeAwaitingRequest('Org Multi C', 'multi.mod@example.com', 'Name C');
        $other = $this->makeAwaitingRequest('Org Other', 'other.multi@example.com', 'Other');

        $user->forceFill(['email_verified_at' => now()])->save();
        $resolved = app(ModeratorEligibilityResolver::class)->resolveForUser($user);

        $this->assertSame(3, $resolved);
        foreach ([$a, $b, $c] as $request) {
            $fresh = $request->fresh();
            $this->assertTrue($fresh->isSubmitted());
            $this->assertSame($user->id, $fresh->proposed_moderator_user_id);
        }

        $this->assertTrue($other->fresh()->isAwaitingModeratorEligibility());
        $this->assertNull($other->fresh()->proposed_moderator_user_id);
        $this->assertDatabaseCount('cultural_organizers', 0);
        Mail::assertNothingSent();
    }

    public function test_resolver_is_idempotent_and_ignores_terminal_statuses(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'idempotent.mod@example.com',
            'email_verified_at' => now(),
        ]);

        $awaiting = $this->makeAwaitingRequest('Idempotent Org', 'idempotent.mod@example.com', 'Idem');
        $submitted = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $user->id,
            'proposed_moderator_name' => 'Already Bound',
            'proposed_moderator_email' => 'idempotent.mod@example.com',
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Already Submitted',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);
        $approved = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $user->id,
            'proposed_moderator_name' => 'Approved',
            'proposed_moderator_email' => 'idempotent.mod@example.com',
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Already Approved',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
        ]);
        $rejected = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $user->id,
            'proposed_moderator_name' => 'Rejected',
            'proposed_moderator_email' => 'idempotent.mod@example.com',
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Already Rejected',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'decision_note' => 'No',
        ]);

        $resolver = app(ModeratorEligibilityResolver::class);
        $this->assertSame(1, $resolver->resolveForUser($user));
        $this->assertSame(0, $resolver->resolveForUser($user));

        event(new Verified($user));
        event(new Verified($user));

        $this->assertTrue($awaiting->fresh()->isSubmitted());
        $this->assertSame($user->id, $awaiting->fresh()->proposed_moderator_user_id);
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_SUBMITTED, $submitted->fresh()->status);
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_APPROVED, $approved->fresh()->status);
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $rejected->fresh()->status);
    }

    public function test_email_case_differences_match_normalized_storage(): void
    {
        $user = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'Case.Match@Example.COM',
        ]);

        $request = $this->makeAwaitingRequest('Case Org', 'case.match@example.com', 'Case Name');

        $user->forceFill(['email_verified_at' => now()])->save();
        app(ModeratorEligibilityResolver::class)->resolveForUser($user);

        $this->assertTrue($request->fresh()->isSubmitted());
        $this->assertSame($user->id, $request->fresh()->proposed_moderator_user_id);
    }

    public function test_editor_list_and_decision_cta_appear_after_resolve(): void
    {
        $user = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'editor.visible@example.com',
        ]);

        $request = $this->makeAwaitingRequest('Editor Visible Org', 'editor.visible@example.com', 'Visible');

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.index'))
            ->assertOk()
            ->assertDontSee('Editor Visible Org', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.show', $request))
            ->assertOk()
            ->assertDontSee('background:#15803d', false);

        $user->forceFill(['email_verified_at' => now()])->save();
        event(new Verified($user));

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.index'))
            ->assertOk()
            ->assertSee('Editor Visible Org', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.show', $request->fresh()))
            ->assertOk()
            ->assertSee('action="'.route('cultural-organizer-creation-requests.approve', $request).'"', false)
            ->assertSee('background:#15803d', false);
    }

    public function test_listener_is_registered_for_verified_event(): void
    {
        Event::fake([Verified::class]);

        Event::assertListening(
            Verified::class,
            ResolveModeratorEligibilityOnVerified::class
        );
    }

    public function test_does_not_resolve_rows_that_already_have_user_id(): void
    {
        $boundUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'already.bound@example.com',
        ]);

        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $boundUser->id,
            'proposed_moderator_name' => 'Bound',
            'proposed_moderator_email' => 'already.bound@example.com',
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Bound Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);

        $resolved = app(ModeratorEligibilityResolver::class)->resolveForUser($boundUser);
        $this->assertSame(0, $resolved);
        $this->assertSame($boundUser->id, $request->fresh()->proposed_moderator_user_id);
        $this->assertTrue($request->fresh()->isAwaitingModeratorEligibility());
    }

    private function makeAwaitingRequest(string $naziv, string $email, string $name): CulturalOrganizerCreationRequest
    {
        return CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => null,
            'proposed_moderator_name' => $name,
            'proposed_moderator_email' => $email,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);
    }
}
