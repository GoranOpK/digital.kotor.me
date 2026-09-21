<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\YouthSecondSessionGate;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthScoringCycleAndPreliminaryRankingTest extends TestCase
{
    use RefreshDatabase;

    private static int $yearSerial = 2100;

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

    public function test_cycle_waits_for_three_seats_not_four_or_five_and_one_incomplete_application_blocks(): void
    {
        $ctx = $this->youthReadyToLock();
        $extra = $this->addM3ApplicationWithoutOral($ctx);
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);

        $service = app(CanonicalIndividualScoringService::class);
        $this->assertFalse($service->isYouthIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertFalse($service->isIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertSame(0, EvaluationScore::query()->whereIn('canonical_seat_no', [4, 5])->count());

        $html = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE, $html);
        $this->assertStringNotContainsString('Napomena mjesta 1', $html);
        $this->assertStringContainsString('Napomena mjesta 2', $html);

        $this->storeAndCompleteOral($ctx, true, $extra);
        $this->lockThreeSeats($ctx, [], $extra);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $extra]);
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());
        $this->assertSame(1, (int) $ctx['application']->fresh()->ranking_position);
        $this->assertSame(1, (int) $extra->fresh()->ranking_position);
    }

    public function test_rejected_application_does_not_block_and_open_window_or_podnesen_blocks_ranking_not_cycle(): void
    {
        $ctx = $this->youthReadyToLock();
        $failed = $this->addFailedApplication($ctx);
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);

        $service = app(CanonicalIndividualScoringService::class);
        $competition = $ctx['competition']->fresh();
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($competition));
        $this->assertFalse($service->isYouthPreliminaryRankingReady($competition));
        $this->assertSame(
            CanonicalIndividualScoringService::YOUTH_RANKING_APPEAL_WINDOW_MESSAGE,
            $service->youthPreliminaryRankingBlockReason($competition)
        );
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Napomena mjesta 1', $memberHtml);
        $this->assertStringContainsString('Napomena mjesta 2', $memberHtml);
        $this->assertStringContainsString('Napomena mjesta 3', $memberHtml);
        $this->assertStringContainsString('Predsjednik', $memberHtml);
        $this->assertStringContainsString('Član mjesta 2', $memberHtml);
        $this->assertStringContainsString('Član mjesta 3', $memberHtml);
        $this->assertGreaterThan(10, substr_count($memberHtml, 'class="score-display"'));
        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_RANKING_APPEAL_WINDOW_MESSAGE, $memberHtml);
        $this->assertStringNotContainsString('Preliminarna rang-lista', $memberHtml);

        $this->actingAs($failed->user)
            ->post(route('applications.prigovor.store', $failed), [
                'contested' => [1],
                'criterion_obrazlozenja' => [1 => 'Izvod je već bio u prijavi.'],
            ])
            ->assertRedirect();
        $this->assertTrue($failed->fresh()->prigovor->isPodnesen());
        $this->assertSame(
            CanonicalIndividualScoringService::YOUTH_RANKING_PRIGOVOR_PODNESEN_MESSAGE,
            $service->youthPreliminaryRankingBlockReason($ctx['competition']->fresh())
        );
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $failed), [
                'decision_note' => 'Razlog ostaje.',
                'criterion_outcomes' => [1 => 'ostaje'],
            ])
            ->assertRedirect();
        $this->assertSame('rejected', $failed->fresh()->status);
        $this->assertTrue($service->isYouthPreliminaryRankingReady($ctx['competition']->fresh()));
        $this->assertSame(1, (int) $ctx['application']->fresh()->ranking_position);
        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
        $this->assertNull($failed->fresh()->ranking_position);
    }

    public function test_favorable_prigovor_enters_scoring_set_and_incomplete_oral_blocks_cycle(): void
    {
        $ctx = $this->youthReadyToLock();
        $failed = $this->addFailedApplication($ctx);
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);
        $this->actingAs($failed->user)
            ->post(route('applications.prigovor.store', $failed), [
                'contested' => [1, 2],
                'criterion_obrazlozenja' => [
                    1 => 'Izvod je već bio u prijavi.',
                    2 => 'M4 je dostavljen.',
                ],
            ])
            ->assertRedirect();
        $decisionTime = now();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $failed), [
                'decision_note' => 'Svi osporeni razlozi su otklonjeni.',
                'criterion_outcomes' => [1 => 'otklonjen', 2 => 'otklonjen'],
            ])
            ->assertRedirect();
        $this->assertSame('submitted', $failed->fresh()->status);

        $this->travelTo($decisionTime->copy()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->addSecond());

        $appealWindowService = app(YouthSecondSessionGate::class);
        $this->assertFalse(
            $appealWindowService->appealWindowIsOpen($failed->fresh())
        );

        $service = app(CanonicalIndividualScoringService::class);
        $this->assertTrue(
            $service->youthPositiveScoringApplications($ctx['competition']->fresh())->contains('id', $failed->id)
        );
        $this->assertNull($failed->fresh('oralPresentation')->oralPresentation?->completed_at);
        $this->assertFalse($service->isYouthIndividualScoringCycleComplete($ctx['competition']->fresh()));

        $this->storeAndCompleteOral($ctx, true, $failed);
        $this->assertFalse($service->isYouthIndividualScoringCycleComplete($ctx['competition']->fresh()));

        $this->lockThreeSeats($ctx, [], $failed);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $failed]);
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertNotNull($failed->fresh()->ranking_position);
        $this->travelBack();
    }

    public function test_mutual_scores_open_after_cycle_without_waiting_for_bonuses_and_unauthorized_actors_are_blocked(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx);
        $service = app(CanonicalIndividualScoringService::class);
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertFalse($ctx['competition']->fresh()->isRankingFormed());
        $this->assertNull($ctx['application']->fresh()->final_score);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        foreach ($ctx['members'] as $member) {
            $html = $this->actingAs($member->fresh('user')->user)
                ->get(route('evaluation.create', $ctx['application']))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString('Napomena mjesta 1', $html);
            $this->assertStringContainsString('Napomena mjesta 2', $html);
            $this->assertStringContainsString('Napomena mjesta 3', $html);
            $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_RANKING_BONUSES_UNCONFIRMED_MESSAGE, $html);
            $this->assertStringNotContainsString('Preliminarna rang-lista', $html);
        }

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('evaluation.create', $ctx['application']))
            ->assertForbidden();
        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->get(route('evaluation.create', $ctx['application']))
            ->assertRedirect(route('admin.dashboard'));
        $this->actingAs($ctx['application']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertForbidden();
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertForbidden();
        $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertForbidden();

        $other = $this->youthReadyToLock();
        $this->actingAs($other['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertForbidden();
        $this->actingAs($other['chairman']->user)
            ->get(route('evaluation.index', ['competition_id' => $ctx['competition']->id]))
            ->assertForbidden();
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $sharedUser = $other['members'][1]->user;
        $ctx['members'][1]->update(['user_id' => $sharedUser->id, 'name' => $sharedUser->name]);
        $html = $this->actingAs($sharedUser)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Napomena mjesta 1', $html);
        $otherHtml = $this->actingAs($sharedUser)
            ->get(route('evaluation.create', $other['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Napomena mjesta 1', $otherHtml);
    }

    public function test_ranking_uses_full_precision_shared_places_threshold_and_is_idempotent(): void
    {
        $ctx = $this->youthReadyToLock();
        $second = $this->addPassedApplication($ctx, 'Drugi plan');
        $third = $this->addPassedApplication($ctx, 'Treci plan');
        $fourth = $this->addPassedApplication($ctx, 'Cetvrti plan');

        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(5),
            2 => $this->allCriteria(5),
            3 => $this->allCriteria(5),
        ]);
        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(4),
            2 => $this->allCriteria(4),
            3 => $this->allCriteria(4),
        ], $second);
        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(4),
            2 => $this->allCriteria(4),
            3 => $this->allCriteria(4),
        ], $third);
        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(3),
            2 => $this->allCriteria(3),
            3 => $this->allCriteria(3),
        ], $fourth);

        $this->confirmYouthBonuses($ctx);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $second]);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $third]);
        $this->assertNull($ctx['application']->fresh()->ranking_position);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $fourth]);

        $first = $ctx['application']->fresh();
        $second = $second->fresh();
        $third = $third->fresh();
        $fourth = $fourth->fresh();
        $this->assertSame('50.00', $first->final_score);
        $this->assertSame('40.00', $second->final_score);
        $this->assertSame('40.00', $third->final_score);
        $this->assertSame('30.00', $fourth->final_score);
        $this->assertSame(1, (int) $first->ranking_position);
        $this->assertSame(2, (int) $second->ranking_position);
        $this->assertSame(2, (int) $third->ranking_position);
        $this->assertSame(4, (int) $fourth->ranking_position);
        foreach ([$first, $second, $third, $fourth] as $application) {
            $this->assertSame('evaluated', $application->status);
            $this->assertNotSame('approved', $application->status);
            $this->assertNotSame('rejected', $application->status);
            $this->assertNull($application->approved_amount);
        }

        $below = $this->addPassedApplication($ctx, 'Ispod praga');
        $this->lockThreeSeats($ctx, [1 => ['criterion_1' => 2]], $below);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $below]);
        $below = $below->fresh();
        $this->assertSame('29.67', $below->final_score);
        $this->assertSame('evaluated', $below->status);
        $this->assertNull($below->ranking_position);
        $this->assertNull($below->approved_amount);
        $this->assertSame(1, (int) $first->fresh()->ranking_position);
        $this->assertSame(4, (int) $fourth->fresh()->ranking_position);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Preliminarna rang-lista', $html);
        $this->assertStringContainsString('Ispod praga', $html);
        $this->assertStringContainsString('nije konačna odluka o podršci', $html);
        $this->assertStringContainsString($below->business_plan_name, $html);
        $this->assertStringNotContainsString('Dobitnik sredstava', $html);

        $applicantHtml = $this->actingAs($first->user)
            ->get(route('evaluation.index'));
        $applicantHtml->assertForbidden();

        $displayTieA = $this->addPassedApplication($ctx, 'Prikaz A');
        $displayTieB = $this->addPassedApplication($ctx, 'Prikaz B');
        $this->lockThreeSeats($ctx, [1 => ['criterion_1' => 3], 2 => ['criterion_1' => 3], 3 => ['criterion_1' => 4]], $displayTieA);
        $this->lockThreeSeats($ctx, [1 => ['criterion_1' => 3], 2 => ['criterion_1' => 4], 3 => ['criterion_1' => 4]], $displayTieB);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $displayTieA]);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $displayTieB]);
        $displayTieA->fresh()->forceFill(['final_score' => '30.33'])->save();
        $displayTieB->fresh()->forceFill(['final_score' => '30.33'])->save();
        $this->assertSame('30.33', $displayTieA->fresh()->final_score);
        $this->assertSame('30.33', $displayTieB->fresh()->final_score);
        $service = app(CanonicalIndividualScoringService::class);
        $fullA = $service->aggregateYouthApplication($displayTieA->fresh())['final_score_full'];
        $fullB = $service->aggregateYouthApplication($displayTieB->fresh())['final_score_full'];
        $this->assertNotSame(0, bccomp($fullA, $fullB, CanonicalIndividualScoringService::YOUTH_BONUS_SCALE));
        $this->assertSame(-1, bccomp($fullA, $fullB, CanonicalIndividualScoringService::YOUTH_BONUS_SCALE));
        $service->persistYouthPreliminaryRankingIfReady($ctx['competition']->fresh());
        $this->assertLessThan(
            (int) $displayTieA->fresh()->ranking_position,
            (int) $displayTieB->fresh()->ranking_position
        );

        $before = [
            $first->id => $first->fresh()->ranking_position,
            $second->id => $second->fresh()->ranking_position,
            $third->id => $third->fresh()->ranking_position,
            $fourth->id => $fourth->fresh()->ranking_position,
            $below->id => $below->fresh()->ranking_position,
        ];
        $evaluatedAt = $first->fresh()->evaluated_at->toDateTimeString();
        $service->persistYouthPreliminaryRankingIfReady($ctx['competition']->fresh());
        $this->assertSame($before[$first->id], $first->fresh()->ranking_position);
        $this->assertSame($before[$second->id], $second->fresh()->ranking_position);
        $this->assertSame($before[$third->id], $third->fresh()->ranking_position);
        $this->assertSame($before[$fourth->id], $fourth->fresh()->ranking_position);
        $this->assertNull($below->fresh()->ranking_position);
        $this->assertSame($evaluatedAt, $first->fresh()->evaluated_at->toDateTimeString());
        $this->assertSame('50.00', $first->fresh()->final_score);
        $this->assertSame('evaluated', $first->fresh()->status);
    }

    public function test_second_call_has_separate_cycle_and_does_not_change_first(): void
    {
        $first = $this->youthReadyToLock();
        $this->lockThreeSeats($first);
        $this->confirmYouthBonuses($first);
        $this->assertSame(1, (int) $first['application']->fresh()->ranking_position);

        $second = $this->youthReadyToLock(2, $first['competition']->year);
        $this->lockThreeSeats($second, [
            1 => $this->allCriteria(5),
            2 => $this->allCriteria(5),
            3 => $this->allCriteria(5),
        ]);
        $this->assertTrue(app(CanonicalIndividualScoringService::class)->isYouthIndividualScoringCycleComplete($first['competition']->fresh()));
        $this->assertTrue(app(CanonicalIndividualScoringService::class)->isYouthIndividualScoringCycleComplete($second['competition']->fresh()));
        $this->assertFalse($second['competition']->fresh()->isRankingFormed());
        $this->confirmYouthBonuses($second, [
            'bonus_green_innovative' => '1',
        ]);
        $this->assertSame(1, (int) $first['application']->fresh()->ranking_position);
        $this->assertSame('30.00', $first['application']->fresh()->final_score);
        $this->assertSame(1, (int) $second['application']->fresh()->ranking_position);
        $this->assertSame('53.00', $second['application']->fresh()->final_score);
    }

    public function test_expired_window_forms_ranking_on_authorized_index_and_zensko_cycle_remains(): void
    {
        $ctx = $this->youthReadyToLock();
        $failed = $this->addFailedApplication($ctx);
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $failed->eliminatoryNotice->forceFill([
            'sent_at' => now()->subDays(4),
        ])->save();

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('evaluation.index'))
            ->assertForbidden();
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertSame('rejected', $failed->fresh()->status);
        $this->assertSame(1, (int) $ctx['application']->fresh()->ranking_position);
        $this->assertStringContainsString('Preliminarna rang-lista', $html);
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, CompetitionProgramCatalog::definitions()['omladinsko']['status']);

        $zensko = $this->zenskoReadyToScore();
        foreach ($zensko['members']->take(4) as $index => $member) {
            EvaluationScore::query()->create(array_merge($this->allCriteria(5), [
                'application_id' => $zensko['application']->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $index + 1,
                'notes' => 'Ženska napomena '.$index,
            ]));
        }
        $this->assertFalse($zensko['competition']->fresh()->isIndividualScoringCycleComplete());
        $html = $this->actingAs($zensko['members'][0]->fresh('user')->user)
            ->get(route('evaluation.create', $zensko['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Ženska napomena 1', $html);
        EvaluationScore::query()->create(array_merge($this->allCriteria(5), [
            'application_id' => $zensko['application']->id,
            'commission_member_id' => $zensko['members'][4]->id,
            'canonical_seat_no' => 5,
            'notes' => 'Ženska napomena 4',
        ]));
        app(CanonicalIndividualScoringService::class)->persistApplicationAggregatesIfCycleComplete($zensko['competition']->fresh());
        $this->assertTrue($zensko['competition']->fresh()->isIndividualScoringCycleComplete());
        $this->assertTrue($zensko['competition']->fresh()->isRankingFormed());
        $this->assertNotNull($zensko['application']->fresh()->ranking_position);
    }

    public function test_formed_youth_ranking_survives_approved_and_rejected_status_changes(): void
    {
        $ctx = $this->youthReadyToLock();
        $below = $this->addPassedApplication($ctx, 'Ispod praga');
        $this->lockThreeSeats($ctx);
        $this->lockThreeSeats($ctx, [1 => ['criterion_1' => 2]], $below);
        $this->confirmYouthBonuses($ctx);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $below]);

        $service = app(CanonicalIndividualScoringService::class);
        $competition = $ctx['competition']->fresh();
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($competition));
        $this->assertTrue($service->isYouthPreliminaryRankingReady($competition));
        $this->assertTrue($competition->isRankingFormed());
        $this->assertNotNull($ctx['application']->fresh()->ranking_position);
        $this->assertNull($below->fresh()->ranking_position);

        $ctx['application']->fresh()->forceFill([
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '1000.00',
            'commission_justification' => 'Podrška.',
        ])->save();
        $competition = $ctx['competition']->fresh();
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($competition));
        $this->assertTrue($service->isYouthPreliminaryRankingReady($competition));
        $this->assertTrue($competition->isRankingFormed());

        $below->fresh()->forceFill([
            'status' => 'rejected',
            'commission_decision' => 'odbija',
            'commission_justification' => 'Ispod praga.',
            'rejection_reason' => 'Ispod praga.',
        ])->save();
        $this->assertNull($below->fresh()->ranking_position);
        $this->assertTrue($service->youthPositiveScoringApplications($ctx['competition']->fresh())->isEmpty());
        $competition = $ctx['competition']->fresh();
        $this->assertTrue($service->isYouthIndividualScoringCycleComplete($competition));
        $this->assertTrue($service->isYouthPreliminaryRankingReady($competition));
        $this->assertTrue($competition->isRankingFormed());
        $service->persistYouthPreliminaryRankingIfReady($competition);
        $this->assertNotNull($ctx['application']->fresh()->ranking_position);
        $this->assertNull($below->fresh()->ranking_position);
        $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());

        $bare = $this->makeYouthContext();
        $bare['application']->forceFill([
            'status' => 'approved',
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => '1000.00',
        ])->save();
        $this->assertFalse($service->isYouthPreliminaryRankingReady($bare['competition']->fresh()));
        $this->assertFalse($bare['competition']->fresh()->isRankingFormed());

        $zensko = $this->zenskoReadyToScore();
        foreach ($zensko['members'] as $index => $member) {
            EvaluationScore::query()->create(array_merge($this->allCriteria(5), [
                'application_id' => $zensko['application']->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $index + 1,
                'notes' => 'Ženska ocjena '.$index,
            ]));
        }
        $service->persistApplicationAggregatesIfCycleComplete($zensko['competition']->fresh());
        $this->assertTrue($zensko['competition']->fresh()->isIndividualScoringCycleComplete());
        $this->assertTrue($zensko['competition']->fresh()->isRankingFormed());
        $zensko['application']->fresh()->forceFill(['status' => 'approved'])->save();
        $this->assertTrue($zensko['competition']->fresh()->isRankingFormed());
        $this->assertTrue($zensko['competition']->fresh()->isIndividualScoringCycleComplete());
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}  $ctx
     * @param  array<int, array<string, int>>  $bySeat
     */
    private function lockThreeSeats(array $ctx, array $bySeat = [], ?Application $application = null): void
    {
        $application ??= $ctx['application'];
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $payload = $this->finalPayload($bySeat[$seat] ?? []);
            $payload['notes'] = 'Napomena mjesta '.$seat;
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $application), $payload)
                ->assertSessionHasNoErrors();
        }
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
    private function addM3ApplicationWithoutOral(array $ctx, string $name = 'Bez usmenog'): Application
    {
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => $name.' '.self::$yearSerial,
            'applicant_type' => KnApplicationClassification::FORM_FIZICKO_LICE,
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(24),
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
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
    private function addFailedApplication(array $ctx): Application
    {
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Neuspjesni plan '.self::$yearSerial,
            'applicant_type' => KnApplicationClassification::FORM_FIZICKO_LICE,
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(24),
        ]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '0',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'criterion_notes' => [
                    1 => 'Nedostaje izvod',
                    2 => 'Nema M4',
                    3 => '',
                ],
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        return $application->fresh(['user', 'eliminatoryNotice', 'prigovor']);
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
            'name' => 'Mladi ciklus '.$year.'-'.$callNumber,
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
            'title' => 'Omladinsko ciklus '.$year.' poziv '.$callNumber,
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
            'business_plan_name' => 'Plan ciklus '.$year.'-'.$callNumber,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application->fresh('user'),
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: \Illuminate\Support\Collection<int, CommissionMember>, application: Application}
     */
    private function zenskoReadyToScore(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska ciklus '.uniqid(),
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
            'title' => 'Zensko ciklus '.uniqid(),
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
