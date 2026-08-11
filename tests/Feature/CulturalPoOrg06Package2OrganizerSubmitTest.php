<?php

namespace Tests\Feature;

use App\Mail\CulturalOrganizerModeratorInvitationMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * PO-ORG-06 Package 2 — privacy-safe Organizer creation request submit.
 */
class CulturalPoOrg06Package2OrganizerSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private User $eligibleModerator;

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
            'name' => 'Submitter User',
            'email' => 'submitter.user@example.com',
        ]);

        $this->eligibleModerator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'name' => 'Eligible Moderator',
            'email' => 'eligible.moderator@example.com',
        ]);
    }

    public function test_form_does_not_enumerate_platform_users(): void
    {
        $secretA = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'name' => 'SecretAlphaUniqueNameZZ',
            'email' => 'secret.alpha.unique.zz@example.com',
        ]);
        $secretB = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'name' => 'SecretBetaUniqueNameYY',
            'email' => 'secret.beta.unique.yy@example.com',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('cultural-organizer-creation-requests.create'));

        $response->assertOk();
        $response->assertDontSee('name="proposed_moderator_user_id"', false);
        $response->assertDontSee('<select', false);
        $response->assertDontSee($secretA->name, false);
        $response->assertDontSee($secretA->email, false);
        $response->assertDontSee($secretB->name, false);
        $response->assertDontSee($secretB->email, false);
        $response->assertDontSee($this->eligibleModerator->email, false);
        $response->assertSee('name="proposed_moderator_name"', false);
        $response->assertSee('name="proposed_moderator_email"', false);
        $response->assertSee('Ime i prezime predloženog Moderatora', false);
        $response->assertSee('E-mail predloženog Moderatora', false);
    }

    public function test_eligible_submit_binds_user_and_does_not_send_invitation(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Eligible Org',
            'proposed_moderator_name' => 'Name Does Not Matter',
            'proposed_moderator_email' => '  Eligible.Moderator@Example.com ',
        ]);

        $response->assertRedirect(route('cultural-organizer-creation-requests.create'));
        $response->assertSessionHas(
            'status',
            OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE
        );

        $request = CulturalOrganizerCreationRequest::query()->firstOrFail();
        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_SUBMITTED, $request->status);
        $this->assertSame($this->eligibleModerator->id, $request->proposed_moderator_user_id);
        $this->assertSame('eligible.moderator@example.com', $request->proposed_moderator_email);
        $this->assertSame('Name Does Not Matter', $request->proposed_moderator_name);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);

        Mail::assertNothingSent();
    }

    public function test_unknown_email_awaits_and_sends_invitation(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Unknown Org',
            'proposed_moderator_name' => 'Nova Osoba',
            'proposed_moderator_email' => 'nova.osoba@example.com',
        ]);

        $response->assertRedirect(route('cultural-organizer-creation-requests.create'));
        $response->assertSessionHas(
            'status',
            OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE
        );

        $request = CulturalOrganizerCreationRequest::query()->firstOrFail();
        $this->assertTrue($request->isAwaitingModeratorEligibility());
        $this->assertNull($request->proposed_moderator_user_id);
        $this->assertSame('nova.osoba@example.com', $request->proposed_moderator_email);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);

        Mail::assertSent(CulturalOrganizerModeratorInvitationMail::class, function (CulturalOrganizerModeratorInvitationMail $mail) use ($request) {
            $this->assertTrue($mail->hasTo('nova.osoba@example.com'));
            $this->assertTrue($mail->hasFrom('noreply@kotor.me'));

            $html = $mail->render();
            $this->assertStringContainsString('Unknown Org', $html);
            $this->assertStringContainsString(route('register'), $html);
            $this->assertStringContainsString('aktivan i verifikovan', $html);

            return $mail->creationRequest->is($request);
        });
    }

    public function test_unverified_and_inactive_users_await_with_invitation_and_same_flash(): void
    {
        Mail::fake();
        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $unverified = User::factory()->unverified()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email' => 'unverified.mod@example.com',
            'name' => 'Unverified Mod',
        ]);

        $inactive = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'deactivated',
            'email' => 'inactive.mod@example.com',
            'name' => 'Inactive Mod',
        ]);

        foreach ([
            ['email' => $unverified->email, 'name' => 'Any Name A'],
            ['email' => $inactive->email, 'name' => 'Any Name B'],
        ] as $payload) {
            $response = $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
                'naziv' => 'Waiting Org '.$payload['email'],
                'proposed_moderator_name' => $payload['name'],
                'proposed_moderator_email' => $payload['email'],
            ]);

            $response->assertSessionHas(
                'status',
                OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE
            );
            $response->assertSessionMissing('errors');
        }

        $rows = CulturalOrganizerCreationRequest::query()->orderBy('id')->get();
        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertTrue($row->isAwaitingModeratorEligibility());
            $this->assertNull($row->proposed_moderator_user_id);
        }

        Mail::assertSent(CulturalOrganizerModeratorInvitationMail::class, 2);
        $this->assertDatabaseCount('cultural_organizers', 0);
        $this->assertDatabaseCount('cultural_moderator_authorizations', 0);
    }

    public function test_malicious_user_id_cannot_bind_and_response_does_not_enumerate_accounts(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->regularUser)
            ->from(route('cultural-organizer-creation-requests.create'))
            ->post(route('cultural-organizer-creation-requests.store'), [
                'naziv' => 'Tamper Org',
                'proposed_moderator_name' => 'Tamper Name',
                'proposed_moderator_email' => 'tamper.unknown@example.com',
                'proposed_moderator_user_id' => $this->eligibleModerator->id,
            ]);

        $response->assertSessionHasErrors('proposed_moderator_user_id');
        $response->assertSessionMissing('status');
        $this->assertDatabaseCount('cultural_organizer_creation_requests', 0);

        $content = $response->headers->get('Location')
            ? $this->actingAs($this->regularUser)->get($response->headers->get('Location'))->getContent()
            : $response->getContent();

        $this->assertStringNotContainsString('nije registrovan', $content);
        $this->assertStringNotContainsString('korisnik ne postoji', $content);
        $this->assertStringNotContainsString('nije verifikovan', $content);
        $this->assertStringNotContainsString('nije aktivan', $content);
    }

    public function test_mail_failure_keeps_awaiting_request(): void
    {
        Log::spy();
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP failure for test'));

        $response = $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Mail Fail Org',
            'proposed_moderator_name' => 'Mail Fail Person',
            'proposed_moderator_email' => 'mail.fail@example.com',
        ]);

        $response->assertRedirect(route('cultural-organizer-creation-requests.create'));
        $response->assertSessionHas(
            'status',
            OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE
        );

        $request = CulturalOrganizerCreationRequest::query()->firstOrFail();
        $this->assertTrue($request->isAwaitingModeratorEligibility());
        $this->assertNull($request->proposed_moderator_user_id);
        $this->assertDatabaseCount('cultural_organizers', 0);

        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context = []): bool {
                return str_contains($message, 'PO-ORG-06 invitation mail failed')
                    && ($context['exception'] ?? null) === 'SMTP failure for test';
            })
            ->once();
    }

    public function test_eligible_request_still_supports_approve_and_po_org_05_reject(): void
    {
        Mail::fake();

        $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Decision Org',
            'proposed_moderator_name' => $this->eligibleModerator->name,
            'proposed_moderator_email' => $this->eligibleModerator->email,
        ]);

        $request = CulturalOrganizerCreationRequest::query()->firstOrFail();
        $this->assertTrue($request->isSubmitted());

        app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);
        $this->assertDatabaseCount('cultural_organizers', 1);
        $this->assertTrue(
            CulturalModeratorAuthorization::query()
                ->where('user_id', $this->eligibleModerator->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists()
        );

        $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Reject Org',
            'proposed_moderator_name' => $this->eligibleModerator->name,
            'proposed_moderator_email' => $this->eligibleModerator->email,
        ]);
        $rejectable = CulturalOrganizerCreationRequest::query()
            ->where('proposed_naziv', 'Reject Org')
            ->firstOrFail();

        $this->actingAs($this->editor)
            ->from(route('cultural-organizer-creation-requests.show', $rejectable))
            ->post(route('cultural-organizer-creation-requests.reject', $rejectable), [
                'decision_note' => '',
            ])
            ->assertSessionHasErrors('decision_note');

        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_SUBMITTED, $rejectable->fresh()->status);

        $this->actingAs($this->editor)
            ->post(route('cultural-organizer-creation-requests.reject', $rejectable), [
                'decision_note' => 'Nedovoljno',
            ])
            ->assertRedirect(route('cultural-organizer-creation-requests.index'));

        $this->assertSame(CulturalOrganizerCreationRequest::STATUS_REJECTED, $rejectable->fresh()->status);
        $this->assertSame(1, CulturalOrganizer::query()->count());
    }

    public function test_awaiting_request_is_not_submitted_and_hidden_from_default_editor_index(): void
    {
        Mail::fake();

        $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Awaiting Hidden Org',
            'proposed_moderator_name' => 'Waiting Person',
            'proposed_moderator_email' => 'waiting.hidden@example.com',
        ]);

        $request = CulturalOrganizerCreationRequest::query()->firstOrFail();
        $this->assertFalse($request->isSubmitted());
        $this->assertTrue($request->isAwaitingModeratorEligibility());

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.index'))
            ->assertOk()
            ->assertDontSee('Awaiting Hidden Org', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.show', $request))
            ->assertOk()
            ->assertDontSee('action="'.route('cultural-organizer-creation-requests.approve', $request).'"', false)
            ->assertDontSee('background:#15803d', false);
    }

    public function test_ordinary_user_cannot_access_editor_actions(): void
    {
        Mail::fake();

        $this->actingAs($this->regularUser)->post(route('cultural-organizer-creation-requests.store'), [
            'naziv' => 'Editor Guard Org',
            'proposed_moderator_name' => $this->eligibleModerator->name,
            'proposed_moderator_email' => $this->eligibleModerator->email,
        ]);

        $request = CulturalOrganizerCreationRequest::query()->firstOrFail();

        $this->actingAs($this->regularUser)
            ->get(route('cultural-organizer-creation-requests.index'))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-organizer-creation-requests.approve', $request))
            ->assertForbidden();
    }
}
