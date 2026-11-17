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
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\CanonicalIndividualScoringService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ZenskoCommissionDisclosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_open_window_and_unresolved_prigovor_hide_scores_total_and_ranking(): void
    {
        $ctx = $this->scoredPositiveApplicationWithOpenAppeal();
        $service = app(CanonicalIndividualScoringService::class);
        $competition = $ctx['competition']->fresh();
        $viewer = $ctx['members'][1];
        $saved = EvaluationScore::query()
            ->where('application_id', $ctx['passed']->id)
            ->orderBy('canonical_seat_no')
            ->get();

        $this->assertTrue($service->isIndividualScoringCycleComplete($competition));
        $this->assertFalse($service->zenskoCommissionDisclosureIsAllowed($competition));
        $this->assertFalse($service->youthMutualScoreViewIsAllowed($competition));
        $this->assertFalse($competition->isRankingFormed());
        $this->assertNull($ctx['passed']->fresh()->ranking_position);
        $this->assertNull($ctx['passed']->fresh()->final_score);
        $this->assertSame(
            CanonicalIndividualScoringService::ZENSKO_APPEAL_WINDOW_MESSAGE,
            $service->zenskoAppealProcessBlockReason($competition)
        );

        $this->assertHiddenFrom($viewer, $ctx['passed']);
        $this->assertHiddenFrom($ctx['chairman'], $ctx['passed']);

        $showHtml = $this->actingAs($viewer->fresh('user')->user)
            ->get(route('evaluation.show', $ctx['passed']))
            ->assertOk()
            ->getContent();
        $this->assertOwnScoresOnly($showHtml, 'NAPOMENA-MJESTA-2', '2');

        $ranking = $this->actingAs($viewer->fresh('user')->user)
            ->get(route('admin.competitions.ranking', $competition));
        $ranking->assertForbidden();
        $this->assertStringNotContainsString('POZITIVNI-PLAN', $ranking->getContent());
        $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.ranking', $competition))
            ->assertForbidden();

        $indexHtml = $this->actingAs($viewer->fresh('user')->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString(' / 58', $indexHtml);
        $this->assertNull($ctx['passed']->fresh()->ranking_position);

        $this->actingAs($ctx['failed']->user)
            ->post(route('applications.prigovor.store', $ctx['failed']), [
                'obrazlozenje' => 'Izvještaj je bio priložen uz prijavu.',
            ])
            ->assertRedirect();
        $this->travelTo(now()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->addSecond());

        $duringPrigovor = $ctx['competition']->fresh();
        $this->assertTrue($service->isIndividualScoringCycleComplete($duringPrigovor));
        $this->assertFalse($service->zenskoCommissionDisclosureIsAllowed($duringPrigovor));
        $this->assertFalse($duringPrigovor->isRankingFormed());
        $this->assertSame(
            CanonicalIndividualScoringService::ZENSKO_PRIGOVOR_PODNESEN_MESSAGE,
            $service->zenskoAppealProcessBlockReason($duringPrigovor)
        );
        $this->assertHiddenFrom($viewer, $ctx['passed']->fresh());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $ctx['failed']), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Razlog ostaje.',
            ])
            ->assertRedirect();

        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['failed']->fresh()));
        $this->assertTrue($ctx['failed']->fresh()->isEliminatedFromScoring());
        $this->actingAs($viewer->fresh('user')->user)
            ->get(route('evaluation.index'))
            ->assertOk();

        $opened = $ctx['competition']->fresh();
        $this->assertTrue($service->zenskoCommissionDisclosureIsAllowed($opened));
        $this->assertTrue($opened->isRankingFormed());
        $this->assertNotNull($ctx['passed']->fresh()->ranking_position);
        $openedHtml = $this->actingAs($viewer->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['passed']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('NAPOMENA-MJESTA-1', $openedHtml);
        $this->assertStringContainsString('NAPOMENA-MJESTA-3', $openedHtml);
        $this->assertStringContainsString('30.00', $openedHtml);

        $after = EvaluationScore::query()
            ->where('application_id', $ctx['passed']->id)
            ->orderBy('canonical_seat_no')
            ->get();
        $this->assertCount(5, $after);
        $this->assertSame(
            $saved->map(fn (EvaluationScore $score) => (int) $score->criterion_1)->all(),
            $after->map(fn (EvaluationScore $score) => (int) $score->criterion_1)->all()
        );
        $this->travelBack();
    }

    public function test_accepted_prigovor_stays_closed_until_the_new_application_has_five_scores(): void
    {
        $ctx = $this->scoredPositiveApplicationWithOpenAppeal();
        $service = app(CanonicalIndividualScoringService::class);
        $before = EvaluationScore::query()->where('application_id', $ctx['passed']->id)->count();

        $this->actingAs($ctx['failed']->user)
            ->post(route('applications.prigovor.store', $ctx['failed']), [
                'obrazlozenje' => 'Izvještaj je bio priložen uz prijavu.',
            ])
            ->assertRedirect();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $ctx['failed']), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Kriterijum 2 je otklonjen.',
                'criterion_outcomes' => [2 => ApplicationPrigovor::OUTCOME_OTKLONJEN],
            ])
            ->assertRedirect();

        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['failed']->fresh()));
        $this->assertFalse($service->isIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertFalse($service->zenskoCommissionDisclosureIsAllowed($ctx['competition']->fresh()));
        $this->assertFalse($ctx['competition']->fresh()->isRankingFormed());
        $this->assertHiddenFrom($ctx['members'][1], $ctx['passed']);

        $this->scoreApplication($ctx['members'], $ctx['failed'], 4);
        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.index'))
            ->assertOk();

        $this->assertTrue($service->isIndividualScoringCycleComplete($ctx['competition']->fresh()));
        $this->assertTrue($service->zenskoCommissionDisclosureIsAllowed($ctx['competition']->fresh()));
        $this->assertTrue($ctx['competition']->fresh()->isRankingFormed());
        $this->assertSame($before, EvaluationScore::query()->where('application_id', $ctx['passed']->id)->count());
        $this->assertSame(1, (int) EvaluationScore::query()->where('application_id', $ctx['passed']->id)->where('canonical_seat_no', 1)->value('criterion_1'));
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: \Illuminate\Support\Collection<int, CommissionMember>, passed: Application, failed: Application}
     */
    private function scoredPositiveApplicationWithOpenAppeal(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska uvid '.uniqid(),
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
                'status' => 'active',
            ]));
        }

        $competition = Competition::create([
            'title' => 'Zensko uvid '.uniqid(),
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
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-ZU-'.uniqid()]);

        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $passed = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'POZITIVNI-PLAN',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $failedOwner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $failed = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $failedOwner->id,
            'business_plan_name' => 'ELIMINISANI-PLAN',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $chairman = $members[0]->fresh('user');
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $passed), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $failed), [
                'criterion_1' => '1',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'note' => 'Nedostaje izvještaj.',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        $this->scoreApplication($members, $passed, null);
        $this->actingAs($members[4]->fresh('user')->user)
            ->post(route('evaluation.store', $passed), $this->scorePayload(5, 'NAPOMENA-MJESTA-5'))
            ->assertRedirect();

        return [
            'competition' => $competition->fresh(),
            'chairman' => $chairman,
            'members' => $members,
            'passed' => $passed->fresh(),
            'failed' => $failed->fresh('user'),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CommissionMember>  $members
     */
    private function scoreApplication($members, Application $application, ?int $mark): void
    {
        foreach ($members as $index => $member) {
            $seat = $index + 1;
            if ($mark === null && $seat === 5) {
                continue;
            }
            $value = $mark ?? $seat;
            EvaluationScore::query()->create(array_merge($this->criteria($value), [
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $seat,
                'notes' => 'NAPOMENA-MJESTA-'.$seat,
            ]));
        }
    }

    private function assertHiddenFrom(CommissionMember $member, Application $application): void
    {
        $html = $this->actingAs($member->fresh('user')->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $seat = (int) $member->fresh()->canonical_seat_no;
        if ($seat < 1) {
            $seat = $member->position === 'predsjednik' ? 1 : 2;
        }
        $this->assertOwnScoresOnly($html, 'NAPOMENA-MJESTA-'.$seat, (string) $seat);
    }

    private function assertOwnScoresOnly(string $html, string $ownNote, string $ownValue): void
    {
        $this->assertStringContainsString($ownNote, $html);
        foreach ([1, 2, 3, 4, 5] as $seat) {
            $note = 'NAPOMENA-MJESTA-'.$seat;
            if ($note === $ownNote) {
                continue;
            }
            $this->assertStringNotContainsString($note, $html);
        }
        $this->assertStringNotContainsString('30.00', $html);
        $this->assertSame(array_fill(0, 10, $ownValue), $this->visibleScoreValues($html));
    }

    /**
     * @return list<string>
     */
    private function visibleScoreValues(string $html): array
    {
        preg_match_all('/<span class="score-display"[^>]*>\s*([1-5])\s*<\/span>/', $html, $matches);

        return array_map(static fn (string $value): string => trim($value), $matches[1]);
    }

    /**
     * @return array<string, int|string>
     */
    private function scorePayload(int $value, string $note): array
    {
        $payload = array_merge($this->criteria($value), [
            'notes' => $note,
            'scoring_confirmed' => '1',
        ]);

        return $payload;
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
