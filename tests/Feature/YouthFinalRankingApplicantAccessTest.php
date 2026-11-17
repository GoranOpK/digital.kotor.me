<?php

namespace Tests\Feature;

use App\Http\Controllers\EvaluationController;
use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use App\Services\Competitions\YouthDecisionDocumentStore;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class YouthFinalRankingApplicantAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<int> */
    private array $cleanupCompetitionIds = [];

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        if ($this->app) {
            foreach ($this->cleanupCompetitionIds as $id) {
                Storage::disk('local')->deleteDirectory('youth-decision-documents/'.$id);
            }
        }
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_participant_reads_the_final_list_only_after_the_competition_is_completed(): void
    {
        $ctx = $this->rankedApplications();
        $competition = $ctx['competition'];
        $winner = $ctx['winner'];
        $rejected = $ctx['rejected'];
        $below = $ctx['below'];
        $participant = $winner->user;

        $this->actingAs($participant)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertForbidden();
        $this->actingAs($participant)
            ->get(route('applications.show', $winner))
            ->assertOk()
            ->assertDontSee('data-testid="youth-final-ranking-link"', false);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $winner), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 4000,
                'commission_justification' => 'Interno obrazloženje koje ne smije da vidi podnosilac.',
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $rejected), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Interno obrazloženje koje ne smije da vidi podnosilac.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $authorName = $ctx['chairman']->name;
        $authorMemberId = (int) $ctx['chairman']->id;
        $winner->refresh();
        $rejected->refresh();
        $this->assertSame($authorMemberId, (int) $winner->commission_decision_author_member_id);
        $this->assertSame($authorName, $winner->commission_decision_author_name);
        $this->assertNotNull($winner->commission_decision_date);
        $this->assertSame($authorMemberId, (int) $rejected->commission_decision_author_member_id);
        $this->assertSame($authorName, $rejected->commission_decision_author_name);

        $this->actingAs($participant)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertForbidden();

        $this->storeDecision($ctx);
        $this->actingAs($participant)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertForbidden();

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.youth-decision', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', $competition->fresh()->status);

        $beforeCloseView = $this->snapshot($competition);
        $commissionHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $competition))
            ->assertOk()
            ->getContent();
        $this->assertSame($beforeCloseView, $this->snapshot($competition));
        $this->assertStringContainsString('<h2>Konačna rang-lista</h2>', $commissionHtml);
        $this->assertStringNotContainsString('<h2>Konačna rang-lista Komisije</h2>', $commissionHtml);
        $this->assertStringContainsString('data-testid="youth-ranking-signatures"', $commissionHtml);
        $this->assertStringContainsString('data-testid="youth-decision-open"', $commissionHtml);
        $this->assertStringContainsString('data-testid="youth-ranking-print"', $commissionHtml);
        $this->assertStringContainsString('Zaključak komisije i obrazloženje', $commissionHtml);
        $this->assertStringContainsString('Interno obrazloženje koje ne smije da vidi podnosilac.', $commissionHtml);
        $this->assertStringContainsString('Dobitnik sredstava', $commissionHtml);
        $this->assertStringContainsString('Ispod bodovnog praga', $commissionHtml);
        $this->assertStringContainsString('Nalog Pobjednik', $this->row($commissionHtml, 'Plan dobitnik'));
        $commissionConclusions = $this->between($commissionHtml, 'Zaključak komisije i obrazloženje', 'Potpis komisije');
        $this->assertSame(2, substr_count($commissionConclusions, $authorName));
        $this->assertStringNotContainsString('data-testid="youth-final-ranking-readonly"', $commissionHtml);

        $ctx['chairman']->user->update(['name' => 'Promijenjeno korisničko ime']);
        $ctx['chairman']->update([
            'name' => 'Promijenjeno ime člana',
            'status' => 'inactive',
        ]);
        $replacement = User::factory()->create([
            'name' => 'Drugi Predsjednik Trajni',
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        CommissionMember::create([
            'commission_id' => $ctx['chairman']->commission_id,
            'user_id' => $replacement->id,
            'name' => 'Drugi Predsjednik Trajni',
            'position' => 'predsjednik',
            'member_type' => null,
            'canonical_seat_no' => 4,
            'status' => 'active',
        ]);
        $winner->refresh();
        $rejected->refresh();
        $this->assertSame($authorName, $winner->commission_decision_author_name);
        $this->assertSame($authorMemberId, (int) $winner->commission_decision_author_member_id);
        $this->assertSame($authorName, $rejected->commission_decision_author_name);
        $rejected->forceFill([
            'commission_decision_author_member_id' => null,
            'commission_decision_author_name' => null,
        ])->save();

        $before = $this->snapshot($competition);

        $realScoring = app(CanonicalIndividualScoringService::class);
        $scoring = Mockery::mock($realScoring);
        $scoring->shouldReceive('persistYouthPreliminaryRankingIfReady')->never();
        $this->app->instance(CanonicalIndividualScoringService::class, $scoring);

        $html = $this->actingAs($participant)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertOk()
            ->getContent();
        $this->assertSame($before, $this->snapshot($competition));
        $this->app->instance(CanonicalIndividualScoringService::class, $realScoring);

        $this->actingAs($replacement)
            ->post(route('evaluation.youth-allocation-draft', $winner), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Pokušaj izmjene nakon zatvaranja.',
            ])
            ->assertForbidden();
        $this->assertSame($before, $this->snapshot($competition));
        $this->assertSame($authorName, $winner->fresh()->commission_decision_author_name);
        $this->assertSame('podrzava_potpuno', $winner->fresh()->commission_decision);

        $this->assertStringContainsString('data-testid="youth-final-ranking-readonly"', $html);
        $this->assertStringContainsString('<h2>Konačna rang-lista Komisije</h2>', $html);
        $this->assertStringContainsString('RANG LISTA BIZNIS PLANOVA', $html);
        $this->assertStringContainsString($competition->title, $html);
        $this->assertStringContainsString('Sekretarijat za razvoj preduzetništva', $html);
        $this->assertStringContainsString('Konkurs: UP-PM-02A2 / 2026', $html);
        $this->assertStringContainsString('ranking-badge', $html);
        $this->assertStringContainsString('background: #d1fae5;', $html);
        $this->assertStringContainsString('/ 56', $html);
        $this->assertStringNotContainsString('/ 58', $html);
        $this->assertStringNotContainsString('data-testid="youth-ranking-signatures"', $html);
        $this->assertStringNotContainsString('data-testid="youth-decision-open"', $html);
        $this->assertStringNotContainsString('data-testid="youth-allocation-draft-form"', $html);
        $this->assertStringNotContainsString('Sačuvaj zaključak', $html);
        $this->assertStringNotContainsString('type="radio"', $html);
        $this->assertStringNotContainsString('Potpis komisije', $html);
        $this->assertStringNotContainsString('Generiši odluku', $html);
        $this->assertStringNotContainsString('067111222', $html);
        $this->assertStringNotContainsString('mila.tajna@example.test', $html);
        $this->assertStringNotContainsString('jovo.tajni@example.test', $html);
        $this->assertStringNotContainsString(route('evaluation.index'), $html);
        $this->assertStringContainsString(route('applications.show', $winner), $html);
        $this->assertStringContainsString('<h2>Zaključak komisije i obrazloženje</h2>', $html);
        $this->assertStringContainsString('Podržava u potpunosti', $html);
        $this->assertStringContainsString('>Odbija<', $html);
        $this->assertSame(2, substr_count($html, 'Interno obrazloženje koje ne smije da vidi podnosilac.'));
        $recordedWinner = $winner->fresh();
        $this->assertNotNull($recordedWinner->commission_decision_date);
        $this->assertStringContainsString($authorName, $html);
        $this->assertStringNotContainsString('Drugi Predsjednik Trajni', $html);
        $this->assertStringNotContainsString('Promijenjeno ime člana', $html);
        $this->assertStringNotContainsString('Promijenjeno korisničko ime', $html);
        $this->assertStringContainsString($recordedWinner->commission_decision_date->format('d.m.Y'), $html);
        $this->assertStringContainsString('Unio / Datum', $html);

        $winnerRow = $this->row($html, 'Plan dobitnik');
        $rejectedRow = $this->row($html, 'Plan odbijen');
        $belowRow = $this->row($html, 'Plan ispod praga');
        $this->assertStringContainsString('Mila Milić', $winnerRow);
        $this->assertStringNotContainsString('Nalog Pobjednik', $winnerRow);
        $this->assertStringContainsString('Fizičko lice', $winnerRow);
        $this->assertStringContainsString('50.00 / 56', $winnerRow);
        $this->assertStringContainsString('5.000,00 €', $winnerRow);
        $this->assertStringContainsString('4.000,00 €', $winnerRow);
        $this->assertStringContainsString('Dobitnik sredstava', $winnerRow);
        $this->assertStringContainsString('Jovo Jović', $rejectedRow);
        $this->assertStringContainsString('Fizičko lice', $rejectedRow);
        $this->assertStringContainsString('40.00 / 56', $rejectedRow);
        $this->assertStringContainsString('Odbijena prijava', $rejectedRow);
        $this->assertStringContainsString('Sara Sarić', $belowRow);
        $this->assertStringContainsString('Fizičko lice', $belowRow);
        $this->assertStringContainsString('20.00 / 56', $belowRow);
        $this->assertStringContainsString('1.000,00 €', $belowRow);
        $this->assertStringContainsString('Ispod bodovnog praga', $belowRow);

        $otherParticipantHtml = $this->actingAs($rejected->user)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Plan dobitnik', $otherParticipantHtml);
        $this->assertStringContainsString('Plan odbijen', $otherParticipantHtml);
        $this->assertSame(2, substr_count($otherParticipantHtml, 'Interno obrazloženje koje ne smije da vidi podnosilac.'));
        $this->assertStringNotContainsString('data-testid="youth-allocation-draft-form"', $otherParticipantHtml);
        $this->assertSame($before, $this->snapshot($competition));

        $this->actingAs($participant)
            ->get(route('evaluation.create', $rejected))
            ->assertForbidden();
        $this->actingAs($participant)
            ->get(route('evaluation.create', $below))
            ->assertForbidden();

        $winnerAt = strpos($html, 'Plan dobitnik');
        $rejectedAt = strpos($html, 'Plan odbijen');
        $separatorAt = strpos($html, 'Prijave ispod minimuma (30 bodova)');
        $belowAt = strpos($html, 'Plan ispod praga');
        $this->assertNotFalse($winnerAt);
        $this->assertTrue($winnerAt < $rejectedAt && $rejectedAt < $separatorAt && $separatorAt < $belowAt);

        $this->actingAs($participant)
            ->get(route('applications.show', $winner))
            ->assertOk()
            ->assertSee('data-testid="youth-final-ranking-link"', false)
            ->assertSee('Konačna rang-lista Komisije')
            ->assertDontSee('nije službena odluka Sekretarijata', false);

        $this->actingAs($participant)
            ->get(route('evaluation.youth-ranking', $competition))
            ->assertForbidden();

        $draftUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $draftUser->id,
            'business_plan_name' => 'Nacrt koji nije podnesen',
            'applicant_type' => 'fizicko_lice',
            'status' => 'draft',
        ]);
        $this->actingAs($draftUser)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertForbidden();
        $afterDraft = $this->snapshot($competition);
        $this->assertSame($before['competition'], $afterDraft['competition']);
        $this->assertSame($before['applications'], array_values(array_filter(
            $afterDraft['applications'],
            fn (array $row): bool => $row['status'] !== 'draft'
        )));
        $this->assertSame('draft', Application::query()->where('user_id', $draftUser->id)->firstOrFail()->status);

        $outsider = $this->applicantUser('Nalog Drugog konkursa');
        $other = $this->bareCompetition('omladinsko', 2);
        Application::create([
            'competition_id' => $other->id,
            'user_id' => $outsider->id,
            'business_plan_name' => 'Tuđi konkurs',
            'applicant_type' => 'fizicko_lice',
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
        ]);
        $this->actingAs($outsider)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertForbidden();
    }

    public function test_guest_and_other_competition_profiles_cannot_open_the_youth_list(): void
    {
        $guestCompetition = $this->bareCompetition('omladinsko', 1);
        $this->get(route('applications.youth-final-ranking', $guestCompetition))
            ->assertRedirect(route('login'));

        $zenskoUser = $this->applicantUser('Korisnik ženskog konkursa');
        $zensko = $this->bareCompetition('zensko', null);
        $zensko->update(['status' => 'completed', 'closed_at' => now()]);
        Application::create([
            'competition_id' => $zensko->id,
            'user_id' => $zenskoUser->id,
            'business_plan_name' => 'Ženski plan',
            'applicant_type' => 'fizicko_lice',
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
        ]);
        $this->actingAs($zenskoUser)
            ->get(route('applications.youth-final-ranking', $zensko))
            ->assertForbidden();

        $mmspUser = $this->applicantUser('Korisnik MMSP konkursa');
        $mmsp = $this->bareCompetition(CompetitionProfileResolver::TYPE_CANONICAL, null);
        $mmsp->update(['status' => 'completed', 'closed_at' => now()]);
        Application::create([
            'competition_id' => $mmsp->id,
            'user_id' => $mmspUser->id,
            'business_plan_name' => 'MMSP plan',
            'applicant_type' => 'doo',
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
        ]);
        $this->actingAs($mmspUser)
            ->get(route('applications.youth-final-ranking', $mmsp))
            ->assertForbidden();
    }

    public function test_youth_signature_is_kept_before_close_and_refused_after_completion(): void
    {
        $ctx = $this->rankedApplications();
        $competition = $ctx['competition'];
        $winner = $ctx['winner'];
        $signer = $ctx['members'][1];
        $laterSigner = $ctx['members'][2];
        $winner->forceFill(['signed_by_chairman' => true])->save();

        $this->actingAs($signer->user)
            ->from(route('evaluation.youth-ranking', $competition))
            ->post(route('evaluation.sign-decision', $winner))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame([(int) $signer->id], array_map('intval', $winner->fresh()->signed_by_members ?? []));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $winner), [
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => 4000,
                'commission_justification' => 'Obrazloženje koje ostaje nakon zatvaranja.',
                'youth_innovative_tech_startup' => '0',
                'youth_prior_municipal_youth_funding' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.youth-allocation-draft', $ctx['rejected']), [
                'commission_decision' => 'odbija',
                'commission_justification' => 'Odbijanje koje ostaje nakon zatvaranja.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->storeDecision($ctx);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.youth-decision', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', $competition->fresh()->status);

        $before = $this->decisionEvidence($winner->fresh());
        $this->assertSame([(int) $signer->id], $before['signed_by_members']);
        $this->assertSame($ctx['chairman']->id, $before['commission_decision_author_member_id']);
        $this->assertSame($ctx['chairman']->name, $before['commission_decision_author_name']);

        $refused = $this->actingAs($laterSigner->user)
            ->post(route('evaluation.sign-decision', $winner));
        $refused->assertForbidden();
        $this->assertSame(EvaluationController::YOUTH_SIGN_LOCKED_MESSAGE, $refused->exception?->getMessage());
        $this->assertSame($before, $this->decisionEvidence($winner->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $competition))
            ->assertOk()
            ->assertSee('Obrazloženje koje ostaje nakon zatvaranja.', false);
        $this->actingAs($winner->user)
            ->get(route('applications.youth-final-ranking', $competition))
            ->assertOk()
            ->assertSee('Obrazloženje koje ostaje nakon zatvaranja.', false)
            ->assertSee($ctx['chairman']->name, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function decisionEvidence(Application $application): array
    {
        $application->refresh();

        return [
            'status' => $application->status,
            'final_score' => (string) $application->final_score,
            'ranking_position' => (int) $application->ranking_position,
            'requested_amount' => (string) $application->requested_amount,
            'approved_amount' => (string) $application->approved_amount,
            'commission_decision' => $application->commission_decision,
            'commission_justification' => $application->commission_justification,
            'commission_decision_date' => optional($application->commission_decision_date)->toDateString(),
            'commission_decision_author_member_id' => (int) $application->commission_decision_author_member_id,
            'commission_decision_author_name' => $application->commission_decision_author_name,
            'signed_by_members' => array_map('intval', $application->signed_by_members ?? []),
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, winner: Application, rejected: Application, below: Application}
     */
    private function rankedApplications(): array
    {
        $commission = Commission::create([
            'name' => 'Mladi uvid '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = User::factory()->create([
                'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
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
            'title' => 'Omladinski konkurs za uvid '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-PM-02A2',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-PM-02A2']);
        $this->cleanupCompetitionIds[] = (int) $competition->id;

        $ctx = [
            'competition' => $competition->fresh(),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
        ];
        $winner = $this->application($ctx, 'Plan dobitnik', 5000, 'Nalog Pobjednik', 'Mila Milić', '067111222', 'mila.tajna@example.test');
        $rejected = $this->application($ctx, 'Plan odbijen', 5000, 'Nalog Odbijen', 'Jovo Jović', '067333444', 'jovo.tajni@example.test');
        $below = $this->application($ctx, 'Plan ispod praga', 1000, 'Nalog Ispod', 'Sara Sarić', '067555666', 'sara.tajna@example.test');
        $this->score($ctx, $winner, 5);
        $this->score($ctx, $rejected, 4);
        $this->score($ctx, $below, 2);
        foreach ([$winner, $rejected, $below] as $application) {
            $this->actingAs($ctx['chairman']->user)
                ->post(route('evaluation.store', $application), [
                    'youth_bonus_action' => 'confirm',
                ])
                ->assertSessionHasNoErrors();
        }

        $winner = $winner->fresh();
        $rejected = $rejected->fresh();
        $below = $below->fresh();
        $this->assertSame('50.00', (string) $winner->final_score);
        $this->assertSame('40.00', (string) $rejected->final_score);
        $this->assertSame('20.00', (string) $below->final_score);
        $this->assertSame(1, (int) $winner->ranking_position);
        $this->assertSame(2, (int) $rejected->ranking_position);
        $this->assertSame(3, (int) $below->ranking_position);
        $this->assertSame(
            YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
            $below->rejection_reason
        );

        return $ctx + [
            'winner' => $winner,
            'rejected' => $rejected,
            'below' => $below,
        ];
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
    private function application(
        array $ctx,
        string $plan,
        int $requestedAmount,
        string $accountName,
        string $personName,
        string $phone,
        string $email,
    ): Application {
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => $this->applicantUser($accountName)->id,
            'business_plan_name' => $plan,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => $personName,
            'physical_person_phone' => $phone,
            'physical_person_email' => $email,
            'business_stage' => 'započinjanje',
            'requested_amount' => $requestedAmount,
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'criterion_notes' => [1 => '', 2 => '', 3 => ''],
            ])
            ->assertRedirect();

        return $application->fresh();
    }

    /**
     * @param  array{members: list<CommissionMember>}  $ctx
     */
    private function score(array $ctx, Application $application, int $value): void
    {
        $payload = ['scoring_confirmed' => '1', 'notes' => null];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = $value;
        }
        foreach ([1, 2, 3] as $seat) {
            $this->actingAs($ctx['members'][$seat - 1]->fresh('user')->user)
                ->post(route('evaluation.store', $application), $payload)
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember}  $ctx
     */
    private function storeDecision(array $ctx): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pm-applicant-ranking-');
        if ($path === false) {
            $this->fail('Testni ulazni fajl nije kreiran.');
        }
        file_put_contents($path, "PK\x03\x04pm-konacna-lista");
        $this->tempFiles[] = $path;
        app(YouthDecisionDocumentStore::class)->store($ctx['competition']->fresh(), $ctx['chairman']->user, $path);
    }

    private function applicantUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function bareCompetition(string $type, ?int $callNumber): Competition
    {
        return Competition::create([
            'title' => 'Van uvida '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'call_number' => $callNumber,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function snapshot(Competition $competition): array
    {
        $competition->refresh();

        return [
            'competition' => [
                'status' => $competition->status,
                'closed_at' => optional($competition->closed_at)->toJSON(),
                'confirmed_at' => optional($competition->youth_allocation_list_confirmed_at)->toJSON(),
            ],
            'applications' => Application::query()
                ->where('competition_id', $competition->id)
                ->orderBy('id')
                ->get([
                    'id',
                    'status',
                    'ranking_position',
                    'final_score',
                    'requested_amount',
                    'approved_amount',
                    'commission_decision',
                    'commission_justification',
                    'commission_decision_author_member_id',
                    'commission_decision_author_name',
                    'rejection_reason',
                ])
                ->map(fn (Application $application) => $application->only([
                    'id',
                    'status',
                    'ranking_position',
                    'final_score',
                    'requested_amount',
                    'approved_amount',
                    'commission_decision',
                    'commission_justification',
                    'commission_decision_author_member_id',
                    'commission_decision_author_name',
                    'rejection_reason',
                ]))
                ->all(),
        ];
    }

    private function between(string $html, string $startText, string $endText): string
    {
        $start = strpos($html, $startText);
        $this->assertNotFalse($start);
        $end = strpos($html, $endText, $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    private function row(string $html, string $plan): string
    {
        $start = strpos($html, $plan);
        $this->assertNotFalse($start);
        $end = strpos($html, '</tr>', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }
}
