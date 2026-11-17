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
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ZenskoPreliminaryRankingFormationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_fifth_locked_score_forms_the_ranking_and_reopening_does_not_change_it(): void
    {
        $ctx = $this->competitionWithPassedApplication('Plan pet ocjena');
        $this->lockSeats($ctx['members'], $ctx['application'], 3, skipSeat: 5);

        $competition = $ctx['competition']->fresh();
        $service = app(CanonicalIndividualScoringService::class);
        $this->assertFalse($service->isIndividualScoringCycleComplete($competition));
        $this->assertFalse($competition->isRankingFormed());
        $this->assertNull($ctx['application']->fresh()->ranking_position);
        $this->assertNull($ctx['application']->fresh()->final_score);

        $blocked = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $competition));
        $blocked->assertForbidden();
        $this->assertSame(CanonicalIndividualScoringService::RANKING_LOCKED_MESSAGE, $blocked->exception?->getMessage());
        $this->assertHiddenFrom($ctx['members'][1], $ctx['application']);

        $this->actingAs($ctx['members'][4]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->scorePayload(3, 'NAPOMENA-MJESTA-5'))
            ->assertRedirect();

        $formed = $ctx['application']->fresh();
        $this->assertSame(1, (int) $formed->ranking_position);
        $this->assertSame('30.00', (string) $formed->final_score);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());
        $this->assertNull($service->commissionPreliminaryResultsLockMessage($ctx['competition']->fresh()));

        $before = [
            'position' => (int) $formed->ranking_position,
            'score' => (string) $formed->final_score,
            'updated_at' => (string) $formed->updated_at,
            'criteria' => EvaluationScore::query()
                ->where('application_id', $formed->id)
                ->orderBy('canonical_seat_no')
                ->pluck('criterion_1')
                ->map(fn ($value) => (int) $value)
                ->all(),
        ];

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStoredBadge($html, 1, 'Plan pet ocjena');

        $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk();

        $again = $ctx['application']->fresh();
        $this->assertSame($before['position'], (int) $again->ranking_position);
        $this->assertSame($before['score'], (string) $again->final_score);
        $this->assertSame($before['updated_at'], (string) $again->updated_at);
        $this->assertSame($before['criteria'], EvaluationScore::query()
            ->where('application_id', $again->id)
            ->orderBy('canonical_seat_no')
            ->pluck('criterion_1')
            ->map(fn ($value) => (int) $value)
            ->all());

        $opened = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('NAPOMENA-MJESTA-1', $opened);
        $this->assertStringContainsString('NAPOMENA-MJESTA-5', $opened);
    }

    public function test_accepted_prigovor_waits_for_five_scores_of_the_new_application(): void
    {
        $ctx = $this->competitionWithPassedApplication('Vec ocijenjen plan');
        $failed = $this->addApplication($ctx['competition'], 'Nova prihvacena prijava');
        $this->confirmFail($ctx['chairman'], $failed);
        $this->lockSeats($ctx['members'], $ctx['application'], 3);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $this->actingAs($failed->user)
            ->post(route('applications.prigovor.store', $failed), [
                'obrazlozenje' => 'Izvještaj je bio priložen uz prijavu.',
            ])
            ->assertRedirect();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $failed), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Kriterijum 2 je otklonjen.',
                'criterion_outcomes' => [2 => ApplicationPrigovor::OUTCOME_OTKLONJEN],
            ])
            ->assertRedirect();

        $service = app(CanonicalIndividualScoringService::class);
        $this->assertTrue($failed->fresh()->prigovor->liftsEliminatoryBar());
        $this->assertFalse($service->isIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertNull($ctx['application']->fresh()->ranking_position);
        $this->assertNull($failed->fresh()->ranking_position);
        $blocked = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']));
        $blocked->assertForbidden();
        $this->assertHiddenFrom($ctx['members'][1], $ctx['application']);

        $this->lockSeats($ctx['members'], $failed, 5, skipSeat: 5);
        $this->assertNull($failed->fresh()->ranking_position);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $this->actingAs($ctx['members'][4]->fresh('user')->user)
            ->post(route('evaluation.store', $failed), $this->scorePayload(5, 'NAPOMENA-NOVA-5'))
            ->assertRedirect();

        $accepted = $failed->fresh();
        $passed = $ctx['application']->fresh();
        $this->assertTrue($accepted->eliminatoryNotice->prigovorWindowIsOpen());
        $this->assertTrue($accepted->prigovor->isFinished());
        $this->assertNull($service->zenskoAppealProcessBlockReason($ctx['competition']->fresh()));
        $this->assertSame(1, (int) $accepted->ranking_position);
        $this->assertSame('50.00', (string) $accepted->final_score);
        $this->assertSame(2, (int) $passed->ranking_position);
        $this->assertSame('30.00', (string) $passed->final_score);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStoredBadge($html, 1, 'Nova prihvacena prijava');
        $this->assertStoredBadge($html, 2, 'Vec ocijenjen plan');
    }

    public function test_rejected_prigovor_forms_the_ranking_without_another_request(): void
    {
        $ctx = $this->competitionWithPassedApplication('Plan poslije odbijanja');
        $failed = $this->addApplication($ctx['competition'], 'Odbijeni plan');
        $this->confirmFail($ctx['chairman'], $failed);
        $this->lockSeats($ctx['members'], $ctx['application'], 4);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $this->actingAs($failed->user)
            ->post(route('applications.prigovor.store', $failed), [
                'obrazlozenje' => 'Izvještaj je bio priložen uz prijavu.',
            ])
            ->assertRedirect();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $failed), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Razlog ostaje.',
            ])
            ->assertRedirect();

        $passed = $ctx['application']->fresh();
        $this->assertTrue($failed->fresh()->isEliminatedFromScoring());
        $this->assertTrue($failed->fresh()->eliminatoryNotice->prigovorWindowIsOpen());
        $this->assertSame(1, (int) $passed->ranking_position);
        $this->assertSame('40.00', (string) $passed->final_score);
        $this->assertNull($failed->fresh()->ranking_position);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStoredBadge($html, 1, 'Plan poslije odbijanja');
        $this->assertStringNotContainsString('Odbijeni plan', $html);
        $this->assertSame(1, (int) $passed->fresh()->ranking_position);
    }

    public function test_expired_unused_window_is_written_by_opening_the_ranking(): void
    {
        $ctx = $this->competitionWithPassedApplication('Plan poslije roka');
        $failed = $this->addApplication($ctx['competition'], 'Neiskoriscen rok');
        $this->confirmFail($ctx['chairman'], $failed);
        $this->lockSeats($ctx['members'], $ctx['application'], 3);
        $service = app(CanonicalIndividualScoringService::class);

        $this->assertTrue($service->isIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertSame(
            CanonicalIndividualScoringService::ZENSKO_APPEAL_WINDOW_MESSAGE,
            $service->zenskoAppealProcessBlockReason($ctx['competition']->fresh())
        );
        $service->persistApplicationAggregatesIfCycleComplete($ctx['competition']->fresh());
        $this->assertNull($ctx['application']->fresh()->ranking_position);
        $open = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']));
        $open->assertForbidden();
        $this->assertSame(CanonicalIndividualScoringService::ZENSKO_APPEAL_WINDOW_MESSAGE, $open->exception?->getMessage());

        $this->travelTo(now()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->addSecond());

        $this->assertNull($ctx['application']->fresh()->ranking_position);
        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $written = $ctx['application']->fresh();
        $this->assertSame(1, (int) $written->ranking_position);
        $this->assertSame('30.00', (string) $written->final_score);
        $this->assertNull($failed->fresh()->ranking_position);
        $this->assertStoredBadge($html, 1, 'Plan poslije roka');
        $this->assertStringNotContainsString('Neiskoriscen rok', $html);
    }

    public function test_unresolved_prigovor_blocks_the_ranking_after_the_calendar_expires(): void
    {
        $ctx = $this->competitionWithPassedApplication('Plan uz nerijesen prigovor');
        $failed = $this->addApplication($ctx['competition'], 'Podnesen prigovor');
        $this->confirmFail($ctx['chairman'], $failed);
        $this->lockSeats($ctx['members'], $ctx['application'], 3);
        $this->actingAs($failed->user)
            ->post(route('applications.prigovor.store', $failed), [
                'obrazlozenje' => 'Izvještaj je bio priložen uz prijavu.',
            ])
            ->assertRedirect();

        $this->travelTo(now()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->addSecond());

        $service = app(CanonicalIndividualScoringService::class);
        $competition = $ctx['competition']->fresh();
        $this->assertFalse($failed->fresh()->eliminatoryNotice->prigovorWindowIsOpen());
        $this->assertSame(
            CanonicalIndividualScoringService::ZENSKO_PRIGOVOR_PODNESEN_MESSAGE,
            $service->zenskoAppealProcessBlockReason($competition)
        );
        $service->persistApplicationAggregatesIfCycleComplete($competition);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $ranking = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $competition));
        $ranking->assertForbidden();
        $this->assertSame(
            CanonicalIndividualScoringService::ZENSKO_PRIGOVOR_PODNESEN_MESSAGE,
            $ranking->exception?->getMessage()
        );
        $this->assertStringNotContainsString('Plan uz nerijesen prigovor', $ranking->getContent());
        $this->assertNull($ctx['application']->fresh()->ranking_position);
    }

    public function test_equal_scores_keep_shared_positions_one_two_two_four(): void
    {
        $ctx = $this->competitionWithPassedApplication('Plan pedeset');
        $tieA = $this->addPassedApplication($ctx, 'Plan cetrdeset A');
        $tieB = $this->addPassedApplication($ctx, 'Plan cetrdeset B');
        $fourth = $this->addPassedApplication($ctx, 'Plan trideset');

        $this->lockSeats($ctx['members'], $ctx['application'], 5);
        $this->lockSeats($ctx['members'], $tieA, 4);
        $this->lockSeats($ctx['members'], $tieB, 4);
        $this->lockSeats($ctx['members'], $fourth, 3, skipSeat: 5);
        $this->assertNull($ctx['application']->fresh()->ranking_position);

        $this->actingAs($ctx['members'][4]->fresh('user')->user)
            ->post(route('evaluation.store', $fourth), $this->scorePayload(3, 'NAPOMENA-MJESTA-5'))
            ->assertRedirect();

        $this->assertSame(1, (int) $ctx['application']->fresh()->ranking_position);
        $this->assertSame(2, (int) $tieA->fresh()->ranking_position);
        $this->assertSame(2, (int) $tieB->fresh()->ranking_position);
        $this->assertSame(4, (int) $fourth->fresh()->ranking_position);
        $this->assertSame('50.00', (string) $ctx['application']->fresh()->final_score);
        $this->assertSame('40.00', (string) $tieA->fresh()->final_score);
        $this->assertSame('40.00', (string) $tieB->fresh()->final_score);
        $this->assertSame('30.00', (string) $fourth->fresh()->final_score);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStoredBadge($html, 1, 'Plan pedeset');
        $this->assertStoredBadge($html, 2, 'Plan cetrdeset A');
        $this->assertStoredBadge($html, 2, 'Plan cetrdeset B');
        $this->assertStoredBadge($html, 4, 'Plan trideset');
        $this->assertDoesNotMatchRegularExpression('/<span class="ranking-badge">\s*3\s*<\/span>/', $html);

        $positions = [
            $ctx['application']->id => 1,
            $tieA->id => 2,
            $tieB->id => 2,
            $fourth->id => 4,
        ];
        $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']))
            ->assertOk();
        foreach ($positions as $id => $position) {
            $this->assertSame($position, (int) Application::query()->findOrFail($id)->ranking_position);
        }
    }

    public function test_closed_competition_keeps_existing_positions(): void
    {
        $ctx = $this->competitionWithPassedApplication('Zatvoren plan');
        $second = $this->addPassedApplication($ctx, 'Zatvoren drugi');
        $this->lockSeats($ctx['members'], $ctx['application'], 5);
        $this->lockSeats($ctx['members'], $second, 3, skipSeat: 5);
        $this->actingAs($ctx['members'][4]->fresh('user')->user)
            ->post(route('evaluation.store', $second), $this->scorePayload(3, 'NAPOMENA-MJESTA-5'))
            ->assertRedirect();

        $ctx['competition']->forceFill(['status' => 'closed'])->save();
        $ctx['application']->forceFill([
            'ranking_position' => 8,
            'final_score' => '48.00',
        ])->save();
        $second->forceFill([
            'ranking_position' => 9,
            'final_score' => '31.00',
        ])->save();
        $before = [
            $ctx['application']->id => ['8', '48.00'],
            $second->id => ['9', '31.00'],
        ];

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']->fresh()))
            ->assertOk()
            ->getContent();

        foreach ($before as $id => [$position, $score]) {
            $fresh = Application::query()->findOrFail($id);
            $this->assertSame($position, (string) $fresh->ranking_position);
            $this->assertSame($score, (string) $fresh->final_score);
        }
        $this->assertStoredBadge($html, 8, 'Zatvoren plan');
        $this->assertStoredBadge($html, 9, 'Zatvoren drugi');
        $this->assertStringContainsString('48.00', $html);
        $this->assertStringContainsString('31.00', $html);
        $this->assertStringNotContainsString('50.00', $html);
        $this->assertDoesNotMatchRegularExpression('/<span class="ranking-badge">\s*1\s*<\/span>/', $html);
    }

    public function test_pm_ranking_route_does_not_write_youth_positions(): void
    {
        $commission = Commission::create([
            'name' => 'Mladi rank zastita',
            'year' => 2114,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $competition = Competition::create([
            'title' => 'Omladinsko rank zastita',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2114,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-C-2114-1',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-C-2114-1']);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ])->id,
            'business_plan_name' => 'PM plan bez ranga',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.competitions.ranking', $competition));
        $response->assertForbidden();
        $this->assertSame(
            CanonicalIndividualScoringService::YOUTH_ADMIN_RANKING_ROUTE_MESSAGE,
            $response->exception?->getMessage()
        );
        app(CanonicalIndividualScoringService::class)
            ->persistApplicationAggregatesIfCycleComplete($competition->fresh());

        $this->assertNull($application->fresh()->ranking_position);
        $this->assertNull($application->fresh()->final_score);
        $this->assertFalse($competition->fresh()->isRankingFormed());
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: Collection<int, CommissionMember>, application: Application}
     */
    private function competitionWithPassedApplication(string $planName): array
    {
        $commission = Commission::create([
            'name' => 'Zenska rang '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
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
                'canonical_seat_no' => $i + 1,
                'status' => 'active',
            ]));
        }

        $competition = Competition::create([
            'title' => 'Zensko rang '.uniqid(),
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
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-ZR-'.uniqid()]);

        $chairman = $members[0]->fresh('user');
        $application = $this->addApplication($competition, $planName);
        $this->confirmPass($chairman, $application);

        return [
            'competition' => $competition->fresh(),
            'chairman' => $chairman,
            'members' => $members,
            'application' => $application->fresh(),
        ];
    }

    private function addPassedApplication(array $ctx, string $planName): Application
    {
        $application = $this->addApplication($ctx['competition'], $planName);
        $this->confirmPass($ctx['chairman'], $application);

        return $application->fresh();
    }

    private function addApplication(Competition $competition, string $planName): Application
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => $planName,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'requested_amount' => 1000,
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ])->fresh('user');
    }

    private function confirmPass(CommissionMember $chairman, Application $application): void
    {
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();
    }

    private function confirmFail(CommissionMember $chairman, Application $application): void
    {
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'note' => 'Nedostaje izvještaj.',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();
    }

    /**
     * @param  Collection<int, CommissionMember>  $members
     */
    private function lockSeats(Collection $members, Application $application, int $value, ?int $skipSeat = null): void
    {
        foreach ($members as $index => $member) {
            $seat = $index + 1;
            if ($skipSeat === $seat) {
                continue;
            }
            EvaluationScore::query()->create(array_merge($this->criteria($value), [
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $seat,
                'notes' => 'NAPOMENA-MJESTA-'.$seat,
                'completed_at' => now(),
            ]));
        }
    }

    private function assertHiddenFrom(CommissionMember $member, Application $application): void
    {
        $html = $this->actingAs($member->fresh('user')->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('NAPOMENA-MJESTA-2', $html);
        $this->assertStringNotContainsString('NAPOMENA-MJESTA-1', $html);
        $this->assertStringNotContainsString('NAPOMENA-MJESTA-5', $html);
        $this->assertStringNotContainsString('30.00', $html);
    }

    private function assertStoredBadge(string $html, int $position, string $planName): void
    {
        $this->assertMatchesRegularExpression(
            '/<span class="ranking-badge">\s*'.$position.'\s*<\/span>[\s\S]{0,900}'.preg_quote($planName, '/').'/',
            $html
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function scorePayload(int $value, string $note): array
    {
        return array_merge($this->criteria($value), [
            'notes' => $note,
            'scoring_confirmed' => '1',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function criteria(int $value): array
    {
        $criteria = [];
        for ($i = 1; $i <= 10; $i++) {
            $criteria['criterion_'.$i] = $value;
        }

        return $criteria;
    }
}
