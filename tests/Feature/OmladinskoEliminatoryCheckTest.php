<?php

namespace Tests\Feature;

use App\Mail\ApplicationEliminatoryRejectionMail;
use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Support\CompetitionProgramCatalog;
use App\Support\EliminatoryProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OmladinskoEliminatoryCheckTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_m3_is_blocked_without_three_seats(): void
    {
        $ctx = $this->makeYouthContext(complete: false, confirmSession: false);
        $this->assertFalse($ctx['competition']->hasCompleteValidCommission());
        $this->assertM3Blocked($ctx['chairman']->user, $ctx['application']);
    }

    public function test_m3_is_blocked_without_confirmed_first_session(): void
    {
        $ctx = $this->makeYouthContext(complete: true, confirmSession: false);
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();
        $this->assertTrue($ctx['competition']->fresh()->firstCommissionSession()->isDraft());
        $this->assertM3Blocked($ctx['chairman']->user, $ctx['application']);
    }

    public function test_m3_is_blocked_with_quorum_of_one(): void
    {
        $ctx = $this->makeYouthContext(complete: true, confirmSession: false);
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id])
        )->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertTrue($ctx['competition']->fresh()->firstCommissionSession()->isDraft());
        $this->assertM3Blocked($ctx['chairman']->user, $ctx['application']);
    }

    public function test_m3_is_allowed_with_quorum_of_two(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->assertSee('id="eliminatoryForm"', false);
        $this->assertNull($ctx['application']->fresh()->eliminatoryCheck);
    }

    public function test_president_on_seats_one_two_or_three_can_record_m3(): void
    {
        foreach ([1, 2, 3] as $seat) {
            $ctx = $this->makeYouthReadyContext(presidentSeat: $seat);
            $this->actingAs($ctx['chairman']->user)
                ->post(route('evaluation.eliminatory.store', $ctx['application']), $this->youthPayload())
                ->assertRedirect(route('evaluation.create', $ctx['application']));
            $this->assertNotNull($ctx['application']->fresh()->eliminatoryCheck);
            $this->assertNull($ctx['application']->fresh()->eliminatoryCheck->confirmed_at);
        }
    }

    public function test_admin_ordinary_member_other_commission_and_inactive_cannot_mutate_m3(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $payload = $this->youthPayload(['confirmation_acknowledged' => '1']);
        $applicationId = $ctx['application']->id;

        $adminStore = $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('evaluation.eliminatory.store', $ctx['application']), $payload);
        $adminStore->assertRedirect(route('admin.dashboard'));
        $adminStore->assertStatus(302);

        $adminConfirm = $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload);
        $adminConfirm->assertRedirect(route('admin.dashboard'));
        $adminConfirm->assertStatus(302);
        $this->assertYouthM3Untouched($applicationId);

        $this->actingAs($ctx['members'][1]->user)
            ->post(route('evaluation.eliminatory.store', $ctx['application']), $payload)
            ->assertForbidden();
        $this->actingAs($ctx['members'][1]->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload)
            ->assertForbidden();
        $this->assertYouthM3Untouched($applicationId);

        $other = $this->makeYouthReadyContext();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.eliminatory.store', $ctx['application']), $payload)
            ->assertForbidden();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload)
            ->assertForbidden();
        $this->assertYouthM3Untouched($applicationId);

        $ctx['chairman']->update(['status' => 'inactive']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.store', $ctx['application']), $payload)
            ->assertForbidden();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload)
            ->assertForbidden();
        $this->assertYouthM3Untouched($applicationId);
    }

    public function test_youth_labels_order_polarity_and_no_womens_formulations(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $pos1 = strpos($html, 'dokumentacija je nepotpuna');
        $pos2 = strpos($html, 'raniji korisnik nije dostavio M4/M4a');
        $pos3 = strpos($html, 'biznis plan nije povezan sa prioritetnim oblastima člana 12 Odluke o mladima');
        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertTrue($pos1 < $pos2 && $pos2 < $pos3);

        $this->assertStringContainsString('Potpuna', $html);
        $this->assertStringContainsString('Nepotpuna', $html);
        $this->assertStringContainsString('Razlog nije aktiviran', $html);
        $this->assertStringContainsString('Razlog aktiviran', $html);
        $this->assertMatchesRegularExpression('/name="criterion_1"[^>]*value="1"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="criterion_1"[^>]*value="0"[^>]*checked/', $html);

        $this->assertStringNotContainsString('članu 10', $html);
        $this->assertStringNotContainsString('član 10', $html);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $html);
        $this->assertStringNotContainsString('Obrasci 4 i 4a', $html);
        $this->assertStringNotContainsString('Da / Da / Da', $html);
        $this->assertStringNotContainsString('name="note"', $html);
        $this->assertStringContainsString('name="criterion_notes[1]"', $html);
        $this->assertStringContainsString('name="criterion_notes[2]"', $html);
        $this->assertStringContainsString('name="criterion_notes[3]"', $html);

        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }

    public function test_each_activated_reason_requires_its_own_explanation(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $payload = $this->youthPayload([
            'criterion_1' => '0',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'criterion_notes' => [1 => '', 2 => 'ne treba', 3 => ''],
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload)
            ->assertRedirect(route('evaluation.create', $ctx['application']))
            ->assertSessionHasErrors('criterion_notes.1')
            ->assertSessionDoesntHaveErrors(['criterion_notes.2', 'criterion_notes.3']);

        $this->assertNull($ctx['application']->fresh()->eliminatoryCheck?->confirmed_at);
    }

    public function test_multiple_activated_reasons_require_multiple_explanations(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $payload = $this->youthPayload([
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'criterion_notes' => [1 => 'Dokazi nedostaju', 2 => '', 3 => ''],
            'confirmation_acknowledged' => '1',
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload)
            ->assertRedirect(route('evaluation.create', $ctx['application']))
            ->assertSessionHasErrors('criterion_notes.2')
            ->assertSessionDoesntHaveErrors(['criterion_notes.1', 'criterion_notes.3']);
    }

    public function test_server_recomputes_activated_criteria_and_ignores_browser_notes(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPayload([
                'criterion_1' => '1',
                'criterion_2' => '0',
                'criterion_3' => '0',
                'criterion_notes' => [1 => 'Ovo nije aktivirano', 2 => '', 3 => 'Član 12 nije vezan'],
                'confirmation_acknowledged' => '1',
            ]))
            ->assertSessionHasErrors('criterion_notes.2')
            ->assertSessionDoesntHaveErrors('criterion_notes.1');
    }

    public function test_structured_note_is_composed_redisplayed_and_preserved_in_draft(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.store', $ctx['application']), $this->youthPayload([
                'criterion_1' => '0',
                'criterion_2' => '1',
                'criterion_3' => '0',
                'criterion_notes' => [
                    1 => 'Nedostaje izvod',
                    2 => 'Nacrt za drugi',
                    3 => 'Nije član 12',
                ],
            ]))
            ->assertRedirect(route('evaluation.create', $ctx['application']));

        $check = $ctx['application']->fresh()->eliminatoryCheck;
        $this->assertNotNull($check);
        $this->assertNull($check->confirmed_at);
        $this->assertFalse((bool) $check->criterion_1);
        $this->assertTrue((bool) $check->criterion_2);
        $this->assertFalse((bool) $check->criterion_3);
        $this->assertStringStartsWith(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX, (string) $check->note);
        $parsed = EliminatoryProfileConfig::parseYouthNotes($check->note);
        $this->assertSame('Nedostaje izvod', $parsed[1]);
        $this->assertSame('Nacrt za drugi', $parsed[2]);
        $this->assertSame('Nije član 12', $parsed[3]);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Nedostaje izvod', $html);
        $this->assertStringContainsString('Nacrt za drugi', $html);
        $this->assertStringContainsString('Nije član 12', $html);
        $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $html);
    }

    public function test_confirmed_record_is_locked(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPayload([
                'confirmation_acknowledged' => '1',
            ]))
            ->assertRedirect();

        $check = $ctx['application']->fresh()->eliminatoryCheck;
        $this->assertNotNull($check->confirmed_at);
        $this->assertSame($ctx['chairman']->id, $check->confirmed_by_commission_member_id);
        $this->assertSame($ctx['chairman']->user_id, $check->confirmed_by_user_id);
        $this->assertSame($ctx['chairman']->name, $check->confirmed_by_name);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.store', $ctx['application']), $this->youthPayload([
                'criterion_1' => '0',
                'criterion_notes' => [1 => 'kasnija izmjena', 2 => '', 3 => ''],
            ]))
            ->assertForbidden();

        $this->assertTrue((bool) $ctx['application']->fresh()->eliminatoryCheck->criterion_1);
        $this->assertNotNull($ctx['application']->fresh()->eliminatoryCheck->confirmed_at);
    }

    public function test_pass_does_not_open_scoring(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPayload())
            ->assertRedirect();

        $application = $ctx['application']->fresh();
        $this->assertTrue($application->eliminatoryCheck->isConfirmedPass());
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('>Ocijeni<', $html);
        $this->assertStringNotContainsString('Da / Da / Da', $html);
        $this->assertStringNotContainsString('4. Ocjena biznis plana u brojkama', $html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();
    }

    public function test_fail_sends_youth_notice_keeps_submitted_and_does_not_open_scoring(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPayload([
                'criterion_1' => '0',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'criterion_notes' => [
                    1 => 'Dokumentacija nepotpuna',
                    2 => 'Nema M4/M4a',
                    3 => '',
                ],
                'confirmation_acknowledged' => '1',
            ]))
            ->assertRedirect();

        $application = $ctx['application']->fresh(['eliminatoryCheck', 'eliminatoryNotice']);
        $this->assertTrue($application->eliminatoryCheck->isConfirmedFail());
        $this->assertSame('submitted', $application->status);
        $this->assertNotSame('rejected', $application->status);
        $this->assertNotNull($application->eliminatoryNotice);
        $this->assertSame(1, ApplicationEliminatoryNotice::query()->count());
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
        Mail::assertSent(\App\Mail\ApplicationEliminatoryAppealNoticeMail::class);
        Mail::assertNotSent(ApplicationEliminatoryRejectionMail::class);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Dokumentacija nepotpuna', $html);
        $this->assertStringContainsString('Nema M4/M4a', $html);
        $this->assertStringContainsString('dokumentacija je nepotpuna', $html);
        $this->assertStringNotContainsString('biće odbijena', $html);
        $this->assertStringNotContainsString('dopuna dokumentacije', $html);
        $this->assertStringNotContainsString('članu 10', $html);
        $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $html);
        $this->assertStringNotContainsString('>Ocijeni<', $html);
    }

    public function test_fail_does_not_create_document_supplement_request(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPayload([
                'criterion_1' => '0',
                'criterion_notes' => [1 => 'Nedostaju prilozi', 2 => '', 3 => ''],
                'confirmation_acknowledged' => '1',
            ]))
            ->assertRedirect();

        $this->assertTrue($ctx['application']->fresh()->eliminatoryCheck->isConfirmedFail());
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertFalse(str_contains(
            strtolower(json_encode($ctx['application']->fresh()->toArray())),
            'dopun'
        ));
    }

    public function test_dual_membership_lists_only_the_two_assigned_competitions(): void
    {
        $shared = $this->userWithRole('komisija');
        $youth = $this->makeYouthReadyContext(chairmanUser: $shared);
        $zensko = $this->makeZenskoReadyContext(chairmanUser: $shared);
        $third = $this->makeYouthReadyContext();

        $globalFirst = CommissionMember::activeMembershipForUser($shared->id);
        $this->assertNotNull($globalFirst);
        $this->assertNotSame(
            (int) $youth['chairman']->id,
            (int) $zensko['chairman']->id
        );

        $html = $this->actingAs($shared)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($youth['competition']->title, $html);
        $this->assertStringContainsString($zensko['competition']->title, $html);
        $this->assertStringContainsString($youth['application']->business_plan_name, $html);
        $this->assertStringContainsString($zensko['application']->business_plan_name, $html);
        $this->assertStringNotContainsString($third['competition']->title, $html);
        $this->assertStringNotContainsString($third['application']->business_plan_name, $html);
        $this->assertStringContainsString('Obrazac 3', $html);
        $this->assertStringContainsString('Ocjeni', $html);

        $this->actingAs($shared)
            ->get(route('evaluation.index', ['competition_id' => $third['competition']->id]))
            ->assertForbidden();
    }

    public function test_structured_note_helpers_round_trip(): void
    {
        $composed = EliminatoryProfileConfig::composeYouthNotes([
            1 => ' A ',
            3 => 'C',
        ]);
        $this->assertStringStartsWith(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX, $composed);
        $this->assertSame([
            1 => 'A',
            2 => '',
            3 => 'C',
        ], EliminatoryProfileConfig::youthDraftExplanations($composed));
        $this->assertSame('A', EliminatoryProfileConfig::youthExplanation($composed, 1));
        $this->assertSame([
            1 => '',
            2 => '',
            3 => '',
        ], EliminatoryProfileConfig::parseYouthNotes('slobodan tekst'));
    }

    private function assertM3Blocked(User $user, Application $application): void
    {
        $this->actingAs($user)
            ->get(route('evaluation.create', $application))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('evaluation.eliminatory.store', $application), $this->youthPayload())
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->youthPayload([
                'confirmation_acknowledged' => '1',
            ]))
            ->assertForbidden();
        $this->assertNull($application->fresh()->eliminatoryCheck);
    }

    private function assertYouthM3Untouched(int $applicationId): void
    {
        $application = Application::query()->with(['eliminatoryCheck', 'eliminatoryNotice'])->findOrFail($applicationId);

        $this->assertNull($application->eliminatoryCheck);
        $this->assertSame(0, ApplicationEliminatoryCheck::query()->where('application_id', $applicationId)->count());
        $this->assertSame(0, ApplicationEliminatoryCheck::query()->where('application_id', $applicationId)->whereNotNull('confirmed_at')->count());
        $this->assertNull($application->eliminatoryNotice);
        $this->assertSame(0, ApplicationEliminatoryNotice::query()->where('application_id', $applicationId)->count());
        $this->assertSame('submitted', $application->status);
        $this->assertNotSame('rejected', $application->status);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $applicationId)->count());
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
        Mail::assertNothingSent();
        Mail::assertNotQueued(ApplicationEliminatoryRejectionMail::class);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthReadyContext(?User $chairmanUser = null, int $presidentSeat = 1): array
    {
        $ctx = $this->makeYouthContext(complete: true, confirmSession: false, chairmanUser: $chairmanUser, presidentSeat: $presidentSeat);
        $present = [
            $ctx['members'][0]->id,
            $ctx['members'][1]->id,
        ];
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload($present)
        )->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();
        $this->assertFalse($ctx['competition']->fresh()->isCommissionProcessingBlocked());

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(
        bool $complete,
        bool $confirmSession,
        ?User $chairmanUser = null,
        int $presidentSeat = 1,
    ): array {
        $year = $this->yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi M3 '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = [];
        $count = $complete ? 3 : 2;
        for ($seat = 1; $seat <= $count; $seat++) {
            $user = ($seat === $presidentSeat && $chairmanUser)
                ? $chairmanUser
                : User::factory()->create([
                    'role_id' => $komisijaRole->id,
                    'activation_status' => 'active',
                    'email_verified_at' => now(),
                ]);
            if ($user->role_id !== $komisijaRole->id) {
                $user->update(['role_id' => $komisijaRole->id]);
            }
            $members[] = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === $presidentSeat ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Omladinsko M3 '.$year,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '80000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-M3-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan mladi '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $chairman = collect($members)->firstWhere('position', 'predsjednik');

        if ($confirmSession) {
            $this->actingAs($chairman->user)->post(
                route('commission-sessions.first.store', $competition),
                $this->sessionPayload([$members[0]->id, $members[1]->id])
            )->assertSessionHasNoErrors();
            $this->actingAs($chairman->user)
                ->post(route('commission-sessions.first.confirm', $competition))
                ->assertSessionHasNoErrors();
        }

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $chairman->fresh('user'),
            'members' => $members,
            'application' => $application,
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, application: Application}
     */
    private function makeZenskoReadyContext(User $chairmanUser): array
    {
        $commission = Commission::create([
            'name' => 'Zenska M3 '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $members = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $i === 0 ? $chairmanUser : User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members[] = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $types[$i],
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Zensko M3 lista '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-Z-'.uniqid()]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan zensko '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        return [
            'competition' => $competition->fresh(),
            'chairman' => $members[0]->fresh('user'),
            'application' => $application,
        ];
    }

    /**
     * @param  list<int>  $presentIds
     * @return array<string, mixed>
     */
    private function sessionPayload(array $presentIds): array
    {
        return [
            'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'notes' => 'Prva sjednica',
            'present_member_ids' => $presentIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function youthPayload(array $overrides = []): array
    {
        return array_merge([
            'criterion_1' => '1',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'criterion_notes' => [1 => '', 2 => '', 3 => ''],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function scorePayload(array $overrides = []): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return array_merge($payload, $overrides);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }
}
