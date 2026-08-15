<?php

namespace Tests\Feature;

use App\Mail\CulturalModeratorAddApprovedMail;
use App\Mail\CulturalModeratorAddInvitationMail;
use App\Mail\CulturalModeratorAddRejectedMail;
use App\Mail\CulturalModeratorRemoveApprovedMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalOrganizer\ModeratorEligibilityResolver;
use App\Services\CulturalOrganizer\ModeratorRequestDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * PO-ORG-06 Package 5 — subsequent Moderator ADD/REMOVE privacy-safe flow.
 */
class CulturalPoOrg06Package5SubsequentModeratorFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $submitter;

    private User $initialModerator;

    private CulturalOrganizer $organizer;

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
            'email' => 'pkg5.submitter@example.com',
        ]);

        $this->initialModerator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email' => 'pkg5.initial.mod@example.com',
            'name' => 'Initial Moderator',
        ]);

        $creation = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->initialModerator->id,
            'proposed_moderator_name' => $this->initialModerator->name,
            'proposed_moderator_email' => $this->initialModerator->email,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Pkg5 Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);

        $this->organizer = app(OrganizerCreationDecisionService::class)->approve($creation, $this->editor);
    }

    public function test_add_form_is_privacy_safe_and_remove_lists_only_active_org_moderators(): void
    {
        $otherOrgMod = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'name' => 'OtherOrgUniqueModZZ',
            'email' => 'other.org.unique.zz@example.com',
        ]);
        $ordinary = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'name' => 'OrdinaryUniqueUserYY',
            'email' => 'ordinary.unique.yy@example.com',
        ]);

        $response = $this->actingAs($this->initialModerator)
            ->get(route('cultural-moderator-requests.create', $this->organizer));

        $response->assertOk();
        $response->assertSee('name="proposed_moderator_name"', false);
        $response->assertSee('name="proposed_moderator_email"', false);
        $response->assertDontSee('candidateUsers', false);
        $response->assertDontSee($otherOrgMod->email, false);
        $response->assertDontSee($ordinary->email, false);
        $response->assertSee($this->initialModerator->name, false);
        $response->assertSee('background:#b91c1c', false);
        $response->assertDontSee('bg-red-800 text-white', false);
    }

    public function test_add_eligible_and_not_eligible_paths_with_neutral_flash(): void
    {
        Mail::fake();

        $eligible = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'eligible.add@example.com',
            'name' => 'Eligible Add',
        ]);

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Any Name',
                'proposed_moderator_email' => '  Eligible.Add@Example.com ',
                'target_user_id' => 999999,
            ])
            ->assertSessionHasErrors('target_user_id');

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Any Name',
                'proposed_moderator_email' => '  Eligible.Add@Example.com ',
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'))
            ->assertSessionHas('status', OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE);

        $eligibleRequest = CulturalModeratorRequest::query()->where('proposed_moderator_email', 'eligible.add@example.com')->firstOrFail();
        $this->assertTrue($eligibleRequest->isSubmitted());
        $this->assertSame($eligible->id, $eligibleRequest->target_user_id);
        Mail::assertNotSent(CulturalModeratorAddInvitationMail::class);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 1);

        Mail::fake();
        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Waiting Person',
                'proposed_moderator_email' => 'waiting.add@example.com',
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'))
            ->assertSessionHas('status', OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE);

        $waiting = CulturalModeratorRequest::query()->where('proposed_moderator_email', 'waiting.add@example.com')->firstOrFail();
        $this->assertTrue($waiting->isAwaitingModeratorEligibility());
        $this->assertNull($waiting->target_user_id);
        Mail::assertSent(CulturalModeratorAddInvitationMail::class, function (CulturalModeratorAddInvitationMail $mail) {
            $this->assertTrue($mail->hasTo('waiting.add@example.com'));
            $this->assertTrue($mail->hasFrom('noreply@kotor.me'));
            $html = $mail->render();
            $this->assertStringContainsString('Pkg5 Org', $html);
            $this->assertStringContainsString(route('register'), $html);

            return true;
        });
    }

    public function test_add_invitation_mail_failure_log_does_not_include_email(): void
    {
        Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP ADD invitation fail'));

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Mail Fail Add',
                'proposed_moderator_email' => 'mailfail.add.invite@example.com',
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'));

        $request = CulturalModeratorRequest::query()
            ->where('proposed_moderator_email', 'mailfail.add.invite@example.com')
            ->firstOrFail();
        $this->assertTrue($request->isAwaitingModeratorEligibility());

        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context = []): bool {
                return str_contains($message, 'PO-ORG-06 ADD invitation mail failed')
                    && ($context['exception'] ?? null) === 'SMTP ADD invitation fail'
                    && isset($context['moderator_request_id'])
                    && ! array_key_exists('proposed_moderator_email', $context)
                    && ! in_array('mailfail.add.invite@example.com', $context, true);
            })
            ->once();
    }

    public function test_duplicate_unfinished_add_blocked_and_cross_org_allowed(): void
    {
        Mail::fake();

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Dup',
                'proposed_moderator_email' => 'dup.add@example.com',
            ])
            ->assertRedirect();

        $this->actingAs($this->initialModerator)
            ->from(route('cultural-moderator-requests.create', $this->organizer))
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Dup Again',
                'proposed_moderator_email' => '  DUP.ADD@EXAMPLE.COM ',
            ])
            ->assertSessionHasErrors('proposed_moderator_email');

        $otherCreation = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->submitter->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Other Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);
        $otherOrg = app(OrganizerCreationDecisionService::class)->approve($otherCreation, $this->editor);

        $this->actingAs($this->submitter)
            ->post(route('cultural-moderator-requests.store', $otherOrg), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Cross',
                'proposed_moderator_email' => 'dup.add@example.com',
            ])
            ->assertRedirect(route('cultural-moderator-workspace.index'));

        $this->assertSame(
            2,
            CulturalModeratorRequest::query()->where('proposed_moderator_email', 'dup.add@example.com')->count()
        );
    }

    public function test_resolver_resolves_add_awaiting_without_grant_or_ready_mail(): void
    {
        Mail::fake();

        $pending = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'resolve.add@example.com',
        ]);

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Resolve',
                'proposed_moderator_email' => 'resolve.add@example.com',
            ]);

        $request = CulturalModeratorRequest::query()->where('proposed_moderator_email', 'resolve.add@example.com')->firstOrFail();
        $this->assertTrue($request->isAwaitingModeratorEligibility());

        $pending->forceFill(['email_verified_at' => now()])->save();
        event(new Verified($pending));

        $fresh = $request->fresh();
        $this->assertTrue($fresh->isSubmitted());
        $this->assertSame($pending->id, $fresh->target_user_id);
        $this->assertSame(1, CulturalModeratorAuthorization::query()->where('organizer_id', $this->organizer->id)->count());
        Mail::assertNotSent(CulturalModeratorAddApprovedMail::class);
    }

    public function test_add_approve_reject_mails_and_remove_approved_silence_on_reject(): void
    {
        Mail::fake();

        $eligible = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'decision.add@example.com',
        ]);

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => $eligible->name,
                'proposed_moderator_email' => $eligible->email,
            ]);

        $addRequest = CulturalModeratorRequest::query()->where('target_user_id', $eligible->id)->firstOrFail();

        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-requests.show', $addRequest))
            ->assertSee('background:#15803d', false)
            ->assertSee('Pri odbijanju napomena se šalje', false);

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.approve', $addRequest))
            ->assertRedirect(route('cultural-moderator-requests.index'));

        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $eligible->id)
                ->where('organizer_id', $this->organizer->id)
                ->where('source', CulturalModeratorAuthorization::SOURCE_SUBSEQUENT)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists()
        );
        Mail::assertSent(CulturalModeratorAddApprovedMail::class, fn ($mail) => $mail->hasTo($eligible->email));

        Mail::fake();
        $rejectableUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'reject.add@example.com',
        ]);
        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => $rejectableUser->name,
                'proposed_moderator_email' => $rejectableUser->email,
            ]);
        $rejectable = CulturalModeratorRequest::query()->where('target_user_id', $rejectableUser->id)->firstOrFail();

        $this->actingAs($this->editor)
            ->from(route('cultural-moderator-requests.show', $rejectable))
            ->post(route('cultural-moderator-requests.reject', $rejectable), ['decision_note' => ''])
            ->assertSessionHasErrors('decision_note');

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.reject', $rejectable), [
                'decision_note' => 'Nedovoljno iskustvo',
            ])
            ->assertRedirect(route('cultural-moderator-requests.index'));

        Mail::assertSent(CulturalModeratorAddRejectedMail::class, function (CulturalModeratorAddRejectedMail $mail) use ($rejectableUser) {
            $this->assertTrue($mail->hasTo($rejectableUser->email));
            $this->assertStringContainsString('Nedovoljno iskustvo', $mail->render());

            return true;
        });

        Mail::fake();
        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_REMOVE,
                'target_user_id' => $eligible->id,
            ])
            ->assertRedirect();

        $removeRequest = CulturalModeratorRequest::query()
            ->where('type', CulturalModeratorRequest::TYPE_REMOVE)
            ->where('target_user_id', $eligible->id)
            ->firstOrFail();
        $this->assertTrue($removeRequest->isSubmitted());

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.reject', $removeRequest), [
                'decision_note' => null,
            ])
            ->assertRedirect(route('cultural-moderator-requests.index'));

        $this->assertSame(CulturalModeratorRequest::STATUS_REJECTED, $removeRequest->fresh()->status);
        $this->assertSame(
            CulturalModeratorAuthorization::STATUS_ACTIVE,
            CulturalModeratorAuthorization::query()
                ->where('user_id', $eligible->id)
                ->where('organizer_id', $this->organizer->id)
                ->value('status')
        );
        Mail::assertNothingSent();

        Mail::fake();
        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_REMOVE,
                'target_user_id' => $eligible->id,
            ]);
        $removeApproved = CulturalModeratorRequest::query()
            ->where('type', CulturalModeratorRequest::TYPE_REMOVE)
            ->where('status', CulturalModeratorRequest::STATUS_SUBMITTED)
            ->where('target_user_id', $eligible->id)
            ->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.approve', $removeApproved))
            ->assertRedirect();

        $this->assertSame(
            CulturalModeratorAuthorization::STATUS_REMOVED,
            CulturalModeratorAuthorization::query()
                ->where('user_id', $eligible->id)
                ->where('organizer_id', $this->organizer->id)
                ->value('status')
        );
        Mail::assertSent(CulturalModeratorRemoveApprovedMail::class, fn ($mail) => $mail->hasTo($eligible->email));
    }

    public function test_last_active_remove_still_blocked_and_awaiting_add_has_no_decision_cta(): void
    {
        Mail::fake();

        $removeLast = CulturalModeratorRequest::create([
            'organizer_id' => $this->organizer->id,
            'submitter_user_id' => $this->initialModerator->id,
            'target_user_id' => $this->initialModerator->id,
            'type' => CulturalModeratorRequest::TYPE_REMOVE,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-moderator-requests.show', $removeLast))
            ->post(route('cultural-moderator-requests.approve', $removeLast))
            ->assertSessionHasErrors('decision');

        $this->assertSame(
            CulturalModeratorAuthorization::STATUS_ACTIVE,
            CulturalModeratorAuthorization::query()->where('user_id', $this->initialModerator->id)->value('status')
        );

        $this->actingAs($this->initialModerator)
            ->post(route('cultural-moderator-requests.store', $this->organizer), [
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'proposed_moderator_name' => 'Wait',
                'proposed_moderator_email' => 'await.cta@example.com',
            ]);
        $awaiting = CulturalModeratorRequest::query()->where('proposed_moderator_email', 'await.cta@example.com')->firstOrFail();

        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-requests.index'))
            ->assertDontSee('await.cta@example.com', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-requests.show', $awaiting))
            ->assertDontSee('background:#15803d', false);

        $this->actingAs($this->editor)
            ->post(route('cultural-moderator-requests.approve', $awaiting))
            ->assertSessionHasErrors('decision');
    }

    public function test_mail_failure_does_not_rollback_add_approve(): void
    {
        Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP ADD fail'));

        $eligible = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'mailfail.add@example.com',
        ]);

        $request = CulturalModeratorRequest::create([
            'organizer_id' => $this->organizer->id,
            'submitter_user_id' => $this->initialModerator->id,
            'target_user_id' => $eligible->id,
            'proposed_moderator_name' => $eligible->name,
            'proposed_moderator_email' => $eligible->email,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
        ]);

        app(ModeratorRequestDecisionService::class)->approve($request, $this->editor);

        $this->assertSame(CulturalModeratorRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $eligible->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists()
        );
        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => str_contains($message, 'PO-ORG-06 Moderator approval outcome mail failed'))
            ->once();
    }
}
