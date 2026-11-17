<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Support\CommissionCanonicalSeat;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Minimal §13.6 gate on existing chairman decision → Predlog flow (ŽP only).
 */
class ZpEqualScoreAllocationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_store_decision_allows_razvoj_before_zapocinjanje_mid_flow(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        // Isti rang ne rezerviše zahtjev otpočinjanja. Već sačuvani iznos umanjuje ostatak.
        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('podrzava_potpuno', $dev->fresh()->commission_decision);
        $this->assertSame('30000.00', $dev->fresh()->approved_amount);
        $this->assertNotNull($dev->fresh()->commission_decision_locked_at);
        $this->assertNull($start->fresh()->commission_decision);
        $this->assertSame(40.0, (float) $start->fresh()->final_score);
        $this->assertSame(40.0, (float) $dev->fresh()->final_score);
        $this->assertSame((int) $start->ranking_position, (int) $dev->fresh()->ranking_position);

        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertRedirect(route('admin.competitions.ranking', $competition))
            ->assertSessionHasErrors('approved_amount');
        $this->assertNull($start->fresh()->commission_decision);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija odbija otpočinjanje iako je razvoj već podržan.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertNotNull($start->fresh()->commission_decision_locked_at);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertForbidden();
        $this->assertSame('odbija', $start->fresh()->commission_decision);
        $this->assertNull($start->fresh()->approved_amount);
    }

    public function test_generate_decision_allowed_when_razvoj_funded_and_zapocinjanje_rejected(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        $this->storeLegacyDecision($dev, 'podrzava_potpuno', '30000.00');
        $this->storeLegacyDecision($start, 'odbija', null, 'Odbijeno uz obrazloženje.');

        $this->assertTrue($competition->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk();

        $this->assertSame(40.0, (float) $start->fresh()->final_score);
        $this->assertSame(1, (int) $start->fresh()->ranking_position);
        $this->assertSame(1, (int) $dev->fresh()->ranking_position);
    }

    public function test_generate_decision_allowed_when_zapocinjanje_funded_over_razvoj(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Sredstva dodijeljena prijavi za započinjanje.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk();
    }

    public function test_generate_decision_allowed_when_budget_covers_entire_equal_score_group(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 100000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk();
    }

    public function test_sufficient_budget_supports_both_equal_score_applications_with_manual_amounts(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 100000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 12500,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 8700,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('podrzava_potpuno', $start->fresh()->commission_decision);
        $this->assertSame('12500.00', $start->fresh()->approved_amount);
        $this->assertSame('podrzava_potpuno', $dev->fresh()->commission_decision);
        $this->assertSame('8700.00', $dev->fresh()->approved_amount);
    }

    public function test_sufficient_budget_allows_rejecting_startup_then_supporting_development(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 100000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija odbija otpočinjanje iz drugog razloga.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 15000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('odbija', $start->fresh()->commission_decision);
        $this->assertSame('podrzava_potpuno', $dev->fresh()->commission_decision);
        $this->assertSame('15000.00', $dev->fresh()->approved_amount);
    }

    public function test_sufficient_budget_allows_supporting_development_then_rejecting_startup(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 100000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 18000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija odbija otpočinjanje iako razvoj već ima iznos.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('18000.00', $dev->fresh()->approved_amount);
        $this->assertSame('odbija', $start->fresh()->commission_decision);
    }

    public function test_rejected_startup_does_not_block_a_manual_development_amount(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijeno otpočinjanje pri nedovoljnom budžetu.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 20000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('podrzava_potpuno', $dev->fresh()->commission_decision);
        $this->assertSame('20000.00', $dev->fresh()->approved_amount);
        $this->assertSame('odbija', $start->fresh()->commission_decision);
    }

    public function test_insufficient_budget_still_allows_rejecting_both_applications(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijeno otpočinjanje.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijen i razvoj.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('odbija', $start->fresh()->commission_decision);
        $this->assertSame('odbija', $dev->fresh()->commission_decision);
    }

    public function test_different_ranks_do_not_apply_equal_score_startup_priority_and_keep_the_twenty_percent_cap(): void
    {
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 5, 5, 5, 5, 5, 5, 5, 5], // 50
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
        ]);
        $competition->update(['budget' => 100000]);
        $higher = $apps[0]->fresh();
        $lower = $apps[1]->fresh();
        $higher->update([
            'business_stage' => 'razvoj',
            'requested_amount' => 30000,
            'bonus_green_innovative' => true,
        ]);
        $lower->update([
            'business_stage' => 'započinjanje',
            'requested_amount' => 30000,
        ]);
        $this->assertNotSame((int) $higher->fresh()->ranking_position, (int) $lower->fresh()->ranking_position);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $lower), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Niži rang se odbija posebno.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->post(route('evaluation.store-decision', $higher), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 25000,
            ])
            ->assertRedirect(route('admin.competitions.ranking', $competition))
            ->assertSessionHasErrors('approved_amount');
        $this->assertNull($higher->fresh()->commission_decision);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $higher), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 6400,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('podrzava_potpuno', $higher->fresh()->commission_decision);
        $this->assertSame('6400.00', $higher->fresh()->approved_amount);
        $this->assertSame('odbija', $lower->fresh()->commission_decision);
    }

    public function test_new_decision_blocks_when_winner_identity_is_missing_and_archive_view_stays_open(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $start), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $dev), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Sredstva dodijeljena prijavi za započinjanje.',
            ])
            ->assertSessionHasNoErrors();

        $start->forceFill(['preduzetnik_name' => null])->save();

        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->get(route('admin.competitions.decision', $competition))
            ->assertRedirect(route('admin.competitions.ranking', $competition))
            ->assertSessionHasErrors([
                'error' => $start->business_plan_name.': '.Application::DECISION_IDENTITY_APPLICANT_NAME_MISSING,
            ]);

        $competition->forceFill(['status' => 'completed', 'closed_at' => now()])->save();

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk()
            ->assertSee('Ime preduzetnice nije upisano u prijavi.', false);
    }

    public function test_same_stage_podrzava_requires_justification_when_budget_insufficient(): void
    {
        [$competition, $president, $a, $b] = $this->equalScoreSameStagePair(
            stage: 'započinjanje',
            budget: 50000,
            requested: 30000
        );

        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->post(route('evaluation.store-decision', $a), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
            ])
            ->assertRedirect(route('admin.competitions.ranking', $competition))
            ->assertSessionHasErrors('commission_justification');

        $this->assertNull($a->fresh()->commission_decision);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $a), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 30000,
                'commission_justification' => 'Komisija daje prioritet ovoj prijavi.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('podrzava_potpuno', $a->fresh()->commission_decision);
        $this->assertSame('Komisija daje prioritet ovoj prijavi.', $a->fresh()->commission_justification);
    }

    public function test_podrzava_without_justification_still_allowed_when_not_equal_score_tie(): void
    {
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 5, 5, 5, 5, 5, 5, 5, 5], // 50
            [5, 5, 5, 4, 4, 4, 4, 4, 4, 4], // 43
        ]);
        $competition->update(['budget' => 100000]);
        $first = $apps[0]->fresh();
        $first->update(['requested_amount' => 1000]);
        $apps[1]->fresh()->update(['requested_amount' => 1000]);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $first), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 250,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('podrzava_potpuno', $first->fresh()->commission_decision);
        $this->assertNull($first->fresh()->commission_justification);
    }

    public function test_omladinsko_unaffected_by_zp_equal_score_gate(): void
    {
        $commission = $this->createCommissionWithMembers(5, true);
        $competition = Competition::create([
            'title' => 'Omladinski '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 50000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-'.uniqid()]);

        $guard = app(ZpEqualScoreAllocationGuard::class);
        $this->assertNull($guard->blockReason($competition->fresh()));
    }

    public function test_ranking_page_and_decision_list_show_startup_before_development_at_the_same_rank(): void
    {
        [$competition, $president, $lowerIdStartup, $higherIdDevelopment] = $this->equalScoreMixedStagePair(budget: 100000, requested: 1000);
        $lowerIdStartup->update([
            'business_stage' => 'razvoj',
            'business_plan_name' => 'Razvoj prije po identifikatoru',
        ]);
        $higherIdDevelopment->update([
            'business_stage' => 'započinjanje',
            'business_plan_name' => 'Otpočinjanje poslije po identifikatoru',
        ]);
        $this->assertGreaterThan($lowerIdStartup->id, $higherIdDevelopment->id);
        $this->assertSame(1, (int) $lowerIdStartup->fresh()->ranking_position);
        $this->assertSame(1, (int) $higherIdDevelopment->fresh()->ranking_position);

        $ranking = $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $rankingBody = $ranking->getContent();
        $startupAt = strpos($rankingBody, 'Otpočinjanje poslije po identifikatoru');
        $developmentAt = strpos($rankingBody, 'Razvoj prije po identifikatoru');
        $this->assertNotFalse($startupAt);
        $this->assertNotFalse($developmentAt);
        $this->assertLessThan($developmentAt, $startupAt);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $higherIdDevelopment), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 700,
            ])
            ->assertSessionHasNoErrors();
        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $lowerIdStartup), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 400,
            ])
            ->assertSessionHasNoErrors();

        $listed = app(\App\Services\Competitions\CompetitionDecisionDocumentBuilder::class)
            ->articleOneApplications($competition)
            ->pluck('business_plan_name')
            ->all();
        $this->assertSame([
            'Otpočinjanje poslije po identifikatoru',
            'Razvoj prije po identifikatoru',
        ], $listed);
        $this->assertSame('700.00', $higherIdDevelopment->fresh()->approved_amount);
        $this->assertSame('400.00', $lowerIdStartup->fresh()->approved_amount);
    }

    public function test_close_competition_is_not_blocked_by_startup_display_order(): void
    {
        [$competition, $president, $start, $dev] = $this->equalScoreMixedStagePair(budget: 50000, requested: 30000);

        $this->storeLegacyDecision($dev, 'podrzava_potpuno', '30000.00');
        $this->storeLegacyDecision($start, 'odbija', null, 'Odbijeno.');

        $this->actingAs($president->user)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $competition->fresh()->status);
    }

    private function storeLegacyDecision(
        Application $application,
        string $decision,
        ?string $amount,
        ?string $justification = null,
    ): void {
        $application->forceFill([
            'status' => $decision === 'podrzava_potpuno' ? 'approved' : 'rejected',
            'commission_decision' => $decision,
            'approved_amount' => $amount,
            'commission_justification' => $justification,
            'rejection_reason' => $decision === 'odbija' ? $justification : null,
            'commission_decision_date' => now()->toDateString(),
            'signed_by_chairman' => true,
            'commission_decision_locked_at' => null,
        ])->save();
    }

    /**
     * @return array{0: Competition, 1: CommissionMember, 2: Application, 3: Application}
     */
    private function equalScoreMixedStagePair(int $budget, int $requested): array
    {
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
        ]);
        $competition->update(['budget' => $budget]);
        $start = $apps[0]->fresh();
        $dev = $apps[1]->fresh();
        $start->update([
            'business_stage' => 'započinjanje',
            'requested_amount' => $requested,
        ]);
        $dev->update([
            'business_stage' => 'razvoj',
            'requested_amount' => $requested,
        ]);

        $this->assertSame((int) $start->ranking_position, (int) $dev->ranking_position);
        $this->assertSame((float) $start->final_score, (float) $dev->final_score);

        return [$competition->fresh(), $president, $start->fresh(), $dev->fresh()];
    }

    /**
     * @return array{0: Competition, 1: CommissionMember, 2: Application, 3: Application}
     */
    private function equalScoreSameStagePair(string $stage, int $budget, int $requested): array
    {
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
        ]);
        $competition->update(['budget' => $budget]);
        $a = $apps[0]->fresh();
        $b = $apps[1]->fresh();
        $a->update(['business_stage' => $stage, 'requested_amount' => $requested]);
        $b->update(['business_stage' => $stage, 'requested_amount' => $requested]);

        return [$competition->fresh(), $president, $a->fresh(), $b->fresh()];
    }

    /**
     * @param  list<list<int>>  $criteriaPerApplication
     * @return array{0: Competition, 1: \Illuminate\Support\Collection<int, CommissionMember>, 2: CommissionMember, 3: list<Application>}
     */
    private function completeCycleWithUniformCriteria(array $criteriaPerApplication): array
    {
        $commission = $this->createCommissionWithMembers(5, true);
        $competition = $this->createZenskoCompetition($commission);
        $members = $commission->activeMembers;
        $president = $members->firstWhere('position', 'predsjednik');
        $this->assertNotNull($president);

        $applications = [];
        foreach ($criteriaPerApplication as $criteria) {
            $application = $this->createSubmittedApplication($competition);
            $this->confirmPass($president, $application);
            $applications[] = $application;

            $payload = ['notes' => null, 'scoring_confirmed' => '1'];
            for ($i = 1; $i <= 10; $i++) {
                $payload["criterion_{$i}"] = $criteria[$i - 1];
            }

            foreach ($members as $commissionMember) {
                $this->actingAs($commissionMember->user)
                    ->post(route('evaluation.store', $application), $payload)
                    ->assertRedirect();
            }
        }

        return [$competition->fresh(), $members, $president, $applications];
    }

    private function confirmPass(CommissionMember $president, Application $application): void
    {
        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();
    }

    private function createSubmittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Biznis plan '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => 'Test Preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
    }

    private function createZenskoCompetition(Commission $commission): Competition
    {
        $competition = Competition::create([
            'title' => 'Konkurs §13.6 '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 100000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        return $competition->fresh();
    }

    private function createCommissionWithMembers(int $count, bool $withPresident): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);

        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => ($withPresident && $i === 0) ? 'predsjednik' : 'clan',
                'member_type' => $types[$i] ?? 'opstina',
                'status' => 'active',
            ]);
        }

        $commission = $commission->fresh(['activeMembers.user', 'members']);
        CommissionCanonicalSeat::persistForCommission($commission);

        return $commission->fresh(['activeMembers.user', 'members']);
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
