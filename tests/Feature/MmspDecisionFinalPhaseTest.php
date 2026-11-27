<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\ApplicationPrigovor;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalFinalRankingSignature;
use App\Models\CanonicalFormalDecision;
use App\Models\CanonicalInvestmentPlan;
use App\Models\CanonicalOutcome;
use App\Models\CanonicalPredlog;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\MmspDecisionDocument;
use App\Models\Role;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Decision\CanonicalCloseService;
use App\Services\Competitions\MmspDecisionDocumentStore;
use App\Services\Competitions\MmspDecisionDocxWriter;
use App\Services\Competitions\MmspDecisionProposal;
use App\Services\Competitions\MmspDecisionProposalGate;
use App\Services\Competitions\Outcome\CanonicalOutcomeService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\YouthDecisionProposalGate;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType as T;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;
use ZipArchive;

class MmspDecisionFinalPhaseTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    /** @var list<int> */
    private array $cleanupCompetitionIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        if ($this->app) {
            foreach ($this->cleanupCompetitionIds as $id) {
                Storage::disk('local')->deleteDirectory(MmspDecisionDocumentStore::DIRECTORY.'/'.$id);
            }
        }

        parent::tearDown();
    }

    public function test_final_ranking_offers_decision_only_to_the_chair_and_does_not_use_the_youth_gate(): void
    {
        [$members, $competition, $application] = $this->supportedCompetition();

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.canonical-outcome.show', $competition))
            ->assertRedirect(route('evaluation.mmsp-ranking', $competition));

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.mmsp-ranking', $competition))
            ->assertOk()
            ->assertSee('Konačna rang-lista', false)
            ->assertSee('data-testid="mmsp-decision-open"', false)
            ->assertSee('data-testid="mmsp-ranking-print"', false);
        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertDontSee('data-testid="mmsp-ranking-title"', false)
            ->assertSee('id="mmsp-p3-print"', false)
            ->assertSee('printMmspSection(\'p3\')', false);

        $this->signAll($competition, $members);
        $competition->refresh();

        $this->assertFalse($competition->hasChairmanCompletedDecisions());
        $this->assertSame(
            YouthDecisionProposalGate::NOT_YOUTH_MESSAGE,
            app(YouthDecisionProposalGate::class)->denyReason($competition, $members[G::ROLE_PREDSJEDNIK]->user)
        );

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.canonical-outcome.show', $competition))
            ->assertRedirect(route('evaluation.mmsp-ranking', $competition));

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.mmsp-ranking', $competition))
            ->assertOk()
            ->assertSee('data-testid="mmsp-ranking-title"', false)
            ->assertSee('Konačna rang-lista', false)
            ->assertSee('data-testid="mmsp-decision-open"', false)
            ->assertSee('Štampaj', false);

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->get(route('evaluation.mmsp-ranking', $competition))
            ->assertOk()
            ->assertDontSee('data-testid="mmsp-decision-open"', false);

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertForbidden();

        $youth = Competition::create([
            'title' => 'Mladi',
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 1000,
            'deadline_days' => 20,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
        ]);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->post(route('evaluation.mmsp-decision.generate', $youth), $this->dates())
            ->assertForbidden();
    }

    public function test_chair_generates_a_stored_docx_and_download_does_not_create_another(): void
    {
        [$members, $competition, $application] = $this->supportedCompetition();
        $this->signAll($competition, $members);
        ApplicationPrigovor::query()->create([
            'application_id' => $application->id,
            'obrazlozenje' => 'Omladinski prigovor ne ulazi u MMSP odluku.',
            'status' => ApplicationPrigovor::STATUS_PRIHVACEN,
            'submitted_at' => now(),
            'submitted_by_user_id' => $application->user_id,
        ]);
        $assessment = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->firstOrFail();
        ApplicationComplaint::query()->create([
            'application_id' => $application->id,
            'competition_id' => $competition->id,
            'completeness_assessment_id' => $assessment->id,
            'submitted_by_user_id' => $application->user_id,
            'body' => 'Prigovor je razmotren.',
            'submitted_at' => now(),
            'decision_due_at' => now()->addDays(7),
            'decision' => ApplicationComplaint::ACCEPTED,
            'decided_at' => now(),
            'decided_by_user_id' => $members[G::ROLE_PREDSJEDNIK]->user_id,
            'decision_notes' => 'Prihvaćen.',
        ]);

        $scoreBefore = (string) $application->fresh()->final_score;
        $amountBefore = (string) CanonicalOutcome::query()->where('application_id', $application->id)->value('approved_amount');

        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $beforeDecision = $this->actingAs($chair)
            ->get(route('evaluation.mmsp-decision', $competition))
            ->assertOk();
        $beforeDecision->assertSee('value="2026-09-10"', false);
        $beforeDecision->assertSee('name="first_session_date"', false);
        $beforeDecision->assertSee('name="second_session_date"', false);
        $beforeDecision->assertSee('name="third_session_date"', false);
        $beforeDecision->assertSee('Datum prve sjednice', false);
        $beforeDecision->assertSee('Datum druge sjednice', false);
        $beforeDecision->assertSee('Datum treće sjednice', false);
        $beforeDecision->assertDontSee('data-testid="mmsp-close-competition"', false);

        $this->actingAs($chair)
            ->from(route('evaluation.mmsp-decision', $competition))
            ->post(route('evaluation.mmsp-decision.generate', $competition), [])
            ->assertRedirect()
            ->assertSessionHasErrors(['second_session_date', 'third_session_date']);
        $this->assertSame([], Storage::disk('local')->allFiles(MmspDecisionDocumentStore::DIRECTORY.'/'.$competition->id));

        $this->actingAs($chair)
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertRedirect(route('evaluation.mmsp-decision', $competition))
            ->assertSessionHas('success', 'Odluka je uspješno generisana i sačuvana.');

        $files = Storage::disk('local')->allFiles(MmspDecisionDocumentStore::DIRECTORY.'/'.$competition->id);
        $this->assertCount(1, $files);
        $this->assertSame(1, MmspDecisionDocument::query()->where('competition_id', $competition->id)->count());
        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame($scoreBefore, (string) $application->fresh()->final_score);
        $this->assertSame($amountBefore, (string) CanonicalOutcome::query()->where('application_id', $application->id)->value('approved_amount'));

        $first = $this->actingAs($chair)->get(route('evaluation.mmsp-decision.download', $competition))->assertOk();
        $text = $this->docxText($first->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Primjer DOO i Ana Anić', $text);
        $this->assertStringContainsString('Subvencije za razvoj autentičnih lokalnih proizvoda/usluga', $text);
        $this->assertStringContainsString('40,00', $text);
        $this->assertStringContainsString('1.000,00', $text);
        $this->assertStringContainsString('5.000,00', $text);
        $this->assertStringContainsString('10.09.2026', $text);
        $this->assertStringContainsString('01.09.2026', $text);
        $this->assertStringContainsString('05.09.2026', $text);
        $this->assertStringContainsString('01.08.2026.', $text);
        $this->assertStringContainsString('21.08.2026.', $text);
        $this->assertStringContainsString('Kotor, 10.09.2026.', $text);
        $this->assertStringContainsString('Komisija je donijela odluku o prihvatanju 1 prigovora.', $text);
        $this->assertStringNotContainsString('0 prigovora', $text);
        $this->assertStringContainsString('prvobitno nepotpunih prijava nema', $text);
        $this->assertStringContainsString('1 prijava može biti uzeta', $text);
        $this->assertStringContainsString('trajao 20 dana', $text);
        $this->assertStringContainsString('dodijeljene subvencije', $text);
        $this->assertStringContainsString('2026. godini (u daljem tekstu: Javni poziv).', $text);
        $this->assertStringContainsString('dodjeljuju sredstva za 1 plan ulaganja', $text);
        $this->assertStringContainsString('preliminarne rang-liste', $text);
        $this->assertStringContainsString('preliminarnoj rang-listi', $text);
        $this->assertStringContainsString('konačnu rang-listu', $text);
        $this->assertStringNotContainsString('dodjeljene subvencije', $text);
        $this->assertStringNotContainsString('dodijeljuju sredstva', $text);
        $this->assertStringNotContainsString('godini. (u daljem tekstu', $text);
        $this->assertStringContainsString('Podnosiocima i podnositeljkama prijava (x1)', $text);
        $this->assertStringContainsString('Članovima Komisije (x3)', $text);
        $this->assertStringContainsString('Vladan Barović', $text);
        $this->assertStringNotContainsString('XX', $text);
        $this->assertStringNotContainsString('xx', $text);
        $this->assertOfficialTemplatePreserved($first->baseResponse->getFile()->getPathname());

        $second = $this->actingAs($chair)->get(route('evaluation.mmsp-decision.download', $competition))->assertOk();
        $this->assertSame(
            hash_file('sha256', $first->baseResponse->getFile()->getPathname()),
            hash_file('sha256', $second->baseResponse->getFile()->getPathname())
        );
        $this->assertCount(1, Storage::disk('local')->allFiles(MmspDecisionDocumentStore::DIRECTORY.'/'.$competition->id));

        $page = $this->actingAs($chair)->get(route('evaluation.mmsp-decision', $competition))->assertOk();
        $page->assertSee('data-testid="mmsp-decision-download"', false);
        $page->assertSee('data-testid="mmsp-close-competition"', false);
    }

    public function test_missing_applicant_identity_blocks_generation_without_a_file(): void
    {
        [$members, $competition, $application] = $this->supportedCompetition();
        $this->signAll($competition, $members);
        CanonicalApplicantForm::query()->where('application_id', $application->id)->update(['person_name' => '']);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->from(route('evaluation.mmsp-decision', $competition))
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertRedirect()
            ->assertSessionHasErrors('decision');

        $this->assertSame([], Storage::disk('local')->allFiles(MmspDecisionDocumentStore::DIRECTORY.'/'.$competition->id));
        $this->assertSame(0, MmspDecisionDocument::query()->where('competition_id', $competition->id)->count());
    }

    public function test_chair_closes_after_the_saved_decision_without_the_secretariat_chain(): void
    {
        [$members, $competition, $application] = $this->supportedCompetition();
        $this->signAll($competition, $members);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->assertNotNull(app(CanonicalCloseService::class)->blockReason($competition->fresh()));
        $this->assertNull(CanonicalPredlog::query()->where('competition_id', $competition->id)->value('finalized_at'));

        $this->actingAs($chair)
            ->from(route('evaluation.mmsp-decision', $competition))
            ->post(route('evaluation.mmsp-decision.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(MmspDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertSame('published', $competition->fresh()->status);
        $this->assertNull($competition->fresh()->closed_at);

        $this->actingAs($chair)
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertRedirect();

        $scoreBefore = (string) $application->fresh()->final_score;
        $amountBefore = (string) CanonicalOutcome::query()->where('application_id', $application->id)->value('approved_amount');
        $rankingBefore = CanonicalFinalRanking::query()->where('competition_id', $competition->id)->value('finalized_at');
        $rankingId = CanonicalFinalRanking::query()->where('competition_id', $competition->id)->value('id');
        $signaturesBefore = CanonicalFinalRankingSignature::query()->where('final_ranking_id', $rankingId)->count();
        $applicationsBefore = Application::query()->where('competition_id', $competition->id)->count();
        $eventsBefore = CanonicalAdministrativeEvent::query()->where('competition_id', $competition->id)->count();
        $documentBefore = MmspDecisionDocument::query()->where('competition_id', $competition->id)->firstOrFail();

        $this->actingAs($chair)
            ->post(route('evaluation.mmsp-decision.close', $competition))
            ->assertRedirect()
            ->assertSessionHas('success');

        $competition->refresh();
        $this->assertSame('completed', $competition->status);
        $this->assertNotNull($competition->closed_at);
        $this->assertSame($scoreBefore, (string) $application->fresh()->final_score);
        $this->assertSame($amountBefore, (string) CanonicalOutcome::query()->where('application_id', $application->id)->value('approved_amount'));
        $this->assertSame((string) $rankingBefore, (string) CanonicalFinalRanking::query()->where('competition_id', $competition->id)->value('finalized_at'));
        $this->assertNull(CanonicalPredlog::query()->where('competition_id', $competition->id)->value('finalized_at'));
        $this->assertTrue(CanonicalAdministrativeEvent::query()
            ->where('competition_id', $competition->id)
            ->where('action', CanonicalAdministrativeEvent::COMPETITION_CLOSED)
            ->exists());
        $this->assertSame(1, MmspDecisionDocument::query()->where('competition_id', $competition->id)->count());
        $this->assertSame(3, $signaturesBefore);
        $this->assertSame($signaturesBefore, CanonicalFinalRankingSignature::query()->where('final_ranking_id', $rankingId)->count());
        $this->assertSame($applicationsBefore, Application::query()->where('competition_id', $competition->id)->count());
        $this->assertSame($eventsBefore + 1, CanonicalAdministrativeEvent::query()->where('competition_id', $competition->id)->count());
        $this->assertTrue(Storage::disk('local')->exists($documentBefore->storage_path));
        $this->assertSame(
            $documentBefore->content_sha256,
            MmspDecisionDocument::query()->findOrFail($documentBefore->id)->content_sha256
        );

        $archive = $this->actingAs($this->canonicalActor())
            ->get(route('admin.competitions.index', ['tab' => 'archive']));
        $archive->assertOk();
        $archive->assertSee($competition->title, false);

        $closedAt = $competition->closed_at->toDateTimeString();
        $predlogId = CanonicalPredlog::query()->where('competition_id', $competition->id)->value('id');
        $outcomeBefore = CanonicalOutcome::query()->where('application_id', $application->id)->firstOrFail();
        $rankingBeforeRow = CanonicalFinalRanking::query()->where('competition_id', $competition->id)->firstOrFail();
        $eventsAfterClose = CanonicalAdministrativeEvent::query()->where('competition_id', $competition->id)->count();
        $documentsAfterClose = MmspDecisionDocument::query()->where('competition_id', $competition->id)->count();

        $this->actingAs($chair)
            ->get(route('evaluation.mmsp-decision', $competition))
            ->assertOk()
            ->assertDontSee('data-testid="mmsp-decision-generate-form"', false)
            ->assertDontSee('data-testid="mmsp-close-competition"', false)
            ->assertSee('data-testid="mmsp-decision-download"', false);

        $this->actingAs($chair)
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertForbidden();

        $this->actingAs($chair)
            ->from(route('evaluation.mmsp-decision', $competition))
            ->post(route('evaluation.mmsp-decision.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(
            \App\Services\Competitions\Decision\MmspDecisionCloseService::ALREADY_CLOSED_MESSAGE,
            session('errors')->first('error')
        );

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->from(route('evaluation.mmsp-decision', $competition))
            ->post(route('evaluation.mmsp-decision.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($chair)
            ->from(route('evaluation.canonical-outcome.show', $competition))
            ->post(route('evaluation.canonical-outcome.draft', [$competition, $application]), [
                'decision' => 'support',
                'amount' => '50',
                'justification' => 'Naknadna izmjena nije dozvoljena.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('canonical_outcome');

        $this->actingAs($chair)
            ->from(route('evaluation.canonical-administrative.show', $competition))
            ->post(route('evaluation.canonical-administrative.predlog.finalize', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(
            \App\Services\Competitions\Decision\CanonicalAdministrativeGate::CLOSED_MESSAGE,
            session('errors')->first('error')
        );

        $this->actingAs($chair)
            ->from(route('evaluation.canonical-administrative.show', $competition))
            ->post(route('evaluation.canonical-administrative.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $administrative = $this->actingAs($chair)
            ->get(route('evaluation.canonical-administrative.show', $competition))
            ->assertOk();
        $administrative->assertSee('Poziv je zatvoren', false);
        $administrative->assertSee('nije finalizovan', false);
        $administrative->assertDontSee('>Zatvori Poziv<', false);
        $administrative->assertDontSee('Evidentiraj Odluku', false);
        $administrative->assertDontSee('Objavi potpisani primjerak', false);
        $administrative->assertDontSee('Javni rezultat', false);

        $competition->refresh();
        $this->assertSame('completed', $competition->status);
        $this->assertSame($closedAt, $competition->closed_at->toDateTimeString());
        $this->assertSame($predlogId, CanonicalPredlog::query()->where('competition_id', $competition->id)->value('id'));
        $this->assertNull(CanonicalPredlog::query()->where('competition_id', $competition->id)->value('finalized_at'));
        $this->assertSame(0, CanonicalFormalDecision::query()->where('competition_id', $competition->id)->count());
        $this->assertSame((string) $outcomeBefore->approved_amount, (string) CanonicalOutcome::query()->findOrFail($outcomeBefore->id)->approved_amount);
        $this->assertSame((string) $outcomeBefore->decision, (string) CanonicalOutcome::query()->findOrFail($outcomeBefore->id)->decision);
        $this->assertSame((string) $rankingBeforeRow->finalized_at, (string) CanonicalFinalRanking::query()->findOrFail($rankingBeforeRow->id)->finalized_at);
        $this->assertSame($eventsAfterClose, CanonicalAdministrativeEvent::query()->where('competition_id', $competition->id)->count());
        $this->assertSame($documentsAfterClose, MmspDecisionDocument::query()->where('competition_id', $competition->id)->count());
    }

    public function test_docx_fills_every_marker_for_two_winners_and_keeps_official_parts(): void
    {
        $proposal = new MmspDecisionProposal(
            competitionId: 9,
            decisionNumber: '',
            decisionDate: '10.09.2026.',
            competitionYear: '2026',
            publishedOn: '01.08.2026.',
            endedOn: '21.08.2026.',
            firstSessionDate: '25.08.2026',
            secondSessionDate: '01.09.2026',
            thirdSessionDate: '05.09.2026',
            winners: [
                [
                    'identity' => 'Primjer DOO i Ana Anić',
                    'subsidy' => 'Subvencije za razvoj autentičnih lokalnih proizvoda/usluga',
                    'score' => '40,00',
                    'approved' => '1.000,00',
                    'total_value' => '5.000,00',
                ],
                [
                    'identity' => 'Marko Marković',
                    'subsidy' => 'Subvencije za digitalizaciju',
                    'score' => '35,00',
                    'approved' => '500,00',
                    'total_value' => '2.000,00',
                ],
            ],
            receivedCount: 4,
            incompleteCount: 1,
            appealsAccepted: 1,
            appealsRejected: 1,
            proceedCount: 3,
            awardedTotal: '1.500,00',
            callDurationDays: 20,
            participantCount: 1,
            commissionMemberCount: 4,
        );

        $path = app(MmspDecisionDocxWriter::class)->write($proposal);
        try {
            $text = $this->docxText($path);
            $this->assertStringContainsString('Primjer DOO i Ana Anić', $text);
            $this->assertStringContainsString('Marko Marković', $text);
            $this->assertStringContainsString('35,00', $text);
            $this->assertStringContainsString('500,00', $text);
            $this->assertStringContainsString('2.000,00', $text);
            $this->assertStringContainsString('1.500,00', $text);
            $this->assertStringContainsString('2 plana ulaganja', $text);
            $this->assertStringContainsString('dodjeljuju sredstva za 2 plana ulaganja', $text);
            $this->assertStringContainsString('dodijeljene subvencije', $text);
            $this->assertStringContainsString('rang-liste', $text);
            $this->assertStringContainsString('rang-listi', $text);
            $this->assertStringContainsString('rang-listu', $text);
            $this->assertStringNotContainsString('dodijeljuju sredstva', $text);
            $this->assertStringContainsString('pristigle ukupno 4 prijave', $text);
            $this->assertStringContainsString('prvobitno 1 prijava nepotpuna', $text);
            $this->assertStringContainsString('3 prijave mogu biti uzete', $text);
            $this->assertStringContainsString('Komisija je donijela odluku o prihvatanju 1 prigovora i odbijanju 1 prigovora.', $text);
            $this->assertStringContainsString('trajao 20 dana', $text);
            $this->assertStringContainsString('Podnosiocima i podnositeljkama prijava (x1)', $text);
            $this->assertStringContainsString('Članovima Komisije (x4)', $text);
            $this->assertStringNotContainsString('plan/ova', $text);
            $this->assertStringNotContainsString('je/su', $text);
            $this->assertStringNotContainsString('ppreduzetnike', $text);
            $this->assertMatchesRegularExpression('/SEKRETAR\s*_{10,30}\s*Vladan Barović/u', preg_replace('/\s+/', ' ', $text) ?? $text);
            $this->assertStringContainsString('Kotor, 10.09.2026.', $text);
            $this->assertStringNotContainsString('Broj:  XX', $text);
            $this->assertOfficialTemplatePreserved($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_zero_winners_and_many_winners_keep_the_signature_together(): void
    {
        $base = [
            'competitionId' => 9,
            'decisionNumber' => '',
            'decisionDate' => '10.09.2026.',
            'competitionYear' => '2026',
            'publishedOn' => '01.08.2026.',
            'endedOn' => '21.08.2026.',
            'firstSessionDate' => '25.08.2026',
            'secondSessionDate' => '01.09.2026',
            'thirdSessionDate' => '05.09.2026',
            'receivedCount' => 0,
            'incompleteCount' => 0,
            'appealsAccepted' => 0,
            'appealsRejected' => 0,
            'proceedCount' => 0,
            'awardedTotal' => '0,00',
            'callDurationDays' => 21,
            'participantCount' => 0,
            'commissionMemberCount' => 3,
        ];
        $none = new MmspDecisionProposal(...[...$base, 'winners' => []]);
        $emptyPath = app(MmspDecisionDocxWriter::class)->write($none);
        try {
            $text = $this->docxText($emptyPath);
            $this->assertStringContainsString('sredstva nijesu dodijeljena', $text);
            $this->assertStringNotContainsString('dodjeljuju sredstva', $text);
            $this->assertStringContainsString('dodijeljene subvencije', $text);
            $this->assertStringContainsString('nije pristigla nijedna prijava', $text);
            $this->assertStringContainsString('trajao 21 dan', $text);
            $this->assertStringContainsString('(x0)', $text);
            $this->assertStringNotContainsString('plan/ova', $text);
        } finally {
            if (is_file($emptyPath)) {
                unlink($emptyPath);
            }
        }

        $winners = [];
        foreach (range(1, 8) as $number) {
            $winners[] = [
                'identity' => 'Dobitnik '.$number,
                'subsidy' => 'Subvencije za digitalizaciju',
                'score' => '30,00',
                'approved' => '100,00',
                'total_value' => '400,00',
            ];
        }
        $many = new MmspDecisionProposal(...[...$base, 'winners' => $winners, 'receivedCount' => 25, 'proceedCount' => 25, 'awardedTotal' => '800,00', 'participantCount' => 22, 'callDurationDays' => 22]);
        $manyPath = app(MmspDecisionDocxWriter::class)->write($many);
        try {
            $text = $this->docxText($manyPath);
            $xml = $this->zipEntry($manyPath, 'word/document.xml');
            $this->assertStringContainsString('Dobitnik 8', $text);
            $this->assertStringContainsString('8 planova ulaganja', $text);
            $this->assertStringContainsString('pristiglo ukupno 25 prijava', $text);
            $this->assertStringContainsString('25 prijava može biti uzeto', $text);
            $this->assertStringContainsString('Prigovora nije bilo', $text);
            $this->assertStringContainsString('trajao 22 dana', $text);
            $this->assertStringContainsString('(x22)', $text);
            $this->assertGreaterThanOrEqual(8, substr_count($xml, '<w:keepNext'));
            $this->assertStringContainsString('Vladan Barović', $text);
        } finally {
            if (is_file($manyPath)) {
                unlink($manyPath);
            }
        }
    }

    public function test_accepted_complaint_returns_the_application_and_participants_follow_pib(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = Competition::create([
            'title' => 'Poziv MMSP brojanja '.uniqid(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 15,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-16',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        $this->cleanupCompetitionIds[] = (int) $competition->id;
        $owner = User::factory()->create([
            'email' => 'brojanje-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $sequence = 0;
        $make = function (string $pib, string $suffix) use ($competition, $owner, &$sequence): Application {
            $sequence++;

            return Application::create([
                'competition_id' => $competition->id,
                'user_id' => $owner->id,
                'applicant_type' => 'doo',
                'business_stage' => 'razvoj',
                'status' => 'submitted',
                'business_plan_name' => 'Plan '.$suffix,
                'founder_name' => 'Ana Anić',
                'doo_name' => 'Primjer DOO',
                'pib' => $pib,
                'submitted_at' => now(),
                'redni_broj' => $sequence,
            ]);
        };
        $complete = $make('81234567', 'A');
        $accepted = $make('81234567', 'B');
        $rejected = $make('81234568', 'C');
        $alsoComplete = $make('81234569', 'D');
        $chairId = $members[G::ROLE_PREDSJEDNIK]->user_id;
        $assess = function (Application $application, string $outcome, string $marker) use ($competition, $chairId): ApplicationCompletenessAssessment {
            return ApplicationCompletenessAssessment::query()->create([
                'application_id' => $application->id,
                'competition_id' => $competition->id,
                'outcome' => $outcome,
                'recorded_by_user_id' => $chairId,
                'notes' => $marker,
                'evidence_snapshot' => [],
                'evidence_fingerprint' => hash('sha256', $marker),
                'recorded_at' => now(),
            ]);
        };
        $assess($complete, ApplicationCompletenessAssessment::COMPLETE, 'a-complete');
        $original = $assess($accepted, ApplicationCompletenessAssessment::INCOMPLETE, 'b-incomplete');
        $assess($rejected, ApplicationCompletenessAssessment::INCOMPLETE, 'c-incomplete');
        $assess($alsoComplete, ApplicationCompletenessAssessment::COMPLETE, 'd-complete');
        $complain = function (Application $application, int $assessmentId, string $decision) use ($competition, $owner, $chairId): void {
            ApplicationComplaint::query()->create([
                'application_id' => $application->id,
                'competition_id' => $competition->id,
                'completeness_assessment_id' => $assessmentId,
                'submitted_by_user_id' => $owner->id,
                'body' => 'Tekst prigovora.',
                'submitted_at' => now(),
                'decision_due_at' => now()->addDays(7),
                'decision' => $decision,
                'decided_at' => now(),
                'decided_by_user_id' => $chairId,
                'decision_notes' => $decision,
            ]);
        };
        $complain($accepted, $original->id, ApplicationComplaint::ACCEPTED);
        $complain($rejected, (int) ApplicationCompletenessAssessment::query()->where('application_id', $rejected->id)->value('id'), ApplicationComplaint::REJECTED);

        $builder = app(\App\Services\Competitions\MmspDecisionProposalBuilder::class);
        $proposal = $builder->build($competition, $this->dates(), now());

        $this->assertSame(4, $proposal->receivedCount);
        $this->assertSame(2, $proposal->incompleteCount);
        $this->assertSame(1, $proposal->appealsAccepted);
        $this->assertSame(1, $proposal->appealsRejected);
        $this->assertSame(3, $proposal->proceedCount);
        $this->assertSame(3, $proposal->participantCount);
        $this->assertSame(15, $proposal->callDurationDays);
        $this->assertSame(3, $proposal->commissionMemberCount);
        $this->assertSame($complete->pib, $accepted->pib);

        $make('81234570', 'E');
        $later = $make('81234571', 'F');
        $assess($later, ApplicationCompletenessAssessment::COMPLETE, 'f-first-complete');
        $assess($later, ApplicationCompletenessAssessment::INCOMPLETE, 'f-later-incomplete');
        $again = $builder->build($competition, $this->dates(), now());

        $this->assertSame(6, $again->receivedCount);
        $this->assertSame(2, $again->incompleteCount);
        $this->assertSame(3, $again->proceedCount);
    }

    public function test_regeneration_keeps_the_previous_word_file(): void
    {
        [$members, $competition] = $this->supportedCompetition();
        $this->signAll($competition, $members);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->actingAs($chair)
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertRedirect();
        $first = MmspDecisionDocument::query()->where('competition_id', $competition->id)->firstOrFail();
        $firstBytes = Storage::disk('local')->get($first->storage_path);
        $this->assertIsString($firstBytes);
        $this->assertSame($first->content_sha256, hash('sha256', $firstBytes));

        $this->actingAs($chair)
            ->post(route('evaluation.mmsp-decision.generate', $competition), $this->dates())
            ->assertRedirect()
            ->assertSessionHas('success', 'Odluka je uspješno generisana i sačuvana.');

        $this->assertSame(2, MmspDecisionDocument::query()->where('competition_id', $competition->id)->count());
        $this->assertSame($firstBytes, Storage::disk('local')->get($first->storage_path));
        $second = MmspDecisionDocument::query()->where('competition_id', $competition->id)->orderByDesc('id')->firstOrFail();
        $this->assertNotSame($first->storage_path, $second->storage_path);
        $this->assertSame($first->ranking_sha256, $second->ranking_sha256);
        $this->actingAs($chair)->get(route('evaluation.mmsp-decision.download', $competition))->assertOk();
    }

    /**
     * @return array{0: array<string, CommissionMember>, 1: Competition, 2: Application}
     */
    private function supportedCompetition(): array
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = Competition::create([
            'title' => 'Poziv MMSP '.uniqid(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        $this->cleanupCompetitionIds[] = (int) $competition->id;
        $this->confirmOrdinarySession($competition);

        $owner = User::factory()->create([
            'email' => 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'doo',
            'business_stage' => 'razvoj',
            'status' => 'submitted',
            'business_plan_name' => 'Plan ulaganja',
            'founder_name' => 'Ana Anić',
            'doo_name' => 'Primjer DOO',
            'pib' => '81234567',
            'submitted_at' => now(),
            'redni_broj' => 1,
        ]);
        CanonicalApplicantForm::query()->create([
            'application_id' => $application->id,
            'legal_form' => CanonicalLegalForm::DRUSTVO,
            'subsidy_type' => T::AUTHENTIC_LOCAL,
            'person_name' => 'Ana Anić',
            'company_name' => 'Primjer DOO',
            'address' => 'Njegoševa 1',
            'activity_code' => '56.10',
            'employee_count' => 2,
            'bank_account' => '510-0000000000001-11',
            'phone' => '067000000',
            'email' => 'p1@example.test',
            'registration_date' => '2020-03-15',
            'accuracy_confirmed_at' => now(),
        ]);
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
            'subsidy_type' => T::AUTHENTIC_LOCAL,
            'beneficiary_pib' => '81234567',
            'beneficiary_slot' => 1,
        ]);
        (new CanonicalInvestmentPlan)->forceFill([
            'application_id' => $application->id,
            'subsidy_type' => T::AUTHENTIC_LOCAL,
            'total_investment' => '5000.00',
            'own_contribution' => '0.00',
            'expected_subsidy' => '5000.00',
            'expense_lines' => [['cost_type' => 'oprema', 'description' => 'Oprema', 'amount' => '5000.00']],
            'frozen_at' => now(),
        ])->save();

        $this->assertTrue(
            app(CompletenessAssessmentService::class)
                ->record($application, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::COMPLETE)
                ->succeeded
        );
        $this->confirmSession($competition, $members, G::PURPOSE_INTERVIEW);

        $scoring = app(CanonicalIndividualScoringService::class);
        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $seat => $role) {
            $scoring->recordCanonicalFinalScore($application->fresh(), $members[$role]->user, $this->criteriaSumming(40), null, true);
        }
        $this->assertTrue($scoring->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->confirmSession($competition, $members, G::PURPOSE_FINAL_DECISION);

        $draft = app(CanonicalOutcomeService::class)->recordDraft($competition->fresh(), $members[G::ROLE_PREDSJEDNIK]->user, $application->id, [
            'decision' => 'support',
            'amount' => '1000',
        ]);
        $this->assertTrue($draft->succeeded, (string) $draft->error);
        $confirm = app(CanonicalOutcomeService::class)->confirm($competition->fresh(), $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($confirm->succeeded, (string) $confirm->error);

        return [$members, $competition->fresh(), $application->fresh()];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function signAll(Competition $competition, array $members): void
    {
        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $role) {
            $result = app(CanonicalOutcomeService::class)->sign($competition, $members[$role]->user);
            $this->assertTrue($result->succeeded, (string) $result->error);
        }
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmSession(Competition $competition, array $members, string $purpose): void
    {
        $service = app(CanonicalCommissionSessionService::class);
        $result = $service->record(
            $competition,
            $members[G::ROLE_PREDSJEDNIK]->user,
            $purpose,
            $purpose === G::PURPOSE_FINAL_DECISION ? CarbonImmutable::now() : now(),
            [
                $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
                $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
                $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
            ],
        );
        $this->assertTrue($result->succeeded, (string) $result->error);
        $session = CommissionSession::query()->findOrFail($result->recordId);
        $confirm = $service->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($confirm->succeeded, (string) $confirm->error);
    }

    /**
     * @return array<string, int>
     */
    private function criteriaSumming(int $total): array
    {
        $base = intdiv($total, 10);
        $remainder = $total % 10;
        $criteria = [];
        foreach (range(1, 10) as $criterion) {
            $criteria["criterion_{$criterion}"] = $base + ($criterion <= $remainder ? 1 : 0);
        }

        return $criteria;
    }

    /**
     * @return array{first_session_date: string, second_session_date: string, third_session_date: string}
     */
    private function dates(): array
    {
        return [
            'first_session_date' => '2026-08-25',
            'second_session_date' => '2026-09-01',
            'third_session_date' => '2026-09-05',
        ];
    }

    private function docxText(string $path): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertIsString($xml);

        return strip_tags($xml);
    }

    private function assertOfficialTemplatePreserved(string $generatedPath): void
    {
        $template = resource_path('templates/mmsp/odluka-o-dodjeli-subvencija.docx');
        $entries = $this->zipNames($template);
        $this->assertSame($entries, $this->zipNames($generatedPath));
        foreach ($entries as $entry) {
            if ($entry === 'word/document.xml') {
                continue;
            }
            $this->assertSame($this->zipEntry($template, $entry), $this->zipEntry($generatedPath, $entry), $entry);
        }
        $this->assertSame($this->sectPr($template), $this->sectPr($generatedPath));

        $templateText = $this->docxText($template);
        $this->assertStringContainsString('konačne rang-liste', $templateText);
        $this->assertStringContainsString('dodijeljene subvencije', $templateText);
        $this->assertStringContainsString('dodjeljuju sredstva', $templateText);

        $text = $this->docxText($generatedPath);
        foreach ([
            'Član 1',
            'Član 2',
            'Međusobna prava i obaveze utvrdiće se posebnim aktom',
            'Član 3',
            'Dodijeljene subvencije utvrđene članom 1',
            'Član 4',
            'Ova Odluka stupa na snagu danom donošenja.',
            'Član 5',
            'Odluka se dostavlja učesnicima',
            'SEKRETAR',
            'Vladan Barović',
        ] as $phrase) {
            $this->assertStringContainsString($phrase, $text, $phrase);
        }
        $this->assertStringNotContainsString('XX', $text);
        $this->assertStringNotContainsString('xx', $text);
    }

    private function zipEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, $path);
        $contents = $zip->getFromName($entry);
        $zip->close();
        $this->assertIsString($contents, $entry);

        return $contents;
    }

    /**
     * @return list<string>
     */
    private function zipNames(string $path): array
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, $path);
        $names = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (is_string($name)) {
                $names[] = $name;
            }
        }
        $zip->close();
        sort($names);

        return $names;
    }

    private function sectPr(string $path): string
    {
        $xml = $this->zipEntry($path, 'word/document.xml');
        $count = preg_match_all('/<w:sectPr\b.*?<\/w:sectPr>/s', $xml, $matches);
        $this->assertGreaterThan(0, $count);

        return implode("\n", $matches[0]);
    }
}
