<?php

namespace Tests\Feature;

use App\Mail\ApplicationPrigovorDecisionMail;
use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Support\EliminatoryProfileConfig;
use App\Support\YouthPrigovorObrazlozenje;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OmladinskoPrigovorDecisionTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2030;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_only_that_commission_chairman_can_decide(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $payload = $this->liftAllContestedPayload();

        $this->actingAs($ctx['members'][1]->user)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertForbidden();

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertForbidden();

        $other = $this->submittedYouthPrigovor();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertForbidden();

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $payload)
            ->assertRedirect(route('evaluation.index', [
                'competition_id' => $application->competition_id,
            ]));

        $this->assertTrue($application->fresh()->prigovor->isAccepted());
    }

    public function test_youth_decision_redirects_chairman_to_the_competition_application_list(): void
    {
        $rejected = $this->submittedYouthPrigovor();
        $rejectedApplication = $rejected['application'];
        $rejectedNote = 'Prigovor se odbija. Eliminatorni razlog ostaje.';
        $stillOpen = Application::create([
            'competition_id' => $rejectedApplication->competition_id,
            'user_id' => $rejectedApplication->user_id,
            'business_plan_name' => 'Plan koji ostaje u postupku',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $rejectedResponse = $this->actingAs($rejected['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $rejectedApplication), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => $rejectedNote,
            ]);

        $rejectedResponse->assertRedirect(route('evaluation.index', [
            'competition_id' => $rejectedApplication->competition_id,
            'filter' => 'rejected',
        ]));
        $rejectedResponse->assertSessionHas(
            'success',
            'Prigovor je odbijen. Odluka Komisije je evidentirana.',
        );

        $rejectedApplication->refresh();
        $this->assertTrue($rejectedApplication->prigovor->isRejected());
        $this->assertSame($rejectedNote, $rejectedApplication->prigovor->decision_note);
        $this->assertSame('rejected', $rejectedApplication->status);
        $this->assertSame('submitted', $stillOpen->fresh()->status);

        $this->actingAs($rejected['chairman']->user)
            ->get(route('evaluation.index', [
                'competition_id' => $rejectedApplication->competition_id,
                'filter' => 'rejected',
            ]))
            ->assertOk()
            ->assertSee('Prigovor je odbijen. Odluka Komisije je evidentirana.', false)
            ->assertSee('Ocjenjivanje prijava', false)
            ->assertSee($rejectedApplication->business_plan_name, false)
            ->assertDontSee($stillOpen->business_plan_name, false);

        $accepted = $this->submittedYouthPrigovor();
        $acceptedApplication = $accepted['application'];
        $acceptedNote = 'Svi osporeni razlozi su otklonjeni.';

        $acceptedResponse = $this->actingAs($accepted['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $acceptedApplication), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => $acceptedNote,
            ]);

        $acceptedLocation = $acceptedResponse->headers->get('Location');
        $acceptedResponse->assertRedirect(route('evaluation.index', [
            'competition_id' => $acceptedApplication->competition_id,
        ]));
        $this->assertIsString($acceptedLocation);
        $this->assertStringNotContainsString('filter=rejected', $acceptedLocation);
        $this->assertStringContainsString(
            'competition_id='.$acceptedApplication->competition_id,
            $acceptedLocation,
        );
        $acceptedResponse->assertSessionHas(
            'success',
            'Prigovor je prihvaćen. Prijava može nastaviti postupak.',
        );

        $acceptedApplication->refresh();
        $this->assertTrue($acceptedApplication->prigovor->isAccepted());
        $this->assertSame($acceptedNote, $acceptedApplication->prigovor->decision_note);
        $this->assertSame('submitted', $acceptedApplication->status);

        $this->actingAs($accepted['chairman']->user)
            ->get(route('evaluation.index', [
                'competition_id' => $acceptedApplication->competition_id,
            ]))
            ->assertOk()
            ->assertSee('Prigovor je prihvaćen. Prijava može nastaviti postupak.', false)
            ->assertSee('Ocjenjivanje prijava', false)
            ->assertSee($acceptedApplication->business_plan_name, false);
    }

    public function test_incomplete_commission_and_inactive_chairman_cannot_decide(): void
    {
        $incomplete = $this->submittedYouthPrigovor();
        $incomplete['members'][2]->update(['status' => 'inactive']);
        $this->actingAs($incomplete['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $incomplete['application']), $this->liftAllContestedPayload())
            ->assertForbidden();
        $this->assertTrue($incomplete['application']->fresh()->prigovor->isPodnesen());

        $inactive = $this->submittedYouthPrigovor();
        $inactive['chairman']->update(['status' => 'inactive']);
        $this->actingAs($inactive['chairman']->fresh('user')->user)
            ->post(route('evaluation.prigovor.decide', $inactive['application']), $this->liftAllContestedPayload())
            ->assertForbidden();
        $this->assertTrue($inactive['application']->fresh()->prigovor->isPodnesen());
    }

    public function test_decision_before_on_and_after_seven_days(): void
    {
        $before = $this->submittedYouthPrigovor();
        $beforeHtml = $this->actingAs($before['chairman']->user)
            ->get(route('evaluation.create', $before['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('7 kalendarskih dana od prijema', $beforeHtml);
        $this->assertStringNotContainsString('Rok od 7 dana je prekoračen', $beforeHtml);

        $this->actingAs($before['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $before['application']), $this->liftAllContestedPayload())
            ->assertRedirect();
        $this->assertFalse($before['application']->fresh()->prigovor->wasDecidedAfterKomisijaDeadline());

        $onTime = $this->submittedYouthPrigovor();
        $this->travel(7)->days();
        $this->actingAs($onTime['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $onTime['application']), $this->liftAllContestedPayload())
            ->assertRedirect();
        $this->assertFalse($onTime['application']->fresh()->prigovor->wasDecidedAfterKomisijaDeadline());
        $this->travelBack();

        $late = $this->submittedYouthPrigovor();
        $this->travel(7)->days();
        $this->travel(1)->seconds();
        $lateHtml = $this->actingAs($late['chairman']->user)
            ->get(route('evaluation.create', $late['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Rok od 7 dana je prekoračen', $lateHtml);
        $this->assertStringContainsString('Evidentiraj odluku Komisije', $lateHtml);

        $this->actingAs($late['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $late['application']), [
                'decision_note' => 'Odluka nakon isteka prikaznog roka.',
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
            ])
            ->assertRedirect();

        $latePrigovor = $late['application']->fresh()->prigovor;
        $this->assertTrue($latePrigovor->isAccepted());
        $this->assertTrue($latePrigovor->wasDecidedAfterKomisijaDeadline());

        $lateDoneHtml = $this->actingAs($late['chairman']->user)
            ->get(route('evaluation.create', $late['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('nakon isteka roka od 7 dana', $lateDoneHtml);
    }

    public function test_no_automatic_decision_after_seven_days(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $this->travel(7)->days();
        $this->travel(1)->seconds();

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk();
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk();

        $fresh = $application->fresh(['prigovor']);
        $this->assertTrue($fresh->prigovor->isPodnesen());
        $this->assertSame('submitted', $fresh->status);
        $this->assertNull($fresh->prigovor->decided_at);
    }

    public function test_commission_records_outcome_for_each_activated_criterion(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Odluka Komisije o prigovoru', $html);
        $this->assertStringContainsString('Prigovor prihvaćen', $html);
        $this->assertStringContainsString('Prigovor odbijen', $html);
        $this->assertStringContainsString('name="odluka"', $html);
        $this->assertStringContainsString('value="prihvacen"', $html);
        $this->assertStringContainsString('value="odbijen"', $html);
        $this->assertStringNotContainsString('name="criterion_outcomes', $html);
        $this->assertStringContainsString('Obrazloženje odluke Komisije', $html);
        $this->assertYouthCopyHasNoGenderedApplicantTerms($html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Prigovor se prihvata za oba aktivirana kriterijuma.',
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
            ])
            ->assertRedirect();

        $application->refresh();
        $prigovor = $application->prigovor;
        $this->assertTrue($prigovor->isAccepted());
        $this->assertFalse((bool) $prigovor->eliminatory_reason_remaining);
        $this->assertFalse((bool) $prigovor->criterion_1_remaining);
        $this->assertFalse((bool) $prigovor->criterion_2_remaining);
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('submitted', $application->status);
        $this->assertNull($application->rejection_reason);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    public function test_only_documentation_activated_shows_and_decides_only_that_criterion(): void
    {
        $ctx = $this->submittedYouthPrigovor($this->youthFailPayload([1]), [
            'criterion_obrazlozenja' => [1 => 'Dokumentacija je kompletna.'],
        ]);
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $html);
        $this->assertStringContainsString('Dokumentacija je kompletna.', $html);
        $this->assertStringContainsString('name="odluka"', $html);
        $this->assertStringContainsString('value="prihvacen"', $html);
        $this->assertStringContainsString('value="odbijen"', $html);
        $this->assertStringNotContainsString('name="criterion_outcomes', $html);
        $activated = $this->activatedPrigovorSection($html);
        $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $activated);
        $this->assertStringNotContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $activated);
        $this->assertStringNotContainsString('nije osporen', $html);

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Jedini aktivirani razlog je otklonjen.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odluka']);

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Jedini aktivirani razlog je otklonjen.',
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertTrue($application->prigovor->isAccepted());
        $this->assertFalse((bool) $application->prigovor->criterion_1_remaining);
        $this->assertNull($application->prigovor->criterion_2_remaining);
        $this->assertSame('submitted', $application->status);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));

        Mail::assertSent(ApplicationPrigovorDecisionMail::class, function (ApplicationPrigovorDecisionMail $mail) use ($application) {
            $html = $mail->render();
            $this->assertTrue($mail->application->is($application));
            $this->assertCount(1, $mail->criterionOutcomes);
            $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $html);
            $this->assertStringContainsString('Otklonjen', $html);
            $this->assertStringNotContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $html);
            $this->assertStringNotContainsString('nije osporen', $html);

            return true;
        });
    }

    public function test_two_activated_criteria_appear_only_those_two_on_decision(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $html);
        $this->assertStringContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $html);
        $this->assertSame(2, substr_count($html, 'name="odluka"'));
        $this->assertStringNotContainsString('name="criterion_outcomes', $html);
        $activated = $this->activatedPrigovorSection($html);
        $this->assertStringContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $activated);
        $this->assertStringNotContainsString('Biznis plan je povezan sa prioritetnim oblastima iz člana 12 Odluke o mladima?', $activated);
        $this->assertSame(
            [1, 2],
            $application->fresh('eliminatoryCheck')->eliminatoryCheck->activatedCriterionNumbers()
        );
    }

    public function test_historical_mismatched_contested_flags_follow_locked_m3(): void
    {
        $ctx = $this->confirmYouthFail($this->youthFailPayload([1]));
        $application = $ctx['application'];
        $m4Explanation = 'Tekst upisan uz neaktivirani M4/M4a.';
        $this->persistMismatchedYouthPrigovor($application, $m4Explanation);
        $before = $application->fresh(['prigovor']);
        $m3Before = $this->m3Snapshot($application);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $html);
        $this->assertStringContainsString('name="odluka"', $html);
        $activated = $this->activatedPrigovorSection($html);
        $this->assertStringNotContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $activated);
        $this->assertStringNotContainsString('name="criterion_outcomes', $html);
        $this->assertStringNotContainsString($m4Explanation, $activated);

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Nedostaje odluka.',
                'criterion_outcomes' => [2 => 'otklonjen'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odluka']);

        $unchanged = $application->fresh(['prigovor']);
        $this->assertTrue($unchanged->prigovor->isPodnesen());
        $this->assertSame('submitted', $unchanged->status);
        $this->assertTrue($unchanged->prigovor->criterionIsContested(2));
        $this->assertFalse($unchanged->prigovor->criterionIsContested(1));
        $this->assertSame($m4Explanation, YouthPrigovorObrazlozenje::explanation($unchanged->prigovor->obrazlozenje, 2));
        $this->assertSame($before->prigovor->submitted_at?->timestamp, $unchanged->prigovor->submitted_at?->timestamp);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($unchanged));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Odluka po zaključanom M3, bez premještanja obrazloženja.',
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
            ])
            ->assertRedirect();

        $decided = $application->fresh(['prigovor']);
        $this->assertTrue($decided->prigovor->isRejected());
        $this->assertTrue($decided->prigovor->criterionRemainingIsTrue(1));
        $this->assertNull($decided->prigovor->criterion_2_remaining);
        $this->assertSame('rejected', $decided->status);
        $this->assertSame('Dostavljena je potpuna dokumentacija?', $decided->rejection_reason);
        $this->assertSame($m4Explanation, YouthPrigovorObrazlozenje::explanation($decided->prigovor->obrazlozenje, 2));
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($decided));
    }

    public function test_missing_contested_outcome_is_422_and_inactive_cannot_be_decided(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Nedostaje odluka.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odluka']);

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['decision_note']);

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
    }

    public function test_direct_post_cannot_skip_activated_or_decide_inactive_criterion(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['decision_note']);

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_partial_accept_rejects_application_when_any_reason_remains(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Prigovor se prihvata bez obzira na broj aktiviranih kriterijuma.',
                'criterion_outcomes' => [1 => 'otklonjen', 2 => 'ostaje'],
            ])
            ->assertRedirect();

        $application->refresh();
        $prigovor = $application->prigovor;
        $this->assertTrue($prigovor->isAccepted());
        $this->assertSame($ctx['chairman']->id, $prigovor->decided_by_commission_member_id);
        $this->assertSame($ctx['chairman']->user_id, $prigovor->decided_by_user_id);
        $this->assertSame($ctx['chairman']->name, $prigovor->decided_by_name);
        $this->assertFalse((bool) $prigovor->criterion_1_remaining);
        $this->assertFalse((bool) $prigovor->criterion_2_remaining);
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('submitted', $application->status);
        $this->assertNull($application->rejection_reason);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    public function test_all_reasons_lifted_keeps_submitted_and_scoring_closed(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $this->liftAllContestedPayload())
            ->assertRedirect();

        $application->refresh();
        $prigovor = $application->prigovor;
        $this->assertTrue($prigovor->isAccepted());
        $this->assertFalse((bool) $prigovor->eliminatory_reason_remaining);
        $this->assertFalse((bool) $prigovor->criterion_1_remaining);
        $this->assertFalse((bool) $prigovor->criterion_2_remaining);
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('submitted', $application->status);
        $this->assertNull($application->rejection_reason);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));

        $evalHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Prihvaćen', $evalHtml);
        $this->assertYouthCopyHasNoGenderedApplicantTerms($evalHtml);
        $this->assertStringNotContainsString('Ocijeni', $evalHtml);
        $this->assertStringNotContainsString('član 10', $evalHtml);
        $this->assertStringNotContainsString('članu 10', $evalHtml);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $evalHtml);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $this->liftAllContestedPayload())
            ->assertForbidden();
        $this->assertTrue($application->fresh()->prigovor->isAccepted());

        $applicantHtml = $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Konačna odluka Komisije', $applicantHtml);
        $this->assertStringContainsString('Prihvaćen', $applicantHtml);
        $this->assertStringContainsString('Eliminatorni razlozi su otklonjeni', $applicantHtml);
        $this->assertYouthCopyHasNoGenderedApplicantTerms($applicantHtml);
        $this->assertStringNotContainsString('član 10', $applicantHtml);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $applicantHtml);
        $this->assertStringNotContainsString('Poštovana', $applicantHtml);

        Mail::assertSent(ApplicationPrigovorDecisionMail::class, function (ApplicationPrigovorDecisionMail $mail) use ($application) {
            $html = $mail->render();
            $this->assertTrue($mail->application->is($application));
            $this->assertTrue($mail->isYouth);
            $this->assertStringContainsString('Prihvaćen', $html);
            $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $html);
            $this->assertStringContainsString('Otklonjen', $html);
            $this->assertStringContainsString('Svi osporeni razlozi su otklonjeni.', $html);
            $this->assertStringContainsString('Komisija za podršku preduzetništvu mladih', $html);
            $this->assertStringContainsString('Poštovani/a', $html);
            $this->assertYouthCopyHasNoGenderedApplicantTerms($html);
            $this->assertStringNotContainsString('član 10', $html);
            $this->assertStringNotContainsString('članu 10', $html);
            $this->assertStringNotContainsString('ženskom preduzetništvu', $html);
            $this->assertStringNotContainsString('Poštovana ', $html);
            $this->assertStringNotContainsString('podnositeljko', $html);
            $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $html);
            $this->assertStringNotContainsString('"1":', $html);

            return true;
        });
    }

    public function test_youth_decision_mail_fallback_is_gender_neutral(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), $this->liftAllContestedPayload())
            ->assertRedirect();

        $application = $application->fresh(['competition', 'eliminatoryCheck', 'prigovor']);
        $application->setRelation('user', null);

        $mail = new ApplicationPrigovorDecisionMail($application, $application->prigovor);
        $this->assertTrue($mail->isYouth);
        $this->assertFalse($mail->hasNamedRecipient);
        $this->assertSame('lice koje je podnijelo prijavu', $mail->recipientName);
        $html = $mail->render();
        $this->assertStringContainsString('Poštovanje, lice koje je podnijelo prijavu', $html);
        $this->assertStringNotContainsString('Poštovani/a', $html);
        $this->assertStringNotContainsString('podnosilac prijave', $html);
        $this->assertStringNotContainsString('podnositeljka', $html);
        $this->assertStringNotContainsString('preduzetnica', $html);
        $this->assertYouthCopyHasNoGenderedApplicantTerms($html);
    }

    public function test_failed_mail_does_not_rollback_decision(): void
    {
        $ctx = $this->submittedYouthPrigovor();
        $application = $ctx['application'];

        Mail::swap(new class
        {
            public function to($users)
            {
                throw new \RuntimeException('smtp failure');
            }
        });

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Odluka ostaje važeća i ako mail padne.',
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertTrue($application->prigovor->isRejected());
        $this->assertSame('rejected', $application->status);
        $this->assertSame('Odluka ostaje važeća i ako mail padne.', $application->prigovor->decision_note);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    /**
     * @param  array<string, mixed>|null  $failPayload
     * @param  array<string, mixed>|null  $prigovorPayload
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function submittedYouthPrigovor(?array $failPayload = null, ?array $prigovorPayload = null): array
    {
        $ctx = $this->confirmYouthFail($failPayload);
        $this->actingAs($ctx['application']->user)
            ->post(route('applications.prigovor.store', $ctx['application']), $prigovorPayload ?? [
                'criterion_obrazlozenja' => [
                    1 => 'Obrazloženje za kriterijum 1.',
                    2 => 'Obrazloženje za kriterijum 2.',
                ],
            ])
            ->assertRedirect();

        return $ctx;
    }

    private function persistMismatchedYouthPrigovor(Application $application, string $m4Explanation): ApplicationPrigovor
    {
        return ApplicationPrigovor::create([
            'application_id' => $application->id,
            'obrazlozenje' => YouthPrigovorObrazlozenje::compose([
                1 => '',
                2 => $m4Explanation,
                3 => '',
            ]),
            'status' => ApplicationPrigovor::STATUS_PODNESEN,
            'submitted_at' => now(),
            'submitted_by_user_id' => $application->user_id,
            'eliminatory_reason_remaining' => true,
            'criterion_1_contested' => false,
            'criterion_2_contested' => true,
            'criterion_3_contested' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function activatedPrigovorSection(string $html): string
    {
        $start = strpos($html, 'Aktivirani kriterijumi i obrazloženja podnosioca:');
        $this->assertNotFalse($start);
        $end = strpos($html, 'Odluka Komisije o prigovoru', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    private function liftAllContestedPayload(): array
    {
        return [
            'decision_note' => 'Svi osporeni razlozi su otklonjeni.',
            'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
        ];
    }

    /**
     * @return array{criterion_1: mixed, criterion_2: mixed, criterion_3: mixed, note: ?string, confirmed_at: mixed}
     */
    private function m3Snapshot(Application $application): array
    {
        $check = $application->fresh()->eliminatoryCheck;

        return [
            'criterion_1' => $check->criterion_1,
            'criterion_2' => $check->criterion_2,
            'criterion_3' => $check->criterion_3,
            'note' => $check->note,
            'confirmed_at' => $check->confirmed_at?->timestamp,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function confirmYouthFail(?array $payload = null): array
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $payload ?? $this->youthFailPayload())
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
            'name' => 'Mladi odluka '.$year,
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
            'title' => 'Omladinsko odluka '.$year,
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
            'competition_number' => 'UP-D-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan odluka '.$year,
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
     * @param  list<int>  $failed
     * @return array<string, mixed>
     */
    private function youthFailPayload(array $failed = [1, 2]): array
    {
        return [
            'criterion_1' => in_array(1, $failed, true) ? '0' : '1',
            'criterion_2' => in_array(2, $failed, true) ? '0' : '1',
            'criterion_3' => in_array(3, $failed, true) ? '0' : '1',
            'criterion_notes' => [
                1 => in_array(1, $failed, true) ? 'Nedostaje izvod' : '',
                2 => in_array(2, $failed, true) ? 'Nema M4' : '',
                3 => in_array(3, $failed, true) ? 'Nije prioritetna oblast' : '',
            ],
            'confirmation_acknowledged' => '1',
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

    private function assertYouthCopyHasNoGenderedApplicantTerms(string $text): void
    {
        $lower = mb_strtolower($text);
        $this->assertStringNotContainsString('podnositeljk', $lower);
        $this->assertStringNotContainsString('preduzetnica', $lower);
        $this->assertStringNotContainsString('podnosioče', $lower);
    }
}
