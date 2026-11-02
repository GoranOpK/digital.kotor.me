<?php

namespace Tests\Feature;

use App\Http\Controllers\EvaluationController;
use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\Competitions\YouthDecisionDocumentStore;
use App\Services\Competitions\YouthDecisionDocumentStoreException;
use App\Services\Competitions\YouthDecisionDocxWriter;
use App\Services\Competitions\YouthDecisionProposalBuilder;
use App\Services\Competitions\YouthDecisionProposalGate;
use App\Services\Competitions\YouthDecisionRankingIdentity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpZip\ZipFile;
use Tests\TestCase;

class YouthDecisionGenerationStorageTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<int> */
    private array $cleanupCompetitionIds = [];

    /** @var list<string> */
    private array $tempFiles = [];

    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    private array $cleanupLogs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        if ($this->app) {
            foreach ($this->cleanupCompetitionIds as $id) {
                $directory = Storage::disk('local')->path('youth-decision-documents/'.$id);
                if (is_dir($directory) && PHP_OS_FAMILY === 'Windows') {
                    exec('attrib -R /S /D '.escapeshellarg($directory));
                }
                Storage::disk('local')->deleteDirectory('youth-decision-documents/'.$id);
            }
        }

        parent::tearDown();
    }

    public function test_builder_allows_a_final_decision_without_a_registry_number_or_supported_plans(): void
    {
        $competition = $this->youthCompetition()['competition'];
        Application::query()->create([
            'competition_id' => $competition->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => 'Odbijeni plan',
            'applicant_type' => 'fizicko_lice',
            'status' => 'rejected',
            'commission_decision' => 'odbija',
            'approved_amount' => '0.00',
            'commission_justification' => 'Nije podržano.',
            'submitted_at' => now()->subDay(),
        ]);

        $proposal = app(YouthDecisionProposalBuilder::class)->build($competition, $this->dates(), now());

        $this->assertSame('', $proposal->decisionNumber);
        $this->assertSame([], $proposal->winners);
        $this->assertSame('0,00', $proposal->awardedTotal);
        $this->assertSame('02.02.2026', $proposal->firstSessionDate);
        $this->assertSame('03.03.2026', $proposal->secondSessionDate);
        $this->assertSame('04.04.2026', $proposal->thirdSessionDate);
        $this->assertSame('03.03.2026', $proposal->oralDate);
    }

    public function test_builder_rejects_other_profiles(): void
    {
        $competition = Competition::query()->create([
            'title' => 'Zensko van PM',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);

        try {
            app(YouthDecisionProposalBuilder::class)->build($competition, $this->dates(), now());
            $this->fail('Ženski konkurs ne smije dobiti PM odluku.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                YouthDecisionProposalGate::NOT_YOUTH_MESSAGE,
                $exception->errors()['decision'][0]
            );
        }
    }

    public function test_generation_is_refused_for_unauthorized_users_and_before_finality(): void
    {
        $ctx = $this->youthCompetition();

        $this->actingAs($ctx['member'])
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates())
            ->assertForbidden();

        $this->actingAs($ctx['chairman'])
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates())
            ->assertForbidden();

        $zensko = Competition::query()->create([
            'title' => 'Zensko HTTP',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
        $this->actingAs($ctx['chairman'])
            ->post(route('evaluation.youth-decision.generate', $zensko), $this->dates())
            ->assertForbidden();

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame('published', $ctx['competition']->fresh()->status);
    }

    public function test_registry_number_is_not_required_and_dates_remain_required(): void
    {
        $ctx = $this->historicallyFinalYouthCompetition();

        $this->actingAs($ctx['chairman'])
            ->from(route('evaluation.youth-decision', $ctx['competition']))
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), [])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'first_session_date',
                'second_session_date',
                'third_session_date',
            ])
            ->assertSessionDoesntHaveErrors(['decision_number', 'oral_presentation_date']);

        $html = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('name="decision_number"', $html);
        $this->assertStringNotContainsString('name="oral_presentation_date"', $html);
        $this->assertStringNotContainsString('Datum usmenih obrazloženja', $html);
        $formStart = strpos($html, 'data-testid="youth-decision-generate-form"');
        $formEnd = strpos($html, '</form>', $formStart);
        $this->assertNotFalse($formStart);
        $this->assertNotFalse($formEnd);
        $this->assertSame(3, substr_count(substr($html, $formStart, $formEnd - $formStart), 'type="date"'));
        $this->assertStringContainsString('name="first_session_date"', $html);
        $this->assertStringContainsString('name="second_session_date"', $html);
        $this->assertStringContainsString('name="third_session_date"', $html);
        $this->assertSame(0, YouthDecisionDocument::query()->count());
    }

    public function test_ranking_change_during_generation_does_not_store_a_document(): void
    {
        $ctx = $this->historicallyFinalYouthCompetition();
        $temp = $this->tempDocx("PK\x03\x04privremeno");
        $this->mock(YouthDecisionDocxWriter::class, function ($mock) use ($temp) {
            $mock->shouldReceive('write')->once()->andReturn($temp);
        });
        $this->mock(YouthDecisionRankingIdentity::class, function ($mock) {
            $mock->shouldReceive('rankingSha256')->andReturn(str_repeat('a', 64), str_repeat('b', 64));
        });
        $this->mock(YouthDecisionDocumentStore::class, function ($mock) {
            $mock->shouldReceive('store')->never();
        });

        $this->actingAs($ctx['chairman'])
            ->from(route('evaluation.youth-decision', $ctx['competition']))
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates())
            ->assertRedirect()
            ->assertSessionHasErrors('decision');

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles('youth-decision-documents/'.$ctx['competition']->id));
    }

    public function test_failed_storage_preserves_an_earlier_version_and_does_not_close(): void
    {
        $ctx = $this->historicallyFinalYouthCompetition();
        $bytes = "PK\x03\x04ranija-verzija";
        $existing = app(YouthDecisionDocumentStore::class)->store(
            $ctx['competition'],
            $ctx['chairman'],
            $this->tempDocx($bytes)
        );
        $temp = $this->tempDocx("PK\x03\x04novi-pokusaj");
        $this->mock(YouthDecisionDocxWriter::class, function ($mock) use ($temp) {
            $mock->shouldReceive('write')->once()->andReturn($temp);
        });
        $this->mock(YouthDecisionRankingIdentity::class, function ($mock) {
            $mock->shouldReceive('rankingSha256')->andReturn(str_repeat('c', 64));
        });
        $this->mock(YouthDecisionDocumentStore::class, function ($mock) {
            $mock->shouldReceive('store')->once()->andThrow(
                new YouthDecisionDocumentStoreException(YouthDecisionDocumentStore::NOT_STORED)
            );
        });

        $this->actingAs($ctx['chairman'])
            ->from(route('evaluation.youth-decision', $ctx['competition']))
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates())
            ->assertRedirect()
            ->assertSessionHasErrors('decision');

        $this->assertSame(1, YouthDecisionDocument::query()->count());
        $this->assertSame($bytes, Storage::disk('local')->get($existing->storage_path));
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertNull($ctx['competition']->fresh()->closed_at);
    }

    public function test_identity_change_found_by_storage_rolls_back_the_new_record(): void
    {
        $ctx = $this->historicallyFinalYouthCompetition();
        $temp = $this->tempDocx("PK\x03\x04promijenjen-identitet");
        $this->mock(YouthDecisionDocxWriter::class, function ($mock) use ($temp) {
            $mock->shouldReceive('write')->once()->andReturn($temp);
        });
        $hashes = [str_repeat('a', 64), str_repeat('a', 64), str_repeat('d', 64)];
        $this->mock(YouthDecisionRankingIdentity::class, function ($mock) use ($hashes) {
            $mock->shouldReceive('rankingSha256')->andReturn(...$hashes);
        });

        $this->actingAs($ctx['chairman'])
            ->from(route('evaluation.youth-decision', $ctx['competition']))
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates())
            ->assertRedirect()
            ->assertSessionHasErrors('decision');

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('youth-decision-documents/'.$ctx['competition']->id));
        $this->assertSame('published', $ctx['competition']->fresh()->status);
    }

    public function test_failed_word_generation_creates_no_record_and_does_not_close(): void
    {
        $ctx = $this->historicallyFinalYouthCompetition();
        $this->mock(YouthDecisionDocxWriter::class, function ($mock) {
            $mock->shouldReceive('write')->once()->andThrow(new \RuntimeException('Template nema očekivani pasus: Broj:'));
        });

        $this->actingAs($ctx['chairman'])
            ->from(route('evaluation.youth-decision', $ctx['competition']))
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates())
            ->assertRedirect()
            ->assertSessionHasErrors([
                'decision' => 'Odluka nije generisana i nije trajno sačuvana.',
            ]);

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertNull($ctx['competition']->fresh()->closed_at);
    }

    public function test_real_word_generation_is_blocked_until_the_zip_package_is_present(): void
    {
        if (class_exists(\PhpZip\ZipFile::class)) {
            $this->assertTrue(is_file(resource_path('templates/pm/odluka-o-raspodjeli-sredstava.docx')));

            return;
        }

        $this->markTestSkipped('BLOCKED: nelexa/zip nije instaliran, pa stvarno generisanje Word odluke nije izvršeno.');
    }

    public function test_chairman_stores_and_downloads_the_real_docx(): void
    {
        $this->captureCleanupLogs();
        $ctx = $this->historicallyFinalYouthCompetition();
        $generatedAt = Carbon::now(config('app.timezone'));
        Carbon::setTestNow($generatedAt);
        try {
            $response = $this->actingAs($ctx['chairman'])
                ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $this->dates());
        } finally {
            Carbon::setTestNow();
        }

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $response->headers->get('content-type')
        );

        $downloadPath = $response->baseResponse->getFile()->getPathname();
        $downloadBytes = file_get_contents($downloadPath);
        $this->assertIsString($downloadBytes);
        $this->assertStringStartsWith("PK\x03\x04", $downloadBytes);

        $this->assertSame(1, YouthDecisionDocument::query()->count());
        $document = YouthDecisionDocument::query()->firstOrFail();
        $this->assertSame((int) $ctx['competition']->id, (int) $document->competition_id);
        $this->assertSame((int) $ctx['chairman']->id, (int) $document->generated_by);
        $this->assertMatchesRegularExpression(
            '#^youth-decision-documents/'.$ctx['competition']->id.'/[0-9A-HJKMNP-TV-Z]{26}\.docx$#',
            $document->storage_path
        );

        $storedBytes = Storage::disk('local')->get($document->storage_path);
        $storedPath = Storage::disk('local')->path($document->storage_path);
        $this->assertFileExists($storedPath);
        $this->assertSame(realpath($storedPath), realpath($downloadPath));
        $this->assertSame($storedBytes, $downloadBytes);
        $this->assertSame(hash('sha256', $downloadBytes), $document->content_sha256);
        $this->assertSame(hash('sha256', (string) $storedBytes), $document->content_sha256);
        $this->assertSame(
            app(YouthDecisionRankingIdentity::class)->rankingSha256($ctx['competition']->fresh()),
            $document->ranking_sha256
        );

        $privateRoot = realpath(storage_path('app/private'));
        $storedReal = realpath($storedPath);
        $publicRoot = realpath(storage_path('app/public'));
        $this->assertNotFalse($privateRoot);
        $this->assertNotFalse($storedReal);
        $this->assertNotFalse($publicRoot);
        $this->assertStringStartsWith(
            strtolower(str_replace('\\', '/', $privateRoot)),
            strtolower(str_replace('\\', '/', $storedReal))
        );
        $this->assertStringStartsNotWith(
            strtolower(str_replace('\\', '/', $publicRoot)),
            strtolower(str_replace('\\', '/', $storedReal))
        );
        $this->assertFalse(Storage::disk('public')->exists($document->storage_path));

        $zip = new ZipFile();
        $zip->openFile($storedPath);
        $this->assertTrue($zip->hasEntry('word/document.xml'));
        $this->assertTrue($zip->hasEntry('[Content_Types].xml'));
        $documentXml = $zip->getEntryContents('word/document.xml');
        $zip->close();

        $this->assertEmptyRegistrySlot($documentXml, $generatedAt->format('d.m.Y.'));
        $text = strip_tags($documentXml);
        $this->assertStringContainsString('02.02.2026', $text);
        $this->assertStringContainsString('03.03.2026', $text);
        $this->assertStringContainsString('04.04.2026', $text);
        $this->assertStringContainsString('na drugoj sjednici Komisije održanoj dana 03.03.2026. godine', $text);
        $this->assertStringNotContainsString('05.05.2026', $text);
        $this->assertGreaterThanOrEqual(2, substr_count($text, '03.03.2026'));

        $previousBytes = $storedBytes;
        $previousPath = $storedPath;
        $again = $this->dates();
        unset($again['oral_presentation_date']);
        $again['second_session_date'] = '2026-06-06';
        $this->actingAs($ctx['chairman'])
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), $again)
            ->assertOk();
        $this->assertSame($previousBytes, file_get_contents($previousPath));
        $this->assertSame(2, YouthDecisionDocument::query()->count());

        $this->assertFileExists($storedPath);
        $this->assertSame($downloadBytes, file_get_contents($storedPath));
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertNull($ctx['competition']->fresh()->closed_at);
        $this->assertNoCleanupLog();
    }

    public function test_cleanup_deletes_a_new_unreferenced_file(): void
    {
        $ctx = $this->youthCompetition();
        $relative = $this->decisionRelativePath((int) $ctx['competition']->id);
        $absolute = $this->writePrivateDecision($relative, "PK\x03\x04novi");
        $this->captureCleanupLogs();

        $this->discardFailedYouthDecision(null, $relative);

        $this->assertFileDoesNotExist($absolute);
        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertNoCleanupLog();
    }

    public function test_cleanup_is_quiet_when_the_unreferenced_file_is_already_gone(): void
    {
        $ctx = $this->youthCompetition();
        $relative = $this->decisionRelativePath((int) $ctx['competition']->id);
        $this->captureCleanupLogs();

        $this->discardFailedYouthDecision(null, $relative);

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertNoCleanupLog();
    }

    public function test_cleanup_keeps_a_referenced_document(): void
    {
        $ctx = $this->youthCompetition();
        $source = $this->tempDocx("PK\x03\x04sacuvana-verzija");
        $document = app(YouthDecisionDocumentStore::class)->store($ctx['competition'], $ctx['chairman'], $source);
        $bytes = Storage::disk('local')->get($document->storage_path);
        $this->captureCleanupLogs();

        $this->discardFailedYouthDecision(null, $document->storage_path);

        $this->assertSame($bytes, Storage::disk('local')->get($document->storage_path));
        $this->assertSame(1, YouthDecisionDocument::query()->whereKey($document->id)->count());
        $this->assertSame('published', $ctx['competition']->fresh()->status);
        $this->assertNoCleanupLog();
    }

    public function test_cleanup_rejects_a_path_outside_the_private_decision_directory(): void
    {
        $sentinel = storage_path('app/private/pm-cleanup-sentinel.txt');
        file_put_contents($sentinel, 'sentinel');
        $this->captureCleanupLogs();

        try {
            $this->discardFailedYouthDecision(null, $sentinel);
            $this->assertSame('sentinel', file_get_contents($sentinel));
            $this->assertSafeCleanupLog('path_not_allowed', forbidden: [$sentinel, 'sentinel', 'pm-cleanup-sentinel']);
        } finally {
            if (is_file($sentinel)) {
                unlink($sentinel);
            }
        }
    }

    public function test_cleanup_rejects_a_malformed_document_name(): void
    {
        $ctx = $this->youthCompetition();
        $relative = 'youth-decision-documents/'.$ctx['competition']->id.'/nije-ulid.docx';
        $absolute = $this->writePrivateDecision($relative, "PK\x03\x04los-naziv");
        $this->captureCleanupLogs();

        $this->discardFailedYouthDecision(null, $relative);

        $this->assertSame("PK\x03\x04los-naziv", file_get_contents($absolute));
        $this->assertSafeCleanupLog('path_not_allowed', forbidden: [$relative, $absolute, 'nije-ulid', 'los-naziv']);
    }

    public function test_cleanup_keeps_the_file_when_the_reference_check_fails(): void
    {
        $ctx = $this->youthCompetition();
        $relative = $this->decisionRelativePath((int) $ctx['competition']->id);
        $absolute = $this->writePrivateDecision($relative, "PK\x03\x04provjera");
        $armed = true;
        DB::connection()->beforeExecuting(function (string $query) use (&$armed): void {
            if ($armed && str_contains($query, 'youth_decision_documents')) {
                throw new \RuntimeException('privremeni prekid provjere');
            }
        });
        $this->captureCleanupLogs();

        try {
            $this->discardFailedYouthDecision(null, $relative);
        } finally {
            $armed = false;
        }

        $this->assertSame("PK\x03\x04provjera", file_get_contents($absolute));
        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSafeCleanupLog(
            'reference_check_failed',
            (int) $ctx['competition']->id,
            '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            ['privremeni prekid provjere', $absolute, "PK\x03\x04provjera"]
        );
    }

    public function test_failed_deletion_is_logged_without_sensitive_data(): void
    {
        $ctx = $this->youthCompetition();
        $relative = $this->decisionRelativePath((int) $ctx['competition']->id);
        $absolute = $this->writePrivateDecision($relative, "PK\x03\x04zakljucan");
        $this->captureCleanupLogs();

        try {
            $this->denyDeletion($absolute);
            $this->discardFailedYouthDecision(null, $relative);
            $this->assertFileExists($absolute);
            $this->assertSame("PK\x03\x04zakljucan", file_get_contents($absolute));
            $this->assertSafeCleanupLog(
                'delete_failed',
                (int) $ctx['competition']->id,
                '01ARZ3NDEKTSV4RRFFQ69G5FAV',
                [$absolute, 'zakljucan', $ctx['chairman']->name, $ctx['chairman']->email]
            );
        } finally {
            $this->allowDeletion($absolute);
        }
    }

    public function test_cleanup_failure_preserves_the_original_exception(): void
    {
        $ctx = $this->youthCompetition();
        $relative = $this->decisionRelativePath((int) $ctx['competition']->id);
        $absolute = $this->writePrivateDecision($relative, "PK\x03\x04izuzetak");
        $original = ValidationException::withMessages([
            'decision' => 'Rang-lista je izmijenjena tokom generisanja. Odluka nije sačuvana.',
        ]);
        $this->captureCleanupLogs();

        try {
            $this->denyDeletion($absolute);
            try {
                throw $original;
            } catch (ValidationException $exception) {
                $this->discardFailedYouthDecision(null, $relative);
                throw $exception;
            }
        } catch (ValidationException $caught) {
            $this->assertSame($original, $caught);
            $this->assertSame(
                'Rang-lista je izmijenjena tokom generisanja. Odluka nije sačuvana.',
                $caught->errors()['decision'][0]
            );
        } finally {
            $this->allowDeletion($absolute);
        }

        $this->assertFileExists($absolute);
        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame('published', $ctx['competition']->fresh()->status);
    }

    /**
     * @return array{first_session_date: string, second_session_date: string, third_session_date: string, oral_presentation_date: string}
     */
    private function dates(): array
    {
        return [
            'first_session_date' => '2026-02-02',
            'second_session_date' => '2026-03-03',
            'third_session_date' => '2026-04-04',
            'oral_presentation_date' => '2026-05-05',
        ];
    }

    private function assertEmptyRegistrySlot(string $documentXml, string $headerDate): void
    {
        $nodes = $this->brojTextNodes($documentXml);
        $this->assertCount(9, $nodes);
        $this->assertSame('Broj', $nodes[0]);
        $this->assertSame(':  ', $nodes[1]);
        $this->assertSame('', $nodes[2]);
        $this->assertMatchesRegularExpression('/^\s+$/', $nodes[3]);
        $this->assertDoesNotMatchRegularExpression('/XX/', $nodes[1].$nodes[2].$nodes[3]);
        $this->assertDoesNotMatchRegularExpression('/\d/', $nodes[1].$nodes[2].$nodes[3]);
        $this->assertSame('Kotor', $nodes[4]);
        $this->assertSame(', ', $nodes[5]);
        $this->assertSame($headerDate, $nodes[6]);
        $this->assertSame(' ', $nodes[7]);
        $this->assertSame('godine', $nodes[8]);
    }

    /**
     * @return list<string>
     */
    private function brojTextNodes(string $documentXml): array
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($documentXml));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            if (! $paragraph instanceof \DOMElement) {
                continue;
            }
            $nodes = [];
            foreach ($xpath->query('.//w:t', $paragraph) ?: [] as $node) {
                $nodes[] = $node->textContent;
            }
            if (($nodes[0] ?? '') === 'Broj') {
                return $nodes;
            }
        }

        $this->fail('Dokument nema pasus Broj:.');
    }

    private function tempDocx(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pm-gen-');
        if ($path === false) {
            $this->fail('Testni Word fajl nije kreiran.');
        }
        file_put_contents($path, $bytes);
        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, chairmanMember: CommissionMember, members: list<CommissionMember>}
     */
    private function historicallyFinalYouthCompetition(): array
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
        ])->save();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, chairmanMember: CommissionMember, members: list<CommissionMember>}
     */
    private function youthCompetition(): array
    {
        $commission = Commission::query()->create([
            'name' => 'PM generisanje '.uniqid(),
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
            'title' => 'PM generisanje '.uniqid(),
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

    private function discardFailedYouthDecision(?string $temporaryPath, ?string $storedPath): void
    {
        $method = new \ReflectionMethod(EvaluationController::class, 'discardFailedYouthDecisionFiles');
        $method->invoke(app(EvaluationController::class), $temporaryPath, $storedPath);
    }

    private function decisionRelativePath(int $competitionId): string
    {
        return 'youth-decision-documents/'.$competitionId.'/01ARZ3NDEKTSV4RRFFQ69G5FAV.docx';
    }

    private function writePrivateDecision(string $relative, string $bytes): string
    {
        $absolute = Storage::disk('local')->path($relative);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->fail('Testni direktorijum odluke nije kreiran.');
        }
        if (is_file($absolute)) {
            $this->allowDeletion($absolute);
        }
        if (file_put_contents($absolute, $bytes) === false) {
            $this->fail('Testni dokument nije upisan.');
        }

        return $absolute;
    }

    private function denyDeletion(string $absolute): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec('attrib +R '.escapeshellarg($absolute), $output, $code);
            $this->assertSame(0, $code);

            return;
        }

        $this->assertTrue(chmod($absolute, 0444));
    }

    private function allowDeletion(string $absolute): void
    {
        if (! is_file($absolute)) {
            return;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            exec('attrib -R '.escapeshellarg($absolute));

            return;
        }

        chmod($absolute, 0666);
    }

    /**
     * @param  list<string>  $forbidden
     */
    private function assertSafeCleanupLog(
        string $outcome,
        ?int $competitionId = null,
        ?string $documentId = null,
        array $forbidden = [],
    ): void {
        $matches = array_values(array_filter(
            $this->cleanupLogs,
            fn (array $log): bool => ($log['context']['outcome'] ?? null) === $outcome
        ));
        $this->assertCount(1, $matches);
        $log = $matches[0];
        $this->assertSame('warning', $log['level']);
        $this->assertSame('Čišćenje PM odluke nije završeno.', $log['message']);
        $allowed = ['operation', 'outcome'];
        if ($competitionId !== null) {
            $allowed[] = 'competition_id';
            $this->assertSame($competitionId, $log['context']['competition_id'] ?? null);
        }
        if ($documentId !== null) {
            $allowed[] = 'document_id';
            $this->assertSame($documentId, $log['context']['document_id'] ?? null);
        }
        $this->assertEqualsCanonicalizing($allowed, array_keys($log['context']));
        $encoded = (string) json_encode([$log['message'], $log['context']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        foreach ($forbidden as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z]:[\\\\\\/]/', $encoded);
        $this->assertStringNotContainsString('storage/app', strtolower($encoded));
    }

    private function captureCleanupLogs(): void
    {
        $this->cleanupLogs = [];
        Log::listen(function (object $event): void {
            $context = $event->context ?? null;
            if (! is_array($context) || ($context['operation'] ?? null) !== 'youth_decision_cleanup') {
                return;
            }
            $this->cleanupLogs[] = [
                'level' => (string) $event->level,
                'message' => (string) $event->message,
                'context' => $context,
            ];
        });
    }

    private function assertNoCleanupLog(): void
    {
        $this->assertSame([], $this->cleanupLogs);
    }
}
