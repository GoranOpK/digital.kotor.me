<?php

namespace Tests\Feature;

use App\Mail\ApplicationEliminatoryRejectionMail;
use App\Mail\ApplicationPrigovorDecisionMail;
use App\Models\Application;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApplicationEliminatoryNoticeAndPrigovorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_fail_confirm_records_portal_notice_sends_mail_and_keeps_submitted_status(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->failPayload())
            ->assertRedirect(route('evaluation.create', $application));

        $application->refresh();
        $notice = $application->eliminatoryNotice;

        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($notice);
        $this->assertNotNull($notice->sent_at);
        $this->assertNotNull($notice->portal_recorded_at);
        $this->assertNotNull($notice->mail_sent_at);
        $this->assertStringContainsString(
            'Dostavljen je Izvještaj o realizaciji biznis plana sa Finansijskim izvještajem',
            implode(' ', $notice->reasons_snapshot),
        );
        $this->assertSame(1, ApplicationEliminatoryNotice::query()->where('application_id', $application->id)->count());

        Mail::assertSent(ApplicationEliminatoryRejectionMail::class, function (ApplicationEliminatoryRejectionMail $mail) use ($application) {
            $html = $mail->render();

            return $mail->application->is($application)
                && str_contains($html, 'isključivo putem Platforme')
                && ! str_contains($html, 'privreda@kotor.me');
        });
    }

    public function test_pass_confirm_does_not_create_notice_or_send_mail(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload())
            ->assertRedirect();

        $this->assertNull($application->fresh()->eliminatoryNotice);
        Mail::assertNothingSent();
    }

    public function test_portal_notice_persist_failure_rolls_back_failed_confirm_and_allows_retry(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->withoutExceptionHandling();

        $failPersist = true;
        ApplicationEliminatoryNotice::creating(function () use (&$failPersist) {
            if ($failPersist) {
                throw new \RuntimeException('forced portal notice persist failure');
            }
        });

        try {
            try {
                $this->actingAs($president->user)
                    ->post(route('evaluation.eliminatory.confirm', $application), $this->failPayload());
                $this->fail('Confirm must fail when portal notice persistence fails.');
            } catch (\RuntimeException $e) {
                $this->assertSame('forced portal notice persist failure', $e->getMessage());
            }

            $application->refresh();
            $this->assertNull($application->eliminatoryCheck?->confirmed_at);
            $this->assertSame(0, ApplicationEliminatoryNotice::query()->count());
            Mail::assertNothingSent();

            $failPersist = false;

            $this->actingAs($president->user)
                ->post(route('evaluation.eliminatory.confirm', $application), $this->failPayload())
                ->assertRedirect(route('evaluation.create', $application));

            $application->refresh();
            $this->assertNotNull($application->eliminatoryCheck?->confirmed_at);
            $this->assertTrue($application->eliminatoryCheck->isConfirmedFail());
            $this->assertNotNull($application->eliminatoryNotice?->sent_at);
            $this->assertSame(1, ApplicationEliminatoryNotice::query()->count());
        } finally {
            ApplicationEliminatoryNotice::flushEventListeners();
        }
    }

    public function test_mail_transport_failure_keeps_confirm_notice_and_sent_at(): void
    {
        Mail::swap(new class
        {
            public function to($users)
            {
                throw new \RuntimeException('smtp failure');
            }
        });

        [$application, $president] = $this->submittedApplicationWithCommission();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->failPayload())
            ->assertRedirect(route('evaluation.create', $application));

        $application->refresh();
        $notice = $application->eliminatoryNotice;

        $this->assertNotNull($application->eliminatoryCheck?->confirmed_at);
        $this->assertTrue($application->eliminatoryCheck->isConfirmedFail());
        $this->assertNotNull($notice);
        $this->assertNotNull($notice->sent_at);
        $this->assertNull($notice->mail_sent_at);
        $this->assertNotNull($notice->mail_failed_at);
        $this->assertSame(1, ApplicationEliminatoryNotice::query()->count());
        $this->assertTrue($notice->prigovorWindowIsOpen());
        $this->assertSame(
            $notice->sent_at->copy()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->timestamp,
            $notice->prigovorDeadlineAt()->timestamp,
        );
    }

    public function test_applicant_sees_notice_and_can_submit_one_prigovor_within_three_days(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $applicant = $application->user;

        $html = $this->actingAs($applicant)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Obavještenje i Prigovor', $html);
        $this->assertStringContainsString('Eliminatorno odbijena — rok za Prigovor', $html);
        $this->assertStringContainsString('Podnesi Prigovor', $html);
        $this->assertStringContainsString('name="obrazlozenje"', $html);
        $this->assertStringNotContainsString('name="document"', $html);

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Komisija nije uočila blagovremeno dostavljen izvještaj.',
            ])
            ->assertRedirect(route('applications.show', $application));

        $prigovor = $application->fresh()->prigovor;
        $this->assertNotNull($prigovor);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $prigovor->status);
        $this->assertSame($applicant->id, $prigovor->submitted_by_user_id);
        $this->assertSame('submitted', $application->fresh()->status);

        $submittedHtml = $this->actingAs($applicant)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Podnesen', $submittedHtml);
        $this->assertStringContainsString($prigovor->submitted_at->format('d.m.Y. H:i'), $submittedHtml);
        $this->assertStringContainsString('Čeka se odluka', $submittedHtml);

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Drugi pokušaj',
            ])
            ->assertForbidden();

        $this->assertSame(1, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
    }

    public function test_prigovor_after_three_days_is_forbidden_and_empty_obrazlozenje_is_rejected(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $applicant = $application->user;

        $this->actingAs($applicant)
            ->from(route('applications.show', $application))
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => '',
            ])
            ->assertSessionHasErrors('obrazlozenje');

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Kasni prigovor',
            ])
            ->assertForbidden();

        $this->assertNull($application->fresh()->prigovor);
    }

    public function test_non_owner_cannot_submit_prigovor(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $stranger = $this->userWithRole('korisnik');

        $this->actingAs($stranger)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Tuđi prigovor',
            ])
            ->assertForbidden();
    }

    public function test_chairman_accepts_prigovor_and_scoring_opens_without_changing_obrazac_3(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Dokument je bio dostavljen.',
            ])
            ->assertRedirect();

        $original = [
            (bool) $application->fresh()->eliminatoryCheck->criterion_1,
            (bool) $application->fresh()->eliminatoryCheck->criterion_2,
            (bool) $application->fresh()->eliminatoryCheck->criterion_3,
        ];

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Kriterijum 2 je otklonjen jer je dokument bio dostavljen.',
                'criterion_outcomes' => [2 => 'otklonjen'],
            ])
            ->assertRedirect(route('evaluation.create', $application));

        $application->refresh();
        $check = $application->eliminatoryCheck;
        $prigovor = $application->prigovor;

        $this->assertTrue($check->isConfirmedFail());
        $this->assertSame($original, [
            (bool) $check->criterion_1,
            (bool) $check->criterion_2,
            (bool) $check->criterion_3,
        ]);
        $this->assertFalse((bool) $check->criterion_2);
        $this->assertTrue($prigovor->isAccepted());
        $this->assertFalse($prigovor->eliminatory_reason_remaining);
        $this->assertNull($prigovor->criterion_1_remaining);
        $this->assertFalse((bool) $prigovor->criterion_2_remaining);
        $this->assertNull($prigovor->criterion_3_remaining);
        $this->assertSame('submitted', $application->status);
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
        $this->assertFalse($application->isEliminatedFromScoring());
        Mail::assertSent(ApplicationPrigovorDecisionMail::class);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Pokušaj ponovne odluke.',
            ])
            ->assertForbidden();
    }

    public function test_rejected_prigovor_keeps_scoring_blocked_and_cannot_reopen(): void
    {
        [$application, $president, $member] = $this->submittedApplicationWithCommission(withMember: true);
        $this->confirmFail($president, $application);
        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Molim preispitivanje.',
            ]);

        $this->actingAs($member->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
            ])
            ->assertForbidden();

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Razlog ostaje.',
            ])
            ->assertRedirect();

        $this->assertTrue($application->fresh()->prigovor->isRejected());
        $this->assertTrue($application->fresh()->prigovor->eliminatory_reason_remaining);
        $this->assertNull($application->fresh()->prigovor->criterion_1_remaining);
        $this->assertNull($application->fresh()->prigovor->criterion_2_remaining);
        $this->assertNull($application->fresh()->prigovor->criterion_3_remaining);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
        $this->assertTrue($application->fresh()->isEliminatedFromScoring());

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Pokušaj ponovne odluke.',
                'criterion_outcomes' => [2 => 'otklonjen'],
            ])
            ->assertForbidden();
    }

    public function test_superadmin_cannot_decide_prigovor_without_presidential_membership(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Prigovor',
            ]);

        $superadmin = $this->userWithRole('superadmin');
        $this->actingAs($superadmin)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
            ]);

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
    }

    public function test_decision_note_is_required_including_whitespace(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->submitApplicantPrigovor($application);

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => '',
            ])
            ->assertSessionHasErrors('decision_note');

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => '   ',
            ])
            ->assertSessionHasErrors('decision_note');

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
    }

    public function test_accepted_single_original_ne_otklonjen_opens_scoring(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application, [
            'criterion_1' => '0',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'note' => 'Ne prolazi kriterijum 1',
        ]);
        $this->submitApplicantPrigovor($application);

        $original = $this->criterionSnapshot($application);

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Prvi razlog je otklonjen.',
                'criterion_outcomes' => [1 => 'otklonjen'],
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame($original, $this->criterionSnapshot($application));
        $this->assertFalse($application->prigovor->eliminatory_reason_remaining);
        $this->assertFalse((bool) $application->prigovor->criterion_1_remaining);
        $this->assertNull($application->prigovor->criterion_2_remaining);
        $this->assertNull($application->prigovor->criterion_3_remaining);
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
    }

    public function test_accepted_with_remaining_reason_keeps_scoring_closed(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application, [
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'note' => 'Ne prolaze kriterijumi 1 i 2',
        ]);
        $this->submitApplicantPrigovor($application);
        $original = $this->criterionSnapshot($application);

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Prvi razlog otklonjen, drugi ostaje.',
                'criterion_outcomes' => [
                    1 => 'otklonjen',
                    2 => 'ostaje',
                ],
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame($original, $this->criterionSnapshot($application));
        $this->assertTrue($application->prigovor->isAccepted());
        $this->assertTrue($application->prigovor->eliminatory_reason_remaining);
        $this->assertFalse((bool) $application->prigovor->criterion_1_remaining);
        $this->assertTrue((bool) $application->prigovor->criterion_2_remaining);
        $this->assertNull($application->prigovor->criterion_3_remaining);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));

        $html = $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Prihvaćen', $html);
        $this->assertStringContainsString('Prvi razlog otklonjen, drugi ostaje.', $html);
        $this->assertStringContainsString('ne nastavlja u bodovanje', $html);
        $this->assertStringContainsString('Eliminatorni razlozi koji ostaju', $html);
        $this->assertStringContainsString(
            'Dostavljen je Izvještaj o realizaciji biznis plana sa Finansijskim izvještajem',
            $html,
        );
    }

    public function test_accepted_all_original_ne_otklonjen_opens_scoring(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application, [
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '0',
            'note' => 'Ne prolaze sva tri',
        ]);
        $this->submitApplicantPrigovor($application);

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Sva tri razloga su otklonjena.',
                'criterion_outcomes' => [
                    1 => 'otklonjen',
                    2 => 'otklonjen',
                    3 => 'otklonjen',
                ],
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertFalse((bool) $application->eliminatoryCheck->criterion_1);
        $this->assertFalse((bool) $application->eliminatoryCheck->criterion_2);
        $this->assertFalse((bool) $application->eliminatoryCheck->criterion_3);
        $this->assertFalse($application->prigovor->eliminatory_reason_remaining);
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));

        $html = $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Prihvaćen', $html);
        $this->assertStringContainsString('Sva tri razloga su otklonjena.', $html);
        $this->assertStringContainsString('može nastaviti u individualno bodovanje', $html);
    }

    public function test_original_da_outcome_is_rejected_and_missing_ne_outcome_fails(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->submitApplicantPrigovor($application);

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Pokušaj ishoda na originalni Da.',
                'criterion_outcomes' => [
                    1 => 'ostaje',
                    2 => 'otklonjen',
                ],
            ])
            ->assertSessionHasErrors('criterion_outcomes.1');

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Nedostaje ishod za originalni Ne.',
            ])
            ->assertSessionHasErrors('criterion_outcomes.2');

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
    }

    public function test_rejected_prigovor_shows_decision_note_to_applicant(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->submitApplicantPrigovor($application);

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Dokument i dalje nedostaje.',
            ])
            ->assertRedirect();

        $html = $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Odbijen', $html);
        $this->assertStringContainsString('Dokument i dalje nedostaje.', $html);
        $this->assertStringContainsString('Eliminatorna odluka ostaje', $html);
        $this->assertStringContainsString($application->fresh()->prigovor->decided_at->format('d.m.Y. H:i'), $html);
        Mail::assertSent(ApplicationPrigovorDecisionMail::class);
    }

    public function test_decision_mail_failure_keeps_persisted_decision(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->submitApplicantPrigovor($application);

        Mail::swap(new class
        {
            public function to($users)
            {
                throw new \RuntimeException('smtp failure');
            }
        });

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Odluka ostaje važeća i ako mail padne.',
            ])
            ->assertRedirect();

        $prigovor = $application->fresh()->prigovor;
        $this->assertTrue($prigovor->isRejected());
        $this->assertNotNull($prigovor->decided_at);
        $this->assertSame('Odluka ostaje važeća i ako mail padne.', $prigovor->decision_note);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
    }

    public function test_decision_after_seven_days_is_still_allowed(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->submitApplicantPrigovor($application);

        $html = $this->actingAs($president->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Rok Komisije za odluku', $html);
        $this->assertStringContainsString('7 dana od prijema', $html);

        $this->travel(7)->days();
        $this->travel(1)->seconds();

        $this->actingAs($president->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_ODBIJEN,
                'decision_note' => 'Odluka nakon isteka prikaznog roka Komisije.',
            ])
            ->assertRedirect(route('evaluation.create', $application));

        $this->assertTrue($application->fresh()->prigovor->isRejected());
    }

    public function test_chairman_of_other_commission_cannot_decide_prigovor(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->confirmFail($president, $application);
        $this->submitApplicantPrigovor($application);

        $otherCommission = $this->createCommissionWithMembers(5, true);
        $otherPresident = $otherCommission->activeMembers()->where('position', 'predsjednik')->firstOrFail();

        $this->actingAs($otherPresident->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Predsjednik druge komisije.',
                'criterion_outcomes' => [2 => 'otklonjen'],
            ])
            ->assertForbidden();

        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
    }

    public function test_obrazac_3_visual_markup_and_style_baseline_is_unchanged(): void
    {
        $create = file_get_contents(resource_path('views/evaluation/create.blade.php'));
        $show = file_get_contents(resource_path('views/evaluation/show.blade.php'));

        [$createObrazac3] = $this->extractBetween(
            $create,
            "                <div class=\"print-document-header\">",
            '            @if(! $scoringIsAllowed)',
        );
        [$showObrazac3] = $this->extractBetween(
            $show,
            "                <div class=\"form-title\" style=\"text-transform: none; font-size: 16px; margin-bottom: 4px;\">",
            '            <!-- 4. Ocjena biznis plana u brojkama -->',
        );

        $createStyle = $this->firstStyleBlock($create);
        $showStyle = $this->firstStyleBlock($show);

        $this->assertSame('29cde03cd76ff93cb965e7796d9d5917a5bb8dc41bf9c31d119e86795a186084', hash('sha256', $createObrazac3));
        $this->assertSame('5478ab31568e39130cefe7b3829bb80a92c19468d796d0b928c9a5e1d66198c3', hash('sha256', $showObrazac3));
        $this->assertSame('219ce30bee90a0b00e87db8d6a0591fbc609c242e59a8baa60e7032f9a2973d3', hash('sha256', $createStyle));
        $this->assertSame('ca68b4b5c41be2686de4ea1ead34bab7206263ae1f19af27b294d7b4af67d6d3', hash('sha256', $showStyle));

        $this->assertStringContainsString('margin: 10mm 8mm;', $createStyle);
        $this->assertStringContainsString('margin: 12mm 10mm;', $showStyle);
        $this->assertStringNotContainsString('kn-prigovor', $createObrazac3);
        $this->assertStringNotContainsString('kn-prigovor', $showObrazac3);
        $this->assertTrue(strpos($create, "@include('evaluation.partials.prigovor_commission_block')") > strpos($create, '</form>'));
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function extractBetween(string $text, string $start, string $end): array
    {
        $i = strpos($text, $start);
        $j = strpos($text, $end, $i);
        $this->assertNotFalse($i);
        $this->assertNotFalse($j);

        return [substr($text, $i, $j - $i), $i, $j];
    }

    private function firstStyleBlock(string $text): string
    {
        $i = strpos($text, '<style>');
        $j = strpos($text, '</style>');
        $this->assertNotFalse($i);
        $this->assertNotFalse($j);

        return substr($text, $i, $j + 8 - $i);
    }

    /**
     * @return array{0: Application, 1: CommissionMember, 2?: CommissionMember}
     */
    private function submittedApplicationWithCommission(bool $withMember = false): array
    {
        $commission = $this->createCommissionWithMembers(5, true);
        $competition = $this->createZenskoCompetition($commission);
        $application = $this->createSubmittedApplication($competition);
        $president = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail();
        $member = $commission->activeMembers()->where('position', 'clan')->firstOrFail();

        if ($withMember) {
            return [$application, $president, $member];
        }

        return [$application, $president];
    }

    private function confirmFail(CommissionMember $president, Application $application, array $overrides = []): void
    {
        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->failPayload($overrides))
            ->assertRedirect();
    }

    private function submitApplicantPrigovor(Application $application, string $obrazlozenje = 'Molim preispitivanje.'): void
    {
        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => $obrazlozenje,
            ])
            ->assertRedirect();
    }

    /**
     * @return array{0: bool, 1: bool, 2: bool}
     */
    private function criterionSnapshot(Application $application): array
    {
        $check = $application->fresh()->eliminatoryCheck;

        return [
            (bool) $check->criterion_1,
            (bool) $check->criterion_2,
            (bool) $check->criterion_3,
        ];
    }

    private function failPayload(array $overrides = []): array
    {
        return $this->eliminatoryPayload(array_merge([
            'criterion_1' => '1',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'note' => 'Ne prolazi kriterijum 2',
            'confirmation_acknowledged' => '1',
        ], $overrides));
    }

    private function eliminatoryPayload(array $overrides = []): array
    {
        return array_merge([
            'criterion_1' => '1',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'note' => null,
        ], $overrides);
    }

    private function scorePayload(): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return $payload;
    }

    private function createSubmittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Biznis plan '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
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

    private function createZenskoCompetition(Commission $commission): Competition
    {
        $competition = Competition::create([
            'title' => 'Konkurs obrazac 3 '.uniqid(),
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

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        return $competition->fresh();
    }

    private function createCommissionWithMembers(int $count, bool $withPresident): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => ($withPresident && $i === 0) ? 'predsjednik' : 'clan',
                'member_type' => $types[$i] ?? 'opstina',
                'status' => 'active',
            ]);
        }

        return $commission->fresh(['activeMembers.user']);
    }
}
