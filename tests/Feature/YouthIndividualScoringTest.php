<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Support\CompetitionProgramCatalog;
use App\Support\ScoringProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthIndividualScoringTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2140;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_draft_is_allowed_before_oral_and_may_be_incomplete(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), [
                'save_as_draft' => '1',
                'criterion_1' => 3,
                'notes' => 'Nacrt prije usmenog',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.create', $ctx['application']));

        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($score);
        $this->assertNull($score->completed_at);
        $this->assertNull($score->canonical_seat_no);
        $this->assertSame(3, (int) $score->criterion_1);
        $this->assertNull($score->criterion_2);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertNull($ctx['application']->fresh()->final_score);
        $this->assertNull($ctx['application']->fresh()->evaluated_at);
        $this->assertNull($ctx['application']->fresh()->ranking_position);
    }

    public function test_full_draft_is_not_automatically_final(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->draftPayload())
            ->assertSessionHasNoErrors();

        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($score);
        $this->assertNull($score->completed_at);
        $this->assertFalse(app(\App\Services\CanonicalIndividualScoringService::class)->isFinalCompleted($score));
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
    }

    public function test_final_is_rejected_before_second_session_and_before_oral(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload())
            ->assertForbidden();
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ctx['application']->id)->whereNotNull('completed_at')->count());

        $this->storeAndConfirmSecond($ctx);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload())
            ->assertForbidden();
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ctx['application']->id)->whereNotNull('completed_at')->count());
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
    }

    public function test_completed_oral_with_attendance_or_no_show_allows_final_without_auto_score(): void
    {
        $attended = $this->youthWithPassedM3();
        $this->storeAndConfirmSecond($attended);
        $this->storeAndCompleteOral($attended, true);
        $this->actingAs($attended['chairman']->user)
            ->post(route('evaluation.store', $attended['application']), $this->finalPayload(['criterion_10' => 4]))
            ->assertSessionHasNoErrors();
        $attendedScore = EvaluationScore::query()
            ->where('application_id', $attended['application']->id)
            ->where('commission_member_id', $attended['chairman']->id)
            ->first();
        $this->assertNotNull($attendedScore->completed_at);
        $this->assertSame(4, (int) $attendedScore->criterion_10);
        $this->assertSame('submitted', $attended['application']->fresh()->status);

        $noShow = $this->youthWithPassedM3();
        $this->storeAndConfirmSecond($noShow);
        $this->storeAndCompleteOral($noShow, false);
        $this->actingAs($noShow['chairman']->user)
            ->post(route('evaluation.store', $noShow['application']), $this->finalPayload(['criterion_10' => 2]))
            ->assertSessionHasNoErrors();
        $noShowApp = $noShow['application']->fresh();
        $this->assertSame('submitted', $noShowApp->status);
        $this->assertNotSame('rejected', $noShowApp->status);
        $this->assertNull($noShowApp->final_score);
        $noShowScore = EvaluationScore::query()
            ->where('application_id', $noShow['application']->id)
            ->where('commission_member_id', $noShow['chairman']->id)
            ->first();
        $this->assertSame(2, (int) $noShowScore->criterion_10);
        $this->assertNotSame(0, (int) $noShowScore->criterion_10);
    }

    public function test_youth_labels_scale_and_no_zensko_bonus_markup(): void
    {
        $ctx = $this->youthWithPassedM3();
        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(ScoringProfileConfig::youthCriteria()[6], $html);
        $this->assertStringContainsString('.......................', $html);
        $this->assertStringContainsString(ScoringProfileConfig::youthCriteria()[10], $html);
        $this->assertStringContainsString('preduzetnik je uvjerljivi siguran', $html);
        $this->assertStringContainsString('Sačuvaj nacrt', $html);
        $this->assertStringContainsString('Završi ocjenjivanje', $html);
        $this->assertStringContainsString(ScoringProfileConfig::SCALE_MIN_LABEL, $html);
        $this->assertStringNotContainsString('>Ocijeni<', $html);
        $this->assertStringNotContainsString('Podaci o preduzetnici', $html);
        $this->assertStringNotContainsString('preduzetnica je uvjerljiva', $html);
        $this->assertStringNotContainsString('Zavoda za zapošljavanje', $html);
        $this->assertStringNotContainsString('bonus_zavod_nezaposleni', $html);
        $this->assertStringNotContainsString('članu 10', $html);
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, CompetitionProgramCatalog::definitions()['omladinsko']['status']);
    }

    public function test_seats_one_two_and_three_lock_independently_and_third_sets_evaluated(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->assertNotSame($ctx['members'][0]->position, 'clan');

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 2]))
            ->assertSessionHasNoErrors();
        $afterOne = $ctx['application']->fresh();
        $this->assertSame('submitted', $afterOne->status);
        $this->assertNull($afterOne->evaluated_at);
        $this->assertNull($afterOne->final_score);
        $this->assertNull($afterOne->ranking_position);

        $this->actingAs($ctx['members'][2]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 3]))
            ->assertSessionHasNoErrors();
        $afterTwo = $ctx['application']->fresh();
        $this->assertSame('submitted', $afterTwo->status);
        $this->assertNull($afterTwo->evaluated_at);
        $this->assertNull($afterTwo->ranking_position);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 4]))
            ->assertSessionHasNoErrors();

        $afterThree = $ctx['application']->fresh();
        $this->assertSame('evaluated', $afterThree->status);
        $this->assertNotNull($afterThree->evaluated_at);
        $this->assertNull($afterThree->final_score);
        $this->assertNull($afterThree->ranking_position);
        $this->assertNotSame('approved', $afterThree->status);
        $this->assertNotSame('rejected', $afterThree->status);
        $this->assertFalse($afterThree->competition->isRankingFormed());
        $this->assertSame(3, EvaluationScore::query()->where('application_id', $afterThree->id)->whereNotNull('completed_at')->count());
        $this->assertEqualsCanonicalizing(
            [1, 2, 3],
            EvaluationScore::query()->where('application_id', $afterThree->id)->whereNotNull('completed_at')->pluck('canonical_seat_no')->all()
        );
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $afterThree->id)->whereIn('canonical_seat_no', [4, 5])->count());
        foreach (EvaluationScore::query()->where('application_id', $afterThree->id)->get() as $score) {
            $this->assertNull($score->final_score);
        }
    }

    public function test_unauthorized_actors_and_dual_membership_isolation(): void
    {
        $ctx = $this->youthReadyToLock();
        $payload = $this->finalPayload();

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('evaluation.store', $ctx['application']), $payload)
            ->assertForbidden();

        $beforeAdmin = $ctx['application']->fresh();
        $konkursAdmin = $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('evaluation.store', $ctx['application']), $payload);
        $konkursAdmin->assertRedirect(route('admin.dashboard'));
        $this->assertNotSame(route('evaluation.index'), $konkursAdmin->headers->get('Location'));
        $this->assertNotSame(route('evaluation.index', ['filter' => 'evaluated']), $konkursAdmin->headers->get('Location'));
        $this->assertSame(
            0,
            EvaluationScore::query()->where('application_id', $ctx['application']->id)->count()
        );
        $afterAdmin = $ctx['application']->fresh();
        $this->assertSame('submitted', $afterAdmin->status);
        $this->assertNull($afterAdmin->evaluated_at);
        $this->assertNull($afterAdmin->final_score);
        $this->assertNull($afterAdmin->ranking_position);
        $this->assertSame($beforeAdmin->status, $afterAdmin->status);
        $this->assertSame($beforeAdmin->evaluated_at?->toDateTimeString(), $afterAdmin->evaluated_at?->toDateTimeString());
        $this->assertSame($beforeAdmin->final_score, $afterAdmin->final_score);
        $this->assertSame($beforeAdmin->ranking_position, $afterAdmin->ranking_position);

        $other = $this->youthReadyToLock();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $payload)
            ->assertForbidden();

        $inactive = $this->youthReadyToLock();
        $inactive['members'][1]->update(['status' => 'inactive']);
        $this->actingAs($inactive['members'][1]->fresh('user')->user)
            ->post(route('evaluation.store', $inactive['application']), $payload)
            ->assertForbidden();

        $youth = $this->youthReadyToLock();
        $zensko = $this->zenskoReadyToScore();
        $sharedUser = $youth['members'][1]->user;
        $zenskoSeatTwo = $zensko['members']->firstWhere('canonical_seat_no', 2)
            ?? $zensko['members']->values()[1];
        $zenskoSeatTwo->update(['user_id' => $sharedUser->id, 'name' => $sharedUser->name]);

        $this->actingAs($sharedUser)
            ->post(route('evaluation.store', $youth['application']), $this->finalPayload(['criterion_1' => 5]))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, EvaluationScore::query()->where('application_id', $youth['application']->id)->count());
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $zensko['application']->id)->count());

        $this->actingAs($sharedUser)
            ->post(route('evaluation.store', $zensko['application']), $this->finalPayload(['criterion_1' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, EvaluationScore::query()->where('application_id', $youth['application']->id)->count());
        $this->assertSame(1, EvaluationScore::query()->where('application_id', $zensko['application']->id)->count());
        $youthScore = EvaluationScore::query()->where('application_id', $youth['application']->id)->first();
        $zenskoScore = EvaluationScore::query()->where('application_id', $zensko['application']->id)->first();
        $this->assertSame(5, (int) $youthScore->criterion_1);
        $this->assertSame(1, (int) $zenskoScore->criterion_1);
        $this->assertNotSame($youthScore->commission_member_id, $zenskoScore->commission_member_id);
    }

    public function test_locked_score_cannot_change_and_others_remain_hidden(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 5, 'notes' => 'Zaključano']))
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 1, 'notes' => 'Izmjena']))
            ->assertForbidden();
        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertSame(5, (int) $score->criterion_1);
        $this->assertSame('Zaključano', $score->notes);

        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 2]))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Individualna ocjena je zaključana', $html);
        $this->assertStringNotContainsString('name="criterion_1"', $html);

        $otherHtml = $this->actingAs($ctx['members'][2]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Zaključano', $otherHtml);
        $this->assertStringNotContainsString('>5</span>', $otherHtml);
    }

    public function test_final_requires_all_ten_values_and_zensko_markup_stays(): void
    {
        $ctx = $this->youthReadyToLock();
        $incomplete = $this->finalPayload();
        unset($incomplete['criterion_10']);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $incomplete)
            ->assertSessionHasErrors('criterion_10');

        $zensko = $this->zenskoReadyToScore();
        $html = $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.create', $zensko['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString(ScoringProfileConfig::zenskoCriteria()[6], $html);
        $this->assertStringContainsString('Podaci o preduzetnici', $html);
        $this->assertStringContainsString('Zavoda za zapošljavanje', $html);
        $this->assertStringContainsString('>Ocijeni<', $html);
        $this->assertStringNotContainsString('.......................', $html);
        $this->assertStringNotContainsString('Sačuvaj nacrt', $html);
        $this->assertStringNotContainsString('Završi ocjenjivanje', $html);
    }

    public function test_youth_scoring_ui_stays_open_after_forty_five_days(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->markEvaluationDeadlinePassed($ctx['competition']);
        $this->assertFalse(ScoringProfileConfig::for('omladinsko')->appliesEvaluationDeadline);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Rok za ocjenjivanje i donošenje odluke je istekao', $html);
        $this->assertStringNotContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs', $html);
        $this->assertStringNotContainsString('onsubmit="event.preventDefault(); return false;"', $html);
        $this->assertStringContainsString('Sačuvaj nacrt', $html);
        $this->assertStringContainsString('Završi ocjenjivanje', $html);
        $this->assertStringNotContainsString('name="save_as_draft" value="1" class="btn-primary" disabled', $html);
        $this->assertStringNotContainsString('class="btn-primary" disabled style="opacity: 0.5; cursor: not-allowed; margin-left: 12px;">Završi ocjenjivanje', $html);
    }

    public function test_youth_post_after_forty_five_days_succeeds(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->markEvaluationDeadlinePassed($ctx['competition']);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 4]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($score?->completed_at);
        $this->assertSame(4, (int) $score->criterion_1);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
    }

    public function test_zensko_scoring_remains_blocked_after_forty_five_days(): void
    {
        $zensko = $this->zenskoReadyToScore();
        $this->markEvaluationDeadlinePassed($zensko['competition']);
        $this->assertTrue(ScoringProfileConfig::for('zensko')->appliesEvaluationDeadline);

        $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.create', $zensko['application']))
            ->assertForbidden();

        $this->actingAs($zensko['chairman']->user)
            ->from(route('evaluation.index'))
            ->post(route('evaluation.store', $zensko['application']), $this->finalPayload())
            ->assertRedirect(route('evaluation.index'))
            ->assertSessionHasErrors('error');
        $this->assertStringContainsString(
            'Rok za ocjenjivanje je istekao',
            session('errors')->first('error')
        );
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $zensko['application']->id)->count());
    }

    private function markEvaluationDeadlinePassed(Competition $competition): void
    {
        $competition->update(['closed_at' => now()->subDays(46)]);
        $this->assertTrue($competition->fresh()->isEvaluationDeadlinePassed());
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthReadyToLock(): array
    {
        $ctx = $this->youthWithPassedM3();
        $this->storeAndConfirmSecond($ctx);
        $this->storeAndCompleteOral($ctx, true);

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthWithPassedM3(): array
    {
        $ctx = $this->makeYouthReadyContext();
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
    private function makeYouthReadyContext(): array
    {
        $ctx = $this->makeYouthContext();
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
    private function makeYouthContext(): array
    {
        $year = $this->yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi scoring '.$year,
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
            'title' => 'Omladinsko scoring '.$year,
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
            'competition_number' => 'UP-S-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application,
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: \Illuminate\Support\Collection<int, CommissionMember>, application: Application}
     */
    private function zenskoReadyToScore(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska scoring '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
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
            'title' => 'Zensko scoring '.uniqid(),
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
            'business_plan_name' => 'Ženski plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $chairman = $members[0]->fresh('user');
        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $chairman,
            'members' => $members,
            'application' => $application->fresh(),
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
     * @param  array{competition: Competition, chairman: CommissionMember, application: Application}  $ctx
     */
    private function storeAndCompleteOral(array $ctx, bool $attended): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
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
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $payload)
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function draftPayload(array $overrides = []): array
    {
        $payload = ['save_as_draft' => '1', 'notes' => 'Nacrt'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 3;
        }

        return array_merge($payload, $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function finalPayload(array $overrides = []): array
    {
        $payload = ['scoring_confirmed' => '1', 'notes' => null];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 3;
        }

        return array_merge($payload, $overrides);
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
