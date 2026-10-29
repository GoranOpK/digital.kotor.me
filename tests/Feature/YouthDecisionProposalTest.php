<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationOralPresentation;
use App\Models\BusinessPlan;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use App\Services\Competitions\YouthDecisionDocxWriter;
use App\Services\Competitions\YouthDecisionProposal;
use App\Services\Competitions\YouthDecisionProposalBuilder;
use App\Services\Competitions\YouthDecisionSessionDates;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PhpZip\ZipFile;
use Tests\TestCase;

class YouthDecisionProposalTest extends TestCase
{
    use RefreshDatabase;

    private static int $yearSerial = 2140;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_session_dates_use_the_latest_trace_of_the_same_competition(): void
    {
        $older = $this->datedCompetition();
        $newer = $this->datedCompetition();

        $this->stamp($older, '2026-01-05 10:00:00', '2026-02-06 11:00:00', '2026-03-07');
        $this->stamp($newer, '2026-11-01 10:00:00', '2026-11-02 11:00:00', '2026-11-03');

        $dates = app(YouthDecisionSessionDates::class)->forCompetition($older['competition']->fresh());

        $this->assertSame('2026-01-05', $dates['first']);
        $this->assertSame('2026-02-06', $dates['second']);
        $this->assertSame('2026-03-07', $dates['third']);
        $this->assertNull($dates['oral']);
    }

    public function test_missing_session_dates_stay_empty(): void
    {
        $ctx = $this->datedCompetition();

        $dates = app(YouthDecisionSessionDates::class)->forCompetition($ctx['competition']);

        $this->assertNull($dates['first']);
        $this->assertNull($dates['second']);
        $this->assertNull($dates['third']);
        $this->assertNull($dates['oral']);
    }

    public function test_oral_date_is_used_only_when_recorded_holdings_share_one_day(): void
    {
        $oneDay = $this->datedCompetition();
        $this->oral($oneDay, '2026-04-04 09:00:00', '2026-04-04 18:00:00');
        $same = app(YouthDecisionSessionDates::class)->forCompetition($oneDay['competition']->fresh());
        $this->assertSame('2026-04-04', $same['oral']);

        $split = $this->datedCompetition();
        $second = $this->addApplication($split['competition']);
        $this->oral($split, '2026-04-04 09:00:00', '2026-04-04 18:00:00');
        $this->oral($split, '2026-04-05 09:00:00', '2026-04-05 18:00:00', $second);
        $different = app(YouthDecisionSessionDates::class)->forCompetition($split['competition']->fresh());
        $this->assertNull($different['oral']);
    }

    public function test_company_identity_names_one_person_once_and_does_not_invent_missing_data(): void
    {
        $identity = app(YouthDecisionApplicantIdentity::class);

        $same = $this->company('Firma Alfa', 'Ana Anić', 'ana anić');
        $this->assertSame(
            'Firma Alfa, koga zastupa osnivač/ica i izvršni direktor/ica Ana Anić',
            $identity->line($same)
        );

        $both = $this->company('Firma Beta', 'Ana Anić', 'Marko Marković');
        $this->assertSame(
            'Firma Beta, koga zastupa osnivač/ica Ana Anić i izvršni direktor/ica Marko Marković',
            $identity->line($both)
        );

        $missingDirector = $this->company('Firma Gama', 'Ana Anić', null);
        $this->assertNull($identity->line($missingDirector));

        $unregistered = new Application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'doo_name' => 'Milica Milić',
        ]);
        $user = new User(['name' => 'Profil Nalog']);
        $user->setRelation('identity', null);
        $unregistered->setRelation('user', $user);
        $this->assertNull($identity->line($unregistered));

        $person = new Application([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Petar Petrović',
        ]);
        $person->setRelation('user', new User(['name' => 'Drugi Nalog']));
        $this->assertSame('Petar Petrović', $identity->line($person));
    }

    public function test_builder_reads_only_the_requested_competition(): void
    {
        $first = $this->winnerCompetition('Plan Alfa', 'Ana Anić');
        $second = $this->winnerCompetition('Plan Beta', 'Marko Marković');

        $proposal = app(YouthDecisionProposalBuilder::class)->build($first, $this->manualDates(), now());

        $this->assertSame('Plan Alfa', $proposal->winners[0]['plan']);
        $this->assertSame('Ana Anić', $proposal->winners[0]['identity']);
        $plans = array_column($proposal->winners, 'plan');
        $this->assertNotContains('Plan Beta', $plans);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_word_document_keeps_the_template_and_manual_dates(): void
    {
        $proposal = new YouthDecisionProposal(
            competitionId: 77,
            decisionNumber: '01-2140/1',
            decisionDate: '29.10.2026.',
            competitionYear: '2140',
            publishedOn: '01.09.2026.',
            endedOn: '21.09.2026.',
            firstSessionDate: '02.02.2026',
            secondSessionDate: '03.03.2026',
            thirdSessionDate: '04.04.2026',
            oralDate: '05.05.2026',
            winners: [
                [
                    'identity' => 'Ana Anić',
                    'plan' => 'Plan Alfa',
                    'score' => '48,50',
                    'approved' => '1.000,00',
                    'total_value' => '2.500,50',
                ],
                [
                    'identity' => 'Firma Alfa, koga zastupa osnivač/ica i izvršni direktor/ica Lana Lanić',
                    'plan' => 'Plan Beta',
                    'score' => '51,00',
                    'approved' => '500,00',
                    'total_value' => '9.000,00',
                ],
            ],
            receivedCount: 4,
            incompleteCount: 1,
            appealsAccepted: 1,
            appealsRejected: 0,
            proceedCount: 3,
            awardedTotal: '1.500,00',
            commissionMemberCount: 3,
        );

        $path = app(YouthDecisionDocxWriter::class)->write($proposal);
        try {
            $text = $this->docxText($path);
        } finally {
            @unlink($path);
        }

        $this->assertStringContainsString('01-2140/1', $text);
        $this->assertStringContainsString('29.10.2026.', $text);
        $this->assertStringContainsString('Kotor', $text);
        $this->assertStringContainsString('Plan Alfa', $text);
        $this->assertStringContainsString('Plan Beta', $text);
        $this->assertStringContainsString('Lana Lanić', $text);
        $this->assertSame(1, substr_count($text, 'Lana Lanić'));
        $this->assertStringContainsString('02.02.2026', $text);
        $this->assertStringContainsString('03.03.2026', $text);
        $this->assertStringContainsString('04.04.2026', $text);
        $this->assertStringContainsString('05.05.2026', $text);
        $this->assertStringContainsString('1.500,00', $text);
        $this->assertStringContainsString('Članovima Komisije (x3)', $text);
        $this->assertStringContainsString('Podnosiocima prijave (x2)', $text);
        $this->assertStringContainsString('Sekretarijatu (x2)', $text);
        $this->assertStringContainsString('Vladan Barović', $text);
        $this->assertStringContainsString('VD', $text);
        $this->assertStringContainsString('sljedećih', $text);
        $this->assertStringContainsString('podnosilaca', $text);
        $this->assertStringContainsString('odluku o prihvatanju (1) ili odbijanju (0)', $text);
        $this->assertStringContainsString('Komisija je na trećoj sjednici', $text);
        $this->assertStringContainsString('Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj', $text);
        $this->assertStringContainsString('preduzetništvu mladih donosi', $text);
        $this->assertStringNotContainsString('ovdje ide', $text);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $text);
        $this->assertStringNotContainsString('u roku od u roku od', $text);
        $this->assertStringNotContainsString('dredstava', $text);
        $this->assertStringNotContainsString('sledećih', $text);
        $this->assertStringNotContainsString('Sekretarijatu 16', $text);
        $this->assertStringNotContainsString('Komisija je dana na trećoj', $text);
        $this->assertStringNotContainsString('22.06. 2026', $text);
    }

    public function test_chairman_downloads_a_corrected_decision_without_changing_source_dates(): void
    {
        $ctx = $this->finalRanking();
        $application = $ctx['application']->fresh();
        $check = $application->eliminatoryCheck;
        EvaluationScore::query()
            ->whereHas('application', function ($query) use ($ctx) {
                $query->where('competition_id', $ctx['competition']->id);
            })
            ->update(['completed_at' => '2026-02-06 12:00:00']);
        $score = EvaluationScore::query()->where('application_id', $application->id)->firstOrFail();

        $check->forceFill(['confirmed_at' => '2026-01-05 12:00:00'])->save();
        $application->forceFill(['commission_decision_date' => '2026-03-07'])->save();

        $confirmedAt = $check->fresh()->confirmed_at->toIso8601String();
        $completedAt = $score->fresh()->completed_at->toIso8601String();
        $decisionDate = $application->fresh()->commission_decision_date->toDateString();
        $approved = (string) $application->fresh()->approved_amount;

        $chairmanPage = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk();
        $chairmanPage->assertSee('data-testid="youth-decision-generate-form"', false);
        $chairmanPage->assertSee('value="2026-01-05"', false);
        $chairmanPage->assertSee('value="2026-02-06"', false);
        $chairmanPage->assertSee('value="2026-03-07"', false);

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->assertDontSee('data-testid="youth-decision-generate-form"', false);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), [
                'decision_number' => '01-2148/2',
                'first_session_date' => '2026-02-02',
                'second_session_date' => '2026-03-03',
                'third_session_date' => '2026-04-04',
                'oral_presentation_date' => '2026-05-05',
            ]);
        $response->assertOk();
        $file = $response->baseResponse->getFile()->getPathname();
        $text = $this->docxText($file);

        $this->assertStringContainsString('01-2148/2', $text);
        $this->assertStringContainsString('02.02.2026', $text);
        $this->assertStringContainsString('03.03.2026', $text);
        $this->assertStringContainsString('04.04.2026', $text);
        $this->assertStringContainsString('05.05.2026', $text);
        $this->assertStringContainsString('Petar Petrović', $text);
        $this->assertStringContainsString($application->business_plan_name, $text);
        $this->assertStringNotContainsString('05.01.2026', $text);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $text);

        $this->assertSame($confirmedAt, $check->fresh()->confirmed_at->toIso8601String());
        $this->assertSame($completedAt, $score->fresh()->completed_at->toIso8601String());
        $this->assertSame($decisionDate, $application->fresh()->commission_decision_date->toDateString());
        $this->assertSame($approved, (string) $application->fresh()->approved_amount);

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->manualDates())
            ->assertForbidden();

        $closed = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.decision', $ctx['competition']));
        $closed->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::WOMEN_PREDLOG_CLOSED_MESSAGE, $closed->exception?->getMessage());
    }

    public function test_generation_is_refused_before_the_final_ranking_and_when_identity_is_missing(): void
    {
        $ctx = $this->rankingReady();

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->assertDontSee('data-testid="youth-decision-generate-form"', false);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->manualDates())
            ->assertForbidden();

        $this->support($ctx);
        $ctx['application']->forceFill(['physical_person_name' => null])->save();

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.youth-ranking', $ctx['competition']))
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->manualDates())
            ->assertRedirect()
            ->assertSessionHasErrors('decision');

        $this->assertNull($ctx['application']->fresh()->physical_person_name);
    }

    /**
     * @return array{decision_number: string, first_session_date: string, second_session_date: string, third_session_date: string, oral_presentation_date: string}
     */
    private function manualDates(): array
    {
        return [
            'decision_number' => '01-2140/1',
            'first_session_date' => '2026-02-02',
            'second_session_date' => '2026-03-03',
            'third_session_date' => '2026-04-04',
            'oral_presentation_date' => '2026-05-05',
        ];
    }

    private function company(string $legalName, ?string $founder, ?string $director): Application
    {
        $application = new Application([
            'applicant_type' => 'doo',
            'is_registered' => true,
            'founder_name' => $founder,
            'director_name' => $director,
        ]);
        $legal = new LegalEntityIdentity([
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => $legalName,
        ]);
        $platform = new PlatformIdentity(['subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY]);
        $platform->setRelation('legalEntity', $legal);
        $user = new User(['name' => 'Profil Nalog']);
        $user->setRelation('identity', $platform);
        $application->setRelation('user', $user);

        return $application;
    }

    /**
     * @param  array{competition: Competition, application: Application, chairman: CommissionMember}  $ctx
     */
    private function stamp(array $ctx, string $confirmedAt, string $completedAt, string $decisionDate): void
    {
        ApplicationEliminatoryCheck::create([
            'application_id' => $ctx['application']->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => $confirmedAt,
            'confirmed_by_user_id' => $ctx['chairman']->user_id,
        ]);
        EvaluationScore::create([
            'application_id' => $ctx['application']->id,
            'commission_member_id' => $ctx['chairman']->id,
            'completed_at' => $completedAt,
        ]);
        $ctx['application']->forceFill([
            'commission_decision' => 'podrzava_potpuno',
            'commission_decision_date' => $decisionDate,
            'approved_amount' => '100.00',
        ])->save();
    }

    /**
     * @param  array{competition: Competition, application: Application}  $ctx
     */
    private function oral(array $ctx, string $sessionHeldAt, string $heldAt, ?Application $application = null): void
    {
        $application ??= $ctx['application'];
        $session = CommissionSession::create([
            'competition_id' => $ctx['competition']->id,
            'commission_id' => $ctx['competition']->commission_id,
            'session_type' => substr('o'.$application->id, 0, 16),
            'held_at' => $sessionHeldAt,
        ]);
        ApplicationOralPresentation::create([
            'commission_session_id' => $session->id,
            'application_id' => $application->id,
            'held_at' => $heldAt,
        ]);
    }

    private function winnerCompetition(string $plan, string $person): Competition
    {
        $ctx = $this->datedCompetition();
        $ctx['application']->forceFill([
            'business_plan_name' => $plan,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => $person,
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '1000.00',
            'final_score' => '48.50',
            'ranking_position' => 1,
        ])->save();
        BusinessPlan::create([
            'application_id' => $ctx['application']->id,
            'required_amount' => '2500.50',
        ]);

        return $ctx['competition']->fresh();
    }

    /**
     * @return array{competition: Competition, application: Application, chairman: CommissionMember, members: list<CommissionMember>}
     */
    private function finalRanking(): array
    {
        $ctx = $this->rankingReady();
        $this->support($ctx);

        return $ctx;
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, application: Application}  $ctx
     */
    private function support(array $ctx): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    /**
     * @return array{competition: Competition, application: Application, chairman: CommissionMember, members: list<CommissionMember>}
     */
    private function rankingReady(): array
    {
        $ctx = $this->passedEliminatory();
        $ctx['application']->update(['requested_amount' => 1000]);
        $this->lockThreeSeats($ctx);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), [
                'youth_bonus_action' => 'confirm',
            ])
            ->assertSessionHasNoErrors();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, application: Application, chairman: CommissionMember, members: list<CommissionMember>}
     */
    private function passedEliminatory(): array
    {
        $ctx = $this->makeYouthContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'criterion_notes' => [1 => '', 2 => '', 3 => ''],
            ])
            ->assertRedirect();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, application: Application, chairman: CommissionMember, members: list<CommissionMember>}
     */
    private function makeYouthContext(): array
    {
        $year = self::$yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi odluka '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
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
            'competition_number' => 'UP-C-'.$year.'-1',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ])->id,
            'business_plan_name' => 'Plan odluka '.$year,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Petar Petrović',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        BusinessPlan::create([
            'application_id' => $application->id,
            'required_amount' => '2500.50',
        ]);

        return [
            'competition' => $competition->fresh(),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application->fresh(),
        ];
    }

    /**
     * @return array{competition: Competition, application: Application, chairman: CommissionMember}
     */
    private function datedCompetition(): array
    {
        return $this->makeYouthContext();
    }

    private function addApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ])->id,
            'business_plan_name' => 'Drugi plan '.$competition->id,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Drugi Podnosilac',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(20),
        ]);
    }

    /**
     * @param  array{application: Application, members: list<CommissionMember>}  $ctx
     */
    private function lockThreeSeats(array $ctx): void
    {
        foreach ([1, 2, 3] as $seat) {
            $this->actingAs($ctx['members'][$seat - 1]->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $this->scorePayload())
                ->assertSessionHasNoErrors();
        }
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

    private function docxText(string $path): string
    {
        $zip = new ZipFile();
        $zip->openFile($path);
        $text = $zip->getEntryContents('word/document.xml').$zip->getEntryContents('word/header1.xml');
        $image = $zip->getEntryContents('word/media/image1.jpeg');
        $zip->close();
        $this->assertNotSame('', $image);

        return strip_tags($text);
    }
}
