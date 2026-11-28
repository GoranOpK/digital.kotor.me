<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CanonicalApplicantForm;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

class MmspEvaluationApprovedApplicationsTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_mmsp_approved_applications_remain_visible_without_changing_other_programs(): void
    {
        [$competition, $members, $apps] = $this->fixture();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $before = $this->snapshot($apps);

        $all = $this->actingAs($chair)->get(route('evaluation.index'))->assertOk()->getContent();
        $explicitAll = $this->actingAs($chair)->get(route('evaluation.index', ['filter' => '']))->assertOk()->getContent();
        $selected = $this->actingAs($chair)->get(route('evaluation.index', [
            'filter' => '',
            'competition_id' => $competition->id,
        ]))->assertOk()->getContent();
        $evaluated = $this->actingAs($chair)->get(route('evaluation.index', ['filter' => 'evaluated']))->assertOk()->getContent();
        $rejected = $this->actingAs($chair)->get(route('evaluation.index', ['filter' => 'rejected']))->assertOk()->getContent();
        $pending = $this->actingAs($chair)->get(route('evaluation.index', ['filter' => 'pending']))->assertOk()->getContent();

        foreach ([$all, $explicitAll] as $html) {
            $this->assertApprovedRow($html, 'Ana Podrzana', '50.00');
            $this->assertApprovedRow($html, 'Mila Podrzana', '40.00');
            $this->assertRejectedRow($html, 'Jovo Odbijen');
            $this->assertRejectedRow($html, 'Petar Odbijen');
            $this->assertStringContainsString('Vera Ceka', $html);
            $this->assertStringContainsString('Plan Sara Youth', $html);
            $this->assertStringContainsString('Plan Ivana Zena', $html);
            $this->assertStringNotContainsString('Nacrt Skriven', $html);
            $this->assertStringNotContainsString('Emina Eliminisana', $html);
            $this->assertStringNotContainsString('Plan Marko Youth', $html);
            $this->assertStringNotContainsString('Plan Jelena Zena', $html);
        }

        $this->assertApprovedRow($selected, 'Ana Podrzana', '50.00');
        $this->assertApprovedRow($selected, 'Mila Podrzana', '40.00');
        $this->assertStringNotContainsString('Plan Sara Youth', $selected);
        $this->assertStringNotContainsString('Plan Ivana Zena', $selected);

        $this->assertApprovedRow($evaluated, 'Ana Podrzana', '50.00');
        $this->assertApprovedRow($evaluated, 'Mila Podrzana', '40.00');
        $this->assertStringContainsString('Jovo Odbijen', $evaluated);
        $this->assertStringNotContainsString('Vera Ceka', $evaluated);
        $this->assertStringNotContainsString('Plan Jelena Zena', $evaluated);

        foreach (['Ana Podrzana', 'Mila Podrzana', 'Plan Marko Youth', 'Plan Jelena Zena', 'Nacrt Skriven', 'Vera Ceka'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $rejected);
        }
        $this->assertRejectedRow($rejected, 'Jovo Odbijen');
        $this->assertStringContainsString('Emina Eliminisana', $rejected);
        $this->assertStringContainsString('Plan Ivana Zena', $rejected);

        $this->assertStringContainsString('Vera Ceka', $pending);
        $this->assertStringContainsString('Plan Sara Youth', $pending);
        foreach (['Ana Podrzana', 'Mila Podrzana', 'Jovo Odbijen', 'Petar Odbijen', 'Nacrt Skriven', 'Emina Eliminisana', 'Plan Marko Youth', 'Plan Jelena Zena'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $pending);
        }

        $outsider = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($outsider)->get(route('evaluation.index'))->assertForbidden();

        $otherCommission = Commission::create([
            'name' => 'Druga komisija',
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        CommissionMember::create([
            'commission_id' => $otherCommission->id,
            'user_id' => $outsider->id,
            'name' => $outsider->name,
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
        ]);
        $this->actingAs($outsider)->get(route('evaluation.index'))
            ->assertOk()
            ->assertDontSee('Ana Podrzana');

        $this->actingAs($chair)->get(route('evaluation.create', $apps['approvedHigh']))
            ->assertOk()
            ->assertSee('UKUPAN ZBIR', false)
            ->assertSee('50.00', false)
            ->assertDontSee('Sačuvaj ocjene', false);

        $scoreBefore = EvaluationScore::query()->where('application_id', $apps['approvedHigh']->id)->orderBy('id')->get();
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($chair)->post(route('evaluation.store', $apps['approvedHigh']), $this->rescorePayload());
            $this->fail('Ponovni upis ocjene na odobrenoj prijavi mora biti odbijen.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame(CanonicalIndividualScoringService::CANONICAL_RANKING_FROZEN_MESSAGE, $exception->getMessage());
        }

        $scoreAfter = EvaluationScore::query()->where('application_id', $apps['approvedHigh']->id)->orderBy('id')->get();
        $this->assertSame($scoreBefore->pluck('criterion_1')->all(), $scoreAfter->pluck('criterion_1')->all());
        $this->assertSame($before, $this->snapshot($apps));
    }

    /**
     * @param  array<string, Application>  $apps
     * @return array<string, array{status: string, score: ?string, amount: ?string}>
     */
    private function snapshot(array $apps): array
    {
        $snapshot = [];
        foreach ($apps as $key => $application) {
            $fresh = $application->fresh();
            $snapshot[$key] = [
                'status' => (string) $fresh->status,
                'score' => $fresh->final_score === null ? null : (string) $fresh->final_score,
                'amount' => $fresh->approved_amount === null ? null : (string) $fresh->approved_amount,
            ];
        }

        return $snapshot;
    }

    private function assertApprovedRow(string $html, string $name, string $score): void
    {
        $row = $this->row($html, $name);
        $this->assertStringContainsString('Odobrena prijava', $row);
        $this->assertStringContainsString('status-evaluated', $row);
        $this->assertStringContainsString($score.' / 50', $row);
        $this->assertStringContainsString('Ocijenjena prijava', $row);
        $this->assertStringContainsString('#10b981', $row);
        $this->assertStringNotContainsString('Odbijena prijava', $row);
    }

    private function assertRejectedRow(string $html, string $name): void
    {
        $row = $this->row($html, $name);
        $this->assertStringContainsString('Odbijena prijava', $row);
        $this->assertStringContainsString('status-rejected', $row);
        $this->assertStringContainsString('Pregledaj', $row);
        $this->assertStringNotContainsString('Odobrena prijava', $row);
    }

    private function row(string $html, string $name): string
    {
        preg_match_all('/<tr\b[^>]*>.*?<\/tr>/s', $html, $rows);
        foreach ($rows[0] as $row) {
            if (str_contains($row, $name)) {
                return $row;
            }
        }

        $this->fail($name);
    }

    /**
     * @return array<string, int|string>
     */
    private function rescorePayload(): array
    {
        $payload = ['notes' => 'Pokusaj izmjene', 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload['criterion_'.$i] = 1;
        }

        return $payload;
    }

    /**
     * @return array{0: Competition, 1: array<string, CommissionMember>, 2: array<string, Application>}
     */
    private function fixture(): array
    {
        [$commission, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $competition = Competition::create([
            'title' => 'MMSP lista odobrenih '.uniqid(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'closed',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        $competition->forceFill(['canonical_ranking_formed_at' => now()])->save();

        $apps = [
            'approvedHigh' => $this->canonicalApplication($competition, 'Ana Podrzana', 'approved', '50.00', '1500.00'),
            'approvedLow' => $this->canonicalApplication($competition, 'Mila Podrzana', 'approved', '40.00', '800.00'),
            'rejectedHigh' => $this->canonicalApplication($competition, 'Jovo Odbijen', 'rejected', '20.00', null),
            'rejectedLow' => $this->canonicalApplication($competition, 'Petar Odbijen', 'rejected', '10.00', null),
            'pending' => $this->canonicalApplication($competition, 'Vera Ceka', 'submitted', null, null),
            'draft' => $this->canonicalApplication($competition, 'Nacrt Skriven', 'draft', null, null, false),
            'eliminated' => $this->canonicalApplication($competition, 'Emina Eliminisana', 'submitted', null, null),
        ];

        $this->scoreAll($members, $apps['approvedHigh'], 5);
        $this->scoreAll($members, $apps['approvedLow'], 4);
        $this->scoreAll($members, $apps['rejectedHigh'], 2);
        $this->scoreAll($members, $apps['rejectedLow'], 1);
        foreach (['approvedHigh', 'approvedLow', 'rejectedHigh', 'rejectedLow', 'pending'] as $key) {
            $this->completeAssessment($apps[$key], $chair);
        }

        ApplicationCompletenessAssessment::query()->create([
            'application_id' => $apps['eliminated']->id,
            'competition_id' => $competition->id,
            'outcome' => ApplicationCompletenessAssessment::INCOMPLETE,
            'recorded_by_user_id' => $chair->user_id,
            'evidence_snapshot' => [],
            'evidence_fingerprint' => 'eliminisana',
            'recorded_at' => now()->subDays(90),
            'notice_sent_at' => now()->subDays(90),
        ]);

        $this->otherProgram($chair->user, 'omladinsko', 'Plan Sara Youth', 'submitted');
        $this->otherProgram($chair->user, 'omladinsko', 'Plan Marko Youth', 'approved');
        $this->otherProgram($chair->user, 'zensko', 'Plan Ivana Zena', 'rejected');
        $this->otherProgram($chair->user, 'zensko', 'Plan Jelena Zena', 'approved');

        return [$competition->fresh(), $members, $apps];
    }

    private function otherProgram(User $viewer, string $type, string $planName, string $status): void
    {
        $commission = Commission::create([
            'name' => $type.' '.$planName,
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $seatCount = $type === 'zensko' ? 5 : 3;
        for ($seat = 1; $seat <= $seatCount; $seat++) {
            $user = $seat === 1 ? $viewer : User::factory()->create([
                'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => $type === 'zensko' && $seat > 3 ? 'udruzenje' : 'opstina',
                'canonical_seat_no' => $type === 'omladinsko' ? $seat : null,
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Konkurs '.$type.' '.$status.' '.uniqid(),
            'type' => $type,
            'status' => 'closed',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => $status,
            'business_plan_name' => $planName,
            'founder_name' => $planName,
            'submitted_at' => $status === 'draft' ? null : now(),
            'redni_broj' => 1,
            'final_score' => $status === 'approved' ? '30.00' : null,
            'approved_amount' => $status === 'approved' ? '500.00' : null,
        ]);
    }

    private function canonicalApplication(
        Competition $competition,
        string $name,
        string $status,
        ?string $score,
        ?string $amount,
        bool $claim = true,
    ): Application {
        $owner = User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => $status,
            'business_plan_name' => null,
            'founder_name' => $name,
            'submitted_at' => $status === 'draft' ? null : now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);
        $application->forceFill([
            'final_score' => $score,
            'approved_amount' => $amount,
        ])->save();
        CanonicalApplicantForm::query()->create([
            'application_id' => $application->id,
            'legal_form' => CanonicalLegalForm::PREDUZETNIK,
            'person_name' => $name,
            'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL,
        ])->forceFill(['frozen_at' => now()])->save();
        if ($claim) {
            FinalApplicationClaim::query()->create([
                'user_id' => $owner->id,
                'competition_id' => $competition->id,
                'application_id' => $application->id,
                'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL,
                'claimed_at' => now(),
            ]);
        }

        return $application->fresh();
    }

    private function completeAssessment(Application $application, CommissionMember $chair): void
    {
        ApplicationCompletenessAssessment::query()->create([
            'application_id' => $application->id,
            'competition_id' => $application->competition_id,
            'outcome' => ApplicationCompletenessAssessment::COMPLETE,
            'recorded_by_user_id' => $chair->user_id,
            'evidence_snapshot' => [],
            'evidence_fingerprint' => 'potpuna-'.$application->id,
            'recorded_at' => now()->subDays(20),
        ]);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function scoreAll(array $members, Application $application, int $mark): void
    {
        foreach ($members as $member) {
            $attributes = [
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $member->canonical_seat_no,
                'completed_at' => now(),
            ];
            for ($i = 1; $i <= 10; $i++) {
                $attributes['criterion_'.$i] = $mark;
            }
            EvaluationScore::query()->create($attributes);
        }
    }
}
