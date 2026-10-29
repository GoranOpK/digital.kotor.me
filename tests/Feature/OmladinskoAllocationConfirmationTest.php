<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Models\YouthEqualScoreGroup;
use App\Models\YouthEqualScoreRound;
use App\Models\YouthEqualScoreRoundApplication;
use App\Models\YouthEqualScoreVote;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use App\Services\Competitions\YouthEqualScoreVotingService;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Support\CompetitionAnnualInstance;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use App\Support\CommissionCanonicalSeat;
use Database\Seeders\RoleSeeder;
use DomainException;
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
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
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
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
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
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
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
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('approved', $ctx['application']->fresh()->status);
    }

    public function test_unresolved_boundary_group_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
    }

    public function test_fewer_than_three_votes_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Dva glasa.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for'],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
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
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
    }

    public function test_adopted_two_one_and_three_zero_confirm_the_list(): void
    {
        $twoOne = $this->twoAppsFundsForOne();
        $this->saveAndLock($twoOne, [$twoOne['tied'][0]->id], ['for', 'for', 'against']);
        $before = $this->decisionSnapshot($twoOne['competition']);
        $response = $this->actingAs($twoOne['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $twoOne['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame($before, $this->decisionSnapshot($twoOne['competition']));
        $this->assertNull($twoOne['competition']->fresh()->youth_allocation_list_confirmed_at);
        $this->assertSame('approved', $twoOne['tied'][0]->fresh()->status);
        $this->assertSame('approved', $twoOne['tied'][1]->fresh()->status);
        $this->assertSame('30000.00', $twoOne['tied'][1]->fresh()->approved_amount);

        $threeZero = $this->twoAppsFundsForOne();
        $beforeThree = $this->decisionSnapshot($threeZero['competition']);
        $this->saveAndLock($threeZero, [$threeZero['tied'][0]->id], ['for', 'for', 'for']);
        $this->actingAs($threeZero['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $threeZero['competition']))
            ->assertForbidden();
        $this->assertSame('approved', $threeZero['tied'][0]->fresh()->status);
        $this->assertSame('approved', $threeZero['tied'][1]->fresh()->status);
        $this->assertSame('podrzava_potpuno', $threeZero['tied'][1]->fresh()->commission_decision);
        $this->assertSame($beforeThree, $this->decisionSnapshot($threeZero['competition']));
    }

    public function test_changed_draft_after_adopted_round_blocks_confirmation(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $rewrite = $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['tied'][0]))
            ->post(route('evaluation.youth-allocation-draft', $ctx['tied'][0]), $this->capDraft([
                'approved_amount' => 20000,
                'commission_justification' => 'Izmijenjen iznos nakon usvojenog kruga.',
            ]));
        $rewrite->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::DECISION_LOCKED_MESSAGE, $rewrite->exception?->getMessage());
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
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
        $this->assertSame('0.00', $rejectedDraft->approved_amount);
        $this->assertSame('rejected', $rejectedDraft->status);
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $scoreBefore = $ctx['tied'][0]->fresh()->final_score;
        $rankBefore = $ctx['tied'][0]->fresh()->ranking_position;
        $belowRank = $below->fresh()->ranking_position;
        $annual = app(CompetitionAnnualInstance::class);
        $this->assertTrue($annual->youthAllocationListIsComplete($ctx['competition']->fresh()));
        $this->assertSame('120000.00', $annual->confirmedAllocation($ctx['competition']->fresh()));
        try {
            $annual->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year);
            $this->fail('Prekoračenje budžeta mora ostati vidljivo prije potvrde liste.');
        } catch (DomainException $exception) {
            $this->assertSame(CompetitionAnnualInstance::MSG_ALLOCATION_EXCEEDS_FIRST_BUDGET, $exception->getMessage());
        }

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['tied'][0]))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-allocation-list-confirm-form"', $html);
        $this->assertStringNotContainsString('Potvrdi konačnu listu raspodjele za mlade', $html);
        $this->assertStringNotContainsString('youth nacrt', $html);

        $before = $this->decisionSnapshot($ctx['competition']);
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-list', $ctx['competition']), [
                'youth_allocation_list_confirmed_at' => '2000-01-01 00:00:00',
                'youth_allocation_list_confirmed_by_user_id' => 999999,
                'youth_allocation_list_confirmed_by_commission_member_id' => 999999,
                'youth_allocation_list_confirmed_by_name' => 'Napadač',
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationListConfirmationService::LIST_CONFIRMATION_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame($before, $this->decisionSnapshot($ctx['competition']));

        $supported = $ctx['tied'][0]->fresh();
        $this->assertSame('approved', $supported->status);
        $this->assertSame('podrzava_potpuno', $supported->commission_decision);
        $this->assertSame('30000.00', $supported->approved_amount);
        $this->assertSame($scoreBefore, $supported->final_score);
        $this->assertSame($rankBefore, $supported->ranking_position);

        $unselected = $ctx['tied'][1]->fresh();
        $this->assertSame('approved', $unselected->status);
        $this->assertSame('podrzava_potpuno', $unselected->commission_decision);
        $this->assertSame('30000.00', $unselected->approved_amount);
        $this->assertNotSame(
            YouthAllocationListConfirmationService::INSUFFICIENT_FUNDS_REASON,
            (string) $unselected->rejection_reason
        );

        $belowFresh = $below->fresh();
        $this->assertSame('evaluated', $belowFresh->status);
        $this->assertSame($belowRank, $belowFresh->ranking_position);
        $this->assertNotSame(
            YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
            (string) $belowFresh->rejection_reason
        );

        $draftRejected = $rejectedDraft->fresh();
        $this->assertSame('rejected', $draftRejected->status);
        $this->assertSame('odbija', $draftRejected->commission_decision);
        $this->assertSame('0.00', $draftRejected->approved_amount);
        $this->assertSame('Komisija nije podržala prijavu.', $draftRejected->commission_justification);
        $this->assertSame('Komisija nije podržala prijavu.', $draftRejected->rejection_reason);

        $this->assertNull($ctx['competition']->fresh()->youth_allocation_list_confirmed_at);
        $this->assertSame('120000.00', $annual->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertFalse($ctx['competition']->fresh()->hasChairmanCompletedDecisions());

        $rewrite = $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $supported))
            ->post(route('evaluation.youth-allocation-draft', $supported), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Pojedinačna izmjena i dalje važi.',
            ]));
        $rewrite->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::DECISION_LOCKED_MESSAGE, $rewrite->exception?->getMessage());
        $this->assertSame('30000.00', $supported->fresh()->approved_amount);
        $this->assertSame('approved', $supported->fresh()->status);
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
        $this->seedHistoricalListConfirmation($ctx);

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
        $this->seedHistoricalListConfirmation($ctx);

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

    /**
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
    private function seedHistoricalListConfirmation(array $ctx): void
    {
        $scoring = app(CanonicalIndividualScoringService::class);
        foreach (Application::query()->where('competition_id', $ctx['competition']->id)->orderBy('id')->get() as $application) {
            if ($scoring->youthMeetsMinimumScore($application) || in_array($application->status, ['approved', 'rejected'], true)) {
                continue;
            }
            $application->forceFill([
                'status' => 'rejected',
                'rejection_reason' => YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
            ])->save();
        }

        $ctx['competition']->forceFill([
            'youth_allocation_list_confirmed_at' => now(),
            'youth_allocation_list_confirmed_by_user_id' => $ctx['chairman']->user_id,
            'youth_allocation_list_confirmed_by_commission_member_id' => $ctx['chairman']->id,
            'youth_allocation_list_confirmed_by_name' => $ctx['chairman']->name,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function decisionSnapshot(Competition $competition): array
    {
        $competition = $competition->fresh();

        return [
            'status' => $competition->status,
            'confirmed_at' => $competition->youth_allocation_list_confirmed_at?->format('Y-m-d H:i:s'),
            'confirmed_by_user_id' => $competition->youth_allocation_list_confirmed_by_user_id,
            'confirmed_by_member_id' => $competition->youth_allocation_list_confirmed_by_commission_member_id,
            'confirmed_by_name' => $competition->youth_allocation_list_confirmed_by_name,
            'rows' => Application::query()
                ->where('competition_id', $competition->id)
                ->orderBy('id')
                ->get()
                ->map(fn (Application $application) => [
                    'id' => $application->id,
                    'status' => $application->status,
                    'commission_decision' => $application->commission_decision,
                    'approved_amount' => $application->approved_amount,
                    'commission_justification' => $application->commission_justification,
                    'rejection_reason' => $application->rejection_reason,
                    'ranking_position' => $application->ranking_position,
                    'final_score' => $application->final_score,
                ])->all(),
        ];
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
     * Istorijski zapis. Ne poziva ukinuti upis glasanja.
     *
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     * @param  list<int>  $selected
     * @param  list<string>  $votes
     */
    private function saveAndLock(array $ctx, array $selected, array $votes): YouthEqualScoreRound
    {
        $detected = app(YouthEqualScoreVotingService::class)
            ->detectBoundaryGroups($ctx['competition']->fresh());
        $groupData = $detected[0] ?? null;
        $this->assertIsArray($groupData);
        $selectedIds = array_map('intval', $selected);
        $proposal = '0.00';
        foreach ($groupData['applications'] as $snapshot) {
            if (in_array((int) $snapshot['application_id'], $selectedIds, true)) {
                $proposal = bcadd($proposal, (string) $snapshot['draft_amount'], 2);
            }
        }
        $forCount = count(array_filter($votes, fn (string $vote) => $vote === YouthEqualScoreVote::FOR));
        $group = YouthEqualScoreGroup::query()->create([
            'competition_id' => $ctx['competition']->id,
            'group_key' => $groupData['group_key'],
            'full_score' => $groupData['full_score'],
            'ranking_position' => $groupData['ranking_position'],
            'applied_rule' => $groupData['applied_rule'],
        ]);
        $round = YouthEqualScoreRound::query()->create([
            'group_id' => $group->id,
            'round_no' => 1,
            'justification' => 'Podrška izjednačenoj prijavi.',
            'budget_before' => $groupData['remaining_before'],
            'proposal_total' => $proposal,
            'locked_at' => now(),
            'outcome' => $forCount >= 2
                ? YouthEqualScoreRound::OUTCOME_ADOPTED
                : YouthEqualScoreRound::OUTCOME_REJECTED,
            'created_by_user_id' => $ctx['chairman']->user_id,
            'created_by_member_id' => $ctx['chairman']->id,
            'created_by_name' => $ctx['chairman']->name,
            'locked_by_user_id' => $ctx['chairman']->user_id,
            'locked_by_member_id' => $ctx['chairman']->id,
            'locked_by_name' => $ctx['chairman']->name,
        ]);
        foreach ($groupData['applications'] as $snapshot) {
            YouthEqualScoreRoundApplication::query()->create([
                'round_id' => $round->id,
                'application_id' => $snapshot['application_id'],
                'business_stage' => $snapshot['business_stage'],
                'requested_amount' => $snapshot['requested_amount'],
                'draft_amount' => $snapshot['draft_amount'],
                'applied_cap_percent' => $snapshot['applied_cap_percent'],
                'selected' => in_array((int) $snapshot['application_id'], $selectedIds, true),
            ]);
        }
        foreach ([1, 2, 3] as $index => $seat) {
            $member = $ctx['members'][$index];
            YouthEqualScoreVote::query()->create([
                'round_id' => $round->id,
                'canonical_seat_no' => $seat,
                'commission_member_id' => $member->id,
                'user_id' => $member->user_id,
                'member_name' => $member->name,
                'vote_value' => $votes[$index],
                'recorded_by_user_id' => $ctx['chairman']->user_id,
                'voted_at' => now(),
            ]);
        }

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
        $this->forceCompetingSupport($tiedB);
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

    private function forceCompetingSupport(Application $application, string $amount = '30000.00'): void
    {
        $application->forceFill([
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => $amount,
            'signed_by_chairman' => true,
            'commission_decision_date' => now(),
            'youth_innovative_tech_startup' => true,
            'youth_innovative_tech_startup_confirmed_at' => now(),
            'youth_prior_municipal_youth_funding' => false,
            'youth_prior_municipal_youth_funding_confirmed_at' => now(),
            'youth_applied_cap_percent' => 30,
        ])->save();
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

        return $application->fresh('user');
    }

    private function youthReadyToLock(int $callNumber = 1, ?int $year = null): array
    {
        return $this->youthWithPassedM3($callNumber, $year);
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
        return $this->makeYouthContext($callNumber, $year);
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

    private function completeZenskoApplication(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska potvrda '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
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
