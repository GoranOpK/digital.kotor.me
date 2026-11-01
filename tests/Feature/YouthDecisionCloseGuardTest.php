<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\BusinessPlan;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use App\Services\Competitions\YouthDecisionDocumentStore;
use App\Services\Competitions\YouthDecisionProposalBuilder;
use App\Services\Competitions\YouthDecisionRankingIdentity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YouthDecisionCloseGuardTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        if ($this->app) {
            foreach ($this->cleanupCompetitionIds as $id) {
                Storage::disk('local')->deleteDirectory('youth-decision-documents/'.$id);
            }
        }

        parent::tearDown();
    }

    public function test_close_is_refused_when_no_word_record_exists(): void
    {
        $ctx = $this->closeableYouthCompetition();

        $this->postClose($ctx)
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
    }

    public function test_close_is_refused_when_the_file_is_missing(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $bytes = "PK\x03\x04nedostaje";
        $relative = 'youth-decision-documents/'.$ctx['competition']->id.'/01ARZ3NDEKTSV4RRFFQ69G5FAV.docx';
        $document = $this->rememberDocument($ctx, $relative, $bytes, now(), $this->rankingHash($ctx['competition']), hash('sha256', $bytes));

        $this->postClose($ctx)->assertRedirect()->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $this->assertNotNull($document->fresh());
        $this->assertFalse(Storage::disk('local')->exists($relative));
    }

    public function test_close_is_refused_when_the_content_hash_does_not_match(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $bytes = "PK\x03\x04sadrzaj";
        $relative = $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAV', $bytes);
        $document = $this->rememberDocument(
            $ctx,
            $relative,
            $bytes,
            now(),
            $this->rankingHash($ctx['competition']),
            hash('sha256', 'drugi-sadrzaj')
        );

        $this->postClose($ctx)->assertRedirect()->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $this->assertSame($bytes, Storage::disk('local')->get($relative));
        $this->assertSame(hash('sha256', 'drugi-sadrzaj'), $document->fresh()->content_sha256);
    }

    public function test_close_is_refused_when_the_path_is_not_allowed(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $document = $this->rememberDocument(
            $ctx,
            'youth-decision-documents/'.$ctx['competition']->id.'/../odluka.docx',
            'van-direktorijuma',
            now(),
            $this->rankingHash($ctx['competition']),
            hash('sha256', 'van-direktorijuma')
        );

        $this->postClose($ctx)->assertRedirect()->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $this->assertSame('youth-decision-documents/'.$ctx['competition']->id.'/../odluka.docx', $document->fresh()->storage_path);
    }

    public function test_close_is_refused_when_the_ranking_hash_does_not_match(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $bytes = "PK\x03\x04rang";
        $relative = $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAV', $bytes);
        $document = $this->rememberDocument($ctx, $relative, $bytes, now(), str_repeat('ab', 32), hash('sha256', $bytes));

        $this->postClose($ctx)->assertRedirect()->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $this->assertSame(str_repeat('ab', 32), $document->fresh()->ranking_sha256);
        $this->assertSame($bytes, Storage::disk('local')->get($relative));
    }

    public function test_mismatched_ranking_position_still_blocks_close(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04neuskadjen-rang");
        $ctx['application']->forceFill(['ranking_position' => 9])->save();

        $this->postClose($ctx)->assertRedirect()->assertSessionHasErrors('error');

        $this->assertSame(
            CanonicalIndividualScoringService::YOUTH_CLOSE_POSITIONS_MISMATCH_MESSAGE,
            session('errors')->first('error')
        );
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $fresh = $ctx['application']->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame(9, (int) $fresh->ranking_position);
        $this->assertNull($fresh->commission_decision);
        $this->assertSame(
            YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
            $fresh->rejection_reason
        );
    }

    public function test_valid_document_closes_the_competition_and_rejects_an_incomplete_application(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $incomplete = $this->incompleteSubmittedApplication($ctx);
        $bytes = "PK\x03\x04ispravna-odluka";
        $document = $this->storeBytes($ctx, $bytes);
        $scoredStatus = $ctx['application']->status;

        $this->postClose($ctx)->assertRedirect()->assertSessionHasNoErrors();

        $closed = $ctx['competition']->fresh();
        $this->assertSame('completed', $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame('rejected', $incomplete->fresh()->status);
        $this->assertSame(
            'Prijava je nepotpuna (nedostaje biznis plan ili obrazac nije kompletan) i rok za prijave je istekao.',
            $incomplete->fresh()->rejection_reason
        );
        $this->assertSame($scoredStatus, $ctx['application']->fresh()->status);
        $this->assertSame($bytes, Storage::disk('local')->get($document->storage_path));
    }

    public function test_direct_post_without_a_document_is_refused(): void
    {
        $ctx = $this->closeableYouthCompetition();

        $this->actingAs($ctx['chairman'])
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $this->assertSame(0, YouthDecisionDocument::query()->count());
    }

    public function test_ordinary_commission_member_cannot_close(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04clan");

        $this->actingAs($ctx['member'])
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertForbidden();

        $this->assertUnchangedOpenCompetition($ctx['competition']);
    }

    public function test_former_chairman_cannot_close(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04bivsi");
        $ctx['chairmanMember']->update(['status' => 'inactive']);

        $this->actingAs($ctx['chairman'])
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertForbidden();

        $this->assertUnchangedOpenCompetition($ctx['competition']);
    }

    public function test_superadmin_without_chairmanship_cannot_close(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04superadmin");
        $superadmin = User::factory()->create([
            'role_id' => Role::query()->where('name', 'superadmin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.competitions.close', $ctx['competition']))
            ->assertForbidden();

        $this->assertUnchangedOpenCompetition($ctx['competition']);
    }

    public function test_repeated_post_does_not_change_closed_at(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04ponovljeno");
        Carbon::setTestNow(Carbon::parse('2026-11-01 10:00:00'));

        $this->postClose($ctx)->assertRedirect()->assertSessionHasNoErrors();
        $closedAt = $ctx['competition']->fresh()->closed_at;
        $this->assertNotNull($closedAt);

        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00'));
        $this->postClose($ctx)->assertRedirect()->assertSessionHasNoErrors();

        $again = $ctx['competition']->fresh();
        $this->assertSame('completed', $again->status);
        $this->assertSame($closedAt->toDateTimeString(), $again->closed_at->toDateTimeString());
    }

    public function test_document_remains_stored_after_close(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $bytes = "PK\x03\x04ostaje";
        $document = $this->storeBytes($ctx, $bytes);
        $before = $document->only(['id', 'competition_id', 'storage_path', 'content_sha256', 'ranking_sha256']);

        $this->postClose($ctx)->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $document->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame($before, $fresh->only(['id', 'competition_id', 'storage_path', 'content_sha256', 'ranking_sha256']));
        $this->assertSame($bytes, Storage::disk('local')->get($fresh->storage_path));
        $this->assertSame(1, YouthDecisionDocument::query()->where('competition_id', $ctx['competition']->id)->count());
    }

    public function test_refused_close_does_not_leave_partial_application_status_changes(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $incomplete = $this->incompleteSubmittedApplication($ctx);
        $beforeScored = $ctx['application']->fresh()->only(['status', 'ranking_position', 'commission_decision', 'rejection_reason']);

        $this->postClose($ctx)->assertRedirect()->assertSessionHasErrors('error');

        $this->assertSame(YouthDecisionDocumentStore::CLOSE_BLOCKED, session('errors')->first('error'));
        $this->assertUnchangedOpenCompetition($ctx['competition']);
        $this->assertSame('submitted', $incomplete->fresh()->status);
        $this->assertNull($incomplete->fresh()->rejection_reason);
        $this->assertSame($beforeScored, $ctx['application']->fresh()->only(['status', 'ranking_position', 'commission_decision', 'rejection_reason']));
    }

    public function test_older_valid_document_allows_close_when_the_newer_one_is_invalid(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $olderBytes = "PK\x03\x04starija-ispravna";
        $older = $this->storeBytes($ctx, $olderBytes);
        $newerBytes = "PK\x03\x04novija-neispravna";
        $newerPath = $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAY', $newerBytes);
        $newer = $this->rememberDocument(
            $ctx,
            $newerPath,
            $newerBytes,
            now()->addMinute(),
            $older->ranking_sha256,
            hash('sha256', 'ne-odgovara-fajlu')
        );

        $this->assertTrue($newer->generated_at->greaterThan($older->fresh()->generated_at));

        $this->postClose($ctx)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('completed', $ctx['competition']->fresh()->status);
        $this->assertSame($olderBytes, Storage::disk('local')->get($older->storage_path));
        $this->assertSame($newerBytes, Storage::disk('local')->get($newerPath));
        $this->assertNotNull($newer->fresh());
        $this->assertSame(2, YouthDecisionDocument::query()->where('competition_id', $ctx['competition']->id)->count());
    }

    public function test_redownload_selects_the_same_valid_document(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $olderBytes = "PK\x03\x04preuzimanje-ispravno";
        $older = $this->storeBytes($ctx, $olderBytes);
        $newerBytes = "PK\x03\x04preuzimanje-neispravno";
        $newerPath = $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAY', $newerBytes);
        $this->rememberDocument(
            $ctx,
            $newerPath,
            $newerBytes,
            now()->addMinute(),
            $older->ranking_sha256,
            hash('sha256', 'ne-odgovara-fajlu')
        );

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertOk();
        $this->assertSame($olderBytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertStringContainsString(
            'Odluka-o-raspodjeli-sredstava-mladi-'.$ctx['competition']->year.'-'.$ctx['competition']->id.'.docx',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertSame($olderBytes, Storage::disk('local')->get($older->storage_path));
        $this->assertSame($newerBytes, Storage::disk('local')->get($newerPath));
    }

    public function test_download_sends_no_file_when_no_document_is_valid(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $bytes = "PK\x03\x04nema-fajla";
        $relative = 'youth-decision-documents/'.$ctx['competition']->id.'/01ARZ3NDEKTSV4RRFFQ69G5FAV.docx';
        $this->rememberDocument($ctx, $relative, $bytes, now(), $this->rankingHash($ctx['competition']), hash('sha256', $bytes));

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertNotFound();
        $this->assertNull($response->headers->get('content-disposition'));
    }

    public function test_zensko_close_does_not_use_the_pm_document_guard(): void
    {
        $this->mock(YouthDecisionDocumentStore::class, function ($mock): void {
            $mock->shouldNotReceive('latestValidDocument');
        });
        $competition = $this->bareCompetition('zensko');
        $user = $this->komisijaUser();

        $this->actingAs($user)
            ->post(route('admin.competitions.close', $competition))
            ->assertForbidden();

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertNull($competition->fresh()->closed_at);
    }

    public function test_canonical_and_other_competitions_do_not_use_the_pm_document_guard(): void
    {
        $this->mock(YouthDecisionDocumentStore::class, function ($mock): void {
            $mock->shouldNotReceive('latestValidDocument');
        });
        $canonical = $this->bareCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $other = $this->bareCompetition('ostalo');
        $user = $this->komisijaUser();

        $canonicalResponse = $this->actingAs($user)
            ->from('/')
            ->post(route('admin.competitions.close', $canonical));
        $this->assertSame('published', $canonical->fresh()->status);
        $this->assertNull($canonical->fresh()->closed_at);
        $this->assertStringNotContainsString(
            YouthDecisionDocumentStore::CLOSE_BLOCKED,
            (string) session('errors')?->first('error')
        );

        $otherResponse = $this->actingAs($user)
            ->post(route('admin.competitions.close', $other));
        $otherResponse->assertForbidden();
        $this->assertSame('published', $other->fresh()->status);
        $this->assertNull($other->fresh()->closed_at);
        $canonicalResponse->assertRedirect();
    }

    public function test_generation_and_first_download_still_work(): void
    {
        $ctx = $this->closeableYouthCompetition();

        $generated = $this->actingAs($ctx['chairman'])
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), [
                'first_session_date' => '2026-02-02',
                'second_session_date' => '2026-03-03',
                'third_session_date' => '2026-04-04',
                'oral_presentation_date' => '2026-05-05',
            ]);

        $generated->assertOk();
        $bytes = file_get_contents($generated->baseResponse->getFile()->getPathname());
        $this->assertNotSame('', $bytes);
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertSame(1, YouthDecisionDocument::query()->where('competition_id', $ctx['competition']->id)->count());

        $downloaded = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));
        $downloaded->assertOk();
        $this->assertSame($bytes, file_get_contents($downloaded->baseResponse->getFile()->getPathname()));
        $this->assertSame('published', $ctx['competition']->fresh()->status);
    }

    public function test_close_button_is_hidden_until_a_valid_document_exists(): void
    {
        $ctx = $this->closeableYouthCompetition();
        $bytes = "PK\x03\x04skriveno";
        $relative = $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAV', $bytes);
        $document = $this->rememberDocument($ctx, $relative, $bytes, now(), str_repeat('cd', 32), hash('sha256', $bytes));

        $html = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-testid="youth-close-competition"', $html);
        $this->assertStringContainsString(YouthDecisionDocumentStore::CLOSE_BLOCKED, $html);
        $this->assertStringNotContainsString($document->storage_path, $html);
        $this->assertStringNotContainsString($document->content_sha256, $html);
        $this->assertStringNotContainsString($document->ranking_sha256, $html);

        $memberHtml = $this->actingAs($ctx['member'])
            ->get(route('evaluation.youth-decision', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-close-competition"', $memberHtml);
        $this->assertStringNotContainsString(YouthDecisionDocumentStore::CLOSE_BLOCKED, $memberHtml);
    }

    public function test_historical_cap_percent_does_not_change_locked_decision_word_or_close(): void
    {
        foreach ([15, 20, 30] as $percent) {
            $ctx = $this->youthCompetition();
            $application = $this->scoredAboveThresholdApplication($ctx, 'Plan '.$percent);
            $application->forceFill([
                'status' => 'approved',
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => '40000.00',
                'requested_amount' => '40000.00',
                'commission_justification' => 'Istorijski zaključak.',
                'commission_decision_date' => now(),
                'signed_by_chairman' => true,
                'youth_applied_cap_percent' => $percent,
            ])->save();

            $rewrite = $this->actingAs($ctx['chairman'])
                ->post(route('evaluation.youth-allocation-draft', $application), [
                    'commission_decision' => 'odbija',
                    'commission_justification' => 'Naknadna izmjena.',
                ]);
            $rewrite->assertForbidden();
            $this->assertSame(YouthAllocationDraftService::DECISION_LOCKED_MESSAGE, $rewrite->exception?->getMessage());

            $locked = $application->fresh();
            $this->assertSame('40000.00', $locked->approved_amount);
            $this->assertSame('podrzava_potpuno', $locked->commission_decision);
            $this->assertSame($percent, (int) $locked->youth_applied_cap_percent);
            $this->assertTrue($ctx['competition']->fresh()->hasChairmanCompletedDecisions());

            $proposal = app(YouthDecisionProposalBuilder::class)->build(
                $ctx['competition']->fresh(),
                [
                    'first_session_date' => '2026-02-02',
                    'second_session_date' => '2026-03-03',
                    'third_session_date' => '2026-04-04',
                    'oral_presentation_date' => '2026-05-05',
                ],
                now()
            );
            $this->assertSame('40.000,00', $proposal->winners[0]['approved']);
            $this->assertSame('40.000,00', $proposal->awardedTotal);

            $this->storeBytes($ctx, "PK\x03\x04istorijski-{$percent}");
            $this->postClose($ctx)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('completed', $ctx['competition']->fresh()->status);
            $this->assertSame($percent, (int) $application->fresh()->youth_applied_cap_percent);
            $this->assertSame('40000.00', $application->fresh()->approved_amount);
        }
    }

    private function postClose(array $ctx)
    {
        return $this->actingAs($ctx['chairman'])
            ->from(route('evaluation.youth-decision', $ctx['competition']))
            ->post(route('admin.competitions.close', $ctx['competition']));
    }

    private function assertUnchangedOpenCompetition(Competition $competition): void
    {
        $fresh = $competition->fresh();
        $this->assertSame('published', $fresh->status);
        $this->assertNull($fresh->closed_at);
    }

    /**
     * @param  array{competition: Competition, chairman: User}  $ctx
     */
    private function storeBytes(array $ctx, string $bytes): YouthDecisionDocument
    {
        $path = tempnam(sys_get_temp_dir(), 'pm-close-guard-');
        if ($path === false) {
            $this->fail('Testni ulazni fajl nije kreiran.');
        }
        file_put_contents($path, $bytes);
        $this->tempFiles[] = $path;

        return app(YouthDecisionDocumentStore::class)->store($ctx['competition'], $ctx['chairman'], $path);
    }

    /**
     * @param  array{competition: Competition, chairman: User}  $ctx
     */
    private function rememberDocument(
        array $ctx,
        string $relativePath,
        string $bytes,
        mixed $generatedAt,
        string $rankingHash,
        string $contentHash,
    ): YouthDecisionDocument {
        unset($bytes);

        return YouthDecisionDocument::query()->create([
            'competition_id' => $ctx['competition']->id,
            'generated_by' => $ctx['chairman']->id,
            'generated_at' => $generatedAt,
            'storage_path' => $relativePath,
            'content_sha256' => $contentHash,
            'ranking_sha256' => $rankingHash,
        ]);
    }

    private function writtenPath(int $competitionId, string $ulid, string $bytes): string
    {
        $relative = 'youth-decision-documents/'.$competitionId.'/'.$ulid.'.docx';
        $absolute = Storage::disk('local')->path($relative);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->fail('Testni direktorijum odluke nije kreiran.');
        }
        if (file_put_contents($absolute, $bytes) === false) {
            $this->fail('Testni dokument nije upisan.');
        }

        return $relative;
    }

    private function rankingHash(Competition $competition): string
    {
        return app(YouthDecisionRankingIdentity::class)->rankingSha256($competition);
    }

    /**
     * @param  array{competition: Competition, chairman: User}  $ctx
     */
    private function incompleteSubmittedApplication(array $ctx): Application
    {
        return Application::query()->create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => 'Nepotpuna prijava',
            'applicant_type' => 'fizicko_lice',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(15),
        ]);
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, chairmanMember: CommissionMember, application: Application}
     */
    private function closeableYouthCompetition(): array
    {
        $ctx = $this->youthCompetition();
        $application = Application::query()->create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => 'Plan ispod praga',
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Ana Anić',
            'status' => 'evaluated',
            'submitted_at' => now()->subDays(20),
            'evaluated_at' => now()->subDay(),
        ]);
        ApplicationEliminatoryCheck::query()->create([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now()->subDays(10),
            'confirmed_by_user_id' => $ctx['chairman']->id,
            'confirmed_by_commission_member_id' => $ctx['chairmanMember']->id,
            'confirmed_by_name' => $ctx['chairman']->name,
        ]);
        foreach ([0, 1, 2] as $index) {
            $payload = [
                'application_id' => $application->id,
                'commission_member_id' => $ctx['members'][$index]->id,
                'canonical_seat_no' => $index + 1,
                'completed_at' => now()->subDay(),
            ];
            for ($criterion = 1; $criterion <= 10; $criterion++) {
                $payload['criterion_'.$criterion] = 1;
            }
            EvaluationScore::query()->create($payload);
        }
        $application->forceFill([
            'bonuses_confirmed_at' => now()->subDay(),
            'final_score' => '10.00',
            'status' => 'rejected',
            'ranking_position' => 1,
            'commission_decision' => null,
            'approved_amount' => null,
            'rejection_reason' => YouthAllocationListConfirmationService::BELOW_THRESHOLD_REASON,
        ])->save();
        $ctx['application'] = $application->fresh();

        return $ctx;
    }

    /**
     * @param  array{competition: Competition, chairman: User, chairmanMember: CommissionMember, members: list<CommissionMember>}  $ctx
     */
    private function scoredAboveThresholdApplication(array $ctx, string $plan): Application
    {
        $application = Application::query()->create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => $plan,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Ana Anić',
            'status' => 'evaluated',
            'submitted_at' => now()->subDays(40),
            'evaluated_at' => now()->subDays(20),
            'requested_amount' => '40000.00',
        ]);
        ApplicationEliminatoryCheck::query()->create([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now()->subDays(30),
            'confirmed_by_user_id' => $ctx['chairman']->id,
            'confirmed_by_commission_member_id' => $ctx['chairmanMember']->id,
            'confirmed_by_name' => $ctx['chairman']->name,
        ]);
        foreach ([0, 1, 2] as $index) {
            $payload = [
                'application_id' => $application->id,
                'commission_member_id' => $ctx['members'][$index]->id,
                'canonical_seat_no' => $index + 1,
                'completed_at' => now()->subDay(),
            ];
            for ($criterion = 1; $criterion <= 10; $criterion++) {
                $payload['criterion_'.$criterion] = 5;
            }
            EvaluationScore::query()->create($payload);
        }
        $application->forceFill([
            'bonuses_confirmed_at' => now()->subDay(),
            'final_score' => '50.00',
        ])->save();
        BusinessPlan::query()->create([
            'application_id' => $application->id,
            'required_amount' => '50000.00',
        ]);
        app(CanonicalIndividualScoringService::class)
            ->persistYouthPreliminaryRankingIfReady($ctx['competition']->fresh());
        $ranked = $application->fresh();
        $this->assertSame(1, (int) $ranked->ranking_position);
        $this->assertSame('50.00', (string) $ranked->final_score);

        return $ranked;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, chairmanMember: CommissionMember, members: list<CommissionMember>}
     */
    private function youthCompetition(): array
    {
        $commission = Commission::query()->create([
            'name' => 'PM zatvaranje '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $roleId = Role::query()->where('name', 'komisija')->firstOrFail()->id;
        $chairman = null;
        $memberUser = null;
        $chairmanMember = null;
        $members = [];
        foreach ([1 => 'predsjednik', 2 => 'clan', 3 => 'clan'] as $seat => $position) {
            $user = User::factory()->create([
                'role_id' => $roleId,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $member = CommissionMember::query()->create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $position,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
            $members[] = $member;
            if ($position === 'predsjednik') {
                $chairman = $user;
                $chairmanMember = $member;
            }
            if ($memberUser === null && $position === 'clan') {
                $memberUser = $user;
            }
        }
        $competition = Competition::query()->create([
            'title' => 'PM zatvaranje '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'commission_id' => $commission->id,
        ]);
        $this->cleanupCompetitionIds[] = (int) $competition->id;

        return [
            'competition' => $competition,
            'chairman' => $chairman,
            'member' => $memberUser,
            'chairmanMember' => $chairmanMember,
            'members' => $members,
        ];
    }

    private function bareCompetition(string $type): Competition
    {
        return Competition::query()->create([
            'title' => 'Van PM '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
        ]);
    }

    private function komisijaUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
