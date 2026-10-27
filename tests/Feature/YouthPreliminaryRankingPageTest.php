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
use App\Support\CommissionCanonicalSeat;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthPreliminaryRankingPageTest extends TestCase
{
    use RefreshDatabase;

    private static int $yearSerial = 2110;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_active_member_can_open_the_youth_ranking_page(): void
    {
        $ctx = $this->rankingReady();

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->assertSee('Preliminarna rang-lista', false)
            ->assertSee($ctx['application']->business_plan_name, false)
            ->assertSee('data-testid="youth-ranking-print"', false);
    }

    public function test_chairman_sees_draft_and_confirmation_without_member_actions_for_others(): void
    {
        $ctx = $this->rankingReady();

        $chairmanHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="youth-allocation-draft-form"', $chairmanHtml);
        $this->assertStringContainsString(route('evaluation.youth-allocation-draft', $ctx['application']), $chairmanHtml);
        $this->assertStringContainsString('data-testid="youth-allocation-list-confirmation"', $chairmanHtml);
        $this->assertStringNotContainsString('data-testid="youth-allocation-list-confirm-form"', $chairmanHtml);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Preliminarna rang-lista', $memberHtml);
        $this->assertStringContainsString('data-testid="youth-ranking-print"', $memberHtml);
        $this->assertStringContainsString('data-testid="youth-allocation-list-confirmation"', $memberHtml);
        $this->assertStringNotContainsString('data-testid="youth-allocation-draft-form"', $memberHtml);
        $this->assertStringNotContainsString('data-testid="youth-equal-score-round-form"', $memberHtml);
        $this->assertStringNotContainsString('data-testid="youth-allocation-list-confirm-form"', $memberHtml);
    }

    public function test_user_outside_the_commission_cannot_open_the_page(): void
    {
        $ctx = $this->rankingReady();
        $other = $this->makeYouthContext();

        $this->actingAs($other['members'][1]->fresh('user')->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('korisnik'))
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertForbidden();
    }

    public function test_zensko_competition_cannot_use_the_youth_ranking_route(): void
    {
        $ctx = $this->zenskoReadyToScore();

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertForbidden()
            ->assertDontSee($ctx['application']->business_plan_name);
    }

    public function test_missing_competition_is_rejected(): void
    {
        $ctx = $this->makeYouthContext();

        $this->actingAs($ctx['chairman']->user)
            ->get('/evaluation/youth/999999999/ranking')
            ->assertNotFound();
    }

    public function test_unready_list_does_not_reveal_results_or_write_positions(): void
    {
        $ctx = $this->passedEliminatory();

        $html = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE, $html);
        $this->assertStringNotContainsString('Preliminarna rang-lista', $html);
        $this->assertStringNotContainsString('/ 56', $html);
        $this->assertStringNotContainsString('data-testid="youth-ranking-print"', $html);
        $this->assertStringNotContainsString('data-testid="youth-allocation-draft-form"', $html);
        $this->assertNull($ctx['application']->fresh()->ranking_position);
        $this->assertNull($ctx['application']->fresh()->final_score);
    }

    public function test_ready_list_shows_existing_data_and_repeat_get_does_not_change_it(): void
    {
        $ctx = $this->rankingReady();
        $before = $ctx['application']->fresh();

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('RANG LISTA BIZNIS PLANOVA', $html);
        $this->assertStringContainsString('Preliminarna rang-lista', $html);
        $this->assertStringContainsString($before->business_plan_name, $html);
        $this->assertStringContainsString(number_format((float) $before->final_score, 2).' / 56', $html);
        $this->assertStringContainsString('data-testid="youth-ranking-print"', $html);

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk();

        $after = $ctx['application']->fresh();
        $this->assertSame($before->final_score, $after->final_score);
        $this->assertSame($before->ranking_position, $after->ranking_position);
    }

    public function test_existing_forms_post_back_to_the_youth_ranking_page(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->update(['requested_amount' => 5000]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.youth-ranking', $ctx['competition']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 5000,
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ])
            ->assertRedirect(route('evaluation.youth-ranking', $ctx['competition']));

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->from(route('evaluation.youth-ranking', $ctx['competition']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'odbija',
            ])
            ->assertForbidden();
    }

    public function test_zensko_only_member_does_not_see_the_pm_ranking_link(): void
    {
        $zensko = $this->zenskoReadyToScore();

        $html = $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Preliminarna rang-lista — PM', $html);
        $this->assertStringNotContainsString('/evaluation/youth/', $html);
        $this->assertStringContainsString($zensko['application']->business_plan_name, $html);
    }

    public function test_multiple_pm_memberships_get_exact_links_and_the_filter_does_not_swap_them(): void
    {
        $first = $this->rankingReady();
        $second = $this->rankingReady();
        $user = $first['chairman']->user;
        $second['chairman']->update([
            'user_id' => $user->id,
            'name' => $user->name,
        ]);

        $html = $this->actingAs($user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertSame(2, substr_count($html, 'Preliminarna rang-lista — PM'));
        $this->assertStringContainsString(route('evaluation.youth-ranking', $first['competition']), $html);
        $this->assertStringContainsString(route('evaluation.youth-ranking', $second['competition']), $html);
        $this->assertStringNotContainsString('RANG LISTA BIZNIS PLANOVA', $html);

        $filteredToSecond = $this->actingAs($user)
            ->get(route('evaluation.index', ['competition_id' => $second['competition']->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString(route('evaluation.youth-ranking', $second['competition']), $filteredToSecond);
        $this->assertStringNotContainsString(route('evaluation.youth-ranking', $first['competition']), $filteredToSecond);
        $this->assertStringContainsString($second['application']->business_plan_name, $filteredToSecond);
        $this->assertStringNotContainsString($first['application']->business_plan_name, $filteredToSecond);

        $zensko = $this->zenskoReadyToScore();
        $zensko['chairman']->update([
            'user_id' => $user->id,
            'name' => $user->name,
        ]);
        $filteredToZensko = $this->actingAs($user->fresh())
            ->get(route('evaluation.index', ['competition_id' => $zensko['competition']->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Preliminarna rang-lista — PM', $filteredToZensko);
        $this->assertStringNotContainsString(route('evaluation.youth-ranking', $first['competition']), $filteredToZensko);
        $this->assertStringNotContainsString(route('evaluation.youth-ranking', $second['competition']), $filteredToZensko);
        $this->assertStringContainsString($zensko['application']->business_plan_name, $filteredToZensko);
    }

    public function test_youth_ranking_page_does_not_replace_the_existing_list_or_zensko_score_redirect(): void
    {
        $ctx = $this->rankingReady();

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee('Preliminarna rang-lista — PM', false)
            ->assertSee(route('evaluation.youth-ranking', $ctx['competition']), false)
            ->assertDontSee('RANG LISTA BIZNIS PLANOVA', false)
            ->assertDontSee('data-testid="youth-ranking-print"', false);

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->assertSee('Preliminarna rang-lista', false)
            ->assertSee('RANG LISTA BIZNIS PLANOVA', false);

        $zensko = $this->zenskoReadyToScore();
        $member = $zensko['members']->firstWhere('position', 'clan');

        $this->actingAs($member->user)
            ->post(route('evaluation.store', $zensko['application']), $this->scorePayload())
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function rankingReady(): array
    {
        $ctx = $this->passedEliminatory();
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function passedEliminatory(): array
    {
        $ctx = $this->makeYouthContext();
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
    private function makeYouthContext(): array
    {
        $year = self::$yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi stranica '.$year,
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
            'title' => 'Omladinsko stranica '.$year,
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
            'business_plan_name' => 'Plan stranica '.$year,
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
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}  $ctx
     */
    private function lockThreeSeats(array $ctx): void
    {
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $this->scorePayload())
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * @param  array{chairman: CommissionMember, application: Application}  $ctx
     */
    private function confirmYouthBonuses(array $ctx): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), [
                'youth_bonus_action' => 'confirm',
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: \Illuminate\Support\Collection<int, CommissionMember>, application: Application}
     */
    private function zenskoReadyToScore(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska stranica '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        foreach ($types as $i => $type) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $type,
                'status' => 'active',
            ]);
        }
        $commission = $commission->fresh(['activeMembers.user', 'members']);
        CommissionCanonicalSeat::persistForCommission($commission);

        $competition = Competition::create([
            'title' => 'Zensko stranica '.uniqid(),
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
            'business_plan_name' => 'Zenski plan stranica',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $chairman = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail()->fresh('user');
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        return [
            'competition' => $competition->fresh(),
            'chairman' => $chairman,
            'members' => $commission->fresh('activeMembers.user')->activeMembers,
            'application' => $application->fresh(),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function scorePayload(): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return $payload;
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
