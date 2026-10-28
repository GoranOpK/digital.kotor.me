<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CompetitionAnnualInstance;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthAnnualAllocationCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private static int $yearSerial = 2140;

    private CompetitionAnnualInstance $annual;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        $this->annual = new CompetitionAnnualInstance;
    }

    public function test_partial_decisions_do_not_reduce_the_annual_remainder_until_the_last_one(): void
    {
        $ctx = $this->rankingReady();
        $second = $this->addScoredApplication($ctx, 'Druga prijava', 5);
        $this->markSupported($ctx['application'], '20000.00');

        $this->assertFalse($this->annual->youthAllocationListIsComplete($ctx['competition']->fresh()));
        $this->assertSame('0.00', $this->annual->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertSame('100000.00', $this->annual->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year));

        $this->markSupported($second, '15000.00');
        $competition = $ctx['competition']->fresh();

        $this->assertTrue($this->annual->youthAllocationListIsComplete($competition));
        $this->assertSame('35000.00', $this->annual->confirmedAllocation($competition));
        $this->assertSame('35000.00', $this->annual->confirmedAllocation($competition->fresh()));
        $this->assertSame('65000.00', $this->annual->remainingAfterFirst('omladinsko', (int) $competition->year));

        $ctx['application']->fresh()->forceFill(['approved_amount' => '25000.00'])->save();

        $this->assertSame('40000.00', $this->annual->confirmedAllocation($competition->fresh()));
        $this->assertSame('60000.00', $this->annual->remainingAfterFirst('omladinsko', (int) $competition->year));

        $this->markRejected($second->fresh());

        $this->assertTrue($this->annual->youthAllocationListIsComplete($competition->fresh()));
        $this->assertSame('25000.00', $this->annual->confirmedAllocation($competition->fresh()));
        $this->assertSame('75000.00', $this->annual->remainingAfterFirst('omladinsko', (int) $competition->year));
    }

    public function test_evaluated_draft_and_unformed_ranking_are_not_a_final_list(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->forceFill([
            'status' => 'evaluated',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '20000.00',
            'youth_innovative_tech_startup' => 0,
            'youth_prior_municipal_youth_funding' => 0,
            'youth_innovative_tech_startup_confirmed_at' => now(),
            'youth_prior_municipal_youth_funding_confirmed_at' => now(),
        ])->save();

        $this->assertFalse($this->annual->youthAllocationListIsComplete($ctx['competition']->fresh()));
        $this->assertSame('0.00', $this->annual->confirmedAllocation($ctx['competition']->fresh()));

        $bare = Competition::create([
            'title' => 'Bez ranga',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2148,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'competition_number' => 'UP-BARE-2148',
        ]);
        Application::create([
            'competition_id' => $bare->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Goli approved',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '50000.00',
            'submitted_at' => now()->subDays(20),
        ]);

        $this->assertFalse($this->annual->youthAllocationListIsComplete($bare));
        $this->assertSame('0.00', $this->annual->confirmedAllocation($bare));
    }

    public function test_confirmed_list_sums_once_and_ignores_other_calls(): void
    {
        $ctx = $this->rankingReady();
        $below = $this->addScoredApplication($ctx, 'Ispod praga', 2);
        $ctx['application']->update(['requested_amount' => 20000]);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 20000,
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ])
            ->assertSessionHasNoErrors();

        $year = (int) $ctx['competition']->year;
        $this->assertSame('approved', $ctx['application']->fresh()->status);
        $this->assertTrue($ctx['application']->fresh()->signed_by_chairman);
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertTrue($this->annual->youthAllocationListIsComplete($ctx['competition']->fresh()));
        $this->assertSame('20000.00', $this->annual->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertSame('80000.00', $this->annual->remainingAfterFirst('omladinsko', $year));

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            \App\Services\Competitions\YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );

        $this->assertNull($below->fresh()->commission_decision);
        $this->assertSame('approved', $ctx['application']->fresh()->status);
        $this->assertSame('20000.00', $ctx['application']->fresh()->approved_amount);
        $this->assertTrue($this->annual->youthAllocationListIsComplete($ctx['competition']->fresh()));
        $this->assertSame('20000.00', $this->annual->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertSame('80000.00', $this->annual->remainingAfterFirst('omladinsko', $year));

        $second = Competition::create([
            'title' => 'Drugi poziv',
            'description' => 'Opis',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => $year,
            'call_number' => 2,
            'annual_budget' => '100000.00',
            'budget' => '40000.00',
            'deadline_days' => 20,
            'competition_number' => 'UP-SECOND-'.$year,
        ]);
        Application::create([
            'competition_id' => $second->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Drugi poziv plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '40000.00',
            'submitted_at' => now(),
        ]);
        $otherYear = Competition::create([
            'title' => 'Druga godina',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year + 1,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'competition_number' => 'UP-OTHER-'.($year + 1),
        ]);
        Application::create([
            'competition_id' => $otherYear->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Druga godina plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '70000.00',
            'submitted_at' => now(),
        ]);
        $zensko = Competition::create([
            'title' => 'Zensko pored',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => $year,
            'budget' => '50000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'competition_number' => 'Z-ANNUAL-'.$year,
        ]);
        Application::create([
            'competition_id' => $zensko->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Zenski plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '30000.00',
            'submitted_at' => now(),
        ]);

        $this->assertSame('20000.00', $this->annual->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertSame('80000.00', $this->annual->remainingAfterFirst('omladinsko', $year));
    }

    public function test_complete_allocation_above_the_first_budget_is_rejected(): void
    {
        $ctx = $this->rankingReady();
        $second = $this->addScoredApplication($ctx, 'Druga velika', 5);
        $this->markSupported($ctx['application'], '60000.00');
        $this->markSupported($second, '50000.00');

        $this->assertTrue($this->annual->youthAllocationListIsComplete($ctx['competition']->fresh()));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(CompetitionAnnualInstance::MSG_ALLOCATION_EXCEEDS_FIRST_BUDGET);

        $this->annual->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year);
    }

    public function test_complete_sixty_thousand_leaves_forty_thousand_for_the_second_call(): void
    {
        $ctx = $this->rankingReady();
        $this->markSupported($ctx['application'], '60000.00');
        $year = (int) $ctx['competition']->year;

        $this->assertSame('40000.00', $this->annual->remainingAfterFirst('omladinsko', $year));

        $second = new Competition([
            'title' => 'Drugi',
            'description' => 'Opis',
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => $year,
            'call_number' => 2,
            'annual_budget' => '100000.00',
            'budget' => '40000.01',
            'deadline_days' => 20,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Budžet drugog Poziva ne smije premašiti preostala godišnja sredstva.');
        $this->annual->validateSecondCall($second);
    }

    public function test_individual_decisions_finalize_the_list_without_confirmation_or_voting(): void
    {
        $ctx = $this->rankingReady();
        $second = $this->addScoredApplication($ctx, 'Druga prijava', 5);
        $below = $this->addScoredApplication($ctx, 'Ispod praga', 2);
        $this->markSupported($ctx['application'], '20000.00');

        $competition = $ctx['competition']->fresh();
        $this->assertFalse($competition->hasChairmanCompletedDecisions());
        $this->assertSame('published', $competition->status);
        $this->assertNull($competition->youth_allocation_list_confirmed_at);
        $this->assertSame(0, \App\Models\YouthEqualScoreRound::query()->count());

        $this->markSupported($second, '15000.00');
        $competition = $ctx['competition']->fresh();

        $this->assertTrue($competition->hasChairmanCompletedDecisions());
        $this->assertNull($below->fresh()->commission_decision);
        $this->assertSame('evaluated', $below->fresh()->status);
        $this->assertNull($competition->youth_allocation_list_confirmed_at);
        $this->assertSame(0, \App\Models\YouthEqualScoreRound::query()->count());
        $this->assertSame('published', $competition->status);
        $this->assertSame('35000.00', $this->annual->confirmedAllocation($competition));
        $this->assertSame('65000.00', $this->annual->remainingAfterFirst('omladinsko', (int) $competition->year));
        $this->assertFalse($this->annual->canCreateSecondCall($competition));
        $this->assertSame(
            CompetitionAnnualInstance::MSG_FIRST_NOT_FINISHED,
            $this->annual->secondCallCreationBlockReason($competition)
        );
    }

    public function test_unformed_ranking_tie_and_budget_block_finality(): void
    {
        $bare = Competition::create([
            'title' => 'Bez ranga finalnost',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2149,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'competition_number' => 'UP-BARE-2149',
        ]);
        $this->assertFalse($bare->hasChairmanCompletedDecisions());

        $tied = $this->rankingReady();
        $sameScore = $this->addScoredApplication($tied, 'Isti bodovi', 5);
        $this->markSupported($tied['application'], '60000.00');
        $this->markSupported($sameScore, '60000.00');
        $this->assertFalse($tied['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame(0, \App\Models\YouthEqualScoreRound::query()->count());

        $this->markRejected($sameScore->fresh());
        $this->assertTrue($tied['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame('60000.00', $this->annual->confirmedAllocation($tied['competition']->fresh()));
        $this->assertSame(0, \App\Models\YouthEqualScoreRound::query()->count());

        $over = $this->rankingReady();
        $lower = $this->addScoredApplication($over, 'Niži rang', 4);
        $this->markSupported($over['application'], '60000.00');
        $this->markSupported($lower, '50000.00');
        $this->assertTrue($this->annual->youthAllocationListIsComplete($over['competition']->fresh()));
        $this->assertFalse($over['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_final_list_can_close_and_then_open_a_second_call(): void
    {
        $ctx = $this->rankingReady();
        $below = $this->addScoredApplication($ctx, 'Ispod praga', 2);
        $this->markSupported($ctx['application'], '20000.00');
        $competition = $ctx['competition']->fresh();
        $this->assertTrue($competition->hasChairmanCompletedDecisions());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $closed = $competition->fresh();
        $this->assertSame('completed', $closed->status);
        $this->assertSame('evaluated', $below->fresh()->status);
        $this->assertSame('80000.00', $this->annual->remainingAfterFirst('omladinsko', (int) $closed->year));
        $this->assertTrue($this->annual->canCreateSecondCall($closed));

        $second = new Competition([
            'title' => 'Drugi poslije zatvaranja',
            'description' => 'Opis',
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => (int) $closed->year,
            'call_number' => 2,
            'annual_budget' => '100000.00',
            'budget' => '80000.00',
            'deadline_days' => 20,
        ]);
        $this->annual->validateSecondCall($second);
        $this->assertSame('20000.00', $this->annual->confirmedAllocation($closed));
    }

    public function test_incomplete_list_blocks_close_and_historical_confirmation_stays_final(): void
    {
        $ctx = $this->rankingReady();
        $second = $this->addScoredApplication($ctx, 'Bez odluke', 5);
        $this->markSupported($ctx['application'], '20000.00');
        $this->assertFalse($ctx['competition']->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertNull($second->fresh()->commission_decision);

        $historical = $this->rankingReady();
        $historical['application']->forceFill([
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '10000.00',
        ])->save();
        $historical['competition']->forceFill([
            'youth_allocation_list_confirmed_at' => now(),
            'youth_allocation_list_confirmed_by_user_id' => $historical['chairman']->user_id,
            'youth_allocation_list_confirmed_by_commission_member_id' => $historical['chairman']->id,
            'youth_allocation_list_confirmed_by_name' => $historical['chairman']->name,
        ])->save();

        $this->assertFalse($this->annual->youthAllocationListIsComplete($historical['competition']->fresh()));
        $this->assertTrue($historical['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function rankingReady(): array
    {
        $ctx = $this->passedEliminatory();
        $ctx['application']->update(['requested_amount' => 60000]);
        $this->lockThreeSeats($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx, $ctx['application']);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
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
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(): array
    {
        $year = self::$yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi godina '.$year,
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
            'title' => 'Omladinsko godina '.$year,
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
            'competition_number' => 'UP-G-'.$year.'-1',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan godina '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
            'requested_amount' => 60000,
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application->fresh('user'),
        ];
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}  $ctx
     */
    private function addScoredApplication(array $ctx, string $name, int $criterion): Application
    {
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => $name,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(24),
            'requested_amount' => 60000,
        ]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'criterion_notes' => [1 => '', 2 => '', 3 => ''],
            ])
            ->assertRedirect();
        $this->lockThreeSeats($ctx, $application, $criterion);
        $this->confirmYouthBonuses($ctx, $application);

        return $application->fresh();
    }

    /**
     * @param  array{chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     */
    private function lockThreeSeats(array $ctx, Application $application, int $criterion): void
    {
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $payload = ['notes' => null, 'scoring_confirmed' => '1'];
            for ($i = 1; $i <= 10; $i++) {
                $payload["criterion_{$i}"] = $criterion;
            }
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $application), $payload)
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * @param  array{chairman: CommissionMember}  $ctx
     */
    private function confirmYouthBonuses(array $ctx, Application $application): void
    {
        if ($application->fresh()->bonuses_confirmed_at !== null) {
            return;
        }

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $application), [
                'youth_bonus_action' => 'confirm',
            ])
            ->assertSessionHasNoErrors();
    }

    private function markSupported(Application $application, string $amount): void
    {
        $application->forceFill([
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => $amount,
            'commission_justification' => null,
            'rejection_reason' => null,
            'youth_innovative_tech_startup' => 0,
            'youth_prior_municipal_youth_funding' => 0,
            'youth_innovative_tech_startup_confirmed_at' => now(),
            'youth_prior_municipal_youth_funding_confirmed_at' => now(),
            'signed_by_chairman' => true,
        ])->save();
    }

    private function markRejected(Application $application): void
    {
        $application->forceFill([
            'status' => 'rejected',
            'commission_decision' => 'odbija',
            'approved_amount' => null,
            'commission_justification' => 'Nema dovoljno sredstava.',
            'signed_by_chairman' => true,
        ])->save();
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
