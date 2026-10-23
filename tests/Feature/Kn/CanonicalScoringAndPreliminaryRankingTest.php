<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService as S;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\CanonicalReviewState;
use App\Services\Competitions\ComplaintService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CompetitionProgramCatalog;
use App\Support\CompetitionRanking;
use App\Support\ScoringProfileConfig;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-007 — canonical MMSP P3 scoring (3 seats, 10 criteria, no bonuses) and the
 * automatic, frozen preliminary ranking (full precision, shared ranks, no outcome).
 */
class CanonicalScoringAndPreliminaryRankingTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

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

    // A — profile

    public function test_canonical_scoring_profile_is_active_with_three_seats_canonical_criteria_and_no_bonus(): void
    {
        $scoring = ScoringProfileConfig::for(self::CANONICAL);

        $this->assertTrue($scoring->scoringFormulaActive);
        $this->assertTrue(CompetitionProfileResolver::resolve(self::CANONICAL)->scoringRuntimeActive());
        $this->assertSame([1, 2, 3], $scoring->allowedSeats);
        $this->assertSame(3, $scoring->requiredFinalCount);
        $this->assertSame([], $scoring->bonusFlagKeys);
        $this->assertFalse($scoring->showsZavodBonus);
        $this->assertFalse($scoring->allowsIncompleteDraft);
        $this->assertTrue($scoring->hidesOtherScoresUntilLaterStep);
        $this->assertSame(range(1, 10), array_keys($scoring->criteria));
        $this->assertStringStartsWith('Kvalitet plana ulaganja', $scoring->criteria[1]);
        $this->assertStringStartsWith('Lokalna autentičnost', $scoring->criteria[5]);
        $this->assertStringStartsWith('Doprinos digitalizaciji', $scoring->criteria[7]);
        $this->assertStringStartsWith('Prezentacija plana na intervjuu', $scoring->criteria[10]);
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, CompetitionProgramCatalog::definitions()[self::CANONICAL]['status']);
    }

    public function test_women_youth_profiles_are_unchanged_and_ostalo_stays_fail_closed(): void
    {
        $canonical = ScoringProfileConfig::for(self::CANONICAL);

        $women = ScoringProfileConfig::for(CompetitionProfileResolver::TYPE_WOMEN);
        $this->assertSame([1, 2, 3, 4, 5], $women->allowedSeats);
        $this->assertSame(5, $women->requiredFinalCount);
        $this->assertSame(S::BONUS_FLAG_KEYS, $women->bonusFlagKeys);
        $this->assertTrue($women->showsZavodBonus);
        $this->assertTrue($women->scoringFormulaActive);
        $this->assertNotSame($canonical->criteria, $women->criteria);

        $youth = ScoringProfileConfig::for(CompetitionProfileResolver::TYPE_YOUTH);
        $this->assertSame([1, 2, 3], $youth->allowedSeats);
        $this->assertSame(3, $youth->requiredFinalCount);
        $this->assertSame(S::YOUTH_BONUS_FLAG_KEYS, $youth->bonusFlagKeys);
        $this->assertTrue($youth->allowsIncompleteDraft);
        $this->assertNotSame($canonical->criteria, $youth->criteria);

        foreach ([CompetitionProfileResolver::TYPE_OTHER, 'nepoznat'] as $type) {
            $policy = CompetitionProfileResolver::resolve($type);
            $this->assertTrue($policy->isFailClosed(), $type);
            $this->assertFalse($policy->scoringRuntimeActive(), $type);
        }

        [$commission, $members] = $this->canonicalCommission();
        $ostalo = $this->competition($commission, CompetitionProfileResolver::TYPE_OTHER);
        $application = $this->finalApplication($ostalo);
        $this->assertSame(G::NOT_CANONICAL_MESSAGE, $this->scoring()->canonicalCycleBlockReason($ostalo));
        $this->assertFalse($this->scoring()->persistCanonicalPreliminaryRankingIfReady($ostalo));
        $this->assertSame(G::NOT_CANONICAL_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($members[G::ROLE_PREDSJEDNIK]->user, $application));
        $this->assertForbiddenWith(G::NOT_CANONICAL_MESSAGE, fn () => $this->score($members[G::ROLE_PREDSJEDNIK], $application, 40));
        $this->assertSame(0, EvaluationScore::query()->count());
    }

    // B — write gates

    public function test_each_of_the_three_seats_records_exactly_one_final_score(): void
    {
        [, $members, , [$application]] = $this->readyCompetition();

        foreach ($members as $member) {
            $this->assertNull($this->scoring()->canonicalScoreWriteBlockReason($member->user, $application->fresh()));
        }

        $this->score($members[G::ROLE_PREDSJEDNIK], $application, 50);
        $this->score($members[G::ROLE_SEKRETAR], $application, 41);
        $this->score($members[G::ROLE_SPOLJASNJI], $application, 10);

        $rows = EvaluationScore::query()->orderBy('canonical_seat_no')->get();
        $this->assertSame([1, 2, 3], $rows->pluck('canonical_seat_no')->map(fn ($seat) => (int) $seat)->all());
        $this->assertSame(
            [$members[G::ROLE_PREDSJEDNIK]->id, $members[G::ROLE_SEKRETAR]->id, $members[G::ROLE_SPOLJASNJI]->id],
            $rows->pluck('commission_member_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertEquals([50, 41, 10], $rows->pluck('final_score')->map(fn ($v) => (int) $v)->all());
        $this->assertTrue($rows->every(fn (EvaluationScore $row) => $row->completed_at !== null));
    }

    public function test_http_store_records_a_confirmed_final_score_and_validates_input(): void
    {
        [, $members, , [$application]] = $this->readyCompetition();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), $this->criteriaSumming(40))
            ->assertSessionHasErrors('scoring_confirmed');
        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(40), ['criterion_3' => 6, 'scoring_confirmed' => '1']))
            ->assertSessionHasErrors('criterion_3');
        $this->assertSame(0, EvaluationScore::query()->count());

        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(40), ['scoring_confirmed' => '1', 'notes' => 'Obrazloženje.']))
            ->assertRedirect(route('evaluation.create', $application))
            ->assertSessionHas('success');

        $row = EvaluationScore::query()->sole();
        $this->assertSame(1, (int) $row->canonical_seat_no);
        $this->assertSame(40, (int) $row->final_score);
        $this->assertSame('Obrazloženje.', $row->notes);
    }

    public function test_non_member_and_member_of_another_commission_are_blocked(): void
    {
        [, $members, , [$application]] = $this->readyCompetition();
        [, $otherMembers] = $this->canonicalCommission();
        $outsider = $this->komisijaUser('van');
        $foreign = $otherMembers[G::ROLE_PREDSJEDNIK]->user;

        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($outsider, $application));
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($foreign, $application));
        $this->assertForbiddenWith(G::NOT_MEMBER_MESSAGE, fn () => $this->scoring()->recordCanonicalFinalScore($application, $foreign, $this->criteriaSumming(50), null, true));

        $this->actingAs($foreign)
            ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(50), ['scoring_confirmed' => '1']))
            ->assertForbidden();
        $this->actingAs($outsider)
            ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(50), ['scoring_confirmed' => '1']))
            ->assertForbidden();
        $this->assertSame(0, EvaluationScore::query()->count());
    }

    public function test_undeclared_replaced_or_expired_governance_is_blocked(): void
    {
        [, $members, , [$application]] = $this->readyCompetition();

        $members[G::ROLE_SEKRETAR]->update(['declarations_signed_at' => null]);
        $this->assertSame(G::DECLARATIONS_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($members[G::ROLE_SEKRETAR]->user, $application));
        $this->signDeclarations($members[G::ROLE_SEKRETAR]);

        $service = app(CanonicalCommissionMembershipService::class);
        $old = $members[G::ROLE_SPOLJASNJI];
        $this->assertTrue($service->vacate($old, 'resigned', now(), $this->canonicalActor())->succeeded);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($old->user, $application));
        $this->assertSame(G::COMMISSION_INCOMPLETE_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($members[G::ROLE_PREDSJEDNIK]->user, $application));
        $replacement = CommissionMember::query()->findOrFail(
            $service->replace($old, $this->komisijaUser('zamjena'), 'R-Z', now(), $this->canonicalActor())->recordId
        );
        $this->signDeclarations($replacement);
        $this->assertNull($this->scoring()->canonicalScoreWriteBlockReason($replacement->user, $application));

        Carbon::setTestNow(Carbon::parse('2027-01-02 10:00:00', 'Europe/Belgrade'));
        $this->assertSame(G::MANDATE_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($members[G::ROLE_PREDSJEDNIK]->user, $application));
        $this->assertForbiddenWith(G::MANDATE_MESSAGE, fn () => $this->score($members[G::ROLE_PREDSJEDNIK], $application, 40));
        $this->assertSame(0, EvaluationScore::query()->count());
    }

    public function test_incomplete_or_unreviewed_application_is_blocked(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $pending = $this->finalApplication($competition);
        $incomplete = $this->finalApplication($competition);
        $this->confirmOrdinarySession($competition);
        $this->assertTrue(app(CompletenessAssessmentService::class)
            ->record($incomplete, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::INCOMPLETE, 'Nedostaje prilog.')->succeeded);
        $this->confirmedSession($competition, $members);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->assertSame(G::SCORING_STAGE_CLOSED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair, $pending));
        $this->assertSame(G::SCORING_STAGE_CLOSED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair, $incomplete));

        Carbon::setTestNow(Carbon::parse('2026-09-14 10:00:01', 'Europe/Belgrade'));
        $this->assertSame(CanonicalReviewState::INCOMPLETE, CanonicalReviewState::resolve($incomplete->fresh()));
        $this->assertSame(G::SCORING_STAGE_CLOSED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair, $incomplete->fresh()));
        $this->assertForbiddenWith(G::SCORING_STAGE_CLOSED_MESSAGE, fn () => $this->score($members[G::ROLE_PREDSJEDNIK], $incomplete, 40));
        $this->actingAs($chair)
            ->post(route('evaluation.store', $incomplete), array_merge($this->criteriaSumming(40), ['scoring_confirmed' => '1']))
            ->assertForbidden();
        $this->assertSame(0, EvaluationScore::query()->count());
    }

    public function test_unresolved_complaint_blocks_scoring_until_decided(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $application = $this->finalApplication($competition);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $this->confirmOrdinarySession($competition);
        $this->assertTrue(app(CompletenessAssessmentService::class)
            ->record($application, $chair, ApplicationCompletenessAssessment::INCOMPLETE, 'Nedostaje prilog.')->succeeded);
        $this->confirmedSession($competition, $members);

        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($application->user)
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->sole();

        $this->assertSame(CanonicalReviewState::COMPLAINT_PENDING, CanonicalReviewState::resolve($application->fresh()));
        $this->assertSame(G::SCORING_STAGE_CLOSED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair, $application->fresh()));
        $this->assertSame(S::CANONICAL_REVIEW_UNRESOLVED_MESSAGE, $this->scoring()->canonicalCycleBlockReason($competition->fresh()));

        $this->assertTrue(app(ComplaintService::class)->decide($complaint, $chair, ApplicationComplaint::ACCEPTED, 'Prilog je bio dostavljen.')->succeeded);
        $this->assertSame(CanonicalReviewState::COMPLAINT_RESOLVED, CanonicalReviewState::resolve($application->fresh()));
        $this->assertNull($this->scoring()->canonicalScoreWriteBlockReason($chair, $application->fresh()));

        $this->scoreAll($members, $application, [40, 40, 40]);
        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));
        $this->assertSame(1, (int) $application->fresh()->ranking_position);
    }

    public function test_missing_interview_session_blocks_scoring(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $application = $this->finalApplication($competition);
        $this->complete($application, $members);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->assertSame(G::INTERVIEW_REQUIRED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair, $application));
        $this->assertForbiddenWith(G::INTERVIEW_REQUIRED_MESSAGE, fn () => $this->score($members[G::ROLE_PREDSJEDNIK], $application, 40));
        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(40), ['scoring_confirmed' => '1']))
            ->assertForbidden();

        $unconfirmed = app(CanonicalCommissionSessionService::class)->record($competition, $chair, G::PURPOSE_INTERVIEW, now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ]);
        $this->assertTrue($unconfirmed->succeeded, (string) $unconfirmed->error);
        $this->assertSame(G::INTERVIEW_REQUIRED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair, $application));
        $this->assertSame(0, EvaluationScore::query()->count());
    }

    public function test_scoring_is_rejected_before_the_commission_phase_and_opens_after_the_full_canonical_flow(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = Competition::create([
            'title' => 'Poziv C3 '.uniqid(),
            'type' => self::CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-21',
            'published_at' => '2026-09-01 09:00:00',
        ]);
        $application = $this->finalApplication($competition);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $completeness = app(CompletenessAssessmentService::class);
        $assertScoringBlocked = function (string $message) use ($members, $application, $chair): void {
            foreach ($members as $member) {
                $this->assertSame($message, $this->scoring()->canonicalScoreWriteBlockReason($member->user, $application->fresh()));
            }
            $this->assertForbiddenWith($message, fn () => $this->score($members[G::ROLE_PREDSJEDNIK], $application, 40));
            $this->actingAs($chair)
                ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(40), ['scoring_confirmed' => '1']))
                ->assertForbidden();
            $this->assertSame(0, EvaluationScore::query()->count());
        };

        // Application deadline still open (2026-09-21 end of day).
        $this->assertFalse($competition->fresh()->isApplicationDeadlinePassed());
        $assertScoringBlocked(G::APPLICATION_DEADLINE_OPEN_MESSAGE);
        $this->assertSame(G::APPLICATION_DEADLINE_OPEN_MESSAGE, $completeness->record($application, $chair, ApplicationCompletenessAssessment::COMPLETE)->error);

        // Deadline passed, but no ordinary session yet: the phase gate precedes the interview gate.
        Carbon::setTestNow(Carbon::parse('2026-09-22 10:00:00', 'Europe/Belgrade'));
        $this->assertTrue($competition->fresh()->isApplicationDeadlinePassed());
        $assertScoringBlocked(G::ORDINARY_SESSION_REQUIRED_MESSAGE);
        $this->assertSame(G::ORDINARY_SESSION_REQUIRED_MESSAGE, $completeness->record($application, $chair, ApplicationCompletenessAssessment::COMPLETE)->error);

        // Recorded but unconfirmed ordinary session is not enough.
        $ordinary = app(CanonicalCommissionSessionService::class)->record($competition, $chair, G::PURPOSE_ORDINARY, now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ]);
        $this->assertTrue($ordinary->succeeded, (string) $ordinary->error);
        $assertScoringBlocked(G::ORDINARY_SESSION_REQUIRED_MESSAGE);
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());

        // Valid order: confirmed ordinary session → COMPLETE → confirmed interview → scoring.
        $this->confirmOrdinarySession($competition);
        $assertScoringBlocked(G::INTERVIEW_REQUIRED_MESSAGE);
        $this->complete($application, $members);
        $this->confirmedSession($competition, $members);

        foreach ($members as $member) {
            $this->assertNull($this->scoring()->canonicalScoreWriteBlockReason($member->user, $application->fresh()));
        }
        $this->scoreAll($members, $application, [40, 41, 42]);
        $this->assertSame(3, EvaluationScore::query()->where('application_id', $application->id)->whereNotNull('completed_at')->count());
        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));
        $this->assertSame(1, (int) $application->fresh()->ranking_position);
    }

    public function test_duplicate_is_blocked_and_completed_score_is_immutable(): void
    {
        [, $members, , [$application, $second]] = $this->readyCompetition(2);
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $this->score($chair, $application, 44, 'Prva ocjena.');
        $before = EvaluationScore::query()->sole()->getAttributes();

        $this->assertSame(G::SEAT_SCORE_COMPLETED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair->user, $application->fresh()));
        $this->assertForbiddenWith(G::SEAT_SCORE_COMPLETED_MESSAGE, fn () => $this->score($chair, $application, 10, 'Prepis.'));
        $this->actingAs($chair->user)
            ->post(route('evaluation.store', $application), array_merge($this->criteriaSumming(10), ['scoring_confirmed' => '1', 'notes' => 'Prepis.']))
            ->assertForbidden();

        $this->assertSame(1, EvaluationScore::query()->count());
        $this->assertSame($before, EvaluationScore::query()->sole()->getAttributes());
        $this->assertNull($this->scoring()->canonicalScoreWriteBlockReason($chair->user, $second));
    }

    public function test_replaced_member_score_survives_and_substitute_cannot_overwrite_the_seat(): void
    {
        [, $members, $competition, [$application]] = $this->readyCompetition();
        $old = $members[G::ROLE_SEKRETAR];
        $this->score($old, $application, 45, 'Ocjena prije zamjene.');

        $service = app(CanonicalCommissionMembershipService::class);
        $service->vacate($old, 'resigned', now(), $this->canonicalActor());
        $replacement = CommissionMember::query()->findOrFail(
            $service->replace($old, $this->komisijaUser('zamjena'), 'R-Z', now(), $this->canonicalActor())->recordId
        );
        $this->signDeclarations($replacement);

        $this->assertSame(G::SEAT_SCORE_COMPLETED_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($replacement->user, $application->fresh()));
        $this->assertNotNull($this->scoring()->canonicalCompletedSeats($application->fresh()));

        $this->score($members[G::ROLE_PREDSJEDNIK], $application, 45);
        $this->score($members[G::ROLE_SPOLJASNJI], $application, 45);

        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));
        $seatTwo = EvaluationScore::query()->where('canonical_seat_no', 2)->sole();
        $this->assertSame($old->id, (int) $seatTwo->commission_member_id);
        $this->assertSame('Ocjena prije zamjene.', $seatTwo->notes);
    }

    public function test_partial_or_historical_bad_rows_fail_closed(): void
    {
        [, $members, $competition, [$partial, $badValue, $wrongSeat]] = $this->readyCompetition(3);
        $chair = $members[G::ROLE_PREDSJEDNIK];

        EvaluationScore::query()->create(array_merge($this->criteriaSumming(40), [
            'application_id' => $partial->id,
            'commission_member_id' => $members[G::ROLE_SEKRETAR]->id,
            'canonical_seat_no' => 2,
            'completed_at' => null,
        ]));
        EvaluationScore::query()->create(array_merge($this->criteriaSumming(40), [
            'application_id' => $badValue->id,
            'commission_member_id' => $members[G::ROLE_SEKRETAR]->id,
            'canonical_seat_no' => 2,
            'criterion_4' => 9,
            'completed_at' => now(),
        ]));
        EvaluationScore::query()->create(array_merge($this->criteriaSumming(40), [
            'application_id' => $wrongSeat->id,
            'commission_member_id' => $members[G::ROLE_SPOLJASNJI]->id,
            'canonical_seat_no' => 2,
            'completed_at' => now(),
        ]));
        $before = EvaluationScore::query()->orderBy('id')->get()->map->getAttributes()->all();

        foreach ([$partial, $badValue, $wrongSeat] as $application) {
            $this->assertNull($this->scoring()->canonicalCompletedSeats($application->fresh()));
            $this->assertNull($this->scoring()->aggregateCanonicalApplication($application->fresh()));
            $this->assertSame(S::CANONICAL_CORRUPT_ROW_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair->user, $application->fresh()));
            $this->assertForbiddenWith(S::CANONICAL_CORRUPT_ROW_MESSAGE, fn () => $this->score($chair, $application, 40));
        }

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->post(route('evaluation.store', $partial), array_merge($this->criteriaSumming(50), ['scoring_confirmed' => '1']))
            ->assertForbidden();

        $this->assertSame(S::CANONICAL_CORRUPT_ROW_MESSAGE, $this->scoring()->canonicalCycleBlockReason($competition->fresh()));
        $this->assertFalse($this->scoring()->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->assertNull($competition->fresh()->canonical_ranking_formed_at);
        $this->assertSame($before, EvaluationScore::query()->orderBy('id')->get()->map->getAttributes()->all());
    }

    // C — Option D visibility

    public function test_option_d_members_see_only_own_score_before_formation_and_everything_after(): void
    {
        [, $members, $competition, [$application]] = $this->readyCompetition();
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $secretary = $members[G::ROLE_SEKRETAR];
        $admin = $this->canonicalActor();
        $outsider = $this->komisijaUser('van');
        $gov = app(G::class);

        $this->score($chair, $application, 50, 'Napomena predsjednika.');

        $this->actingAs($chair->user)->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertViewHas('visibleScores', fn ($scores) => $scores->keys()->all() === [$chair->id])
            ->assertViewHas('aggregate', null)
            ->assertViewHas('rankingFormed', false)
            ->assertSee('Moja ocjena')
            ->assertDontSee('Preliminarna rang-lista');

        $this->actingAs($secretary->user)->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertViewHas('visibleScores', fn ($scores) => $scores->isEmpty())
            ->assertViewHas('ownScore', null)
            ->assertViewHas('aggregate', null)
            ->assertViewHas('members', fn ($visibleMembers) => $visibleMembers->pluck('id')->all() === [$secretary->id])
            ->assertDontSee('Napomena predsjednika.')
            ->assertDontSee('Preliminarna rang-lista');

        $this->actingAs($admin)->get(route('evaluation.create', $application))->assertForbidden();
        $this->assertCount(0, $gov->visibleScores($admin, $application));
        $this->assertCount(0, $gov->visibleScores($outsider, $application));
        $this->assertCount(0, $gov->visibleScores($secretary->user, $application));
        $this->assertFalse($gov->aggregatesVisibleTo($chair->user, $competition));
        $this->assertFalse($this->scoring()->canonicalPreliminaryRankingView($competition, $chair->user)['visible']);
        $this->assertFalse($this->scoring()->canonicalPreliminaryRankingView($competition, $admin)['visible']);

        $this->score($secretary, $application, 40);
        $this->assertCount(1, $gov->visibleScores($secretary->user, $application));
        $this->assertFalse($this->scoring()->canonicalRankingIsFormed($competition));
        $this->score($members[G::ROLE_SPOLJASNJI], $application, 45);
        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));

        $this->actingAs($secretary->user)->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertViewHas('visibleScores', fn ($scores) => $scores->count() === 3)
            ->assertViewHas('aggregate', fn ($aggregate) => $aggregate['final_score_display'] === '45.00')
            ->assertSee('Preliminarna rang-lista')
            ->assertSee('Prosječna ocjena');
        $this->assertCount(3, $gov->visibleScores($admin, $application));
        $this->assertCount(0, $gov->visibleScores($outsider, $application));
        $this->assertTrue($this->scoring()->canonicalPreliminaryRankingView($competition, $admin)['visible']);
        $this->assertFalse($this->scoring()->canonicalPreliminaryRankingView($competition, $outsider)['visible']);
    }

    public function test_outcome_and_legacy_ranking_routes_stay_closed_for_canonical(): void
    {
        [, $members, $competition, [$application]] = $this->readyCompetition();
        $this->scoreAll($members, $application, [40, 40, 40]);
        $admin = $this->canonicalActor();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->actingAs($admin)->get(route('admin.competitions.ranking', $competition))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.competitions.select-winners', $competition), ['application_ids' => [$application->id]])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.competitions.decision', $competition))->assertForbidden();
        $this->actingAs($chair)->post(route('evaluation.store-decision', $application), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($chair)->post(route('evaluation.sign-decision', $application))->assertForbidden();
        $this->actingAs($chair)->get(route('evaluation.show', $application))->assertRedirect(route('evaluation.create', $application));
        $this->actingAs($chair)->get(route('evaluation.index'))->assertOk();

        $fresh = $application->fresh();
        $this->assertSame('submitted', $fresh->status);
        $this->assertNull($fresh->commission_decision);
        $this->assertFalse($competition->fresh()->hasChairmanCompletedDecisions());
    }

    // D — cycle completion

    public function test_cycle_completes_only_when_all_three_seats_scored_every_eligible_application(): void
    {
        [, $members, $competition, [$first, $second]] = $this->readyCompetition(2);

        $this->score($members[G::ROLE_PREDSJEDNIK], $first, 40);
        $this->assertFalse($this->scoring()->isCanonicalIndividualScoringCycleComplete($competition->fresh()));
        $this->score($members[G::ROLE_SEKRETAR], $first, 40);
        $this->assertFalse($this->scoring()->isCanonicalIndividualScoringCycleComplete($competition->fresh()));
        $this->score($members[G::ROLE_SPOLJASNJI], $first, 40);
        $this->assertFalse($this->scoring()->isCanonicalIndividualScoringCycleComplete($competition->fresh()));
        $this->assertSame(S::CANONICAL_CYCLE_INCOMPLETE_MESSAGE, $this->scoring()->canonicalCycleBlockReason($competition->fresh()));
        $this->assertFalse($this->scoring()->canonicalRankingIsFormed($competition));
        $this->assertNull($first->fresh()->ranking_position);
        $this->assertNull($first->fresh()->final_score_calc);

        $this->score($members[G::ROLE_PREDSJEDNIK], $second, 30);
        $this->score($members[G::ROLE_SEKRETAR], $second, 30);
        $this->assertFalse($this->scoring()->isCanonicalIndividualScoringCycleComplete($competition->fresh()));
        $this->score($members[G::ROLE_SPOLJASNJI], $second, 30);

        $this->assertTrue($this->scoring()->isCanonicalIndividualScoringCycleComplete($competition->fresh()));
        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));
        $this->assertSame(1, (int) $first->fresh()->ranking_position);
        $this->assertSame(2, (int) $second->fresh()->ranking_position);
    }

    public function test_pending_review_and_open_complaint_window_block_formation_and_eliminated_plan_is_not_ranked(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $scored = $this->finalApplication($competition);
        $other = $this->finalApplication($competition);
        $this->complete($scored, $members);
        $this->confirmedSession($competition, $members);
        $this->scoreAll($members, $scored, [42, 42, 42]);

        $this->assertSame(S::CANONICAL_REVIEW_UNRESOLVED_MESSAGE, $this->scoring()->canonicalCycleBlockReason($competition->fresh()));
        $this->assertFalse($this->scoring()->canonicalRankingIsFormed($competition));

        $this->assertTrue(app(CompletenessAssessmentService::class)
            ->record($other, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::INCOMPLETE, 'Nedostaje prilog.')->succeeded);
        $this->assertSame(CanonicalReviewState::COMPLAINT_ELIGIBLE, CanonicalReviewState::resolve($other->fresh()));
        $this->assertFalse($this->scoring()->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->assertSame(S::CANONICAL_REVIEW_UNRESOLVED_MESSAGE, $this->scoring()->canonicalCycleBlockReason($competition->fresh()));

        Carbon::setTestNow(Carbon::parse('2026-09-13 10:00:01', 'Europe/Belgrade'));
        $this->assertSame(CanonicalReviewState::INCOMPLETE, CanonicalReviewState::resolve($other->fresh()));
        $this->assertTrue($this->scoring()->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));

        $this->assertSame(1, (int) $scored->fresh()->ranking_position);
        $this->assertNull($other->fresh()->ranking_position);
        $this->assertNull($other->fresh()->final_score);
        $this->assertNull($other->fresh()->final_score_calc);
        $this->assertSame('submitted', $other->fresh()->status);
    }

    // E — aggregate

    public function test_aggregate_uses_exact_criterion_averages_full_precision_and_separate_display(): void
    {
        [, $members, , [$application]] = $this->readyCompetition();
        $this->scoreWith($members[G::ROLE_PREDSJEDNIK], $application, $this->uniformCriteria(5));
        $this->scoreWith($members[G::ROLE_SEKRETAR], $application, $this->uniformCriteria(4));
        $this->scoreWith($members[G::ROLE_SPOLJASNJI], $application, array_merge($this->uniformCriteria(4), ['criterion_1' => 5]));

        $aggregate = $this->scoring()->aggregateCanonicalApplication($application->fresh());

        $this->assertSame(14, $aggregate['criterion_sums'][1]);
        $this->assertSame(13, $aggregate['criterion_sums'][2]);
        $this->assertSame('4.6666666666', $aggregate['criterion_averages'][1]);
        foreach (range(2, 10) as $criterion) {
            $this->assertSame('4.3333333333', $aggregate['criterion_averages'][$criterion]);
        }
        $this->assertSame(131, $aggregate['total_points']);
        $this->assertSame('43.6666666666', $aggregate['final_score_full']);
        $this->assertSame('43.67', $aggregate['final_score_display']);

        $sumOfAverages = array_reduce($aggregate['criterion_averages'], fn (string $carry, string $avg) => bcadd($carry, $avg, 10), '0');
        $this->assertSame(0, bccomp($sumOfAverages, $aggregate['final_score_full'], 8));

        $fresh = $application->fresh();
        $this->assertSame('43.6666666666', (string) $fresh->final_score_calc);
        $this->assertSame('43.67', (string) $fresh->final_score);
        $this->assertNotSame((string) $fresh->final_score, (string) $fresh->final_score_calc);
    }

    // F — threshold

    public function test_below_thirty_and_exactly_thirty_are_both_ranked_without_auto_reject_or_outcome(): void
    {
        [, $members, $competition, [$below, $exact, $top]] = $this->readyCompetition(3);
        $this->scoreAll($members, $below, [30, 30, 29]);
        $this->scoreAll($members, $exact, [30, 30, 30]);
        $this->scoreAll($members, $top, [50, 50, 50]);

        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));

        $belowAggregate = $this->scoring()->aggregateCanonicalApplication($below->fresh());
        $exactAggregate = $this->scoring()->aggregateCanonicalApplication($exact->fresh());
        $this->assertSame('29.6666666666', $belowAggregate['final_score_full']);
        $this->assertSame('29.67', $belowAggregate['final_score_display']);
        $this->assertFalse($belowAggregate['meets_support_threshold']);
        $this->assertSame('30.0000000000', $exactAggregate['final_score_full']);
        $this->assertTrue($exactAggregate['meets_support_threshold']);

        $this->assertSame(1, (int) $top->fresh()->ranking_position);
        $this->assertSame(2, (int) $exact->fresh()->ranking_position);
        $this->assertSame(3, (int) $below->fresh()->ranking_position);

        foreach ([$below, $exact, $top] as $application) {
            $fresh = $application->fresh();
            $this->assertSame('submitted', $fresh->status);
            $this->assertNull($fresh->commission_decision);
            $this->assertNull($fresh->rejection_reason);
        }

        $rows = $this->scoring()->canonicalPreliminaryRankingView($competition, $members[G::ROLE_SEKRETAR]->user)['rows'];
        $this->assertSame([$top->id, $exact->id, $below->id], $rows->map(fn ($row) => $row['application']->id)->all());
        $this->assertSame([true, true, false], $rows->pluck('meets_support_threshold')->all());
    }

    // G — ranking

    public function test_ranking_orders_by_full_precision_with_shared_ranks_and_is_idempotent(): void
    {
        [, $members, $competition, [$fourth, $tieB, $tieA, $first]] = $this->readyCompetition(4);
        $this->scoreAll($members, $fourth, [40, 40, 40]);
        $this->scoreAll($members, $tieB, [45, 45, 45]);
        $this->scoreAll($members, $tieA, [50, 45, 40]);
        $this->scoreAll($members, $first, [50, 50, 50]);

        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));
        $this->assertSame(1, (int) $first->fresh()->ranking_position);
        $this->assertSame(2, (int) $tieB->fresh()->ranking_position);
        $this->assertSame(2, (int) $tieA->fresh()->ranking_position);
        $this->assertSame(4, (int) $fourth->fresh()->ranking_position);
        $this->assertSame((string) $tieA->fresh()->final_score_calc, (string) $tieB->fresh()->final_score_calc);

        $rows = $this->scoring()->canonicalPreliminaryRankingView($competition, $members[G::ROLE_PREDSJEDNIK]->user)['rows'];
        $this->assertSame([1, 2, 2, 4], $rows->pluck('rank')->all());
        $this->assertSame([$first->id, $tieB->id, $tieA->id, $fourth->id], $rows->map(fn ($row) => $row['application']->id)->all());
        $this->assertSame([false, true, true, false], $rows->pluck('shared')->all());

        $formedAt = (string) Competition::query()->whereKey($competition->id)->value('canonical_ranking_formed_at');
        $applicationsBefore = Application::query()->orderBy('id')->get()->map->getAttributes()->all();
        $scoresBefore = EvaluationScore::query()->orderBy('id')->get()->map->getAttributes()->all();

        Carbon::setTestNow(Carbon::parse('2026-09-10 12:00:00', 'Europe/Belgrade'));
        $this->assertTrue($this->scoring()->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->assertTrue($this->scoring()->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->actingAs($members[G::ROLE_SEKRETAR]->user)->get(route('evaluation.create', $first))->assertOk();

        $this->assertSame($formedAt, (string) Competition::query()->whereKey($competition->id)->value('canonical_ranking_formed_at'));
        $this->assertSame($applicationsBefore, Application::query()->orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($scoresBefore, EvaluationScore::query()->orderBy('id')->get()->map->getAttributes()->all());
        $this->assertTrue(Application::query()->get()->every(fn (Application $application) => $application->status === 'submitted'));
    }

    public function test_ranking_helper_shares_true_ties_and_separates_display_equal_values(): void
    {
        $ranked = CompetitionRanking::rank([13 => '40', 12 => '45.0000000000', 11 => '50', 10 => '45']);
        $this->assertSame([11, 10, 12, 13], array_column($ranked, 'id'));
        $this->assertSame([1, 2, 2, 4], array_column($ranked, 'rank'));
        $this->assertSame($ranked, CompetitionRanking::rank([10 => '45', 11 => '50', 12 => '45.0000000000', 13 => '40']));

        $this->assertSame('31.33', CompetitionRanking::roundHalfUp('31.3340000000'));
        $this->assertSame('31.33', CompetitionRanking::roundHalfUp('31.3330000000'));
        $this->assertSame([1 => 1, 2 => 2], CompetitionRanking::positions([2 => '31.3330000000', 1 => '31.3340000000']));
        $this->assertSame([1 => 1, 2 => 2], CompetitionRanking::positions([1 => '45.0000000001', 2 => '45']));

        $this->assertSame('29.67', CompetitionRanking::roundHalfUp('29.6666666666'));
        $this->assertSame('30.01', CompetitionRanking::roundHalfUp('30.005'));
        $this->assertSame('30.00', CompetitionRanking::roundHalfUp('30.0049999999'));
    }

    public function test_formed_ranking_is_frozen_against_new_scores_and_rewrites(): void
    {
        [, $members, $competition, [$application]] = $this->readyCompetition();
        $this->scoreAll($members, $application, [44, 44, 44]);
        $this->assertTrue($this->scoring()->canonicalRankingIsFormed($competition));
        $formedAt = (string) Competition::query()->whereKey($competition->id)->value('canonical_ranking_formed_at');
        $rankedBefore = $application->fresh()->getAttributes();

        $late = $this->finalApplication($competition->fresh());
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $this->assertSame(
            CompletenessAssessmentService::STAGE_LOCKED_MESSAGE,
            app(CompletenessAssessmentService::class)->record($late, $chair->user, ApplicationCompletenessAssessment::COMPLETE)->error
        );

        // Even a COMPLETE row that already exists for a late application must not reopen scoring.
        $basis = app(\App\Services\Competitions\CanonicalReviewBasis::class);
        $snapshot = $basis->snapshot($late->fresh());
        ApplicationCompletenessAssessment::query()->create([
            'application_id' => $late->id,
            'competition_id' => $competition->id,
            'outcome' => ApplicationCompletenessAssessment::COMPLETE,
            'catalog_key' => 'canonical_mmsp_document_catalog',
            'recorded_by_user_id' => $chair->user_id,
            'evidence_snapshot' => $snapshot,
            'evidence_fingerprint' => $basis->fingerprint($snapshot),
            'recorded_at' => now(),
        ]);

        $this->assertSame(S::CANONICAL_RANKING_FROZEN_MESSAGE, $this->scoring()->canonicalScoreWriteBlockReason($chair->user, $late->fresh()));
        $this->assertForbiddenWith(S::CANONICAL_RANKING_FROZEN_MESSAGE, fn () => $this->score($chair, $late, 50));
        $this->actingAs($chair->user)
            ->post(route('evaluation.store', $late), array_merge($this->criteriaSumming(50), ['scoring_confirmed' => '1']))
            ->assertForbidden();

        $this->assertTrue($this->scoring()->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->assertSame($formedAt, (string) Competition::query()->whereKey($competition->id)->value('canonical_ranking_formed_at'));
        $this->assertSame($rankedBefore, $application->fresh()->getAttributes());
        $this->assertNull($late->fresh()->ranking_position);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $late->id)->count());
    }

    private function scoring(): S
    {
        return app(S::class);
    }

    private function assertForbiddenWith(string $message, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a 403 response.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame($message, $exception->getMessage());
        }
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

    /**
     * @return array<string, int>
     */
    private function uniformCriteria(int $value): array
    {
        return $this->criteriaSumming($value * 10);
    }

    private function score(CommissionMember $member, Application $application, int $total, ?string $notes = null): EvaluationScore
    {
        return $this->scoreWith($member, $application, $this->criteriaSumming($total), $notes);
    }

    /**
     * @param  array<string, int>  $criteria
     */
    private function scoreWith(CommissionMember $member, Application $application, array $criteria, ?string $notes = null): EvaluationScore
    {
        return $this->scoring()->recordCanonicalFinalScore(
            Application::query()->findOrFail($application->id),
            $member->user,
            $criteria,
            $notes,
            true,
        );
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  array{0: int, 1: int, 2: int}  $totals  seat 1, 2, 3
     */
    private function scoreAll(array $members, Application $application, array $totals): void
    {
        $this->score($members[G::ROLE_PREDSJEDNIK], $application, $totals[0]);
        $this->score($members[G::ROLE_SEKRETAR], $application, $totals[1]);
        $this->score($members[G::ROLE_SPOLJASNJI], $application, $totals[2]);
    }

    /**
     * @return array{0: Commission, 1: array<string, CommissionMember>, 2: Competition, 3: list<Application>}
     */
    private function readyCompetition(int $applications = 1): array
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $created = [];
        for ($i = 0; $i < $applications; $i++) {
            $application = $this->finalApplication($competition);
            $this->complete($application, $members);
            $created[] = $application;
        }
        $this->confirmedSession($competition, $members);

        return [$commission, $members, $competition->fresh(), array_map(fn (Application $a) => $a->fresh(['competition', 'finalClaim']), $created)];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function complete(Application $application, array $members): void
    {
        $this->confirmOrdinarySession($application->competition);
        $result = app(CompletenessAssessmentService::class)
            ->record($application, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::COMPLETE);
        $this->assertTrue($result->succeeded, (string) $result->error);
    }

    private function competition(?Commission $commission, string $type = self::CANONICAL): Competition
    {
        return Competition::create([
            'title' => 'Poziv '.$type.' '.uniqid(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission?->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function finalApplication(Competition $competition, array $overrides = []): Application
    {
        $owner = User::factory()->create([
            'email' => 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $application = Application::create(array_merge([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Podnosilac',
            'submitted_at' => now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ], $overrides));
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
        ]);

        return $application->fresh(['competition', 'finalClaim', 'user']);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmedSession(Competition $competition, array $members): CommissionSession
    {
        $service = app(CanonicalCommissionSessionService::class);
        $result = $service->record($competition, $members[G::ROLE_PREDSJEDNIK]->user, G::PURPOSE_INTERVIEW, now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ]);
        $this->assertTrue($result->succeeded, (string) $result->error);
        $session = CommissionSession::query()->findOrFail($result->recordId);
        $confirm = $service->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($confirm->succeeded, (string) $confirm->error);

        return $session->fresh();
    }
}
