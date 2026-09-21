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
use App\Services\Competitions\YouthAllocationDraftService;
use App\Support\CompetitionAnnualInstance;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthAllocationGateTest extends TestCase
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

    public function test_draft_is_blocked_until_preliminary_ranking_is_ready(): void
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockThreeSeats($ctx);
        $before = $ctx['application']->fresh();
        $this->assertSame('evaluated', $before->status);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-allocation-draft-form"', $html);
        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_RANKING_BONUSES_UNCONFIRMED_MESSAGE, $html);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 5000,
            ]);
        $response->assertForbidden();
        $this->assertSame(
            YouthAllocationDraftService::RANKING_NOT_READY_MESSAGE,
            $response->exception?->getMessage()
        );

        $after = $ctx['application']->fresh();
        $this->assertSame($before->commission_decision, $after->commission_decision);
        $this->assertSame($before->approved_amount, $after->approved_amount);
        $this->assertSame($before->commission_justification, $after->commission_justification);
        $this->assertSame('evaluated', $after->status);

        $this->confirmYouthBonuses($ctx);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 5000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
        $this->assertSame('podrzava_potpuno', $ctx['application']->fresh()->commission_decision);
        $this->assertSame('5000.00', $ctx['application']->fresh()->approved_amount);
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }

    public function test_only_active_chairman_of_that_call_can_save_and_see_draft_form(): void
    {
        $ctx = $this->rankingReady();
        $other = $this->rankingReady();
        $below = $this->addPassedApplication($ctx, 'Ispod praga');
        $this->lockThreeSeats($ctx, [1 => ['criterion_1' => 2]], $below);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $below]);

        $chairmanHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-allocation-draft-form"', $chairmanHtml);
        $this->assertStringContainsString(route('evaluation.youth-allocation-draft', $ctx['application']), $chairmanHtml);
        $this->assertStringNotContainsString(route('evaluation.youth-allocation-draft', $below), $chairmanHtml);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-allocation-draft-form"', $memberHtml);

        $response = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

        $response = $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

        $dualUser = $ctx['chairman']->user;
        $otherSeatTwo = $other['members'][1];
        $otherSeatTwo->update([
            'user_id' => $dualUser->id,
            'name' => $dualUser->name,
            'position' => 'clan',
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);
        $response = $this->actingAs($dualUser)
            ->post(route('evaluation.youth-allocation-draft', $other['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

        $replacementChairman = $this->userWithRole('komisija');
        $ctx['chairman']->update([
            'user_id' => $replacementChairman->id,
            'name' => $replacementChairman->name,
            'position' => 'predsjednik',
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);
        $response = $this->actingAs($dualUser)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ])
            ->assertForbidden();

        $before = $ctx['application']->fresh();
        $this->assertSame('evaluated', $before->status);
        $drafts = app(YouthAllocationDraftService::class);
        $remainingBefore = $drafts->remainingBudget($ctx['competition']->fresh());
        $annualBefore = app(CompetitionAnnualInstance::class)
            ->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year);
        $youthCreateUrl = route('evaluation.create', $before);

        $konkursAdmin = $this->userWithRole('konkurs_admin');
        $response = $this->actingAs($konkursAdmin)
            ->from($youthCreateUrl)
            ->post(route('evaluation.youth-allocation-draft', $before), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionMissing('success');
        $this->assertNotSame($youthCreateUrl, $response->headers->get('Location'));

        $after = $before->fresh();
        $this->assertSame($before->commission_decision, $after->commission_decision);
        $this->assertSame($before->approved_amount, $after->approved_amount);
        $this->assertSame($before->commission_justification, $after->commission_justification);
        $this->assertSame('evaluated', $after->status);
        $this->assertSame($before->ranking_position, $after->ranking_position);
        $this->assertSame($before->final_score, $after->final_score);
        $this->assertSame($remainingBefore, $drafts->remainingBudget($ctx['competition']->fresh()));
        $this->assertSame(
            $annualBefore,
            app(CompetitionAnnualInstance::class)->remainingAfterFirst('omladinsko', (int) $ctx['competition']->year)
        );

        $this->actingAs($ctx['application']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ])
            ->assertForbidden();

        $zensko = $this->makeZenskoChairman();
        $response = $this->actingAs($zensko)
            ->post(route('evaluation.youth-allocation-draft', $other['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::CHAIRMAN_ONLY_MESSAGE, $response->exception?->getMessage());
    }

    public function test_below_threshold_cannot_receive_support_decision(): void
    {
        $ctx = $this->rankingReady();
        $below = $this->addPassedApplication($ctx, 'Ispod praga');
        $below->update(['requested_amount' => 5000]);
        $this->lockThreeSeats($ctx, [1 => ['criterion_1' => 2]], $below);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $below]);
        $this->assertNull($below->fresh()->ranking_position);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $below), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 1000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::BELOW_THRESHOLD_MESSAGE, $response->exception?->getMessage());

        $this->assertNull($below->fresh()->commission_decision);
        $this->assertSame('evaluated', $below->fresh()->status);
    }

    public function test_amount_cannot_exceed_requested_or_remaining_and_green_cap_is_not_applied(): void
    {
        $ctx = $this->rankingReady();
        $ctx['competition']->update(['budget' => '10000.00']);
        $ctx['application']->update([
            'requested_amount' => 3000,
            'bonus_green_innovative' => true,
        ]);
        $second = $this->addPassedApplication($ctx, 'Drugi plan');
        $second->update(['requested_amount' => 8000]);
        $this->lockThreeSeats($ctx, [], $second);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $second]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 4000,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REQUESTED_MESSAGE]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 3000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('3000.00', $ctx['application']->fresh()->approved_amount);
        $this->assertSame('evaluated', $ctx['application']->fresh()->status);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $second))
            ->post(route('evaluation.youth-allocation-draft', $second), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 8000,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE]);
    }

    public function test_reject_and_reduced_amount_require_justification(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->update(['requested_amount' => 5000]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'odbija',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['commission_justification' => YouthAllocationDraftService::REJECT_JUSTIFICATION_REQUIRED_MESSAGE]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 2000,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['commission_justification' => YouthAllocationDraftService::REDUCED_AMOUNT_JUSTIFICATION_REQUIRED_MESSAGE]);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 2000,
                'commission_justification' => 'Djelimična podrška zbog budžeta.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
        $this->assertSame('2000.00', $ctx['application']->fresh()->approved_amount);
        $this->assertSame('Djelimična podrška zbog budžeta.', $ctx['application']->fresh()->commission_justification);
    }

    public function test_draft_can_be_edited_without_changing_status_or_remaining_after_first(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->update(['requested_amount' => 8000]);
        $year = (int) $ctx['competition']->year;
        $annual = app(CompetitionAnnualInstance::class);
        $before = $annual->remainingAfterFirst('omladinsko', $year);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 8000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
        $this->assertSame($before, $annual->remainingAfterFirst('omladinsko', $year));

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Nema dovoljno sredstava za cijeli iznos.',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $ctx['application']->fresh();
        $this->assertSame('evaluated', $fresh->status);
        $this->assertSame('odbija', $fresh->commission_decision);
        $this->assertNull($fresh->approved_amount);
        $this->assertSame($before, $annual->remainingAfterFirst('omladinsko', $year));
        $this->assertNotSame('rejected', $fresh->status);
    }

    public function test_womens_entries_are_closed_and_forty_five_day_deadline_is_not_applied(): void
    {
        $ctx = $this->rankingReady();
        $ctx['application']->update(['requested_amount' => 4000]);

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store-decision', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 4000,
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::WOMEN_STORE_DECISION_CLOSED_MESSAGE, $response->exception?->getMessage());

        $response = $this->actingAs($ctx['chairman']->user)
            ->post(route('admin.competitions.select-winners', $ctx['competition']), [
                'winners' => [
                    $ctx['application']->id => [
                        'selected' => '1',
                        'approved_amount' => 4000,
                    ],
                ],
            ]);
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::WOMEN_SELECT_WINNERS_CLOSED_MESSAGE, $response->exception?->getMessage());

        $response = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.decision', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(YouthAllocationDraftService::WOMEN_PREDLOG_CLOSED_MESSAGE, $response->exception?->getMessage());

        $response = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $ctx['competition']));
        $response->assertForbidden();
        $this->assertSame(CanonicalIndividualScoringService::YOUTH_ADMIN_RANKING_ROUTE_MESSAGE, $response->exception?->getMessage());

        $this->travelTo(now()->addDays(50));
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 4000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
        $this->assertSame('4000.00', $ctx['application']->fresh()->approved_amount);
    }

    public function test_equal_score_insufficient_budget_does_not_resolve_priority(): void
    {
        $ctx = $this->rankingReady();
        $ctx['competition']->update(['budget' => '50000.00']);
        $ctx['application']->update(['requested_amount' => 40000, 'business_stage' => 'započinjanje']);
        $tied = $this->addPassedApplication($ctx, 'Izjednaceni razvoj');
        $tied->update(['requested_amount' => 40000, 'business_stage' => 'razvoj']);
        $this->lockThreeSeats($ctx, [], $tied);
        $this->confirmYouthBonuses(['chairman' => $ctx['chairman'], 'application' => $tied]);

        $this->assertSame(
            (int) $ctx['application']->fresh()->ranking_position,
            (int) $tied->fresh()->ranking_position
        );

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.youth-allocation-draft', $ctx['application']), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 40000,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $tied))
            ->post(route('evaluation.youth-allocation-draft', $tied), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 40000,
            ])
            ->assertSessionHasErrors(['approved_amount' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REMAINING_MESSAGE]);

        $this->assertSame('evaluated', $ctx['application']->fresh()->status);
        $this->assertSame('evaluated', $tied->fresh()->status);
        $this->assertNull($tied->fresh()->commission_decision);
        $this->assertSame(
            (int) $ctx['application']->fresh()->ranking_position,
            (int) $tied->fresh()->ranking_position
        );
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function rankingReady(): array
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->update(['requested_amount' => 5000]);
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());

        return $ctx;
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
            'name' => 'Mladi raspodjela '.$year.'-'.$callNumber,
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
            'title' => 'Omladinsko raspodjela '.$year.' poziv '.$callNumber,
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
            'competition_number' => 'UP-A-'.$year.'-'.$callNumber,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan raspodjela '.$year.'-'.$callNumber,
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

    private function makeZenskoChairman(): User
    {
        $commission = Commission::create([
            'name' => 'Zenska raspodjela '.uniqid(),
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

        return $user;
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
