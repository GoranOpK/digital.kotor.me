<?php

namespace Tests\Feature;

use App\Mail\ApplicationEliminatoryAppealNoticeMail;
use App\Mail\ApplicationEliminatoryRejectionMail;
use App\Models\Application;
use App\Models\ApplicationDocument;
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
use App\Services\ApplicationPrigovorService;
use App\Services\ApplicationYouthAppealWindowService;
use App\Support\EliminatoryProfileConfig;
use App\Support\YouthPrigovorObrazlozenje;
use Illuminate\Database\QueryException;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OmladinskoEliminatoryNoticeAndPrigovorTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_fail_creates_one_youth_notice_with_activated_reasons_and_keeps_submitted(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application']->fresh(['eliminatoryCheck', 'eliminatoryNotice']);
        $notice = $application->eliminatoryNotice;

        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($notice);
        $this->assertSame(1, ApplicationEliminatoryNotice::query()->where('application_id', $application->id)->count());
        $this->assertNotNull($notice->sent_at);
        $this->assertTrue($notice->prigovorWindowIsOpen());
        $this->assertSame(
            $notice->sent_at->copy()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->timestamp,
            $notice->prigovorDeadlineAt()->timestamp,
        );

        $snapshot = implode("\n", array_merge($notice->reasons_snapshot ?? [], [$notice->note_snapshot]));
        $this->assertStringContainsString('dokumentacija je nepotpuna', $snapshot);
        $this->assertStringContainsString('raniji korisnik nije dostavio M4/M4a', $snapshot);
        $this->assertStringContainsString('Nedostaje izvod', $snapshot);
        $this->assertStringContainsString('Nema M4', $snapshot);
        $this->assertStringNotContainsString('biznis plan nije povezan', $snapshot);
        $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $snapshot);
        $this->assertStringNotContainsString('{', (string) $notice->note_snapshot);
        $this->assertStringNotContainsString('član 10', $snapshot);
        $this->assertStringNotContainsString('članu 10', $snapshot);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));

        Mail::assertSent(ApplicationEliminatoryAppealNoticeMail::class, function (ApplicationEliminatoryAppealNoticeMail $mail) use ($application) {
            $html = $mail->render();

            $this->assertTrue($mail->application->is($application));
            $this->assertStringContainsString('dokumentacija je nepotpuna', $html);
            $this->assertStringContainsString('Nedostaje izvod', $html);
            $this->assertStringContainsString('isključivo putem Platforme', $html);
            $this->assertStringContainsString('Prigovor nije dopuna prijave', $html);
            $this->assertStringContainsString('Prijava ostaje podnesena', $html);
            $this->assertStringContainsString('Komisija za podršku preduzetništvu mladih', $html);
            $this->assertStringContainsString('Poštovani/a', $html);
            $this->assertStringNotContainsString('član 10', $html);
            $this->assertStringNotContainsString('članu 10', $html);
            $this->assertStringNotContainsString('ženskom preduzetništvu', $html);
            $this->assertStringNotContainsString('Poštovana ', $html);
            $this->assertStringNotContainsString('odbila Vašu prijavu', $html);
            $this->assertStringNotContainsString('konačno odbijena', $html);
            $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $html);
            $this->assertStringNotContainsString('"1":', $html);

            return true;
        });
        Mail::assertNotSent(ApplicationEliminatoryRejectionMail::class);
    }

    public function test_pass_does_not_create_notice(): void
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPayload())
            ->assertRedirect();

        $this->assertNull($ctx['application']->fresh()->eliminatoryNotice);
        Mail::assertNothingSent();
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
    }

    public function test_owner_can_submit_one_prigovor_on_activated_reasons_only(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $applicant = $application->user;
        $m3Before = $this->m3Snapshot($application);

        $html = $this->actingAs($applicant)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dokumentacija je nepotpuna', $html);
        $this->assertStringContainsString('Nedostaje izvod', $html);
        $this->assertStringContainsString('name="contested[]"', $html);
        $this->assertStringContainsString('name="criterion_obrazlozenja[1]"', $html);
        $this->assertStringContainsString('name="criterion_obrazlozenja[2]"', $html);
        $this->assertStringNotContainsString('name="criterion_obrazlozenja[3]"', $html);
        $this->assertStringNotContainsString('name="obrazlozenje"', $html);
        $this->assertStringNotContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringNotContainsString('name="document"', $html);
        $this->assertStringNotContainsString('član 10', $html);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $html);
        $this->assertStringNotContainsString('konačno odbijena', $html);
        $this->assertStringNotContainsString(trim(EliminatoryProfileConfig::YOUTH_NOTE_PREFIX), $html);
        $this->assertStringNotContainsString('Vaša prijava je odbijena', $html);

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [1],
                'criterion_obrazlozenja' => [
                    1 => 'Izvod je već bio u prijavi.',
                    2 => 'ne treba',
                ],
                'document_type' => 'licna_karta',
                'obrazlozenje' => 'ženski kanal se ignorira',
            ])
            ->assertRedirect(route('applications.show', $application));

        $prigovor = $application->fresh()->prigovor;
        $this->assertNotNull($prigovor);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $prigovor->status);
        $this->assertSame($applicant->id, $prigovor->submitted_by_user_id);
        $this->assertTrue($prigovor->criterionIsContested(1));
        $this->assertFalse($prigovor->criterionIsContested(2));
        $this->assertNull($prigovor->criterion_3_contested);
        $this->assertTrue(YouthPrigovorObrazlozenje::isStructured($prigovor->obrazlozenje));
        $this->assertSame('Izvod je već bio u prijavi.', YouthPrigovorObrazlozenje::explanation($prigovor->obrazlozenje, 1));
        $this->assertSame('', YouthPrigovorObrazlozenje::explanation($prigovor->obrazlozenje, 2));
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame($m3Before, $this->m3Snapshot($application));
        $this->assertSame(0, ApplicationDocument::query()->where('application_id', $application->id)->count());
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));

        $shown = $this->actingAs($applicant)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Izvod je već bio u prijavi.', $shown);
        $this->assertStringNotContainsString(trim(YouthPrigovorObrazlozenje::PREFIX), $shown);
        $this->assertStringNotContainsString('7 dana', $shown);

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [2],
                'criterion_obrazlozenja' => [2 => 'Drugi pokušaj'],
            ])
            ->assertForbidden();
        $this->assertSame(1, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
    }

    public function test_non_owner_cannot_submit_and_inactive_criterion_is_rejected(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $stranger = $this->userWithRole('korisnik');

        $this->actingAs($stranger)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [1],
                'criterion_obrazlozenja' => [1 => 'Tuđi prigovor'],
            ])
            ->assertForbidden();

        $this->actingAs($application->user)
            ->from(route('applications.show', $application))
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [3],
                'criterion_obrazlozenja' => [3 => 'Neaktiviran'],
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors('contested');

        $this->actingAs($application->user)
            ->from(route('applications.show', $application))
            ->post(route('applications.prigovor.store', $application), [
                'criterion_obrazlozenja' => [1 => 'Bez izbora'],
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors('contested');

        $this->actingAs($application->user)
            ->from(route('applications.show', $application))
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [1],
                'criterion_obrazlozenja' => [1 => '   '],
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors('criterion_obrazlozenja.1');

        $this->assertNull($application->fresh()->prigovor);
    }

    public function test_failed_mail_does_not_move_sent_at_or_deadline(): void
    {
        Mail::swap(new class
        {
            public function to($users)
            {
                throw new \RuntimeException('smtp failure');
            }
        });

        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthFailPayload())
            ->assertRedirect();

        $application = $ctx['application']->fresh(['eliminatoryNotice']);
        $notice = $application->eliminatoryNotice;
        $this->assertNotNull($notice?->sent_at);
        $originalSent = $notice->sent_at->copy();
        $this->assertNull($notice->mail_sent_at);
        $this->assertNotNull($notice->mail_failed_at);
        $this->assertTrue($notice->prigovorWindowIsOpen());
        $this->assertSame(
            $originalSent->copy()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->timestamp,
            $notice->prigovorDeadlineAt()->timestamp,
        );
        $this->assertSame('submitted', $application->status);
    }

    public function test_submit_is_allowed_at_exact_three_day_boundary(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $originalSent = $application->eliminatoryNotice->sent_at->copy();

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk();
        $this->assertSame(
            $originalSent->timestamp,
            $application->fresh()->eliminatoryNotice->sent_at->timestamp
        );

        $this->travelTo($originalSent->copy()->addDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS)->subSecond());
        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [1],
                'criterion_obrazlozenja' => [1 => 'Neposredno prije isteka'],
            ])
            ->assertRedirect(route('applications.show', $application));
        $this->assertNotNull($application->fresh()->prigovor);
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame(
            $originalSent->timestamp,
            $application->fresh()->eliminatoryNotice->sent_at->timestamp
        );
    }

    public function test_submit_is_forbidden_immediately_after_deadline(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [1],
                'criterion_obrazlozenja' => [1 => 'Kasni prigovor'],
            ])
            ->assertForbidden();

        $this->assertNull($application->fresh()->prigovor);
        $this->assertSame('rejected', $application->fresh()->status);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
    }

    public function test_owner_view_and_commission_index_finalize_expired_window_idempotently(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $m3Before = $this->m3Snapshot($application);

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk();

        $rejected = $application->fresh();
        $this->assertSame('rejected', $rejected->status);
        $this->assertNotEmpty($rejected->rejection_reason);
        $reason = $rejected->rejection_reason;
        $this->assertSame($m3Before, $this->m3Snapshot($application));

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk();
        $this->assertSame('rejected', $application->fresh()->status);
        $this->assertSame($reason, $application->fresh()->rejection_reason);

        $ctx2 = $this->confirmYouthFail();
        $this->travel(3)->days();
        $this->travel(1)->seconds();
        $this->actingAs($ctx2['chairman']->user)
            ->get(route('evaluation.index'))
            ->assertOk();
        $this->assertSame('rejected', $ctx2['application']->fresh()->status);

        app(ApplicationYouthAppealWindowService::class)->finalizeExpiredWithoutPrigovor($ctx2['application']->fresh());
        $this->assertSame('rejected', $ctx2['application']->fresh()->status);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx2['application']->fresh()));
    }

    public function test_timely_prigovor_keeps_submitted_and_chairman_cannot_decide_yet(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [1, 2],
                'criterion_obrazlozenja' => [
                    1 => 'Prvi razlog',
                    2 => 'Drugi razlog',
                ],
            ])
            ->assertRedirect();

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertTrue($application->fresh()->prigovor->isPodnesen());

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Prvi razlog', $html);
        $this->assertStringNotContainsString('name="odluka"', $html);
        $this->assertStringNotContainsString('Evidentiraj odluku Komisije', $html);
        $this->assertStringNotContainsString('7 dana od prijema', $html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'odluka' => ApplicationPrigovor::STATUS_PRIHVACEN,
                'decision_note' => 'Premature',
                'criterion_outcomes' => [1 => 'otklonjen', 2 => 'otklonjen'],
            ])
            ->assertForbidden();
        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
    }

    public function test_unauthorized_evaluation_create_does_not_finalize_expired_foreign_application(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);
        $stranger = $this->userWithRole('korisnik');

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($stranger)
            ->get(route('evaluation.create', $application))
            ->assertForbidden();

        $this->assertSame($before, $this->sideEffectsSnapshot($application->fresh()));
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNull($application->fresh()->rejection_reason);
        $this->assertNull($application->fresh()->prigovor);
    }

    public function test_other_commission_member_evaluation_show_does_not_finalize_application(): void
    {
        $ctx = $this->confirmYouthFail();
        $other = $this->makeYouthReadyContext();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($other['chairman']->user)
            ->get(route('evaluation.show', $application))
            ->assertForbidden();

        $this->assertSame($before, $this->sideEffectsSnapshot($application->fresh()));
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNull($application->fresh()->prigovor);
    }

    public function test_owner_and_own_commission_member_can_lazy_finalize_expired_window(): void
    {
        $ownerCtx = $this->confirmYouthFail();
        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $this->actingAs($ownerCtx['application']->user)
            ->get(route('applications.show', $ownerCtx['application']))
            ->assertOk();
        $this->assertSame('rejected', $ownerCtx['application']->fresh()->status);
        $this->assertNotEmpty($ownerCtx['application']->fresh()->rejection_reason);
        $this->assertNull($ownerCtx['application']->fresh()->prigovor);

        $memberCtx = $this->confirmYouthFail();
        $this->travel(3)->days();
        $this->travel(1)->seconds();
        $this->actingAs($memberCtx['chairman']->user)
            ->get(route('evaluation.create', $memberCtx['application']))
            ->assertOk();
        $this->assertSame('rejected', $memberCtx['application']->fresh()->status);
        $this->assertNull($memberCtx['application']->fresh()->prigovor);

        $showCtx = $this->confirmYouthFail();
        $this->travel(3)->days();
        $this->travel(1)->seconds();
        $this->actingAs($showCtx['chairman']->user)
            ->get(route('evaluation.show', $showCtx['application']))
            ->assertRedirect(route('evaluation.create', $showCtx['application']));
        $this->assertSame('rejected', $showCtx['application']->fresh()->status);
        $this->assertNull($showCtx['application']->fresh()->prigovor);
    }

    public function test_youth_submit_requires_submitted_status(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);

        $application->status = 'evaluated';
        $application->save();

        $response = $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), $this->youthPrigovorPayload());

        $response->assertForbidden();
        $this->assertStringContainsString(
            ApplicationPrigovorService::NOT_SUBMITTED_MESSAGE,
            $response->exception?->getMessage() ?? $response->getContent()
        );
        $this->assertNull($application->fresh()->prigovor);
        $this->assertSame('evaluated', $application->fresh()->status);

        $after = $this->sideEffectsSnapshot($application->fresh());
        $this->assertSame($before['m3'], $after['m3']);
        $this->assertSame($before['sent_at'], $after['sent_at']);
        $this->assertSame($before['documents'], $after['documents']);
        $this->assertSame($before['scores'], $after['scores']);
    }

    public function test_timely_submit_keeps_submitted_then_finalize_is_noop(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), $this->youthPrigovorPayload())
            ->assertRedirect(route('applications.show', $application));

        $fresh = $application->fresh();
        $this->assertSame('submitted', $fresh->status);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $fresh->prigovor->status);
        $this->assertNull($fresh->rejection_reason);

        $this->travel(3)->days();
        $this->travel(1)->seconds();
        app(ApplicationYouthAppealWindowService::class)->finalizeExpiredWithoutPrigovor($fresh);

        $afterFinalize = $application->fresh();
        $this->assertSame('submitted', $afterFinalize->status);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $afterFinalize->prigovor->status);
        $this->assertNull($afterFinalize->rejection_reason);
        $this->assertSame(1, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
        $this->assertSame($before['m3'], $this->sideEffectsSnapshot($afterFinalize)['m3']);
        $this->assertSame($before['sent_at'], $this->sideEffectsSnapshot($afterFinalize)['sent_at']);
        $this->assertSame($before['documents'], $this->sideEffectsSnapshot($afterFinalize)['documents']);
        $this->assertSame($before['scores'], $this->sideEffectsSnapshot($afterFinalize)['scores']);
    }

    public function test_expired_submit_does_not_create_prigovor_and_rejects(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $response = $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), $this->youthPrigovorPayload());

        $response->assertForbidden();
        $this->assertStringContainsString(
            ApplicationPrigovorService::WINDOW_CLOSED_MESSAGE,
            $response->exception?->getMessage() ?? $response->getContent()
        );
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());

        $fresh = $application->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertNotEmpty($fresh->rejection_reason);
        $this->assertNull($fresh->prigovor);
        $this->assertFalse(
            $fresh->status === 'rejected' && $fresh->prigovor?->status === ApplicationPrigovor::STATUS_PODNESEN
        );

        $after = $this->sideEffectsSnapshot($fresh);
        $this->assertSame($before['m3'], $after['m3']);
        $this->assertSame($before['sent_at'], $after['sent_at']);
        $this->assertSame($before['documents'], $after['documents']);
        $this->assertSame($before['scores'], $after['scores']);
    }

    public function test_rejected_application_cannot_gain_new_podnesen_prigovor(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];

        $this->travel(3)->days();
        $this->travel(1)->seconds();
        app(ApplicationYouthAppealWindowService::class)->finalizeExpiredWithoutPrigovor($application);
        $this->assertSame('rejected', $application->fresh()->status);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), $this->youthPrigovorPayload())
            ->assertForbidden();

        $fresh = $application->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertNull($fresh->prigovor);
        $this->assertSame(0, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
    }

    public function test_repeated_finalize_is_idempotent(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);

        $this->travel(3)->days();
        $this->travel(1)->seconds();

        $service = app(ApplicationYouthAppealWindowService::class);
        $service->finalizeExpiredWithoutPrigovor($application);
        $first = $application->fresh();
        $reason = $first->rejection_reason;
        $this->assertSame('rejected', $first->status);

        $service->finalizeExpiredWithoutPrigovor($first);
        $second = $application->fresh();
        $this->assertSame('rejected', $second->status);
        $this->assertSame($reason, $second->rejection_reason);
        $this->assertNull($second->prigovor);

        $after = $this->sideEffectsSnapshot($second);
        $this->assertSame($before['m3'], $after['m3']);
        $this->assertSame($before['sent_at'], $after['sent_at']);
        $this->assertSame($before['documents'], $after['documents']);
        $this->assertSame($before['scores'], $after['scores']);
    }

    public function test_sequential_duplicate_submit_keeps_one_row_and_controlled_message(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application'];
        $before = $this->sideEffectsSnapshot($application);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), $this->youthPrigovorPayload())
            ->assertRedirect();

        $duplicate = $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'contested' => [2],
                'criterion_obrazlozenja' => [2 => 'Drugi paralelni pokušaj'],
            ]);

        $duplicate->assertForbidden();
        $this->assertStringContainsString(
            ApplicationPrigovorService::ALREADY_SUBMITTED_MESSAGE,
            $duplicate->exception?->getMessage() ?? $duplicate->getContent()
        );
        $this->assertStringNotContainsString('SQLSTATE', $duplicate->getContent());
        $this->assertStringNotContainsString('Duplicate entry', $duplicate->getContent());
        $this->assertSame(1, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $application->fresh()->prigovor->status);

        try {
            ApplicationPrigovor::create([
                'application_id' => $application->id,
                'obrazlozenje' => 'direktni duplikat',
                'status' => ApplicationPrigovor::STATUS_PODNESEN,
                'submitted_at' => now(),
                'submitted_by_user_id' => $application->user_id,
                'eliminatory_reason_remaining' => true,
            ]);
            $this->fail('Očekivan unique QueryException za apg_application_id_unique.');
        } catch (QueryException $e) {
            $this->assertSame(1062, (int) ($e->errorInfo[1] ?? 0));
            $this->assertStringContainsString('apg_application_id_unique', strtolower($e->getMessage()));
        }

        $after = $this->sideEffectsSnapshot($application->fresh());
        $this->assertSame($before['m3'], $after['m3']);
        $this->assertSame($before['sent_at'], $after['sent_at']);
        $this->assertSame($before['documents'], $after['documents']);
        $this->assertSame($before['scores'], $after['scores']);
        $this->assertSame(1, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
    }

    /**
     * Windows PHPUnit + RefreshDatabase drži setup u jednoj konekciji/transakciji.
     * Druga MySQL konekcija ne vidi uncommitted redove, a lockForUpdate na istoj niti
     * bi čekao zauvijek. Stvarni dvokonekcijski race zato nije pouzdano moguć ovdje.
     * Unique 1062 se klasifikuje samo za apg_application_id_unique.
     */
    public function test_duplicate_prigovor_constraint_classifier_does_not_swallow_other_errors(): void
    {
        $service = app(ApplicationPrigovorService::class);
        $method = new \ReflectionMethod(ApplicationPrigovorService::class, 'isDuplicatePrigovorConstraint');
        $method->setAccessible(true);

        $unique = $this->queryException(
            '23000',
            1062,
            "Duplicate entry '12' for key 'application_prigovors.apg_application_id_unique'",
        );
        $otherUnique = $this->queryException(
            '23000',
            1062,
            "Duplicate entry 'x' for key 'some_other_unique'",
        );
        $deadlock = $this->queryException('40001', 1213, 'Deadlock found when trying to get lock');

        $this->assertTrue($method->invoke($service, $unique));
        $this->assertFalse($method->invoke($service, $otherUnique));
        $this->assertFalse($method->invoke($service, $deadlock));
    }

    public function test_structured_obrazlozenje_helpers_do_not_throw_on_invalid_content(): void
    {
        $this->assertSame([1 => '', 2 => '', 3 => ''], YouthPrigovorObrazlozenje::parse('običan ženski tekst'));
        $this->assertSame([1 => '', 2 => '', 3 => ''], YouthPrigovorObrazlozenje::parse(YouthPrigovorObrazlozenje::PREFIX.'{not-json'));
        $this->assertSame([1 => '', 2 => '', 3 => ''], YouthPrigovorObrazlozenje::parse(null));
        $composed = YouthPrigovorObrazlozenje::compose([1 => ' A ', 3 => 'C']);
        $this->assertSame([1 => 'A', 2 => '', 3 => 'C'], YouthPrigovorObrazlozenje::parse($composed));
    }

    public function test_womens_submit_still_stores_plain_text_and_null_contested(): void
    {
        $commission = Commission::create([
            'name' => 'Zenska notice '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $president = null;
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $member = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $types[$i],
                'status' => 'active',
            ]);
            if ($i === 0) {
                $president = $member;
            }
        }
        $competition = Competition::create([
            'title' => 'Zensko notice '.uniqid(),
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
            'business_plan_name' => 'Plan zensko notice',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'note' => 'Ne prolazi kriterijum 2',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        Mail::assertSent(ApplicationEliminatoryRejectionMail::class);
        Mail::assertNotSent(ApplicationEliminatoryAppealNoticeMail::class);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Molim preispitivanje.',
                'contested' => [2],
            ])
            ->assertRedirect();

        $prigovor = $application->fresh()->prigovor;
        $this->assertSame('Molim preispitivanje.', $prigovor->obrazlozenje);
        $this->assertFalse(YouthPrigovorObrazlozenje::isStructured($prigovor->obrazlozenje));
        $this->assertNull($prigovor->criterion_1_contested);
        $this->assertNull($prigovor->criterion_2_contested);
        $this->assertNull($prigovor->criterion_3_contested);
        $this->assertSame('submitted', $application->fresh()->status);

        $this->travel(3)->days();
        $this->travel(1)->seconds();
        $this->actingAs($application->user)->get(route('applications.show', $application))->assertOk();
        $this->assertSame('submitted', $application->fresh()->status);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function confirmYouthFail(): array
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthFailPayload())
            ->assertRedirect();

        return $ctx;
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
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthReadyContext(): array
    {
        $ctx = $this->makeYouthContext();
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            [
                'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
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
            'name' => 'Mladi notice '.$year,
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
            'title' => 'Omladinsko notice '.$year,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '80000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-N-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan notice '.$year,
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
     * @return array<string, mixed>
     */
    private function youthFailPayload(): array
    {
        return $this->youthPayload([
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'criterion_notes' => [
                1 => 'Nedostaje izvod',
                2 => 'Nema M4',
                3 => '',
            ],
            'confirmation_acknowledged' => '1',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function youthPayload(array $overrides = []): array
    {
        return array_merge([
            'criterion_1' => '1',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'criterion_notes' => [1 => '', 2 => '', 3 => ''],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function youthPrigovorPayload(): array
    {
        return [
            'contested' => [1],
            'criterion_obrazlozenja' => [1 => 'Izvod je već bio u prijavi.'],
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     rejection_reason: ?string,
     *     m3: array{criterion_1: mixed, criterion_2: mixed, criterion_3: mixed, note: ?string, confirmed_at: mixed},
     *     sent_at: ?int,
     *     notice_id: mixed,
     *     note_snapshot: mixed,
     *     reasons_snapshot: mixed,
     *     documents: int,
     *     scores: int,
     *     prigovor_count: int
     * }
     */
    private function sideEffectsSnapshot(Application $application): array
    {
        $fresh = $application->fresh(['eliminatoryCheck', 'eliminatoryNotice']);
        $notice = $fresh->eliminatoryNotice;

        return [
            'status' => $fresh->status,
            'rejection_reason' => $fresh->rejection_reason,
            'm3' => $this->m3Snapshot($fresh),
            'sent_at' => $notice?->sent_at?->timestamp,
            'notice_id' => $notice?->id,
            'note_snapshot' => $notice?->note_snapshot,
            'reasons_snapshot' => $notice?->reasons_snapshot,
            'documents' => ApplicationDocument::query()->where('application_id', $fresh->id)->count(),
            'scores' => EvaluationScore::query()->where('application_id', $fresh->id)->count(),
            'prigovor_count' => ApplicationPrigovor::query()->where('application_id', $fresh->id)->count(),
        ];
    }

    private function queryException(string $sqlState, int $driverCode, string $message): QueryException
    {
        $previous = new \PDOException('SQLSTATE['.$sqlState.']: '.$message);
        $previous->errorInfo = [$sqlState, $driverCode, $message];

        return new QueryException('mysql', 'insert into application_prigovors', [], $previous);
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
