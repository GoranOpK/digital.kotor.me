<?php

namespace Tests\Feature;

use App\Mail\ApplicationPrigovorDecisionMail;
use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Support\EliminatoryProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OmladinskoPrigovorDecisionTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2030;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_only_that_commission_chairman_can_decide(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $payload = $this->liftAllContestedPayload();

        $this->actingAs($ctx['members'][1]->user)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertForbidden();

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertForbidden();

        $other = $this->submittedYouthPrigovor();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertForbidden();

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertRedirect(route('evaluation.create', $application));

        $this->assertTrue($application->fresh()->prigovor->isAccepted());
    }

    public function test_incomplete_commission_and_inactive_chairman_cannot_decide(): void
    {
        $incomplete = $this->submittedYouthPrigovor();
        $incomplete['members'][2]->update(['status' => 'inactive']);
        $this->actingAs($incomplete['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $incomplete['application']), $this->liftAllContestedPayload())
            ->assertForbidden();
        $this->assertTrue($incomplete['application']->fresh()->prigovor->isPodnesen());

        $inactive = $this->submittedYouthPrigovor();
        $inactive['chairman']->update(['status' => 'inactive']);
        $this->actingAs($inactive['chairman']->fresh('user')->user)
            ->post(route('evaluation.prigovor.decide', $inactive['application']), $this->liftAllContestedPayload())
            ->assertForbidden();
        $this->assertTrue($inactive['application']->fresh()->prigovor->isPodnesen());
    }

    public function test_decision_before_on_and_after_seven_days(): void
    {
        $before = $this->submittedYouthPrigovor();
        $beforeHtml = $this->actingAs($before['chairman']->user)
            ->get(route('evaluation.create', $before['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('7 kalendarskih dana od prijema', $beforeHtml);
        $this->assertStringNotContainsString('Rok od 7 dana je prekoračen', $beforeHtml);

        $this->actingAs($before['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $before['application']), $this->liftAllContestedPayload())
            ->assertRedirect();
        $this->assertFalse($before['application']->fresh()->prigovor->wasDecidedAfterKomisijaDeadline());

        $onTime = $this->submittedYouthPrigovor();
        $this->travel(7)->days();
        $this->actingAs($onTime['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $onTime['application']), $this->liftAllContestedPayload())
            ->assertRedirect();
        $this->assertFalse($onTime['application']->fresh()->prigovor->wasDecidedAfterKomisijaDeadline());
        $this->travelBack();

        $late = $this->submittedYouthPrigovor();
        $this->travel(7)->days();
        $this->travel(1)->seconds();
        $lateHtml = $this->actingAs($late['chairman']->user)
            ->get(route('evaluation.create', $late['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Rok od 7 dana je prekoračen', $lateHtml);
        $this->assertStringContainsString('Evidentiraj odluku Komisije', $lateHtml);

        $this->actingAs($late['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $late['application']), [
                'decision_note' => 'Odluka nakon isteka prikaznog roka.',
                'criterion_outcomes' => [1 => 'otklonjen', 2 => 'otklonjen'],
            ])
            ->assertRedirect();

        $latePrigovor = $late['application']->fresh()->prigovor;
        $this->assertTrue($latePrigovor->isAccepted());
        $this->assertTrue($latePrigovor->wasDecidedAfterKomisijaDeadline());

        $lateDoneHtml = $this->actingAs($late['chairman']->user)
            ->get(route('evaluation.create', $late['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('nakon isteka roka od 7 dana', $lateDoneHtml);
    }

    public function test_no_automatic_decision_after_seven_days(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $this->travel(7)->days();
        $this->travel(1)->seconds();

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk();
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk();

        $fresh = $application->fresh(['prigovor']);
        $this->assertTrue($fresh->prigovor->isPodnesen());
        $this->assertSame('submitted', $fresh->status);
        $this->assertNull($fresh->prigovor->decided_at);
    }

    public function test_only_contested_criterion_has_choice_and_uncontested_stays(): void
    {
        $ctx = $this->submittedYouthPrigovor([1]);
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('name="criterion_outcomes[1]"', $html);
        $this->assertStringNotContainsString('name="criterion_outcomes[2]"', $html);
        $this->assertStringNotContainsString('name="criterion_outcomes[3]"', $html);
        $this->assertStringContainsString('Nije osporen. Ishod: <strong>Ostaje</strong>', $html);
        $this->assertStringNotContainsString('name="odluka"', $html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Prvi razlog je otklonjen. Drugi ostaje jer nije osporen.',
                'criterion_outcomes' => [1 => 'otklonjen'],
            ])
            ->assertRedirect();

        $application->refresh();
        $prigovor = $application->prigovor;
        $this->assertTrue($prigovor->isRejected());
        $this->assertFalse((bool) $prigovor->criterion_1_remaining);
        $this->assertNotNull($prigovor->criterion_1_remaining);
        $this->assertTrue($prigovor->criterionRemainingIsTrue(2));
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('rejected', $application->status);
        $this->assertStringContainsString('raniji korisnik nije dostavio M4/M4a', (string) $application->rejection_reason);
        $this->assertStringNotContainsString('dokumentacija je nepotpuna', (string) $application->rejection_reason);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    public function test_missing_contested_outcome_is_422_and_inactive_cannot_be_decided(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Nedostaje ishod za osporeni kriterijum.',
                'criterion_outcomes' => [2 => 'otklonjen'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['criterion_outcomes.1']);

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Pokušaj odluke o neaktiviranom kriterijumu.',
                'criterion_outcomes' => [
                    1 => 'otklonjen',
                    2 => 'otklonjen',
                    3 => 'otklonjen',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['criterion_outcomes.3']);

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
    }

    public function test_direct_post_cannot_lift_uncontested_reason(): void
    {
        $ctx = $this->submittedYouthPrigovor([1]);
        $application = $ctx['application'];

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Pokušaj otklanjanja neosporenog razloga.',
                'criterion_outcomes' => [
                    1 => 'otklonjen',
                    2 => 'otklonjen',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['criterion_outcomes.2']);

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_partial_accept_rejects_application_when_any_reason_remains(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Prvi razlog otklonjen, drugi ostaje.',
                'criterion_outcomes' => [1 => 'otklonjen', 2 => 'ostaje'],
            ])
            ->assertRedirect();

        $application->refresh();
        $prigovor = $application->prigovor;
        $this->assertTrue($prigovor->isRejected());
        $this->assertSame($ctx['chairman']->id, $prigovor->decided_by_commission_member_id);
        $this->assertSame($ctx['chairman']->user_id, $prigovor->decided_by_user_id);
        $this->assertSame($ctx['chairman']->name, $prigovor->decided_by_name);
        $this->assertFalse((bool) $prigovor->criterion_1_remaining);
        $this->assertTrue($prigovor->criterionRemainingIsTrue(2));
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('rejected', $application->status);
        $this->assertSame('raniji korisnik nije dostavio M4/M4a', $application->rejection_reason);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    public function test_all_reasons_lifted_keeps_submitted_and_scoring_closed(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $this->liftAllContestedPayload())
            ->assertRedirect();

        $application->refresh();
        $prigovor = $application->prigovor;
        $this->assertTrue($prigovor->isAccepted());
        $this->assertFalse((bool) $prigovor->eliminatory_reason_remaining);
        $this->assertFalse((bool) $prigovor->criterion_1_remaining);
        $this->assertFalse((bool) $prigovor->criterion_2_remaining);
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('submitted', $application->status);
        $this->assertNull($application->rejection_reason);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));

        $evalHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Prihvaćen', $evalHtml);
        $this->assertStringNotContainsString('Ocijeni', $evalHtml);
        $this->assertStringNotContainsString('Usmeno obrazloženje', $evalHtml);
        $this->assertStringNotContainsString('član 10', $evalHtml);
        $this->assertStringNotContainsString('članu 10', $evalHtml);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $evalHtml);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $this->liftAllContestedPayload())
            ->assertForbidden();
        $this->assertTrue($application->fresh()->prigovor->isAccepted());

        $applicantHtml = $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Konačna odluka Komisije', $applicantHtml);
        $this->assertStringContainsString('Prihvaćen', $applicantHtml);
        $this->assertStringContainsString('Eliminatorni razlozi su otklonjeni', $applicantHtml);
        $this->assertStringNotContainsString('član 10', $applicantHtml);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $applicantHtml);
        $this->assertStringNotContainsString('Poštovana', $applicantHtml);

        Mail::assertSent(ApplicationPrigovorDecisionMail::class, function (ApplicationPrigovorDecisionMail $mail) use ($application) {
            $html = $mail->render();
            $this->assertTrue($mail->application->is($application));
            $this->assertTrue($mail->isYouth);
            $this->assertStringContainsString('Prihvaćen', $html);
            $this->assertStringContainsString('dokumentacija je nepotpuna', $html);
            $this->assertStringContainsString('Otklonjen', $html);
            $this->assertStringContainsString('Svi osporeni razlozi su otklonjeni.', $html);
            $this->assertStringContainsString('Komisija za podršku preduzetništvu mladih', $html);
            $this->assertStringContainsString('Poštovani/a', $html);
            $this->assertStringNotContainsString('član 10', $html);
            $this->assertStringNotContainsString('članu 10', $html);
            $this->assertStringNotContainsString('ženskom preduzetništvu', $html);
            $this->assertStringNotContainsString('Poštovana ', $html);
            $this->assertStringNotContainsString('podnositeljko', $html);
            $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $html);
            $this->assertStringNotContainsString('"1":', $html);

            return true;
        });
    }

    public function test_failed_mail_does_not_rollback_decision(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        Mail::swap(new class
        {
            public function to($users)
            {
                throw new \RuntimeException('smtp failure');
            }
        });

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Odluka ostaje važeća i ako mail padne.',
                'criterion_outcomes' => [1 => 'ostaje', 2 => 'ostaje'],
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertTrue($application->prigovor->isRejected());
        $this->assertSame('rejected', $application->status);
        $this->assertSame('Odluka ostaje važeća i ako mail padne.', $application->prigovor->decision_note);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    /**
     * @param  list<int>  $contested
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function submittedYouthPrigovor(array $contested = [1, 2]): array
    {
        $ctx = $this->confirmYouthFail();
        $explanations = [];
        foreach ($contested as $number) {
            $explanations[$number] = 'Obrazloženje za kriterijum '.$number.'.';
        }

        $this->actingAs($ctx['application']->user)
            ->post(route('applications.prigovor.store', $ctx['application']), [
                'contested' => $contested,
                'criterion_obrazlozenja' => $explanations,
            ])
            ->assertRedirect();

        return $ctx;
    }

    /**
     * @return array<string, mixed>
     */
    private function liftAllContestedPayload(): array
    {
        return [
            'decision_note' => 'Svi osporeni razlozi su otklonjeni.',
            'criterion_outcomes' => [1 => 'otklonjen', 2 => 'otklonjen'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scorePayload(): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return $payload;
    }

    /**
     * @return array{criterion_1: mixed, criterion_2: mixed, criterion_3: mixed, note: ?string, confirmed_at: mixed}
     */
    private function m3Snapshot(Application $application): array
    {
        $check = $application->fresh()->eliminatoryCheck;

        return [
            'criterion_1' => $check->criterion_1,
            'criterion_2' => $check->criterion_2,
            'criterion_3' => $check->criterion_3,
            'note' => $check->note,
            'confirmed_at' => $check->confirmed_at?->timestamp,
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function confirmYouthFail(): array
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthFailPayload())
            ->assertRedirect();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthReadyContext(): array
    {
        $ctx = $this->makeYouthContext();
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            [
                'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'notes' => 'Prva sjednica',
                'present_member_ids' => [$ctx['members'][0]->id, $ctx['members'][1]->id],
            ]
        )->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(): array
    {
        $year = $this->yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi odluka '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members[] = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Omladinsko odluka '.$year,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-D-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan odluka '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function youthFailPayload(): array
    {
        return [
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'criterion_notes' => [
                1 => 'Nedostaje izvod',
                2 => 'Nema M4',
                3 => '',
            ],
            'confirmation_acknowledged' => '1',
        ];
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
