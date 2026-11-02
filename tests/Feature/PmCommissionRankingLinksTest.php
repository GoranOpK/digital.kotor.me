<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PmCommissionRankingLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_commission_show_sends_pm_ranking_button_to_youth_ranking(): void
    {
        $ctx = $this->closedCompetition('omladinsko');
        $youthUrl = route('evaluation.youth-ranking', $ctx['competition']);
        $adminUrl = route('admin.competitions.ranking', $ctx['competition']);

        foreach ([$ctx['chairman'], $ctx['member']] as $user) {
            $html = $this->actingAs($user)
                ->get(route('admin.competitions.show', $ctx['competition']))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString($youthUrl, $html);
            $this->assertStringNotContainsString($adminUrl, $html);
            $this->assertStringContainsString('>Rang lista<', $html);

            $this->actingAs($user)
                ->get($youthUrl)
                ->assertOk();
        }

        $response = $this->actingAs($ctx['chairman'])
            ->get($adminUrl);
        $response->assertForbidden();
        $this->assertSame(
            CanonicalIndividualScoringService::YOUTH_ADMIN_RANKING_ROUTE_MESSAGE,
            $response->exception?->getMessage()
        );
    }

    public function test_commission_show_keeps_zensko_ranking_button_on_admin_route(): void
    {
        $ctx = $this->closedCompetition('zensko');
        $html = $this->actingAs($ctx['member'])
            ->get(route('admin.competitions.show', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.competitions.ranking', $ctx['competition']), $html);
        $this->assertStringNotContainsString(route('evaluation.youth-ranking', $ctx['competition']), $html);
        $this->assertStringContainsString('>Rang lista<', $html);
    }

    public function test_dashboard_sends_pm_ranking_button_to_youth_ranking(): void
    {
        $ctx = $this->rankingReadyYouth();
        $youthUrl = route('evaluation.youth-ranking', $ctx['competition']);
        $adminUrl = route('admin.competitions.ranking', $ctx['competition']);

        foreach ([$ctx['chairman']->user, $ctx['members'][1]->fresh('user')->user] as $user) {
            $html = $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString($youthUrl, $html);
            $this->assertStringNotContainsString($adminUrl, $html);

            $this->actingAs($user)->get($youthUrl)->assertOk();
        }
    }

    public function test_dashboard_keeps_zensko_ranking_destination(): void
    {
        $zensko = $this->openCompetition('zensko');
        $this->partialMock(CanonicalIndividualScoringService::class, function ($mock): void {
            $mock->shouldReceive('isIndividualScoringCycleComplete')->andReturn(true);
        });

        $zenskoHtml = $this->actingAs($zensko['member'])
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.competitions.ranking', $zensko['competition']), $zenskoHtml);
        $this->assertStringNotContainsString(route('evaluation.youth-ranking', $zensko['competition']), $zenskoHtml);
    }

    public function test_dashboard_keeps_canonical_ranking_destination(): void
    {
        $canonical = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $canonical['competition']->forceFill(['canonical_ranking_formed_at' => now()])->save();

        $html = $this->actingAs($canonical['member'])
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('evaluation.canonical-outcome.show', $canonical['competition']), $html);
        $this->assertStringNotContainsString(route('evaluation.youth-ranking', $canonical['competition']), $html);
        $this->assertStringNotContainsString(route('admin.competitions.ranking', $canonical['competition']), $html);
    }

    public function test_evaluation_index_does_not_render_the_purple_pm_ranking_link(): void
    {
        $ctx = $this->closedCompetition('omladinsko');

        $html = $this->actingAs($ctx['member'])
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('evaluation.youth-ranking', $ctx['competition']), $html);
        $this->assertStringContainsString('Preliminarna rang-lista — PM — '.$ctx['competition']->title, $html);
        $this->assertStringNotContainsString('📊 Rang lista - '.$ctx['competition']->title, $html);
        $this->assertStringNotContainsString(route('admin.competitions.ranking', $ctx['competition']), $html);
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User}
     */
    private function closedCompetition(string $type): array
    {
        $ctx = $this->openCompetition($type);
        $ctx['competition']->update(['status' => 'closed']);
        $ctx['competition'] = $ctx['competition']->fresh();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User}
     */
    private function openCompetition(string $type): array
    {
        $commission = Commission::create([
            'name' => 'Komisija '.$type.' '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $chairman = $this->userWithRole('komisija');
        $member = $this->userWithRole('komisija');
        $seatPlan = match ($type) {
            'omladinsko' => [
                [$chairman, 'predsjednik', 1],
                [$member, 'clan', 2],
                [$this->userWithRole('komisija'), 'clan', 3],
            ],
            'zensko' => [
                [$chairman, 'predsjednik', 1],
                [$member, 'clan', 2],
                [$this->userWithRole('komisija'), 'clan', 3],
                [$this->userWithRole('komisija'), 'clan', 4],
                [$this->userWithRole('komisija'), 'clan', 5],
            ],
            default => [
                [$chairman, 'predsjednik', 1],
                [$member, 'clan', 2],
            ],
        };
        foreach ($seatPlan as [$user, $position, $seat]) {
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $position,
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }
        $competition = Competition::create([
            'title' => 'Konkurs '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'commission_id' => $commission->id,
        ]);

        return [
            'competition' => $competition->fresh(),
            'chairman' => $chairman,
            'member' => $member,
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function rankingReadyYouth(): array
    {
        $year = 2026;
        $commission = Commission::create([
            'name' => 'Mladi linkovi '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = $this->userWithRole('komisija');
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
            'title' => 'Omladinsko linkovi '.$year,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-C-'.$year.'-1',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan linkovi '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $ctx = [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application->fresh('user'),
        ];

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'criterion_notes' => [1 => '', 2 => '', 3 => ''],
            ])
            ->assertRedirect();

        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }
        foreach ([1, 2, 3] as $seat) {
            $this->actingAs($ctx['members'][$seat - 1]->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $payload)
                ->assertSessionHasNoErrors();
        }
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), [
                'youth_bonus_action' => 'confirm',
            ])
            ->assertSessionHasNoErrors();
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        return $ctx;
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
