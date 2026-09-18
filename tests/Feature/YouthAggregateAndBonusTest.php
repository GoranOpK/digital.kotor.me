<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthAggregateAndBonusTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_three_seats_average_divides_by_three_without_waiting_for_seats_four_and_five(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx, [
            1 => ['criterion_1' => 2],
            2 => ['criterion_1' => 3],
            3 => ['criterion_1' => 4],
        ]);
        $this->confirmYouthBonuses($ctx);

        $application = $ctx['application']->fresh();
        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateYouthApplication($application);

        $this->assertNotNull($aggregate);
        $this->assertSame('3.0000000000', $aggregate['criterion_averages'][1]);
        $this->assertSame('30.0000000000', $aggregate['base_score']);
        $this->assertSame(0, $aggregate['bonus']);
        $this->assertSame('30.0000000000', $aggregate['final_score_full']);
        $this->assertSame('30.00', $application->final_score);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $application->id)->whereIn('canonical_seat_no', [4, 5])->count());
        $this->assertTrue($application->meetsMinimumScore());
        $this->assertSame('evaluated', $application->status);
        $this->assertNull($application->ranking_position);
        $this->assertNotSame('approved', $application->status);
        $this->assertNotSame('rejected', $application->status);
        $this->assertFalse($application->competition->isRankingFormed());
    }

    public function test_info_day_or_training_alone_is_zero_and_both_are_plus_one(): void
    {
        $onlyInfo = $this->youthReadyToLock();
        $this->actingAs($onlyInfo['chairman']->user)
            ->post(route('evaluation.store', $onlyInfo['application']), $this->bonusPayload(['bonus_info_day' => '1'], 'draft'))
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $onlyInfo['application']->fresh()->bonus_info_day);
        $this->assertFalse((bool) $onlyInfo['application']->fresh()->bonus_training);
        $this->assertSame(0, app(CanonicalIndividualScoringService::class)->youthBonusScore($onlyInfo['application']->fresh()));

        $onlyTraining = $this->youthReadyToLock();
        $this->actingAs($onlyTraining['chairman']->user)
            ->post(route('evaluation.store', $onlyTraining['application']), $this->bonusPayload(['bonus_training' => '1'], 'draft'))
            ->assertSessionHasNoErrors();
        $this->assertSame(0, app(CanonicalIndividualScoringService::class)->youthBonusScore($onlyTraining['application']->fresh()));

        $both = $this->youthReadyToLock();
        $this->actingAs($both['chairman']->user)
            ->post(route('evaluation.store', $both['application']), $this->bonusPayload([
                'bonus_info_day' => '1',
                'bonus_training' => '1',
            ], 'draft'))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, app(CanonicalIndividualScoringService::class)->youthBonusScore($both['application']->fresh()));
    }

    public function test_planned_registration_bonus_is_plus_two_only_for_eligible_category(): void
    {
        $eligible = $this->youthReadyToLock();
        $this->assertTrue(app(CanonicalIndividualScoringService::class)->youthQualifiesForPlannedRegistrationBonus($eligible['application']));
        $this->actingAs($eligible['chairman']->user)
            ->post(route('evaluation.store', $eligible['application']), $this->bonusPayload(['bonus_new_business' => '1'], 'draft'))
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $eligible['application']->fresh()->bonus_new_business);
        $this->assertSame(2, app(CanonicalIndividualScoringService::class)->youthBonusScore($eligible['application']->fresh()));

        $ineligible = $this->youthReadyToLock();
        $ineligible['application']->forceFill([
            'applicant_type' => KnApplicationClassification::FORM_PREDUZETNIK,
            'is_registered' => true,
        ])->save();
        $this->actingAs($ineligible['chairman']->user)
            ->from(route('evaluation.create', $ineligible['application']))
            ->post(route('evaluation.store', $ineligible['application']), $this->bonusPayload(['bonus_new_business' => '1'], 'draft'))
            ->assertSessionHasErrors('bonus_new_business');
        $this->assertFalse((bool) $ineligible['application']->fresh()->bonus_new_business);
        $this->assertSame(0, app(CanonicalIndividualScoringService::class)->youthBonusScore($ineligible['application']->fresh()));
    }

    public function test_green_is_plus_three_and_all_bonuses_cap_at_six_and_fifty_six(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(5),
            2 => $this->allCriteria(5),
            3 => $this->allCriteria(5),
        ]);
        $ctx['application']->forceFill([
            'bonus_zavod_nezaposleni' => true,
            'is_registered' => false,
        ])->save();
        $this->confirmYouthBonuses($ctx, [
            'bonus_info_day' => '1',
            'bonus_training' => '1',
            'bonus_new_business' => '1',
            'bonus_green_innovative' => '1',
        ]);

        $application = $ctx['application']->fresh();
        $service = app(CanonicalIndividualScoringService::class);
        $this->assertTrue((bool) $application->bonus_zavod_nezaposleni);
        $this->assertSame(6, $service->youthBonusScore($application));
        $this->assertSame(8, $application->getBonusScore());
        $this->assertSame('56.00', $application->final_score);
        $this->assertSame('56.0000000000', $service->aggregateYouthApplication($application)['final_score_full']);
        $this->assertNull($application->ranking_position);
        $this->assertSame('evaluated', $application->status);
    }

    public function test_draft_confirm_lock_and_unauthorized_actors(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'draft'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.create', $ctx['application']));
        $draft = $ctx['application']->fresh();
        $this->assertTrue((bool) $draft->bonus_green_innovative);
        $this->assertNull($draft->bonuses_confirmed_at);
        $this->assertNull($draft->final_score);

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'confirm'))
            ->assertForbidden();
        $konkursAdmin = $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'confirm'));
        $konkursAdmin->assertRedirect(route('admin.dashboard'));
        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'confirm'))
            ->assertForbidden();

        $other = $this->youthReadyToLock();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'confirm'))
            ->assertForbidden();

        $inactive = $this->youthReadyToLock();
        $inactive['chairman']->update(['status' => 'inactive']);
        $this->actingAs($inactive['chairman']->fresh('user')->user)
            ->post(route('evaluation.store', $inactive['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'confirm'))
            ->assertForbidden();
        $this->assertNull($inactive['application']->fresh()->bonuses_confirmed_at);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload([
                'bonus_green_innovative' => '1',
                'bonuses_confirmed_by_name' => 'Lažni auditor',
                'bonuses_confirmed_by_user_id' => 999999,
            ], 'confirm'))
            ->assertSessionHasNoErrors();
        $locked = $ctx['application']->fresh();
        $this->assertNotNull($locked->bonuses_confirmed_at);
        $this->assertSame($ctx['chairman']->id, (int) $locked->bonuses_confirmed_by_commission_member_id);
        $this->assertSame($ctx['chairman']->user_id, (int) $locked->bonuses_confirmed_by_user_id);
        $this->assertSame($ctx['chairman']->name, $locked->bonuses_confirmed_by_name);
        $this->assertNotSame('Lažni auditor', $locked->bonuses_confirmed_by_name);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload(['bonus_info_day' => '1'], 'draft'))
            ->assertForbidden();
        $this->assertTrue((bool) $ctx['application']->fresh()->bonus_green_innovative);
        $this->assertFalse((bool) $ctx['application']->fresh()->bonus_info_day);
    }

    public function test_final_score_requires_both_three_scores_and_confirmed_bonuses_in_either_order(): void
    {
        $orderA = $this->youthReadyToLock();
        $this->confirmYouthBonuses($orderA, ['bonus_green_innovative' => '1']);
        $this->assertNull($orderA['application']->fresh()->final_score);
        $this->lockThreeSeats($orderA);
        $afterA = $orderA['application']->fresh();
        $this->assertSame('evaluated', $afterA->status);
        $this->assertNotNull($afterA->evaluated_at);
        $this->assertSame('33.00', $afterA->final_score);
        $this->assertNull($afterA->ranking_position);

        $orderB = $this->youthReadyToLock();
        $this->lockThreeSeats($orderB);
        $beforeConfirm = $orderB['application']->fresh();
        $this->assertSame('evaluated', $beforeConfirm->status);
        $this->assertNotNull($beforeConfirm->evaluated_at);
        $this->assertNull($beforeConfirm->final_score);
        $evaluatedAt = $beforeConfirm->evaluated_at->toDateTimeString();
        $this->confirmYouthBonuses($orderB, ['bonus_green_innovative' => '1']);
        $afterB = $orderB['application']->fresh();
        $this->assertSame('33.00', $afterB->final_score);
        $this->assertSame($evaluatedAt, $afterB->evaluated_at->toDateTimeString());
        $this->assertSame('evaluated', $afterB->status);
        $this->assertNull($afterB->ranking_position);
        $this->assertSame($afterA->final_score, $afterB->final_score);
    }

    public function test_threshold_uses_full_precision_and_does_not_change_status(): void
    {
        $pass = $this->youthReadyToLock();
        $this->lockThreeSeats($pass);
        $this->confirmYouthBonuses($pass);
        $this->assertTrue($pass['application']->fresh()->meetsMinimumScore());
        $this->assertSame('30.00', $pass['application']->fresh()->final_score);
        $this->assertSame('evaluated', $pass['application']->fresh()->status);

        $below = $this->youthReadyToLock();
        $this->lockThreeSeats($below, [1 => ['criterion_1' => 2]]);
        $this->confirmYouthBonuses($below);
        $belowApp = $below['application']->fresh();
        $this->assertSame('29.67', $belowApp->final_score);
        $this->assertFalse($belowApp->meetsMinimumScore());
        $this->assertSame('evaluated', $belowApp->status);
        $this->assertNotSame('approved', $belowApp->status);
        $this->assertNotSame('rejected', $belowApp->status);

        $roundedDisplay = $this->youthReadyToLock();
        $this->lockThreeSeats($roundedDisplay, [1 => ['criterion_1' => 2]]);
        $this->confirmYouthBonuses($roundedDisplay);
        $roundedDisplay['application']->fresh()->forceFill(['final_score' => '30.00'])->save();
        $this->assertSame('30.00', $roundedDisplay['application']->fresh()->final_score);
        $this->assertFalse($roundedDisplay['application']->fresh()->meetsMinimumScore());
        $this->assertSame('evaluated', $roundedDisplay['application']->fresh()->status);
    }

    public function test_secrecy_hides_totals_and_other_scores_before_cycle_complete(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(5),
            2 => $this->allCriteria(5),
            3 => $this->allCriteria(5),
        ]);
        $this->confirmYouthBonuses($ctx, [
            'bonus_info_day' => '1',
            'bonus_training' => '1',
            'bonus_new_business' => '1',
            'bonus_green_innovative' => '1',
        ]);
        $this->assertSame('56.00', $ctx['application']->fresh()->final_score);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('56.00', $memberHtml);
        $this->assertStringNotContainsString('name="youth_bonus_action"', $memberHtml);
        $this->assertStringNotContainsString('Sačuvaj nacrt bonusa', $memberHtml);

        $chairmanHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Dodatni bodovi su zaključani', $chairmanHtml);
        $this->assertStringNotContainsString('56.00', $chairmanHtml);
        $this->assertStringNotContainsString('bonus_zavod_nezaposleni', $chairmanHtml);
        $this->assertStringNotContainsString('Zavoda za zapošljavanje', $chairmanHtml);

        $indexHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('56.00', $indexHtml);
        $this->assertStringContainsString('Ocjenjivanje u toku', $indexHtml);
    }

    public function test_dual_membership_does_not_copy_youth_bonuses_to_zensko(): void
    {
        $youth = $this->youthReadyToLock();
        $zensko = $this->zenskoReadyToScore();
        $sharedUser = $youth['chairman']->user;
        $zensko['chairman']->update(['user_id' => $sharedUser->id, 'name' => $sharedUser->name]);

        $this->actingAs($sharedUser)
            ->post(route('evaluation.store', $youth['application']), $this->bonusPayload(['bonus_green_innovative' => '1'], 'confirm'))
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $youth['application']->fresh()->bonus_green_innovative);
        $this->assertFalse((bool) $zensko['application']->fresh()->bonus_green_innovative);
        $this->assertNull($zensko['application']->fresh()->bonuses_confirmed_at);
    }

    public function test_youth_index_hides_forty_five_day_card_and_zensko_keeps_it(): void
    {
        $youth = $this->youthReadyToLock();
        $youthHtml = $this->actingAs($youth['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Rok za donošenje odluke', $youthHtml);
        $this->assertStringNotContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava', $youthHtml);

        $zensko = $this->zenskoReadyToScore();
        $zenskoHtml = $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Rok za donošenje odluke', $zenskoHtml);
        $this->assertStringContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava', $zenskoHtml);
    }

    public function test_zensko_formula_zavod_ranking_and_null_youth_audit_remain(): void
    {
        $zensko = $this->zenskoReadyToScore();
        foreach ($zensko['members'] as $index => $member) {
            EvaluationScore::query()->create(array_merge($this->allCriteria(5), [
                'application_id' => $zensko['application']->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $index + 1,
                'notes' => null,
            ]));
        }
        $zensko['application']->forceFill([
            'bonus_zavod_nezaposleni' => true,
            'bonus_info_day' => true,
        ])->save();

        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateApplication($zensko['application']->fresh());
        $this->assertNotNull($aggregate);
        $this->assertSame(5.0, $aggregate['criterion_averages'][1]);
        $this->assertSame(50.0, $aggregate['base_score']);
        $this->assertSame(3, $aggregate['bonus']);
        $this->assertSame(53.0, $aggregate['final_score']);

        app(CanonicalIndividualScoringService::class)->persistApplicationAggregatesIfCycleComplete($zensko['competition']->fresh());
        $fresh = $zensko['application']->fresh();
        $this->assertSame('53.00', $fresh->final_score);
        $this->assertNotNull($fresh->ranking_position);
        $this->assertNull($fresh->bonuses_confirmed_at);
        $this->assertNull($fresh->bonuses_confirmed_by_user_id);
        $this->assertNull($fresh->bonuses_confirmed_by_commission_member_id);
        $this->assertNull($fresh->bonuses_confirmed_by_name);
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, CompetitionProgramCatalog::definitions()['omladinsko']['status']);
    }

    /**
     * @param  array{application: Application, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     * @param  array<int, array<string, int>>  $bySeat
     */
    private function lockThreeSeats(array $ctx, array $bySeat = []): void
    {
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $this->finalPayload($bySeat[$seat] ?? []))
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * @param  array{application: Application, chairman: CommissionMember}  $ctx
     * @param  array<string, string>  $flags
     */
    private function confirmYouthBonuses(array $ctx, array $flags = []): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload($flags, 'confirm'))
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bonusPayload(array $overrides = [], string $action = 'draft'): array
    {
        return array_merge([
            'youth_bonus_action' => $action,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function finalPayload(array $overrides = []): array
    {
        return array_merge($this->allCriteria(3), [
            'scoring_confirmed' => '1',
            'notes' => null,
        ], $overrides);
    }

    /**
     * @return array<string, int>
     */
    private function allCriteria(int $value): array
    {
        $payload = [];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = $value;
        }

        return $payload;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthReadyToLock(): array
    {
        $ctx = $this->youthWithPassedM3();
        $this->storeAndConfirmSecond($ctx);
        $this->storeAndCompleteOral($ctx, true);

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthWithPassedM3(): array
    {
        $ctx = $this->makeYouthReadyContext();
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
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthReadyContext(): array
    {
        $ctx = $this->makeYouthContext();
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            [
                'held_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
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
            'name' => 'Mladi agregat '.$year,
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
            'title' => 'Omladinsko agregat '.$year,
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
            'competition_number' => 'UP-A-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan '.$year,
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
     * @return array{competition: Competition, chairman: CommissionMember, members: \Illuminate\Support\Collection<int, CommissionMember>, application: Application}
     */
    private function zenskoReadyToScore(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska agregat '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = collect();
        foreach ($types as $i => $type) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members->push(CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $type,
                'status' => 'active',
            ]));
        }
        $competition = Competition::create([
            'title' => 'Zensko agregat '.uniqid(),
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
            'business_plan_name' => 'Ženski plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $chairman = $members[0]->fresh('user');
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $chairman,
            'members' => $members,
            'application' => $application->fresh(),
        ];
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     */
    private function storeAndConfirmSecond(array $ctx): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), [
                'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'notes' => 'Druga sjednica',
                'present_member_ids' => array_map(fn (CommissionMember $member) => $member->id, $ctx['members']),
            ])->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, application: Application}  $ctx
     */
    private function storeAndCompleteOral(array $ctx, bool $attended): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
                'notes' => $attended ? 'Prisutan' : 'Nedolazak',
            ])->assertSessionHasNoErrors();
        $payload = [
            'scheduled_at' => now()->format('Y-m-d H:i:s'),
            'applicant_attended' => $attended ? '1' : '0',
            'notes' => $attended ? 'Prisutan' : 'Nedolazak',
        ];
        if ($attended) {
            $payload['held_at'] = now()->format('Y-m-d H:i:s');
        }
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $payload)
            ->assertSessionHasNoErrors();
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
