<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PmFinalRankingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_final_ranking_status_follows_the_stored_chairman_outcome(): void
    {
        $ctx = $this->rankedApplications();
        $winner = $ctx['winner'];
        $rejected = $ctx['rejected'];
        $below = $ctx['below'];

        $before = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('<h2>Preliminarna rang-lista</h2>', $before);
        $this->assertStringNotContainsString('Konačna rang-lista', $before);
        $this->assertStringNotContainsString('data-testid="youth-ranking-signatures"', $before);
        $this->assertStringNotContainsString('data-testid="youth-decision-open"', $before);
        $this->assertStringContainsString('Štampaj rang-listu', $before);
        $indexBefore = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Preliminarna rang-lista — PM — '.$ctx['competition']->title, $indexBefore);
        $this->assertStringNotContainsString('Konačna rang-lista — PM — '.$ctx['competition']->title, $indexBefore);

        $this->assertStringNotContainsString('Dobitnik sredstava', $before);
        $this->assertStringNotContainsString('Odbijena prijava', $before);
        $this->assertStringContainsString('Ispod bodovnog praga', $before);
        $this->assertNull($winner->fresh()->commission_decision);
        $this->assertNull($rejected->fresh()->commission_decision);
        $this->assertNull($below->fresh()->commission_decision);
        $this->assertSame('rejected', $below->fresh()->status);
        $this->assertSame(
            YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
            $below->fresh()->rejection_reason
        );

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $winner), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 5000,
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $rejected), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Komisija nije podržala ovu prijavu.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $winner = $winner->fresh();
        $rejected = $rejected->fresh();
        $this->assertSame('podrzava_potpuno', $winner->commission_decision);
        $this->assertSame('approved', $winner->status);
        $this->assertSame('5000.00', (string) $winner->approved_amount);
        $this->assertSame('odbija', $rejected->commission_decision);
        $this->assertSame('rejected', $rejected->status);
        $this->assertSame('0.00', (string) $rejected->approved_amount);

        $first = $this->page($ctx);
        $second = $this->page($ctx);
        foreach ([$first, $second] as $html) {
            $winnerRow = $this->row($html, $winner->business_plan_name);
            $rejectedRow = $this->row($html, $rejected->business_plan_name);
            $belowRow = $this->row($html, $below->business_plan_name);

            $this->assertStringContainsString('Dobitnik sredstava', $winnerRow);
            $this->assertStringNotContainsString('Odbijena prijava', $winnerRow);

            $this->assertStringContainsString('Odbijena prijava', $rejectedRow);
            $this->assertStringContainsString('score-badge score-low', $rejectedRow);
            $this->assertStringContainsString('color: #6b7280;">-</span>', $rejectedRow);
            $this->assertStringNotContainsString('Dobitnik sredstava', $rejectedRow);

            $this->assertStringContainsString('Ispod bodovnog praga', $belowRow);
            $this->assertStringNotContainsString('Odbijena prijava', $belowRow);
            $this->assertStringNotContainsString('Dobitnik sredstava', $belowRow);
        }

        $finalHtml = $first;
        $this->assertStringContainsString('<h2>Konačna rang-lista</h2>', $finalHtml);
        $this->assertStringNotContainsString('<h2>Preliminarna rang-lista</h2>', $finalHtml);
        $this->assertStringContainsString('data-testid="youth-decision-open"', $finalHtml);
        $this->assertStringContainsString('Štampaj rang-listu', $finalHtml);
        $this->assertStringContainsString('youth-ranking-no-print no-print', $finalHtml);
        $signatures = $this->signatureBlock($finalHtml);
        $this->assertStringNotContainsString('no-print', $signatures);
        $this->assertStringContainsString('Potpis komisije', $signatures);
        $this->assertStringContainsString('Predsjednik Komisije', $signatures);
        $this->assertStringContainsString('Član 1', $signatures);
        $this->assertStringContainsString('Član 2', $signatures);
        $chairmanAt = strpos($signatures, $ctx['chairman']->name);
        $memberAt = strpos($signatures, $ctx['members'][1]->name);
        $lastAt = strpos($signatures, $ctx['members'][2]->name);
        $this->assertNotFalse($chairmanAt);
        $this->assertNotFalse($memberAt);
        $this->assertNotFalse($lastAt);
        $this->assertLessThan($memberAt, $chairmanAt);
        $this->assertLessThan($lastAt, $memberAt);
        $this->assertStringContainsString('printYouthPreliminaryRanking(this)', $finalHtml);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('<h2>Konačna rang-lista</h2>', $memberHtml);
        $this->assertStringContainsString($ctx['chairman']->name, $this->signatureBlock($memberHtml));
        $this->assertStringNotContainsString('data-testid="youth-decision-open"', $memberHtml);

        $indexAfter = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Konačna rang-lista — PM — '.$ctx['competition']->title, $indexAfter);
        $this->assertStringNotContainsString('Preliminarna rang-lista — PM — '.$ctx['competition']->title, $indexAfter);

        $lockedWinner = $winner->fresh()->only([
            'status', 'commission_decision', 'approved_amount', 'rejection_reason', 'commission_justification',
        ]);
        $lockedRejected = $rejected->fresh()->only([
            'status', 'commission_decision', 'approved_amount', 'rejection_reason', 'commission_justification',
        ]);
        $lockedBelow = $below->fresh()->only([
            'status', 'commission_decision', 'approved_amount', 'rejection_reason', 'ranking_position', 'final_score',
        ]);

        $changeWinner = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $winner), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Pokušaj izmjene.',
            ]);
        $changeWinner->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::DECISION_LOCKED_MESSAGE, $changeWinner->exception?->getMessage());

        $changeRejected = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $rejected), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 5000,
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ]);
        $changeRejected->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::DECISION_LOCKED_MESSAGE, $changeRejected->exception?->getMessage());

        $this->assertSame($lockedWinner, $winner->fresh()->only(array_keys($lockedWinner)));
        $this->assertSame($lockedRejected, $rejected->fresh()->only(array_keys($lockedRejected)));
        $this->assertSame($lockedBelow, $below->fresh()->only(array_keys($lockedBelow)));
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
    private function page(array $ctx): string
    {
        return $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
    }

    private function signatureBlock(string $html): string
    {
        $start = strpos($html, 'data-testid="youth-ranking-signatures"');
        $this->assertNotFalse($start);
        $end = strpos($html, 'youth-ranking-no-print', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    private function row(string $html, string $name): string
    {
        $start = strpos($html, $name);
        $this->assertNotFalse($start);
        $end = strpos($html, '</tr>', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, winner: Application, rejected: Application, below: Application}
     */
    private function rankedApplications(): array
    {
        $year = 2026;
        $commission = Commission::create([
            'name' => 'Mladi status '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = User::factory()->create([
                'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
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
            'title' => 'Omladinsko status '.$year,
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
            'competition_number' => 'UP-C-'.$year.'-status',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $ctx = [
            'competition' => $competition->fresh(),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
        ];
        $winner = $this->application($ctx, 'Plan dobitnik', 5000);
        $rejected = $this->application($ctx, 'Plan odbijen', 5000);
        $below = $this->application($ctx, 'Plan ispod praga', 1000);

        $this->score($ctx, $winner, 5);
        $this->score($ctx, $rejected, 3);
        $this->score($ctx, $below, 2);
        foreach ([$winner, $rejected, $below] as $application) {
            $this->actingAs($ctx['chairman']->user)
                ->post(route('evaluation.store', $application), [
                    'youth_bonus_action' => 'confirm',
                ])
                ->assertSessionHasNoErrors();
        }
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());
        $this->assertGreaterThanOrEqual(30, (float) $winner->fresh()->final_score);
        $this->assertGreaterThanOrEqual(30, (float) $rejected->fresh()->final_score);
        $this->assertLessThan(30, (float) $below->fresh()->final_score);

        return $ctx + [
            'winner' => $winner->fresh(),
            'rejected' => $rejected->fresh(),
            'below' => $below->fresh(),
        ];
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
    private function application(array $ctx, string $name, int $requestedAmount): Application
    {
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ])->id,
            'business_plan_name' => $name,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'requested_amount' => $requestedAmount,
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'criterion_notes' => [1 => '', 2 => '', 3 => ''],
            ])
            ->assertRedirect();

        return $application->fresh();
    }

    /**
     * @param  array{members: list<CommissionMember>}  $ctx
     */
    private function score(array $ctx, Application $application, int $value): void
    {
        $payload = ['scoring_confirmed' => '1', 'notes' => null];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = $value;
        }
        foreach ([1, 2, 3] as $seat) {
            $this->actingAs($ctx['members'][$seat - 1]->fresh('user')->user)
                ->post(route('evaluation.store', $application), $payload)
                ->assertSessionHasNoErrors();
        }
    }
}
