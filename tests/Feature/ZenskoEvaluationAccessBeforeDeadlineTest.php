<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\ScoringProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZenskoEvaluationAccessBeforeDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_evaluation_index_hides_zensko_applications_while_the_calendar_deadline_is_open(): void
    {
        foreach (['published', 'closed', 'completed'] as $status) {
            $competition = $this->competition('zensko', $status, false);
            $member = $this->attachCommission($competition, 5);
            $application = $this->application($competition, 'Zenska Lista '.$status);

            $response = $this->actingAs($member)->get(route('evaluation.index', [
                'competition_id' => $competition->id,
            ]));

            $response->assertOk();
            $response->assertDontSee($application->business_plan_name);
            $response->assertDontSee('Zenska Lista '.$status);
            $response->assertDontSee($competition->title);
        }
    }

    public function test_evaluation_create_denies_zensko_while_the_calendar_deadline_is_open(): void
    {
        foreach (['published', 'closed', 'completed'] as $status) {
            $competition = $this->competition('zensko', $status, false);
            $member = $this->attachCommission($competition, 5);
            $application = $this->application($competition, 'Zenska Obrazac '.$status);

            $response = $this->actingAs($member)->get(route('evaluation.create', $application));

            $response->assertForbidden();
            $this->assertStringContainsString(
                ScoringProfileConfig::for('zensko')->evaluationOpensAfterApplicationDeadlineMessage(),
                html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
            $response->assertDontSee($application->business_plan_name);
        }
    }

    public function test_closed_zensko_evaluation_follows_existing_rules_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'closed', true);
        $member = $this->attachCommission($competition, 5);
        $application = $this->application($competition, 'Zenska Poslije Roka');

        $this->actingAs($member)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee($application->business_plan_name)
            ->assertSee('Zenska Poslije Roka');

        $this->actingAs($member)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertSee($application->business_plan_name)
            ->assertSee('Zenska Poslije Roka');
    }

    public function test_incomplete_commission_stays_blocked_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'closed', true);
        $member = $this->attachCommission($competition, 1);
        $application = $this->application($competition, 'Zenska Nepotpuna Komisija');

        $this->actingAs($member)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertDontSee($application->business_plan_name)
            ->assertDontSee('Zenska Nepotpuna Komisija');

        $response = $this->actingAs($member)->get(route('evaluation.create', $application));
        $response->assertForbidden();
        $this->assertStringContainsString(
            Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE,
            html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
    }

    public function test_youth_closed_before_the_calendar_deadline_stays_available(): void
    {
        $competition = $this->competition(CompetitionProfileResolver::TYPE_YOUTH, 'closed', false);
        $member = $this->attachYouthCommission($competition);
        $application = $this->application($competition, 'Omladinski Vidljiv Prije Roka');

        $this->actingAs($member)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee($application->business_plan_name)
            ->assertSee('Omladinski Vidljiv Prije Roka');

        $this->actingAs($member)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertSee($application->business_plan_name);

        $this->confirmPass($application, $member);
        $this->actingAs($member)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $this->assertSame(1, EvaluationScore::query()->where('application_id', $application->id)->count());
    }

    public function test_store_rejects_zensko_score_before_the_calendar_deadline(): void
    {
        $message = ScoringProfileConfig::for('zensko')->evaluationOpensAfterApplicationDeadlineMessage();

        foreach (['published', 'closed', 'completed'] as $status) {
            $competition = $this->competition('zensko', $status, false);
            $chair = $this->attachCommission($competition, 5);
            $application = $this->application($competition, 'Zenska Upis '.$status);
            $this->confirmPass($application, $chair);
            $score = $this->finalScore($application, $chair, 3, 'Prvobitna ocjena');
            $application->forceFill([
                'bonus_info_day' => false,
                'bonus_new_business' => false,
                'bonus_zavod_nezaposleni' => false,
                'bonus_green_innovative' => false,
                'final_score' => null,
                'evaluated_at' => null,
            ])->save();
            $before = $this->scoringFingerprint($application);

            $this->actingAs($chair)
                ->from(route('evaluation.index'))
                ->post(route('evaluation.store', $application), $this->scorePayload([
                    'criterion_1' => 1,
                    'notes' => 'Pokušaj izmjene prije roka',
                    'bonus_info_day' => '1',
                    'bonus_new_business' => '1',
                    'bonus_zavod_nezaposleni' => '1',
                    'bonus_green_innovative' => '1',
                ]))
                ->assertRedirect(route('evaluation.index'))
                ->assertSessionHasErrors(['error' => $message]);

            $this->assertSame($before, $this->scoringFingerprint($application));
            $score->refresh();
            $this->assertSame(3, (int) $score->criterion_1);
            $this->assertSame('Prvobitna ocjena', $score->notes);
            $this->assertSame(1, EvaluationScore::query()->where('application_id', $application->id)->count());
        }
    }

    public function test_store_accepts_closed_zensko_score_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'closed', true);
        $chair = $this->attachCommission($competition, 5);
        $application = $this->application($competition, 'Zenska Upis Poslije Roka');
        $this->confirmPass($application, $chair);

        $this->actingAs($chair)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'criterion_1' => 4,
                'notes' => 'Ocjena poslije roka',
            ]))
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $score = EvaluationScore::query()->where('application_id', $application->id)->sole();
        $this->assertSame(4, (int) $score->criterion_1);
        $this->assertSame('Ocjena poslije roka', $score->notes);
        $this->assertNotNull($score->criterion_10);
        $application->refresh();
        $this->assertSame('submitted', $application->status);
    }

    public function test_incomplete_commission_still_cannot_store_a_score_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'closed', true);
        $chair = $this->attachCommission($competition, 1);
        $application = $this->application($competition, 'Zenska Nepotpuna Upis');
        $this->confirmPass($application, $chair);

        $response = $this->actingAs($chair)
            ->post(route('evaluation.store', $application), $this->scorePayload());

        $response->assertForbidden();
        $this->assertStringContainsString(
            Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE,
            html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $application->id)->count());
        $application->refresh();
        $this->assertNull($application->final_score);
        $this->assertNull($application->evaluated_at);
    }

    public function test_unconfirmed_form_3_still_blocks_scoring_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'closed', true);
        $chair = $this->attachCommission($competition, 5);
        $application = $this->application($competition, 'Zenska Bez Obrasca 3');

        $response = $this->actingAs($chair)
            ->post(route('evaluation.store', $application), $this->scorePayload());

        $response->assertForbidden();
        $this->assertStringContainsString(
            ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE,
            html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $application->id)->count());
        $application->refresh();
        $this->assertNull($application->final_score);
        $this->assertNull($application->evaluated_at);
        $this->assertFalse((bool) $application->bonus_info_day);
    }

    private function userWithRole(string $role, ?string $name = null): User
    {
        return User::factory()->create([
            'name' => $name ?? ('Korisnik '.$role.' '.uniqid()),
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $type, string $status, bool $deadlinePassed): Competition
    {
        return Competition::create([
            'title' => $type.' '.$status.' '.uniqid(),
            'type' => $type,
            'status' => $status,
            'year' => (int) now()->year,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => $deadlinePassed ? now()->subDays(40)->toDateString() : now()->subDay()->toDateString(),
            'end_date' => $deadlinePassed ? now()->subDays(10)->toDateString() : now()->addDays(19)->toDateString(),
            'published_at' => $status === 'published' ? now()->subDays(40) : null,
            'call_number' => $type === CompetitionProfileResolver::TYPE_YOUTH ? 1 : null,
            'annual_budget' => $type === CompetitionProfileResolver::TYPE_YOUTH ? '100000.00' : null,
        ]);
    }

    private function application(Competition $competition, string $applicantName): Application
    {
        $owner = $this->userWithRole('korisnik', $applicantName);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.$applicantName,
            'founder_name' => $applicantName,
            'submitted_at' => now(),
            'redni_broj' => 1,
        ]);
    }

    private function attachCommission(Competition $competition, int $members): User
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);

        $chair = null;
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < $members; $i++) {
            $user = $this->userWithRole('komisija');
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $types[$i],
                'status' => 'active',
                'canonical_seat_no' => $i + 1,
            ]);
            $chair ??= $user;
        }

        $competition->update(['commission_id' => $commission->id]);

        return $chair;
    }

    private function attachYouthCommission(Competition $competition): User
    {
        $commission = Commission::create([
            'name' => 'Komisija mladi '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);

        $chair = null;
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = $this->userWithRole('komisija');
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
            $chair ??= $user;
        }

        $competition->update(['commission_id' => $commission->id]);

        return $chair;
    }

    private function confirmPass(Application $application, User $chair): void
    {
        $member = CommissionMember::query()->where('user_id', $chair->id)->firstOrFail();

        ApplicationEliminatoryCheck::create([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now(),
            'confirmed_by_commission_member_id' => $member->id,
            'confirmed_by_user_id' => $chair->id,
            'confirmed_by_name' => $chair->name,
        ]);
    }

    private function finalScore(Application $application, User $chair, int $criterion, string $notes): EvaluationScore
    {
        $member = CommissionMember::query()->where('user_id', $chair->id)->firstOrFail();
        $payload = [
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'canonical_seat_no' => $member->canonical_seat_no,
            'completed_at' => now(),
            'notes' => $notes,
        ];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = $criterion;
        }

        return EvaluationScore::create($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function scorePayload(array $overrides = []): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return array_merge($payload, $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function scoringFingerprint(Application $application): array
    {
        $application->refresh();

        return [
            'status' => $application->status,
            'final_score' => $application->final_score,
            'evaluated_at' => $application->evaluated_at?->toDateTimeString(),
            'bonus_info_day' => (bool) $application->bonus_info_day,
            'bonus_new_business' => (bool) $application->bonus_new_business,
            'bonus_zavod_nezaposleni' => (bool) $application->bonus_zavod_nezaposleni,
            'bonus_green_innovative' => (bool) $application->bonus_green_innovative,
            'scores' => EvaluationScore::query()
                ->where('application_id', $application->id)
                ->orderBy('id')
                ->get()
                ->map(fn (EvaluationScore $score) => [
                    'id' => $score->id,
                    'commission_member_id' => $score->commission_member_id,
                    'criterion_1' => $score->criterion_1,
                    'criterion_10' => $score->criterion_10,
                    'notes' => $score->notes,
                    'final_score' => $score->final_score,
                    'completed_at' => $score->completed_at?->toDateTimeString(),
                ])
                ->all(),
        ];
    }
}
