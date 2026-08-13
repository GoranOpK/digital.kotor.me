<?php

namespace Tests\Feature;

use App\Mail\CulturalOrganizerCreationApprovedMail;
use App\Mail\CulturalOrganizerCreationRejectedMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * PO-ORG-06 Package 4 — editor gating + Organizer creation outcome emails (FIRST Moderator).
 */
class CulturalPoOrg06Package4DecisionOutcomeMailTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $submitter;

    private User $moderator;

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

        $this->submitter = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email' => 'pkg4.submitter@example.com',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email' => 'pkg4.moderator@example.com',
            'name' => 'Pkg4 Moderator',
        ]);
    }

    public function test_awaiting_request_has_no_decision_cta_and_direct_posts_are_blocked(): void
    {
        Mail::fake();

        $request = $this->makeAwaitingRequest();

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.show', $request))
            ->assertOk()
            ->assertSee('Čeka registraciju Moderatora', false)
            ->assertDontSee('background:#15803d', false)
            ->assertDontSee('background:#b45309', false);

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.approve', $request))
            ->assertRedirect(route('cultural-organizer-creation-requests.show', $request))
            ->assertSessionHasErrors('decision');

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.reject', $request), [
                'decision_note' => 'Ne smije proći',
            ])
            ->assertRedirect(route('cultural-organizer-creation-requests.show', $request))
            ->assertSessionHasErrors('decision');

        $this->assertTrue($request->fresh()->isAwaitingModeratorEligibility());
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);
        Mail::assertNothingSent();
    }

    public function test_approve_without_and_with_note_creates_org_grant_and_sends_approval_mail(): void
    {
        Mail::fake();

        $withoutNote = $this->makeSubmittedRequest('Approve No Note Org');
        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.approve', $withoutNote))
            ->assertRedirect(route('cultural-organizers.index'));

        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_APPROVED, $withoutNote->fresh()->status);
        $this->assertNull($withoutNote->fresh()->decision_note);
        $this->assertDatabaseHas('cultural_organizers', ['naziv' => 'Approve No Note Org']);
        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $this->moderator->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->where('source', CulturalModeratorAuthorization::SOURCE_INITIAL)
                ->exists()
        );

        Mail::assertSent(CulturalOrganizerCreationApprovedMail::class, function (CulturalOrganizerCreationApprovedMail $mail) {
            $this->assertTrue($mail->hasTo($this->moderator->email));
            $this->assertTrue($mail->hasFrom('noreply@kotor.me'));
            $html = $mail->render();
            $this->assertStringContainsString('Approve No Note Org', $html);
            $this->assertStringContainsString('odobren', mb_strtolower($html));
            $this->assertStringContainsString(route('cultural-moderator-dashboard.index'), $html);

            return true;
        });

        Mail::fake();
        $withNote = $this->makeSubmittedRequest('Approve With Note Org');
        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.approve', $withNote), [
                'decision_note' => 'Dobrodošli',
            ])
            ->assertRedirect(route('cultural-organizers.index'));

        $this->assertSame('Dobrodošli', $withNote->fresh()->decision_note);
        Mail::assertSent(CulturalOrganizerCreationApprovedMail::class, 1);
        Mail::assertNotSent(CulturalOrganizerCreationRejectedMail::class);
    }

    public function test_approve_fails_closed_when_moderator_no_longer_eligible(): void
    {
        Mail::fake();

        $request = $this->makeSubmittedRequest('Ineligible Approve Org');
        $this->moderator->forceFill([
            'activation_status' => 'deactivated',
        ])->save();

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.approve', $request))
            ->assertRedirect(route('cultural-organizer-creation-requests.show', $request))
            ->assertSessionHasErrors('decision');

        $this->assertTrue($request->fresh()->isSubmitted());
        $this->assertDatabaseMissing('cultural_organizers', ['naziv' => 'Ineligible Approve Org']);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);
        Mail::assertNothingSent();
    }

    public function test_reject_note_rules_and_rejection_mail_include_decision_note(): void
    {
        Mail::fake();

        $request = $this->makeSubmittedRequest('Reject Org');

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.reject', $request), [
                'decision_note' => '',
            ])
            ->assertSessionHasErrors('decision_note');
        $this->assertTrue($request->fresh()->isSubmitted());

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.reject', $request), [
                'decision_note' => "  \t\n  ",
            ])
            ->assertSessionHasErrors('decision_note');
        $this->assertTrue($request->fresh()->isSubmitted());
        Mail::assertNothingSent();

        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.reject', $request), [
                'decision_note' => 'Nedostaju dokumenta',
            ])
            ->assertRedirect(route('cultural-organizer-creation-requests.index'));

        $fresh = $request->fresh();
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $fresh->status);
        $this->assertSame('Nedostaju dokumenta', $fresh->decision_note);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);

        Mail::assertSent(CulturalOrganizerCreationRejectedMail::class, function (CulturalOrganizerCreationRejectedMail $mail) {
            $this->assertTrue($mail->hasTo($this->moderator->email));
            $this->assertTrue($mail->hasFrom('noreply@kotor.me'));
            $html = $mail->render();
            $this->assertStringContainsString('Reject Org', $html);
            $this->assertStringContainsString('Nedostaju dokumenta', $html);
            $this->assertStringContainsString('Napomena Urednika', $html);

            return true;
        });
    }

    public function test_mail_failure_does_not_rollback_approve_or_reject(): void
    {
        Log::spy();
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP failure approve'));

        $approveRequest = $this->makeSubmittedRequest('Mail Fail Approve Org');
        app(OrganizerCreationDecisionService::class)->approve($approveRequest, $this->editor, null);

        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_APPROVED, $approveRequest->fresh()->status);
        $this->assertDatabaseHas('cultural_organizers', ['naziv' => 'Mail Fail Approve Org']);
        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $this->moderator->id)
                ->exists()
        );
        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => str_contains($message, 'PO-ORG-06 approval outcome mail failed'))
            ->once();

        Log::spy();
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP failure reject'));

        $rejectRequest = $this->makeSubmittedRequest('Mail Fail Reject Org');
        app(OrganizerCreationDecisionService::class)->reject($rejectRequest, $this->editor, 'Razlog ostaje');

        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $rejectRequest->fresh()->status);
        $this->assertSame('Razlog ostaje', $rejectRequest->fresh()->decision_note);
        $this->assertDatabaseMissing('cultural_organizers', ['naziv' => 'Mail Fail Reject Org']);
        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => str_contains($message, 'PO-ORG-06 rejection outcome mail failed'))
            ->once();
    }

    public function test_decided_request_cannot_be_decided_again_and_no_duplicate_outcome_mail(): void
    {
        Mail::fake();

        $request = $this->makeSubmittedRequest('Terminal Org');
        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.approve', $request))
            ->assertRedirect(route('cultural-organizers.index'));

        Mail::assertSent(CulturalOrganizerCreationApprovedMail::class, 1);

        Mail::fake();
        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.approve', $request))
            ->assertSessionHasErrors('decision');

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $request))
            ->post(route('cultural-organizer-creation-requests.reject', $request), [
                'decision_note' => 'Kasno',
            ])
            ->assertSessionHasErrors('decision');

        Mail::assertNothingSent();
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(1, CulturalOrganizer::query()->where('naziv', 'Terminal Org')->count());
    }

    public function test_submitted_show_mentions_rejection_note_is_emailed(): void
    {
        $request = $this->makeSubmittedRequest('UI Note Org');

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.show', $request))
            ->assertOk()
            ->assertSee('Pri odbijanju napomena se šalje predloženom Moderatoru e-mailom', false)
            ->assertSee('background:#15803d', false)
            ->assertSee('background:#b45309', false);
    }

    public function test_package_2_privacy_form_still_has_no_user_enumeration(): void
    {
        $secret = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'name' => 'SecretPkg4UserZZ',
            'email' => 'secret.pkg4.user.zz@example.com',
        ]);

        $this->actingAs($this->submitter)
            ->get(route('cultural-organizer-creation-requests.create'))
            ->assertOk()
            ->assertDontSee('name="proposed_moderator_user_id"', false)
            ->assertDontSee('<select', false)
            ->assertDontSee($secret->name, false)
            ->assertDontSee($secret->email, false)
            ->assertSee('name="proposed_moderator_name"', false);
    }

    private function makeSubmittedRequest(string $naziv): CulturalOrganizerCreationRequest
    {
        return CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);
    }

    private function makeAwaitingRequest(): CulturalOrganizerCreationRequest
    {
        return CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => null,
            'proposed_moderator_name' => 'Waiting Person',
            'proposed_moderator_email' => 'waiting.pkg4@example.com',
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Awaiting Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);
    }
}
