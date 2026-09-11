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
use App\Support\CommissionCanonicalSeat;
use App\Support\ScoringSeatBackfill;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CanonicalIndividualScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_final_score_requires_all_ten_criteria_and_explicit_confirmation(): void
    {
        [$application, $president] = $this->readyToScore();

        $incomplete = $this->scorePayload();
        unset($incomplete['criterion_10']);

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.store', $application), $incomplete)
            ->assertSessionHasErrors('criterion_10');

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.store', $application), $this->scorePayload(['scoring_confirmed' => '0']))
            ->assertSessionHasErrors('scoring_confirmed');

        $this->assertSame(0, EvaluationScore::where('application_id', $application->id)->whereCompletedFinal()->count());
    }

    public function test_first_final_score_succeeds_and_second_post_cannot_modify_scores_or_notes(): void
    {
        [$application, $president] = $this->readyToScore();

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'notes' => 'Prva napomena',
                'criterion_1' => 4,
            ]))
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $score = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $president->id)
            ->first();
        $this->assertNotNull($score->completed_at);
        $this->assertSame(1, (int) $score->canonical_seat_no);
        $this->assertSame(4, (int) $score->criterion_1);
        $this->assertSame('Prva napomena', $score->notes);
        $this->assertSame('submitted', $application->fresh()->status);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'notes' => 'Izmijenjena napomena',
                'criterion_1' => 1,
            ]))
            ->assertForbidden();

        $score->refresh();
        $this->assertSame(4, (int) $score->criterion_1);
        $this->assertSame('Prva napomena', $score->notes);
    }

    public function test_chairman_competition_admin_and_superadmin_cannot_modify_finalized_score(): void
    {
        [$application, $president, $member] = $this->readyToScore(withMember: true);

        $this->actingAs($member->user)
            ->post(route('evaluation.store', $application), $this->scorePayload(['notes' => 'Član']))
            ->assertRedirect();

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'criterion_1' => 1,
                'notes' => 'Predsjednikova sopstvena ocjena',
            ]))
            ->assertRedirect();

        $memberScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $member->id)
            ->first();
        $this->assertSame('Član', $memberScore->notes);
        $this->assertSame(5, (int) $memberScore->criterion_1);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'criterion_1' => 2,
                'notes' => 'Pokušaj izmjene predsjednikove ocjene',
            ]))
            ->assertForbidden();

        $presidentScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $president->id)
            ->first();
        $this->assertSame(1, (int) $presidentScore->criterion_1);
        $this->assertSame('Predsjednikova sopstvena ocjena', $presidentScore->notes);

        foreach (['konkurs_admin', 'superadmin', 'admin'] as $role) {
            $actor = $this->userWithRole($role);
            $response = $this->actingAs($actor)
                ->post(route('evaluation.store', $application), $this->scorePayload([
                    'criterion_1' => 1,
                    'notes' => 'Admin pokušaj izmjene',
                ]));
            $this->assertContains($response->status(), [401, 403, 302]);
        }

        $memberScore->refresh();
        $this->assertSame('Član', $memberScore->notes);
        $this->assertSame(5, (int) $memberScore->criterion_1);
        $presidentScore->refresh();
        $this->assertSame(1, (int) $presidentScore->criterion_1);
        $this->assertSame('Predsjednikova sopstvena ocjena', $presidentScore->notes);
    }

    public function test_canonical_seats_predecessor_substitute_and_no_sixth_contribution(): void
    {
        [$application, $president, $firstClan, $competition, $members] = $this->readyToScore(returnAll: true);
        $seatTwo = $members->sortBy('id')->values()[1];
        $otherApplication = $this->createSubmittedApplication($competition);
        $this->confirmPass($president, $otherApplication);

        $this->actingAs($seatTwo->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect();

        $seatTwo->update(['status' => 'inactive']);
        $substitute = $this->makeSubstitute($seatTwo, 2);

        $this->actingAs($substitute->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->actingAs($substitute->user)
            ->post(route('evaluation.store', $otherApplication), $this->scorePayload())
            ->assertRedirect();

        $this->assertSame(2, (int) EvaluationScore::where('application_id', $otherApplication->id)
            ->where('commission_member_id', $substitute->id)
            ->value('canonical_seat_no'));

        $extra = $this->makeExtraMember($seatTwo->commission);
        $this->actingAs($extra->user)
            ->post(route('evaluation.store', $otherApplication), $this->scorePayload())
            ->assertForbidden();
    }

    public function test_completeness_uses_five_seats_not_active_count_and_inactive_predecessor_remains(): void
    {
        [$application, $president, $member, $competition, $members] = $this->readyToScore(returnAll: true);

        foreach ($members as $commissionMember) {
            $this->actingAs($commissionMember->user)
                ->post(route('evaluation.store', $application), $this->scorePayload())
                ->assertRedirect();
        }

        $members->sortBy('id')->values()[1]->update(['status' => 'inactive']);

        $this->assertTrue(app(CanonicalIndividualScoringService::class)->applicationHasFiveCanonicalSeats($application->fresh()));
        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNull($application->fresh()->rejection_reason);
        $this->assertSame(50.0, (float) $application->fresh()->final_score);
    }

    public function test_placeholder_and_partial_rows_are_not_completed_and_duplicate_seat_fails_closed(): void
    {
        [$application, $president, $member] = $this->readyToScore(withMember: true);

        $placeholder = EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $president->id,
            'documents_complete' => false,
            'final_score' => 0,
        ]);
        $this->assertFalse(app(CanonicalIndividualScoringService::class)->isFinalCompleted($placeholder));
        $this->assertTrue(CommissionCanonicalSeat::isPlaceholder($placeholder));

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect();

        $placeholder->refresh();
        $this->assertTrue(app(CanonicalIndividualScoringService::class)->isFinalCompleted($placeholder));
        $this->assertSame(1, (int) $placeholder->canonical_seat_no);
        $this->assertNotNull($placeholder->completed_at);

        $extra = $this->makeExtraMember($president->commission);
        try {
            DB::transaction(function () use ($application, $extra) {
                EvaluationScore::create([
                    'application_id' => $application->id,
                    'commission_member_id' => $extra->id,
                    'canonical_seat_no' => 1,
                    'criterion_1' => 5,
                    'criterion_2' => 5,
                    'criterion_3' => 5,
                    'criterion_4' => 5,
                    'criterion_5' => 5,
                    'criterion_6' => 5,
                    'criterion_7' => 5,
                    'criterion_8' => 5,
                    'criterion_9' => 5,
                    'criterion_10' => 5,
                    'completed_at' => now(),
                    'final_score' => 50,
                ]);
            });
            $this->fail('Duplicate completed application+seat must fail closed.');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertSame(1, EvaluationScore::query()
            ->where('application_id', $application->id)
            ->where('canonical_seat_no', 1)
            ->count());
    }

    public function test_partial_legacy_row_cannot_be_overwritten_or_bypassed(): void
    {
        [$application, $president, $member] = $this->readyToScore(withMember: true);

        $partial = EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'criterion_1' => 3,
            'criterion_2' => 3,
            'notes' => 'Djelimični istorijski red',
            'final_score' => 6,
        ]);
        $originalUpdatedAt = $partial->updated_at?->toDateTimeString();
        $this->assertTrue(CommissionCanonicalSeat::isPartial($partial));
        $this->assertFalse(app(CanonicalIndividualScoringService::class)->isFinalCompleted($partial));

        $this->actingAs($member->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'notes' => 'Pokušaj prepisivanja',
                'criterion_1' => 5,
            ]))
            ->assertForbidden();

        $partial->refresh();
        $this->assertSame(3, (int) $partial->criterion_1);
        $this->assertSame(3, (int) $partial->criterion_2);
        $this->assertNull($partial->criterion_3);
        $this->assertSame('Djelimični istorijski red', $partial->notes);
        $this->assertSame(6.0, (float) $partial->final_score);
        $this->assertNull($partial->canonical_seat_no);
        $this->assertNull($partial->completed_at);
        $this->assertSame($originalUpdatedAt, $partial->updated_at?->toDateTimeString());
        $this->assertSame(1, EvaluationScore::where('application_id', $application->id)->where('commission_member_id', $member->id)->count());

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect();

        $extra = $this->makeExtraMember($member->commission);
        $this->actingAs($extra->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->assertSame(0, EvaluationScore::where('application_id', $application->id)->where('commission_member_id', $extra->id)->count());
        $this->assertTrue(CommissionCanonicalSeat::isPartial($partial->fresh()));
        $this->assertNull($partial->fresh()->canonical_seat_no);
    }

    public function test_scoring_gate_still_blocks_and_five_of_five_does_not_reject_below_thirty(): void
    {
        [$application, $president, $member, $competition, $members] = $this->submittedApplicationWithCommission(returnAll: true);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->confirmPass($president, $application);

        foreach ($members as $index => $commissionMember) {
            $this->actingAs($commissionMember->user)
                ->post(route('evaluation.store', $application), $this->scorePayload([
                    'criterion_1' => 1,
                    'criterion_2' => 1,
                    'criterion_3' => 1,
                    'criterion_4' => 1,
                    'criterion_5' => 1,
                    'criterion_6' => 1,
                    'criterion_7' => 1,
                    'criterion_8' => 1,
                    'criterion_9' => 1,
                    'criterion_10' => 1,
                ]))
                ->assertRedirect();
        }

        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotSame('rejected', $application->status);
        $this->assertSame(10.0, (float) $application->final_score);
        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateApplication($application);
        $this->assertSame(10.0, $aggregate['final_score']);
    }

    public function test_results_remain_hidden_until_global_cycle_complete_for_all_roles(): void
    {
        [$application, $president, $member, $competition, $members] = $this->readyToScore(returnAll: true);
        $second = $this->createSubmittedApplication($competition);
        $this->confirmPass($president, $second);

        $seatTwo = $members->firstWhere('canonical_seat_no', 2);
        $this->assertNotNull($seatTwo);

        $this->actingAs($seatTwo->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'notes' => 'SEAT2-SECRET-NOTE',
                'criterion_1' => 2,
            ]))
            ->assertRedirect();

        foreach ($members as $commissionMember) {
            $this->actingAs($commissionMember->user)
                ->post(route('evaluation.store', $second), $this->scorePayload())
                ->assertRedirect();
        }

        $this->assertFalse($competition->fresh()->isIndividualScoringCycleComplete());
        $this->assertNull($application->fresh()->final_score);
        $this->assertNull($second->fresh()->final_score);

        $beforeIncompleteRanking = $this->competitionScoringFingerprint($competition);
        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertForbidden();
        $this->assertSame($beforeIncompleteRanking, $this->competitionScoringFingerprint($competition));

        $html = $this->actingAs($president->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('SEAT2-SECRET-NOTE', $html);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $application), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Rano',
            ])
            ->assertForbidden();

        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertForbidden();

        foreach (['konkurs_admin', 'admin', 'superadmin'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('admin.competitions.ranking', $competition))
                ->assertForbidden();
        }

        foreach ($members as $commissionMember) {
            if ((int) $commissionMember->id === (int) $seatTwo->id) {
                continue;
            }
            $this->actingAs($commissionMember->user)
                ->post(route('evaluation.store', $application), $this->scorePayload())
                ->assertRedirect();
        }

        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());
        $this->assertNotNull($application->fresh()->final_score);
        $this->assertNotNull($second->fresh()->final_score);

        $beforeRanking = $this->competitionScoringFingerprint($competition);
        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->assertSame($beforeRanking, $this->competitionScoringFingerprint($competition));

        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->assertSame($beforeRanking, $this->competitionScoringFingerprint($competition));

        $this->actingAs($this->userWithRole('superadmin'))
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->assertSame($beforeRanking, $this->competitionScoringFingerprint($competition));

        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateApplication($application->fresh());
        $this->assertSame(4.4, $aggregate['criterion_averages'][1]);
        $this->assertSame(5.0, $aggregate['criterion_averages'][2]);
        $this->assertSame(49.4, $aggregate['base_score']);
        $this->assertSame(49.4, $aggregate['final_score']);
    }

    public function test_dead_legacy_scoring_routes_do_not_produce_http_500(): void
    {
        [$application, $president] = $this->readyToScore();

        $scoreResponse = $this->actingAs($president->user)
            ->post('/evaluations/'.$application->id.'/score', $this->scorePayload());
        $this->assertNotSame(500, $scoreResponse->status());
        $this->assertContains($scoreResponse->status(), [403, 404]);

        $commentResponse = $this->actingAs($president->user)
            ->post('/evaluations/'.$application->id.'/comment', ['comment' => 'x']);
        $this->assertNotSame(500, $commentResponse->status());
        $this->assertContains($commentResponse->status(), [403, 404]);
    }

    public function test_historical_completed_rows_map_to_seats_and_placeholders_do_not(): void
    {
        [$application, $president, $member, $competition, $members] = $this->readyToScore(returnAll: true);

        foreach ($members as $commissionMember) {
            $score = EvaluationScore::create([
                'application_id' => $application->id,
                'commission_member_id' => $commissionMember->id,
                'criterion_1' => 5,
                'criterion_2' => 5,
                'criterion_3' => 5,
                'criterion_4' => 5,
                'criterion_5' => 5,
                'criterion_6' => 5,
                'criterion_7' => 5,
                'criterion_8' => 5,
                'criterion_9' => 5,
                'criterion_10' => 5,
                'notes' => 'Istorijska ocjena',
                'final_score' => 50,
            ]);
            $this->assertNull($score->completed_at);
            $updated = $score->updated_at?->toDateTimeString();
            CommissionCanonicalSeat::assignSeatToCompletedLegacyRow($score);
            $score->refresh();
            $this->assertNull($score->completed_at);
            $this->assertNull(CommissionCanonicalSeat::legacyCompletedAtForBackfill($score));
            $this->assertNotNull($score->canonical_seat_no);
            $this->assertSame(5, (int) $score->criterion_1);
            $this->assertSame(50.0, (float) $score->final_score);
            $this->assertSame('Istorijska ocjena', $score->notes);
            $this->assertSame($updated, $score->updated_at?->toDateTimeString());
        }

        $bySeat = app(CanonicalIndividualScoringService::class)->completedEvaluationsBySeat($application->fresh(['evaluationScores.commissionMember']));
        $this->assertSame([1, 2, 3, 4, 5], array_keys($bySeat));

        $emptyApp = $this->createSubmittedApplication($competition);
        foreach ($members as $commissionMember) {
            EvaluationScore::create([
                'application_id' => $emptyApp->id,
                'commission_member_id' => $commissionMember->id,
                'final_score' => 0,
            ]);
        }
        $this->assertSame([], app(CanonicalIndividualScoringService::class)->completedEvaluationsBySeat($emptyApp));
        $this->assertFalse(app(CanonicalIndividualScoringService::class)->applicationHasFiveCanonicalSeats($emptyApp));
        $this->assertTrue(
            EvaluationScore::query()
                ->where('application_id', $emptyApp->id)
                ->get()
                ->every(fn (EvaluationScore $score) => $score->canonical_seat_no === null
                    && $score->completed_at === null
                    && CommissionCanonicalSeat::isPlaceholder($score))
        );
    }

    public function test_partial_and_placeholder_rows_are_not_seat_mapped_or_stamped(): void
    {
        [$application, $president, $member] = $this->readyToScore(withMember: true);

        $partial = EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'criterion_1' => 2,
            'notes' => 'Ostaje djelimičan',
            'final_score' => 2,
        ]);
        $placeholder = EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $president->id,
            'final_score' => 0,
        ]);

        CommissionCanonicalSeat::assignSeatToCompletedLegacyRow($partial);
        CommissionCanonicalSeat::assignSeatToCompletedLegacyRow($placeholder);

        $partial->refresh();
        $placeholder->refresh();
        $this->assertTrue(CommissionCanonicalSeat::isPartial($partial));
        $this->assertTrue(CommissionCanonicalSeat::isPlaceholder($placeholder));
        $this->assertNull($partial->canonical_seat_no);
        $this->assertNull($placeholder->canonical_seat_no);
        $this->assertNull($partial->completed_at);
        $this->assertNull($placeholder->completed_at);
        $this->assertSame('Ostaje djelimičan', $partial->notes);
        $this->assertSame(2.0, (float) $partial->final_score);
    }

    public function test_unique_index_allows_multiple_null_placeholder_seats_and_rejects_duplicate_completed_seat(): void
    {
        [$application, $president, $member] = $this->readyToScore(withMember: true);
        $secondApp = $this->createSubmittedApplication($application->competition);
        $this->confirmPass($president, $secondApp);

        EvaluationScore::create([
            'application_id' => $secondApp->id,
            'commission_member_id' => $president->id,
            'final_score' => 0,
        ]);
        EvaluationScore::create([
            'application_id' => $secondApp->id,
            'commission_member_id' => $member->id,
            'final_score' => 0,
        ]);
        $this->assertSame(2, EvaluationScore::query()
            ->where('application_id', $secondApp->id)
            ->whereNull('canonical_seat_no')
            ->count());

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect();

        try {
            DB::transaction(function () use ($application, $member) {
                EvaluationScore::create([
                    'application_id' => $application->id,
                    'commission_member_id' => $member->id,
                    'canonical_seat_no' => 1,
                    'criterion_1' => 5,
                    'criterion_2' => 5,
                    'criterion_3' => 5,
                    'criterion_4' => 5,
                    'criterion_5' => 5,
                    'criterion_6' => 5,
                    'criterion_7' => 5,
                    'criterion_8' => 5,
                    'criterion_9' => 5,
                    'criterion_10' => 5,
                    'completed_at' => now(),
                    'final_score' => 50,
                ]);
            });
            $this->fail('Duplicate completed application+seat must fail closed.');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertSame(1, EvaluationScore::query()
            ->where('application_id', $application->id)
            ->where('canonical_seat_no', 1)
            ->count());
    }

    public function test_invalid_substitute_seat_cannot_record_final_score(): void
    {
        [$application, $president] = $this->readyToScore();

        $user = $this->userWithRole('komisija');
        $invalid = CommissionMember::create([
            'commission_id' => $president->commission_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'clan',
            'member_type' => 'opstina',
            'is_substitute' => true,
            'replaces_member_number' => 9,
            'status' => 'active',
        ]);

        $this->actingAs($invalid->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->assertSame(0, EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $invalid->id)
            ->count());
    }

    public function test_chairman_bonus_locks_before_results_and_cannot_be_changed_afterwards(): void
    {
        [$application, $president, $member, $competition, $members] = $this->readyToScore(returnAll: true);
        $second = $this->createSubmittedApplication($competition);
        $this->confirmPass($president, $second);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'bonus_info_day' => '1',
            ]))
            ->assertRedirect();

        $application->refresh();
        $this->assertTrue((bool) $application->bonus_info_day);
        $this->assertFalse($competition->fresh()->isIndividualScoringCycleComplete());
        $this->assertNull($application->final_score);

        $this->actingAs($member->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'bonus_new_business' => '1',
            ]))
            ->assertRedirect();
        $this->assertFalse((bool) $application->fresh()->bonus_new_business);
        $this->assertTrue((bool) $application->fresh()->bonus_info_day);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'bonus_info_day' => '1',
                'bonus_green_innovative' => '1',
            ]))
            ->assertRedirect();
        $this->assertTrue((bool) $application->fresh()->bonus_green_innovative);

        foreach (['konkurs_admin', 'admin', 'superadmin'] as $role) {
            $response = $this->actingAs($this->userWithRole($role))
                ->post(route('evaluation.store', $application), $this->scorePayload([
                    'bonus_zavod_nezaposleni' => '1',
                ]));
            $this->assertContains($response->status(), [401, 403, 302]);
        }
        $this->assertFalse((bool) $application->fresh()->bonus_zavod_nezaposleni);

        $remainingOnFirst = $members->filter(function (CommissionMember $commissionMember) use ($president, $member) {
            return (int) $commissionMember->id !== (int) $president->id
                && (int) $commissionMember->id !== (int) $member->id;
        });
        foreach ($remainingOnFirst as $commissionMember) {
            $this->actingAs($commissionMember->user)
                ->post(route('evaluation.store', $application), $this->scorePayload())
                ->assertRedirect();
        }

        $others = $members->filter(fn (CommissionMember $m) => (int) $m->id !== (int) $president->id);
        foreach ($others as $commissionMember) {
            $this->actingAs($commissionMember->user)
                ->post(route('evaluation.store', $second), $this->scorePayload())
                ->assertRedirect();
        }

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $second), $this->scorePayload([
                'bonus_zavod_nezaposleni' => '1',
            ]))
            ->assertRedirect();

        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());
        $this->assertTrue((bool) $second->fresh()->bonus_zavod_nezaposleni);
        $this->assertSame(52.0, (float) $second->fresh()->final_score);
        $this->assertSame(54.0, (float) $application->fresh()->final_score);
        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateApplication($application->fresh());
        $this->assertSame(4, $aggregate['bonus']);
        $this->assertSame(54.0, $aggregate['final_score']);

        $beforeLock = $this->competitionScoringFingerprint($competition);
        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->assertSame($beforeLock, $this->competitionScoringFingerprint($competition));

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'bonus_zavod_nezaposleni' => '1',
            ]))
            ->assertForbidden();
        $this->assertSame($beforeLock, $this->competitionScoringFingerprint($competition));
        $this->assertFalse((bool) $application->fresh()->bonus_zavod_nezaposleni);
        $this->assertSame(54.0, (float) $application->fresh()->final_score);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $application), [
                'commission_decision' => 'podrzava_potpuno',
                'commission_justification' => 'Obrazlozenje',
            ])
            ->assertRedirect();

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'bonus_new_business' => '1',
            ]))
            ->assertForbidden();

        $this->actingAs($member->user)
            ->post(route('evaluation.sign-decision', $application))
            ->assertRedirect();

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'bonus_new_business' => '1',
            ]))
            ->assertForbidden();

        $this->assertFalse((bool) $application->fresh()->bonus_new_business);
        $this->assertTrue((bool) $application->fresh()->bonus_info_day);
        $this->assertTrue((bool) $application->fresh()->bonus_green_innovative);
        $this->assertSame(54.0, (float) $application->fresh()->final_score);

        $afterLaterLifecycle = $this->competitionScoringFingerprint($competition);
        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->assertSame($afterLaterLifecycle, $this->competitionScoringFingerprint($competition));
    }

    public function test_preflight_fails_closed_before_mapping_completed_scores(): void
    {
        [$application, $president, $member] = $this->readyToScore(withMember: true);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect();

        $user = $this->userWithRole('komisija');
        CommissionMember::create([
            'commission_id' => $president->commission_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'clan',
            'member_type' => 'opstina',
            'is_substitute' => true,
            'replaces_member_number' => 9,
            'status' => 'active',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('substitute without valid replaces_member_number');
        ScoringSeatBackfill::assertPreflightAgainstExistingSchema();
    }

    public function test_preflight_allows_partials_and_rejects_duplicate_inferred_seats(): void
    {
        [$application, $president, $member, $competition, $members] = $this->readyToScore(returnAll: true);
        $seatTwo = $members->sortBy('id')->values()[1];

        EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $president->id,
            'criterion_1' => 2,
            'notes' => 'partial',
            'final_score' => 2,
        ]);
        ScoringSeatBackfill::assertPreflightAgainstExistingSchema();

        $this->actingAs($seatTwo->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect();

        $substitute = $this->makeSubstitute($seatTwo, 2);
        EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $substitute->id,
            'criterion_1' => 5,
            'criterion_2' => 5,
            'criterion_3' => 5,
            'criterion_4' => 5,
            'criterion_5' => 5,
            'criterion_6' => 5,
            'criterion_7' => 5,
            'criterion_8' => 5,
            'criterion_9' => 5,
            'criterion_10' => 5,
            'final_score' => 50,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('duplicate completed evaluations');
        ScoringSeatBackfill::assertPreflightAgainstExistingSchema();
    }

    public function test_obrazac_3_visual_lock_remains_exact(): void
    {
        $create = file_get_contents(resource_path('views/evaluation/create.blade.php'));
        $show = file_get_contents(resource_path('views/evaluation/show.blade.php'));

        [$createObrazac3] = $this->extractBetween(
            $create,
            "                <div class=\"print-document-header\">",
            '            @if(! $scoringIsAllowed)',
        );
        [$showObrazac3] = $this->extractBetween(
            $show,
            "                <div class=\"form-title\" style=\"text-transform: none; font-size: 16px; margin-bottom: 4px;\">",
            '            <!-- 4. Ocjena biznis plana u brojkama -->',
        );

        $this->assertSame('29cde03cd76ff93cb965e7796d9d5917a5bb8dc41bf9c31d119e86795a186084', hash('sha256', $createObrazac3));
        $this->assertSame('5478ab31568e39130cefe7b3829bb80a92c19468d796d0b928c9a5e1d66198c3', hash('sha256', $showObrazac3));
        $this->assertSame('219ce30bee90a0b00e87db8d6a0591fbc609c242e59a8baa60e7032f9a2973d3', hash('sha256', $this->firstStyleBlock($create)));
        $this->assertSame('ca68b4b5c41be2686de4ea1ead34bab7206263ae1f19af27b294d7b4af67d6d3', hash('sha256', $this->firstStyleBlock($show)));
    }

    public function test_shared_competition_ranks_for_equal_final_scores_pattern_a(): void
    {
        // scores 42, 40, 40, 38 → ranks 1, 2, 2, 4
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 4, 4, 4, 4, 4, 4, 4, 4], // 42
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
            [3, 3, 4, 4, 4, 4, 4, 4, 4, 4], // 38
        ]);

        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());

        $ranked = collect($apps)->map(fn (Application $app) => $app->fresh())->sortBy('id')->values();
        $this->assertSame(42.0, (float) $ranked[0]->final_score);
        $this->assertSame(40.0, (float) $ranked[1]->final_score);
        $this->assertSame(40.0, (float) $ranked[2]->final_score);
        $this->assertSame(38.0, (float) $ranked[3]->final_score);

        $this->assertSame(1, (int) $ranked[0]->ranking_position);
        $this->assertSame(2, (int) $ranked[1]->ranking_position);
        $this->assertSame(2, (int) $ranked[2]->ranking_position);
        $this->assertSame(4, (int) $ranked[3]->ranking_position);
        $this->assertNotSame(
            (int) $ranked[1]->id,
            (int) $ranked[2]->id
        );
        $this->assertSame(
            (int) $ranked[1]->ranking_position,
            (int) $ranked[2]->ranking_position
        );
    }

    public function test_shared_competition_ranks_for_equal_final_scores_pattern_b(): void
    {
        // scores 50, 45, 45, 45, 40 → ranks 1, 2, 2, 2, 5
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 5, 5, 5, 5, 5, 5, 5, 5], // 50
            [5, 5, 5, 5, 5, 4, 4, 4, 4, 4], // 45
            [5, 5, 5, 5, 5, 4, 4, 4, 4, 4], // 45
            [5, 5, 5, 5, 5, 4, 4, 4, 4, 4], // 45
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
        ]);

        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());

        $byScoreThenId = collect($apps)
            ->map(fn (Application $app) => $app->fresh())
            ->sort(function (Application $a, Application $b) {
                $scoreCmp = ((float) $b->final_score) <=> ((float) $a->final_score);
                if ($scoreCmp !== 0) {
                    return $scoreCmp;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        $this->assertSame([50.0, 45.0, 45.0, 45.0, 40.0], $byScoreThenId->map(fn ($a) => (float) $a->final_score)->all());
        $this->assertSame([1, 2, 2, 2, 5], $byScoreThenId->map(fn ($a) => (int) $a->ranking_position)->all());
    }

    public function test_equal_final_score_keeps_shared_rank_regardless_of_application_id_order(): void
    {
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
        ]);

        [$first, $second] = collect($apps)->map(fn (Application $app) => $app->fresh())->sortBy('id')->values();

        $this->assertNotSame((int) $first->id, (int) $second->id);
        $this->assertSame(40.0, (float) $first->final_score);
        $this->assertSame(40.0, (float) $second->final_score);
        $this->assertSame(1, (int) $first->ranking_position);
        $this->assertSame(1, (int) $second->ranking_position);
    }

    public function test_shared_ranks_do_not_regress_threshold_or_scoring_cycle(): void
    {
        // Above line with a tie, plus one below-threshold application.
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 5, 5, 5, 5, 5, 5, 5, 5], // 50 → rank 1
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40 → rank 2
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40 → rank 2
            [1, 1, 1, 1, 1, 1, 1, 1, 1, 1], // 10 → below 30, no rank
        ]);

        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());

        $fresh = collect($apps)->map(fn (Application $app) => $app->fresh())->sortBy('id')->values();

        $this->assertTrue($fresh[0]->meetsMinimumScore());
        $this->assertTrue($fresh[1]->meetsMinimumScore());
        $this->assertTrue($fresh[2]->meetsMinimumScore());
        $this->assertFalse($fresh[3]->meetsMinimumScore());
        $this->assertSame(10.0, (float) $fresh[3]->final_score);
        $this->assertNull($fresh[3]->ranking_position);

        $this->assertSame(1, (int) $fresh[0]->ranking_position);
        $this->assertSame(2, (int) $fresh[1]->ranking_position);
        $this->assertSame(2, (int) $fresh[2]->ranking_position);

        $before = $this->competitionScoringFingerprint($competition);
        $this->actingAs($president->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertOk();
        $this->assertSame($before, $this->competitionScoringFingerprint($competition));
    }

    public function test_close_competition_preserves_shared_competition_ranks(): void
    {
        // scores 42, 40, 40, 38 → shared ranks 1, 2, 2, 4 must survive closeCompetition()
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 4, 4, 4, 4, 4, 4, 4, 4], // 42
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
            [4, 4, 4, 4, 4, 4, 4, 4, 4, 4], // 40
            [3, 3, 4, 4, 4, 4, 4, 4, 4, 4], // 38
        ]);

        $beforeClose = collect($apps)->map(fn (Application $app) => $app->fresh())->sortBy('id')->values();
        $this->assertSame([42.0, 40.0, 40.0, 38.0], $beforeClose->map(fn ($a) => (float) $a->final_score)->all());
        $this->assertSame([1, 2, 2, 4], $beforeClose->map(fn ($a) => (int) $a->ranking_position)->all());

        foreach ($beforeClose as $application) {
            $this->actingAs($president->user)
                ->post(route('evaluation.store-decision', $application), [
                    'commission_decision' => 'odbija',
                    'commission_justification' => 'Zakljucak potreban za zatvaranje konkursa.',
                ])
                ->assertRedirect();
        }

        $this->assertTrue($competition->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $competition->fresh()->status);

        $afterClose = collect($apps)->map(fn (Application $app) => $app->fresh())->sortBy('id')->values();
        $this->assertSame([42.0, 40.0, 40.0, 38.0], $afterClose->map(fn ($a) => (float) $a->final_score)->all());
        $this->assertSame([1, 2, 2, 4], $afterClose->map(fn ($a) => (int) $a->ranking_position)->all());
        $this->assertNotSame([1, 2, 3, 4], $afterClose->map(fn ($a) => (int) $a->ranking_position)->all());
    }

    public function test_proposal_decision_requires_completed_chairman_decisions(): void
    {
        [$competition, $members, $president, $apps] = $this->completeCycleWithUniformCriteria([
            [5, 5, 5, 5, 5, 5, 5, 5, 5, 5], // 50 — above line, needs decision
            [1, 1, 1, 1, 1, 1, 1, 1, 1, 1], // 10 — below line, no decision required
        ]);

        $aboveLine = $apps[0]->fresh();
        $belowLine = $apps[1]->fresh();

        $this->assertTrue($competition->fresh()->isIndividualScoringCycleComplete());
        $this->assertFalse($competition->fresh()->hasChairmanCompletedDecisions());
        $this->assertSame(50.0, (float) $aboveLine->final_score);
        $this->assertSame(1, (int) $aboveLine->ranking_position);
        $this->assertSame(10.0, (float) $belowLine->final_score);
        $this->assertNull($belowLine->ranking_position);

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertForbidden();

        $this->assertNull($aboveLine->fresh()->commission_decision);
        $this->assertSame(50.0, (float) $aboveLine->fresh()->final_score);
        $this->assertSame(1, (int) $aboveLine->fresh()->ranking_position);

        $this->actingAs($president->user)
            ->post(route('evaluation.store-decision', $aboveLine), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Zakljucak za Predlog odluke.',
            ])
            ->assertRedirect();

        $this->assertTrue($competition->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk();

        $this->assertSame(50.0, (float) $aboveLine->fresh()->final_score);
        $this->assertSame(1, (int) $aboveLine->fresh()->ranking_position);
        $this->assertSame(10.0, (float) $belowLine->fresh()->final_score);
        $this->assertNull($belowLine->fresh()->ranking_position);
    }

    /**
     * @param  list<list<int>>  $criteriaPerApplication  each inner list is 10 criterion values (same for all five seats)
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
            $this->assertCount(10, $criteria);
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

    /**
     * @return array{0: Application, 1: CommissionMember, 2?: CommissionMember, 3?: Competition, 4?: \Illuminate\Support\Collection<int, CommissionMember>}
     */
    private function readyToScore(bool $withMember = false, bool $returnAll = false): array
    {
        $result = $this->submittedApplicationWithCommission($withMember, $returnAll);
        $this->confirmPass($result[1], $result[0]);

        return $result;
    }

    /**
     * @return array{0: Application, 1: CommissionMember, 2?: CommissionMember, 3?: Competition, 4?: \Illuminate\Support\Collection<int, CommissionMember>}
     */
    private function submittedApplicationWithCommission(bool $withMember = false, bool $returnAll = false): array
    {
        $commission = $this->createCommissionWithMembers(5, true);
        $competition = $this->createZenskoCompetition($commission);
        $application = $this->createSubmittedApplication($competition);
        $president = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail();
        $member = $commission->activeMembers()->where('position', 'clan')->firstOrFail();

        if ($returnAll) {
            return [$application, $president, $member, $competition, $commission->activeMembers];
        }

        if ($withMember) {
            return [$application, $president, $member];
        }

        return [$application, $president];
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

    private function scorePayload(array $overrides = []): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return array_merge($payload, $overrides);
    }

    private function makeSubstitute(CommissionMember $predecessor, int $slot): CommissionMember
    {
        $user = $this->userWithRole('komisija');

        return CommissionMember::create([
            'commission_id' => $predecessor->commission_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $predecessor->position,
            'member_type' => $predecessor->member_type,
            'is_substitute' => true,
            'replaces_member_number' => $slot,
            'canonical_seat_no' => $slot,
            'status' => 'active',
        ]);
    }

    private function makeExtraMember(Commission $commission): CommissionMember
    {
        $user = $this->userWithRole('komisija');

        return CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'clan',
            'member_type' => 'opstina',
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);
    }

    private function createSubmittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Biznis plan '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
    }

    private function competitionScoringFingerprint(Competition $competition): string
    {
        $applications = Application::query()
            ->where('competition_id', $competition->id)
            ->orderBy('id')
            ->get([
                'id',
                'final_score',
                'ranking_position',
                'evaluated_at',
                'updated_at',
                'status',
                'bonus_info_day',
                'bonus_new_business',
                'bonus_zavod_nezaposleni',
                'bonus_green_innovative',
            ]);

        $scores = EvaluationScore::query()
            ->whereIn('application_id', $applications->pluck('id'))
            ->orderBy('id')
            ->get([
                'id',
                'application_id',
                'commission_member_id',
                'canonical_seat_no',
                'completed_at',
                'criterion_1',
                'criterion_2',
                'criterion_3',
                'criterion_4',
                'criterion_5',
                'criterion_6',
                'criterion_7',
                'criterion_8',
                'criterion_9',
                'criterion_10',
                'notes',
                'final_score',
                'updated_at',
            ]);

        return $applications->toJson().'|'.$scores->toJson();
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

    private function createZenskoCompetition(Commission $commission): Competition
    {
        $competition = Competition::create([
            'title' => 'Konkurs scoring '.uniqid(),
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

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function extractBetween(string $text, string $start, string $end): array
    {
        $i = strpos($text, $start);
        $j = strpos($text, $end, $i);
        $this->assertNotFalse($i);
        $this->assertNotFalse($j);

        return [substr($text, $i, $j - $i), $i, $j];
    }

    private function firstStyleBlock(string $text): string
    {
        $i = strpos($text, '<style>');
        $j = strpos($text, '</style>');
        $this->assertNotFalse($i);
        $this->assertNotFalse($j);

        return substr($text, $i, $j + 8 - $i);
    }
}
