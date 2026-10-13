<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalFinalRankingSignature;
use App\Models\CanonicalInvestmentPlan;
use App\Models\CanonicalOutcome;
use App\Models\CanonicalPredlog;
use App\Models\CanonicalTieResolution;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Outcome\CanonicalAllocationPolicy as A;
use App\Services\Competitions\Outcome\CanonicalAmountCapPolicy;
use App\Services\Competitions\Outcome\CanonicalOutcomeService;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\Outcome\CanonicalTieResolutionService;
use App\Services\Competitions\ProcessWriteResult;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType as T;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-008 — canonical MMSP third session, outcome per application, čl.19 amounts,
 * one support per PIB, shared-rank scarcity resolution, atomic confirmation, 3/3 signatures
 * and the private Predlog foundation.
 */
class CanonicalOutcomeAndPredlogTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    private int $pibCounter = 0;

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

    // A — third session

    public function test_a_third_session_requires_formed_ranking_and_confirmed_interview(): void
    {
        [, $members, $competition] = $this->rankedCompetition([['scores' => [40, 40, 40]]], thirdSession: false, formRanking: false);

        $this->assertNull($competition->fresh()->canonical_ranking_formed_at);
        $result = $this->recordThirdSession($competition, $members);
        $this->assertSame(CanonicalCommissionSessionService::RANKING_BEFORE_FINAL_MESSAGE, $result->error);
    }

    public function test_a_outcome_requires_confirmed_full_third_session_and_chair(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([['scores' => [40, 40, 40]]], thirdSession: false);

        $this->assertSame(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, $this->draft($competition, $members, $apps[0], 'support', '1000')->error);

        $partial = app(CanonicalCommissionSessionService::class)->record($competition, $members[G::ROLE_PREDSJEDNIK]->user, G::PURPOSE_FINAL_DECISION, now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_ELECTRONIC,
            $members[G::ROLE_SPOLJASNJI]->id => null,
        ]);
        $this->assertTrue($partial->succeeded, (string) $partial->error);
        $session = CommissionSession::query()->findOrFail($partial->recordId);
        $this->assertSame(CommissionSession::TYPE_THIRD, $session->session_type);
        $confirm = app(CanonicalCommissionSessionService::class)->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertSame(G::FULL_ATTENDANCE_MESSAGE, $confirm->error);
        $this->assertSame(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, $this->draft($competition, $members, $apps[0], 'support', '1000')->error);

        $nonChair = app(CanonicalOutcomeService::class)->recordDraft($competition, $members[G::ROLE_SEKRETAR]->user, $apps[0]->id, ['decision' => 'support', 'amount' => '1000']);
        $this->assertSame(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, $nonChair->error);
    }

    public function test_a_non_chair_cannot_record_outcome_or_confirm(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([['scores' => [40, 40, 40]]]);
        $service = app(CanonicalOutcomeService::class);

        $this->assertSame(CanonicalOutcomeService::CHAIR_ONLY_MESSAGE,
            $service->recordDraft($competition, $members[G::ROLE_SEKRETAR]->user, $apps[0]->id, ['decision' => 'support', 'amount' => '1000'])->error);
        $this->assertSame(CanonicalOutcomeService::CHAIR_ONLY_MESSAGE, $service->confirm($competition, $members[G::ROLE_SPOLJASNJI]->user)->error);
    }

    public function test_a_late_third_session_is_recorded_with_warning_and_does_not_block(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([['scores' => [40, 40, 40]]], thirdSession: false);

        Carbon::setTestNow(Carbon::parse('2026-09-18 10:00:00', 'Europe/Belgrade'));
        $result = $this->recordThirdSession($competition, $members);
        $this->assertTrue($result->succeeded, (string) $result->error);
        $this->assertSame(CanonicalCommissionSessionService::LATE_THIRD_SESSION_WARNING, $result->warning);
        $session = CommissionSession::query()->findOrFail($result->recordId);
        $this->assertTrue((bool) $session->late);
        $this->assertTrue(app(CanonicalCommissionSessionService::class)->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user)->succeeded);

        $this->assertTrue($this->draft($competition, $members, $apps[0], 'support', '1000')->succeeded);
        $confirm = $this->confirm($competition, $members);
        $this->assertTrue($confirm->succeeded, (string) $confirm->error);
        $this->assertSame(CanonicalCommissionSessionService::LATE_THIRD_SESSION_WARNING, $confirm->warning);
    }

    // B — below 30

    public function test_b_below_30_cannot_be_supported_and_exact_30_is_eligible(): void
    {
        [, $members, $competition, [$below, $exact]] = $this->rankedCompetition([
            ['scores' => [30, 30, 29]],
            ['scores' => [30, 30, 30]],
        ]);
        $below->refresh();
        $exact->refresh();
        $this->assertSame('29.6666666666', (string) $below->final_score_calc);
        $this->assertSame('30.0000000000', (string) $exact->final_score_calc);

        $this->assertSame(A::BELOW_30_SUPPORT_MESSAGE, $this->draft($competition, $members, $below, 'support', '100')->error);
        $this->assertSame(A::BELOW_30_REASON_REQUIRED_MESSAGE, $this->draft($competition, $members, $below, 'reject', null, CanonicalOutcome::REASON_COMMISSION_REJECTED, 'Obrazloženje')->error);
        $this->assertSame(A::BELOW_30_REASON_INVALID_MESSAGE, $this->draft($competition, $members, $exact, 'reject', null, CanonicalOutcome::REASON_BELOW_30)->error);

        $this->assertTrue($this->draft($competition, $members, $below, 'reject', null, CanonicalOutcome::REASON_BELOW_30)->succeeded);
        $this->assertTrue($this->draft($competition, $members, $exact, 'support', '2000')->succeeded);
        $this->assertTrue($this->confirm($competition, $members)->succeeded);

        $this->assertSame('rejected', $below->fresh()->status);
        $this->assertSame('29.6666666666', (string) $below->fresh()->final_score_calc);
        $this->assertSame('29.67', (string) $below->fresh()->final_score);
        $this->assertSame(2, (int) $below->fresh()->ranking_position);
        $this->assertSame('approved', $exact->fresh()->status);
    }

    // C — caps

    public function test_c_legal_maximum_is_min_of_20_percent_budget_and_type_share_of_frozen_p2_total(): void
    {
        $caps = app(CanonicalAmountCapPolicy::class);

        $this->assertSame('800.00', $caps->legalMaximum('10000.00', T::AUTHENTIC_LOCAL, '1000.00'));
        $this->assertSame('500.00', $caps->legalMaximum('10000.00', T::INNOVATIVE, '1000.00'));
        $this->assertSame('500.00', $caps->legalMaximum('10000.00', T::DIGITALIZATION, '1000.00'));
        $this->assertSame('2000.00', $caps->legalMaximum('10000.00', T::AUTHENTIC_LOCAL, '5000.00'));
        $this->assertSame('2000.00', $caps->legalMaximum('10000.00', T::INNOVATIVE, '4000.00'));
        $this->assertSame('2000.01', $caps->budgetCap('10000.05'));
        $this->assertSame('166.66', $caps->investmentCap(T::INNOVATIVE, '333.33'));
        $this->assertSame('266.66', $caps->investmentCap(T::AUTHENTIC_LOCAL, '333.33'));
        $this->assertNull($caps->legalMaximum('10000.00', 'ostalo', '1000.00'));
        $this->assertNull($caps->legalMaximum('10000.00', T::INNOVATIVE, null));
        $this->assertNull($caps->legalMaximum('10000.00', T::INNOVATIVE, '0.00'));
    }

    public function test_c_amounts_above_cap_zero_negative_and_manipulated_are_rejected(): void
    {
        [, $members, $competition, [$app]] = $this->rankedCompetition([
            ['scores' => [40, 40, 40], 'type' => T::AUTHENTIC_LOCAL, 'total' => '1000.00'],
        ]);

        $this->assertSame(CanonicalAmountCapPolicy::AMOUNT_ABOVE_CAP_MESSAGE, $this->draft($competition, $members, $app, 'support', '800.01')->error);
        $this->assertSame(CanonicalAmountCapPolicy::AMOUNT_NOT_POSITIVE_MESSAGE, $this->draft($competition, $members, $app, 'support', '0')->error);
        $this->assertSame(CanonicalAmountCapPolicy::AMOUNT_NOT_POSITIVE_MESSAGE, $this->draft($competition, $members, $app, 'support', '0.00')->error);
        foreach (['-5', '1e3', '800.001', 'abc', '', null, '99999999999999'] as $bad) {
            $this->assertSame(CanonicalAmountCapPolicy::AMOUNT_INVALID_MESSAGE, $this->draft($competition, $members, $app, 'support', $bad)->error, var_export($bad, true));
        }
        $this->assertSame(A::REJECT_AMOUNT_MESSAGE, $this->draft($competition, $members, $app, 'reject', '100', CanonicalOutcome::REASON_COMMISSION_REJECTED, 'x')->error);
        $this->assertSame(0, CanonicalOutcome::query()->count());

        $this->assertTrue($this->draft($competition, $members, $app, 'support', '800,00')->succeeded);
        $this->assertSame('800.00', (string) CanonicalOutcome::query()->firstOrFail()->approved_amount);
    }

    public function test_c_support_requires_frozen_p2_basis(): void
    {
        [, $members, $competition, [$app]] = $this->rankedCompetition([['scores' => [40, 40, 40], 'frozen' => false]]);

        $this->assertSame(CanonicalAmountCapPolicy::BASIS_MISSING_MESSAGE, $this->draft($competition, $members, $app, 'support', '100')->error);
        $this->assertSame(CanonicalAmountCapPolicy::BASIS_MISSING_MESSAGE, $this->draft($competition, $members, $app, 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS)->error);
        $this->assertTrue($this->draft($competition, $members, $app, 'reject', null, CanonicalOutcome::REASON_COMMISSION_REJECTED, 'Nedostaje P2.')->succeeded);
    }

    // D — total budget

    public function test_d_exact_budget_exhaustion_is_allowed_and_overspend_is_blocked(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->sixDistinct());
        $byRank = $this->byRank($apps);

        foreach (range(1, 5) as $rank) {
            $this->assertTrue($this->draft($competition, $members, $byRank[$rank], 'support', '2000.00')->succeeded, "rank {$rank}");
        }
        $this->assertSame(A::BUDGET_EXCEEDED_MESSAGE, $this->draft($competition, $members, $byRank[6], 'support', '0.01')->error);
        $this->assertTrue($this->draft($competition, $members, $byRank[6], 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS)->succeeded);

        DB::table('canonical_outcomes')->where('application_id', $byRank[6]->id)->update([
            'decision' => CanonicalOutcome::SUPPORT, 'approved_amount' => '100.00', 'rejection_reason' => null,
        ]);
        $this->assertSame(A::BUDGET_EXCEEDED_MESSAGE, $this->confirm($competition, $members)->error);

        DB::table('canonical_outcomes')->where('application_id', $byRank[6]->id)->update([
            'decision' => CanonicalOutcome::REJECT, 'approved_amount' => null, 'rejection_reason' => CanonicalOutcome::REASON_INSUFFICIENT_FUNDS,
        ]);
        $confirm = $this->confirm($competition, $members);
        $this->assertTrue($confirm->succeeded, (string) $confirm->error);
        $ranking = CanonicalFinalRanking::query()->firstOrFail();
        $this->assertSame('10000.00', (string) $ranking->total_approved);
        $this->assertSame('10000.00', (string) $ranking->budget);
    }

    public function test_d_residual_stays_unused_and_insufficient_funds_needs_real_scarcity(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([
            ['scores' => [40, 40, 40]],
            ['scores' => [45, 45, 45]],
        ]);
        $byRank = $this->byRank($apps);

        $this->draft($competition, $members, $byRank[1], 'support', '1500.00');
        $this->draft($competition, $members, $byRank[2], 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->assertStringStartsWith(A::INSUFFICIENT_FUNDS_INVALID_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $byRank[2], 'support', '1999.99');
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
        $this->assertSame('3499.99', (string) CanonicalFinalRanking::query()->firstOrFail()->total_approved);
    }

    // E — two applications, one beneficiary

    public function test_e_same_pib_both_qualify_only_one_supported_other_stays_ranked(): void
    {
        $owner = $this->applicant();
        [, $members, $competition, [$first, $second]] = $this->rankedCompetition([
            ['scores' => [45, 45, 45], 'owner' => $owner, 'pib' => '71234567', 'type' => T::AUTHENTIC_LOCAL],
            ['scores' => [40, 40, 40], 'owner' => $owner, 'pib' => '71234567', 'type' => T::INNOVATIVE],
        ]);
        $this->assertSame([1, 2], [(int) $first->fresh()->ranking_position, (int) $second->fresh()->ranking_position]);

        $this->draft($competition, $members, $first, 'support', '2000');
        $this->draft($competition, $members, $second, 'support', '2000');
        $this->assertSame(A::DUPLICATE_SUPPORT_MESSAGE, $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $second, 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->assertStringStartsWith(A::DUPLICATE_REASON_REQUIRED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $second, 'reject', null, CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT);
        $this->assertTrue($this->confirm($competition, $members)->succeeded);

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('rejected', $second->fresh()->status);
        $this->assertSame(2, (int) $second->fresh()->ranking_position);
        $this->assertSame(2, Application::query()->where('competition_id', $competition->id)->count());
        $this->assertSame(CanonicalOutcome::REASON_LABELS[CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT], $second->fresh()->rejection_reason);
    }

    public function test_e_commission_may_explicitly_choose_the_lower_ranked_application_of_the_same_pib(): void
    {
        $owner = $this->applicant();
        [, $members, $competition, [$first, $second]] = $this->rankedCompetition([
            ['scores' => [45, 45, 45], 'owner' => $owner, 'pib' => '71234568', 'type' => T::AUTHENTIC_LOCAL],
            ['scores' => [40, 40, 40], 'owner' => $owner, 'pib' => '71234568', 'type' => T::DIGITALIZATION],
        ]);

        $this->draft($competition, $members, $first, 'reject', null, CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT);
        $this->assertStringStartsWith(A::INCOMPLETE_MESSAGE, (string) $this->confirm($competition, $members)->error);
        $this->draft($competition, $members, $second, 'support', '2000');
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
        $this->assertSame(['rejected', 'approved'], [$first->fresh()->status, $second->fresh()->status]);
    }

    public function test_e_duplicate_reason_requires_a_supported_sibling(): void
    {
        [, $members, $competition, [$only]] = $this->rankedCompetition([['scores' => [40, 40, 40]]]);

        $this->draft($competition, $members, $only, 'reject', null, CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT);
        $this->assertStringStartsWith(A::DUPLICATE_REASON_INVALID_MESSAGE, (string) $this->confirm($competition, $members)->error);
    }

    // F — rank order

    public function test_f_funding_follows_rank_and_id_is_never_a_priority(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->sixDistinct());
        $byRank = $this->byRank($apps);
        $this->assertGreaterThan($byRank[6]->id, $byRank[1]->id);

        foreach (range(1, 4) as $rank) {
            $this->draft($competition, $members, $byRank[$rank], 'support', '2000');
        }
        $this->draft($competition, $members, $byRank[5], 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->draft($competition, $members, $byRank[6], 'support', '2000');
        $this->assertStringStartsWith(A::RANK_SKIPPED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $byRank[6], 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->draft($competition, $members, $byRank[5], 'support', '2000');
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
    }

    public function test_f_commission_rejection_of_a_better_rank_allows_funding_the_next(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->sixDistinct());
        $byRank = $this->byRank($apps);

        $this->assertSame(A::JUSTIFICATION_REQUIRED_MESSAGE, $this->draft($competition, $members, $byRank[1], 'reject', null, CanonicalOutcome::REASON_COMMISSION_REJECTED)->error);
        $this->draft($competition, $members, $byRank[1], 'reject', null, CanonicalOutcome::REASON_COMMISSION_REJECTED, 'Plan nije održiv.');
        foreach (range(2, 6) as $rank) {
            $this->draft($competition, $members, $byRank[$rank], 'support', '2000');
        }
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
    }

    // G — true tie / shared rank

    public function test_g_shared_rank_is_preserved_and_whole_group_funding_needs_no_resolution(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([
            ['scores' => [45, 45, 45]],
            ['scores' => [42, 42, 42]],
            ['scores' => [42, 42, 42]],
            ['scores' => [40, 40, 40]],
        ]);
        $this->assertSame([1, 2, 2, 4], array_map(fn (Application $a) => (int) $a->fresh()->ranking_position, $apps));

        foreach ($apps as $app) {
            $this->draft($competition, $members, $app, 'support', '2000');
        }
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
        $this->assertSame([1, 2, 2, 4], array_map(fn (Application $a) => (int) $a->fresh()->ranking_position, $apps));
        $this->assertSame(0, CanonicalTieResolution::query()->count());
    }

    public function test_g_single_zapocinjanje_gets_priority_at_the_boundary(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->boundaryTie('razvoj', 'započinjanje'));
        [$razvoj, $starter] = [$apps[4], $apps[5]];
        $this->fundTopFour($competition, $members, $apps);
        $this->assertSame([5, 5], [(int) $razvoj->fresh()->ranking_position, (int) $starter->fresh()->ranking_position]);

        $this->draft($competition, $members, $razvoj, 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->draft($competition, $members, $starter, 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->assertStringStartsWith(A::INSUFFICIENT_FUNDS_INVALID_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $razvoj, 'support', '2000');
        $this->draft($competition, $members, $starter, 'reject', null, CanonicalOutcome::REASON_INSUFFICIENT_FUNDS);
        $this->assertStringStartsWith(A::TIE_REASON_REQUIRED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $starter, 'reject', null, CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED);
        $this->assertStringStartsWith(A::ZAPOCINJANJE_PRIORITY_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->draft($competition, $members, $razvoj, 'reject', null, CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED);
        $this->draft($competition, $members, $starter, 'support', '2000');
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
        $this->assertSame([5, 5], [(int) $razvoj->fresh()->ranking_position, (int) $starter->fresh()->ranking_position]);
        $this->assertSame(0, CanonicalTieResolution::query()->count());
    }

    // H — majority

    public function test_h_no_zapocinjanje_requires_adopted_majority_over_exact_tied_set(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->boundaryTie('razvoj', 'razvoj'));
        [$a, $b] = [$apps[4], $apps[5]];
        $this->fundTopFour($competition, $members, $apps);
        $this->draft($competition, $members, $a, 'reject', null, CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED);
        $this->draft($competition, $members, $b, 'support', '2000');
        $this->assertStringStartsWith(A::MAJORITY_REQUIRED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $ties = app(CanonicalTieResolutionService::class);
        $this->assertSame(CanonicalTieResolutionService::CHAIR_ONLY_MESSAGE, $ties->propose($competition, $members[G::ROLE_SEKRETAR]->user, 5, [$a->id, $b->id], [$b->id])->error);
        $this->assertSame(CanonicalTieResolutionService::TIED_SET_INVALID_MESSAGE, $ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$a->id, $apps[0]->id], [$a->id])->error);
        $this->assertSame(CanonicalTieResolutionService::SELECTION_INVALID_MESSAGE, $ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$a->id, $b->id], [$a->id, $b->id])->error);

        $proposed = $ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$b->id, $a->id], [$b->id]);
        $this->assertTrue($proposed->succeeded, (string) $proposed->error);
        $resolution = CanonicalTieResolution::query()->findOrFail($proposed->recordId);
        $this->assertSame(CanonicalTieResolutionService::PENDING_EXISTS_MESSAGE, $ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$a->id, $b->id], [$a->id])->error);

        $this->assertTrue($ties->vote($resolution, $members[G::ROLE_PREDSJEDNIK]->user, true)->succeeded);
        $this->assertSame(CanonicalTieResolutionService::DUPLICATE_VOTE_MESSAGE, $ties->vote($resolution, $members[G::ROLE_PREDSJEDNIK]->user, false)->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $ties->vote($resolution, $this->komisijaUser('outsider'), true)->error);
        $this->assertTrue($ties->vote($resolution, $members[G::ROLE_SEKRETAR]->user, true)->succeeded);
        $this->assertSame(CanonicalTieResolution::PROPOSED, $resolution->fresh()->status);
        $this->assertStringStartsWith(A::MAJORITY_REQUIRED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->assertTrue($ties->vote($resolution, $members[G::ROLE_SPOLJASNJI]->user, false)->succeeded);
        $this->assertSame(CanonicalTieResolution::ADOPTED, $resolution->fresh()->status);
        $this->assertSame(CanonicalTieResolutionService::NOT_PROPOSED_MESSAGE, $ties->vote($resolution, $members[G::ROLE_SPOLJASNJI]->user, true)->error);

        $this->assertSame(A::BUDGET_EXCEEDED_MESSAGE, $this->draft($competition, $members, $a, 'support', '2000')->error);
        $this->assertTrue($this->draft($competition, $members, $b, 'reject', null, CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED)->succeeded);
        $this->assertTrue($this->draft($competition, $members, $a, 'support', '2000')->succeeded);
        $this->assertStringStartsWith(A::MAJORITY_MISMATCH_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $this->assertTrue($this->draft($competition, $members, $a, 'reject', null, CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED)->succeeded);
        $this->assertTrue($this->draft($competition, $members, $b, 'support', '2000')->succeeded);
        $this->assertTrue($this->confirm($competition, $members)->succeeded);

        $locked = $resolution->fresh();
        $this->assertNotNull($locked->locked_at);
        $this->assertSame([5, 5], [(int) $a->fresh()->ranking_position, (int) $b->fresh()->ranking_position]);
        $this->expectException(LogicException::class);
        $locked->forceFill(['selected_application_ids' => [$a->id]])->save();
    }

    public function test_h_minority_rejects_proposal_and_multiple_zapocinjanje_also_need_majority(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->boundaryTie('započinjanje', 'započinjanje'));
        [$a, $b] = [$apps[4], $apps[5]];
        $this->fundTopFour($competition, $members, $apps);
        $this->draft($competition, $members, $a, 'support', '2000');
        $this->draft($competition, $members, $b, 'reject', null, CanonicalOutcome::REASON_TIE_BOUNDARY_NOT_SELECTED);
        $this->assertStringStartsWith(A::MAJORITY_REQUIRED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $ties = app(CanonicalTieResolutionService::class);
        $first = CanonicalTieResolution::query()->findOrFail($ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$a->id, $b->id], [$a->id])->recordId);
        $ties->vote($first, $members[G::ROLE_PREDSJEDNIK]->user, true);
        $ties->vote($first, $members[G::ROLE_SEKRETAR]->user, false);
        $ties->vote($first, $members[G::ROLE_SPOLJASNJI]->user, false);
        $this->assertSame(CanonicalTieResolution::REJECTED, $first->fresh()->status);
        $this->assertStringStartsWith(A::MAJORITY_REQUIRED_MESSAGE, (string) $this->confirm($competition, $members)->error);

        $second = CanonicalTieResolution::query()->findOrFail($ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$a->id, $b->id], [$a->id])->recordId);
        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $role) {
            $this->assertTrue($ties->vote($second, $members[$role]->user, true)->succeeded);
        }
        $this->assertSame(CanonicalTieResolution::ADOPTED, $second->fresh()->status);
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
    }

    public function test_h_replaced_member_cannot_vote(): void
    {
        [$commission, $members, $competition, $apps] = $this->rankedCompetition($this->boundaryTie('razvoj', 'razvoj'));
        $ties = app(CanonicalTieResolutionService::class);
        $resolution = CanonicalTieResolution::query()->findOrFail($ties->propose($competition, $members[G::ROLE_PREDSJEDNIK]->user, 5, [$apps[4]->id, $apps[5]->id], [$apps[4]->id])->recordId);

        $vacated = app(CanonicalCommissionMembershipService::class)->vacate($members[G::ROLE_SEKRETAR], 'resigned', now(), $this->canonicalActor());
        $this->assertTrue($vacated->succeeded, (string) $vacated->error);

        $this->assertFalse($ties->vote($resolution, $members[G::ROLE_SEKRETAR]->user, true)->succeeded);
        $this->assertSame(0, $resolution->votes()->count());
    }

    // I — outcome completeness / draft

    public function test_i_draft_does_not_change_status_and_confirmation_requires_complete_outcome(): void
    {
        [, $members, $competition, [$a, $b]] = $this->rankedCompetition([
            ['scores' => [45, 45, 45]],
            ['scores' => [40, 40, 40]],
        ]);

        $this->assertTrue($this->draft($competition, $members, $a, 'support', '2000', null, null, 'Interna napomena')->succeeded);
        $this->assertSame(['submitted', null, null], [$a->fresh()->status, $a->fresh()->approved_amount, $a->fresh()->rejection_reason]);
        $this->assertStringStartsWith(A::INCOMPLETE_MESSAGE, (string) $this->confirm($competition, $members)->error);
        $this->assertSame(['submitted', 'submitted'], [$a->fresh()->status, $b->fresh()->status]);
        $this->assertSame(0, CanonicalFinalRanking::query()->count());
        $this->assertNull(CanonicalOutcome::query()->firstOrFail()->confirmed_at);

        $foreign = $this->rankedCompetition([['scores' => [40, 40, 40]]])[3][0];
        $this->assertSame(CanonicalOutcomeService::NOT_RANKED_MESSAGE, $this->draft($competition, $members, $foreign, 'support', '100')->error);
        $this->assertSame(A::DECISION_INVALID_MESSAGE, $this->draft($competition, $members, $b, 'approve', '100')->error);
        $this->assertSame(A::REASON_INVALID_MESSAGE, $this->draft($competition, $members, $b, 'reject', null, 'nepoznat')->error);
    }

    // J — immutability

    public function test_j_confirmed_outcome_ranking_and_status_are_immutable(): void
    {
        [, $members, $competition, [$a]] = $this->rankedCompetition([['scores' => [40, 40, 40]]]);
        $this->draft($competition, $members, $a, 'support', '2000');
        $this->assertTrue($this->confirm($competition, $members)->succeeded);

        $this->assertSame(CanonicalOutcomeService::ALREADY_CONFIRMED_MESSAGE, $this->draft($competition, $members, $a, 'support', '1000')->error);
        $this->assertSame(CanonicalOutcomeService::ALREADY_CONFIRMED_MESSAGE, $this->confirm($competition, $members)->error);

        foreach ([
            fn () => CanonicalOutcome::query()->firstOrFail()->update(['approved_amount' => '1.00']),
            fn () => CanonicalOutcome::query()->firstOrFail()->delete(),
            fn () => $a->fresh()->update(['status' => 'submitted']),
            fn () => $a->fresh()->update(['approved_amount' => '5.00']),
            fn () => $a->fresh()->update(['ranking_position' => 2]),
            fn () => $a->fresh()->forceFill(['final_score_calc' => '41.0000000000'])->save(),
            fn () => $a->fresh()->forceFill(['final_score' => '41.00'])->save(),
            fn () => CanonicalFinalRanking::query()->firstOrFail()->update(['total_approved' => '1.00']),
            fn () => CanonicalFinalRanking::query()->firstOrFail()->delete(),
        ] as $index => $mutation) {
            try {
                $mutation();
                $this->fail("Mutation {$index} should be blocked.");
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('2000.00', (string) $a->fresh()->approved_amount);
    }

    public function test_j_tampered_score_or_rank_blocks_confirmation(): void
    {
        [, $members, $competition, [$a, $b]] = $this->rankedCompetition([
            ['scores' => [45, 45, 45]],
            ['scores' => [40, 40, 40]],
        ]);
        $this->draft($competition, $members, $a, 'support', '2000');
        $this->draft($competition, $members, $b, 'support', '2000');

        DB::table('applications')->where('id', $b->id)->update(['ranking_position' => 1]);
        $this->assertSame(CanonicalOutcomeService::RANKING_CHANGED_MESSAGE, $this->confirm($competition, $members)->error);
        DB::table('applications')->where('id', $b->id)->update(['ranking_position' => 2, 'final_score_calc' => '41.0000000000']);
        $this->assertSame(CanonicalOutcomeService::RANKING_CHANGED_MESSAGE, $this->confirm($competition, $members)->error);
        DB::table('applications')->where('id', $b->id)->update(['final_score_calc' => '40.0000000000']);
        $this->assertTrue($this->confirm($competition, $members)->succeeded);
    }

    // K — status

    public function test_k_confirmation_sets_approved_and_rejected_atomically(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([
            ['scores' => [45, 45, 45]],
            ['scores' => [40, 40, 40]],
            ['scores' => [29, 29, 29]],
        ]);
        $byRank = $this->byRank($apps);
        $this->draft($competition, $members, $byRank[1], 'support', '2000');
        $this->draft($competition, $members, $byRank[2], 'reject', null, CanonicalOutcome::REASON_COMMISSION_REJECTED, 'Neusklađen plan.');
        $this->draft($competition, $members, $byRank[3], 'reject', null, CanonicalOutcome::REASON_BELOW_30);

        foreach ($apps as $app) {
            $this->assertSame('submitted', $app->fresh()->status);
        }
        $this->assertTrue($this->confirm($competition, $members)->succeeded);

        $this->assertSame(['approved', '2000.00', null], [$byRank[1]->fresh()->status, (string) $byRank[1]->fresh()->approved_amount, $byRank[1]->fresh()->rejection_reason]);
        $this->assertSame(['rejected', null, CanonicalOutcome::REASON_LABELS[CanonicalOutcome::REASON_COMMISSION_REJECTED]], [$byRank[2]->fresh()->status, $byRank[2]->fresh()->approved_amount, $byRank[2]->fresh()->rejection_reason]);
        $this->assertSame('rejected', $byRank[3]->fresh()->status);
        $this->assertSame(3, CanonicalOutcome::query()->whereNotNull('confirmed_at')->count());
    }

    // L — signatures

    public function test_l_three_of_three_signatures_finalize_and_invalid_signers_are_blocked(): void
    {
        [, $members, $competition, [$a]] = $this->rankedCompetition([['scores' => [40, 40, 40]]]);
        $service = app(CanonicalOutcomeService::class);

        $this->assertSame(CanonicalOutcomeService::NOT_CONFIRMED_MESSAGE, $service->sign($competition, $members[G::ROLE_PREDSJEDNIK]->user)->error);
        $this->draft($competition, $members, $a, 'support', '2000');
        $this->confirm($competition, $members);

        [$otherCommission, $otherMembers] = $this->canonicalCommission();
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->sign($competition, $otherMembers[G::ROLE_PREDSJEDNIK]->user)->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->sign($competition, $this->komisijaUser('outsider'))->error);

        $this->assertTrue($service->sign($competition, $members[G::ROLE_PREDSJEDNIK]->user)->succeeded);
        $this->assertSame(CanonicalOutcomeService::DUPLICATE_SIGNATURE_MESSAGE, $service->sign($competition, $members[G::ROLE_PREDSJEDNIK]->user)->error);
        $this->assertFalse(CanonicalFinalRanking::query()->firstOrFail()->isFinal());
        $this->assertSame(CanonicalPredlogService::NOT_SIGNED_MESSAGE, app(CanonicalPredlogService::class)->readinessBlockReason($competition));

        $this->assertTrue($service->sign($competition, $members[G::ROLE_SEKRETAR]->user)->succeeded);
        $this->assertFalse(CanonicalFinalRanking::query()->firstOrFail()->isFinal());
        $this->assertSame(0, CanonicalPredlog::query()->count());

        $this->assertTrue($service->sign($competition, $members[G::ROLE_SPOLJASNJI]->user)->succeeded);
        $this->assertTrue(CanonicalFinalRanking::query()->firstOrFail()->isFinal());
        $this->assertSame(CanonicalOutcomeService::ALREADY_FINAL_MESSAGE, $service->sign($competition, $members[G::ROLE_SPOLJASNJI]->user)->error);

        $signatures = CanonicalFinalRankingSignature::query()->orderBy('canonical_seat_no')->get();
        $this->assertSame([1, 2, 3], $signatures->pluck('canonical_seat_no')->map(fn ($seat) => (int) $seat)->all());
        $this->assertSame(G::ROLES, $signatures->pluck('canonical_seat')->all());
        $this->assertSame($members[G::ROLE_SEKRETAR]->user_id, (int) $signatures[1]->user_id);
        $this->assertNotSame('', $signatures[0]->signer_name);
        $this->assertNotNull($signatures[2]->signed_at);

        $this->expectException(LogicException::class);
        $signatures[0]->update(['signer_name' => 'X']);
    }

    public function test_l_invalid_mandate_and_replaced_member_cannot_sign(): void
    {
        [$commission, $members, $competition, [$a]] = $this->rankedCompetition([['scores' => [40, 40, 40]]]);
        $this->draft($competition, $members, $a, 'support', '2000');
        $this->confirm($competition, $members);
        $service = app(CanonicalOutcomeService::class);

        $vacated = app(CanonicalCommissionMembershipService::class)->vacate($members[G::ROLE_SPOLJASNJI], 'resigned', now(), $this->canonicalActor());
        $this->assertTrue($vacated->succeeded, (string) $vacated->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->sign($competition, $members[G::ROLE_SPOLJASNJI]->user)->error);

        Carbon::setTestNow(Carbon::parse('2027-06-01 10:00:00', 'Europe/Belgrade'));
        $this->assertNotNull($service->sign($competition, $members[G::ROLE_PREDSJEDNIK]->user)->error);
        $this->assertSame(0, CanonicalFinalRankingSignature::query()->count());
    }

    // M — Predlog

    public function test_m_predlog_is_ready_only_after_confirmation_and_three_signatures_and_is_private_and_immutable(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition([
            ['scores' => [45, 45, 45], 'type' => T::AUTHENTIC_LOCAL, 'total' => '1000.00'],
            ['scores' => [40, 40, 40], 'type' => T::INNOVATIVE, 'total' => '3000.00'],
            ['scores' => [29, 29, 29]],
        ]);
        $byRank = $this->byRank($apps);
        $predlog = app(CanonicalPredlogService::class);

        $this->assertSame(CanonicalPredlogService::NOT_CONFIRMED_MESSAGE, $predlog->readinessBlockReason($competition));
        $this->draft($competition, $members, $byRank[1], 'support', '800', null, 'Obrazloženje podrške', 'Interna napomena komisije');
        $this->draft($competition, $members, $byRank[2], 'support', '1500');
        $this->draft($competition, $members, $byRank[3], 'reject', null, CanonicalOutcome::REASON_BELOW_30);
        $this->assertSame(CanonicalPredlogService::NOT_CONFIRMED_MESSAGE, $predlog->readinessBlockReason($competition));
        $this->confirm($competition, $members);
        $this->assertSame(CanonicalPredlogService::NOT_SIGNED_MESSAGE, $predlog->readinessBlockReason($competition));
        $this->signAll($competition, $members);

        $this->assertNull($predlog->readinessBlockReason($competition));
        $record = CanonicalPredlog::query()->firstOrFail();
        $payload = $record->payload;
        $this->assertSame(CanonicalPredlogService::SCHEMA, $payload['schema']);
        $this->assertFalse($payload['is_public']);
        $this->assertFalse($payload['is_odluka']);
        $this->assertFalse($payload['is_contract']);
        $this->assertFalse($payload['is_payment']);
        $this->assertSame('2300.00', $payload['final_ranking']['total_approved']);
        $this->assertSame('7700.00', $payload['final_ranking']['residual_unallocated']);
        $this->assertCount(3, $payload['rows']);
        $this->assertSame([1, 2, 3], array_column($payload['rows'], 'rank'));
        $this->assertSame(['800.00', '1500.00', null], array_column($payload['rows'], 'approved_amount'));
        $this->assertSame([T::AUTHENTIC_LOCAL, T::INNOVATIVE], array_slice(array_column($payload['rows'], 'subsidy_type'), 0, 2));
        $this->assertSame('1000.00', $payload['rows'][0]['total_investment']);
        $this->assertSame('45.0000000000', $payload['rows'][0]['final_score_calc']);
        $this->assertSame('45.00', $payload['rows'][0]['final_score_display']);
        $this->assertCount(3, $payload['signatures']);
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Interna napomena komisije', $encoded);
        $this->assertStringNotContainsString('remarks', $encoded);
        $this->assertStringNotContainsString('jmb', strtolower($encoded));
        $this->assertSame($record->fingerprint, CanonicalPredlogService::fingerprint($payload));

        $this->assertSame($record->id, $predlog->prepare(CanonicalFinalRanking::query()->firstOrFail())->id);
        $this->assertSame(1, CanonicalPredlog::query()->count());

        $this->expectException(LogicException::class);
        $record->update(['fingerprint' => str_repeat('0', 64)]);
    }

    // HTTP — thin controller, Commission only, contracts stay closed

    public function test_http_outcome_screen_is_commission_only_and_amounts_cannot_be_manipulated(): void
    {
        [, $members, $competition, [$a]] = $this->rankedCompetition([['scores' => [40, 40, 40], 'type' => T::INNOVATIVE, 'total' => '1000.00']]);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->actingAs($chair)->get(route('evaluation.canonical-outcome.show', $competition))->assertOk()->assertSee('Treća sjednica');
        $this->actingAs($this->applicant())->get(route('evaluation.canonical-outcome.show', $competition))->assertForbidden();
        $this->actingAs($this->komisijaUser('outsider'))->get(route('evaluation.canonical-outcome.show', $competition))->assertForbidden();

        $this->actingAs($chair)->from(route('evaluation.canonical-outcome.show', $competition))
            ->post(route('evaluation.canonical-outcome.draft', [$competition, $a]), ['decision' => 'support', 'amount' => '999999'])
            ->assertSessionHasErrors(['canonical_outcome' => CanonicalAmountCapPolicy::AMOUNT_ABOVE_CAP_MESSAGE]);
        $this->assertSame(0, CanonicalOutcome::query()->count());

        $this->actingAs($chair)->post(route('evaluation.canonical-outcome.draft', [$competition, $a]), ['decision' => 'support', 'amount' => '500'])
            ->assertSessionHasNoErrors();
        $this->actingAs($chair)->post(route('evaluation.canonical-outcome.confirm', $competition))->assertSessionHasNoErrors();
        $this->assertSame('approved', $a->fresh()->status);

        $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->firstOrFail()->id, 'activation_status' => 'active', 'email_verified_at' => now()]);
        $this->actingAs($admin)->get(route('contracts.generate', $a))->assertForbidden();
        $this->actingAs($admin)->post(route('contracts.store', $a))->assertForbidden();
    }

    public function test_http_non_canonical_competition_is_not_found(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $zensko = Competition::create([
            'title' => 'Žensko '.uniqid(), 'type' => CompetitionProfileResolver::TYPE_WOMEN, 'status' => 'published', 'year' => 2026,
            'budget' => 10000, 'deadline_days' => 20, 'commission_id' => $commission->id,
            'start_date' => '2026-08-01', 'end_date' => '2026-08-21', 'published_at' => '2026-08-01 09:00:00',
        ]);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)->get(route('evaluation.canonical-outcome.show', $zensko))->assertNotFound();
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /**
     * @param  list<array{scores: array{0: int, 1: int, 2: int}, type?: string, total?: string, stage?: string, owner?: User, pib?: string, frozen?: bool}>  $specs
     * @return array{0: Commission, 1: array<string, CommissionMember>, 2: Competition, 3: list<Application>}
     */
    private function rankedCompetition(array $specs, int $budget = 10000, bool $thirdSession = true, bool $formRanking = true): array
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = Competition::create([
            'title' => 'Poziv MMSP '.uniqid(),
            'type' => self::CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => $budget,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);

        $apps = [];
        foreach ($specs as $spec) {
            $app = $this->finalApplication($competition, $spec);
            $result = app(CompletenessAssessmentService::class)
                ->record($app, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::COMPLETE);
            $this->assertTrue($result->succeeded, (string) $result->error);
            $apps[] = $app;
        }

        $this->confirmSession($competition, $members, G::PURPOSE_INTERVIEW);

        if ($formRanking) {
            $scoring = app(CanonicalIndividualScoringService::class);
            foreach ($apps as $index => $app) {
                foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $seat => $role) {
                    $scoring->recordCanonicalFinalScore(Application::query()->findOrFail($app->id), $members[$role]->user, $this->criteriaSumming($specs[$index]['scores'][$seat]), null, true);
                }
            }
            $this->assertTrue($scoring->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        }

        if ($thirdSession) {
            $this->confirmSession($competition, $members, G::PURPOSE_FINAL_DECISION);
        }

        return [$commission, $members, $competition->fresh(), array_map(fn (Application $app) => $app->fresh(), $apps)];
    }

    /**
     * @param  array{type?: string, total?: string, stage?: string, owner?: User, pib?: string, frozen?: bool}  $spec
     */
    private function finalApplication(Competition $competition, array $spec): Application
    {
        $owner = $spec['owner'] ?? $this->applicant();
        $pib = $spec['pib'] ?? $this->nextPib();
        $type = $spec['type'] ?? T::AUTHENTIC_LOCAL;
        $total = $spec['total'] ?? '5000.00';

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'doo',
            'business_stage' => $spec['stage'] ?? 'razvoj',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Osnivač '.uniqid(),
            'doo_name' => 'Firma '.uniqid(),
            'pib' => $pib,
            'submitted_at' => now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);

        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
            'subsidy_type' => $type,
            'beneficiary_pib' => $pib,
            'beneficiary_slot' => FinalApplicationClaim::query()->where('competition_id', $competition->id)->where('beneficiary_pib', $pib)->count() + 1,
        ]);

        (new CanonicalInvestmentPlan)->forceFill([
            'application_id' => $application->id,
            'subsidy_type' => $type,
            'total_investment' => $total,
            'own_contribution' => '0.00',
            'expected_subsidy' => $total,
            'expense_lines' => [['cost_type' => 'oprema', 'description' => 'Oprema', 'amount' => $total]],
            'frozen_at' => ($spec['frozen'] ?? true) ? now() : null,
        ])->save();

        return $application->fresh();
    }

    private function applicant(): User
    {
        return User::factory()->create([
            'email' => 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function nextPib(): string
    {
        return '8'.str_pad((string) ++$this->pibCounter, 7, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmSession(Competition $competition, array $members, string $purpose): CommissionSession
    {
        $service = app(CanonicalCommissionSessionService::class);
        $result = $purpose === G::PURPOSE_FINAL_DECISION
            ? $this->recordThirdSession($competition, $members)
            : $service->record($competition, $members[G::ROLE_PREDSJEDNIK]->user, $purpose, now(), $this->fullAttendance($members));
        $this->assertTrue($result->succeeded, (string) $result->error);
        $session = CommissionSession::query()->findOrFail($result->recordId);
        $confirm = $service->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($confirm->succeeded, (string) $confirm->error);

        return $session->fresh();
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function recordThirdSession(Competition $competition, array $members): ProcessWriteResult
    {
        return app(CanonicalCommissionSessionService::class)->record(
            $competition, $members[G::ROLE_PREDSJEDNIK]->user, G::PURPOSE_FINAL_DECISION, CarbonImmutable::now(), $this->fullAttendance($members),
        );
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @return array<int, string>
     */
    private function fullAttendance(array $members): array
    {
        return [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function draft(Competition $competition, array $members, Application $application, string $decision, mixed $amount, ?string $reason = null, ?string $justification = null, ?string $remarks = null): ProcessWriteResult
    {
        return app(CanonicalOutcomeService::class)->recordDraft($competition, $members[G::ROLE_PREDSJEDNIK]->user, $application->id, [
            'decision' => $decision,
            'amount' => $amount,
            'reason' => $reason,
            'justification' => $justification,
            'remarks' => $remarks,
        ]);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirm(Competition $competition, array $members): ProcessWriteResult
    {
        return app(CanonicalOutcomeService::class)->confirm($competition, $members[G::ROLE_PREDSJEDNIK]->user);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function signAll(Competition $competition, array $members): void
    {
        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $role) {
            $result = app(CanonicalOutcomeService::class)->sign($competition, $members[$role]->user);
            $this->assertTrue($result->succeeded, (string) $result->error);
        }
    }

    /**
     * Six distinct ranks; created worst-first so the application id order is the reverse of the rank order.
     *
     * @return list<array{scores: array{0: int, 1: int, 2: int}}>
     */
    private function sixDistinct(): array
    {
        return array_map(fn (int $score) => ['scores' => [$score, $score, $score]], [40, 41, 42, 43, 44, 45]);
    }

    /**
     * Ranks 1–4 distinct, rank 5 shared by two applications with the given stages. Budget 10000, max 2000 each.
     *
     * @return list<array{scores: array{0: int, 1: int, 2: int}, stage?: string}>
     */
    private function boundaryTie(string $firstStage, string $secondStage): array
    {
        return [
            ['scores' => [48, 48, 48]],
            ['scores' => [47, 47, 47]],
            ['scores' => [46, 46, 46]],
            ['scores' => [45, 45, 45]],
            ['scores' => [40, 40, 40], 'stage' => $firstStage],
            ['scores' => [40, 40, 40], 'stage' => $secondStage],
        ];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  list<Application>  $apps
     */
    private function fundTopFour(Competition $competition, array $members, array $apps): void
    {
        foreach (array_slice($apps, 0, 4) as $app) {
            $result = $this->draft($competition, $members, $app, 'support', '2000');
            $this->assertTrue($result->succeeded, (string) $result->error);
        }
    }

    /**
     * @param  list<Application>  $apps
     * @return array<int, Application>
     */
    private function byRank(array $apps): array
    {
        $byRank = [];
        foreach ($apps as $app) {
            $byRank[(int) $app->fresh()->ranking_position] = $app->fresh();
        }

        return $byRank;
    }

    /**
     * @return array<string, int>
     */
    private function criteriaSumming(int $total): array
    {
        $base = intdiv($total, 10);
        $remainder = $total % 10;
        $criteria = [];
        foreach (range(1, 10) as $criterion) {
            $criteria["criterion_{$criterion}"] = $base + ($criterion <= $remainder ? 1 : 0);
        }

        return $criteria;
    }
}
