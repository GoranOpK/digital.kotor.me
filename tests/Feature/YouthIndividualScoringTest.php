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
use App\Services\CanonicalIndividualScoringService;
use App\Services\YouthSecondSessionGate;
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

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_m3_pass_opens_scoring_while_sibling_appeal_window_is_open(): void
    {
        $ctx = $this->youthWithPassedM3();
        $failed = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $failed), $this->youthFailPayload())
            ->assertRedirect();

        $gate = app(YouthSecondSessionGate::class);
        $this->assertTrue($gate->appealWindowIsOpen($failed->fresh()));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
        $this->assertFalse($gate->oralPresentationIsAllowed($ctx['application']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->assertDontSee(ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE, false);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->draftPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.create', $ctx['application']));
        $this->assertNotNull(
            EvaluationScore::query()
                ->where('application_id', $ctx['application']->id)
                ->whereNull('completed_at')
                ->first()
        );
    }

    public function test_unresolved_sibling_prigovor_does_not_block_draft_but_blocks_oral(): void
    {
        $ctx = $this->youthWithPassedM3();
        $failed = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $failed), $this->youthFailPayload())
            ->assertRedirect();
        $this->actingAs($failed->user)->post(route('applications.prigovor.store', $failed), [
            'contested' => [1, 2],
            'criterion_obrazlozenja' => [1 => 'Obrazloženje 1.', 2 => 'Obrazloženje 2.'],
        ])->assertRedirect();
        $failed->fresh()->eliminatoryNotice->update(['sent_at' => now()->subDays(4)]);
        $this->assertTrue($failed->fresh()->prigovor->isPodnesen());

        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
        $this->assertFalse(app(YouthSecondSessionGate::class)->oralPresentationIsAllowed($ctx['application']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->draftPayload())
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertSame(0, \App\Models\ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
    }

    public function test_final_score_is_allowed_without_oral_record(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->assertSame(0, \App\Models\ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->draftPayload())
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_10' => 4]))
            ->assertSessionHasNoErrors();
        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($score->completed_at);
        $this->assertSame(4, (int) $score->criterion_10);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertSame(0, \App\Models\ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
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

    public function test_chairman_partial_draft_from_real_form_persists_on_reload(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
        $this->assertSame(0, \App\Models\ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $payload = $this->youthDraftPayloadFromRenderedForm($html, ['criterion_1' => '4']);
        $this->assertSame('1', $payload['save_as_draft']);
        $this->assertSame('4', $payload['criterion_1']);
        $this->assertArrayNotHasKey('criterion_2', $payload);
        $this->assertArrayNotHasKey('youth_bonus_action', $payload);
        $this->assertSame('0', $payload['scoring_confirmed'] ?? '0');

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Nacrt ocjene je sačuvan.')
            ->assertRedirect(route('evaluation.create', $ctx['application']));

        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($score);
        $this->assertNull($score->completed_at);
        $this->assertNull($score->canonical_seat_no);
        $this->assertNull($score->final_score);
        $this->assertSame(4, (int) $score->criterion_1);
        $this->assertNull($score->criterion_2);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertNull($ctx['application']->fresh()->final_score);
        $this->assertNull($ctx['application']->fresh()->evaluated_at);

        $reload = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk();
        $reloadHtml = $reload->getContent();
        $this->assertStringContainsString('Nacrt ocjene je sačuvan.', $reloadHtml);
        $this->assertMatchesRegularExpression(
            '/<input\b[^>]*\bname="criterion_1"[^>]*\bvalue="4"/',
            $reloadHtml
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="youth-finish-scoring"[^>]*disabled/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="youth-finish-scoring"[^>]*disabled/',
            $reloadHtml
        );
        $this->assertStringNotContainsString('završene evidencije usmenog', $html);
        $this->assertStringNotContainsString('Evidencija usmenog', $html);
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

    public function test_final_is_allowed_after_m3_without_second_session_or_oral_record(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->assertSame(0, \App\Models\ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_10' => 5]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));
        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($score?->completed_at);
        $this->assertSame(5, (int) $score->criterion_10);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertSame(0, \App\Models\ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
    }

    public function test_criterion_ten_stays_required_and_is_not_auto_scored(): void
    {
        $ctx = $this->youthWithPassedM3();
        $incomplete = $this->finalPayload();
        unset($incomplete['criterion_10']);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $incomplete)
            ->assertSessionHasErrors('criterion_10');
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ctx['application']->id)->whereNotNull('completed_at')->count());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_10' => 2]))
            ->assertSessionHasNoErrors();
        $score = EvaluationScore::query()
            ->where('application_id', $ctx['application']->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertSame(2, (int) $score->criterion_10);
        $this->assertNotSame(0, (int) $score->criterion_10);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertNotSame('rejected', $ctx['application']->fresh()->status);
    }

    public function test_youth_labels_scale_and_no_zensko_bonus_markup(): void
    {
        $ctx = $this->youthWithPassedM3();
        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(ScoringProfileConfig::youthCriteria()[6], $html);
        $this->assertStringContainsString('Biznis ideja je finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja).', $html);
        $this->assertStringNotContainsString('.......................', $html);
        $this->assertStringContainsString(ScoringProfileConfig::youthCriteria()[10], $html);
        $this->assertStringContainsString('preduzetnik je uvjerljivi siguran', $html);
        $this->assertStringContainsString('Sačuvaj nacrt', $html);
        $this->assertStringContainsString('Završi ocjenjivanje', $html);
        $this->assertStringContainsString(ScoringProfileConfig::SCALE_MIN_LABEL, $html);
        $this->assertStringNotContainsString('>Ocijeni<', $html);
        $this->assertStringNotContainsString('Podaci o podnositeljki prijave', $html);
        $this->assertStringNotContainsString('preduzetnica je uvjerljiva', $html);
        $this->assertStringNotContainsString('Zavoda za zapošljavanje', $html);
        $this->assertStringNotContainsString('bonus_zavod_nezaposleni', $html);
        $this->assertStringNotContainsString('članu 10', $html);
        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, CompetitionProgramCatalog::definitions()['omladinsko']['status']);
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
        $this->assertStringContainsString('Podaci o podnositeljki prijave', $html);
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
        $this->assertStringNotContainsString('45 dana', $html);
        $this->assertStringNotContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs', $html);
        $this->assertStringNotContainsString('onsubmit="event.preventDefault(); return false;"', $html);
        $this->assertStringContainsString('Sačuvaj nacrt', $html);
        $this->assertStringContainsString('Završi ocjenjivanje', $html);
        $this->assertStringNotContainsString('name="save_as_draft" value="1" class="btn-primary" disabled', $html);
        $this->assertStringNotContainsString('class="btn-primary" disabled style="opacity: 0.5; cursor: not-allowed; margin-left: 12px;">Završi ocjenjivanje', $html);
    }

    public function test_youth_does_not_claim_forty_five_days_before_applications_close(): void
    {
        $ctx = $this->youthReadyToLock();
        $competition = $this->reopenApplicationWindow($ctx['competition']);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ctx['application']->id)->count());

        $response = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']));
        $response->assertForbidden();
        $this->assertSame(
            ScoringProfileConfig::YOUTH_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE,
            $response->exception?->getMessage()
        );
        $this->assertStringNotContainsString('45 dana', (string) $response->exception?->getMessage());

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.index'))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(
            ScoringProfileConfig::YOUTH_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE,
            session('errors')->first('error')
        );
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ctx['application']->id)->count());

        $this->travelTo($competition->deadline->copy()->addMinute());
        $this->assertTrue($ctx['competition']->fresh()->isApplicationDeadlinePassed());

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('45 dana', $html);
        $this->assertStringNotContainsString('Rok za ocjenjivanje i donošenje odluke je istekao', $html);
        $this->assertStringContainsString('Sačuvaj nacrt', $html);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ctx['application']->id)->count());
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

    public function test_completed_youth_member_sees_own_ten_criteria_sum(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 5]))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="youth-member-criteria-sum"', $html);
        $this->assertStringContainsString('Zbir vaših ocjena za 10 kriterijuma', $html);
        $this->assertStringContainsString('32 / 50', $html);
        $this->assertStringNotContainsString('Zbir trenutno sačuvanih ocjena', $html);
        $this->assertStringNotContainsString('Konačna ocjena Komisije', $html);
        $this->assertStringNotContainsString('Sačuvaj izmjene', $html);
        $this->assertStringNotContainsString('Otkaži', $html);
    }

    public function test_incomplete_youth_score_is_labeled_as_saved_sum_not_final(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), [
                'save_as_draft' => '1',
                'criterion_1' => 5,
                'notes' => 'Nepotpun nacrt',
            ])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="youth-member-criteria-sum"', $html);
        $this->assertStringContainsString('Zbir trenutno sačuvanih ocjena', $html);
        $this->assertStringContainsString('5 / 50', $html);
        $this->assertStringNotContainsString('Zbir vaših ocjena za 10 kriterijuma', $html);
        $this->assertStringNotContainsString('Konačna ocjena Komisije', $html);
        $this->assertStringContainsString('Sačuvaj nacrt', $html);
        $this->assertStringContainsString('Otkaži', $html);
    }

    public function test_youth_commission_final_score_appears_only_after_cycle_and_confirmed_bonuses(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx);

        $beforeBonuses = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Zbir vaših ocjena za 10 kriterijuma', $beforeBonuses);
        $this->assertStringContainsString('30 / 50', $beforeBonuses);
        $this->assertStringNotContainsString('Konačna ocjena Komisije', $beforeBonuses);
        $this->assertStringNotContainsString(' / 56', $beforeBonuses);
        $this->assertStringNotContainsString('Sačuvaj izmjene', $beforeBonuses);
        $this->assertStringNotContainsString('Otkaži', $beforeBonuses);

        $this->confirmYouthBonuses($ctx);

        $afterBonuses = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('id="youth-commission-final-score"', $afterBonuses);
        $this->assertStringContainsString('Konačna ocjena Komisije: 30.00 / 56', $afterBonuses);
        $this->assertStringContainsString('Zbir vaših ocjena za 10 kriterijuma', $afterBonuses);
        $this->assertStringContainsString('30 / 50', $afterBonuses);
        $this->assertStringNotContainsString('Sačuvaj izmjene', $afterBonuses);
        $this->assertStringNotContainsString('Otkaži', $afterBonuses);
    }

    public function test_zensko_completed_form_keeps_save_cancel_and_does_not_show_youth_totals(): void
    {
        $zensko = $this->zenskoReadyToScore();
        $this->actingAs($zensko['chairman']->user)
            ->post(route('evaluation.store', $zensko['application']), $this->finalPayload(['criterion_1' => 5]))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.create', $zensko['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sačuvaj izmjene', $html);
        $this->assertStringContainsString('Otkaži', $html);
        $this->assertStringContainsString('KONAČNA OCJENA', $html);
        $this->assertStringNotContainsString('id="youth-member-criteria-sum"', $html);
        $this->assertStringNotContainsString('id="youth-commission-final-score"', $html);
        $this->assertStringNotContainsString('Zbir vaših ocjena za 10 kriterijuma', $html);
        $this->assertStringNotContainsString('Zbir trenutno sačuvanih ocjena', $html);
        $this->assertStringNotContainsString('Konačna ocjena Komisije', $html);
        $this->assertStringNotContainsString('32 / 50', $html);
        $this->assertStringContainsString('Podaci o podnositeljki prijave', $html);
        $this->assertStringContainsString('Zavoda za zapošljavanje', $html);
        $this->assertStringContainsString('Potvrdio:', $html);
        $this->assertStringContainsString('Datum i vrijeme:', $html);
        $this->assertStringNotContainsString('>Štampaj</button>', $html);
        $this->assertStringNotContainsString('Dugme Štampaj otvara prozor za štampu pregledača', $html);
        $this->assertStringNotContainsString('id="youth-bonus-locked-banner"', $html);

        $show = $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.show', $zensko['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Potvrdio:', $show);
        $this->assertStringContainsString('Datum i vrijeme:', $show);
        $this->assertStringContainsString('Ispunjava eliminatorne kriterijume', $show);
        $this->assertStringContainsString('>Štampaj</button>', $show);
        $this->assertStringNotContainsString('Dugme Štampaj otvara prozor za štampu pregledača', $show);
        $this->assertStringNotContainsString('id="evaluation-print-hint"', $show);
    }

    public function test_locked_youth_bonuses_hide_confirmer_name_and_timestamp(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx);
        $this->confirmYouthBonuses($ctx);

        $locked = $ctx['application']->fresh();
        $this->assertNotNull($locked->bonuses_confirmed_at);
        $this->assertSame($ctx['chairman']->name, $locked->bonuses_confirmed_by_name);
        $this->assertSame($ctx['chairman']->user_id, (int) $locked->bonuses_confirmed_by_user_id);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Dodatni bodovi su zaključani.', $html);
        $this->assertMatchesRegularExpression(
            '/id="youth-bonus-locked-banner"[^>]*>\s*Dodatni bodovi su zaključani\.\s*<\/div>/u',
            $html
        );
        preg_match('/id="youth-bonus-locked-banner".*?<\/div>/s', $html, $banner);
        $this->assertNotEmpty($banner);
        $this->assertStringNotContainsString('Potvrdio:', $banner[0]);
        $this->assertStringNotContainsString('Datum i vrijeme:', $banner[0]);
        $this->assertStringNotContainsString($ctx['chairman']->name, $banner[0]);
        $this->assertStringNotContainsString($locked->bonuses_confirmed_at->format('d.m.Y. H:i'), $banner[0]);

        $eliminatory = $locked->eliminatoryCheck;
        $this->assertNotNull($eliminatory);
        $this->assertNotNull($eliminatory->confirmed_at);
        $this->assertSame($ctx['chairman']->name, $eliminatory->confirmed_by_name);
        $this->assertStringContainsString('Ispunjava eliminatorne kriterijume', $html);
        $this->assertStringNotContainsString('Potvrdio:', $html);
        $this->assertStringNotContainsString('Datum i vrijeme:', $html);
        $this->assertStringNotContainsString($eliminatory->confirmed_at->format('d.m.Y. H:i'), $html);

        $show = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.show', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Ispunjava eliminatorne kriterijume', $show);
        $this->assertStringNotContainsString('Potvrdio:', $show);
        $this->assertStringNotContainsString('Datum i vrijeme:', $show);
        $this->assertStringNotContainsString($eliminatory->confirmed_at->format('d.m.Y. H:i'), $show);
    }

    public function test_youth_print_is_available_after_personal_completion_before_cycle(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), [
                'save_as_draft' => '1',
                'criterion_1' => 5,
                'notes' => 'Nacrt',
            ])
            ->assertSessionHasNoErrors();

        $draftCreate = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('>Štampaj</button>', $draftCreate);
        $this->assertStringNotContainsString('id="evaluation-print-hint"', $draftCreate);

        $draftShow = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.show', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('>Štampaj</button>', $draftShow);
        $this->assertStringNotContainsString('id="evaluation-print-hint"', $draftShow);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 5]))
            ->assertSessionHasNoErrors();

        $personalCreate = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertYouthPrintHintBesideButton($personalCreate);
        $this->assertYouthOthersRemainHiddenBeforeCycle($personalCreate);

        $personalShow = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.show', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertYouthPrintHintBesideButton($personalShow);
        $this->assertYouthOthersRemainHiddenBeforeCycle($personalShow);

        $otherCreate = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('>Štampaj</button>', $otherCreate);
        $this->assertStringNotContainsString('id="evaluation-print-hint"', $otherCreate);
        $this->assertStringNotContainsString('>5</span>', $otherCreate);
        $this->assertStringNotContainsString('Konačna ocjena Komisije', $otherCreate);
    }

    private function assertYouthPrintHintBesideButton(string $html): void
    {
        $this->assertMatchesRegularExpression(
            '/>Štampaj<\/button>\s*<span\b[^>]*\bid="evaluation-print-hint"/',
            $html
        );
        $this->assertStringContainsString(
            'Dugme Štampaj otvara prozor za štampu pregledača. U njemu možete izabrati štampač ili opciju Sačuvaj kao PDF.',
            $html
        );
    }

    private function assertYouthOthersRemainHiddenBeforeCycle(string $html): void
    {
        $this->assertStringContainsString(
            CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE,
            $html
        );
        $this->assertStringNotContainsString('Konačna ocjena Komisije', $html);
        $this->assertStringNotContainsString('id="youth-commission-final-score"', $html);
    }

    private function markEvaluationDeadlinePassed(Competition $competition): void
    {
        $competition->update(['closed_at' => now()->subDays(46)]);
        $this->assertTrue($competition->fresh()->isEvaluationDeadlinePassed());
    }

    private function reopenApplicationWindow(Competition $competition): Competition
    {
        $competition->update([
            'start_date' => now()->toDateString(),
            'deadline_days' => 20,
            'published_at' => now(),
            'status' => 'published',
        ]);

        $fresh = $competition->fresh();
        $this->assertNotNull($fresh->deadline);
        $this->assertTrue($fresh->deadline->isFuture());
        $this->assertFalse($fresh->isApplicationDeadlinePassed());

        return $fresh;
    }

    /**
     * @param  array{application: Application, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     * @param  array<int, array<string, int>>  $bySeat
     */
    private function lockThreeSeats(array $ctx, array $bySeat = []): void
    {
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $this->finalPayload($bySeat[$seat] ?? []))
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * @param  array{application: Application, chairman: CommissionMember}  $ctx
     * @param  array<string, string>  $flags
     */
    private function confirmYouthBonuses(array $ctx, array $flags = []): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), array_merge([
                'youth_bonus_action' => 'confirm',
            ], $flags))
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthReadyToLock(): array
    {
        return $this->youthWithPassedM3();
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
        return $this->makeYouthContext();
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

    private function addSubmittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Drugi plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function youthFailPayload(): array
    {
        return [
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'criterion_notes' => [
                1 => 'Nedostaje izvod',
                2 => 'Nema M4',
                3 => '',
            ],
            'confirmation_acknowledged' => '1',
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
     * Build a browser-like POST from the rendered chairman form, using the real
     * "Sačuvaj nacrt" button associated with evaluationForm.
     *
     * @param  array<string, string>  $criteria
     * @return array<string, string>
     */
    private function youthDraftPayloadFromRenderedForm(string $html, array $criteria): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $form = $xpath->query('//form[@id="evaluationForm"]')->item(0);
        $this->assertNotNull($form, 'Forma evaluationForm mora postojati.');

        $draftButton = $xpath->query('.//*[@id="youth-save-score-draft"]', $form)->item(0)
            ?? $xpath->query('//*[@id="youth-save-score-draft"][@form="evaluationForm"]')->item(0);
        $this->assertNotNull($draftButton, 'Dugme Sačuvaj nacrt mora biti povezano sa evaluationForm.');
        $this->assertSame('submit', $draftButton->getAttribute('type'));
        $this->assertSame('save_as_draft', $draftButton->getAttribute('name'));
        $this->assertSame('1', $draftButton->getAttribute('value'));
        $this->assertSame('evaluationForm', $draftButton->getAttribute('form'));
        $this->assertFalse($draftButton->hasAttribute('disabled'));

        $payload = [];
        foreach ($xpath->query('//input|//textarea|//select') as $control) {
            if (! $control instanceof \DOMElement) {
                continue;
            }
            if (! $this->htmlControlBelongsToEvaluationForm($control, $form)) {
                continue;
            }
            if ($control->hasAttribute('disabled')) {
                continue;
            }

            $nodeName = strtolower($control->nodeName);
            $type = strtolower($control->getAttribute('type') ?: $nodeName);
            $name = $control->getAttribute('name');
            if ($name === '') {
                continue;
            }
            if (in_array($type, ['submit', 'button', 'image', 'reset'], true)) {
                continue;
            }
            if (in_array($type, ['checkbox', 'radio'], true) && ! $control->hasAttribute('checked')) {
                continue;
            }

            $payload[$name] = $nodeName === 'textarea'
                ? $control->textContent
                : $control->getAttribute('value');
        }

        foreach (array_keys($payload) as $name) {
            if (preg_match('/^criterion_\d+$/', $name) && ($payload[$name] === '' || $payload[$name] === null)) {
                unset($payload[$name]);
            }
        }

        foreach (array_keys($criteria) as $key) {
            $criterionInput = $xpath->query('//input[@name="'.$key.'"]')->item(0);
            $this->assertNotNull($criterionInput, 'Polje '.$key.' mora postojati na formi.');
            $this->assertInstanceOf(\DOMElement::class, $criterionInput);
            $this->assertTrue(
                $this->htmlControlBelongsToEvaluationForm($criterionInput, $form),
                $key.' mora pripadati evaluationForm.'
            );
            $this->assertFalse($criterionInput->hasAttribute('disabled'));
        }

        $payload['save_as_draft'] = '1';
        unset($payload['youth_bonus_action']);
        foreach ($criteria as $name => $value) {
            $payload[$name] = $value;
        }

        return $payload;
    }

    private function htmlControlBelongsToEvaluationForm(\DOMElement $control, \DOMElement $form): bool
    {
        if ($control->getAttribute('form') === 'evaluationForm') {
            return true;
        }
        if ($control->hasAttribute('form')) {
            return false;
        }

        $ancestor = $control->parentNode;
        while ($ancestor instanceof \DOMElement) {
            if ($ancestor->isSameNode($form)) {
                return true;
            }
            $ancestor = $ancestor->parentNode;
        }

        return false;
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
