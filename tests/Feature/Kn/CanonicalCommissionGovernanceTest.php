<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\Commission;
use App\Models\CommissionGovernanceConflict;
use App\Models\CommissionMember;
use App\Models\CommissionMemberReplacement;
use App\Models\CommissionSession;
use App\Models\CommissionSessionAttendance;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CommissionProfileConfig;
use App\Support\CompetitionProgramCatalog;
use App\Support\ScoringProfileConfig;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-006 — canonical MMSP Commission governance (profile, membership, declarations,
 * quorum, replacement, mandate, sessions, Option D visibility, signature and complaint gates).
 */
class CanonicalCommissionGovernanceTest extends TestCase
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

    // 1
    public function test_canonical_profile_now_provides_a_commission(): void
    {
        $config = CommissionProfileConfig::for(self::CANONICAL);

        $this->assertTrue($config->providesCommission);
        $this->assertSame(3, $config->seatCount);
        $this->assertSame([1, 2, 3], $config->allowedSeats);
        $this->assertSame(2, $config->ordinaryQuorum);
        $this->assertSame(3, $config->fullDecisionQuorum);
        $this->assertTrue($config->requiresSemanticSeats);
        $this->assertTrue($config->enforcesDeclarations);
        $this->assertTrue($config->allowsRemoteAttendance);
        $this->assertTrue($this->competition(null)->profileProvidesCommission());
    }

    // 2
    public function test_missing_incomplete_or_non_canonical_commission_fails_completeness(): void
    {
        $this->assertFalse($this->competition(null)->hasCompleteValidCommission());
        $this->assertSame(G::NO_COMMISSION_MESSAGE, $this->gov()->structuralCompletenessBlockReason(null));

        $service = app(CanonicalCommissionMembershipService::class);
        $partial = $service->establish('Djelimična', CarbonImmutable::parse('2026-01-01'), 'R-1');
        $service->appoint($partial, $this->komisijaUser('p'), 1, G::ROLE_PREDSJEDNIK, null, 'R', now(), $this->canonicalActor());
        $service->appoint($partial, $this->komisijaUser('s'), 2, G::ROLE_SEKRETAR, null, 'R', now(), $this->canonicalActor());
        $this->assertFalse($this->competition($partial)->hasCompleteValidCommission());
        $this->assertSame(G::SIZE_MESSAGE, $this->gov()->structuralCompletenessBlockReason($partial->fresh()));

        $legacy = Commission::create([
            'name' => 'Legacy', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2027-01-01', 'status' => 'active',
        ]);
        foreach ([1, 2, 3] as $seat) {
            CommissionMember::create([
                'commission_id' => $legacy->id, 'user_id' => $this->komisijaUser('l'.$seat)->id, 'name' => 'L'.$seat,
                'position' => $seat === 1 ? 'predsjednik' : 'clan', 'member_type' => 'opstina', 'status' => 'active',
                'canonical_seat_no' => $seat,
            ]);
        }
        $this->assertFalse($this->competition($legacy)->hasCompleteValidCommission());

        [$valid] = $this->canonicalCommission();
        $this->assertTrue($this->competition($valid)->hasCompleteValidCommission());
    }

    // 3
    public function test_canonical_does_not_use_women_member_catalogue_or_criteria(): void
    {
        $this->assertFalse(CommissionProfileConfig::for(self::CANONICAL)->usesMemberTypeCatalog);
        $this->assertTrue(CommissionProfileConfig::for('zensko')->usesMemberTypeCatalog);

        $scoring = ScoringProfileConfig::for(self::CANONICAL);
        $this->assertCount(10, $scoring->criteria);
        $this->assertNotSame(ScoringProfileConfig::for('zensko')->criteria, $scoring->criteria);
        $this->assertNotSame(ScoringProfileConfig::for('omladinsko')->criteria, $scoring->criteria);

        [, $members] = $this->canonicalCommission();
        foreach ($members as $member) {
            $this->assertNull($member->member_type);
        }
    }

    // 4
    public function test_canonical_has_no_youth_or_women_bonus_and_three_seat_scoring(): void
    {
        $scoring = ScoringProfileConfig::for(self::CANONICAL);

        $this->assertSame([], $scoring->bonusFlagKeys);
        $this->assertNotSame([], ScoringProfileConfig::for('omladinsko')->bonusFlagKeys);
        $this->assertTrue($scoring->scoringFormulaActive);
        $this->assertSame([1, 2, 3], $scoring->allowedSeats);
        $this->assertSame(3, $scoring->requiredFinalCount);
        $this->assertFalse($scoring->showsZavodBonus);
        $this->assertTrue($scoring->hidesOtherScoresUntilLaterStep);
        $this->assertTrue(ScoringProfileConfig::for('omladinsko')->scoringFormulaActive);
        $this->assertTrue(ScoringProfileConfig::for('zensko')->scoringFormulaActive);
        $this->assertTrue(CompetitionProfileResolver::resolve(self::CANONICAL)->scoringRuntimeActive());
    }

    // 5
    public function test_valid_commission_has_exactly_three_semantic_seats(): void
    {
        [$commission, $members] = $this->canonicalCommission();

        $this->assertNull($this->gov()->structuralCompletenessBlockReason($commission));
        $this->assertSame(3, $this->gov()->activeMembers($commission)->count());
        $this->assertSame(1, $members[G::ROLE_PREDSJEDNIK]->canonical_seat_no);
        $this->assertSame(2, $members[G::ROLE_SEKRETAR]->canonical_seat_no);
        $this->assertSame(3, $members[G::ROLE_SPOLJASNJI]->canonical_seat_no);
        $this->assertSame('predsjednik', $members[G::ROLE_PREDSJEDNIK]->position);
        $this->assertSame('biznis', $members[G::ROLE_SPOLJASNJI]->external_category);
        $this->assertNotNull($members[G::ROLE_SEKRETAR]->appointment_reference);
        $this->assertNotNull($members[G::ROLE_SEKRETAR]->appointed_at);
        $this->assertSame(self::CANONICAL, $commission->assignedProfileType());
    }

    // 6
    public function test_fourth_seat_is_blocked(): void
    {
        [$commission] = $this->canonicalCommission();

        $result = app(CanonicalCommissionMembershipService::class)->appoint(
            $commission, $this->komisijaUser('cetvrti'), 3, G::ROLE_SPOLJASNJI, 'udruzenje', 'R', now(), $this->canonicalActor(),
        );

        $this->assertFalse($result->succeeded);
        $this->assertSame(G::FOURTH_SEAT_MESSAGE, $result->error);
        $this->assertSame(3, CommissionMember::query()->where('commission_id', $commission->id)->count());
    }

    // 7
    public function test_structural_seat_is_unique_in_service_and_database(): void
    {
        [$commission, $user] = $this->commissionWithChairOnly();

        $result = app(CanonicalCommissionMembershipService::class)->appoint(
            $commission, $this->komisijaUser('dup'), 1, G::ROLE_SEKRETAR, null, 'R', now(), $this->canonicalActor(),
        );
        $this->assertSame(G::SEAT_DUPLICATE_MESSAGE, $result->error);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->rawActiveMember($commission, $this->komisijaUser('raw'), 1, G::ROLE_SEKRETAR);
    }

    // 8
    public function test_predsjednik_role_is_unique(): void
    {
        [$commission] = $this->commissionWithChairOnly();

        $result = app(CanonicalCommissionMembershipService::class)->appoint(
            $commission, $this->komisijaUser('p2'), 2, G::ROLE_PREDSJEDNIK, null, 'R', now(), $this->canonicalActor(),
        );
        $this->assertSame(G::SEAT_DUPLICATE_MESSAGE, $result->error);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->rawActiveMember($commission, $this->komisijaUser('raw-p'), 2, G::ROLE_PREDSJEDNIK);
    }

    // 9
    public function test_sekretar_role_is_unique(): void
    {
        [$commission] = $this->commissionWithChairOnly();
        $service = app(CanonicalCommissionMembershipService::class);
        $this->assertTrue($service->appoint($commission, $this->komisijaUser('s1'), 2, G::ROLE_SEKRETAR, null, 'R', now(), $this->canonicalActor())->succeeded);

        $result = $service->appoint($commission, $this->komisijaUser('s2'), 3, G::ROLE_SEKRETAR, null, 'R', now(), $this->canonicalActor());
        $this->assertSame(G::SEAT_DUPLICATE_MESSAGE, $result->error);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->rawActiveMember($commission, $this->komisijaUser('raw-s'), 3, G::ROLE_SEKRETAR);
    }

    // 10
    public function test_spoljasnji_role_is_unique(): void
    {
        [$commission] = $this->commissionWithChairOnly();
        $service = app(CanonicalCommissionMembershipService::class);
        $this->assertTrue($service->appoint($commission, $this->komisijaUser('e1'), 3, G::ROLE_SPOLJASNJI, 'udruzenje', 'R', now(), $this->canonicalActor())->succeeded);

        $result = $service->appoint($commission, $this->komisijaUser('e2'), 2, G::ROLE_SPOLJASNJI, 'biznis', 'R', now(), $this->canonicalActor());
        $this->assertSame(G::SEAT_DUPLICATE_MESSAGE, $result->error);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->rawActiveMember($commission, $this->komisijaUser('raw-e'), 2, G::ROLE_SPOLJASNJI, 'akademska_zajednica');
    }

    // 11
    public function test_external_category_is_required_and_restricted(): void
    {
        [$commission] = $this->commissionWithChairOnly();
        $service = app(CanonicalCommissionMembershipService::class);
        $actor = $this->canonicalActor();

        $this->assertSame(G::EXTERNAL_CATEGORY_MESSAGE, $service->appoint($commission, $this->komisijaUser('x1'), 3, G::ROLE_SPOLJASNJI, null, 'R', now(), $actor)->error);
        $this->assertSame(G::EXTERNAL_CATEGORY_MESSAGE, $service->appoint($commission, $this->komisijaUser('x2'), 3, G::ROLE_SPOLJASNJI, 'zene_mreza', 'R', now(), $actor)->error);
        $this->assertSame(G::MUNICIPAL_CATEGORY_MESSAGE, $service->appoint($commission, $this->komisijaUser('x3'), 2, G::ROLE_SEKRETAR, 'biznis', 'R', now(), $actor)->error);

        foreach (G::EXTERNAL_CATEGORIES as $category) {
            $this->assertNull($this->gov()->externalCategoryBlockReason(G::ROLE_SPOLJASNJI, $category));
        }
        $this->assertSame(1, CommissionMember::query()->where('commission_id', $commission->id)->count());
    }

    // 12
    public function test_cross_profile_assignment_is_blocked(): void
    {
        [$canonicalCommission] = $this->canonicalCommission();
        $women = $this->competition(null, 'zensko');
        $youth = $this->competition(null, 'omladinsko');
        $canonical = $this->competition(null);
        $legacy = Commission::create([
            'name' => 'Legacy', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2027-01-01', 'status' => 'active',
        ]);

        $this->assertSame(CommissionProfileConfig::CANONICAL_PROFILE_ASSIGNMENT_MESSAGE,
            CommissionProfileConfig::conflictWithAssignedCompetitions($canonicalCommission, 'zensko'));
        $this->assertSame(CommissionProfileConfig::CANONICAL_PROFILE_ASSIGNMENT_MESSAGE,
            CommissionProfileConfig::conflictWithAssignedCompetitions($canonicalCommission, 'omladinsko'));
        $this->assertSame(CommissionProfileConfig::CANONICAL_COMMISSION_REQUIRED_MESSAGE,
            CommissionProfileConfig::conflictWithAssignedCompetitions($legacy, self::CANONICAL));
        $this->assertNull(CommissionProfileConfig::conflictWithAssignedCompetitions($canonicalCommission, self::CANONICAL));
        $this->assertNotNull(CommissionProfileConfig::homogeneousCompetitionTypes([$women->id, $canonical->id]));
        $this->assertNotNull(CommissionProfileConfig::homogeneousCompetitionTypes([$youth->id, $canonical->id]));
        $this->assertSame(CommissionProfileConfig::MIXED_PROFILE_ASSIGNMENT_MESSAGE,
            CommissionProfileConfig::homogeneousCompetitionTypes([$women->id, $youth->id]));

        $admin = $this->canonicalActor();
        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $canonicalCommission), [
                'name' => 'X', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2027-01-01',
                'status' => 'active', 'competition_ids' => [$women->id],
            ])
            ->assertSessionHasErrors(['competition_ids' => CommissionProfileConfig::COMMISSION_COMPETITION_PROFILE_MISMATCH_MESSAGE]);
        $this->assertNull($women->fresh()->commission_id);
        $this->assertSame(self::CANONICAL, $canonicalCommission->fresh()->profile_type);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), [
                'name' => 'Legacy form', 'year' => 2026, 'start_date' => '2026-01-01', 'status' => 'active',
                'members' => [['email' => 'legacy-form@example.test', 'name' => 'L']],
                'competition_ids' => [$canonical->id],
            ])
            ->assertSessionHasErrors('competition_type');
        $this->assertNull($canonical->fresh()->commission_id);

        $this->actingAs($admin)
            ->post(route('admin.commissions.members.add', $canonicalCommission), ['name' => 'N', 'position' => 'clan'])
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);
        $this->assertSame(3, CommissionMember::query()->where('commission_id', $canonicalCommission->id)->count());
    }

    // 13
    public function test_same_user_cannot_hold_two_active_seats_service_and_database(): void
    {
        [$commission, $chair] = $this->commissionWithChairOnly();

        $result = app(CanonicalCommissionMembershipService::class)->appoint(
            $commission, $chair, 2, G::ROLE_SEKRETAR, null, 'R', now(), $this->canonicalActor(),
        );
        $this->assertSame(G::SAME_USER_MESSAGE, $result->error);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->rawActiveMember($commission, $chair, 2, G::ROLE_SEKRETAR);
    }

    // 14
    public function test_represented_company_pib_conflict_blocks_and_persists_evidence(): void
    {
        [$commission] = $this->commissionWithChairOnly();
        $competition = $this->competition($commission);
        $application = $this->finalApplication($competition, ['pib' => '02345678']);
        $service = app(CanonicalCommissionMembershipService::class);

        $result = $service->appoint(
            $commission, $this->komisijaUser('pib'), 3, G::ROLE_SPOLJASNJI, 'biznis', 'R', now(), $this->canonicalActor(), '02-345.678',
        );

        $this->assertFalse($result->succeeded);
        $this->assertSame(G::PIB_CONFLICT_MESSAGE, $result->error);
        $conflict = CommissionGovernanceConflict::query()->sole();
        $this->assertSame('02345678', $conflict->normalized_value);
        $this->assertSame($application->id, (int) $conflict->application_id);
        $this->assertSame(CommissionGovernanceConflict::TYPE_REPRESENTED_COMPANY_PIB, $conflict->conflict_type);
        $this->assertSame(1, CommissionMember::query()->where('commission_id', $commission->id)->count());
        $this->assertSame(CanonicalCommissionMembershipService::INVALID_PIB_MESSAGE, $service->appoint(
            $commission, $this->komisijaUser('bad'), 3, G::ROLE_SPOLJASNJI, 'biznis', 'R', now(), $this->canonicalActor(), '123',
        )->error);

        [$active, $members] = $this->canonicalCommission();
        $members[G::ROLE_SPOLJASNJI]->update(['represented_company_pib' => '11122233']);
        $activeCompetition = $this->competition($active);
        $this->assertTrue($activeCompetition->hasCompleteValidCommission());
        $this->finalApplication($activeCompetition, ['pib' => '11122233']);

        $this->assertSame(G::PIB_CONFLICT_MESSAGE, $this->gov()->structuralCompletenessBlockReason($active));
        $this->assertFalse($activeCompetition->fresh()->hasCompleteValidCommission());
        $this->assertSame(G::PIB_CONFLICT_MESSAGE, $this->gov()->memberActionBlockReason($members[G::ROLE_PREDSJEDNIK]->fresh(), $activeCompetition));
        $this->assertSame(1, $service->recordPibConflicts($active, $this->canonicalActor()));
        $this->assertSame(0, $service->recordPibConflicts($active, $this->canonicalActor()));
    }

    // 15
    public function test_missing_confidentiality_declaration_blocks_actions(): void
    {
        [$commission, $members] = $this->canonicalCommission(signDeclarations: false);
        $member = $members[G::ROLE_SEKRETAR];
        $member->update([
            'conflict_of_interest_declaration' => 'Izjava o sukobu interesa.',
            'declarations_signed_at' => now(),
        ]);

        $this->assertNull($this->gov()->structuralCompletenessBlockReason($commission));
        $this->assertSame(G::DECLARATIONS_MESSAGE, $this->gov()->memberActionBlockReason($member->fresh()));
    }

    // 16
    public function test_missing_conflict_of_interest_declaration_blocks_actions(): void
    {
        [, $members] = $this->canonicalCommission(signDeclarations: false);
        $member = $members[G::ROLE_SPOLJASNJI];
        $member->update([
            'confidentiality_declaration' => 'Izjava o povjerljivosti.',
            'declarations_signed_at' => now(),
        ]);

        $this->assertSame(G::DECLARATIONS_MESSAGE, $this->gov()->memberActionBlockReason($member->fresh()));
        $this->signDeclarations($member);
        $this->assertNull($this->gov()->memberActionBlockReason($member->fresh()));
    }

    // 17
    public function test_ordinary_session_two_of_three_is_quorate_with_remote_member(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);

        $session = $this->recordSession($competition, $members, G::PURPOSE_ORDINARY, [
            G::ROLE_PREDSJEDNIK => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SEKRETAR => G::ATTENDANCE_REMOTE,
            G::ROLE_SPOLJASNJI => null,
        ]);

        $result = app(CanonicalCommissionSessionService::class)->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($result->succeeded, (string) $result->error);
        $this->assertSame(CommissionSession::TYPE_FIRST, $session->fresh()->session_type);
    }

    // 18
    public function test_ordinary_session_one_of_three_is_rejected(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $session = $this->recordSession($this->competition($commission), $members, G::PURPOSE_ORDINARY, [
            G::ROLE_PREDSJEDNIK => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SEKRETAR => null,
            G::ROLE_SPOLJASNJI => null,
        ]);

        $result = app(CanonicalCommissionSessionService::class)->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertSame(G::QUORUM_MESSAGE, $result->error);
        $this->assertNull($session->fresh()->completed_at);
    }

    // 19
    public function test_interview_requires_three_of_three(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $partialCompetition = $this->competition($commission);
        $partial = $this->recordSession($partialCompetition, $members, G::PURPOSE_INTERVIEW, [
            G::ROLE_PREDSJEDNIK => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SEKRETAR => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SPOLJASNJI => null,
        ]);
        $sessions = app(CanonicalCommissionSessionService::class);

        $this->assertSame(G::FULL_ATTENDANCE_MESSAGE, $sessions->confirm($partial, $members[G::ROLE_PREDSJEDNIK]->user)->error);
        $this->assertSame(G::INTERVIEW_REQUIRED_MESSAGE, $this->gov()->interviewBlockReason($partialCompetition));

        $fullCompetition = $this->competition($commission);
        $this->confirmedSession($fullCompetition, $members, G::PURPOSE_INTERVIEW);
        $this->assertNull($this->gov()->interviewBlockReason($fullCompetition));
    }

    // 20
    public function test_remote_and_electronic_attendance_count_and_mode_is_audited(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $session = $this->recordSession($competition, $members, G::PURPOSE_INTERVIEW, [
            G::ROLE_PREDSJEDNIK => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SEKRETAR => G::ATTENDANCE_REMOTE,
            G::ROLE_SPOLJASNJI => G::ATTENDANCE_ELECTRONIC,
        ]);

        $this->assertTrue(app(CanonicalCommissionSessionService::class)->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user)->succeeded);
        $this->assertSame(
            [G::ATTENDANCE_IN_PERSON, G::ATTENDANCE_REMOTE, G::ATTENDANCE_ELECTRONIC],
            CommissionSessionAttendance::query()->where('commission_session_id', $session->id)->orderBy('commission_member_id')->pluck('attendance_mode')->all()
        );

        $invalid = app(CanonicalCommissionSessionService::class)->record(
            $this->competition($commission), $members[G::ROLE_PREDSJEDNIK]->user, G::PURPOSE_ORDINARY, now(),
            [$members[G::ROLE_PREDSJEDNIK]->id => 'telefon'],
        );
        $this->assertSame(CanonicalCommissionSessionService::ATTENDANCE_MODE_INVALID_MESSAGE, $invalid->error);
    }

    // 21, 22, 23
    public function test_substitute_replacement_preserves_seat_and_role(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $service = app(CanonicalCommissionMembershipService::class);
        $old = $members[G::ROLE_SEKRETAR];

        $this->assertTrue($service->vacate($old, 'resigned', now(), $this->canonicalActor())->succeeded);
        $this->assertSame(G::SIZE_MESSAGE, $this->gov()->structuralCompletenessBlockReason($commission));

        $newUser = $this->komisijaUser('zamjena');
        $result = $service->replace($old, $newUser, 'Rješenje o zamjeni', now(), $this->canonicalActor());
        $this->assertTrue($result->succeeded, (string) $result->error);

        $new = CommissionMember::query()->findOrFail($result->recordId);
        $this->assertSame(2, $new->canonical_seat_no);
        $this->assertSame(G::ROLE_SEKRETAR, $new->canonical_seat);
        $this->assertTrue((bool) $new->is_substitute);
        $this->assertSame((int) $newUser->id, (int) $new->user_id);
        $this->assertNull($this->gov()->structuralCompletenessBlockReason($commission));

        $old->refresh();
        $this->assertSame('resigned', $old->status);
        $this->assertSame($new->id, (int) $old->replaced_by_member_id);
        $this->assertNotNull($old->replaced_at);
        $this->assertSame(2, $old->canonical_seat_no);
        $this->assertSame(G::ROLE_SEKRETAR, $old->canonical_seat);

        $history = CommissionMemberReplacement::query()->sole();
        $this->assertSame($old->id, (int) $history->vacated_member_id);
        $this->assertSame($new->id, (int) $history->replacement_member_id);
        $this->assertSame(2, (int) $history->seat_no);
    }

    // 24, 25
    public function test_replaced_member_is_blocked_and_completed_score_survives(): void
    {
        [$commission, $members, $competition, $application] = $this->scoringReadyApplication();
        $old = $members[G::ROLE_SEKRETAR];
        $score = EvaluationScore::query()->create([
            'application_id' => $application->id,
            'commission_member_id' => $old->id,
            'canonical_seat_no' => 2,
            'completed_at' => now(),
            'notes' => 'Završena ocjena.',
        ]);

        $service = app(CanonicalCommissionMembershipService::class);
        $service->vacate($old, 'dismissed', now(), $this->canonicalActor());
        $replacement = CommissionMember::query()->findOrFail(
            $service->replace($old, $this->komisijaUser('nova'), 'R-Z', now(), $this->canonicalActor())->recordId
        );
        $this->signDeclarations($replacement);

        $this->assertSame(G::MEMBER_INACTIVE_MESSAGE, $this->gov()->memberActionBlockReason($old->fresh(), $competition));
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->gov()->scoreWriteBlockReason($old->user, $application));

        $this->assertSame(G::SEAT_SCORE_COMPLETED_MESSAGE, $this->gov()->scoreWriteBlockReason($replacement->user, $application));
        $score->refresh();
        $this->assertSame($old->id, (int) $score->commission_member_id);
        $this->assertSame('Završena ocjena.', $score->notes);
        $this->assertNotNull($score->completed_at);

        $this->assertNull($this->gov()->scoreWriteBlockReason($members[G::ROLE_PREDSJEDNIK]->user, $application));
    }

    // 26, 27
    public function test_replacement_deadline_is_exact_vacancy_time_plus_fifteen_days_inclusive(): void
    {
        $cases = [
            'on-time-before' => ['2026-09-16 09:59:59', false, '2026-09-16 09:59:59'],
            'on-time-exact' => ['2026-09-16 10:00:00', false, '2026-09-16 10:00:00'],
            'late-next-instant' => ['2026-09-16 10:00:00.000001', true, '2026-09-16 10:00:00'],
            'late-next-second' => ['2026-09-16 10:00:01', true, '2026-09-16 10:00:01'],
        ];
        $service = app(CanonicalCommissionMembershipService::class);

        foreach ($cases as $key => [$replacedAt, $expectLate, $storedReplacedAt]) {
            Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
            [, $members] = $this->canonicalCommission();
            $old = $members[G::ROLE_SPOLJASNJI];
            $service->vacate($old, 'resigned', CarbonImmutable::parse('2026-09-01 10:00:00', 'Europe/Belgrade'), $this->canonicalActor());

            $vacancy = CommissionMemberReplacement::query()->where('vacated_member_id', $old->id)->sole();
            $this->assertSame('2026-09-16 10:00:00', $vacancy->due_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);

            Carbon::setTestNow(Carbon::parse($replacedAt, 'Europe/Belgrade'));
            $result = $service->replace($old, $this->komisijaUser('zamjena-'.$key), 'R', now(), $this->canonicalActor(), 'akademska_zajednica');

            $this->assertTrue($result->succeeded, $key);
            $vacancy->refresh();
            $this->assertSame($expectLate, (bool) $vacancy->late, $key);
            $this->assertSame($storedReplacedAt, $vacancy->replaced_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $this->assertSame('2026-09-01 10:00:00', $vacancy->vacancy_triggered_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $this->assertSame((int) $result->recordId, (int) $vacancy->replacement_member_id, $key);

            if ($expectLate) {
                $this->assertSame(CanonicalCommissionMembershipService::LATE_REPLACEMENT_WARNING, $result->warning, $key);
                $this->assertSame(CanonicalCommissionMembershipService::LATE_REPLACEMENT_WARNING, $vacancy->compliance_warning, $key);
            } else {
                $this->assertNull($result->warning, $key);
                $this->assertNull($vacancy->compliance_warning, $key);
            }
        }
    }

    // 28
    public function test_one_year_mandate_ends_at_exact_anniversary(): void
    {
        [$commission] = $this->canonicalCommission(mandateStart: '2026-01-01 10:00:00');
        $at = fn (string $value) => CarbonImmutable::parse($value, 'Europe/Belgrade');

        $this->assertSame('2027-01-01 10:00:00', $commission->mandate_ends_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'));
        $this->assertSame('2027-01-01', $commission->end_date->toDateString());
        $this->assertFalse($this->gov()->mandateIsValid($commission, $at('2026-01-01 09:59:59')));
        $this->assertTrue($this->gov()->mandateIsValid($commission, $at('2026-01-01 10:00:00')));
        $this->assertTrue($this->gov()->mandateIsValid($commission, $at('2027-01-01 09:59:59')));
        $this->assertTrue($this->gov()->mandateIsValid($commission, $at('2027-01-01 10:00:00')));
        $this->assertFalse($this->gov()->mandateIsValid($commission, $at('2027-01-01 10:00:00.000001')));
        $this->assertFalse($this->gov()->mandateIsValid($commission, $at('2027-01-01 10:00:01')));
        $this->assertFalse($this->gov()->mandateIsValid($commission, $at('2027-01-01 23:59:59')));
    }

    public function test_replacement_member_inherits_original_commission_mandate_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00', 'Europe/Belgrade'));
        [$commission, $members] = $this->canonicalCommission(mandateStart: '2026-01-01 10:00:00');
        $competition = $this->competition($commission);
        $service = app(CanonicalCommissionMembershipService::class);

        Carbon::setTestNow(Carbon::parse('2026-12-20 10:00:00', 'Europe/Belgrade'));
        $old = $members[G::ROLE_SEKRETAR];
        $service->vacate($old, 'resigned', now(), $this->canonicalActor());
        $replacement = CommissionMember::query()->findOrFail(
            $service->replace($old, $this->komisijaUser('kasna-zamjena'), 'R-Z', now(), $this->canonicalActor())->recordId
        );
        $this->signDeclarations($replacement);

        $fresh = $commission->fresh();
        $this->assertSame($commission->id, $replacement->commission_id);
        $this->assertSame('2026-01-01 10:00:00', $fresh->mandate_starts_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'));
        $this->assertSame('2027-01-01 10:00:00', $fresh->mandate_ends_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'));
        $this->assertSame('2027-01-01', $fresh->end_date->toDateString());
        $this->assertNull($this->gov()->memberActionBlockReason($replacement->fresh(), $competition, CarbonImmutable::parse('2027-01-01 10:00:00', 'Europe/Belgrade')));
        $this->assertSame(G::MANDATE_MESSAGE,
            $this->gov()->memberActionBlockReason($replacement->fresh(), $competition, CarbonImmutable::parse('2027-01-01 10:00:01', 'Europe/Belgrade')));
        $this->assertSame(G::MANDATE_MESSAGE,
            $this->gov()->memberActionBlockReason($replacement->fresh(), $competition, CarbonImmutable::parse('2027-12-20 10:00:00', 'Europe/Belgrade')));
    }

    // 29
    public function test_expired_mandate_blocks_governance_actions(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $expired = CarbonImmutable::parse('2027-01-02 00:00:00', 'Europe/Belgrade');

        $this->assertSame(G::MANDATE_MESSAGE, $this->gov()->memberActionBlockReason($chair, $competition, $expired));
        $this->assertSame(G::FINAL_SIGNATURES_MESSAGE,
            $this->gov()->finalRankSignatureBlockReason($competition, collect($members)->pluck('id'), $expired));

        Carbon::setTestNow(Carbon::parse('2027-01-02 00:00:00', 'Europe/Belgrade'));
        $result = app(CanonicalCommissionSessionService::class)->record(
            $competition, $chair->user, G::PURPOSE_ORDINARY, now(), [$chair->id => G::ATTENDANCE_IN_PERSON],
        );
        $this->assertSame(G::MANDATE_MESSAGE, $result->error);
    }

    // 30, 31
    public function test_third_session_deadline_is_exact_interview_time_plus_seven_days_inclusive(): void
    {
        $cases = [
            'on-time-before' => ['2026-09-08 14:29:59', false, '2026-09-08 14:29:59'],
            'on-time-exact' => ['2026-09-08 14:30:00', false, '2026-09-08 14:30:00'],
            'late-next-instant' => ['2026-09-08 14:30:00.000001', true, '2026-09-08 14:30:00'],
            'late-next-second' => ['2026-09-08 14:30:01', true, '2026-09-08 14:30:01'],
        ];
        [$commission, $members] = $this->canonicalCommission();
        $sessions = app(CanonicalCommissionSessionService::class);

        foreach ($cases as $key => [$heldAt, $expectLate, $storedHeldAt]) {
            $competition = $this->competition($commission);
            Carbon::setTestNow(Carbon::parse('2026-09-01 14:30:00', 'Europe/Belgrade'));
            $interview = $this->confirmedSession($competition, $members, G::PURPOSE_INTERVIEW);
            $this->assertSame('2026-09-01 14:30:00', $interview->completed_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $competition->forceFill(['canonical_ranking_formed_at' => now()])->save();

            $result = $this->recordSessionResult($competition, $members, G::PURPOSE_FINAL_DECISION,
                CarbonImmutable::parse($heldAt, 'Europe/Belgrade'));

            $this->assertTrue($result->succeeded, $key.': '.$result->error);
            $session = CommissionSession::query()->findOrFail($result->recordId);
            $this->assertSame(CommissionSession::TYPE_THIRD, $session->session_type, $key);
            $this->assertSame('2026-09-08 14:30:00', $session->due_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $this->assertSame($expectLate, (bool) $session->late, $key);
            $this->assertSame($storedHeldAt, $session->held_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);

            if ($expectLate) {
                $this->assertSame(CanonicalCommissionSessionService::LATE_THIRD_SESSION_WARNING, $result->warning, $key);
                $this->assertSame(CanonicalCommissionSessionService::LATE_THIRD_SESSION_WARNING, $session->compliance_warning, $key);
            } else {
                $this->assertNull($result->warning, $key);
                $this->assertNull($session->compliance_warning, $key);
            }

            $this->assertSame(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, $this->gov()->finalDecisionSessionBlockReason($competition), $key);
            $confirm = $sessions->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
            $this->assertTrue($confirm->succeeded, $key);
            $this->assertNull($this->gov()->finalDecisionSessionBlockReason($competition), $key);
        }
    }

    public function test_final_decision_session_requires_confirmed_interview(): void
    {
        [$commission, $members] = $this->canonicalCommission();

        $this->assertSame(CanonicalCommissionSessionService::INTERVIEW_BEFORE_FINAL_MESSAGE,
            $this->recordSessionResult($this->competition($commission), $members, G::PURPOSE_FINAL_DECISION, now())->error);
    }

    // 32, 33, 34
    public function test_option_d_visibility_own_score_only_before_ranking(): void
    {
        [$commission, $members, $competition, $application] = $this->scoringReadyApplication();
        foreach ($members as $member) {
            EvaluationScore::query()->create([
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $member->canonical_seat_no,
                'completed_at' => now(),
            ]);
        }

        $chairView = $this->gov()->visibleScores($members[G::ROLE_PREDSJEDNIK]->user, $application);
        $this->assertCount(1, $chairView);
        $this->assertSame($members[G::ROLE_PREDSJEDNIK]->id, (int) $chairView->first()->commission_member_id);

        $secretaryView = $this->gov()->visibleScores($members[G::ROLE_SEKRETAR]->user, $application);
        $this->assertSame([$members[G::ROLE_SEKRETAR]->id], $secretaryView->pluck('commission_member_id')->map(fn ($id) => (int) $id)->all());

        $admin = $this->canonicalActor();
        $this->assertCount(0, $this->gov()->visibleScores($admin, $application));
        $this->assertFalse($this->gov()->aggregatesVisibleTo($admin, $competition));
        $this->assertFalse($this->gov()->aggregatesVisibleTo($members[G::ROLE_PREDSJEDNIK]->user, $competition));

        $this->assertCount(3, $this->gov()->visibleScores($members[G::ROLE_SEKRETAR]->user, $application, rankingFormed: true));
    }

    // 35
    public function test_inactive_or_non_member_is_denied(): void
    {
        [$commission, $members, $competition, $application] = $this->scoringReadyApplication();
        $outsider = $this->komisijaUser('van');

        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->gov()->scoreWriteBlockReason($outsider, $application));
        $this->assertCount(0, $this->gov()->visibleScores($outsider, $application));

        app(CanonicalCommissionMembershipService::class)->vacate($members[G::ROLE_SPOLJASNJI], 'inactive', now(), $this->canonicalActor());
        $this->assertSame(G::MEMBER_INACTIVE_MESSAGE, $this->gov()->memberActionBlockReason($members[G::ROLE_SPOLJASNJI]->fresh(), $competition));
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->gov()->scoreWriteBlockReason($members[G::ROLE_SPOLJASNJI]->user, $application));
        $this->assertSame(G::COMMISSION_INCOMPLETE_MESSAGE, $this->gov()->scoreWriteBlockReason($members[G::ROLE_PREDSJEDNIK]->user, $application));
    }

    // 36
    public function test_undeclared_member_is_denied(): void
    {
        [$commission, $members, $competition, $application] = $this->scoringReadyApplication();
        $members[G::ROLE_SEKRETAR]->update(['confidentiality_declaration' => null, 'declarations_signed_at' => null]);

        $this->assertSame(G::DECLARATIONS_MESSAGE, $this->gov()->scoreWriteBlockReason($members[G::ROLE_SEKRETAR]->user, $application));
        $this->assertSame(G::DECLARATIONS_MESSAGE, $this->gov()->memberActionBlockReason($members[G::ROLE_SEKRETAR]->fresh(), $competition));
    }

    // 37, 38
    public function test_final_rank_signatures_require_three_of_three(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $ids = collect($members)->pluck('id');

        $this->assertSame(G::FINAL_SIGNATURES_MESSAGE, $this->gov()->finalRankSignatureBlockReason($competition, $ids->take(2)));
        $this->assertSame(G::FINAL_SIGNATURES_MESSAGE, $this->gov()->finalRankSignatureBlockReason($competition, [$ids[0], $ids[0], $ids[1]]));
        $this->assertNull($this->gov()->finalRankSignatureBlockReason($competition, $ids));

        $members[G::ROLE_SPOLJASNJI]->update(['declarations_signed_at' => null]);
        $this->assertSame(G::FINAL_SIGNATURES_MESSAGE, $this->gov()->finalRankSignatureBlockReason($competition, $ids));
    }

    // 39, 40
    public function test_canonical_complaint_decision_requires_ready_chair(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $application = $this->finalApplication($competition);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $secretary = $members[G::ROLE_SEKRETAR]->user;

        $this->confirmOrdinarySession($competition);
        $this->assertTrue(app(CompletenessAssessmentService::class)->record($application, $chair, ApplicationCompletenessAssessment::INCOMPLETE, 'Nedostaje prilog.')->succeeded);
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($application->user)
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->sole();

        $this->assertSame(G::CHAIR_REQUIRED_MESSAGE, $this->gov()->complaintDecisionBlockReason($secretary, $application));
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $this->gov()->complaintDecisionBlockReason($this->komisijaUser('van'), $application));

        $this->actingAs($secretary)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected'])
            ->assertForbidden();
        $this->actingAs($this->canonicalActor())
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected'])
            ->assertForbidden();
        $this->assertNull($complaint->fresh()->decision);

        $members[G::ROLE_PREDSJEDNIK]->update(['declarations_signed_at' => null]);
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected'])
            ->assertForbidden();

        $this->signDeclarations($members[G::ROLE_PREDSJEDNIK]);
        $this->assertNull($this->gov()->complaintDecisionBlockReason($chair, $application));
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');
        $this->assertSame('rejected', $complaint->fresh()->decision);
        $this->assertSame((int) $chair->id, (int) $complaint->fresh()->decided_by_user_id);
    }

    public function test_canonical_partial_score_is_rejected_and_outcome_and_youth_session_routes_stay_closed(): void
    {
        [$commission, $members, $competition, $application] = $this->scoringReadyApplication();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->assertNull($this->gov()->scoreWriteBlockReason($chair, $application));

        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), ['criterion_1' => 5])
            ->assertSessionHasErrors(['criterion_2', 'scoring_confirmed']);
        $this->actingAs($chair)
            ->post(route('evaluation.store-decision', $application), ['decision' => 'approved'])
            ->assertForbidden();
        $this->actingAs($chair)
            ->post(route('commission-sessions.first.store', $competition), [])
            ->assertForbidden();
        $this->assertSame(0, EvaluationScore::query()->count());
    }

    public function test_legacy_member_mutation_routes_refuse_canonical_members(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $admin = $this->canonicalActor();

        $this->actingAs($admin)
            ->post(route('admin.commissions.members.update-status', $members[G::ROLE_SEKRETAR]), ['status' => 'inactive'])
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);
        $this->actingAs($admin)
            ->delete(route('admin.commissions.members.delete', $members[G::ROLE_SEKRETAR]))
            ->assertSessionHasErrors(['error' => CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE]);

        $this->assertSame('active', $members[G::ROLE_SEKRETAR]->fresh()->status);
        $this->assertNull($this->gov()->structuralCompletenessBlockReason($commission));
    }

    public function test_all_canonical_deadlines_are_exact_time_without_end_of_day_extension(): void
    {
        $policy = CompetitionProfileResolver::resolve(self::CANONICAL);
        $start = CarbonImmutable::parse('2026-09-01 10:00:00', 'Europe/Belgrade');

        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $policy->processDeadlineSemantics());
        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $policy->commissionGovernanceDeadlineSemantics());

        $this->assertSame('2026-09-04 10:00:00.000000', $policy->complaintWindowEndsAt($start)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2026-09-08 10:00:00.000000', $policy->complaintDecisionDueAt($start)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2026-09-16 10:00:00.000000', $policy->commissionReplacementDueAt($start)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2026-09-08 14:30:00.000000',
            $policy->thirdSessionDueAt(CarbonImmutable::parse('2026-09-01 14:30:00', 'Europe/Belgrade'))->format('Y-m-d H:i:s.u'));
        $this->assertSame('2027-01-01 10:00:00.000000',
            $policy->commissionMandateEndsAt(CarbonImmutable::parse('2026-01-01 10:00:00', 'Europe/Belgrade'))->format('Y-m-d H:i:s.u'));

        // Leap years: 29 February has no anniversary in 2029, so the mandate ends on the last day of February.
        $this->assertSame('2029-02-28 10:00:00',
            $policy->commissionMandateEndsAt(CarbonImmutable::parse('2028-02-29 10:00:00', 'Europe/Belgrade'))->format('Y-m-d H:i:s'));
        $this->assertSame('2028-02-28 10:00:00',
            $policy->commissionMandateEndsAt(CarbonImmutable::parse('2027-02-28 10:00:00', 'Europe/Belgrade'))->format('Y-m-d H:i:s'));
        $this->assertSame('2028-03-01 10:00:00',
            $policy->commissionMandateEndsAt(CarbonImmutable::parse('2027-03-01 10:00:00', 'Europe/Belgrade'))->format('Y-m-d H:i:s'));

        // Across DST the wall-clock time is kept (2026-10-25 CEST -> CET).
        $dst = $policy->commissionReplacementDueAt(CarbonImmutable::parse('2026-10-20 10:00:00', 'Europe/Belgrade'));
        $this->assertSame('2026-11-04 10:00:00 +01:00', $dst->format('Y-m-d H:i:s P'));

        foreach ([CompetitionProfileResolver::TYPE_WOMEN, CompetitionProfileResolver::TYPE_YOUTH] as $type) {
            $other = CompetitionProfileResolver::resolve($type);
            $this->assertNull($other->commissionReplacementDueAt($start), $type);
            $this->assertNull($other->thirdSessionDueAt($start), $type);
            $this->assertNull($other->commissionMandateEndsAt($start), $type);
        }
    }

    // 47
    public function test_canonical_catalogue_status_stays_development(): void
    {
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()[self::CANONICAL]['status']
        );
    }

    private function gov(): G
    {
        return app(G::class);
    }

    /**
     * @return array{0: Commission, 1: User}
     */
    private function commissionWithChairOnly(): array
    {
        $service = app(CanonicalCommissionMembershipService::class);
        $commission = $service->establish('Djelimična '.uniqid(), CarbonImmutable::parse('2026-01-01', 'Europe/Belgrade'), 'R-1');
        $chair = $this->komisijaUser('predsjednik');
        $this->assertTrue($service->appoint($commission, $chair, 1, G::ROLE_PREDSJEDNIK, null, 'R', now(), $this->canonicalActor())->succeeded);

        return [$commission->fresh(), $chair];
    }

    private function rawActiveMember(Commission $commission, User $user, int $seatNo, string $role, ?string $category = null): CommissionMember
    {
        return CommissionMember::query()->create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'clan',
            'status' => 'active',
            'canonical_seat_no' => $seatNo,
            'canonical_seat' => $role,
            'external_category' => $category,
            'canonical_active_user_id' => $user->id,
            'canonical_active_seat' => $role,
            'canonical_active_seat_no' => $seatNo,
        ]);
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
            'business_plan_name' => 'Plan',
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
     * @return array{0: Commission, 1: array<string, CommissionMember>, 2: Competition, 3: Application}
     */
    private function scoringReadyApplication(): array
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = $this->competition($commission);
        $application = $this->finalApplication($competition);
        $this->confirmOrdinarySession($competition);
        $this->assertTrue(app(CompletenessAssessmentService::class)
            ->record($application, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::COMPLETE)->succeeded);
        $this->confirmedSession($competition, $members, G::PURPOSE_INTERVIEW);

        return [$commission, $members, $competition, $application->fresh(['competition', 'finalClaim'])];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  array<string, string|null>  $modes  role => mode
     */
    private function recordSession(Competition $competition, array $members, string $purpose, array $modes): CommissionSession
    {
        $attendance = [];
        foreach ($modes as $role => $mode) {
            $attendance[$members[$role]->id] = $mode;
        }

        $result = app(CanonicalCommissionSessionService::class)->record(
            $competition, $members[G::ROLE_PREDSJEDNIK]->user, $purpose, now(), $attendance,
        );
        $this->assertTrue($result->succeeded, (string) $result->error);

        return CommissionSession::query()->findOrFail($result->recordId);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function recordSessionResult(Competition $competition, array $members, string $purpose, \DateTimeInterface $heldAt): \App\Services\Competitions\ProcessWriteResult
    {
        $attendance = [];
        foreach ($members as $member) {
            $attendance[$member->id] = G::ATTENDANCE_IN_PERSON;
        }

        return app(CanonicalCommissionSessionService::class)->record(
            $competition, $members[G::ROLE_PREDSJEDNIK]->user, $purpose, $heldAt, $attendance,
        );
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmedSession(Competition $competition, array $members, string $purpose): CommissionSession
    {
        $session = $this->recordSession($competition, $members, $purpose, [
            G::ROLE_PREDSJEDNIK => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SEKRETAR => G::ATTENDANCE_IN_PERSON,
            G::ROLE_SPOLJASNJI => G::ATTENDANCE_REMOTE,
        ]);
        $result = app(CanonicalCommissionSessionService::class)->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($result->succeeded, (string) $result->error);

        return $session->fresh();
    }
}
