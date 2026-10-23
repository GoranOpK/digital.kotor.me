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
use App\Services\CanonicalIndividualScoringService;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use App\Support\ScoringProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class YouthAggregateAndBonusTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_three_seats_average_divides_by_three_without_waiting_for_seats_four_and_five(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->lockThreeSeats($ctx, [
            1 => ['criterion_1' => 2],
            2 => ['criterion_1' => 3],
            3 => ['criterion_1' => 4],
        ]);
        $this->confirmYouthBonuses($ctx);

        $application = $ctx['application']->fresh();
        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateYouthApplication($application);

        $this->assertNotNull($aggregate);
        $this->assertSame('3.0000000000', $aggregate['criterion_averages'][1]);
        $this->assertSame('30.0000000000', $aggregate['base_score']);
        $this->assertSame(0, $aggregate['bonus']);
        $this->assertSame('30.0000000000', $aggregate['final_score_full']);
        $this->assertSame('30.00', $application->final_score);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $application->id)->whereIn('canonical_seat_no', [4, 5])->count());
        $this->assertTrue($application->meetsMinimumScore());
        $this->assertSame('evaluated', $application->status);
        $this->assertSame(1, (int) $application->ranking_position);
        $this->assertNotSame('approved', $application->status);
        $this->assertNotSame('rejected', $application->status);
        $this->assertTrue($application->competition->fresh()->isRankingFormed());
    }

    public function test_youth_bonus_table_shows_four_fields_without_zavod(): void
    {
        $ctx = $this->youthReadyToLock();
        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<form\b[^>]*\bid="evaluationForm"/', $html);
        $this->assertTrue((bool) preg_match('/<form\b[^>]*\bid="evaluationForm"[^>]*>(.*)<\/form>/s', $html, $formMatch));
        $mainForm = $formMatch[1];
        $this->assertStringNotContainsString('<form', $mainForm);
        $this->assertSame(1, substr_count($mainForm, 'id="youth-scoring-list"'));
        $this->assertStringNotContainsString('evaluation-table-bonus', $mainForm);
        $list = $this->youthScoringListHtml($mainForm);
        foreach (ScoringProfileConfig::youthCriteria() as $criterionName) {
            $this->assertStringContainsString($criterionName, $list);
        }
        $this->assertStringContainsString('Info dan i obuka (+1)', $list);
        $this->assertSame(1, substr_count($list, 'Info dan i obuka (+1)'));
        $this->assertStringNotContainsString('>Info dan</td>', $list);
        $this->assertStringNotContainsString('>Obuka</td>', $list);
        $this->assertStringContainsString('Planirana registracija', $list);
        $this->assertStringContainsString('Inovativno/zeleno poslovanje', $list);
        $this->assertStringContainsString('UKUPAN ZBIR (maksimum 6)', $list);
        $this->assertStringContainsString('KONAČNA OCJENA', $list);
        $this->assertStringContainsString('Član 1', $list);
        $this->assertStringContainsString('name="bonus_info_and_training"', $list);
        $this->assertSame(1, substr_count($list, 'type="checkbox" name="bonus_info_and_training"'));
        $this->assertStringNotContainsString('name="bonus_info_day"', $list);
        $this->assertStringNotContainsString('name="bonus_training"', $list);
        $this->assertStringContainsString('name="bonus_new_business"', $list);
        $this->assertStringContainsString('name="bonus_green_innovative"', $list);
        $this->assertStringContainsString('Sačuvaj ocjene', $mainForm);
        $this->assertSame(1, substr_count($mainForm, '>Sačuvaj ocjene<'));
        $this->assertStringNotContainsString('name="youth_bonus_action"', $mainForm);
        $this->assertStringNotContainsString('Sačuvaj nacrt bonusa', $mainForm);
        $this->assertStringNotContainsString('Potvrdi i zaključaj bonuse', $mainForm);
        $this->assertStringNotContainsString('Predsjednik evidentira činjenice u ime Komisije na osnovu evidencije organizatora', $mainForm);
        $this->assertStringNotContainsString('>Bodovi</th>', $mainForm);
        $this->assertStringNotContainsString('name="notes"', $mainForm);
        $this->assertStringNotContainsString('Ostale napomene', $mainForm);
        $this->assertStringNotContainsString('name="bonus_zavod_nezaposleni"', $mainForm);
        $this->assertStringNotContainsString('Zavoda za zapošljavanje', $mainForm);
        $this->assertStringNotContainsString('bonus_zavod_nezaposleni', $mainForm);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('id="youth-scoring-list"', $memberHtml);
        $this->assertStringContainsString('Info dan i obuka (+1)', $memberHtml);
        $this->assertStringContainsString('UKUPAN ZBIR (maksimum 6)', $memberHtml);
        $this->assertStringNotContainsString('name="bonus_info_and_training"', $memberHtml);
        $this->assertStringNotContainsString('name="bonus_info_day"', $memberHtml);
        $this->assertStringNotContainsString('name="bonus_training"', $memberHtml);
        $this->assertStringNotContainsString('name="bonus_new_business"', $memberHtml);
        $this->assertStringNotContainsString('name="bonus_green_innovative"', $memberHtml);
        $this->assertStringNotContainsString('name="youth_bonus_action"', $memberHtml);
        $this->assertStringNotContainsString('Sačuvaj nacrt bonusa', $memberHtml);
        $this->assertStringNotContainsString('name="notes"', $memberHtml);
        $this->assertStringNotContainsString('Ostale napomene', $memberHtml);
    }

    public function test_info_day_or_training_alone_is_zero_and_both_are_plus_one(): void
    {
        $legacy = $this->youthReadyToLock();
        $legacy['application']->forceFill([
            'bonus_info_day' => true,
            'bonus_training' => false,
        ])->save();
        $this->assertSame(0, app(CanonicalIndividualScoringService::class)->youthBonusScore($legacy['application']->fresh()));
        $legacyHtml = $this->actingAs($legacy['chairman']->user)
            ->get(route('evaluation.create', $legacy['application']))
            ->assertOk()
            ->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/name="bonus_info_and_training"[^>]*value="1"[^>]*checked/',
            $legacyHtml
        );
        $this->assertStringNotContainsString('name="bonus_info_day"', $legacyHtml);
        $this->assertStringNotContainsString('name="bonus_training"', $legacyHtml);

        $both = $this->youthReadyToLock();
        $this->actingAs($both['chairman']->user)
            ->post(route('evaluation.store', $both['application']), $this->finalPayload([
                'bonus_info_and_training' => '1',
            ]))
            ->assertSessionHasNoErrors();
        $bothApp = $both['application']->fresh();
        $this->assertTrue((bool) $bothApp->bonus_info_day);
        $this->assertTrue((bool) $bothApp->bonus_training);
        $this->assertSame(1, app(CanonicalIndividualScoringService::class)->youthBonusScore($bothApp));
        $this->assertNotNull($bothApp->bonuses_confirmed_at);
        $bothHtml = $this->actingAs($both['chairman']->user)
            ->get(route('evaluation.create', $both['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Info dan i obuka (+1)', $bothHtml);
        $this->assertStringContainsString('✓', $bothHtml);

        $unchecked = $this->youthReadyToLock();
        $this->actingAs($unchecked['chairman']->user)
            ->post(route('evaluation.store', $unchecked['application']), $this->finalPayload([
                'bonus_info_and_training' => '0',
            ]))
            ->assertSessionHasNoErrors();
        $uncheckedApp = $unchecked['application']->fresh();
        $this->assertFalse((bool) $uncheckedApp->bonus_info_day);
        $this->assertFalse((bool) $uncheckedApp->bonus_training);
        $this->assertSame(0, app(CanonicalIndividualScoringService::class)->youthBonusScore($uncheckedApp));
    }

    public function test_planned_registration_bonus_is_plus_two_only_for_eligible_category(): void
    {
        $eligible = $this->youthReadyToLock();
        $this->assertTrue(app(CanonicalIndividualScoringService::class)->youthQualifiesForPlannedRegistrationBonus($eligible['application']));
        $this->actingAs($eligible['chairman']->user)
            ->post(route('evaluation.store', $eligible['application']), $this->finalPayload(['bonus_new_business' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $eligible['application']->fresh()->bonus_new_business);
        $this->assertNotNull($eligible['application']->fresh()->bonuses_confirmed_at);
        $this->assertSame(2, app(CanonicalIndividualScoringService::class)->youthBonusScore($eligible['application']->fresh()));

        $ineligible = $this->youthReadyToLock();
        $ineligible['application']->forceFill([
            'applicant_type' => KnApplicationClassification::FORM_PREDUZETNIK,
            'is_registered' => true,
        ])->save();
        $this->actingAs($ineligible['chairman']->user)
            ->from(route('evaluation.create', $ineligible['application']))
            ->post(route('evaluation.store', $ineligible['application']), $this->finalPayload(['bonus_new_business' => '1']))
            ->assertSessionHasErrors('bonus_new_business');
        $this->assertFalse((bool) $ineligible['application']->fresh()->bonus_new_business);
        $this->assertNull($ineligible['application']->fresh()->bonuses_confirmed_at);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $ineligible['application']->id)->count());
        $this->assertSame(0, app(CanonicalIndividualScoringService::class)->youthBonusScore($ineligible['application']->fresh()));
    }

    public function test_green_is_plus_three_and_all_bonuses_cap_at_six_and_fifty_six(): void
    {
        $ctx = $this->youthReadyToLock();
        $ctx['application']->forceFill([
            'bonus_zavod_nezaposleni' => true,
            'is_registered' => false,
        ])->save();
        $this->lockThreeSeats($ctx, [
            1 => $this->allCriteria(5),
            2 => $this->allCriteria(5),
            3 => $this->allCriteria(5),
        ], [
            'bonus_info_day' => '1',
            'bonus_training' => '1',
            'bonus_new_business' => '1',
            'bonus_green_innovative' => '1',
        ]);

        $application = $ctx['application']->fresh();
        $service = app(CanonicalIndividualScoringService::class);
        $this->assertTrue((bool) $application->bonus_zavod_nezaposleni);
        $this->assertSame(6, $service->youthBonusScore($application));
        $this->assertSame(8, $application->getBonusScore());
        $this->assertSame('56.00', $application->final_score);
        $this->assertSame('56.0000000000', $service->aggregateYouthApplication($application)['final_score_full']);
        $this->assertSame(1, (int) $application->ranking_position);
        $this->assertSame('evaluated', $application->status);
    }

    public function test_draft_confirm_lock_and_unauthorized_actors(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['bonus_green_innovative' => '1']))
            ->assertForbidden();
        $konkursAdmin = $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['bonus_green_innovative' => '1']));
        $konkursAdmin->assertRedirect(route('admin.dashboard'));
        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['bonus_green_innovative' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertFalse((bool) $ctx['application']->fresh()->bonus_green_innovative);
        $this->assertNull($ctx['application']->fresh()->bonuses_confirmed_at);

        $other = $this->youthReadyToLock();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['bonus_green_innovative' => '1']))
            ->assertForbidden();

        $inactive = $this->youthReadyToLock();
        $inactive['chairman']->update(['status' => 'inactive']);
        $this->actingAs($inactive['chairman']->fresh('user')->user)
            ->post(route('evaluation.store', $inactive['application']), $this->finalPayload(['bonus_green_innovative' => '1']))
            ->assertForbidden();
        $this->assertNull($inactive['application']->fresh()->bonuses_confirmed_at);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload([
                'bonus_green_innovative' => '1',
                'bonuses_confirmed_by_name' => 'Lažni auditor',
                'bonuses_confirmed_by_user_id' => 999999,
            ]))
            ->assertSessionHasNoErrors();
        $locked = $ctx['application']->fresh();
        $this->assertTrue((bool) $locked->bonus_green_innovative);
        $this->assertNotNull($locked->bonuses_confirmed_at);
        $this->assertSame($ctx['chairman']->id, (int) $locked->bonuses_confirmed_by_commission_member_id);
        $this->assertSame($ctx['chairman']->user_id, (int) $locked->bonuses_confirmed_by_user_id);
        $this->assertSame($ctx['chairman']->name, $locked->bonuses_confirmed_by_name);
        $this->assertNotSame('Lažni auditor', $locked->bonuses_confirmed_by_name);
        $chairmanScore = EvaluationScore::query()
            ->where('application_id', $locked->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertNotNull($chairmanScore?->completed_at);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload([
                'criterion_1' => 5,
                'bonus_info_day' => '1',
            ]))
            ->assertForbidden();
        $this->assertTrue((bool) $ctx['application']->fresh()->bonus_green_innovative);
        $this->assertFalse((bool) $ctx['application']->fresh()->bonus_info_day);
        $this->assertSame(3, (int) $chairmanScore->fresh()->criterion_1);
    }

    public function test_locked_chairman_score_can_still_lock_bonuses_without_changing_scores(): void
    {
        $ctx = $this->youthReadyToLock();
        foreach ([2, 3] as $seat) {
            $this->actingAs($ctx['members'][$seat - 1]->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $this->finalPayload(['criterion_1' => 4]))
                ->assertSessionHasNoErrors();
        }
        EvaluationScore::query()->create(array_merge($this->allCriteria(3), [
            'application_id' => $ctx['application']->id,
            'commission_member_id' => $ctx['chairman']->id,
            'canonical_seat_no' => 1,
            'completed_at' => now(),
            'notes' => 'Zaključano ranije',
        ]));
        $this->assertNull($ctx['application']->fresh()->bonuses_confirmed_at);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($html, '>Sačuvaj ocjene<'));
        $this->assertStringContainsString('Info dan i obuka (+1)', $html);
        $this->assertStringNotContainsString('Sačuvaj nacrt bonusa', $html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $ctx['application']), [
                'criterion_1' => 5,
                'bonus_green_innovative' => '1',
                'bonuses_confirmed_by_name' => 'Lažni auditor',
            ])
            ->assertSessionHasNoErrors();

        $application = $ctx['application']->fresh();
        $score = EvaluationScore::query()
            ->where('application_id', $application->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $this->assertSame(3, (int) $score->criterion_1);
        $this->assertSame('Zaključano ranije', $score->notes);
        $this->assertTrue((bool) $application->bonus_green_innovative);
        $this->assertNotNull($application->bonuses_confirmed_at);
        $this->assertSame($ctx['chairman']->name, $application->bonuses_confirmed_by_name);
        $this->assertNotSame('Lažni auditor', $application->bonuses_confirmed_by_name);
    }

    public function test_final_score_requires_both_three_scores_and_confirmed_bonuses_in_either_order(): void
    {
        $orderA = $this->youthReadyToLock();
        $this->confirmYouthBonuses($orderA, ['bonus_green_innovative' => '1']);
        $this->assertNull($orderA['application']->fresh()->final_score);
        $this->lockThreeSeats($orderA);
        $afterA = $orderA['application']->fresh();
        $this->assertSame('evaluated', $afterA->status);
        $this->assertNotNull($afterA->evaluated_at);
        $this->assertSame('33.00', $afterA->final_score);
        $this->assertSame(1, (int) $afterA->ranking_position);

        $orderB = $this->youthReadyToLock();
        foreach ([2, 3] as $seat) {
            $this->actingAs($orderB['members'][$seat - 1]->fresh('user')->user)
                ->post(route('evaluation.store', $orderB['application']), $this->finalPayload())
                ->assertSessionHasNoErrors();
        }
        $beforeConfirm = $orderB['application']->fresh();
        $this->assertSame('submitted', $beforeConfirm->status);
        $this->assertNull($beforeConfirm->final_score);
        $this->assertNull($beforeConfirm->ranking_position);
        $this->confirmYouthBonuses($orderB, ['bonus_green_innovative' => '1']);
        $afterB = $orderB['application']->fresh();
        $this->assertNotNull($afterB->evaluated_at);
        $this->assertSame('33.00', $afterB->final_score);
        $this->assertSame('evaluated', $afterB->status);
        $this->assertSame(1, (int) $afterB->ranking_position);
        $this->assertSame($afterA->final_score, $afterB->final_score);
    }

    public function test_threshold_uses_full_precision_and_does_not_change_status(): void
    {
        $pass = $this->youthReadyToLock();
        $this->lockThreeSeats($pass);
        $this->confirmYouthBonuses($pass);
        $this->assertTrue($pass['application']->fresh()->meetsMinimumScore());
        $this->assertSame('30.00', $pass['application']->fresh()->final_score);
        $this->assertSame('evaluated', $pass['application']->fresh()->status);

        $below = $this->youthReadyToLock();
        $this->lockThreeSeats($below, [1 => ['criterion_1' => 2]]);
        $this->confirmYouthBonuses($below);
        $belowApp = $below['application']->fresh();
        $this->assertSame('29.67', $belowApp->final_score);
        $this->assertFalse($belowApp->meetsMinimumScore());
        $this->assertSame('evaluated', $belowApp->status);
        $this->assertNotSame('approved', $belowApp->status);
        $this->assertNotSame('rejected', $belowApp->status);

        $roundedDisplay = $this->youthReadyToLock();
        $this->lockThreeSeats($roundedDisplay, [1 => ['criterion_1' => 2]]);
        $this->confirmYouthBonuses($roundedDisplay);
        $roundedDisplay['application']->fresh()->forceFill(['final_score' => '30.00'])->save();
        $this->assertSame('30.00', $roundedDisplay['application']->fresh()->final_score);
        $this->assertFalse($roundedDisplay['application']->fresh()->meetsMinimumScore());
        $this->assertSame('evaluated', $roundedDisplay['application']->fresh()->status);
    }

    public function test_secrecy_hides_totals_and_other_scores_before_cycle_complete(): void
    {
        $ctx = $this->youthReadyToLock();
        $this->actingAs($ctx['members'][0]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload($this->allCriteria(5) + [
                'notes' => 'Napomena mjesta 1',
                'bonus_info_day' => '1',
                'bonus_training' => '1',
                'bonus_new_business' => '1',
                'bonus_green_innovative' => '1',
            ]))
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload($this->allCriteria(5) + ['notes' => 'Napomena mjesta 2']))
            ->assertSessionHasNoErrors();
        $this->assertNull($ctx['application']->fresh()->final_score);

        $memberHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('56.00', $memberHtml);
        $this->assertStringNotContainsString('Napomena mjesta 1', $memberHtml);
        $this->assertStringNotContainsString('Napomena mjesta 2', $memberHtml);
        $this->assertStringNotContainsString('name="notes"', $memberHtml);
        $this->assertStringNotContainsString('Ostale napomene', $memberHtml);
        $this->assertStringNotContainsString('name="youth_bonus_action"', $memberHtml);
        $this->assertStringNotContainsString('Sačuvaj nacrt bonusa', $memberHtml);
        $this->assertStringNotContainsString('name="bonus_info_day"', $memberHtml);
        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE, $memberHtml);

        $chairmanHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('56.00', $chairmanHtml);
        $this->assertStringNotContainsString('Napomena mjesta 2', $chairmanHtml);
        $this->assertStringNotContainsString('name="notes"', $chairmanHtml);
        $this->assertStringNotContainsString('Ostale napomene', $chairmanHtml);
        $this->assertStringNotContainsString('bonus_zavod_nezaposleni', $chairmanHtml);
        $this->assertStringNotContainsString('Zavoda za zapošljavanje', $chairmanHtml);

        $indexHtml = $this->actingAs($ctx['members'][1]->fresh('user')->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('56.00', $indexHtml);
        $this->assertStringContainsString('Ocjenjivanje u toku', $indexHtml);
        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE, $indexHtml);
        $this->assertStringNotContainsString('Preliminarna rang-lista', $indexHtml);
    }

    public function test_dual_membership_does_not_copy_youth_bonuses_to_zensko(): void
    {
        $youth = $this->youthReadyToLock();
        $zensko = $this->zenskoReadyToScore();
        $sharedUser = $youth['chairman']->user;
        $zensko['chairman']->update(['user_id' => $sharedUser->id, 'name' => $sharedUser->name]);

        $this->actingAs($sharedUser)
            ->post(route('evaluation.store', $youth['application']), $this->finalPayload(['bonus_green_innovative' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $youth['application']->fresh()->bonus_green_innovative);
        $this->assertFalse((bool) $zensko['application']->fresh()->bonus_green_innovative);
        $this->assertNull($zensko['application']->fresh()->bonuses_confirmed_at);
    }

    public function test_youth_index_hides_forty_five_day_card_and_zensko_keeps_it(): void
    {
        $youth = $this->youthReadyToLock();
        $youthSurfaces = [
            $this->actingAs($youth['chairman']->user)
                ->get(route('evaluation.index'))
                ->assertOk()
                ->getContent(),
            $this->actingAs($youth['chairman']->user)
                ->get(route('evaluation.create', $youth['application']))
                ->assertOk()
                ->getContent(),
            $this->actingAs($youth['chairman']->user)
                ->get(route('dashboard'))
                ->assertOk()
                ->getContent(),
            $this->actingAs($youth['chairman']->user)
                ->get(route('admin.competitions.show', $youth['competition']))
                ->assertOk()
                ->getContent(),
        ];
        foreach ($youthSurfaces as $youthHtml) {
            $this->assertStringNotContainsString('45 dana', $youthHtml);
            $this->assertStringNotContainsString('Rok za donošenje odluke', $youthHtml);
            $this->assertStringNotContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava', $youthHtml);
        }

        $zensko = $this->zenskoReadyToScore();
        $zenskoIndex = $this->actingAs($zensko['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Rok za donošenje odluke', $zenskoIndex);
        $this->assertStringContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava', $zenskoIndex);
        $this->assertStringContainsString('45 dana', $zenskoIndex);

        $zenskoShow = $this->actingAs($zensko['chairman']->user)
            ->get(route('admin.competitions.show', $zensko['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('45 dana', $zenskoShow);
        $this->assertStringContainsString('Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs', $zenskoShow);

        $zenskoDashboard = $this->actingAs($zensko['chairman']->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('45 dana', $zenskoDashboard);
    }

    public function test_zensko_formula_zavod_ranking_and_null_youth_audit_remain(): void
    {
        $zensko = $this->zenskoReadyToScore();
        foreach ($zensko['members'] as $index => $member) {
            EvaluationScore::query()->create(array_merge($this->allCriteria(5), [
                'application_id' => $zensko['application']->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $index + 1,
                'notes' => null,
            ]));
        }
        $zensko['application']->forceFill([
            'bonus_zavod_nezaposleni' => true,
            'bonus_info_day' => true,
        ])->save();

        $aggregate = app(CanonicalIndividualScoringService::class)->aggregateApplication($zensko['application']->fresh());
        $this->assertNotNull($aggregate);
        $this->assertSame(5.0, $aggregate['criterion_averages'][1]);
        $this->assertSame(50.0, $aggregate['base_score']);
        $this->assertSame(3, $aggregate['bonus']);
        $this->assertSame(53.0, $aggregate['final_score']);

        app(CanonicalIndividualScoringService::class)->persistApplicationAggregatesIfCycleComplete($zensko['competition']->fresh());
        $fresh = $zensko['application']->fresh();
        $this->assertSame('53.00', $fresh->final_score);
        $this->assertNotNull($fresh->ranking_position);
        $this->assertNull($fresh->bonuses_confirmed_at);
        $this->assertNull($fresh->bonuses_confirmed_by_user_id);
        $this->assertNull($fresh->bonuses_confirmed_by_commission_member_id);
        $this->assertNull($fresh->bonuses_confirmed_by_name);
        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, CompetitionProgramCatalog::definitions()['omladinsko']['status']);
    }

    /**
     * @param  array{application: Application, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     * @param  array<int, array<string, int>>  $bySeat
     */
    /**
     * @param  array{application: Application, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     * @param  array<int, array<string, int>>  $bySeat
     * @param  array<string, string>  $bonusFlags
     */
    private function lockThreeSeats(array $ctx, array $bySeat = [], array $bonusFlags = []): void
    {
        foreach ([1, 2, 3] as $seat) {
            $member = $ctx['members'][$seat - 1];
            $existing = EvaluationScore::query()
                ->where('application_id', $ctx['application']->id)
                ->where('commission_member_id', $member->id)
                ->first();
            if ($existing && $existing->completed_at !== null) {
                continue;
            }
            $payload = $this->finalPayload($bySeat[$seat] ?? []);
            if ($seat === 1) {
                $payload = array_merge($payload, $bonusFlags);
            }
            $this->actingAs($member->fresh('user')->user)
                ->post(route('evaluation.store', $ctx['application']), $payload)
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * @param  array{application: Application, chairman: CommissionMember}  $ctx
     * @param  array<string, string>  $flags
     */
    private function confirmYouthBonuses(array $ctx, array $flags = []): void
    {
        $application = $ctx['application']->fresh();
        $chairmanScore = EvaluationScore::query()
            ->where('application_id', $application->id)
            ->where('commission_member_id', $ctx['chairman']->id)
            ->first();
        $alreadyFinal = $chairmanScore && $chairmanScore->completed_at !== null;
        if ($alreadyFinal && $application->bonuses_confirmed_at !== null) {
            return;
        }

        $payload = $alreadyFinal ? $flags : array_merge($this->finalPayload(), $flags);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $application), $payload)
            ->assertSessionHasNoErrors();
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
            'name' => 'Mladi agregat '.$year,
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
            'title' => 'Omladinsko agregat '.$year,
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
            'competition_number' => 'UP-A-'.$year,
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
            'name' => 'Zenska agregat '.uniqid(),
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
            'title' => 'Zensko agregat '.uniqid(),
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

    private function youthScoringListHtml(string $html): string
    {
        $this->assertTrue((bool) preg_match(
            '/<table\b[^>]*\bid="youth-scoring-list"[^>]*>.*?<\/table>/s',
            $html,
            $match
        ));

        return $match[0];
    }
}
