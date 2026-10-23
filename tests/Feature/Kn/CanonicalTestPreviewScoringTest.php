<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
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
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\ScoringProfileConfig;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * Individual MMSP scoring before the application deadline is limited to a draft
 * competition with the test preview flag. Ranking formation is not part of this gate.
 */
class CanonicalTestPreviewScoringTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    private const DRAFT_HIDDEN = 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".';

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

    public function test_preview_member_can_score_a_submitted_application_before_the_deadline_without_forming_a_ranking(): void
    {
        [$members, $competition, $application] = $this->readyPreview();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->assertTrue($competition->isCanonicalTestPreview());
        $this->assertFalse($competition->isApplicationDeadlinePassed());

        $this->actingAs($chair)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee($application->business_plan_name);

        $this->actingAs($chair)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertSee('Kvalitet plana ulaganja');

        $this->actingAs($chair)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee($application->business_plan_name);

        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect(route('evaluation.create', $application));

        $this->assertSame(1, EvaluationScore::query()->where('application_id', $application->id)->whereNotNull('completed_at')->count());
        $this->assertNull($competition->fresh()->canonical_ranking_formed_at);

        foreach ([G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $role) {
            $this->actingAs($members[$role]->user)
                ->post(route('evaluation.store', $application), $this->scorePayload())
                ->assertRedirect(route('evaluation.create', $application));
        }

        $this->assertSame(3, EvaluationScore::query()->where('application_id', $application->id)->whereNotNull('completed_at')->count());
        $this->assertNull($competition->fresh()->canonical_ranking_formed_at);
        $this->assertNotNull(app(S::class)->canonicalCycleBlockReason($competition->fresh()));
    }

    public function test_preview_rejects_drafts_incomplete_applications_open_sessions_outsiders_and_locked_scores(): void
    {
        [$members, $competition, $application] = $this->readyPreview();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $draft = $this->application($competition, ['status' => 'draft', 'business_plan_name' => 'Skriveni nacrt', 'submitted_at' => null], false);

        $this->actingAs($chair)->get(route('evaluation.index'))->assertOk()->assertDontSee('Skriveni nacrt');
        $this->assertForbiddenSee(self::DRAFT_HIDDEN, fn () => $this->actingAs($chair)->get(route('evaluation.create', $draft)));
        $this->assertForbiddenSee(self::DRAFT_HIDDEN, fn () => $this->actingAs($chair)->get(route('admin.applications.show', $draft)));
        $this->assertSame(0, EvaluationScore::query()->count());

        $incomplete = $this->application($competition, ['business_plan_name' => 'Nepotpuna prijava']);
        $this->actingAs($chair)
            ->get(route('evaluation.create', $incomplete))
            ->assertOk()
            ->assertSee(G::SCORING_STAGE_CLOSED_MESSAGE);
        $this->assertForbiddenSee(
            G::SCORING_STAGE_CLOSED_MESSAGE,
            fn () => $this->actingAs($chair)->post(route('evaluation.store', $incomplete), $this->scorePayload()),
        );

        $outsider = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->assertForbiddenSee('Niste član komisije.', fn () => $this->actingAs($outsider)->get(route('evaluation.create', $application)));
        $this->assertForbiddenSee(G::NOT_MEMBER_MESSAGE, fn () => $this->actingAs($outsider)->post(route('evaluation.store', $application), $this->scorePayload()));

        $unconfirmed = $this->previewCompetition($competition->commission);
        $waiting = $this->application($unconfirmed);
        $this->confirmOrdinarySession($unconfirmed);
        $this->recordComplete($waiting, $members);
        $this->recordInterview($unconfirmed, $members, false);
        $this->assertForbiddenSee(
            G::INTERVIEW_REQUIRED_MESSAGE,
            fn () => $this->actingAs($chair)->post(route('evaluation.store', $waiting), $this->scorePayload()),
        );

        $noOrdinary = $this->previewCompetition($competition->commission);
        $early = $this->application($noOrdinary);
        $this->assertForbiddenSee(
            G::ORDINARY_SESSION_REQUIRED_MESSAGE,
            fn () => $this->actingAs($chair)->post(route('evaluation.store', $early), $this->scorePayload()),
        );

        $this->actingAs($chair)->post(route('evaluation.store', $application), $this->scorePayload())->assertRedirect();
        $before = EvaluationScore::query()->where('application_id', $application->id)->sole()->getAttributes();
        $this->assertForbiddenSee(
            G::SEAT_SCORE_COMPLETED_MESSAGE,
            fn () => $this->actingAs($chair)->post(route('evaluation.store', $application), $this->scorePayload()),
        );
        $this->assertSame($before, EvaluationScore::query()->where('application_id', $application->id)->sole()->getAttributes());
    }

    public function test_regular_mmsp_women_and_youth_remain_blocked_before_the_deadline(): void
    {
        [, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $published = $this->openCompetition($chair->commission, self::CANONICAL, 'published', false);
        $publishedApplication = $this->application($published);
        $message = ScoringProfileConfig::for(self::CANONICAL)->evaluationOpensAfterApplicationDeadlineMessage();

        $this->assertFalse($published->isCanonicalTestPreview());
        $this->assertFalse($published->isApplicationDeadlinePassed());
        $this->actingAs($chair->user)->get(route('evaluation.index'))->assertOk()->assertDontSee($publishedApplication->business_plan_name);
        $this->assertForbiddenSee($message, fn () => $this->actingAs($chair->user)->get(route('evaluation.create', $publishedApplication)));
        $this->assertForbiddenSee($message, fn () => $this->actingAs($chair->user)->post(route('evaluation.store', $publishedApplication), $this->scorePayload()));
        $this->assertForbiddenSee(
            'Prijave su komisiji vidljive tek nakon isteka roka za prijavljivanje na konkurs (20 dana). Do tada prijave nisu dostupne za pregled ni ocjenjivanje.',
            fn () => $this->actingAs($chair->user)->get(route('admin.applications.show', $publishedApplication)),
        );
        $this->assertSame(
            G::APPLICATION_DEADLINE_OPEN_MESSAGE,
            app(S::class)->canonicalScoreWriteBlockReason($chair->user, $publishedApplication),
        );

        $flagged = $this->openCompetition($chair->commission, self::CANONICAL, 'published', true);
        $flaggedApplication = $this->application($flagged);
        $this->assertTrue($flagged->isCanonicalTestMode());
        $this->assertFalse($flagged->isCanonicalTestPreview());
        $this->assertForbiddenSee($message, fn () => $this->actingAs($chair->user)->get(route('evaluation.create', $flaggedApplication)));
        $this->assertSame(
            G::APPLICATION_DEADLINE_OPEN_MESSAGE,
            app(S::class)->canonicalScoreWriteBlockReason($chair->user, $flaggedApplication),
        );

        $draftWithoutFlag = $this->openCompetition($chair->commission, self::CANONICAL, 'draft', false);
        $draftCallApplication = $this->application($draftWithoutFlag);
        $this->assertForbiddenSee($message, fn () => $this->actingAs($chair->user)->get(route('evaluation.create', $draftCallApplication)));

        $this->assertProfileStaysClosed('zensko', ScoringProfileConfig::WOMEN_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE);
        $this->assertProfileStaysClosed('omladinsko', ScoringProfileConfig::YOUTH_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE);
    }

    private function assertProfileStaysClosed(string $type, string $message): void
    {
        $commission = Commission::create([
            'name' => 'Komisija '.$type.' '.uniqid(),
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'status' => 'active',
        ]);
        $competition = $this->openCompetition($commission, $type, 'published', true);
        $application = $this->application($competition);

        $this->assertFalse($competition->isCanonicalTestPreview());
        $this->actingAs($user)->get(route('evaluation.index'))->assertOk()->assertDontSee($application->business_plan_name);
        $this->assertForbiddenSee($message, fn () => $this->actingAs($user)->get(route('evaluation.create', $application)));
        $this->actingAs($user)
            ->from(route('evaluation.index'))
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect(route('evaluation.index'))
            ->assertSessionHasErrors(['error' => $message]);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $application->id)->count());
    }

    /**
     * @return array{0: array<string, CommissionMember>, 1: Competition, 2: Application}
     */
    private function readyPreview(): array
    {
        [, $members] = $this->canonicalCommission();
        $competition = $this->previewCompetition($members[G::ROLE_PREDSJEDNIK]->commission);
        $application = $this->application($competition);
        $this->confirmOrdinarySession($competition);
        $this->recordComplete($application, $members);
        $this->recordInterview($competition, $members, true);

        return [$members, $competition->fresh(), $application->fresh(['competition', 'finalClaim'])];
    }

    private function previewCompetition(Commission $commission): Competition
    {
        return $this->openCompetition($commission, self::CANONICAL, 'draft', true);
    }

    private function openCompetition(Commission $commission, string $type, string $status, bool $testPreview): Competition
    {
        return Competition::create([
            'title' => $type.' '.$status.' '.uniqid(),
            'type' => $type,
            'status' => $status,
            'is_test_preview' => $testPreview,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-21',
            'published_at' => $status === 'published' ? '2026-09-01 09:00:00' : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function application(Competition $competition, array $overrides = [], bool $finalClaim = true): Application
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
            'applicant_type' => 'preduzetnik',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Podnosilac',
            'submitted_at' => now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ], $overrides));
        if ($finalClaim && $application->status !== 'draft') {
            FinalApplicationClaim::query()->create([
                'user_id' => $owner->id,
                'competition_id' => $competition->id,
                'application_id' => $application->id,
                'claimed_at' => now(),
            ]);
        }

        return $application->fresh(['competition', 'finalClaim']);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function recordComplete(Application $application, array $members): void
    {
        $result = app(CompletenessAssessmentService::class)
            ->record($application, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::COMPLETE);
        $this->assertTrue($result->succeeded, (string) $result->error);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function recordInterview(Competition $competition, array $members, bool $confirm): void
    {
        $service = app(CanonicalCommissionSessionService::class);
        $result = $service->record($competition, $members[G::ROLE_PREDSJEDNIK]->user, G::PURPOSE_INTERVIEW, now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ]);
        $this->assertTrue($result->succeeded, (string) $result->error);
        if ($confirm) {
            $session = CommissionSession::query()->findOrFail($result->recordId);
            $confirmed = $service->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
            $this->assertTrue($confirmed->succeeded, (string) $confirmed->error);
        }
    }

    /**
     * @return array<string, int|string>
     */
    private function scorePayload(): array
    {
        $payload = ['scoring_confirmed' => '1'];
        foreach (range(1, 10) as $criterion) {
            $payload['criterion_'.$criterion] = 4;
        }

        return $payload;
    }

    private function assertForbiddenSee(string $message, callable $request): void
    {
        $response = $request();
        $response->assertForbidden();
        $this->assertStringContainsString($message, html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
