<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\BusinessPlan;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use App\Services\Competitions\YouthDecisionRankingIdentity;
use App\Services\YouthSecondSessionGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;

class YouthDecisionRankingIdentityTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\MakesIdentitySnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame($this->expectedTestDatabase(), (string) config('database.connections.mysql.database'));
    }

    public function test_profile_rename_preserves_saved_pm_identity_and_ranking_hash(): void
    {
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Istorijski plan');
        $this->support($application, '1000.00', 'Podrška', 'Didije Drogba', '2500.00');
        $user = $application->user;
        (new \App\Identity\CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user));
        $service = app(YouthDecisionRankingIdentity::class);
        $beforeHash = $service->rankingSha256($ctx['competition']->fresh());
        $beforeApplication = $application->fresh()->getRawOriginal();
        $beforePlan = $application->businessPlan->getRawOriginal();
        $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković'])
            ->assertSessionHasNoErrors();
        $this->assertSame($beforeApplication, $application->fresh()->getRawOriginal());
        $this->assertSame($beforePlan, $application->businessPlan->fresh()->getRawOriginal());
        $this->assertSame('Didije Drogba', app(YouthDecisionApplicantIdentity::class)->line($application->fresh()));
        $this->assertSame($beforeHash, $service->rankingSha256($ctx['competition']->fresh()));
    }

    /** Missing history must never borrow a later profile identity. */
    public function test_missing_pm_snapshot_uses_marker_and_keeps_ranking_hash_after_profile_rename(): void
    {
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Plan bez imena');
        $this->support($application, '1000.00', 'Podrška', 'Didije Drogba', '2500.00');
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['physical_person_name' => null]);
        $application->refresh();
        $user = $application->user;
        (new \App\Identity\CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['firstName' => 'Didije', 'lastName' => 'Drogba'],
        ]));
        $service = app(YouthDecisionRankingIdentity::class);
        $beforeHash = $service->rankingSha256($ctx['competition']->fresh());
        $beforeApplication = $application->fresh()->getRawOriginal();
        $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković'])
            ->assertSessionHasNoErrors();
        $this->assertSame($beforeApplication, $application->fresh()->getRawOriginal());
        $this->assertNull(app(YouthDecisionApplicantIdentity::class)->line($application->fresh()));
        $this->assertSame($beforeHash, $service->rankingSha256($ctx['competition']->fresh()));
    }
    public function test_same_data_always_returns_the_same_hash(): void
    {
        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Plan A');
        $this->support($application, '1000.00', '  Podrška.  ', 'Ana Anić', '2500.50');

        $service = app(YouthDecisionRankingIdentity::class);
        $first = $service->rankingSha256($ctx['competition']->fresh());
        $this->travel(2)->hours();
        $second = $service->rankingSha256($ctx['competition']->fresh());

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $first);
        $this->travelBack();
    }

    public function test_score_change_changes_hash_without_changing_rank(): void
    {
        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Plan A');
        $service = app(YouthDecisionRankingIdentity::class);
        $before = $this->rows($ctx['competition']);
        $beforeHash = $service->rankingSha256($ctx['competition']->fresh());

        EvaluationScore::query()
            ->where('application_id', $application->id)
            ->where('canonical_seat_no', 1)
            ->firstOrFail()
            ->forceFill(['criterion_1' => 4])
            ->save();

        $after = $this->rows($ctx['competition']->fresh());
        $this->assertSame('1', $before[0]['ranking_position']);
        $this->assertSame('1', $after[0]['ranking_position']);
        $this->assertNotSame($before[0]['final_score_full'], $after[0]['final_score_full']);
        $this->assertNotSame($beforeHash, $service->rankingSha256($ctx['competition']->fresh()));
    }

    public function test_computed_rank_change_changes_hash(): void
    {
        $ctx = $this->youthCompetition();
        $higher = $this->scoredApplication($ctx, 5, 'Viši plan');
        $lower = $this->scoredApplication($ctx, 4, 'Niži plan');
        $service = app(YouthDecisionRankingIdentity::class);
        $before = $this->rows($ctx['competition']);
        $beforeHash = $service->rankingSha256($ctx['competition']->fresh());

        $higher->forceFill(['ranking_position' => 99])->save();
        $this->assertSame($beforeHash, $service->rankingSha256($ctx['competition']->fresh()));

        EvaluationScore::query()
            ->where('application_id', $higher->id)
            ->update([
                'criterion_1' => 3,
                'criterion_2' => 3,
                'criterion_3' => 3,
                'criterion_4' => 3,
                'criterion_5' => 3,
                'criterion_6' => 3,
                'criterion_7' => 3,
                'criterion_8' => 3,
                'criterion_9' => 3,
                'criterion_10' => 3,
            ]);

        $after = collect($this->rows($ctx['competition']->fresh()))->keyBy('application_id');
        $this->assertSame('1', $before[0]['ranking_position']);
        $this->assertSame((int) $higher->id, $before[0]['application_id']);
        $this->assertSame('2', $after[$higher->id]['ranking_position']);
        $this->assertSame('1', $after[$lower->id]['ranking_position']);
        $this->assertNotSame($beforeHash, $service->rankingSha256($ctx['competition']->fresh()));
    }

    public function test_conclusion_amount_justification_and_plan_data_change_hash(): void
    {
        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Plan A');
        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update([
            'physical_person_name' => 'Ana Anić',
            'applicant_type' => 'fizicko_lice',
        ]);
        $application->refresh();
        BusinessPlan::query()->create([
            'application_id' => $application->id,
            'required_amount' => '2500.00',
        ]);
        $service = app(YouthDecisionRankingIdentity::class);
        $competition = $ctx['competition'];

        $withoutDecision = $service->rankingSha256($competition->fresh());
        $plain = $this->rows($competition->fresh())[0];
        $this->assertSame('', $plain['applicant_identity']);
        $this->assertSame('', $plain['plan_name']);
        $this->assertSame('', $plain['required_amount']);

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['business_plan_name' => 'Drugi naziv']);
        $application->refresh();
        $this->assertSame($withoutDecision, $service->rankingSha256($competition->fresh()));

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['commission_decision' => 'odbija']);
        $application->refresh();
        $rejected = $service->rankingSha256($competition->fresh());
        $this->assertNotSame($withoutDecision, $rejected);
        $this->assertSame('', $this->rows($competition->fresh())[0]['plan_name']);

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '1000.00',
            'commission_justification' => '  Podrška.  ',
        ]);
        $application->refresh();
        $supported = $service->rankingSha256($competition->fresh());
        $row = $this->rows($competition->fresh())[0];
        $this->assertNotSame($rejected, $supported);
        $this->assertSame('Ana Anić', $row['applicant_identity']);
        $this->assertSame('Drugi naziv', $row['plan_name']);
        $this->assertSame('1000.00', $row['approved_amount']);
        $this->assertSame('Podrška.', $row['commission_justification']);
        $this->assertSame('2500.00', $row['required_amount']);

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['commission_justification' => 'Podrška.']);
        $application->refresh();
        $this->assertSame($supported, $service->rankingSha256($competition->fresh()));

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['approved_amount' => '1500.00']);
        $application->refresh();
        $amountChanged = $service->rankingSha256($competition->fresh());
        $this->assertNotSame($supported, $amountChanged);

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['commission_justification' => 'Drugo obrazloženje.']);
        $application->refresh();
        $justificationChanged = $service->rankingSha256($competition->fresh());
        $this->assertNotSame($amountChanged, $justificationChanged);

        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update(['business_plan_name' => 'Novi plan']);
        $application->refresh();
        $planChanged = $service->rankingSha256($competition->fresh());
        $this->assertNotSame($justificationChanged, $planChanged);

        BusinessPlan::query()->where('application_id', $application->id)->update(['required_amount' => '3000.00']);
        $this->assertNotSame($planChanged, $service->rankingSha256($competition->fresh()));
    }

    public function test_database_load_order_does_not_change_hash_or_id_order(): void
    {
        $ctx = $this->youthCompetition();
        $first = $this->scoredApplication($ctx, 4, 'Prvi unos');
        $second = $this->scoredApplication($ctx, 5, 'Drugi unos');
        $service = app(YouthDecisionRankingIdentity::class);
        $hash = $service->rankingSha256($ctx['competition']->fresh());
        $ids = array_column($this->rows($ctx['competition']->fresh()), 'application_id');

        $this->assertSame([(int) $first->id, (int) $second->id], $ids);
        $this->assertLessThan($second->id, $first->id);

        $reversed = new class(app(ApplicationEliminatoryCheckService::class), app(YouthSecondSessionGate::class)) extends CanonicalIndividualScoringService
        {
            public function youthScoringCycleApplications(Competition $competition): Collection
            {
                return parent::youthScoringCycleApplications($competition)->reverse()->values();
            }
        };
        $reversedIdentity = new YouthDecisionRankingIdentity($reversed, new YouthDecisionApplicantIdentity());

        $this->assertSame($hash, $reversedIdentity->rankingSha256($ctx['competition']->fresh()));
        $this->assertSame($ids, array_column(
            json_decode($reversedIdentity->canonicalJson($ctx['competition']->fresh()), true, 512, JSON_THROW_ON_ERROR),
            'application_id'
        ));
    }

    public function test_empty_list_and_list_without_supported_applications_are_deterministic(): void
    {
        $empty = $this->youthCompetition();
        Application::query()->create([
            'competition_id' => $empty['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => 'Nacrt koji nije u ciklusu',
            'applicant_type' => 'fizicko_lice',
            'status' => 'draft',
            'submitted_at' => now(),
        ]);
        $service = app(YouthDecisionRankingIdentity::class);
        $emptyJson = $service->canonicalJson($empty['competition']->fresh());
        $this->assertSame('[]', $emptyJson);
        $this->assertSame(
            $service->rankingSha256($empty['competition']->fresh()),
            hash('sha256', '[]')
        );

        $unsupported = $this->youthCompetition();
        $application = $this->scoredApplication($unsupported, 5, 'Nepodržani plan');
        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update([
            'commission_decision' => 'odbija',
            'approved_amount' => '0.00',
            'commission_justification' => 'Nije podržano.',
            'physical_person_name' => 'Marko Marković',
        ]);
        $application->refresh();
        BusinessPlan::query()->create([
            'application_id' => $application->id,
            'required_amount' => '8000.00',
        ]);

        $row = $this->rows($unsupported['competition']->fresh())[0];
        $this->assertSame((int) $application->id, $row['application_id']);
        $this->assertNotSame('', $row['final_score_full']);
        $this->assertSame('odbija', $row['commission_decision']);
        $this->assertSame('0.00', $row['approved_amount']);
        $this->assertSame('Nije podržano.', $row['commission_justification']);
        $this->assertSame('', $row['applicant_identity']);
        $this->assertSame('', $row['plan_name']);
        $this->assertSame('', $row['required_amount']);
        $this->assertSame(
            $service->rankingSha256($unsupported['competition']->fresh()),
            $service->rankingSha256($unsupported['competition']->fresh())
        );
    }

    public function test_zensko_and_other_competitions_are_not_covered(): void
    {
        $service = app(YouthDecisionRankingIdentity::class);

        foreach (['zensko', 'ostalo'] as $type) {
            $competition = Competition::query()->create([
                'title' => 'Van PM '.$type,
                'description' => 'Opis',
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->subDays(5)->toDateString(),
                'type' => $type,
                'status' => 'published',
                'year' => 2026,
                'deadline_days' => 20,
                'published_at' => now()->subDays(30),
            ]);
            Application::query()->create([
                'competition_id' => $competition->id,
                'user_id' => User::factory()->create()->id,
                'business_plan_name' => 'Plan van PM',
                'applicant_type' => 'fizicko_lice',
                'status' => 'evaluated',
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => '1000.00',
                'submitted_at' => now()->subDay(),
            ]);

            try {
                $service->rankingSha256($competition->fresh());
                $this->fail($type.' konkurs ne smije dobiti PM identitet.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Identitet PM rang-liste važi samo za omladinski konkurs.', $exception->getMessage());
            }
        }
    }

    /**
     * @param  array{competition: Competition, members: list<CommissionMember>}  $ctx
     */
    private function scoredApplication(array $ctx, int $score, string $plan): Application
    {
        $application = Application::query()->create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => $plan,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Podnosilac '.$plan,
            'status' => 'evaluated',
            'submitted_at' => now()->subDays(20),
            'evaluated_at' => now()->subDay(),
        ]);
        ApplicationEliminatoryCheck::query()->create([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now()->subDays(10),
            'confirmed_by_user_id' => $ctx['members'][0]->user_id,
            'confirmed_by_commission_member_id' => $ctx['members'][0]->id,
            'confirmed_by_name' => $ctx['members'][0]->name,
        ]);
        foreach ($ctx['members'] as $index => $member) {
            $payload = [
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $index + 1,
                'completed_at' => now()->subDay(),
            ];
            for ($criterion = 1; $criterion <= 10; $criterion++) {
                $payload['criterion_'.$criterion] = $score;
            }
            EvaluationScore::query()->create($payload);
        }

        return $application->fresh();
    }

    private function support(Application $application, string $amount, string $justification, string $person, string $required): void
    {
        // Fixture/corruption setup only; regular historical writes are tested separately.
        \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)->update([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => $amount,
            'commission_justification' => $justification,
            'physical_person_name' => $person,
            'applicant_type' => 'fizicko_lice',
        ]);
        $application->refresh();
        BusinessPlan::query()->create([
            'application_id' => $application->id,
            'required_amount' => $required,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(Competition $competition): array
    {
        return json_decode(
            app(YouthDecisionRankingIdentity::class)->canonicalJson($competition->fresh()),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array{competition: Competition, members: list<CommissionMember>}
     */
    private function youthCompetition(): array
    {
        $commission = Commission::query()->create([
            'name' => 'PM identitet '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $members = [];
        foreach ([1 => 'predsjednik', 2 => 'clan', 3 => 'clan'] as $seat => $position) {
            $user = User::factory()->create();
            $members[] = CommissionMember::query()->create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $position,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }
        $competition = Competition::query()->create([
            'title' => 'PM identitet '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);

        return [
            'competition' => $competition,
            'members' => $members,
        ];
    }
}
