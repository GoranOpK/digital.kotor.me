<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Models\YouthEqualScoreRound;
use App\Models\YouthEqualScoreVote;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Support\CompetitionAnnualInstance;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use App\Support\CommissionCanonicalSeat;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OmladinskoAllocationConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private static int $yearSerial = 2060;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_confirmation_is_blocked_without_permanent_ranking(): void
    {
        $ctx = $this->youthReadyToLock();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::RANKING_NOT_READY_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertNull($ctx['competition']->fresh()->youth_allocation_list_confirmed_at);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
    }

    public function test_confirmation_is_blocked_without_complete_drafts(): void
    {
        $ctx = $this->rankingReady();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::INCOMPLETE_DRAFTS_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
    }

    public function test_confirmation_is_blocked_without_confirmed_facts(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->forceFill([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '5000.00',
        ])->save();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::FACTS_REQUIRED_MESSAGE,
            $response->exception?->getMessage()
        );
    }

    public function test_confirmation_is_blocked_when_amount_exceeds_limits(): void
    {
        $ctx = $this->rankingReady();
        $this->saveSupport($ctx, $ctx['application'], 5000);
        $ctx['application']->forceFill(['approved_amount' => '50000.00'])->save();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::AMOUNT_INVALID_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
    }

    public function test_unresolved_boundary_group_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::UNRESOLVED_GROUP_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('evaluated', $ctx['tied'][0]->fresh()->status);
    }

    public function test_fewer_than_three_votes_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Dva glasa.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $round = YouthEqualScoreRound::query()->firstOrFail();
        $round->forceFill([
            'locked_at' => now(),
            'outcome' => YouthEqualScoreRound::OUTCOME_ADOPTED,
            'locked_by_user_id' => $ctx['chairman']->user_id,
            'locked_by_member_id' => $ctx['chairman']->id,
            'locked_by_name' => $ctx['chairman']->name,
        ])->save();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::THREE_VOTES_REQUIRED_MESSAGE,
            $response->exception?->getMessage()
        );
    }

    public function test_locked_rejected_round_without_adopted_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['against', 'against', 'for']);
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::REJECTED_WITHOUT_ADOPTED_MESSAGE,
            $response->exception?->getMessage()
        );
    }

    public function test_adopted_two_one_and_three_zero_confirm_the_list(): void
    {
        $twoOne = $this->twoAppsFundsForOne();
        $this->saveAndLock($twoOne, [$twoOne['tied'][0]->id], ['for', 'for', 'against']);
        $this->confirmList($twoOne);
        $this->assertNotNull($twoOne['competition']->fresh()->youth_allocation_list_confirmed_at);
        $this->assertSame('approved', $twoOne['tied'][0]->fresh()->status);
        $this->assertSame('rejected', $twoOne['tied'][1]->fresh()->status);

        $threeZero = $this->twoAppsFundsForOne();
        $this->saveAndLock($threeZero, [$threeZero['tied'][0]->id], ['for', 'for', 'for']);
        $this->confirmList($threeZero);
        $this->assertSame('approved', $threeZero['tied'][0]->fresh()->status);
        $this->assertSame('rejected', $threeZero['tied'][1]->fresh()->status);
    }

    public function test_changed_draft_after_adopted_round_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['tied'][0]))
            ->post(route('evaluation.youth-allocation-draft', $ctx['tied'][0]), $this->capDraft([
                'approved_amount' => 20000,
                'commission_justification' => 'Izmijenjen iznos nakon usvojenog kruga.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::SNAPSHOT_CHANGED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('evaluated', $ctx['tied'][0]->fresh()->status);
    }

    public function test_statuses_scores_ranks_budget_and_lock_after_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $below = $this->addScoredApplication($ctx, 'Ispod praga', 5000, 2);
        $rejectedDraft = $this->addScoredApplication($ctx, 'Nacrtno odbijena', 5000, 3, 5);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $rejectedDraft))
            ->post(route('evaluation.youth-allocation-draft', $rejectedDraft), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija nije podržala prijavu.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $rejectedDraft = $rejectedDraft->fresh();
        $this->assertTrue(bccomp((string) $rejectedDraft->final_score, '30', 10) >= 0);
        $this->assertNotNull($rejectedDraft->ranking_position);
        $this->assertNotSame((int) $ctx['tied'][0]->fresh()->ranking_position, (int) $rejectedDraft->ranking_position);
        $this->assertNotSame((int) $ctx['tied'][1]->fresh()->ranking_position, (int) $rejectedDraft->ranking_position);
        $this->assertSame('odbija', $rejectedDraft->commission_decision);
        $this->assertSame('Komisija nije podržala prijavu.', $rejectedDraft->commission_justification);
        $this->assertNull($rejectedDraft->approved_amount);
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $scoreBefore = $ctx['tied'][0]->fresh()->final_score;
        $rankBefore = $ctx['tied'][0]->fresh()->ranking_position;
        $belowRank = $below->fresh()->ranking_position;
        $annual = app(CompetitionAnnualInstance::class);
        $beforeRemaining = $annual->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['tied'][0]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-allocation-list-confirm-form"', $html);
        $this->assertStringContainsString('Potvrdi konačnu listu raspodjele za mlade', $html);
        $this->assertStringNotContainsString('youth nacrt', $html);

        $this->travelTo(now()->addMinutes(3));
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']), [
                'youth_allocation_list_confirmed_at' => '2000-01-01 00:00:00',
                'youth_allocation_list_confirmed_by_user_id' => 999999,
                'youth_allocation_list_confirmed_by_commission_member_id' => 999999,
                'youth_allocation_list_confirmed_by_name' => 'Napadač',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $confirmed = $ctx['competition']->fresh();
        $this->assertNotNull($confirmed->youth_allocation_list_confirmed_at);
        $this->assertSame((int) $ctx['chairman']->user_id, (int) $confirmed->youth_allocation_list_confirmed_by_user_id);
        $this->assertSame((int) $ctx['chairman']->id, (int) $confirmed->youth_allocation_list_confirmed_by_commission_member_id);
        $this->assertSame($ctx['chairman']->name, $confirmed->youth_allocation_list_confirmed_by_name);
        $this->assertNotSame('2000-01-01 00:00:00', $confirmed->youth_allocation_list_confirmed_at->format('Y-m-d H:i:s'));
        $this->assertNotSame('Napadač', $confirmed->youth_allocation_list_confirmed_by_name);

        $supported = $ctx['tied'][0]->fresh();
        $this->assertSame('approved', $supported->status);
        $this->assertSame('podrzava_potpuno', $supported->commission_decision);
        $this->assertSame('30000.00', $supported->approved_amount);
        $this->assertSame($scoreBefore, $supported->final_score);
        $this->assertSame($rankBefore, $supported->ranking_position);

        $unselected = $ctx['tied'][1]->fresh();
        $this->assertSame('rejected', $unselected->status);
        $this->assertSame('odbija', $unselected->commission_decision);
        $this->assertNull($unselected->approved_amount);
        $this->assertSame(
            YouthAllocationListConfirmationService::INSUFFICIENT_FUNDS_REASON,
            $unselected->rejection_reason
        );

        $belowFresh = $below->fresh();
        $this->assertSame('rejected', $belowFresh->status);
        $this->assertSame($belowRank, $belowFresh->ranking_position);
        $this->assertSame(
            YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
            $belowFresh->rejection_reason
        );
        $this->assertNotSame('podrzava_potpuno', $belowFresh->commission_decision);

        $draftRejected = $rejectedDraft->fresh();
        $this->assertSame('rejected', $draftRejected->status);
        $this->assertSame('odbija', $draftRejected->commission_decision);
        $this->assertSame('Komisija nije podržala prijavu.', $draftRejected->commission_justification);
        $this->assertSame(
            YouthAllocationListConfirmationService::COMMISSION_REJECT_REASON,
            $draftRejected->rejection_reason
        );

        $allocated = '0.00';
        foreach ([$ctx['application'], $ctx['tied'][0]] as $app) {
            $fresh = $app->fresh();
            if ($fresh->status === 'approved') {
                $allocated = bcadd($allocated, (string) $fresh->approved_amount, 2);
            }
        }
        $this->assertTrue(bccomp($allocated, (string) $ctx['competition']->budget, 2) <= 0);
        $afterRemaining = $annual->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year);
        $this->assertNotSame($beforeRemaining, $afterRemaining);
        $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertFalse(app(CompetitionAnnualInstance::class)->canCreateSecondCall($ctx['competition']->fresh()));

        $auditAt = $confirmed->youth_allocation_list_confirmed_at->format('Y-m-d H:i:s');
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertForbidden();
        $this->assertSame($auditAt, $ctx['competition']->fresh()->youth_allocation_list_confirmed_at->format('Y-m-d H:i:s'));
        $this->assertSame('approved', $supported->fresh()->status);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $supported), $this->capDraft([
                'approved_amount' => 10000,
            ]))
            ->assertForbidden();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Poslije potvrde.',
                'selected' => [$ctx['tied'][0]->id],
            ])
            ->assertForbidden();
        $this->assertSame(3, YouthEqualScoreVote::query()
            ->whereHas('round.group', fn ($q) => $q->where('competition_id', $ctx['competition']->id))
            ->count());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertRedirect();
        $this->assertSame('completed', $ctx['competition']->fresh()->status);
        $this->assertSame(
            $afterRemaining,
            $annual->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year)
        );
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }

    public function test_close_keeps_distinct_full_precision_ranks_that_display_the_same(): void
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);

        $higherFull = $this->addScoredBySeat($ctx, 'Puni viši prikaz isti', [
            1 => array_merge($this->allCriteria(3), ['criterion_1' => 5]),
            2 => array_merge($this->allCriteria(3), ['criterion_1' => 5]),
            3 => $this->allCriteria(3),
        ]);
        $lowerFull = $this->addScoredBySeat($ctx, 'Puni niži prikaz isti', [
            1 => array_merge($this->allCriteria(3), ['criterion_1' => 5, 'criterion_2' => 5]),
            2 => $this->allCriteria(3),
            3 => $this->allCriteria(3),
        ]);
        $below = $this->addScoredApplication($ctx, 'Ispod praga', 5000, 2);

        $canonical = app(CanonicalIndividualScoringService::class);
        $higherAggregate = $canonical->aggregateYouthApplication($higherFull->fresh());
        $lowerAggregate = $canonical->aggregateYouthApplication($lowerFull->fresh());
        $this->assertNotNull($higherAggregate);
        $this->assertNotNull($lowerAggregate);
        $this->assertNotSame($higherAggregate['final_score_full'], $lowerAggregate['final_score_full']);
        $this->assertSame($higherAggregate['final_score_display'], $lowerAggregate['final_score_display']);
        $this->assertSame('31.33', $higherAggregate['final_score_display']);
        $this->assertSame($higherAggregate['final_score_display'], $higherFull->fresh()->final_score);
        $this->assertSame($lowerAggregate['final_score_display'], $lowerFull->fresh()->final_score);

        $this->assertSame(1, (int) $ctx['application']->fresh()->ranking_position);
        $this->assertSame(2, (int) $higherFull->fresh()->ranking_position);
        $this->assertSame(3, (int) $lowerFull->fresh()->ranking_position);
        $this->assertNull($below->fresh()->ranking_position);
        $this->assertNotSame(
            (int) $higherFull->fresh()->ranking_position,
            (int) $lowerFull->fresh()->ranking_position
        );

        $this->saveSupport($ctx, $ctx['application'], 5000);
        $this->saveSupport($ctx, $higherFull, 5000);
        $this->saveSupport($ctx, $lowerFull, 5000);
        $this->confirmList($ctx);

        $snapshot = $this->youthCloseFingerprint($ctx['competition'], [
            $ctx['application'],
            $higherFull,
            $lowerFull,
            $below,
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $ctx['competition']->fresh()->status);
        $this->assertSame($snapshot, $this->youthCloseFingerprint($ctx['competition'], [
            $ctx['application'],
            $higherFull,
            $lowerFull,
            $below,
        ]));
        $this->assertSame(2, (int) $higherFull->fresh()->ranking_position);
        $this->assertSame(3, (int) $lowerFull->fresh()->ranking_position);
        $this->assertNull($below->fresh()->ranking_position);
    }

    public function test_close_keeps_true_equal_full_scores_as_one_two_two_four(): void
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $tiedA = $this->addScoredApplication($ctx, 'Jednaka A', 5000, 4);
        $tiedB = $this->addScoredApplication($ctx, 'Jednaka B', 5000, 4);
        $fourth = $this->addScoredApplication($ctx, 'Cetvrti rang', 5000, 3);
        $below = $this->addScoredApplication($ctx, 'Ispod praga', 5000, 2);

        $this->assertSame(1, (int) $ctx['application']->fresh()->ranking_position);
        $this->assertSame(2, (int) $tiedA->fresh()->ranking_position);
        $this->assertSame(2, (int) $tiedB->fresh()->ranking_position);
        $this->assertSame(4, (int) $fourth->fresh()->ranking_position);
        $this->assertNull($below->fresh()->ranking_position);

        foreach ([$ctx['application'], $tiedA, $tiedB, $fourth] as $application) {
            $this->saveSupport($ctx, $application, 5000);
        }
        $this->confirmList($ctx);

        $snapshot = $this->youthCloseFingerprint($ctx['competition'], [
            $ctx['application'],
            $tiedA,
            $tiedB,
            $fourth,
            $below,
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $ctx['competition']->fresh()->status);
        $this->assertSame($snapshot, $this->youthCloseFingerprint($ctx['competition'], [
            $ctx['application'],
            $tiedA,
            $tiedB,
            $fourth,
            $below,
        ]));
        $this->assertSame([1, 2, 2, 4], [
            (int) $ctx['application']->fresh()->ranking_position,
            (int) $tiedA->fresh()->ranking_position,
            (int) $tiedB->fresh()->ranking_position,
            (int) $fourth->fresh()->ranking_position,
        ]);
        $this->assertNull($below->fresh()->ranking_position);
    }

    public function test_authorization_dual_membership_and_womens_flow_untouched(): void
    {
        $ctx = $this->rankingReady();
        $this->saveSupport($ctx, $ctx['application'], 5000);
        $other = $this->rankingReady();
        $this->saveSupport($other, $other['application'], 5000);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-allocation-list-confirmation"', $memberHtml);
        $this->assertStringNotContainsString('data-testid="youth-allocation-list-confirm-form"', $memberHtml);

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertForbidden();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertForbidden();

        $dualUser = $ctx['chairman']->user;
        $other['members'][1]->update([
            'user_id' => $dualUser->id,
            'name' => $dualUser->name,
            'position' => 'clan',
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);
        $this->actingAs($dualUser)
            ->post(route('evaluation.youth-allocation-list', $other['competition']))
            ->assertForbidden();

        $former = $ctx['chairman']->user;
        $replacement = $this->userWithRole('komisija');
        $ctx['chairman']->update([
            'user_id' => $replacement->id,
            'name' => $replacement->name,
            'status' => 'active',
        ]);
        $this->actingAs($former)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertForbidden();
        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertRedirect(route('admin.dashboard'));
        $this->actingAs($ctx['application']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertForbidden();
        $this->assertNull($ctx['competition']->fresh()->youth_allocation_list_confirmed_at);

        [$zenskoCompetition, $president, $zenskoApplication] = $this->completeZenskoApplication();
        $response = $this->actingAs($president->user)
            ->post(route('evaluation.youth-allocation-list', $zenskoCompetition));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::NOT_YOUTH_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertNull($zenskoCompetition->fresh()->youth_allocation_list_confirmed_at);
        $this->assertSame('submitted', $zenskoApplication->fresh()->status);

        $this->actingAs($president->user)
            ->from(route('evaluation.show', $zenskoApplication))
            ->post(route('evaluation.store-decision', $zenskoApplication), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 20000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('20000.00', $zenskoApplication->fresh()->approved_amount);
        $this->assertNull(app(ZpEqualScoreAllocationGuard::class)->blockReason($zenskoCompetition->fresh()));
    }

    private function confirmList(array $ctx): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  list<Application>  $applications
     * @return array<string, mixed>
     */
    private function youthCloseFingerprint(Competition $competition, array $applications): array
    {
        $fresh = $competition->fresh();

        return [
            'confirmed_at' => $fresh->youth_allocation_list_confirmed_at?->format('Y-m-d H:i:s'),
            'confirmed_user' => $fresh->youth_allocation_list_confirmed_by_user_id,
            'confirmed_member' => $fresh->youth_allocation_list_confirmed_by_commission_member_id,
            'confirmed_name' => $fresh->youth_allocation_list_confirmed_by_name,
            'apps' => collect($applications)->map(fn (Application $application) => [
                'id' => $application->id,
                'final_score' => $application->fresh()->final_score,
                'ranking_position' => $application->fresh()->ranking_position,
                'approved_amount' => $application->fresh()->approved_amount,
                'status' => $application->fresh()->status,
            ])->all(),
        ];
    }

    private function addScoredBySeat(array $ctx, string $name, array $bySeat): Application
    {
        $application = $this->addPassedApplication($ctx, $name);
        $application->update(['requested_amount' => 5000]);
        $this->lockScore($ctx, $application, 3, $bySeat);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $application]);

        return $application->fresh();
    }

    /**
     * @param  list<string>  $votes
     */
    private function saveAndLock(array $ctx, array $selected, array $votes): YouthEqualScoreRound
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Podrška izjednačenoj prijavi.',
                'selected' => $selected,
                'votes' => [1 => $votes[0], 2 => $votes[1], 3 => $votes[2]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $round = YouthEqualScoreRound::query()
            ->whereHas('group', fn ($q) => $q->where('competition_id', $ctx['competition']->id))
            ->whereNull('locked_at')
            ->firstOrFail();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return $round->fresh();
    }

    private function twoAppsFundsForOne(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 30000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $second = $this->addScoredApplication($ctx, 'Drugi rang', 30000, 4);
        $tiedA = $this->addScoredApplication($ctx, 'Izjednačena A', 30000, 3);
        $tiedB = $this->addScoredApplication($ctx, 'Izjednačena B', 30000, 3);
        $this->saveCapSupport($ctx, $ctx['application']);
        $this->saveCapSupport($ctx, $second);
        $this->saveCapSupport($ctx, $tiedA);
        $this->saveCapSupport($ctx, $tiedB);
        $ctx['tied'] = [$tiedA, $tiedB];

        return $ctx;
    }

    private function rankingReady(int $callNumber = 1, ?int $year = null): array
    {
        $ctx = $this->youthReadyToLock($callNumber, $year);
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockScore($ctx, $ctx['application'], 3);
        $this->confirmYouthBonuses($ctx);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        return $ctx;
    }

    private function saveSupport(array $ctx, Application $application, int $amount): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.youth-allocation-draft', $application), $this->supportDraft([
                'approved_amount' => $amount,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function saveCapSupport(array $ctx, Application $application): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $application), $this->capDraft())
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function capDraft(array $overrides = []): array
    {
        return array_merge([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 30000,
            'youth_innovative_tech_startup' => '1',
            'youth_prior_municipal_youth_funding' => '0',
        ], $overrides);
    }

    private function supportDraft(array $overrides = []): array
    {
        return array_merge([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 5000,
            'youth_innovative_tech_startup' => '0',
            'youth_prior_municipal_youth_funding' => '0',
        ], $overrides);
    }

    private function addScoredApplication(array $ctx, string $name, int $requested, int $criterion, int $overrideSeat1 = 0): Application
    {
        $application = $this->addPassedApplication($ctx, $name);
        $application->update(['requested_amount' => $requested]);
        $bySeat = [];
        if ($overrideSeat1 > 0) {
            $bySeat[1] = array_merge($this->allCriteria($criterion), ['criterion_1' => $overrideSeat1]);
            $bySeat[2] = $this->allCriteria($criterion);
            $bySeat[3] = $this->allCriteria($criterion);
        }
        $this->lockScore($ctx, $application, $criterion, $bySeat);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $application]);

        return $application->fresh();
    }

    private function lockScore(array $ctx, Application $application, int $criterion, array $bySeat = []): void
    {
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $payload = $this->finalPayload($bySeat[$seat] ?? $this->allCriteria($criterion));
            $payload['notes'] = 'Napomena mjesta '.$seat;
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $application), $payload)
                ->assertSessionHasNoErrors();
        }
    }

    private function confirmYouthBonuses(array $ctx, array $flags = []): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->bonusPayload($flags, 'confirm'))
            ->assertSessionHasNoErrors();
    }

    private function bonusPayload(array $overrides = [], string $action = 'draft'): array
    {
        return array_merge(['youth_bonus_action' => $action], $overrides);
    }

    private function finalPayload(array $overrides = []): array
    {
        return array_merge($this->allCriteria(3), [
            'scoring_confirmed' => '1',
            'notes' => null,
        ], $overrides);
    }

    private function allCriteria(int $value): array
    {
        $payload = [];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = $value;
        }

        return $payload;
    }

    private function addPassedApplication(array $ctx, string $name = 'Dodatni plan'): Application
    {
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => $name.' '.self::$yearSerial,
            'applicant_type' => KnApplicationClassification::FORM_FIZICKO_LICE,
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(24),
            'requested_amount' => 5000,
        ]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'criterion_notes' => [1 => '', 2 => '', 3 => ''],
            ])
            ->assertRedirect();
        $this->storeAndCompleteOral($ctx, true, $application);

        return $application->fresh('user');
    }

    private function youthReadyToLock(int $callNumber = 1, ?int $year = null): array
    {
        $ctx = $this->youthWithPassedM3($callNumber, $year);
        $this->storeAndConfirmSecond($ctx);
        $this->storeAndCompleteOral($ctx, true);

        return $ctx;
    }

    private function youthWithPassedM3(int $callNumber = 1, ?int $year = null): array
    {
        $ctx = $this->makeYouthReadyContext($callNumber, $year);
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

    private function makeYouthReadyContext(int $callNumber = 1, ?int $year = null): array
    {
        $ctx = $this->makeYouthContext($callNumber, $year);
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

    private function makeYouthContext(int $callNumber = 1, ?int $year = null): array
    {
        $year ??= self::$yearSerial++;
        if ($callNumber === 1) {
            self::$yearSerial = max(self::$yearSerial, $year + 1);
        }
        $commission = Commission::create([
            'name' => 'Mladi potvrda '.$year.'-'.$callNumber,
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
            'title' => 'Omladinsko potvrda '.$year.' poziv '.$callNumber,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => $callNumber,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-C-'.$year.'-'.$callNumber,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan potvrda '.$year.'-'.$callNumber,
            'applicant_type' => KnApplicationClassification::FORM_FIZICKO_LICE,
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
            'requested_amount' => 5000,
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application->fresh('user'),
        ];
    }

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

    private function storeAndCompleteOral(array $ctx, bool $attended, ?Application $application = null): void
    {
        $application ??= $ctx['application'];
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $application]), [
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
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $application]), $payload)
            ->assertSessionHasNoErrors();
    }

    private function completeZenskoApplication(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska potvrda '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $members = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create([
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
        $commission = $commission->fresh(['activeMembers.user', 'members']);
        CommissionCanonicalSeat::persistForCommission($commission);
        $commission = $commission->fresh(['activeMembers.user', 'members']);
        $president = $commission->activeMembers->firstWhere('position', 'predsjednik');
        $competition = Competition::create([
            'title' => 'Zensko potvrda '.uniqid(),
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
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-'.uniqid()]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Zenski plan '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
            'requested_amount' => 30000,
        ]);
        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 4;
        }
        foreach ($commission->activeMembers as $member) {
            $this->actingAs($member->user)
                ->post(route('evaluation.store', $application), $payload)
                ->assertRedirect();
        }

        return [$competition->fresh(), $president->fresh('user'), $application->fresh()];
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
