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
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\YouthEqualScoreVotingService;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Support\CompetitionAnnualInstance;
use App\Support\CompetitionProgramCatalog;
use DomainException;
use App\Support\KnApplicationClassification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthEqualScoreVotingTest extends TestCase
{
    use RefreshDatabase;

    private static int $yearSerial = 2050;

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

    public function test_group_is_not_created_without_permanent_ranking(): void
    {
        $ctx = $this->youthReadyToLock();
        $second = $this->addPassedApplication($ctx, 'Druga');
        $this->lockScore($ctx, $ctx['application'], 3);
        $this->lockScore($ctx, $second, 3);

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk();
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Predlog',
                'selected' => [$ctx['application']->id],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
    }

    public function test_get_does_not_create_group_or_round(): void
    {
        $ctx = $this->twoTiedCoveredByBudget();

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk();
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk();
        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk();

        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertSame(0, YouthEqualScoreRoundApplication::query()->count());
        $this->assertSame(0, YouthEqualScoreVote::query()->count());
    }

    public function test_no_group_when_budget_covers_all_tied_drafts(): void
    {
        $ctx = $this->twoTiedCoveredByBudget();

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-equal-score-voting"', $html);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Nema grupe',
                'selected' => [$ctx['application']->id],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_startup_priority_fully_resolves_without_vote(): void
    {
        $ctx = $this->startupPriorityResolvesFixture();

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['tied_startup']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-equal-score-voting"', $html);
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertFalse($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_two_apps_funds_for_one_and_three_apps_funds_for_two(): void
    {
        $two = $this->twoAppsFundsForOne();
        $html = $this->actingAs($two['chairman']->user)
            ->get(route('evaluation.create', $two['tied'][0]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-equal-score-voting"', $html);
        $this->assertStringContainsString('Izjednačeni kandidati', $html);
        $this->assertStringContainsString($two['tied'][0]->business_plan_name, $html);
        $this->assertStringContainsString($two['tied'][1]->business_plan_name, $html);
        $this->assertStringNotContainsString('data-testid="youth-equal-score-round-form"', $html);
        $this->assertStringNotContainsString('data-testid="youth-equal-score-round-lock-form"', $html);
        $this->assertStringNotContainsString('Sačuvaj predlog i glasove', $html);
        $this->assertStringNotContainsString('Zaključi krug', $html);
        $this->assertStringNotContainsString('glasanje Komisije', $html);
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());

        $twoResponse = $this->actingAs($two['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $two['competition']), [
                'justification' => 'Podrška jednoj od dvije izjednačene prijave.',
                'selected' => [$two['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'against'],
            ]);
        $twoResponse->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $twoResponse->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertSame('approved', $two['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $two['tied'][0]->fresh()->approved_amount);
        $this->assertSame('approved', $two['tied'][1]->fresh()->status);
        $this->assertSame('30000.00', $two['tied'][1]->fresh()->approved_amount);

        $three = $this->threeAppsFundsForTwo();
        $threeResponse = $this->actingAs($three['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $three['competition']), [
                'justification' => 'Dvije od tri.',
                'selected' => [$three['tied'][0]->id, $three['tied'][1]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ]);
        $threeResponse->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $threeResponse->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        foreach ($three['tied'] as $application) {
            $this->assertSame('approved', $application->fresh()->status);
            $this->assertSame('30000.00', $application->fresh()->approved_amount);
        }
    }

    public function test_different_draft_amounts_and_no_automatic_selection(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['tied'][1]))
            ->post(route('evaluation.youth-allocation-draft', $ctx['tied'][1]), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Smanjen iznos nacrta.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Bez automatskog izbora.',
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $this->assertSame('approved', $ctx['tied'][1]->fresh()->status);
        $this->assertSame('10000.00', $ctx['tied'][1]->fresh()->approved_amount);
    }

    public function test_three_votes_are_required_before_lock(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $create = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Dva glasa nijesu dovoljna.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for'],
            ]);
        $create->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $create->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreRound::query()->count());

        $round = $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $beforeVotes = YouthEqualScoreVote::query()->where('round_id', $round->id)->get()->toArray();
        $lock = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]));
        $lock->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $lock->exception?->getMessage()
        );
        $this->assertSame($beforeVotes, YouthEqualScoreVote::query()->where('round_id', $round->id)->get()->toArray());
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $round->fresh()->outcome);
        $this->assertNotNull($round->fresh()->locked_at);
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
    }

    public function test_outcomes_three_zero_two_one_one_two_and_zero_three(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $service = app(YouthEqualScoreVotingService::class);
        $this->assertVotingWriteRefused(function () use ($service, $ctx) {
            $service->saveRound($ctx['competition'], $ctx['chairman']->user, [
                'justification' => '3:0',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ]);
        });
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertSame(0, YouthEqualScoreVote::query()->count());

        $round = $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'against']);
        $this->assertVotingWriteRefused(function () use ($service, $ctx, $round) {
            $service->lockRound($ctx['competition'], $ctx['chairman']->user, $round);
        });
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $round->fresh()->outcome);
        $this->assertSame(1, YouthEqualScoreRound::query()->count());
        $this->assertSame(3, YouthEqualScoreVote::query()->where('round_id', $round->id)->count());
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $this->assertSame('30000.00', $ctx['tied'][1]->fresh()->approved_amount);
    }

    public function test_rejected_round_allows_new_and_adopted_forbids_new(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['against', 'against', 'for']);
        $first = YouthEqualScoreRound::query()->firstOrFail();
        $this->assertSame(YouthEqualScoreRound::OUTCOME_REJECTED, $first->outcome);
        $this->assertSame(1, (int) $first->round_no);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Novi krug poslije odbijanja.',
                'selected' => [$ctx['tied'][1]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $lock = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $first]));
        $lock->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $lock->exception?->getMessage()
        );
        $this->assertSame(1, YouthEqualScoreRound::query()->count());
        $this->assertSame(YouthEqualScoreRound::OUTCOME_REJECTED, $first->fresh()->outcome);
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $this->assertFalse($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_locked_round_is_immutable(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $round = $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $beforeVotes = YouthEqualScoreVote::query()->where('round_id', $round->id)->get()->toArray();
        $beforeApps = YouthEqualScoreRoundApplication::query()->where('round_id', $round->id)->get()->toArray();

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Izmjena zaključanog.',
                'selected' => [$ctx['tied'][1]->id],
                'votes' => [1 => 'against', 2 => 'against', 3 => 'against'],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );

        $lockAgain = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]));
        $lockAgain->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE, $lockAgain->exception?->getMessage());
        $service = app(YouthEqualScoreVotingService::class);
        $this->assertVotingWriteRefused(function () use ($service, $ctx, $round) {
            $service->saveRound($ctx['competition'], $ctx['chairman']->user, [
                'justification' => 'Direktan upis.',
                'selected' => [$ctx['tied'][1]->id],
            ]);
        });
        $this->assertVotingWriteRefused(function () use ($service, $ctx, $round) {
            $service->lockRound($ctx['competition'], $ctx['chairman']->user, $round);
        });

        $this->assertSame($beforeVotes, YouthEqualScoreVote::query()->where('round_id', $round->id)->get()->toArray());
        $this->assertSame($beforeApps, YouthEqualScoreRoundApplication::query()->where('round_id', $round->id)->get()->toArray());
        $this->assertSame('Podrška izjednačenoj prijavi.', $round->fresh()->justification);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $round->fresh()->outcome);
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][1]->fresh()->approved_amount);
    }

    public function test_member_replacement_before_and_after_lock(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $round = $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $oldSeatTwo = $ctx['members'][1];
        $oldVoteUser = (int) YouthEqualScoreVote::query()
            ->where('round_id', $round->id)
            ->where('canonical_seat_no', 2)
            ->value('user_id');

        $oldSeatTwo->update(['status' => 'inactive']);
        $replacement = $this->userWithRole('komisija');
        CommissionMember::create([
            'commission_id' => $ctx['competition']->commission_id,
            'user_id' => $replacement->id,
            'name' => $replacement->name,
            'position' => 'clan',
            'member_type' => null,
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);

        $lock = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]));
        $lock->assertForbidden();
        $rewrite = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Glas novog člana.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'against', 3 => 'for'],
            ]);
        $rewrite->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $rewrite->exception?->getMessage()
        );

        $seatTwoVote = YouthEqualScoreVote::query()
            ->where('round_id', $round->id)
            ->where('canonical_seat_no', 2)
            ->firstOrFail();
        $this->assertSame((int) $oldSeatTwo->id, (int) $seatTwoVote->commission_member_id);
        $this->assertSame($oldVoteUser, (int) $seatTwoVote->user_id);
        $this->assertNotSame((int) $replacement->id, (int) $seatTwoVote->user_id);
        $this->assertSame(YouthEqualScoreVote::FOR, $seatTwoVote->vote_value);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $round->fresh()->outcome);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
    }

    public function test_malicious_snapshot_and_audit_payload_is_ignored(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $spoofUser = $this->userWithRole('komisija');
        $positions = [
            (int) $ctx['tied'][0]->fresh()->ranking_position,
            (int) $ctx['tied'][1]->fresh()->ranking_position,
        ];
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Serverski snapshot.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
                'full_score' => '99.0000000000',
                'ranking_position' => 1,
                'business_stage' => 'razvoj',
                'requested_amount' => 1,
                'draft_amount' => 1,
                'applied_cap_percent' => 15,
                'budget_before' => 1,
                'proposal_total' => 1,
                'commission_member_id' => 999999,
                'user_id' => $spoofUser->id,
                'member_name' => 'Lažno ime',
                'recorded_by_user_id' => $spoofUser->id,
                'created_by_name' => 'Lažni predsjednik',
                'locked_by_name' => 'Lažni zaključavalac',
                'outcome' => 'adopted',
                'locked_at' => now()->toDateTimeString(),
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertSame(0, YouthEqualScoreVote::query()->count());
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $this->assertSame(KnApplicationClassification::STAGE_ZAPOCINJANJE, $ctx['tied'][0]->fresh()->business_stage);
        $this->assertSame($positions[0], (int) $ctx['tied'][0]->fresh()->ranking_position);
        $this->assertSame($positions[1], (int) $ctx['tied'][1]->fresh()->ranking_position);
        $this->assertSame('30000.00', $ctx['tied'][1]->fresh()->approved_amount);
    }

    public function test_authorization_and_dual_membership(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $other = $this->twoTiedCoveredByBudget();
        $payload = [
            'justification' => 'Neovlašćeno.',
            'selected' => [$ctx['tied'][0]->id],
            'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
        ];

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['tied'][0]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-equal-score-voting"', $memberHtml);
        $this->assertStringNotContainsString('data-testid="youth-equal-score-round-form"', $memberHtml);

        $response = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE, $response->exception?->getMessage());

        $response = $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE, $response->exception?->getMessage());

        $dualUser = $ctx['chairman']->user;
        $other['members'][1]->update([
            'user_id' => $dualUser->id,
            'name' => $dualUser->name,
            'position' => 'clan',
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);
        $response = $this->actingAs($dualUser)
            ->post(route('evaluation.youth-equal-score-round', $other['competition']), [
                'justification' => 'Dual.',
                'selected' => [$other['tied']->id],
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE, $response->exception?->getMessage());

        $former = $ctx['chairman']->user;
        $replacementChairman = $this->userWithRole('komisija');
        $ctx['chairman']->update([
            'user_id' => $replacementChairman->id,
            'name' => $replacementChairman->name,
            'position' => 'predsjednik',
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);
        $response = $this->actingAs($former)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE, $response->exception?->getMessage());

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload)
            ->assertForbidden();

        $youthUrl = route('evaluation.create', $ctx['tied'][0]);
        $konkursAdmin = $this->userWithRole('konkurs_admin');
        $response = $this->actingAs($konkursAdmin)
            ->from($youthUrl)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload);
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionMissing('success');

        $this->actingAs($ctx['tied'][0]->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload)
            ->assertForbidden();

        $zensko = $this->makeZenskoCall();
        $response = $this->actingAs($zensko['chairman'])
            ->post(route('evaluation.youth-equal-score-round', $zensko['competition']), $payload);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::NOT_YOUTH_MESSAGE, $response->exception?->getMessage());

        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
    }

    public function test_status_stays_evaluated_and_remaining_after_first_is_unchanged(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $annual = app(CompetitionAnnualInstance::class);
        $allocationBefore = $annual->confirmedAllocation($ctx['competition']->fresh());
        $overflowBefore = $this->annualOverflowMessage($annual, (int) $ctx['competition']->year);
        $statuses = collect($ctx['tied'])->map(fn (Application $app) => $app->fresh()->status)->all();
        $amounts = collect($ctx['tied'])->map(fn (Application $app) => $app->fresh()->approved_amount)->all();

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Ne smije promijeniti godišnji zbir.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ]);
        $response->assertForbidden();
        $this->assertVotingWriteRefused(function () use ($ctx) {
            app(YouthEqualScoreVotingService::class)->saveRound($ctx['competition'], $ctx['chairman']->user, [
                'justification' => 'Direktan upis.',
                'selected' => [$ctx['tied'][0]->id],
            ]);
        });
        $this->assertSame(0, YouthEqualScoreRound::query()->count());

        foreach ($ctx['tied'] as $i => $app) {
            $fresh = $app->fresh();
            $this->assertSame('approved', $fresh->status);
            $this->assertSame($statuses[$i], $fresh->status);
            $this->assertSame($amounts[$i], $fresh->approved_amount);
        }
        $this->assertSame($allocationBefore, $annual->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertSame($overflowBefore, $this->annualOverflowMessage($annual, (int) $ctx['competition']->year));
        $this->assertSame(
            CompetitionAnnualInstance::MSG_ALLOCATION_EXCEEDS_FIRST_BUDGET,
            $overflowBefore
        );
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
        $this->assertNull($ctx['competition']->fresh()->closed_at);
        $this->assertSame('published', $ctx['competition']->fresh()->status);
    }

    public function test_reduce_single_priority_draft_blocks_round_creation(): void
    {
        $ctx = $this->singlePriorityDoesNotFit();
        $voting = app(YouthEqualScoreVotingService::class);
        $this->assertSame(
            '10000.00',
            $voting->remainingBeforeApplication($ctx['competition']->fresh(), $ctx['startup']->fresh())
        );

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['startup']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['startup']), $this->capDraft())
            ->assertRedirect()
            ->assertSessionHasErrors([
                'approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
            ]);
        $this->assertNull($ctx['startup']->fresh()->commission_decision);
        $this->assertNull($ctx['startup']->fresh()->approved_amount);
        $this->assertSame('evaluated', $ctx['startup']->fresh()->status);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['startup']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['startup']), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Smanjen iznos nacrta jer ne staje u preostalih 10000.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('10000.00', $ctx['startup']->fresh()->approved_amount);
        $this->assertSame('podrzava_potpuno', $ctx['startup']->fresh()->commission_decision);
        $this->assertSame('approved', $ctx['startup']->fresh()->status);
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertFalse($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_legacy_oversized_priority_draft_still_blocks_round_creation(): void
    {
        $ctx = $this->singlePriorityDoesNotFit();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['startup']), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Smanjen iznos nacrta jer ne staje u preostalih 10000.',
            ]))
            ->assertSessionHasNoErrors();
        $this->forceCompetingSupport($ctx['dev'], '10000.00');

        // Direktni DB upis simulira legacy/neispravno stanje; nije dozvoljeni UI tok.
        $ctx['startup']->forceFill(['approved_amount' => '30000.00'])->save();
        $this->assertSame('30000.00', $ctx['startup']->fresh()->approved_amount);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Ne smije se kreirati.',
                'selected' => [$ctx['startup']->id],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
        $this->assertFalse($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_two_tied_drafts_of_thirty_thousand_fit_individually_against_remaining_forty_thousand(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $voting = app(YouthEqualScoreVotingService::class);
        $this->assertSame(
            '40000.00',
            $voting->remainingBeforeApplication($ctx['competition']->fresh(), $ctx['tied'][0]->fresh())
        );
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $this->assertSame('30000.00', $ctx['tied'][1]->fresh()->approved_amount);
        $this->assertSame('approved', $ctx['tied'][0]->fresh()->status);
        $groups = $voting->detectBoundaryGroups($ctx['competition']->fresh());
        $this->assertCount(1, $groups);
        $this->assertTrue($groups[0]['can_create_round']);
        $this->assertSame('40000.00', $groups[0]['remaining_before']);
        $this->assertCount(2, $groups[0]['applications']);
    }

    public function test_three_tied_drafts_of_thirty_thousand_fit_individually_against_remaining_sixty_thousand(): void
    {
        $ctx = $this->threeAppsRemainingSixty();
        $voting = app(YouthEqualScoreVotingService::class);
        $this->assertSame(
            '60000.00',
            $voting->remainingBeforeApplication($ctx['competition']->fresh(), $ctx['tied'][0]->fresh())
        );
        foreach ($ctx['tied'] as $application) {
            $this->assertSame('30000.00', $application->fresh()->approved_amount);
            $this->assertSame('approved', $application->fresh()->status);
        }
        $groups = $voting->detectBoundaryGroups($ctx['competition']->fresh());
        $this->assertCount(1, $groups);
        $this->assertTrue($groups[0]['can_create_round']);
        $this->assertSame('60000.00', $groups[0]['remaining_before']);
        $this->assertCount(3, $groups[0]['applications']);
    }

    public function test_lower_rank_draft_is_blocked_before_adopted_round(): void
    {
        $ctx = $this->twoAppsFundsForOneWithLowerRankHeadroom();
        $lower = $this->addLowerRankScoredApplication($ctx, 'Nizi rang', 10000);
        $voting = app(YouthEqualScoreVotingService::class);
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $lower))
            ->assertOk();
        $this->assertSame(5, (int) $lower->fresh()->ranking_position);
        $groups = $voting->detectBoundaryGroups($ctx['competition']->fresh());
        $this->assertCount(1, $groups);
        $this->assertTrue($groups[0]['can_create_round']);
        $this->assertSame(3, (int) $groups[0]['ranking_position']);
        $this->assertSame(0, YouthEqualScoreRound::query()->whereNotNull('locked_at')->count());

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10000,
            ]));
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::LOWER_RANK_UNRESOLVED_GROUP_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertNull($lower->fresh()->commission_decision);
        $this->assertSame('evaluated', $lower->fresh()->status);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijanje nižeg ranga bez kruga glasanja.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('rejected', $lower->fresh()->status);
        $this->assertSame('Odbijanje nižeg ranga bez kruga glasanja.', $lower->fresh()->commission_justification);
        $this->assertSame(5, (int) $lower->fresh()->ranking_position);
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
        $this->assertSame(0, YouthEqualScoreRound::query()->count());
    }

    public function test_remaining_after_adopted_round_counts_only_selected_applications(): void
    {
        $ctx = $this->twoAppsFundsForOneWithLowerRankHeadroom();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);
        $lockedRound = YouthEqualScoreRound::query()->whereNotNull('locked_at')->firstOrFail();
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $lockedRound->outcome);

        $lower = $this->addLowerRankScoredApplication($ctx, 'Nizi nakon usvajanja', 15000);
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $lower))
            ->assertOk();
        $this->assertSame(5, (int) $lower->fresh()->ranking_position);

        $voting = app(YouthEqualScoreVotingService::class);
        $this->assertSame(
            '10000.00',
            $voting->remainingBeforeApplication($ctx['competition']->fresh(), $lower->fresh())
        );

        $blocked = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10000,
            ]));
        $blocked->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::LOWER_RANK_UNRESOLVED_GROUP_MESSAGE,
            $blocked->exception?->getMessage()
        );
        $this->assertSame('evaluated', $lower->fresh()->status);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $lockedRound->fresh()->outcome);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['tied'][1]), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija je na sjednici odabrala drugu prijavu istog ranga.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('rejected', $ctx['tied'][1]->fresh()->status);
        $this->assertSame(3, (int) $ctx['tied'][1]->fresh()->ranking_position);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10001,
                'commission_justification' => 'Iznad ostatka poslije pojedinačnih odluka.',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Iznos u okviru ostatka poslije pojedinačnih odluka.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('10000.00', $lower->fresh()->approved_amount);
        $this->assertSame('approved', $lower->fresh()->status);
        $this->assertSame(5, (int) $lower->fresh()->ranking_position);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $lockedRound->fresh()->outcome);
        $this->assertSame(3, YouthEqualScoreVote::query()->where('round_id', $lockedRound->id)->count());
    }

    public function test_rejected_round_still_blocks_lower_rank_draft(): void
    {
        $ctx = $this->twoAppsFundsForOneWithLowerRankHeadroom();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['against', 'against', 'against']);
        $this->assertSame(
            YouthEqualScoreRound::OUTCOME_REJECTED,
            YouthEqualScoreRound::query()->whereNotNull('locked_at')->value('outcome')
        );

        $lower = $this->addLowerRankScoredApplication($ctx, 'Nizi poslije odbijanja', 10000);
        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10000,
            ]));
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::LOWER_RANK_UNRESOLVED_GROUP_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertNull($lower->fresh()->commission_decision);
        $this->assertNull($lower->fresh()->approved_amount);
        $this->assertSame('evaluated', $lower->fresh()->status);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijanje nižeg ranga ne čeka stari krug.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('rejected', $lower->fresh()->status);
        $this->assertSame(
            YouthEqualScoreRound::OUTCOME_REJECTED,
            YouthEqualScoreRound::query()->whereNotNull('locked_at')->value('outcome')
        );
        $this->assertSame('30000.00', $ctx['tied'][0]->fresh()->approved_amount);
    }

    public function test_second_tied_support_is_limited_by_live_amounts_and_startup_priority(): void
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
        $positions = [(int) $tiedA->fresh()->ranking_position, (int) $tiedB->fresh()->ranking_position];

        $overflow = $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $tiedB))
            ->post(route('evaluation.youth-allocation-draft', $tiedB), $this->capDraft());
        $overflow->assertRedirect();
        $overflow->assertSessionHasErrors([
            'approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
        ]);
        $this->assertNull($tiedB->fresh()->commission_decision);
        $this->assertSame($positions[0], (int) $tiedA->fresh()->ranking_position);
        $this->assertSame($positions[1], (int) $tiedB->fresh()->ranking_position);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $tiedB), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Smanjen iznos jer zbir ne staje u ostatak poziva.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('10000.00', $tiedB->fresh()->approved_amount);
        $this->assertSame('Smanjen iznos jer zbir ne staje u ostatak poziva.', $tiedB->fresh()->commission_justification);

        $priority = $this->youthReadyToLock();
        $priority['application']->update(['requested_amount' => 30000]);
        $this->lockScore($priority, $priority['application'], 5);
        $this->confirmYouthBonuses($priority);
        $higher = $this->addScoredApplication($priority, 'Viši rang', 30000, 4);
        $startup = $this->addScoredApplication($priority, 'Otpočinjanje', 30000, 3);
        $development = $this->addScoredApplication($priority, 'Razvoj', 30000, 3);
        $development->update(['business_stage' => KnApplicationClassification::STAGE_RAZVOJ]);
        $this->saveCapSupport($priority, $priority['application']);
        $this->saveCapSupport($priority, $higher);
        $earlyDevelopment = $this->actingAs($priority['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $development), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Prije odluke o otpočinjanju.',
            ]));
        $earlyDevelopment->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::STARTUP_PRIORITY_PENDING_MESSAGE,
            $earlyDevelopment->exception?->getMessage()
        );
        $this->saveCapSupport($priority, $startup);
        $this->actingAs($priority['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $development), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Ostatak poslije prioriteta otpočinjanja.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('10000.00', $development->fresh()->approved_amount);
        $this->assertSame('30000.00', $startup->fresh()->approved_amount);
        $this->assertSame((int) $startup->fresh()->ranking_position, (int) $development->fresh()->ranking_position);
    }

    public function test_same_stage_tie_requires_choice_justification_and_resolves_without_voting(): void
    {
        $ctx = $this->unresolvedSameStageTie();
        [$tiedA, $tiedB] = $ctx['tied'];
        $tiedA = $tiedA->fresh();
        $tiedB = $tiedB->fresh();
        $reason = 'Komisija je na sjednici podržala drugu prijavu istog ranga i faze.';
        $this->assertSame($tiedA->business_stage, $tiedB->business_stage);
        $this->assertSame((string) $tiedA->final_score, (string) $tiedB->final_score);
        $this->assertSame((int) $tiedA->ranking_position, (int) $tiedB->ranking_position);
        $score = (string) $tiedA->final_score;
        $rank = (int) $tiedA->ranking_position;

        $lower = $this->addLowerRankScoredApplication($ctx, 'Nizi dok visi nije rijesen', 10000);
        $this->assertGreaterThan($rank, (int) $lower->fresh()->ranking_position);
        $blocked = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10000,
            ]));
        $blocked->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::LOWER_RANK_UNRESOLVED_GROUP_MESSAGE,
            $blocked->exception?->getMessage()
        );

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), [
                'commission_decision' => 'odbija',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'commission_justification' => YouthAllocationDraftService::REJECT_JUSTIFICATION_REQUIRED_MESSAGE,
            ]);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), [
                'commission_decision' => 'odbija',
                'commission_justification' => '   ',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'commission_justification' => YouthAllocationDraftService::REJECT_JUSTIFICATION_REQUIRED_MESSAGE,
            ]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $lower), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijanje nižeg ranga bez elektronskog glasanja.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame(0, YouthEqualScoreRound::query()->count());

        $supported = $this->addLowerRankScoredApplication($ctx, 'Nizi poslije razrjesenja', 10000);
        $this->saveCapSupport($ctx, $tiedA);
        $this->assertNull($tiedA->fresh()->commission_justification);
        $this->assertSame('30000.00', $tiedA->fresh()->approved_amount);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $tiedB))
            ->post(route('evaluation.youth-allocation-draft', $tiedB), $this->capDraft())
            ->assertRedirect()
            ->assertSessionHasErrors([
                'approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
            ]);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $tiedB))
            ->post(route('evaluation.youth-allocation-draft', $tiedB), $this->capDraft([
                'approved_amount' => 10000,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'commission_justification' => YouthAllocationDraftService::REDUCED_AMOUNT_JUSTIFICATION_REQUIRED_MESSAGE,
            ]);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $tiedB))
            ->post(route('evaluation.youth-allocation-draft', $tiedB), [
                'commission_decision' => 'odbija',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'commission_justification' => YouthAllocationDraftService::REJECT_JUSTIFICATION_REQUIRED_MESSAGE,
            ]);
        $this->assertNull($tiedB->fresh()->commission_decision);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $tiedB), [
                'commission_decision' => 'odbija',
                'commission_justification' => $reason,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $tiedB = $tiedB->fresh();
        $tiedA = $tiedA->fresh();
        $this->assertSame('odbija', $tiedB->commission_decision);
        $this->assertSame($reason, $tiedB->commission_justification);
        $this->assertSame($reason, $tiedB->rejection_reason);
        $this->assertNull($tiedA->commission_justification);
        $this->assertSame($score, (string) $tiedA->final_score);
        $this->assertSame($score, (string) $tiedB->final_score);
        $this->assertSame($rank, (int) $tiedA->ranking_position);
        $this->assertSame($rank, (int) $tiedB->ranking_position);
        $higherGroups = app(YouthEqualScoreVotingService::class)->detectBoundaryGroups($ctx['competition']->fresh());
        $this->assertCount(1, $higherGroups);
        $this->assertGreaterThan($rank, (int) $higherGroups[0]['ranking_position']);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $supported), $this->supportDraft([
                'approved_amount' => 10000,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('10000.00', $supported->fresh()->approved_amount);
        $this->assertSame([], app(YouthEqualScoreVotingService::class)->detectBoundaryGroups($ctx['competition']->fresh()));
        $this->assertSame('100000.00', app(CompetitionAnnualInstance::class)->confirmedAllocation($ctx['competition']->fresh()));
        $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame(0, YouthEqualScoreRound::query()->count());

        $chairmanHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $tiedB))
            ->assertOk()
            ->getContent();
        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $tiedB))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($reason, $chairmanHtml);
        $this->assertStringContainsString($reason, $memberHtml);
        $this->assertSame($reason, $tiedB->fresh()->commission_justification);
    }

    public function test_historical_round_neither_raises_nor_shrinks_the_live_support_room(): void
    {
        $voting = app(YouthEqualScoreVotingService::class);

        $over = $this->unresolvedSameStageTie();
        [$overA, $overB] = $over['tied'];
        $this->actingAs($over['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $overA), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Privremeno smanjen iznos prije istorijskog kruga.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $overRound = $this->saveAndLock($over, [$overA->id], ['for', 'for', 'for']);
        $this->assertSame('10000.00', $overRound->proposal_total);
        $this->actingAs($over['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $overA), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Nova odluka odbija kandidata iz starog kruga.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->saveCapSupport($over, $overB);
        $overLower = $this->addLowerRankScoredApplication($over, 'Nizi poslije drugacije raspodjele', 30000);
        $this->assertSame('10000.00', $voting->liveSupportRoom($over['competition']->fresh(), $overLower->fresh()));
        $this->assertSame('30000.00', $voting->remainingBeforeApplication($over['competition']->fresh(), $overLower->fresh()));
        $this->actingAs($over['chairman']->user)
            ->from(route('evaluation.create', $overLower))
            ->post(route('evaluation.youth-allocation-draft', $overLower), $this->capDraft([
                'approved_amount' => 20000,
                'commission_justification' => 'Iznos koji bi stari krug prikazao kao slobodan.',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
            ]);
        $this->assertNull($overLower->fresh()->commission_decision);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $overRound->fresh()->outcome);
        $this->assertSame('10000.00', $overRound->fresh()->proposal_total);
        $this->assertTrue((bool) YouthEqualScoreRoundApplication::query()
            ->where('round_id', $overRound->id)
            ->where('application_id', $overA->id)
            ->value('selected'));
        $this->assertSame('rejected', $overA->fresh()->status);
        $this->assertSame('30000.00', $overB->fresh()->approved_amount);

        $under = $this->unresolvedSameStageTie();
        [$underA, $underB] = $under['tied'];
        $this->saveCapSupport($under, $underA);
        $underRound = $this->saveAndLock($under, [$underA->id], ['for', 'for', 'for']);
        $this->assertSame('30000.00', $underRound->proposal_total);
        $this->actingAs($under['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $underA), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Nova odluka ne slijedi izabrane kandidate starog kruga.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($under['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $underB), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Manji iznos od onog u starom krugu.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $underLower = $this->addLowerRankScoredApplication($under, 'Nizi nije blokiran starim krugom', 30000);
        $this->assertSame('30000.00', $voting->liveSupportRoom($under['competition']->fresh(), $underLower->fresh()));
        $this->assertSame('10000.00', $voting->remainingBeforeApplication($under['competition']->fresh(), $underLower->fresh()));
        $this->actingAs($under['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $underLower), $this->capDraft([
                'approved_amount' => 20000,
                'commission_justification' => 'Iznos staje u sačuvane odluke, ne u stari krug.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('20000.00', $underLower->fresh()->approved_amount);
        $this->assertSame('30000.00', $underRound->fresh()->proposal_total);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $underRound->fresh()->outcome);
        $this->assertSame(3, YouthEqualScoreVote::query()->where('round_id', $underRound->id)->count());
        $this->assertSame((int) $underA->ranking_position, (int) $underA->fresh()->ranking_position);
        $this->assertSame((string) $underB->final_score, (string) $underB->fresh()->final_score);
    }

    public function test_conclusion_flash_and_tied_card_amounts_follow_the_ranking_format(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->update(['requested_amount' => 30000]);
        $unsaved = $this->addScoredApplication($ctx, 'Nesačuvana prijava', 30000, 3);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.youth-ranking', $ctx['competition']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), $this->capDraft([
                'approved_amount' => 30000,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Zaključak Komisije je uspješno sačuvan.');

        $savedPage = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->assertSee('Zaključak Komisije je uspješno sačuvan.')
            ->assertDontSee('Nacrt raspodjele je sačuvan.');
        $html = $savedPage->getContent();
        $this->assertStringContainsString('30.000,00 €', $html);
        $this->assertStringContainsString('Izjednačeni kandidati', $html);
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($unsaved->business_plan_name, '/').'<\/td>\s*<td[^>]*>.*?<\/td>\s*<td[^>]*>.*?<\/td>\s*<td[^>]*>\s*<span style="color: #6b7280;">-<\/span>/s',
            $html
        );

        $card = view('evaluation._youth_equal_score_voting', [
            'youthEqualScoreVotingBoard' => [
                'visible' => true,
                'ranking_ready' => true,
                'groups' => [[
                    'ranking_position' => 1,
                    'full_score' => '39.000000',
                    'remaining_before' => '40000.00',
                    'applications' => [
                        [
                            'business_plan_name' => 'Puna podrška',
                            'business_stage' => 'započinjanje',
                            'requested_amount' => '30000.00',
                            'approved_amount' => '30000.00',
                            'draft_amount' => '30000.00',
                            'applied_cap_percent' => 30,
                        ],
                        [
                            'business_plan_name' => 'Decimalna podrška',
                            'business_stage' => 'započinjanje',
                            'requested_amount' => '10000.50',
                            'approved_amount' => '10000.50',
                            'draft_amount' => '10000.50',
                            'applied_cap_percent' => 20,
                        ],
                        [
                            'business_plan_name' => 'Nulta podrška',
                            'business_stage' => 'razvoj',
                            'requested_amount' => '5000.00',
                            'approved_amount' => '0.00',
                            'draft_amount' => '0.00',
                            'applied_cap_percent' => 15,
                        ],
                        [
                            'business_plan_name' => 'Još bez odluke',
                            'business_stage' => 'započinjanje',
                            'requested_amount' => '30000.00',
                            'approved_amount' => null,
                            'draft_amount' => '0.00',
                            'applied_cap_percent' => null,
                        ],
                    ],
                ]],
            ],
        ])->render();

        $this->assertStringContainsString('30.000,00 €', $card);
        $this->assertStringContainsString('10.000,50 €', $card);
        $this->assertStringContainsString('0,00 €', $card);
        $this->assertMatchesRegularExpression(
            '/Još bez odluke<\/td>\s*<td[^>]*>.*?<\/td>\s*<td[^>]*>.*?<\/td>\s*<td[^>]*>\s*<span style="color: #6b7280;">-<\/span>/s',
            $card
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Još bez odluke<\/td>\s*<td[^>]*>.*?<\/td>\s*<td[^>]*>.*?<\/td>\s*<td[^>]*>\s*0,00 €/s',
            $card
        );
    }

    public function test_automatic_finality_follows_saved_decisions_not_locked_rounds(): void
    {
        $roundsFor = function (Competition $competition): int {
            return YouthEqualScoreRound::query()
                ->whereHas('group', function ($query) use ($competition) {
                    $query->where('competition_id', $competition->id);
                })
                ->count();
        };

        $covered = $this->twoTiedCoveredByBudget();
        $this->assertTrue($covered['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame(0, $roundsFor($covered['competition']));
        $covered['application']->fresh()->forceFill([
            'youth_innovative_tech_startup' => null,
        ])->save();
        $this->assertFalse($covered['competition']->fresh()->hasChairmanCompletedDecisions());
        $covered['application']->fresh()->forceFill([
            'youth_innovative_tech_startup' => 0,
        ])->save();
        $this->assertTrue($covered['competition']->fresh()->hasChairmanCompletedDecisions());

        $boundary = $this->unresolvedSameStageTie();
        [$boundaryA, $boundaryB] = $boundary['tied'];
        $boundaryRank = (int) $boundaryA->fresh()->ranking_position;
        $boundaryScore = (string) $boundaryA->fresh()->final_score;
        $this->saveCapSupport($boundary, $boundaryA);
        $this->forceCompetingSupport($boundaryB);
        $this->assertFalse($boundary['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertTrue(app(YouthEqualScoreVotingService::class)->hasUnresolvedLiveAllocation($boundary['competition']->fresh()));
        $this->actingAs($boundary['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $boundaryB), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija podržava drugu prijavu istog ranga.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertTrue($boundary['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame(0, $roundsFor($boundary['competition']));
        $this->assertSame($boundaryRank, (int) $boundaryA->fresh()->ranking_position);
        $this->assertSame($boundaryRank, (int) $boundaryB->fresh()->ranking_position);
        $this->assertSame($boundaryScore, (string) $boundaryA->fresh()->final_score);
        $this->assertSame($boundaryScore, (string) $boundaryB->fresh()->final_score);

        $this->actingAs($boundary['chairman']->user)
            ->post(route('admin.competitions.close', $boundary['competition']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $closed = $boundary['competition']->fresh();
        $this->assertSame('completed', $closed->status);
        $annual = app(CompetitionAnnualInstance::class);
        $this->assertSame('10000.00', $annual->remainingAfterFirst('omladinsko', (int) $closed->year));
        $this->assertTrue($annual->canCreateSecondCall($closed));
        $annual->validateSecondCall(new Competition([
            'title' => 'Drugi poslije konačne liste',
            'description' => 'Opis',
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => (int) $closed->year,
            'call_number' => 2,
            'annual_budget' => '100000.00',
            'budget' => '10000.00',
            'deadline_days' => 20,
        ]));

        $different = $this->unresolvedSameStageTie();
        [$differentA, $differentB] = $different['tied'];
        $this->saveCapSupport($different, $differentA);
        $differentRound = $this->saveAndLock($different, [$differentA->id], ['for', 'for', 'for']);
        $this->actingAs($different['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $differentA), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Nova odluka odbija kandidata iz starog kruga.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($different['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $differentB), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Podrška drugom kandidatu, suprotno starom krugu.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertTrue($different['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertFalse(app(YouthEqualScoreVotingService::class)->hasUnresolvedLiveAllocation($different['competition']->fresh()));
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $differentRound->fresh()->outcome);
        $this->assertSame('30000.00', $differentRound->fresh()->proposal_total);
        $this->assertSame('rejected', $differentA->fresh()->status);
        $this->assertSame('10000.00', $differentB->fresh()->approved_amount);

        $incomplete = $this->unresolvedSameStageTie();
        $this->saveCapSupport($incomplete, $incomplete['tied'][0]);
        $incompleteRound = $this->saveAndLock($incomplete, [$incomplete['tied'][0]->id], ['for', 'for', 'for']);
        $this->assertFalse($incomplete['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertNull($incomplete['tied'][1]->fresh()->commission_decision);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $incompleteRound->fresh()->outcome);

        $priority = $this->youthReadyToLock();
        $priority['application']->update(['requested_amount' => 30000]);
        $this->lockScore($priority, $priority['application'], 5);
        $this->confirmYouthBonuses($priority);
        $higher = $this->addScoredApplication($priority, 'Viši rang', 30000, 4);
        $startup = $this->addScoredApplication($priority, 'Otpočinjanje', 30000, 3);
        $development = $this->addScoredApplication($priority, 'Razvoj', 30000, 3);
        $development->update(['business_stage' => KnApplicationClassification::STAGE_RAZVOJ]);
        $this->saveCapSupport($priority, $priority['application']);
        $this->saveCapSupport($priority, $higher);
        $early = $this->actingAs($priority['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $development), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Prije odluke o otpočinjanju.',
            ]));
        $early->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::STARTUP_PRIORITY_PENDING_MESSAGE,
            $early->exception?->getMessage()
        );
        $this->assertFalse($priority['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->saveCapSupport($priority, $startup);
        $this->actingAs($priority['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $development), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Ostatak nije dodijeljen razvoju.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertTrue($priority['competition']->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame((int) $startup->fresh()->ranking_position, (int) $development->fresh()->ranking_position);
        $this->assertSame((string) $startup->fresh()->final_score, (string) $development->fresh()->final_score);
        $this->assertSame(0, $roundsFor($priority['competition']));
    }

    public function test_fully_covered_group_consumes_all_competing_drafts(): void
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockScore($ctx, $ctx['application'], 4);
        $this->confirmYouthBonuses($ctx);
        $tied = $this->addScoredApplication($ctx, 'Izjednačena pokrivena', 5000, 4);
        $this->saveSupport($ctx, $ctx['application'], 5000);
        $this->saveSupport($ctx, $tied, 5000);

        $lower = $this->addLowerRankScoredApplication($ctx, 'Nizi pokrivena grupa', 5000);
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $lower))
            ->assertOk();
        $this->assertSame(3, (int) $lower->fresh()->ranking_position);
        $this->assertSame(
            '90000.00',
            app(YouthEqualScoreVotingService::class)
                ->remainingBeforeApplication($ctx['competition']->fresh(), $lower->fresh())
        );
        $this->assertSame([], app(YouthEqualScoreVotingService::class)->detectBoundaryGroups($ctx['competition']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 5000,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('5000.00', $lower->fresh()->approved_amount);
    }

    public function test_womens_equal_score_flow_stays_untouched(): void
    {
        $zensko = $this->makeZenskoCall();
        $this->assertNull(app(ZpEqualScoreAllocationGuard::class)->blockReason($zensko['competition']));

        $response = $this->actingAs($zensko['chairman'])
            ->post(route('evaluation.youth-equal-score-round', $zensko['competition']), [
                'justification' => 'Zensko.',
                'selected' => [1],
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::NOT_YOUTH_MESSAGE, $response->exception?->getMessage());
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());

        $youth = $this->twoTiedCoveredByBudget();
        $this->assertNull(app(ZpEqualScoreAllocationGuard::class)->blockReason($youth['competition']->fresh()));
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }

    /**
     * @param  list<string>  $votes
     */
    private function lockWithVotes(array $votes): YouthEqualScoreRound
    {
        $ctx = $this->twoAppsFundsForOne();

        return $this->saveAndLock($ctx, [$ctx['tied'][0]->id], $votes);
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, tied: list<Application>}  $ctx
     * @param  list<int>  $selected
     * @param  list<string>  $votes
     */
    private function assertVotingWriteRefused(callable $call): void
    {
        try {
            $call();
            $this->fail('Upis elektronskog glasanja mora biti odbijen.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame(YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE, $exception->getMessage());
        }
    }

    /**
     * Istorijski zapis za provjeru postojećih pravila budžeta i ranga.
     * Ne poziva ukinuti upis glasanja.
     *
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, tied: list<Application>}  $ctx
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
        $outcome = $forCount >= 2
            ? YouthEqualScoreRound::OUTCOME_ADOPTED
            : YouthEqualScoreRound::OUTCOME_REJECTED;
        $group = YouthEqualScoreGroup::query()->create([
            'competition_id' => $ctx['competition']->id,
            'group_key' => $groupData['group_key'],
            'full_score' => $groupData['full_score'],
            'ranking_position' => $groupData['ranking_position'],
            'applied_rule' => $groupData['applied_rule'],
        ]);
        $round = YouthEqualScoreRound::query()->create([
            'group_id' => $group->id,
            'round_no' => (int) YouthEqualScoreRound::query()->where('group_id', $group->id)->max('round_no') + 1,
            'justification' => 'Podrška izjednačenoj prijavi.',
            'budget_before' => $groupData['remaining_before'],
            'proposal_total' => $proposal,
            'locked_at' => now(),
            'outcome' => $outcome,
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

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application, tied: Application}
     */
    private function twoTiedCoveredByBudget(): array
    {
        $ctx = $this->rankingReady();
        $tied = $this->addScoredApplication($ctx, 'Izjednačena pokrivena', 5000, 3);
        $this->saveSupport($ctx, $ctx['application'], 5000);
        $this->saveSupport($ctx, $tied, 5000);
        $ctx['tied'] = $tied;

        return $ctx;
    }

    /**
     * Granična izjednačena grupa na 39.333, ostatak prije grupe 40000.
     * Niža prijava sa kriterijumom 3 (30 bodova) dobija sopstveni rang 5.
     *
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, tied: list<Application>}
     */
    private function twoAppsFundsForOneWithLowerRankHeadroom(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 30000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $second = $this->addScoredApplication($ctx, 'Drugi rang', 30000, 4);
        $tiedA = $this->addScoredApplication($ctx, 'Izjednačena A', 30000, 4, 2);
        $tiedB = $this->addScoredApplication($ctx, 'Izjednačena B', 30000, 4, 2);
        $this->saveCapSupport($ctx, $ctx['application']);
        $this->saveCapSupport($ctx, $second);
        $this->saveCapSupport($ctx, $tiedA);
        $this->forceCompetingSupport($tiedB);
        $ctx['tied'] = [$tiedA, $tiedB];

        return $ctx;
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     */
    private function addLowerRankScoredApplication(array $ctx, string $name, int $requested): Application
    {
        return $this->addScoredApplication($ctx, $name, $requested, 3);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, tied: list<Application>}
     */
    private function annualOverflowMessage(CompetitionAnnualInstance $annual, int $year): ?string
    {
        try {
            $annual->remainingAfterFirst('omladinsko', $year);

            return null;
        } catch (DomainException $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * Dva viša iznosa po 30000 ostavljaju 40000 prije izjednačene grupe iste faze.
     *
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, tied: list<Application>}
     */
    private function unresolvedSameStageTie(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 30000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $second = $this->addScoredApplication($ctx, 'Drugi rang', 30000, 4);
        $tiedA = $this->addScoredApplication($ctx, 'Izjednačena A', 30000, 4, 2);
        $tiedB = $this->addScoredApplication($ctx, 'Izjednačena B', 30000, 4, 2);
        $this->saveCapSupport($ctx, $ctx['application']);
        $this->saveCapSupport($ctx, $second);
        $ctx['tied'] = [$tiedA, $tiedB];

        return $ctx;
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

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, tied: list<Application>}
     */
    private function threeAppsFundsForTwo(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 30000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $tiedA = $this->addScoredApplication($ctx, 'Tri A', 30000, 3);
        $tiedB = $this->addScoredApplication($ctx, 'Tri B', 30000, 3);
        $tiedC = $this->addScoredApplication($ctx, 'Tri C', 30000, 3);
        $this->saveCapSupport($ctx, $ctx['application']);
        $this->saveCapSupport($ctx, $tiedA);
        $this->saveCapSupport($ctx, $tiedB);
        $this->forceCompetingSupport($tiedC);
        $ctx['tied'] = [$tiedA, $tiedB, $tiedC];

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, tied: list<Application>}
     */
    private function threeAppsRemainingSixty(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 20000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $second = $this->addScoredApplication($ctx, 'Drugi rang 20', 20000, 4);
        $tiedA = $this->addScoredApplication($ctx, 'Tri 60 A', 30000, 3);
        $tiedB = $this->addScoredApplication($ctx, 'Tri 60 B', 30000, 3);
        $tiedC = $this->addScoredApplication($ctx, 'Tri 60 C', 30000, 3);
        $this->saveTwentySupport($ctx, $ctx['application']);
        $this->saveTwentySupport($ctx, $second);
        $this->saveCapSupport($ctx, $tiedA);
        $this->saveCapSupport($ctx, $tiedB);
        $this->forceCompetingSupport($tiedC);
        $ctx['tied'] = [$tiedA, $tiedB, $tiedC];

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, tied_startup: Application, tied_dev: Application}
     */
    private function startupPriorityResolvesFixture(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 30000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $second = $this->addScoredApplication($ctx, 'Drugi rang', 30000, 4);
        $startup = $this->addScoredApplication($ctx, 'Prioritet', 30000, 3);
        $dev = $this->addScoredApplication($ctx, 'Razvoj', 30000, 3);
        $dev->update(['business_stage' => KnApplicationClassification::STAGE_RAZVOJ]);
        $this->saveCapSupport($ctx, $ctx['application']);
        $this->saveCapSupport($ctx, $second);
        $this->saveCapSupport($ctx, $startup);
        $this->forceCompetingSupport($dev);
        $ctx['tied_startup'] = $startup;
        $ctx['tied_dev'] = $dev;

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, startup: Application, dev: Application}
     */
    private function singlePriorityDoesNotFit(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 30000]);
        $this->lockScore($ctx, $ctx['application'], 5);
        $this->confirmYouthBonuses($ctx);
        $second = $this->addScoredApplication($ctx, 'Drugi rang', 30000, 4);
        $third = $this->addScoredApplication($ctx, 'Treći rang', 30000, 4, 2);
        $startup = $this->addScoredApplication($ctx, 'Prioritet ne staje', 30000, 3);
        $dev = $this->addScoredApplication($ctx, 'Razvoj izjednačen', 30000, 3);
        $dev->update(['business_stage' => KnApplicationClassification::STAGE_RAZVOJ]);
        $this->saveCapSupport($ctx, $ctx['application']);
        $this->saveCapSupport($ctx, $second);
        $this->saveCapSupport($ctx, $third);
        $ctx['startup'] = $startup;
        $ctx['dev'] = $dev;

        return $ctx;
    }

    /**
     * @param  array{chairman: CommissionMember}  $ctx
     */
    private function saveSupport(array $ctx, Application $application, int $amount): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $application), $this->supportDraft([
                'approved_amount' => $amount,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array{chairman: CommissionMember}  $ctx
     */
    private function saveTwentySupport(array $ctx, Application $application): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $application), $this->supportDraft([
                'approved_amount' => 20000,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array{chairman: CommissionMember}  $ctx
     */
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function capDraft(array $overrides = []): array
    {
        return array_merge([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 30000,
            'youth_innovative_tech_startup' => '1',
            'youth_prior_municipal_youth_funding' => '0',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function supportDraft(array $overrides = []): array
    {
        return array_merge([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 5000,
            'youth_innovative_tech_startup' => '0',
            'youth_prior_municipal_youth_funding' => '0',
        ], $overrides);
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}  $ctx
     */
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

    /**
     * @param  array{members: list<CommissionMember>}  $ctx
     * @param  array<int, array<string, int>>  $bySeat
     */
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

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function rankingReady(int $callNumber = 1, ?int $year = null): array
    {
        $ctx = $this->youthReadyToLock($callNumber, $year);
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockScore($ctx, $ctx['application'], 3);
        $this->confirmYouthBonuses($ctx);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        return $ctx;
    }

    /**
     * @param  array{chairman: CommissionMember, application: Application}  $ctx
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
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     */
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

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthReadyToLock(int $callNumber = 1, ?int $year = null): array
    {
        return $this->youthWithPassedM3($callNumber, $year);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
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

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthReadyContext(int $callNumber = 1, ?int $year = null): array
    {
        return $this->makeYouthContext($callNumber, $year);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(int $callNumber = 1, ?int $year = null): array
    {
        $year ??= self::$yearSerial++;
        if ($callNumber === 1) {
            self::$yearSerial = max(self::$yearSerial, $year + 1);
        }
        $commission = Commission::create([
            'name' => 'Mladi glasanje '.$year.'-'.$callNumber,
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
            'title' => 'Omladinsko glasanje '.$year.' poziv '.$callNumber,
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
            'competition_number' => 'UP-G-'.$year.'-'.$callNumber,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan glasanje '.$year.'-'.$callNumber,
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

    /**
     * @return array{competition: Competition, chairman: User}
     */
    private function makeZenskoCall(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska glasanje '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);
        $user = $this->userWithRole('komisija');
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
        ]);
        $competition = Competition::create([
            'title' => 'Zensko glasanje',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-Z-'.uniqid(),
        ]);

        return ['competition' => $competition, 'chairman' => $user];
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
