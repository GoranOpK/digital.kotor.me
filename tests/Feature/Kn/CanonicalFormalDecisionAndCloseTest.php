<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalFormalDecision;
use App\Models\CanonicalInvestmentPlan;
use App\Models\CanonicalOutcome;
use App\Models\CanonicalPredlog;
use App\Models\CanonicalSecretariatHandoff;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\FinalApplicationClaim;
use App\Models\Notice;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\ComplaintService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate as Gate;
use App\Services\Competitions\Decision\CanonicalCloseService;
use App\Services\Competitions\Decision\CanonicalFormalDecisionService;
use App\Services\Competitions\Decision\CanonicalResultPublicationService;
use App\Services\Competitions\Decision\CanonicalSecretariatHandoffService;
use App\Services\Competitions\FinalSubmissionService;
use App\Services\Competitions\Outcome\CanonicalOutcomeService;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\Outcome\CanonicalTieResolutionService;
use App\Services\Competitions\ProcessWriteResult;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType as T;
use App\Support\CompetitionAnnualInstance;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-009 — canonical MMSP finish line: Predlog finalization, Secretariat handoff,
 * formal Odluka record + signed copy, one-time publication, close/archive and post-close immutability.
 */
class CanonicalFormalDecisionAndCloseTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    private const STAGES = ['signed', 'finalized', 'handed', 'recorded', 'attached', 'published', 'closed'];

    private int $pibCounter = 0;

    private ?User $secretariatUser = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // A — Predlog readiness

    public function test_a_predlog_finalization_requires_confirmed_outcome_three_signatures_valid_chair_and_commission(): void
    {
        [, $members, $competition, $apps] = $this->rankedCompetition($this->specs());
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $predlogs = app(CanonicalPredlogService::class);

        $this->assertSame(CanonicalPredlogService::NOT_CONFIRMED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        $this->draftAll($competition, $members, $apps);
        $this->assertSame(CanonicalPredlogService::NOT_CONFIRMED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        $this->ok(app(CanonicalOutcomeService::class)->confirm($competition, $chair));
        $this->assertSame(CanonicalPredlogService::NOT_SIGNED_MESSAGE, $predlogs->finalize($competition, $chair)->error);

        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR] as $role) {
            $this->ok(app(CanonicalOutcomeService::class)->sign($competition, $members[$role]->user));
        }
        $this->assertSame(CanonicalPredlogService::NOT_SIGNED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        $this->assertSame(0, CanonicalPredlog::query()->count());

        $this->ok(app(CanonicalOutcomeService::class)->sign($competition, $members[G::ROLE_SPOLJASNJI]->user));
        $this->assertNull($predlogs->finalizationBlockReason($competition->fresh()));

        $this->assertSame(Gate::CHAIR_ONLY_MESSAGE, $predlogs->finalize($competition, $members[G::ROLE_SEKRETAR]->user)->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $predlogs->finalize($competition, $this->secretariat())->error);

        DB::table('commission_members')->where('id', $members[G::ROLE_SPOLJASNJI]->id)->update(['status' => 'inactive']);
        $this->assertSame(G::COMMISSION_INCOMPLETE_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        DB::table('commission_members')->where('id', $members[G::ROLE_SPOLJASNJI]->id)->update(['status' => 'active']);

        $this->assertNull(CanonicalPredlog::query()->firstOrFail()->finalized_at);
        $this->ok($predlogs->finalize($competition->fresh(), $chair));
    }

    // B — finalization snapshot and immutability

    public function test_b_finalization_preserves_snapshot_ranks_shared_ranks_amounts_types_and_one_support(): void
    {
        $ctx = $this->flow('signed');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        $predlogs = app(CanonicalPredlogService::class);
        $prepared = CanonicalPredlog::query()->firstOrFail();

        $this->ok($predlogs->finalize($competition, $chair));

        $predlog = $prepared->fresh();
        $this->assertNotNull($predlog->finalized_at);
        $this->assertSame($chair->id, (int) $predlog->finalized_by_user_id);
        $this->assertSame($prepared->fingerprint, $predlog->fingerprint);
        $this->assertTrue($predlog->integrityIntact());

        $payload = $predlog->payload;
        $this->assertSame(CanonicalPredlogService::SCHEMA, $payload['schema']);
        $this->assertFalse($payload['is_public']);
        $this->assertFalse($payload['is_odluka']);
        $this->assertSame('UP-01-332/26', $payload['competition']['reference_number']);
        $this->assertSame('10000.00', $payload['competition']['budget']);
        $this->assertSame((int) $competition->commission_id, $payload['commission']['id']);
        $this->assertNotNull($payload['commission']['name']);
        $this->assertSame([1, 1, 3], array_column($payload['rows'], 'rank'));
        $this->assertSame([true, true, false], array_column($payload['rows'], 'shared_rank'));
        $this->assertSame(['800.00', '1500.00', null], array_column($payload['rows'], 'approved_amount'));
        $this->assertSame([T::AUTHENTIC_LOCAL, T::INNOVATIVE], array_slice(array_column($payload['rows'], 'subsidy_type'), 0, 2));
        $this->assertSame([CanonicalOutcome::SUPPORT, CanonicalOutcome::SUPPORT, CanonicalOutcome::REJECT], array_column($payload['rows'], 'decision'));
        $this->assertSame('7700.00', $payload['final_ranking']['residual_unallocated']);
        $supportedPibs = array_column(array_filter($payload['rows'], fn ($row) => $row['decision'] === CanonicalOutcome::SUPPORT), 'pib');
        $this->assertSame($supportedPibs, array_values(array_unique($supportedPibs)));
        $this->assertCount(3, $payload['signatures']);

        $event = CanonicalAdministrativeEvent::query()->where('action', CanonicalAdministrativeEvent::PREDLOG_FINALIZED)->firstOrFail();
        $this->assertSame($chair->id, (int) $event->actor_user_id);
        $this->assertSame($predlog->id, (int) $event->subject_id);
        $this->assertSame($competition->id, (int) $event->competition_id);

        $this->assertSame(CanonicalPredlogService::ALREADY_FINALIZED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
    }

    public function test_b_finalization_fails_closed_when_source_facts_or_snapshot_drift(): void
    {
        $ctx = $this->flow('signed');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        [$first] = $ctx['apps'];
        $predlogs = app(CanonicalPredlogService::class);

        $calc = DB::table('applications')->where('id', $first->id)->value('final_score_calc');
        DB::table('applications')->where('id', $first->id)->update(['final_score_calc' => '44.0000000000']);
        $this->assertSame(CanonicalPredlogService::SOURCE_CHANGED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        DB::table('applications')->where('id', $first->id)->update(['final_score_calc' => $calc]);

        DB::table('canonical_outcomes')->where('application_id', $first->id)->update(['approved_amount' => '900.00']);
        $this->assertSame(CanonicalPredlogService::SOURCE_CHANGED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        DB::table('canonical_outcomes')->where('application_id', $first->id)->update(['approved_amount' => '800.00']);

        DB::table('canonical_investment_plans')->where('application_id', $first->id)->update(['subsidy_type' => T::INNOVATIVE]);
        $this->assertSame(CanonicalPredlogService::SOURCE_CHANGED_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        DB::table('canonical_investment_plans')->where('application_id', $first->id)->update(['subsidy_type' => T::AUTHENTIC_LOCAL]);

        $fingerprint = DB::table('canonical_predlogs')->value('fingerprint');
        DB::table('canonical_predlogs')->update(['fingerprint' => str_repeat('a', 64)]);
        $this->assertSame(CanonicalPredlogService::INTEGRITY_MESSAGE, $predlogs->finalize($competition, $chair)->error);
        DB::table('canonical_predlogs')->update(['fingerprint' => $fingerprint]);

        $this->assertNull(CanonicalPredlog::query()->firstOrFail()->finalized_at);
        $this->assertSame(0, CanonicalAdministrativeEvent::query()->whereNotIn('action', CanonicalAdministrativeEvent::COMMISSION_ACTIONS)->count());
        $this->ok($predlogs->finalize($competition, $chair));
    }

    public function test_b_finalized_predlog_scores_ranks_outcomes_and_amounts_are_immutable(): void
    {
        $ctx = $this->flow('finalized');
        [$first] = $ctx['apps'];
        $predlog = CanonicalPredlog::query()->firstOrFail();
        $fingerprint = $predlog->fingerprint;

        $this->assertThrows(fn () => $predlog->update(['fingerprint' => str_repeat('0', 64)]), LogicException::class);
        $this->assertThrows(fn () => $predlog->fresh()->forceFill(['finalized_at' => now()->addDay(), 'finalized_by_user_id' => $this->secretariat()->id])->save(), LogicException::class);
        $this->assertThrows(fn () => $predlog->fresh()->delete(), LogicException::class);
        $this->assertThrows(fn () => $first->fresh()->forceFill(['ranking_position' => 2])->save(), LogicException::class);
        $this->assertThrows(fn () => $first->fresh()->forceFill(['approved_amount' => '2000.00'])->save(), LogicException::class);
        $this->assertThrows(fn () => $first->fresh()->forceFill(['final_score_calc' => '10.0000000000'])->save(), LogicException::class);
        $this->assertThrows(fn () => CanonicalOutcome::query()->where('application_id', $first->id)->firstOrFail()->update(['approved_amount' => '1.00']), LogicException::class);

        $this->assertSame($fingerprint, CanonicalPredlog::query()->firstOrFail()->fingerprint);
        $this->assertTrue(CanonicalPredlog::query()->firstOrFail()->integrityIntact());
        $this->assertSame(1, (int) $first->fresh()->ranking_position);
    }

    // C — Secretariat handoff

    public function test_c_handoff_requires_finalized_predlog_records_fields_blocks_duplicates_and_publishes_nothing(): void
    {
        $ctx = $this->flow('signed');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        $handoffs = app(CanonicalSecretariatHandoffService::class);

        $this->assertSame(CanonicalPredlogService::NOT_FINALIZED_MESSAGE, $handoffs->handOff($competition, $chair, 'Sekretarijat')->error);

        $this->ok(app(CanonicalPredlogService::class)->finalize($competition, $chair));

        $this->assertSame(CanonicalSecretariatHandoffService::RECIPIENT_REQUIRED_MESSAGE, $handoffs->handOff($competition, $chair, '   ')->error);
        $this->assertSame(Gate::CHAIR_ONLY_MESSAGE, $handoffs->handOff($competition, $ctx['members'][G::ROLE_SEKRETAR]->user, 'Sekretarijat')->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $handoffs->handOff($competition, $this->secretariat(), 'Sekretarijat')->error);

        $result = $handoffs->handOff($competition, $chair, ' Sekretarijat za razvoj preduzetništva ', 'Akt 01-55/26', 'Predaja finalizovanog Predloga');
        $this->ok($result);

        $handoff = CanonicalSecretariatHandoff::query()->findOrFail($result->recordId);
        $this->assertSame('Sekretarijat za razvoj preduzetništva', $handoff->recipient);
        $this->assertSame('Akt 01-55/26', $handoff->reference);
        $this->assertSame('Predaja finalizovanog Predloga', $handoff->note);
        $this->assertSame($chair->id, (int) $handoff->handed_off_by_user_id);
        $this->assertNotNull($handoff->handed_off_at);
        $this->assertSame(CanonicalPredlog::query()->firstOrFail()->id, (int) $handoff->predlog_id);
        $this->assertTrue(CanonicalAdministrativeEvent::query()->where('action', CanonicalAdministrativeEvent::SECRETARIAT_HANDOFF)->where('subject_id', $handoff->id)->exists());

        $this->assertSame(CanonicalSecretariatHandoffService::DUPLICATE_MESSAGE, $handoffs->handOff($competition, $chair, 'Drugi Sekretarijat')->error);
        $this->assertSame(1, CanonicalSecretariatHandoff::query()->count());

        $this->assertSame(0, Notice::query()->count());
        $this->assertSame(0, CanonicalFormalDecision::query()->count());
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertNull(app(CanonicalResultPublicationService::class)->publicResult($competition));

        $this->assertThrows(fn () => $handoff->update(['recipient' => 'Izmijenjeno']), LogicException::class);
        $this->assertThrows(fn () => $handoff->delete(), LogicException::class);
    }

    // D — formal Odluka record and signed copy

    public function test_d_formal_decision_requires_handoff_number_date_and_is_not_replaceable(): void
    {
        $ctx = $this->flow('finalized');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        $admin = $this->secretariat();
        $decisions = app(CanonicalFormalDecisionService::class);

        $this->assertSame(CanonicalFormalDecisionService::HANDOFF_REQUIRED_MESSAGE, $decisions->record($competition, $admin, '01-123/26', '2026-09-10')->error);

        $this->ok(app(CanonicalSecretariatHandoffService::class)->handOff($competition, $chair, 'Sekretarijat'));

        $this->assertSame(Gate::SECRETARIAT_ONLY_MESSAGE, $decisions->record($competition, $chair, '01-123/26', '2026-09-10')->error);
        $this->assertSame(CanonicalFormalDecisionService::NUMBER_REQUIRED_MESSAGE, $decisions->record($competition, $admin, '  ', '2026-09-10')->error);
        $this->assertSame(CanonicalFormalDecisionService::DATE_REQUIRED_MESSAGE, $decisions->record($competition, $admin, '01-123/26', null)->error);
        $this->assertSame(CanonicalFormalDecisionService::DATE_REQUIRED_MESSAGE, $decisions->record($competition, $admin, '01-123/26', '2026-02-30')->error);
        $this->assertSame(CanonicalFormalDecisionService::DATE_FUTURE_MESSAGE, $decisions->record($competition, $admin, '01-123/26', '2026-09-11')->error);
        $this->assertSame(CanonicalFormalDecisionService::DATE_BEFORE_HANDOFF_MESSAGE, $decisions->record($competition, $admin, '01-123/26', '2026-09-01')->error);
        $this->assertSame(0, CanonicalFormalDecision::query()->count());

        $result = $decisions->record($competition, $admin, ' 01-123/26 ', '2026-09-10', 'Predsjednik Opštine', 'Interna napomena Sekretarijata');
        $this->ok($result);

        $decision = CanonicalFormalDecision::query()->findOrFail($result->recordId);
        $this->assertSame('01-123/26', $decision->decision_number);
        $this->assertSame('2026-09-10', $decision->decision_date->toDateString());
        $this->assertSame(CanonicalFormalDecision::SLOT, $decision->publication_slot);
        $this->assertSame($admin->id, (int) $decision->recorded_by_user_id);
        $this->assertSame(CanonicalPredlog::query()->firstOrFail()->id, (int) $decision->predlog_id);
        $this->assertSame(CanonicalSecretariatHandoff::query()->firstOrFail()->id, (int) $decision->handoff_id);
        $this->assertNull($decision->official_decision_copy_id);
        $this->assertNull($decision->published_at);
        $this->assertTrue(CanonicalAdministrativeEvent::query()->where('action', CanonicalAdministrativeEvent::FORMAL_DECISION_RECORDED)->where('subject_id', $decision->id)->exists());

        $this->assertSame(CanonicalFormalDecisionService::DUPLICATE_MESSAGE, $decisions->record($competition, $admin, '01-999/26', '2026-09-10')->error);
        $this->assertSame(1, CanonicalFormalDecision::query()->count());
        $this->assertThrows(fn () => $decision->update(['decision_number' => '01-999/26']), LogicException::class);
        $this->assertThrows(fn () => $decision->delete(), LogicException::class);
        $this->assertSame(0, Notice::query()->count());
    }

    public function test_d_signed_copy_is_required_pdf_attached_once_and_bound_to_the_competition(): void
    {
        $ctx = $this->flow('recorded');
        $competition = $ctx['competition'];
        $admin = $this->secretariat();
        $decisions = app(CanonicalFormalDecisionService::class);
        $publication = app(CanonicalResultPublicationService::class);

        $this->assertSame(CanonicalResultPublicationService::SIGNED_COPY_REQUIRED_MESSAGE, $publication->publish($competition, $admin)->error);

        $this->assertSame(CanonicalFormalDecisionService::PDF_REQUIRED_MESSAGE,
            $decisions->attachSignedCopy($competition, $admin, UploadedFile::fake()->create('odluka.txt', 10, 'text/plain'))->error);
        $this->assertSame(Gate::SECRETARIAT_ONLY_MESSAGE,
            $decisions->attachSignedCopy($competition, $ctx['members'][G::ROLE_PREDSJEDNIK]->user, $this->pdf())->error);

        $other = $this->rankedCompetition([['scores' => [40, 40, 40]]])[2];
        $this->assertSame(CanonicalFormalDecisionService::DECISION_REQUIRED_MESSAGE, $decisions->attachSignedCopy($other, $admin, $this->pdf())->error);
        $this->assertSame(CanonicalFormalDecisionService::DECISION_REQUIRED_MESSAGE, $publication->publish($other, $admin)->error);

        $result = $decisions->attachSignedCopy($competition, $admin, $this->pdf());
        $this->ok($result);
        $copy = CompetitionOfficialDecisionCopy::query()->findOrFail($result->recordId);
        $this->assertSame($competition->id, (int) $copy->competition_id);
        $this->assertStringStartsWith('competitions/'.$competition->id.'/official-decisions/', $copy->storage_path);
        Storage::disk('local')->assertExists($copy->storage_path);
        $this->assertSame('Odluka br. 01-123/26 od 10.09.2026', $copy->business_title);

        $decision = CanonicalFormalDecision::query()->firstOrFail();
        $this->assertSame($copy->id, (int) $decision->official_decision_copy_id);
        $this->assertSame($admin->id, (int) $decision->attached_by_user_id);

        $this->assertSame(CanonicalFormalDecisionService::COPY_ALREADY_ATTACHED_MESSAGE, $decisions->attachSignedCopy($competition, $admin, $this->pdf())->error);
        $this->assertSame(1, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertThrows(fn () => $decision->fresh()->forceFill(['official_decision_copy_id' => null])->save(), LogicException::class);

        DB::table('competition_official_decision_copies')->where('id', $copy->id)->update(['competition_id' => $other->id]);
        $this->assertSame(CanonicalResultPublicationService::SIGNED_COPY_INVALID_MESSAGE, $publication->publish($competition, $admin)->error);
        DB::table('competition_official_decision_copies')->where('id', $copy->id)->update(['competition_id' => $competition->id]);

        Storage::disk('local')->delete($copy->storage_path);
        $this->assertSame(CanonicalResultPublicationService::SIGNED_COPY_INVALID_MESSAGE, $publication->publish($competition, $admin)->error);
        $this->assertSame(0, Notice::query()->count());
    }

    // E — publication exactly once

    public function test_e_publication_requires_signed_odluka_and_happens_exactly_once(): void
    {
        $ctx = $this->flow('attached');
        $competition = $ctx['competition'];
        $admin = $this->secretariat();
        $publication = app(CanonicalResultPublicationService::class);

        $this->assertSame(Gate::SECRETARIAT_ONLY_MESSAGE, $publication->publish($competition, $ctx['members'][G::ROLE_PREDSJEDNIK]->user)->error);

        $fingerprint = DB::table('canonical_predlogs')->value('fingerprint');
        DB::table('canonical_predlogs')->update(['fingerprint' => str_repeat('b', 64)]);
        $this->assertSame(CanonicalPredlogService::INTEGRITY_MESSAGE, $publication->publish($competition, $admin)->error);
        DB::table('canonical_predlogs')->update(['fingerprint' => $fingerprint]);

        $result = $publication->publish($competition, $admin);
        $this->ok($result);

        $decision = CanonicalFormalDecision::query()->firstOrFail();
        $notice = Notice::query()->findOrFail($result->recordId);
        $this->assertTrue($notice->publicly_available);
        $this->assertSame(CanonicalResultPublicationService::NOTICE_SOURCE_TYPE, $notice->source_type);
        $this->assertSame(CanonicalResultPublicationService::NOTICE_DELIVERY, $notice->content_delivery);
        $this->assertSame($competition->id, (int) $notice->source_id);
        $this->assertSame((int) $decision->official_decision_copy_id, (int) $notice->source_object_id);
        $this->assertSame('Odluka br. 01-123/26 od 10.09.2026', $notice->title);
        $this->assertSame($notice->id, (int) $decision->published_notice_id);
        $this->assertSame($admin->id, (int) $decision->published_by_user_id);
        $this->assertNotNull($decision->published_at);
        $this->assertTrue(CanonicalAdministrativeEvent::query()->where('action', CanonicalAdministrativeEvent::RESULT_PUBLISHED)->where('subject_id', $decision->id)->exists());

        $this->assertSame(CanonicalResultPublicationService::ALREADY_PUBLISHED_MESSAGE, $publication->publish($competition, $admin)->error);
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame(0, Notice::query()->where('content_delivery', 'competition_decision_html')->count());
        $this->assertThrows(fn () => $decision->fresh()->forceFill(['published_at' => now()->addDay()])->save(), LogicException::class);
    }

    public function test_e_test_preview_publication_is_audited_but_never_public_and_can_close(): void
    {
        $ctx = $this->flow('attached');
        $competition = $ctx['competition'];
        $admin = $this->secretariat();
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;

        DB::table('competitions')->where('id', $competition->id)->update([
            'status' => 'draft',
            'is_test_preview' => true,
            'start_date' => now()->addDay()->toDateString(),
            'canonical_test_applications_closed_at' => now(),
        ]);
        $competition = $competition->fresh();

        $this->assertTrue($competition->isCanonicalTestApplicationPhaseClosed());
        $this->assertTrue($competition->deadline->isFuture());

        $publication = app(CanonicalResultPublicationService::class);
        $this->ok($publication->publish($competition, $admin));

        $decision = CanonicalFormalDecision::query()->firstOrFail();
        $copy = CompetitionOfficialDecisionCopy::query()->firstOrFail();

        $this->assertNull($decision->published_notice_id);
        $this->assertNull($decision->published_at);
        $this->assertNull($copy->business_published_on);
        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($publication->publicResult($competition));
        $this->get(route('competitions.canonical-result', $competition))->assertNotFound();

        $event = CanonicalAdministrativeEvent::query()
            ->where('competition_id', $competition->id)
            ->where('action', CanonicalAdministrativeEvent::RESULT_TEST_PUBLISHED)
            ->firstOrFail();
        $this->assertTrue((bool) ($event->payload['test_preview'] ?? false));
        $this->assertSame((int) $copy->id, (int) ($event->payload['official_decision_copy_id'] ?? 0));

        $this->assertSame(
            CanonicalResultPublicationService::TEST_ALREADY_PUBLISHED_MESSAGE,
            $publication->publish($competition, $admin)->error,
        );
        $this->assertNull($publication->publishedBlockReason($competition));

        $this->ok(app(CanonicalCloseService::class)->close($competition, $chair));
        $this->assertSame('completed', $competition->fresh()->status);
        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($publication->publicResult($competition->fresh()));
    }

    public function test_e_generic_official_decision_actions_are_closed_for_canonical(): void
    {
        $ctx = $this->flow('published');
        $competition = $ctx['competition'];
        $admin = $this->secretariat();
        $copy = CompetitionOfficialDecisionCopy::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.competitions.official-decision.store', $competition), [
            'official_decision_copy' => $this->pdf(), 'business_title' => 'Zamjena',
        ])->assertForbidden();
        foreach (['publish', 'correct', 'unpublish', 'republish', 'permanent-delete', 'update-metadata'] as $action) {
            $this->actingAs($admin)->post(route('admin.competitions.official-decision.'.$action, [$competition, $copy]), [
                'business_title' => 'Izmjena', 'business_published_on' => '2026-09-10',
            ])->assertForbidden();
        }

        $this->assertSame(1, Notice::query()->where('publicly_available', true)->count());
        $this->assertSame(1, CompetitionOfficialDecisionCopy::query()->count());
        Storage::disk('local')->assertExists($copy->fresh()->storage_path);
    }

    // F — public privacy

    public function test_f_public_result_shows_only_the_signed_odluka_and_no_internal_data(): void
    {
        $ctx = $this->flow('attached');
        $competition = $ctx['competition'];

        $this->get(route('competitions.canonical-result', $competition))->assertNotFound();

        $this->ok(app(CanonicalResultPublicationService::class)->publish($competition, $this->secretariat()));
        $result = app(CanonicalResultPublicationService::class)->publicResult($competition->fresh());
        $this->assertSame(['competition_title', 'competition_year', 'decision_number', 'decision_date', 'published_at', 'signed_copy_url'], array_keys($result));

        $response = $this->get(route('competitions.canonical-result', $competition))->assertOk()
            ->assertSee('01-123/26')
            ->assertSee('10.09.2026')
            ->assertSee($competition->title)
            ->assertSee('Potpisani primjerak Odluke');

        $predlog = CanonicalPredlog::query()->firstOrFail();
        foreach ($predlog->payload['rows'] as $row) {
            $response->assertDontSee($row['pib']);
            $response->assertDontSee($row['applicant']);
            $response->assertDontSee($row['company']);
            $response->assertDontSee($row['business_plan_name']);
        }
        $response->assertDontSee('45.00')
            ->assertDontSee($predlog->fingerprint)
            ->assertDontSee('Interna napomena')
            ->assertDontSee('Obrazloženje')
            ->assertDontSee('Akt 01-55/26');

        $notice = Notice::query()->firstOrFail();
        $this->get(route('notices.public-content', $notice))->assertOk();

        $html = Notice::query()->create([
            'title' => 'Generisana odluka', 'visible_in_active_panel' => true, 'publicly_available' => true,
            'source_type' => 'competition_decision', 'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html', 'published_at' => now(),
        ]);
        $this->get(route('notices.public-content', $html))->assertNotFound();

        $this->assertContains($this->get(route('evaluation.canonical-administrative.show', $competition))->status(), [302, 403]);
        $this->actingAs($this->applicant())->get(route('evaluation.canonical-administrative.show', $competition))->assertForbidden();
        $this->actingAs($this->applicant())->get(route('admin.competitions.canonical-decision.show', $competition))->assertForbidden();
    }

    // G — close gates

    public function test_g_close_requires_every_prior_step_including_publication(): void
    {
        $ctx = $this->flow('signed');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        $closer = app(CanonicalCloseService::class);

        $expected = [
            'signed' => CanonicalPredlogService::NOT_FINALIZED_MESSAGE,
            'finalized' => CanonicalCloseService::HANDOFF_REQUIRED_MESSAGE,
            'handed' => CanonicalFormalDecisionService::DECISION_REQUIRED_MESSAGE,
            'recorded' => 'Zatvaranje zahtijeva objavljen potpisani primjerak formalne Odluke.',
            'attached' => 'Zatvaranje zahtijeva objavljen potpisani primjerak formalne Odluke.',
        ];
        foreach ($expected as $stage => $message) {
            if ($stage !== 'signed') {
                $this->advance($stage, $ctx);
            }
            $this->assertSame($message, $closer->close($competition->fresh(), $chair)->error, $stage);
            $this->assertSame('published', $competition->fresh()->status, $stage);
        }

        $this->advance('published', $ctx);

        $this->assertSame(Gate::CHAIR_ONLY_MESSAGE, $closer->close($competition->fresh(), $ctx['members'][G::ROLE_SEKRETAR]->user)->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $closer->close($competition->fresh(), $this->secretariat())->error);

        $notice = Notice::query()->firstOrFail();
        DB::table('notices')->where('id', $notice->id)->update(['publicly_available' => false]);
        $this->assertSame('Objava potpisanog primjerka formalne Odluke nije javno dostupna.', $closer->close($competition->fresh(), $chair)->error);
        DB::table('notices')->where('id', $notice->id)->update(['publicly_available' => true]);

        DB::table('competitions')->where('id', $competition->id)->update(['start_date' => '2026-09-05']);
        $this->assertSame(CanonicalCloseService::DEADLINE_MESSAGE, $closer->close($competition->fresh(), $chair)->error);
        DB::table('competitions')->where('id', $competition->id)->update(['start_date' => '2026-08-01']);

        $ranksBefore = Application::query()->where('competition_id', $competition->id)->orderBy('id')->pluck('ranking_position', 'id')->all();
        $this->ok($closer->close($competition->fresh(), $chair));

        $closed = $competition->fresh();
        $this->assertSame('completed', $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame($ranksBefore, Application::query()->where('competition_id', $competition->id)->orderBy('id')->pluck('ranking_position', 'id')->all());
        $this->assertTrue(CanonicalAdministrativeEvent::query()->where('action', CanonicalAdministrativeEvent::COMPETITION_CLOSED)->where('actor_user_id', $chair->id)->exists());
        $this->assertSame(
            [CanonicalAdministrativeEvent::PREDLOG_FINALIZED, CanonicalAdministrativeEvent::SECRETARIAT_HANDOFF, CanonicalAdministrativeEvent::FORMAL_DECISION_RECORDED,
                CanonicalAdministrativeEvent::SIGNED_COPY_ATTACHED, CanonicalAdministrativeEvent::RESULT_PUBLISHED, CanonicalAdministrativeEvent::COMPETITION_CLOSED],
            CanonicalAdministrativeEvent::query()->whereNotIn('action', CanonicalAdministrativeEvent::COMMISSION_ACTIONS)->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_g_generic_close_and_edit_cannot_bypass_canonical_close(): void
    {
        $ctx = $this->flow('attached');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        $ranksBefore = Application::query()->where('competition_id', $competition->id)->orderBy('id')->pluck('ranking_position', 'id')->all();

        $this->actingAs($chair)->post(route('admin.competitions.close', $competition))->assertSessionHasErrors('error');
        $this->assertSame('published', $competition->fresh()->status);

        $this->actingAs($this->secretariat())->put(route('admin.competitions.update', $competition), [
            'title' => $competition->title, 'type' => self::CANONICAL, 'up_number' => 'UP-01-332/26',
            'year' => 2026, 'budget' => 10000, 'status' => 'completed', 'commission_id' => $competition->commission_id,
        ])->assertSessionHasErrors('status');
        $this->assertSame('published', $competition->fresh()->status);

        $this->advance('published', $ctx);
        $this->actingAs($chair)->post(route('admin.competitions.close', $competition))->assertSessionHasNoErrors();
        $this->assertSame('completed', $competition->fresh()->status);
        $this->assertSame($ranksBefore, Application::query()->where('competition_id', $competition->id)->orderBy('id')->pluck('ranking_position', 'id')->all());
    }

    // H — post-close immutability

    public function test_h_after_close_every_process_write_is_blocked(): void
    {
        $ctx = $this->flow('closed');
        $competition = $ctx['competition'];
        $members = $ctx['members'];
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $admin = $this->secretariat();
        [$first, $second, $third] = array_map(fn (Application $app) => $app->fresh(), $ctx['apps']);

        $this->assertSame(Gate::CLOSED_MESSAGE, app(CanonicalCloseService::class)->close($competition, $chair)->error);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(CanonicalPredlogService::class)->finalize($competition, $chair)->error);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(CanonicalSecretariatHandoffService::class)->handOff($competition, $chair, 'Sekretarijat')->error);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(CanonicalFormalDecisionService::class)->record($competition, $admin, '02-1/26', '2026-09-10')->error);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(CanonicalFormalDecisionService::class)->attachSignedCopy($competition, $admin, $this->pdf())->error);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(CanonicalResultPublicationService::class)->publish($competition, $admin)->error);

        $this->assertSame(Gate::CLOSED_MESSAGE, app(CompletenessAssessmentService::class)->record($first, $chair, ApplicationCompletenessAssessment::COMPLETE)->error);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(ComplaintService::class)->submit($first, $first->user, 'Prigovor nakon zatvaranja')->error);

        $late = Application::create([
            'competition_id' => $competition->id, 'user_id' => $this->applicant()->id, 'applicant_type' => 'doo',
            'business_stage' => 'razvoj', 'status' => 'draft', 'business_plan_name' => 'Kasna prijava', 'pib' => $this->nextPib(),
        ]);
        $this->assertSame(Gate::CLOSED_MESSAGE, app(FinalSubmissionService::class)->submit($late, $late->user)->error);
        $this->assertSame('draft', $late->fresh()->status);

        try {
            app(CanonicalIndividualScoringService::class)->recordCanonicalFinalScore($first, $chair, $this->criteriaSumming(50), null, true);
            $this->fail('Scoring after close must be blocked.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertFalse($this->draft($competition, $members, $third, 'support', '100')->succeeded);
        $this->assertFalse(app(CanonicalOutcomeService::class)->confirm($competition, $chair)->succeeded);
        $this->assertFalse(app(CanonicalTieResolutionService::class)->propose($competition, $chair, 1, [$first->id, $second->id], [$first->id])->succeeded);

        $this->assertThrows(fn () => $first->forceFill(['ranking_position' => 3])->save(), LogicException::class);
        $this->assertThrows(fn () => $third->fresh()->forceFill(['approved_amount' => '500.00', 'status' => 'approved'])->save(), LogicException::class);
        $this->assertThrows(fn () => CanonicalPredlog::query()->firstOrFail()->update(['payload' => []]), LogicException::class);
        $this->assertThrows(fn () => CanonicalFormalDecision::query()->firstOrFail()->update(['decision_number' => 'X']), LogicException::class);

        $this->actingAs($admin)->put(route('admin.competitions.update', $competition), [
            'title' => $competition->title, 'type' => self::CANONICAL, 'up_number' => 'UP-01-332/26',
            'year' => 2026, 'budget' => 10000, 'status' => 'published',
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.competitions.destroy', $competition))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.competitions.official-decision.republish', [$competition, CompetitionOfficialDecisionCopy::query()->firstOrFail()]), [
            'business_title' => 'Ponovo', 'business_published_on' => '2026-09-10',
        ])->assertForbidden();

        $this->assertSame('completed', $competition->fresh()->status);
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame(1, CanonicalFormalDecision::query()->count());
    }

    // I — withdrawal / residual / no second call

    public function test_i_later_withdrawal_does_not_reopen_or_reallocate_and_no_second_call_exists(): void
    {
        $ctx = $this->flow('closed');
        $competition = $ctx['competition'];
        $members = $ctx['members'];
        [$first, , $third] = array_map(fn (Application $app) => $app->fresh(), $ctx['apps']);
        $competitionCount = Competition::query()->count();

        $this->assertThrows(fn () => $first->forceFill(['status' => 'withdrawn'])->save(), LogicException::class);
        $this->assertFalse($this->draft($competition, $members, $third, 'support', '800')->succeeded);

        $this->assertSame('7700.00', CanonicalPredlog::query()->firstOrFail()->payload['final_ranking']['residual_unallocated']);
        $this->assertSame('completed', $competition->fresh()->status);
        $this->assertSame(0, (int) CanonicalOutcome::query()->where('application_id', $third->id)->whereNotNull('approved_amount')->count());

        $this->assertSame(CompetitionAnnualInstance::MSG_INVALID_INSTANCE, app(CompetitionAnnualInstance::class)->secondCallCreationBlockReason($competition->fresh()));
        $this->actingAs($this->secretariat())->get(route('admin.competitions.second-call.create', $competition))->assertSessionHasErrors('error');
        $this->actingAs($this->secretariat())->post(route('admin.competitions.second-call.store', $competition), [
            'title' => 'Drugi poziv', 'up_number' => 'UP-2', 'budget' => 7700,
        ]);
        $this->assertSame($competitionCount, Competition::query()->count());
        $this->assertSame(0, Competition::query()->where('type', self::CANONICAL)->where('call_number', 2)->count());
    }

    // J — Women / Youth unchanged

    public function test_j_women_and_youth_are_outside_the_canonical_finish_line_and_keep_generic_paths(): void
    {
        [$commission] = $this->canonicalCommission();
        $admin = $this->secretariat();
        $women = Competition::create([
            'title' => 'Žensko '.uniqid(), 'type' => CompetitionProfileResolver::TYPE_WOMEN, 'status' => 'completed', 'year' => 2026,
            'budget' => 10000, 'deadline_days' => 20, 'start_date' => '2026-08-01', 'published_at' => '2026-08-01 09:00:00', 'closed_at' => now(),
        ]);
        $youth = Competition::create([
            'title' => 'Omladinsko '.uniqid(), 'type' => CompetitionProfileResolver::TYPE_YOUTH, 'call_number' => 1, 'status' => 'published', 'year' => 2026,
            'budget' => 10000, 'deadline_days' => 20, 'start_date' => '2026-08-01', 'published_at' => '2026-08-01 09:00:00',
        ]);
        $chair = $this->komisijaUser('zp-predsjednik');

        foreach ([$women, $youth] as $competition) {
            $this->assertSame(G::NOT_CANONICAL_MESSAGE, app(CanonicalPredlogService::class)->finalize($competition, $chair)->error);
            $this->assertSame(G::NOT_CANONICAL_MESSAGE, app(CanonicalSecretariatHandoffService::class)->handOff($competition, $chair, 'Sekretarijat')->error);
            $this->assertSame(G::NOT_CANONICAL_MESSAGE, app(CanonicalFormalDecisionService::class)->record($competition, $admin, '1', '2026-09-10')->error);
            $this->assertSame(G::NOT_CANONICAL_MESSAGE, app(CanonicalResultPublicationService::class)->publish($competition, $admin)->error);
            $this->assertSame(G::NOT_CANONICAL_MESSAGE, app(CanonicalCloseService::class)->close($competition, $chair)->error);
            $this->assertNull(app(CanonicalResultPublicationService::class)->publicResult($competition));
            $this->get(route('competitions.canonical-result', $competition))->assertNotFound();
            $this->actingAs($admin)->get(route('admin.competitions.canonical-decision.show', $competition))->assertNotFound();
        }

        $this->actingAs($admin)->post(route('admin.competitions.official-decision.store', $women), [
            'official_decision_copy' => $this->pdf(), 'business_title' => 'Odluka ŽP',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, CompetitionOfficialDecisionCopy::query()->where('competition_id', $women->id)->count());

        $this->assertNotSame(CompetitionAnnualInstance::MSG_INVALID_INSTANCE, app(CompetitionAnnualInstance::class)->secondCallCreationBlockReason($youth));
        $this->assertSame(0, CanonicalAdministrativeEvent::query()->whereNotIn('action', CanonicalAdministrativeEvent::COMMISSION_ACTIONS)->count());
        $this->assertNotNull($commission);
    }

    // HTTP — thin controllers orchestrate the full chain

    public function test_http_full_chain_through_thin_controllers(): void
    {
        $ctx = $this->flow('signed');
        $competition = $ctx['competition'];
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;
        $admin = $this->secretariat();

        $this->actingAs($ctx['members'][G::ROLE_SEKRETAR]->user)->get(route('evaluation.canonical-administrative.show', $competition))->assertOk();
        $this->actingAs($ctx['members'][G::ROLE_SEKRETAR]->user)->post(route('evaluation.canonical-administrative.predlog.finalize', $competition))
            ->assertSessionHasErrors('error');

        $this->actingAs($chair)->get(route('evaluation.canonical-administrative.show', $competition))->assertOk()->assertSee('Finalizuj Predlog');
        $this->actingAs($chair)->post(route('evaluation.canonical-administrative.predlog.finalize', $competition))->assertSessionHasNoErrors();
        $this->actingAs($chair)->post(route('evaluation.canonical-administrative.handoff', $competition), [
            'recipient' => 'Sekretarijat za razvoj preduzetništva', 'reference' => 'Akt 01-55/26',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.competitions.canonical-decision.show', $competition))->assertOk()->assertSee('Evidentiraj Odluku');
        $this->actingAs($admin)->post(route('admin.competitions.canonical-decision.record', $competition), [
            'decision_number' => '01-123/26', 'decision_date' => '2026-09-10',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-decision.signed-copy', $competition), [
            'signed_copy' => $this->pdf(),
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-decision.publish', $competition))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.competitions.canonical-decision.publish', $competition))->assertSessionHasErrors('error');

        $this->actingAs($chair)->post(route('evaluation.canonical-administrative.close', $competition))->assertSessionHasNoErrors();
        $this->assertSame('completed', $competition->fresh()->status);

        $this->get(route('competitions.canonical-result', $competition))->assertOk()->assertSee('01-123/26');
        $this->assertSame(1, Notice::query()->count());
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /**
     * Ranks 1, 1 (shared, both funded) and 3 (below 30, rejected). Budget 10000, total supported 2300.
     *
     * @return list<array{scores: array{0: int, 1: int, 2: int}, type?: string, total?: string}>
     */
    private function specs(): array
    {
        return [
            ['scores' => [45, 45, 45], 'type' => T::AUTHENTIC_LOCAL, 'total' => '1000.00'],
            ['scores' => [45, 45, 45], 'type' => T::INNOVATIVE, 'total' => '3000.00'],
            ['scores' => [29, 29, 29]],
        ];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  list<Application>  $apps
     */
    private function draftAll(Competition $competition, array $members, array $apps): void
    {
        $this->ok($this->draft($competition, $members, $apps[0], 'support', '800'));
        $this->ok($this->draft($competition, $members, $apps[1], 'support', '1500'));
        $this->ok($this->draft($competition, $members, $apps[2], 'reject', null, CanonicalOutcome::REASON_BELOW_30));
    }

    /**
     * @return array{commission: mixed, members: array<string, CommissionMember>, competition: Competition, apps: list<Application>}
     */
    private function flow(string $until): array
    {
        [$commission, $members, $competition, $apps] = $this->rankedCompetition($this->specs());
        $this->draftAll($competition, $members, $apps);
        $this->ok(app(CanonicalOutcomeService::class)->confirm($competition, $members[G::ROLE_PREDSJEDNIK]->user));
        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $role) {
            $this->ok(app(CanonicalOutcomeService::class)->sign($competition, $members[$role]->user));
        }

        $ctx = ['commission' => $commission, 'members' => $members, 'competition' => $competition->fresh(), 'apps' => $apps];
        foreach (array_slice(self::STAGES, 1, (int) array_search($until, self::STAGES, true)) as $stage) {
            $this->advance($stage, $ctx);
        }
        $ctx['competition'] = $competition->fresh();

        return $ctx;
    }

    /**
     * @param  array{members: array<string, CommissionMember>, competition: Competition}  $ctx
     */
    private function advance(string $stage, array $ctx): void
    {
        $competition = $ctx['competition']->fresh();
        $chair = $ctx['members'][G::ROLE_PREDSJEDNIK]->user;

        $result = match ($stage) {
            'finalized' => app(CanonicalPredlogService::class)->finalize($competition, $chair),
            'handed' => app(CanonicalSecretariatHandoffService::class)->handOff($competition, $chair, 'Sekretarijat za razvoj preduzetništva', 'Akt 01-55/26', 'Predaja'),
            'recorded' => app(CanonicalFormalDecisionService::class)->record($competition, $this->secretariat(), '01-123/26', '2026-09-10', 'Predsjednik Opštine'),
            'attached' => app(CanonicalFormalDecisionService::class)->attachSignedCopy($competition, $this->secretariat(), $this->pdf()),
            'published' => app(CanonicalResultPublicationService::class)->publish($competition, $this->secretariat()),
            'closed' => app(CanonicalCloseService::class)->close($competition, $chair),
        };

        $this->ok($result, $stage);
    }

    private function ok(ProcessWriteResult $result, string $context = ''): void
    {
        $this->assertTrue($result->succeeded, trim($context.' '.(string) $result->error));
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('odluka.pdf', 100, 'application/pdf');
    }

    private function secretariat(): User
    {
        return $this->secretariatUser ??= User::factory()->create([
            'name' => 'Sekretarijat',
            'email' => 'sekretarijat-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  list<array{scores: array{0: int, 1: int, 2: int}, type?: string, total?: string}>  $specs
     * @return array{0: mixed, 1: array<string, CommissionMember>, 2: Competition, 3: list<Application>}
     */
    private function rankedCompetition(array $specs, int $budget = 10000): array
    {
        [$commission, $members] = $this->canonicalCommission();
        $competition = Competition::create([
            'title' => 'Poziv MMSP '.uniqid(),
            'type' => self::CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => $budget,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        UpNumber::query()->create(['competition_id' => $competition->id, 'number' => 'UP-01-332/26']);
        $this->confirmOrdinarySession($competition);

        $apps = [];
        foreach ($specs as $spec) {
            $app = $this->finalApplication($competition, $spec);
            $this->ok(app(CompletenessAssessmentService::class)
                ->record($app, $members[G::ROLE_PREDSJEDNIK]->user, ApplicationCompletenessAssessment::COMPLETE));
            $apps[] = $app;
        }

        $this->confirmSession($competition, $members, G::PURPOSE_INTERVIEW);

        $scoring = app(CanonicalIndividualScoringService::class);
        foreach ($apps as $index => $app) {
            foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $seat => $role) {
                $scoring->recordCanonicalFinalScore(Application::query()->findOrFail($app->id), $members[$role]->user, $this->criteriaSumming($specs[$index]['scores'][$seat]), null, true);
            }
        }
        $this->assertTrue($scoring->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));

        $this->confirmSession($competition, $members, G::PURPOSE_FINAL_DECISION);

        return [$commission, $members, $competition->fresh(), array_map(fn (Application $app) => $app->fresh(), $apps)];
    }

    /**
     * @param  array{type?: string, total?: string}  $spec
     */
    private function finalApplication(Competition $competition, array $spec): Application
    {
        $owner = $this->applicant();
        $pib = $this->nextPib();
        $type = $spec['type'] ?? T::AUTHENTIC_LOCAL;
        $total = $spec['total'] ?? '5000.00';

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'doo',
            'business_stage' => 'razvoj',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Osnivač '.uniqid(),
            'doo_name' => 'Firma '.uniqid(),
            'pib' => $pib,
            'submitted_at' => now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);

        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
            'subsidy_type' => $type,
            'beneficiary_pib' => $pib,
            'beneficiary_slot' => 1,
        ]);

        (new CanonicalInvestmentPlan)->forceFill([
            'application_id' => $application->id,
            'subsidy_type' => $type,
            'total_investment' => $total,
            'own_contribution' => '0.00',
            'expected_subsidy' => $total,
            'expense_lines' => [['cost_type' => 'oprema', 'description' => 'Oprema', 'amount' => $total]],
            'frozen_at' => now(),
        ])->save();

        return $application->fresh();
    }

    private function applicant(): User
    {
        return User::factory()->create([
            'email' => 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function nextPib(): string
    {
        return '8'.str_pad((string) ++$this->pibCounter, 7, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmSession(Competition $competition, array $members, string $purpose): CommissionSession
    {
        $service = app(CanonicalCommissionSessionService::class);
        $result = $service->record($competition, $members[G::ROLE_PREDSJEDNIK]->user, $purpose, CarbonImmutable::now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ]);
        $this->ok($result, $purpose);
        $session = CommissionSession::query()->findOrFail($result->recordId);
        $this->ok($service->confirm($session, $members[G::ROLE_PREDSJEDNIK]->user), $purpose);

        return $session->fresh();
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function draft(Competition $competition, array $members, Application $application, string $decision, mixed $amount, ?string $reason = null): ProcessWriteResult
    {
        return app(CanonicalOutcomeService::class)->recordDraft($competition, $members[G::ROLE_PREDSJEDNIK]->user, $application->id, [
            'decision' => $decision,
            'amount' => $amount,
            'reason' => $reason,
            'justification' => $decision === 'support' ? 'Obrazloženje podrške' : null,
            'remarks' => 'Interna napomena komisije',
        ]);
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
}
