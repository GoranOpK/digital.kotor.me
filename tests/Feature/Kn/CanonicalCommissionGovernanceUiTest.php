<?php

namespace Tests\Feature\Kn;

use App\Mail\CommissionAppointmentMailCopy;
use App\Mail\CommissionMemberAdded;
use App\Models\Application;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\Commission;
use App\Models\CommissionGovernanceConflict;
use App\Models\CommissionMember;
use App\Models\CommissionMemberReplacement;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionAdministrationService as Admin;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\Lifecycle\PublishedCallLifecycleLock;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CommissionProfileConfig;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-012 — canonical Commission governance admin UI / HTTP layer.
 */
class CanonicalCommissionGovernanceUiTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    // ---------------------------------------------------------------- A. Governance page

    public function test_admin_roles_reach_canonical_commission_page(): void
    {
        $call = $this->canonicalCall();

        foreach (['konkurs_admin', 'admin', 'superadmin'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('admin.competitions.canonical-commission.show', $call))
                ->assertOk()
                ->assertSee('Uspostavljanje Komisije', false);
        }

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->get(route('admin.competitions.show', $call))
            ->assertOk()
            ->assertSee(route('admin.competitions.canonical-commission.show', $call), false);
    }

    public function test_wrong_profile_is_forbidden_and_writes_nothing(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        foreach (['zensko', 'omladinsko'] as $type) {
            $call = $this->canonicalCall(['type' => $type]);
            $this->actingAs($admin)->get(route('admin.competitions.canonical-commission.show', $call))->assertForbidden();
            $this->actingAs($admin)
                ->post(route('admin.competitions.canonical-commission.establish', $call), $this->establishPayload())
                ->assertForbidden();
            $this->assertNull($call->fresh()->commission_id);
        }

        $this->assertSame(0, Commission::query()->count());
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call);

        foreach ([$this->userWithRole('korisnik'), $members[G::ROLE_PREDSJEDNIK]->user] as $user) {
            $this->actingAs($user)->get(route('admin.competitions.canonical-commission.show', $call))->assertForbidden();
            $this->actingAs($user)
                ->post(route('admin.competitions.canonical-commission.vacate', [$call, $members[G::ROLE_SEKRETAR]]), [
                    'reason' => 'dismissed', 'triggered_at' => now()->subDay()->format('Y-m-d H:i'),
                ])
                ->assertForbidden();
        }

        $this->assertSame('active', $members[G::ROLE_SEKRETAR]->fresh()->status);
    }

    // ---------------------------------------------------------------- B. Establish

    public function test_valid_canonical_commission_is_established_and_assigned(): void
    {
        $call = $this->canonicalCall();

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.establish', $call), $this->establishPayload())
            ->assertSessionHasNoErrors();

        $commission = Commission::query()->sole();
        $this->assertTrue($commission->isCanonicalProfile());
        $this->assertSame($commission->id, $call->fresh()->commission_id);
        $this->assertSame('Rješenje 01-012/26', $commission->appointment_reference);
        $this->assertSame(now()->subMonths(2)->toDateString(), $commission->start_date->toDateString());
        $this->assertSame(now()->subMonths(2)->addYear()->toDateString(), $commission->end_date->toDateString());
        $this->assertSame(0, $commission->members()->count());

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->get(route('admin.competitions.canonical-commission.show', $call))
            ->assertOk()
            ->assertSee('Mjesto 1', false)
            ->assertSee('Mjesto 3', false)
            ->assertDontSee('Mjesto 4', false)
            ->assertSee('važeći', false);
    }

    public function test_duplicate_closed_or_expired_establish_is_blocked(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $call = $this->canonicalCall();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.establish', $call), $this->establishPayload());

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.establish', $call), $this->establishPayload())
            ->assertSessionHasErrors(['error' => Admin::ALREADY_ASSIGNED_MESSAGE]);

        $closed = $this->canonicalCall(['status' => 'completed', 'closed_at' => now()]);
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.establish', $closed), $this->establishPayload())
            ->assertSessionHasErrors(['error' => Admin::CALL_CLOSED_MESSAGE]);

        $expired = $this->canonicalCall();
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.establish', $expired), $this->establishPayload([
                'mandate_starts_on' => now()->subYears(2)->toDateString(),
            ]))
            ->assertSessionHasErrors(['error' => Admin::MANDATE_ALREADY_EXPIRED_MESSAGE]);

        $this->assertSame(1, Commission::query()->count());
        $this->assertNull($closed->fresh()->commission_id);
        $this->assertNull($expired->fresh()->commission_id);
    }

    public function test_exactly_three_seat_model_is_preserved(): void
    {
        $call = $this->canonicalCall();
        [$commission] = $this->governedCall($call);
        $admin = $this->userWithRole('konkurs_admin');

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($this->komisijaUser('cetvrti'), 3, G::ROLE_SPOLJASNJI, 'biznis'))
            ->assertSessionHasErrors(['error' => G::FOURTH_SEAT_MESSAGE]);

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($this->komisijaUser('peti'), 4, G::ROLE_SEKRETAR))
            ->assertSessionHasErrors('seat_no');

        $this->assertCount(3, app(G::class)->activeMembers($commission));
        $this->assertNull(app(G::class)->structuralCompletenessBlockReason($commission));
    }

    // ---------------------------------------------------------------- C. Appoint

    public function test_valid_member_is_appointed_with_canonical_mail(): void
    {
        $call = $this->canonicalCall(['title' => 'Javni poziv MMSP 2026']);
        $commission = $this->establishedCommission($call);
        $user = $this->komisijaUser('predsjednik');

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($user, 1, G::ROLE_PREDSJEDNIK))
            ->assertSessionHasNoErrors();

        $member = CommissionMember::query()->where('commission_id', $commission->id)->sole();
        $this->assertSame($user->id, $member->user_id);
        $this->assertSame(1, $member->canonical_seat_no);
        $this->assertSame(G::ROLE_PREDSJEDNIK, $member->canonical_seat);
        $this->assertSame('active', $member->status);
        $this->assertFalse($member->hasSignedDeclarations());

        Mail::assertSent(CommissionMemberAdded::class, 1);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($user) {
            $html = $mail->render();

            return $mail->hasTo($user->email)
                && $mail->hasSubject('Imenovanje u Komisiju za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima')
                && str_contains($html, 'Komisije za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima')
                && str_contains($html, 'Predsjednik Komisije (predstavnik Opštine)')
                && str_contains($html, 'Mjesto u Komisiji:</strong> 1 od 3')
                && str_contains($html, 'Javni poziv MMSP 2026')
                && str_contains($html, $user->name)
                && ! str_contains($html, 'ženskom')
                && ! str_contains($html, 'mladih')
                && ! str_contains($html, 'Privremeno');
        });
    }

    public function test_duplicate_seat_role_and_active_member_are_blocked(): void
    {
        $call = $this->canonicalCall();
        $commission = $this->establishedCommission($call);
        $admin = $this->userWithRole('konkurs_admin');
        $chair = $this->komisijaUser('predsjednik');
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($chair, 1, G::ROLE_PREDSJEDNIK));

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($this->komisijaUser('drugi'), 1, G::ROLE_SEKRETAR))
            ->assertSessionHasErrors(['error' => G::SEAT_DUPLICATE_MESSAGE]);
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($this->komisijaUser('treci'), 2, G::ROLE_PREDSJEDNIK))
            ->assertSessionHasErrors(['error' => G::SEAT_DUPLICATE_MESSAGE]);
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($chair, 2, G::ROLE_SEKRETAR))
            ->assertSessionHasErrors(['error' => G::SAME_USER_MESSAGE]);

        $this->assertSame(1, CommissionMember::query()->where('commission_id', $commission->id)->count());
        Mail::assertSent(CommissionMemberAdded::class, 1);
    }

    public function test_pib_conflict_blocks_appointment_persists_evidence_and_is_shown_masked(): void
    {
        $call = $this->canonicalCall();
        $commission = $this->establishedCommission($call);
        $application = $this->submittedApplication($call, '12345678');
        $candidate = $this->komisijaUser('spoljasnji');
        $admin = $this->userWithRole('konkurs_admin');

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($candidate, 3, G::ROLE_SPOLJASNJI, 'biznis', '123-456-78'))
            ->assertSessionHasErrors(['error' => G::PIB_CONFLICT_MESSAGE]);

        $this->assertSame(0, CommissionMember::query()->where('commission_id', $commission->id)->count());
        $conflict = CommissionGovernanceConflict::query()->sole();
        $this->assertNull($conflict->commission_member_id);
        $this->assertSame($candidate->id, $conflict->user_id);
        $this->assertSame($application->id, $conflict->application_id);
        $this->assertSame('12345678', $conflict->normalized_value);
        Mail::assertNothingSent();

        $this->actingAs($admin)
            ->get(route('admin.competitions.canonical-commission.show', $call))
            ->assertOk()
            ->assertSee('Blokirano imenovanje', false)
            ->assertSee('•••••678', false)
            ->assertSee('Prijavom #'.$application->id, false)
            ->assertDontSee('12345678', false)
            ->assertDontSee($application->user->email, false);
    }

    public function test_active_member_pib_conflict_is_detected_and_shown(): void
    {
        $call = $this->canonicalCall();
        $this->establishedCommission($call);
        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($this->komisijaUser('spoljasnji'), 3, G::ROLE_SPOLJASNJI, 'biznis', '02345678'))
            ->assertSessionHasNoErrors();
        $member = CommissionMember::query()->sole();
        $this->submittedApplication($call, '02345678');

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.conflicts', $call))
            ->assertSessionHasNoErrors();

        $conflict = CommissionGovernanceConflict::query()->sole();
        $this->assertSame($member->id, $conflict->commission_member_id);
        $this->assertTrue(app(G::class)->memberHasPibConflict($member->commission, $member));

        $this->actingAs($admin)
            ->get(route('admin.competitions.canonical-commission.show', $call))
            ->assertOk()
            ->assertSee('DA — članstvo blokirano', false)
            ->assertSee('Aktivni član', false);

        $call->forceFill(['status' => 'completed', 'closed_at' => now()])->save();
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.conflicts', $call))
            ->assertSessionHasErrors(['error' => Admin::CALL_CLOSED_MESSAGE]);
        $this->assertSame(1, CommissionGovernanceConflict::query()->count());
    }

    public function test_invalid_mandate_state_and_eligibility_are_blocked(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        $expiredCall = $this->canonicalCall();
        $expired = app(CanonicalCommissionMembershipService::class)->establish('Istekla', now()->subYears(2), 'R-old', now()->subYears(2));
        $expiredCall->forceFill(['commission_id' => $expired->id])->save();
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $expiredCall), $this->appointPayload($this->komisijaUser('a'), 1, G::ROLE_PREDSJEDNIK))
            ->assertSessionHasErrors(['error' => G::MANDATE_MESSAGE]);

        $call = $this->canonicalCall();
        $this->establishedCommission($call);
        $cases = [
            [$this->appointPayload($this->komisijaUser('b'), 1, G::ROLE_PREDSJEDNIK, null, null, now()->subMonths(3)->toDateString()), Admin::APPOINTMENT_OUTSIDE_MANDATE_MESSAGE],
            [$this->appointPayload($this->komisijaUser('c'), 1, G::ROLE_PREDSJEDNIK, null, null, now()->addDay()->toDateString()), Admin::FUTURE_DATE_MESSAGE],
            [array_merge($this->appointPayload($this->komisijaUser('d'), 1, G::ROLE_PREDSJEDNIK), ['email' => 'nepostoji@example.test']), Admin::USER_NOT_FOUND_MESSAGE],
            [$this->appointPayload($this->userWithRole('korisnik'), 1, G::ROLE_PREDSJEDNIK), Admin::USER_ROLE_MESSAGE],
            [$this->appointPayload($this->komisijaUser('e'), 3, G::ROLE_SPOLJASNJI), G::EXTERNAL_CATEGORY_MESSAGE],
        ];
        $applicantMember = $this->komisijaUser('podnosilac');
        $this->submittedApplication($call, '87654321', $applicantMember);
        $cases[] = [$this->appointPayload($applicantMember, 1, G::ROLE_PREDSJEDNIK), Admin::APPLICANT_MESSAGE];

        foreach ($cases as [$payload, $message]) {
            $this->actingAs($admin)
                ->post(route('admin.competitions.canonical-commission.appoint', $call), $payload)
                ->assertSessionHasErrors(['error' => $message]);
        }

        $closed = $this->canonicalCall();
        $this->establishedCommission($closed);
        $closed->forceFill(['status' => 'completed', 'closed_at' => now()])->save();
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $closed), $this->appointPayload($this->komisijaUser('f'), 1, G::ROLE_PREDSJEDNIK))
            ->assertSessionHasErrors(['error' => Admin::CALL_CLOSED_MESSAGE]);

        $this->assertSame(0, CommissionMember::query()->count());
        Mail::assertNothingSent();
    }

    // ---------------------------------------------------------------- D. Declarations

    public function test_active_member_declaration_is_recorded_once_and_immutable(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call, signDeclarations: false);
        $member = $members[G::ROLE_SEKRETAR];
        $admin = $this->userWithRole('konkurs_admin');

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $member]), $this->declarationPayload())
            ->assertSessionHasNoErrors();

        $recorded = $member->fresh();
        $this->assertTrue($recorded->hasSignedDeclarations());
        $this->assertSame(now()->subDays(5)->toDateString(), $recorded->declarations_signed_at->timezone('Europe/Belgrade')->toDateString());
        $this->assertSame(Admin::DECLARATION_CONFIDENTIALITY_TEXT, $recorded->confidentiality_declaration);
        $this->assertSame(Admin::DECLARATION_CONFLICT_TEXT, $recorded->conflict_of_interest_declaration);
        $event = CanonicalAdministrativeEvent::query()->where('action', CanonicalAdministrativeEvent::COMMISSION_DECLARATIONS_RECORDED)->sole();
        $this->assertSame($admin->id, $event->actor_user_id);
        $this->assertSame($call->id, $event->competition_id);
        $this->assertSame($member->id, $event->subject_id);
        $this->assertSame($member->user_id, $event->payload['recorded_on_behalf_of_user_id']);

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $member]), $this->declarationPayload(['signed_on' => now()->toDateString()]))
            ->assertSessionHasErrors(['error' => Admin::DECLARATION_IMMUTABLE_MESSAGE]);

        $this->assertSame($recorded->confidentiality_declaration, $member->fresh()->confidentiality_declaration);
        $this->assertEquals($recorded->declarations_signed_at, $member->fresh()->declarations_signed_at);

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $members[G::ROLE_PREDSJEDNIK]]), ['signed_on' => now()->toDateString()])
            ->assertSessionHasErrors(['confidentiality_signed', 'conflict_of_interest_signed']);
        $this->assertFalse($members[G::ROLE_PREDSJEDNIK]->fresh()->hasSignedDeclarations());

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $members[G::ROLE_PREDSJEDNIK]]), $this->declarationPayload(['signed_on' => now()->subYear()->toDateString()]))
            ->assertSessionHasErrors(['error' => Admin::DATE_BEFORE_APPOINTMENT_MESSAGE]);
    }

    public function test_wrong_member_and_cross_call_declaration_are_blocked(): void
    {
        $call = $this->canonicalCall();
        $this->governedCall($call, signDeclarations: false);
        $otherCall = $this->canonicalCall();
        [, $otherMembers] = $this->governedCall($otherCall, signDeclarations: false);
        $foreign = $otherMembers[G::ROLE_PREDSJEDNIK];

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $foreign]), $this->declarationPayload())
            ->assertForbidden();
        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.vacate', [$call, $foreign]), ['reason' => 'resigned', 'triggered_at' => now()->subDay()->format('Y-m-d H:i')])
            ->assertForbidden();

        $this->actingAs($foreign->user)
            ->post(route('admin.competitions.canonical-commission.declarations', [$otherCall, $foreign]), $this->declarationPayload())
            ->assertForbidden();

        $this->assertFalse($foreign->fresh()->hasSignedDeclarations());
        $this->assertSame('active', $foreign->fresh()->status);
    }

    public function test_replaced_member_cannot_declare_and_history_is_preserved(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call);
        $old = $members[G::ROLE_SEKRETAR];
        $oldDeclaration = $old->fresh()->confidentiality_declaration;
        $admin = $this->userWithRole('konkurs_admin');

        $this->vacateAndReplace($call, $old, $this->komisijaUser('novi-sekretar'));

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $old]), $this->declarationPayload())
            ->assertSessionHasErrors(['error' => G::MEMBER_INACTIVE_MESSAGE]);

        $this->assertSame($oldDeclaration, $old->fresh()->confidentiality_declaration);
        $this->assertTrue($old->fresh()->hasSignedDeclarations());

        $new = CommissionMember::query()->where('commission_id', $old->commission_id)->where('status', 'active')->where('canonical_seat', G::ROLE_SEKRETAR)->sole();
        $this->assertFalse($new->hasSignedDeclarations());
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.declarations', [$call, $new]), $this->declarationPayload(['signed_on' => now()->toDateString()]))
            ->assertSessionHasNoErrors();
        $this->assertTrue($new->fresh()->hasSignedDeclarations());
    }

    public function test_legacy_declaration_route_stays_blocked_for_canonical(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call, signDeclarations: false);

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.commissions.members.store-declarations', $members[G::ROLE_PREDSJEDNIK]), [
                'confidentiality_declaration' => 'x',
                'conflict_of_interest_declaration' => 'y',
            ])
            ->assertForbidden();

        $this->assertNull($members[G::ROLE_PREDSJEDNIK]->fresh()->declarations_signed_at);
    }

    // ---------------------------------------------------------------- E. Vacate

    public function test_active_seat_is_vacated_history_kept_and_authority_lost(): void
    {
        $call = $this->canonicalCall();
        [$commission, $members] = $this->governedCall($call);
        $member = $members[G::ROLE_SPOLJASNJI];
        $triggered = now('Europe/Belgrade')->subDays(2)->startOfMinute();
        $this->assertNull(app(G::class)->memberActionBlockReason($member, $call));

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.vacate', [$call, $member]), [
                'reason' => 'resigned', 'triggered_at' => $triggered->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        $vacated = $member->fresh();
        $this->assertSame('resigned', $vacated->status);
        $this->assertNull($vacated->canonical_active_seat_no);
        $this->assertSame(3, $vacated->canonical_seat_no);
        $this->assertTrue($vacated->hasSignedDeclarations());

        $vacancy = CommissionMemberReplacement::query()->sole();
        $this->assertSame($member->id, $vacancy->vacated_member_id);
        $this->assertTrue(CarbonImmutable::parse($vacancy->due_at)->equalTo(CarbonImmutable::parse($triggered)->addDays(15)));

        $this->assertSame(G::MEMBER_INACTIVE_MESSAGE, app(G::class)->memberActionBlockReason($vacated, $call));
        $this->assertNull(app(G::class)->activeMemberFor($member->user, $call));
        $this->assertSame(G::SIZE_MESSAGE, app(G::class)->structuralCompletenessBlockReason($commission));

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->get(route('admin.competitions.canonical-commission.show', $call))
            ->assertOk()
            ->assertSee('Upražnjeno — čeka zamjenu', false)
            ->assertSee('Ostavka', false);

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.vacate', [$call, $vacated]), ['reason' => 'dismissed', 'triggered_at' => now()->subDay()->format('Y-m-d H:i')])
            ->assertSessionHasErrors(['error' => G::MEMBER_INACTIVE_MESSAGE]);
    }

    public function test_future_vacancy_is_blocked(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call);

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.vacate', [$call, $members[G::ROLE_SEKRETAR]]), [
                'reason' => 'resigned', 'triggered_at' => now()->addDays(2)->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors(['error' => Admin::FUTURE_DATE_MESSAGE]);

        $this->assertSame('active', $members[G::ROLE_SEKRETAR]->fresh()->status);
        $this->assertSame(0, CommissionMemberReplacement::query()->count());
    }

    // ---------------------------------------------------------------- F. Replace

    public function test_replacement_inherits_seat_old_member_historical_and_mail_is_canonical(): void
    {
        $call = $this->canonicalCall();
        [$commission, $members] = $this->governedCall($call);
        $old = $members[G::ROLE_SPOLJASNJI];
        $newUser = $this->komisijaUser('novi-spoljasnji');

        $this->vacateAndReplace($call, $old, $newUser, now()->subDays(3), ['external_category' => 'akademska_zajednica']);

        $old->refresh();
        $new = CommissionMember::query()->whereKey($old->replaced_by_member_id)->sole();
        $this->assertNotNull($old->replaced_at);
        $this->assertSame('resigned', $old->status);
        $this->assertSame('active', $new->status);
        $this->assertSame($newUser->id, $new->user_id);
        $this->assertSame(3, $new->canonical_seat_no);
        $this->assertSame(G::ROLE_SPOLJASNJI, $new->canonical_seat);
        $this->assertSame('akademska_zajednica', $new->external_category);

        $vacancy = CommissionMemberReplacement::query()->sole();
        $this->assertSame($new->id, $vacancy->replacement_member_id);
        $this->assertFalse($vacancy->late);
        $this->assertNull($vacancy->compliance_warning);
        $this->assertCount(4, CommissionMember::query()->where('commission_id', $commission->id)->get());
        $this->assertSame(G::MEMBER_INACTIVE_MESSAGE, app(G::class)->memberActionBlockReason($old, $call));

        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($newUser) {
            $html = $mail->render();

            return $mail->hasTo($newUser->email)
                && $mail->hasSubject('Imenovanje na upražnjeno mjesto u Komisiji za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima')
                && str_contains($html, 'na upražnjeno mjesto')
                && str_contains($html, 'Spoljašnji član Komisije')
                && str_contains($html, 'Akademska zajednica')
                && ! str_contains($html, 'Privremeno')
                && ! str_contains($html, 'ženskom');
        });
    }

    public function test_late_replacement_keeps_15_day_warning_semantics(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call);

        $response = $this->vacateAndReplace($call, $members[G::ROLE_SEKRETAR], $this->komisijaUser('kasni'), now()->subDays(20));

        $response->assertSessionHas('warning', CanonicalCommissionMembershipService::LATE_REPLACEMENT_WARNING);
        $vacancy = CommissionMemberReplacement::query()->sole();
        $this->assertTrue($vacancy->late);
        $this->assertSame(CanonicalCommissionMembershipService::LATE_REPLACEMENT_WARNING, $vacancy->compliance_warning);
    }

    public function test_replace_without_vacancy_is_blocked(): void
    {
        $call = $this->canonicalCall();
        [, $members] = $this->governedCall($call);

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('admin.competitions.canonical-commission.replace', [$call, $members[G::ROLE_SEKRETAR]]), $this->replacePayload($this->komisijaUser('x')))
            ->assertSessionHasErrors(['error' => CanonicalCommissionMembershipService::VACANCY_REQUIRED_MESSAGE]);

        $this->assertSame(3, CommissionMember::query()->where('commission_id', $members[G::ROLE_SEKRETAR]->commission_id)->count());
        Mail::assertNothingSent();
    }

    // ---------------------------------------------------------------- G. Legacy isolation

    public function test_legacy_commission_routes_cannot_change_canonical_commission(): void
    {
        $call = $this->canonicalCall();
        [$commission, $members] = $this->governedCall($call);
        $admin = $this->userWithRole('konkurs_admin');
        $before = $commission->fresh()->only(['name', 'year', 'start_date', 'end_date', 'status', 'mandate_starts_at']);
        $memberCount = CommissionMember::query()->count();

        $this->actingAs($admin)->get(route('admin.commissions.edit', $commission))->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $commission), [
                'name' => 'Preimenovana', 'year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2031-01-01',
                'status' => 'inactive', 'competition_ids' => [],
            ])
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->actingAs($admin)
            ->delete(route('admin.commissions.destroy', $commission))
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->actingAs($admin)
            ->post(route('admin.commissions.members.add', $commission), ['name' => 'N', 'email' => 'n@example.test', 'position' => 'clan', 'member_type' => 'opstina'])
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), [
                'name' => 'Legacy', 'year' => 2026, 'start_date' => now()->toDateString(), 'status' => 'active',
                'members' => [['email' => 'legacy@example.test', 'name' => 'L']],
                'competition_ids' => [$call->id],
            ])
            ->assertSessionHasErrors(['competition_ids' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->actingAs($admin)
            ->post(route('admin.commissions.members.update-status', $members[G::ROLE_SEKRETAR]), ['status' => 'dismissed'])
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->actingAs($admin)
            ->delete(route('admin.commissions.members.delete', $members[G::ROLE_SEKRETAR]))
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->assertEquals($before, $commission->fresh()->only(['name', 'year', 'start_date', 'end_date', 'status', 'mandate_starts_at']));
        $this->assertSame($memberCount, CommissionMember::query()->count());
        $this->assertSame(1, Commission::query()->count());
        $this->assertSame('active', $members[G::ROLE_SEKRETAR]->fresh()->status);
        $this->assertSame($commission->id, $call->fresh()->commission_id);
        Mail::assertNothingSent();
    }

    // ---------------------------------------------------------------- H. Profile safety

    public function test_women_and_youth_mail_profiles_and_legacy_commission_delete_unchanged(): void
    {
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['zensko'])->isZensko());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['omladinsko'])->isOmladinsko());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['ostalo'])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['zensko', self::CANONICAL])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes([self::CANONICAL])->isCanonical());
        $this->assertSame('Imenovanje u Komisiju za podršku ženskom preduzetništvu', CommissionAppointmentMailCopy::fromCompetitionTypes(['zensko'])->memberAddedSubject(false));
        $this->assertSame('Imenovanje u Komisiju za podršku preduzetništvu mladih', CommissionAppointmentMailCopy::fromCompetitionTypes(['omladinsko'])->memberAddedSubject(false));

        $legacy = Commission::create([
            'name' => 'Ženska komisija', 'year' => 2026, 'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(), 'status' => 'active',
        ]);
        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->delete(route('admin.commissions.destroy', $legacy))
            ->assertRedirect(route('admin.commissions.index'));
        $this->assertDatabaseMissing('commissions', ['id' => $legacy->id]);
    }

    // ---------------------------------------------------------------- Audit and competition page

    public function test_every_governance_mutation_is_audited_with_actor_competition_and_subject(): void
    {
        $call = $this->canonicalCall();
        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.establish', $call), $this->establishPayload())->assertSessionHasNoErrors();
        $commission = Commission::query()->sole();

        $chair = $this->komisijaUser('predsjednik');
        $secretary = $this->komisijaUser('sekretar');
        $external = $this->komisijaUser('spoljasnji');
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($chair, 1, G::ROLE_PREDSJEDNIK))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($secretary, 2, G::ROLE_SEKRETAR))->assertSessionHasNoErrors();
        $this->submittedApplication($call, '87654321');
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($this->komisijaUser('sukob'), 3, G::ROLE_SPOLJASNJI, 'biznis', '87654321'))
            ->assertSessionHasErrors(['error' => G::PIB_CONFLICT_MESSAGE]);
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.appoint', $call), $this->appointPayload($external, 3, G::ROLE_SPOLJASNJI, 'biznis'))->assertSessionHasNoErrors();

        $secretaryMember = CommissionMember::query()->where('user_id', $secretary->id)->sole();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.declarations', [$call, $secretaryMember]), $this->declarationPayload())->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.conflicts', $call))->assertSessionHasNoErrors();
        $this->vacateAndReplace($call, $secretaryMember, $this->komisijaUser('zamjena'));
        $replacement = CommissionMember::query()->where('replaces_member_number', 2)->sole();

        $events = CanonicalAdministrativeEvent::query()->orderBy('id')->get();
        $this->assertSame([
            CanonicalAdministrativeEvent::COMMISSION_ESTABLISHED,
            CanonicalAdministrativeEvent::COMMISSION_APPOINTMENT_RECORDED,
            CanonicalAdministrativeEvent::COMMISSION_APPOINTMENT_RECORDED,
            CanonicalAdministrativeEvent::COMMISSION_APPOINTMENT_BLOCKED,
            CanonicalAdministrativeEvent::COMMISSION_APPOINTMENT_RECORDED,
            CanonicalAdministrativeEvent::COMMISSION_DECLARATIONS_RECORDED,
            CanonicalAdministrativeEvent::COMMISSION_CONFLICTS_CHECKED,
            CanonicalAdministrativeEvent::COMMISSION_MEMBER_VACATED,
            CanonicalAdministrativeEvent::COMMISSION_MEMBER_REPLACED,
        ], $events->pluck('action')->all());

        foreach ($events as $event) {
            $this->assertSame($call->id, $event->competition_id);
            $this->assertNotNull($event->created_at);
            $this->assertStringNotContainsString('87654321', json_encode($event->payload));
        }
        $this->assertSame([$admin->id], $events->take(7)->pluck('actor_user_id')->unique()->values()->all());

        $this->assertSame(['Commission', $commission->id], [$events[0]->subject_type, $events[0]->subject_id]);
        $this->assertSame(['CommissionMember', $secretaryMember->id, 2, G::ROLE_SEKRETAR],
            [$events[2]->subject_type, $events[2]->subject_id, $events[2]->payload['seat_no'], $events[2]->payload['role']]);
        $this->assertSame(['Commission', 3], [$events[3]->subject_type, $events[3]->payload['seat_no']]);
        $this->assertSame([$secretaryMember->id, 'resigned'], [$events[7]->subject_id, $events[7]->payload['vacancy_reason']]);
        $this->assertSame([$replacement->id, $secretaryMember->id, false],
            [$events[8]->subject_id, $events[8]->payload['vacated_member_id'], $events[8]->payload['late']]);

        $this->actingAs($admin)
            ->get(route('admin.competitions.canonical-commission.show', $call))
            ->assertOk()
            ->assertSee('Revizijski trag Komisije', false)
            ->assertSee('Evidentirana zamjena', false)
            ->assertSee('evidentirao: '.$admin->name, false);
    }

    public function test_competition_page_shows_canonical_commission_summary(): void
    {
        $call = $this->canonicalCall();
        $admin = $this->userWithRole('konkurs_admin');

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $call))
            ->assertOk()
            ->assertSee('data-section="canonical-commission"', false)
            ->assertSee('Komisija nije uspostavljena.', false);

        [$commission, $members] = $this->governedCall($call, signDeclarations: false);
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.vacate', [$call, $members[G::ROLE_SEKRETAR]]), [
                'reason' => 'resigned', 'triggered_at' => now()->subDay()->format('Y-m-d H:i'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $call))
            ->assertOk()
            ->assertSee($commission->name)
            ->assertSee('Mandat:', false)
            ->assertSee($members[G::ROLE_PREDSJEDNIK]->name.' — Predsjednik Komisije (predstavnik Opštine)')
            ->assertSee('Upražnjeno — čeka zamjenu', false)
            ->assertSee('izjave: nijesu evidentirane', false)
            ->assertSee('sukob interesa: ne', false)
            ->assertSee('Zamjene: 1', false);

        foreach (['zensko', 'omladinsko'] as $type) {
            $this->actingAs($admin)
                ->get(route('admin.competitions.show', $this->canonicalCall(['type' => $type])))
                ->assertOk()
                ->assertDontSee('data-section="canonical-commission"', false);
        }
    }

    public function test_audited_canonical_draft_cannot_be_deleted(): void
    {
        $draft = $this->canonicalCall(['status' => 'draft', 'start_date' => null, 'published_at' => null]);
        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)->post(route('admin.competitions.canonical-commission.establish', $draft), $this->establishPayload())->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $draft))
            ->assertForbidden()
            ->assertSee(PublishedCallLifecycleLock::DELETE_AUDITED_MESSAGE, false);

        $this->assertDatabaseHas('competitions', ['id' => $draft->id]);
        $this->assertSame(1, CanonicalAdministrativeEvent::query()->count());
    }

    // ---------------------------------------------------------------- helpers

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'email' => $role.'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function canonicalCall(array $overrides = []): Competition
    {
        $competition = Competition::create(array_merge([
            'title' => 'KN-SYNC-012-'.uniqid(),
            'description' => 'Governance UI',
            'type' => self::CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ], $overrides));
        $competition->upNumber()->create(['number' => 'UP-012-'.$competition->id]);

        return $competition->fresh();
    }

    private function establishedCommission(Competition $call): Commission
    {
        $start = now()->subMonths(2);
        $commission = app(CanonicalCommissionMembershipService::class)->establish('Kanonska '.uniqid(), $start, 'R-'.uniqid(), $start);
        $call->forceFill(['commission_id' => $commission->id])->save();

        return $commission;
    }

    /**
     * @return array{0: Commission, 1: array<string, CommissionMember>}
     */
    private function governedCall(Competition $call, bool $signDeclarations = true): array
    {
        [$commission, $members] = $this->canonicalCommission(
            mandateStart: now('Europe/Belgrade')->subMonths(2)->format('Y-m-d H:i:s'),
            signDeclarations: $signDeclarations,
        );
        $call->forceFill(['commission_id' => $commission->id])->save();

        return [$commission, $members];
    }

    private function vacateAndReplace(
        Competition $call,
        CommissionMember $member,
        User $newUser,
        ?\DateTimeInterface $triggeredAt = null,
        array $replaceOverrides = [],
    ): \Illuminate\Testing\TestResponse {
        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.vacate', [$call, $member]), [
                'reason' => 'resigned',
                'triggered_at' => ($triggeredAt ?? now()->subDay())->format('Y-m-d H:i'),
            ])
            ->assertSessionHasNoErrors();

        return $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.replace', [$call, $member]), array_merge($this->replacePayload($newUser), $replaceOverrides))
            ->assertSessionHasNoErrors();
    }

    private function submittedApplication(Competition $call, string $pib, ?User $owner = null): Application
    {
        return Application::create([
            'competition_id' => $call->id,
            'user_id' => ($owner ?? $this->userWithRole('korisnik'))->id,
            'status' => 'submitted',
            'business_plan_name' => 'Plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'pib' => $pib,
            'submitted_at' => now()->subDay(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function establishPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => CommissionAppointmentMailCopy::CANONICAL_COMMISSION_TITLE,
            'appointment_reference' => 'Rješenje 01-012/26',
            'appointed_on' => now()->subMonths(2)->toDateString(),
            'mandate_starts_on' => now()->subMonths(2)->toDateString(),
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function appointPayload(
        User $user,
        int $seatNo,
        string $role,
        ?string $category = null,
        ?string $pib = null,
        ?string $appointedOn = null,
    ): array {
        return array_filter([
            'email' => $user->email,
            'seat_no' => $seatNo,
            'role' => $role,
            'external_category' => $category,
            'appointment_reference' => 'Rješenje o imenovanju '.$seatNo,
            'appointed_on' => $appointedOn ?? now()->subMonth()->toDateString(),
            'represented_company_pib' => $pib,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, string>
     */
    private function replacePayload(User $user): array
    {
        return [
            'email' => $user->email,
            'appointment_reference' => 'Rješenje o zamjeni',
            'appointed_on' => now()->toDateString(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function declarationPayload(array $overrides = []): array
    {
        return array_merge([
            'signed_on' => now()->subDays(5)->toDateString(),
            'confidentiality_signed' => '1',
            'conflict_of_interest_signed' => '1',
        ], $overrides);
    }
}
