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
            YouthEqualScoreVotingService::RANKING_NOT_READY_MESSAGE,
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
            YouthEqualScoreVotingService::NO_BOUNDARY_GROUP_MESSAGE,
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
        $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_two_apps_funds_for_one_and_three_apps_funds_for_two(): void
    {
        $two = $this->twoAppsFundsForOne();
        $html = $this->actingAs($two['chairman']->user)
            ->get(route('evaluation.create', $two['tied'][0]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-equal-score-voting"', $html);
        $this->assertStringContainsString('data-testid="youth-equal-score-round-form"', $html);
        $this->assertSame(0, YouthEqualScoreGroup::query()->count());

        $this->actingAs($two['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $two['competition']), [
                'justification' => 'Podrška jednoj od dvije izjednačene prijave.',
                'selected' => [$two['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'against'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $round = YouthEqualScoreRound::query()->firstOrFail();
        $this->assertSame(2, YouthEqualScoreRoundApplication::query()->where('round_id', $round->id)->count());
        $this->assertTrue(
            (bool) YouthEqualScoreRoundApplication::query()
                ->where('round_id', $round->id)
                ->where('application_id', $two['tied'][0]->id)
                ->value('selected')
        );
        $this->assertFalse(
            (bool) YouthEqualScoreRoundApplication::query()
                ->where('round_id', $round->id)
                ->where('application_id', $two['tied'][1]->id)
                ->value('selected')
        );
        $this->assertSame('30000.00', $round->proposal_total);
        $this->assertNull($round->outcome);
        $this->assertNull($round->locked_at);

        $three = $this->threeAppsFundsForTwo();
        $this->actingAs($three['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $three['competition']), [
                'justification' => 'Dvije od tri.',
                'selected' => [$three['tied'][0]->id, $three['tied'][1]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $threeRound = YouthEqualScoreRound::query()
            ->whereHas('group', fn ($q) => $q->where('competition_id', $three['competition']->id))
            ->firstOrFail();
        $this->assertSame(3, YouthEqualScoreRoundApplication::query()->where('round_id', $threeRound->id)->count());
        $this->assertSame('60000.00', $threeRound->proposal_total);
        $this->assertSame(2, YouthEqualScoreRoundApplication::query()->where('round_id', $threeRound->id)->where('selected', true)->count());
    }

    public function test_different_draft_amounts_and_no_automatic_selection(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['tied'][1]))
            ->post(route('evaluation.youth-allocation-draft', $ctx['tied'][1]), $this->capDraft([
                'approved_amount' => 20000,
                'commission_justification' => 'Smanjen iznos nacrta.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Bez automatskog izbora.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $round = YouthEqualScoreRound::query()->firstOrFail();
        $this->assertSame('0.00', $round->proposal_total);
        $this->assertSame(0, YouthEqualScoreRoundApplication::query()->where('round_id', $round->id)->where('selected', true)->count());
        $amounts = YouthEqualScoreRoundApplication::query()
            ->where('round_id', $round->id)
            ->orderBy('application_id')
            ->pluck('draft_amount')
            ->map(fn ($v) => (string) $v)
            ->all();
        $this->assertContains('30000.00', $amounts);
        $this->assertContains('20000.00', $amounts);
        $this->assertSame('evaluated', $ctx['tied'][0]->fresh()->status);
        $this->assertSame('evaluated', $ctx['tied'][1]->fresh()->status);
    }

    public function test_three_votes_are_required_before_lock(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Dva glasa nijesu dovoljna.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $round = YouthEqualScoreRound::query()->firstOrFail();
        $this->assertSame(2, YouthEqualScoreVote::query()->where('round_id', $round->id)->count());
        $this->assertNull($round->outcome);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['tied'][0]))
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]))
            ->assertRedirect()
            ->assertSessionHasErrors('votes');

        $round->refresh();
        $this->assertNull($round->locked_at);
        $this->assertNull($round->outcome);
    }

    public function test_outcomes_three_zero_two_one_one_two_and_zero_three(): void
    {
        $this->assertSame('adopted', $this->lockWithVotes(['for', 'for', 'for'])->outcome);
        $this->assertSame('adopted', $this->lockWithVotes(['for', 'for', 'against'])->outcome);
        $this->assertSame('rejected', $this->lockWithVotes(['for', 'against', 'against'])->outcome);
        $this->assertSame('rejected', $this->lockWithVotes(['against', 'against', 'against'])->outcome);
    }

    public function test_rejected_round_allows_new_and_adopted_forbids_new(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['against', 'against', 'for']);
        $first = YouthEqualScoreRound::query()->firstOrFail();
        $this->assertSame(YouthEqualScoreRound::OUTCOME_REJECTED, $first->outcome);
        $this->assertSame(1, (int) $first->round_no);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Novi krug poslije odbijanja.',
                'selected' => [$ctx['tied'][1]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $second = YouthEqualScoreRound::query()->where('round_no', 2)->firstOrFail();
        $this->assertNull($second->locked_at);
        $this->assertSame(1, YouthEqualScoreRound::query()->whereNotNull('locked_at')->count());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $second]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $second->fresh()->outcome);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Poslije usvajanja.',
                'selected' => [$ctx['tied'][0]->id],
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthEqualScoreVotingService::ADOPTED_ROUND_EXISTS_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertSame(2, YouthEqualScoreRound::query()->count());
        $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());
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
            YouthEqualScoreVotingService::ADOPTED_ROUND_EXISTS_MESSAGE,
            $response->exception?->getMessage()
        );

        $lockAgain = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]));
        $lockAgain->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::ROUND_LOCKED_MESSAGE, $lockAgain->exception?->getMessage());

        $this->assertSame($beforeVotes, YouthEqualScoreVote::query()->where('round_id', $round->id)->get()->toArray());
        $this->assertSame($beforeApps, YouthEqualScoreRoundApplication::query()->where('round_id', $round->id)->get()->toArray());
        $this->assertSame('Podrška izjednačenoj prijavi.', $round->fresh()->justification);
    }

    public function test_member_replacement_before_and_after_lock(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Prije zamjene.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'for', 3 => 'for'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $round = YouthEqualScoreRound::query()->firstOrFail();
        $oldSeatTwo = $ctx['members'][1];
        $oldVoteUser = (int) YouthEqualScoreVote::query()
            ->where('round_id', $round->id)
            ->where('canonical_seat_no', 2)
            ->value('user_id');

        $oldSeatTwo->update(['status' => 'inactive']);
        $replacement = $this->userWithRole('komisija');
        $newSeatTwo = CommissionMember::create([
            'commission_id' => $ctx['competition']->commission_id,
            'user_id' => $replacement->id,
            'name' => $replacement->name,
            'position' => 'clan',
            'member_type' => null,
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['tied'][0]))
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]))
            ->assertRedirect()
            ->assertSessionHasErrors('votes');
        $this->assertNull($round->fresh()->locked_at);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Glas novog člana.',
                'selected' => [$ctx['tied'][0]->id],
                'votes' => [1 => 'for', 2 => 'against', 3 => 'for'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round.lock', [$ctx['competition'], $round]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $seatTwoVote = YouthEqualScoreVote::query()
            ->where('round_id', $round->id)
            ->where('canonical_seat_no', 2)
            ->firstOrFail();
        $this->assertSame((int) $newSeatTwo->id, (int) $seatTwoVote->commission_member_id);
        $this->assertSame((int) $replacement->id, (int) $seatTwoVote->user_id);
        $this->assertNotSame($oldVoteUser, (int) $seatTwoVote->user_id);
        $this->assertSame(YouthEqualScoreVote::AGAINST, $seatTwoVote->vote_value);
        $this->assertSame(YouthEqualScoreRound::OUTCOME_ADOPTED, $round->fresh()->outcome);

        $later = $this->userWithRole('komisija');
        $ctx['members'][2]->update(['status' => 'inactive']);
        CommissionMember::create([
            'commission_id' => $ctx['competition']->commission_id,
            'user_id' => $later->id,
            'name' => $later->name,
            'position' => 'clan',
            'member_type' => null,
            'canonical_seat_no' => 3,
            'status' => 'active',
        ]);
        $seatThree = YouthEqualScoreVote::query()
            ->where('round_id', $round->id)
            ->where('canonical_seat_no', 3)
            ->firstOrFail();
        $this->assertSame((int) $ctx['members'][2]->id, (int) $seatThree->commission_member_id);
        $this->assertNotSame((int) $later->id, (int) $seatThree->user_id);
    }

    public function test_malicious_snapshot_and_audit_payload_is_ignored(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $spoofUser = $this->userWithRole('komisija');
        $this->actingAs($ctx['chairman']->user)
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
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $round = YouthEqualScoreRound::query()->firstOrFail();
        $this->assertNull($round->locked_at);
        $this->assertNull($round->outcome);
        $this->assertSame($ctx['chairman']->name, $round->created_by_name);
        $this->assertSame((int) $ctx['chairman']->user_id, (int) $round->created_by_user_id);
        $this->assertSame('30000.00', $round->proposal_total);
        $item = YouthEqualScoreRoundApplication::query()
            ->where('round_id', $round->id)
            ->where('application_id', $ctx['tied'][0]->id)
            ->firstOrFail();
        $this->assertSame(KnApplicationClassification::STAGE_ZAPOCINJANJE, $item->business_stage);
        $this->assertSame('30000.00', $item->draft_amount);
        $this->assertSame(30, (int) $item->applied_cap_percent);
        $vote = YouthEqualScoreVote::query()
            ->where('round_id', $round->id)
            ->where('canonical_seat_no', 1)
            ->firstOrFail();
        $this->assertSame((int) $ctx['chairman']->id, (int) $vote->commission_member_id);
        $this->assertSame((int) $ctx['chairman']->user_id, (int) $vote->user_id);
        $this->assertSame($ctx['chairman']->name, $vote->member_name);
        $this->assertSame((int) $ctx['chairman']->user_id, (int) $vote->recorded_by_user_id);
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
        $this->assertSame(YouthEqualScoreVotingService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

        $response = $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), $payload);
        $response->assertForbidden();
        $this->assertSame(YouthEqualScoreVotingService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

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
        $this->assertSame(YouthEqualScoreVotingService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

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
        $this->assertSame(YouthEqualScoreVotingService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

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
        $this->assertSame('evaluated', $ctx['tied'][0]->fresh()->status);
    }

    public function test_status_stays_evaluated_and_remaining_after_first_is_unchanged(): void
    {
        $ctx = $this->twoAppsFundsForOne();
        $annualBefore = app(CompetitionAnnualInstance::class)
            ->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year);
        $statuses = collect($ctx['tied'])->map(fn (Application $app) => $app->fresh()->status)->all();
        $amounts = collect($ctx['tied'])->map(fn (Application $app) => $app->fresh()->approved_amount)->all();

        $this->saveAndLock($ctx, [$ctx['tied'][0]->id], ['for', 'for', 'for']);

        foreach ($ctx['tied'] as $i => $app) {
            $fresh = $app->fresh();
            $this->assertSame('evaluated', $fresh->status);
            $this->assertSame($statuses[$i], $fresh->status);
            $this->assertSame($amounts[$i], $fresh->approved_amount);
            $this->assertNotSame('approved', $fresh->status);
            $this->assertNotSame('rejected', $fresh->status);
        }
        $this->assertSame(
            $annualBefore,
            app(CompetitionAnnualInstance::class)->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year)
        );
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
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
        $this->assertSame('evaluated', $ctx['startup']->fresh()->status);
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
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['dev']), $this->capDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Smanjen iznos nacrta jer ne staje u preostalih 10000.',
            ]))
            ->assertSessionHasNoErrors();

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
            YouthEqualScoreVotingService::REDUCE_PRIORITY_DRAFT_MESSAGE,
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
        $this->assertSame('evaluated', $ctx['tied'][0]->fresh()->status);
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
            $this->assertSame('evaluated', $application->fresh()->status);
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

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10001,
                'commission_justification' => 'Iznad ostatka poslije usvojenog kruga.',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $lower))
            ->post(route('evaluation.youth-allocation-draft', $lower), $this->supportDraft([
                'approved_amount' => 10000,
                'commission_justification' => 'Iznos u okviru ostatka poslije usvojenog kruga.',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('10000.00', $lower->fresh()->approved_amount);
        $this->assertSame('evaluated', $lower->fresh()->status);
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
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
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
    private function saveAndLock(array $ctx, array $selected, array $votes): YouthEqualScoreRound
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-equal-score-round', $ctx['competition']), [
                'justification' => 'Podrška izjednačenoj prijavi.',
                'selected' => $selected,
                'votes' => [
                    1 => $votes[0],
                    2 => $votes[1],
                    3 => $votes[2],
                ],
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
        $this->saveCapSupport($ctx, $tiedB);
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
        $this->saveCapSupport($ctx, $tiedC);
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
        $this->saveCapSupport($ctx, $tiedC);
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
        $this->saveCapSupport($ctx, $dev);
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
        $this->storeAndCompleteOral($ctx, true, $application);

        return $application->fresh('user');
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthReadyToLock(int $callNumber = 1, ?int $year = null): array
    {
        $ctx = $this->youthWithPassedM3($callNumber, $year);
        $this->storeAndConfirmSecond($ctx);
        $this->storeAndCompleteOral($ctx, true);

        return $ctx;
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
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
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
