<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\Competitions\YouthDecisionDocumentStore;
use App\Services\Competitions\YouthDecisionDocumentStoreException;
use App\Services\Competitions\YouthDecisionRankingIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class YouthDecisionDocumentStoreTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\MakesIdentitySnapshots;

    /** @var list<string> */
    private array $tempFiles = [];

    /**
     * Dokumenti koje je ovaj test sam kreirao. tearDown briše samo ove putanje.
     *
     * @var list<string>
     */
    private array $ownedDocumentPaths = [];

    /**
     * Dokumenti koji su postojali prije testa. Ključ je relativna putanja, vrijednost je SHA-256.
     *
     * @var array<string, string>
     */
    private array $documentsBeforeTest = [];

    private bool $capturedExistingDocuments = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame($this->expectedTestDatabase(), (string) config('database.connections.mysql.database'));
        $this->documentsBeforeTest = $this->privateDecisionInventory();
        $this->capturedExistingDocuments = true;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->deleteOwnedDocuments();

        parent::tearDown();
    }

    public function test_profile_rename_preserves_stored_pm_document_bytes_and_hashes(): void
    {
        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Istorijski plan');
        $application->forceFill(['commission_decision' => 'podrzava_potpuno', 'approved_amount' => 1000])->save();
        $document = $this->storeOwned(app(YouthDecisionDocumentStore::class), $ctx['competition'], $ctx['chairman'], $this->sourceFile("PK\x03\x04historic-document"));
        $beforeDocument = $document->fresh()->getRawOriginal();
        $beforeApplication = $application->fresh()->getRawOriginal();
        $beforeBytes = Storage::disk('local')->get($document->storage_path);
        $user = $application->user;
        (new \App\Identity\CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user));
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
        $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Markovic'])
            ->assertSessionHasNoErrors();
        $this->assertSame($beforeDocument, $document->fresh()->getRawOriginal());
        $this->assertSame($beforeApplication, $application->fresh()->getRawOriginal());
        $this->assertSame($beforeBytes, Storage::disk('local')->get($document->storage_path));
        $this->assertSame($document->content_sha256, hash('sha256', $beforeBytes));
        $this->assertSame($document->ranking_sha256, app(YouthDecisionRankingIdentity::class)->rankingSha256($ctx['competition']->fresh()));
    }
    public function test_successful_store_keeps_original_bytes_hash_and_ranking_identity(): void
    {
        $parameters = array_map(
            fn ($parameter) => $parameter->getName(),
            (new ReflectionMethod(YouthDecisionDocumentStore::class, 'store'))->getParameters()
        );
        $this->assertSame(['competition', 'actor', 'sourcePath'], $parameters);

        $ctx = $this->youthCompetition();
        $application = $this->scoredApplication($ctx, 5, 'Plan A');
        $before = $application->only([
            'commission_decision',
            'approved_amount',
            'commission_justification',
            'ranking_position',
            'final_score',
        ]);
        $bytes = "PK\x03\x04pm-odluka-original";
        $source = $this->sourceFile($bytes);
        $store = app(YouthDecisionDocumentStore::class);

        $document = $this->storeOwned($store, $ctx['competition'], $ctx['chairman'], $source);

        $this->assertSame($bytes, file_get_contents($source));
        $this->assertSame($bytes, Storage::disk('local')->get($document->storage_path));
        $this->assertSame(hash('sha256', $bytes), $document->content_sha256);
        $this->assertSame(
            app(YouthDecisionRankingIdentity::class)->rankingSha256($ctx['competition']->fresh()),
            $document->ranking_sha256
        );
        $this->assertNotSame(hash('sha256', '[]'), $document->ranking_sha256);
        $this->assertMatchesRegularExpression(
            '#^youth-decision-documents/'.$ctx['competition']->id.'/[0-9A-HJKMNP-TV-Z]{26}\.docx$#',
            $document->storage_path
        );
        $this->assertSame((int) $ctx['chairman']->id, (int) $document->generated_by);
        $this->assertNotNull($document->generated_at);
        $this->assertFalse(Storage::disk('public')->exists($document->storage_path));
        $storedReal = realpath(Storage::disk('local')->path($document->storage_path));
        $privateReal = realpath(storage_path('app/private'));
        $this->assertNotFalse($storedReal);
        $this->assertNotFalse($privateReal);
        $this->assertStringStartsWith(strtolower(str_replace('\\', '/', $privateReal)), strtolower(str_replace('\\', '/', $storedReal)));
        $this->assertSame($before, $application->fresh()->only(array_keys($before)));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_each_success_gets_its_own_path_and_keeps_earlier_bytes(): void
    {
        $ctx = $this->youthCompetition();
        $store = app(YouthDecisionDocumentStore::class);
        $firstBytes = "PK\x03\x04verzija-1";
        $secondBytes = "PK\x03\x04verzija-2";
        $first = $this->storeOwned($store, $ctx['competition'], $ctx['chairman'], $this->sourceFile($firstBytes));
        $second = $this->storeOwned($store, $ctx['competition'], $ctx['chairman'], $this->sourceFile($secondBytes));

        $this->assertNotSame($first->storage_path, $second->storage_path);
        $this->assertSame($firstBytes, Storage::disk('local')->get($first->storage_path));
        $this->assertSame($secondBytes, Storage::disk('local')->get($second->storage_path));
        $this->assertSame(2, YouthDecisionDocument::query()->where('competition_id', $ctx['competition']->id)->count());
        $this->assertSame(
            app(YouthDecisionRankingIdentity::class)->rankingSha256($ctx['competition']->fresh()),
            $second->ranking_sha256
        );
    }

    public function test_unreadable_or_empty_source_is_rejected(): void
    {
        $ctx = $this->youthCompetition();
        $store = app(YouthDecisionDocumentStore::class);
        $missing = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pm-ydd-missing-'.uniqid().'.docx';
        $before = $this->privateDecisionInventory((int) $ctx['competition']->id);

        try {
            $store->store($ctx['competition'], $ctx['chairman'], $missing);
            $this->fail('Nedostajući fajl mora biti odbijen.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::UNREADABLE, $exception->getMessage());
        }

        try {
            $store->store($ctx['competition'], $ctx['chairman'], $this->sourceFile(''));
            $this->fail('Prazan fajl mora biti odbijen.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::EMPTY_DOCUMENT, $exception->getMessage());
        }

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame($before, $this->privateDecisionInventory((int) $ctx['competition']->id));
    }

    public function test_storage_write_failure_creates_no_record(): void
    {
        $ctx = $this->youthCompetition();
        $store = new class(app(YouthDecisionRankingIdentity::class)) extends YouthDecisionDocumentStore
        {
            protected function writeExclusively(string $relativePath, string $bytes): void
            {
                throw new YouthDecisionDocumentStoreException(self::WRITE_FAILED);
            }
        };

        $before = $this->privateDecisionInventory((int) $ctx['competition']->id);

        try {
            $store->store($ctx['competition'], $ctx['chairman'], $this->sourceFile("PK\x03\x04neuspjeh"));
            $this->fail('Neuspješan upis mora biti odbijen.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::WRITE_FAILED, $exception->getMessage());
        }

        $this->assertSame(0, YouthDecisionDocument::query()->count());
        $this->assertSame($before, $this->privateDecisionInventory((int) $ctx['competition']->id));
    }

    public function test_hash_mismatch_removes_only_the_new_unreferenced_file(): void
    {
        $ctx = $this->youthCompetition();
        $store = app(YouthDecisionDocumentStore::class);
        $firstBytes = "PK\x03\x04sacuvana-verzija";
        $first = $this->storeOwned($store, $ctx['competition'], $ctx['chairman'], $this->sourceFile($firstBytes));

        $mismatch = new class(app(YouthDecisionRankingIdentity::class)) extends YouthDecisionDocumentStore
        {
            protected function readStoredBytes(string $relativePath): string
            {
                return 'tampered-bytes';
            }
        };

        try {
            $mismatch->store($ctx['competition'], $ctx['chairman'], $this->sourceFile("PK\x03\x04nova-verzija"));
            $this->fail('Nepoklopljen hash mora biti odbijen.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::HASH_MISMATCH, $exception->getMessage());
        }

        $this->assertSame([$first->storage_path], Storage::disk('local')->allFiles('youth-decision-documents/'.$ctx['competition']->id));
        $this->assertSame($firstBytes, Storage::disk('local')->get($first->storage_path));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_database_failure_does_not_remove_a_referenced_file(): void
    {
        $ctx = $this->youthCompetition();
        $bytes = "PK\x03\x04referencirana";
        $source = $this->sourceFile($bytes);
        $first = $this->storeOwned(app(YouthDecisionDocumentStore::class), $ctx['competition'], $ctx['chairman'], $source);

        $failing = new class(app(YouthDecisionRankingIdentity::class)) extends YouthDecisionDocumentStore
        {
            public string $reusePath = '';

            protected function relativePath(Competition $competition): string
            {
                return $this->reusePath;
            }

            protected function writeExclusively(string $relativePath, string $bytes): void {}

            protected function readStoredBytes(string $relativePath): string
            {
                $stored = Storage::disk('local')->get($relativePath);

                return is_string($stored) ? $stored : '';
            }
        };
        $failing->reusePath = $first->storage_path;

        try {
            $failing->store($ctx['competition'], $ctx['chairman'], $source);
            $this->fail('Dupli upis u bazu mora biti odbijen.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::NOT_STORED, $exception->getMessage());
            $this->assertInstanceOf(QueryException::class, $exception->getPrevious());
        }

        $this->assertSame($bytes, Storage::disk('local')->get($first->storage_path));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_existing_file_is_not_overwritten(): void
    {
        $ctx = $this->youthCompetition();
        $store = new class(app(YouthDecisionRankingIdentity::class)) extends YouthDecisionDocumentStore
        {
            public string $fixedId = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

            protected function nextDocumentId(): string
            {
                return $this->fixedId;
            }
        };
        $firstBytes = "PK\x03\x04ne-prepisuj";
        $first = $this->storeOwned($store, $ctx['competition'], $ctx['chairman'], $this->sourceFile($firstBytes));

        try {
            $store->store($ctx['competition'], $ctx['chairman'], $this->sourceFile("PK\x03\x04drugi-sadrzaj"));
            $this->fail('Postojeći fajl ne smije biti prepisan.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::NOT_OVERWRITTEN, $exception->getMessage());
        }

        $this->assertSame($firstBytes, Storage::disk('local')->get($first->storage_path));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_other_profiles_and_non_chairman_are_rejected(): void
    {
        $store = app(YouthDecisionDocumentStore::class);
        $source = $this->sourceFile("PK\x03\x04van-pm");

        foreach (['zensko', 'ostalo'] as $type) {
            $competition = $this->plainCompetition($type);
            $before = $this->privateDecisionInventory((int) $competition->id);
            try {
                $store->store($competition, User::factory()->create(), $source);
                $this->fail($type.' konkurs ne smije dobiti PM zapis.');
            } catch (YouthDecisionDocumentStoreException $exception) {
                $this->assertSame(YouthDecisionDocumentStore::NOT_YOUTH, $exception->getMessage());
            }
            $this->assertSame($before, $this->privateDecisionInventory((int) $competition->id));
        }

        $ctx = $this->youthCompetition();
        try {
            $store->store($ctx['competition'], $ctx['member'], $source);
            $this->fail('Član Komisije ne smije trajno sačuvati odluku.');
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->assertSame(YouthDecisionDocumentStore::NOT_CHAIRMAN, $exception->getMessage());
        }

        $this->assertSame(0, YouthDecisionDocument::query()->count());
    }

    public function test_store_locks_the_competition_row(): void
    {
        $ctx = $this->youthCompetition();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->storeOwned(
            app(YouthDecisionDocumentStore::class),
            $ctx['competition'],
            $ctx['chairman'],
            $this->sourceFile("PK\x03\x04zakljucavanje")
        );

        $sql = strtolower(implode("\n", array_column(DB::getQueryLog(), 'query')));
        $this->assertStringContainsString('for update', $sql);
        $this->assertStringContainsString('competitions', $sql);
    }

    public function test_cleanup_deletes_only_documents_recorded_by_this_test(): void
    {
        $ctx = $this->youthCompetition();
        $competitionId = (int) $ctx['competition']->id;
        $directory = 'youth-decision-documents/'.$competitionId;
        $disk = Storage::disk('local');

        $existingPath = $directory.'/'.Str::ulid().'.docx';
        $intruderPath = $directory.'/'.Str::ulid().'.docx';
        $this->assertNotSame($existingPath, $intruderPath);
        $this->assertFalse($disk->exists($existingPath));
        $this->assertFalse($disk->exists($intruderPath));

        $existingBytes = "PK\x03\x04vec-postoji";
        $intruderBytes = "PK\x03\x04tudji-tokom-testa";
        $disk->put($existingPath, $existingBytes);
        $disk->put($intruderPath, $intruderBytes);

        try {
            $this->documentsBeforeTest[$existingPath] = hash('sha256', $existingBytes);

            $helperPath = $this->writeOwnedDecisionFile($directory.'/owned-helper.docx', "PK\x03\x04pomocna-metoda");
            $stored = $this->storeOwned(
                app(YouthDecisionDocumentStore::class),
                $ctx['competition'],
                $ctx['chairman'],
                $this->sourceFile("PK\x03\x04vlastiti-zapis")
            );

            $this->assertNotContains($existingPath, $this->ownedDocumentPaths);
            $this->assertNotContains($intruderPath, $this->ownedDocumentPaths);
            $this->assertContains($helperPath, $this->ownedDocumentPaths);
            $this->assertContains($stored->storage_path, $this->ownedDocumentPaths);

            $beforeCleanup = $this->privateDecisionInventory($competitionId);
            $this->deleteOwnedDocuments();
            $afterCleanup = $this->privateDecisionInventory($competitionId);

            unset($beforeCleanup[$helperPath], $beforeCleanup[$stored->storage_path]);
            $this->assertSame($beforeCleanup, $afterCleanup);
            $this->assertSame(hash('sha256', $existingBytes), $afterCleanup[$existingPath]);
            $this->assertSame(hash('sha256', $intruderBytes), $afterCleanup[$intruderPath]);
            $this->assertArrayNotHasKey($helperPath, $afterCleanup);
            $this->assertArrayNotHasKey($stored->storage_path, $afterCleanup);
            $this->assertTrue($disk->directoryExists($directory));
        } finally {
            $disk->delete($existingPath);
            $disk->delete($intruderPath);
        }
    }

    /**
     * @param  array{competition: Competition, chairman: User, members: list<CommissionMember>}  $ctx
     */
    private function scoredApplication(array $ctx, int $score, string $plan): Application
    {
        $application = Application::query()->create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => $plan,
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Podnosilac '.$plan,
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
            'confirmed_by_user_id' => $ctx['members'][0]->user_id,
            'confirmed_by_commission_member_id' => $ctx['members'][0]->id,
            'confirmed_by_name' => $ctx['members'][0]->name,
        ]);
        foreach ($ctx['members'] as $index => $member) {
            $payload = [
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $index + 1,
                'completed_at' => now()->subDay(),
            ];
            for ($criterion = 1; $criterion <= 10; $criterion++) {
                $payload['criterion_'.$criterion] = $score;
            }
            EvaluationScore::query()->create($payload);
        }

        return $application->fresh();
    }

    private function sourceFile(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pm-ydd-');
        if ($path === false) {
            $this->fail('Testni ulazni fajl nije kreiran.');
        }
        file_put_contents($path, $bytes);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function storeOwned(
        YouthDecisionDocumentStore $store,
        Competition $competition,
        User $actor,
        string $sourcePath
    ): YouthDecisionDocument {
        $document = $store->store($competition, $actor, $sourcePath);
        $this->recordOwnedDocumentPath((string) $document->storage_path);

        return $document;
    }

    private function writeOwnedDecisionFile(string $relativePath, string $bytes): string
    {
        $path = str_replace('\\', '/', $relativePath);
        $this->assertArrayNotHasKey($path, $this->documentsBeforeTest);
        $this->assertFalse(Storage::disk('local')->exists($path));
        Storage::disk('local')->put($path, $bytes);

        return $this->recordOwnedDocumentPath($path);
    }

    private function recordOwnedDocumentPath(string $path): string
    {
        $this->assertTrue($this->capturedExistingDocuments);
        $normalized = str_replace('\\', '/', $path);
        $this->assertNotSame('', $normalized);
        $this->assertArrayNotHasKey($normalized, $this->documentsBeforeTest);
        if (! in_array($normalized, $this->ownedDocumentPaths, true)) {
            $this->ownedDocumentPaths[] = $normalized;
        }

        return $normalized;
    }

    private function deleteOwnedDocuments(): void
    {
        if (! $this->app) {
            return;
        }

        $disk = Storage::disk('local');
        foreach ($this->ownedDocumentPaths as $path) {
            $disk->delete($path);
        }
    }

    /**
     * Sortiran spisak postojećih privatnih PM odluka: relativna putanja => SHA-256.
     *
     * @return array<string, string>
     */
    private function privateDecisionInventory(?int $competitionId = null): array
    {
        $prefix = 'youth-decision-documents';
        if ($competitionId !== null) {
            $prefix .= '/'.$competitionId;
        }

        $disk = Storage::disk('local');
        $inventory = [];
        foreach ($disk->allFiles($prefix) as $path) {
            $normalized = str_replace('\\', '/', $path);
            $absolute = $disk->path($path);
            if (! is_file($absolute)) {
                continue;
            }

            $hash = hash_file('sha256', $absolute);
            if (! is_string($hash)) {
                continue;
            }

            $inventory[$normalized] = $hash;
        }

        ksort($inventory, SORT_STRING);

        return $inventory;
    }

    private function plainCompetition(string $type): Competition
    {
        $competition = Competition::query()->create([
            'title' => 'Van PM '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
        return $competition;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, members: list<CommissionMember>}
     */
    private function youthCompetition(): array
    {
        $commission = Commission::query()->create([
            'name' => 'PM cuvanje '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $members = [];
        $chairman = null;
        $memberUser = null;
        foreach ([1 => 'predsjednik', 2 => 'clan', 3 => 'clan'] as $seat => $position) {
            $user = User::factory()->create();
            $members[] = CommissionMember::query()->create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $position,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
            if ($position === 'predsjednik') {
                $chairman = $user;
            }
            if ($memberUser === null && $position === 'clan') {
                $memberUser = $user;
            }
        }
        $competition = Competition::query()->create([
            'title' => 'PM cuvanje '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);
        return [
            'competition' => $competition,
            'chairman' => $chairman,
            'member' => $memberUser,
            'members' => $members,
        ];
    }
}
