<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\ZenskoDecisionProposalSnapshot;
use App\Services\Competitions\CompetitionDecisionDocumentBuilder;
use App\Services\Competitions\ZenskoDecisionProposalHtmlStore;
use App\Services\Competitions\ZenskoDecisionProposalStoreException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ZpDecisionProposalHtmlStoreTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private ?string $createdCrest = null;

    /** @var array<string, string> */
    private array $hiddenMarks = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureLocalMark();
    }

    protected function tearDown(): void
    {
        $this->restoreLocalMarks();
        if ($this->createdCrest !== null && is_file($this->createdCrest)) {
            unlink($this->createdCrest);
        }

        parent::tearDown();
    }

    public function test_store_writes_the_decision_html(): void
    {
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        Storage::fake('local');
        $competition = $this->createCompetition();

        $stored = $this->store()->store($competition);
        $html = Storage::disk('local')->get($stored->storagePath);

        $this->assertSame($competition->id, $stored->toArray()['competition_id']);
        $this->assertSame($stored->storagePath, $stored->toArray()['storage_path']);
        $this->assertSame($stored->contentSha256, $stored->toArray()['content_sha256']);
        $this->assertIsString($html);
        $this->assertStringContainsString('<style', $html);
        $this->assertStringContainsString('class="decision-document"', $html);
        $this->assertStringContainsString('ODLUKU', $html);
        $this->assertStringContainsString('Član 1', $html);
    }

    public function test_stored_bytes_match_sha256(): void
    {
        Storage::fake('local');
        $stored = $this->store()->store($this->createCompetition());
        $html = Storage::disk('local')->get($stored->storagePath);

        $this->assertSame(64, strlen($stored->contentSha256));
        $this->assertSame(hash('sha256', (string) $html), $stored->contentSha256);
    }

    public function test_each_attempt_uses_a_distinct_path(): void
    {
        Storage::fake('local');
        $competition = $this->createCompetition();
        $store = $this->store();

        $first = $store->store($competition);
        $second = $store->store($competition);

        $this->assertNotSame($first->storagePath, $second->storagePath);
        Storage::disk('local')->assertExists($first->storagePath);
        Storage::disk('local')->assertExists($second->storagePath);
        $this->assertStringStartsWith('zensko-decision-proposals/'.$competition->id.'/', $first->storagePath);
        $this->assertStringStartsWith('zensko-decision-proposals/'.$competition->id.'/', $second->storagePath);
        $this->assertMatchesRegularExpression('/[0-9A-HJKMNP-TV-Z]{26}\.html$/', $first->storagePath);
        $this->assertMatchesRegularExpression('/[0-9A-HJKMNP-TV-Z]{26}\.html$/', $second->storagePath);
    }

    public function test_unsupported_competition_type_is_rejected(): void
    {
        Storage::fake('local');

        foreach (['omladinsko', 'preduzetnici_mikro_mala_i_srednja_preduzeca'] as $type) {
            try {
                $this->store()->store($this->createCompetition($type));
                $this->fail($type.' mora biti odbijen.');
            } catch (ZenskoDecisionProposalStoreException $exception) {
                $this->assertSame(
                    'Snimak Predloga odluke može da se sačuva samo za konkurs ženskog preduzetništva.',
                    $exception->getMessage()
                );
            }
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_failed_write_is_rejected(): void
    {
        $competition = $this->createCompetition();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldNotReceive('get');
        $disk->shouldNotReceive('path');
        $disk->shouldNotReceive('delete');
        $disk->shouldNotReceive('exists');
        Storage::set('local', $disk);

        try {
            $this->store()->store($competition);
            $this->fail('Neuspjeo upis mora biti odbijen.');
        } catch (ZenskoDecisionProposalStoreException $exception) {
            $this->assertSame('Privremeni fajl Predloga odluke nije upisan.', $exception->getMessage());
        }
    }

    public function test_failed_publish_is_rejected_without_replacing_an_existing_path(): void
    {
        $competition = $this->createCompetition();
        $blockingDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'zp04b-block-'.bin2hex(random_bytes(4));
        mkdir($blockingDirectory);
        $captured = null;
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturnUsing(function (string $path, string $contents) use (&$captured): bool {
            $captured = $contents;

            return true;
        });
        $disk->shouldReceive('exists')->once()->andReturn(false);
        $disk->shouldReceive('get')->once()->andReturnUsing(function () use (&$captured): ?string {
            return $captured;
        });
        $disk->shouldReceive('path')->once()->andReturn($blockingDirectory);
        $disk->shouldReceive('delete')->once()->with(Mockery::on(
            fn (string $path): bool => str_ends_with($path, '.html.partial')
        ))->andReturn(true);
        Storage::set('local', $disk);

        try {
            $this->store()->store($competition);
            $this->fail('Neuspjelo premještanje mora biti odbijeno.');
        } catch (ZenskoDecisionProposalStoreException $exception) {
            $this->assertSame(
                'Konačni fajl Predloga odluke nije mogao da se premjesti bez prepisivanja.',
                $exception->getMessage()
            );
            $this->assertDirectoryExists($blockingDirectory);
        } finally {
            if (is_dir($blockingDirectory)) {
                rmdir($blockingDirectory);
            }
        }
    }

    public function test_integrity_mismatch_is_rejected_and_leaves_other_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('zensko-decision-proposals/keep.html', 'KEEP');
        $store = new class(app(CompetitionDecisionDocumentBuilder::class)) extends ZenskoDecisionProposalHtmlStore
        {
            protected function readFinalBytes(FilesystemAdapter $disk, string $path): string
            {
                return 'tampered-bytes';
            }
        };

        try {
            $store->store($this->createCompetition());
            $this->fail('Neuspjela provjera integriteta mora biti odbijena.');
        } catch (ZenskoDecisionProposalStoreException $exception) {
            $this->assertSame(
                'Provjera integriteta sačuvanog Predloga odluke nije uspjela.',
                $exception->getMessage()
            );
        }

        $this->assertSame(['zensko-decision-proposals/keep.html'], Storage::disk('local')->allFiles());
        $this->assertSame('KEEP', Storage::disk('local')->get('zensko-decision-proposals/keep.html'));
    }

    public function test_existing_final_file_is_not_overwritten(): void
    {
        Storage::fake('local');
        $competition = $this->createCompetition();
        $store = new class(app(CompetitionDecisionDocumentBuilder::class)) extends ZenskoDecisionProposalHtmlStore
        {
            protected function newDocumentId(): string
            {
                return '01ARZ3NDEKTSV4RRFFQ69G5FAV';
            }
        };

        $stored = $store->store($competition);
        $original = Storage::disk('local')->get($stored->storagePath);

        try {
            $store->store($competition);
            $this->fail('Postojeći fajl ne smije biti prepisan.');
        } catch (ZenskoDecisionProposalStoreException $exception) {
            $this->assertSame(
                'Konačni fajl Predloga odluke već postoji i neće biti prepisan.',
                $exception->getMessage()
            );
        }

        $this->assertSame($original, Storage::disk('local')->get($stored->storagePath));
        $this->assertSame([$stored->storagePath], Storage::disk('local')->allFiles());
    }

    public function test_local_mark_is_embedded_as_data_uri(): void
    {
        Storage::fake('local');
        $mark = is_file(public_path('img/grb-kotor.png'))
            ? public_path('img/grb-kotor.png')
            : public_path('img/logo.png');
        $stored = $this->store()->store($this->createCompetition());
        $html = (string) Storage::disk('local')->get($stored->storagePath);

        $this->assertStringContainsString(
            'data:image/png;base64,'.base64_encode((string) file_get_contents($mark)),
            $html
        );
        $this->assertStringNotContainsString('img/grb-kotor.png', $html);
        $this->assertStringNotContainsString('img/logo.png', $html);
        $this->assertDoesNotMatchRegularExpression('/<img\b[^>]*\bsrc\s*=\s*["\'](?!data:)/i', $html);
    }

    public function test_missing_local_mark_is_rejected(): void
    {
        Storage::fake('local');
        $this->hideLocalMarks();

        try {
            $this->store()->store($this->createCompetition());
            $this->fail('Nedostajući grb ili logotip mora zaustaviti čuvanje.');
        } catch (ZenskoDecisionProposalStoreException $exception) {
            $this->assertSame(
                'Predlog odluke ne može da se sačuva jer nedostaje lokalni grb ili logotip.',
                $exception->getMessage()
            );
        } finally {
            $this->restoreLocalMarks();
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_stored_document_has_no_admin_controls(): void
    {
        Storage::fake('local');
        $stored = $this->store()->store($this->createCompetition());
        $html = (string) Storage::disk('local')->get($stored->storagePath);

        $this->assertStringNotContainsString('Štampaj Odluku', $html);
        $this->assertStringNotContainsString('Nazad na rang listu', $html);
        $this->assertStringNotContainsString('Zatvori konkurs', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_store_does_not_change_competition_or_database_rows(): void
    {
        Storage::fake('local');
        $competition = $this->createCompetition();
        $updatedAt = $competition->updated_at?->toISOString();

        $this->store()->store($competition);
        $fresh = $competition->fresh();

        $this->assertSame('published', $fresh->status);
        $this->assertNull($fresh->closed_at);
        $this->assertNull($fresh->zensko_decision_proposal_snapshot_id);
        $this->assertSame($updatedAt, $fresh->updated_at?->toISOString());
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame(0, Notice::query()->count());
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
    }

    private function store(): ZenskoDecisionProposalHtmlStore
    {
        return app(ZenskoDecisionProposalHtmlStore::class);
    }

    private function createCompetition(string $type = 'zensko'): Competition
    {
        return Competition::query()->create([
            'title' => 'Žensko preduzetništvo — HTML snimak',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
        ]);
    }

    private function ensureLocalMark(): void
    {
        $path = public_path('img/grb-kotor.png');
        if (is_file($path) || is_file(public_path('img/logo.png'))) {
            return;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, base64_decode(self::PNG_BASE64, true));
        $this->createdCrest = $path;
    }

    private function hideLocalMarks(): void
    {
        foreach (['grb-kotor.png', 'logo.png'] as $name) {
            $path = public_path('img/'.$name);
            if (! is_file($path)) {
                continue;
            }

            $backup = $path.'.zp04b-hidden';
            rename($path, $backup);
            $this->hiddenMarks[$path] = $backup;
        }
    }

    private function restoreLocalMarks(): void
    {
        foreach ($this->hiddenMarks as $path => $backup) {
            if (is_file($backup)) {
                rename($backup, $path);
            }
        }

        $this->hiddenMarks = [];
    }
}
