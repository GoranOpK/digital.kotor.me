<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityCensusCommand;
use App\Identity\Census\IdentityCensusService;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class IdentityCensusReadOnlyTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const FORBIDDEN_TOKENS = [
        'CanonicalIdentityWriter',
        '->save(',
        '->update(',
        '->delete(',
        '->create(',
        '->insert(',
        '->upsert(',
        'updateOrCreate(',
        'firstOrCreate(',
        '->increment(',
        '->decrement(',
        'DB::statement',
        'DB::unprepared',
        'INSERT INTO',
        'DELETE FROM',
        'REPLACE INTO',
        'CREATE TABLE',
        'ALTER TABLE',
        'DROP TABLE',
        'TRUNCATE',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
    }

    public function test_census_implementation_has_no_mutation_or_writer_tokens(): void
    {
        foreach ($this->censusPhpFiles() as $relative => $path) {
            $contents = (string) file_get_contents($path);
            foreach (self::FORBIDDEN_TOKENS as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $contents,
                    $relative.' must not contain '.$token
                );
            }
        }
    }

    public function test_command_has_no_write_or_production_override_flags(): void
    {
        $definition = implode(' ', array_keys((new IdentityCensusCommand)->getDefinition()->getOptions()));

        foreach (['apply', 'write', 'migrate', 'backfill', 'force', 'production'] as $flag) {
            $this->assertStringNotContainsString($flag, $definition);
        }
    }

    public function test_production_environment_refuses_command_execution(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('identity:census')
            ->expectsOutputToContain('identity:census refuses execution in production.')
            ->assertFailed();
    }

    public function test_census_makes_zero_writes_to_users_and_canonical_tables(): void
    {
        $user = $this->makeKorisnik([
            'email' => 'census-pii-secret@example.test',
            'jmb' => '0000000000000',
            'passport_number' => 'PIIPASSPORT1',
        ]);
        $beforeLegacy = $this->legacyPayload($user);
        $beforeCounts = $this->identityCounts();
        $beforeHash = $this->usersTableFingerprint();

        $report = (new IdentityCensusService)->run();
        $again = (new IdentityCensusService)->run();

        $this->assertSame($beforeCounts, $this->identityCounts());
        $this->assertSame($beforeLegacy, $this->legacyPayload($user->fresh()));
        $this->assertSame($beforeHash, $this->usersTableFingerprint());
        $this->assertSame(
            array_map(static fn ($row) => $row->toArray(), $report->rows),
            array_map(static fn ($row) => $row->toArray(), $again->rows)
        );
        $this->assertGreaterThan(0, $report->aggregates['total_users']);
    }

    public function test_default_report_omits_raw_jmb_passport_and_email(): void
    {
        $this->makeKorisnik([
            'email' => 'census-pii-secret@example.test',
            'jmb' => '0000000000000',
            'passport_number' => 'PIIPASSPORT1',
        ]);

        $encoded = json_encode((new IdentityCensusService)->run()->toArray(), JSON_UNESCAPED_UNICODE);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('census-pii-secret@example.test', $encoded);
        $this->assertStringNotContainsString('0000000000000', $encoded);
        $this->assertStringNotContainsString('PIIPASSPORT1', $encoded);
        $this->assertStringNotContainsString('password', $encoded);
    }

    public function test_command_emits_aggregate_json_and_row_jsonl_in_testing(): void
    {
        $this->makeKorisnik(['email' => 'census-cmd@example.test', 'jmb' => '0000000000000']);

        $aggregatePath = storage_path('framework/census-aggregate-test.json');
        $rowsPath = storage_path('framework/census-rows-test.jsonl');
        @unlink($aggregatePath);
        @unlink($rowsPath);

        $this->artisan('identity:census', [
            '--aggregate' => $aggregatePath,
            '--rows' => $rowsPath,
        ])->assertSuccessful();

        $this->assertFileExists($aggregatePath);
        $this->assertFileExists($rowsPath);

        $aggregate = json_decode((string) file_get_contents($aggregatePath), true);
        $this->assertIsArray($aggregate);
        $this->assertArrayHasKey('aggregates', $aggregate);
        $this->assertArrayHasKey('metadata', $aggregate);
        $this->assertSame('testing', $aggregate['metadata']['environment']);
        $this->assertGreaterThan(0, $aggregate['aggregates']['total_users']);

        $firstLine = trim((string) file($rowsPath)[0]);
        $row = json_decode($firstLine, true);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('user_id', $row);
        $this->assertArrayHasKey('row_status', $row);
        $this->assertArrayHasKey('field_statuses', $row);
        $this->assertStringNotContainsString('census-cmd@example.test', $firstLine);

        @unlink($aggregatePath);
        @unlink($rowsPath);
    }

    /**
     * @return array<string, int>
     */
    private function identityCounts(): array
    {
        return [
            'users' => User::query()->count(),
            'platform_identities' => PlatformIdentity::query()->count(),
            'physical_person_identities' => PhysicalPersonIdentity::query()->count(),
            'legal_entity_identities' => LegalEntityIdentity::query()->count(),
            'legal_entity_authorized_persons' => LegalEntityAuthorizedPerson::query()->count(),
            'foreign_branch_identities' => ForeignBranchIdentity::query()->count(),
            'foreign_branch_representatives' => ForeignBranchRepresentative::query()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyPayload(User $user): array
    {
        return $user->only([
            'user_type',
            'residential_status',
            'first_name',
            'last_name',
            'company_name',
            'jmb',
            'pib',
            'passport_number',
            'phone',
            'address',
            'city',
            'email',
            'password',
        ]);
    }

    private function usersTableFingerprint(): string
    {
        $rows = User::query()
            ->orderBy('id')
            ->get([
                'id',
                'user_type',
                'residential_status',
                'first_name',
                'last_name',
                'company_name',
                'jmb',
                'pib',
                'passport_number',
                'phone',
                'address',
                'city',
                'email',
                'password',
                'updated_at',
            ])
            ->toArray();

        return hash('sha256', json_encode($rows));
    }

    /**
     * @return array<string, string>
     */
    private function censusPhpFiles(): array
    {
        $files = [
            'app/Console/Commands/IdentityCensusCommand.php' => base_path('app/Console/Commands/IdentityCensusCommand.php'),
            'app/Console/Commands/IdentityProductionCensusCommand.php' => base_path('app/Console/Commands/IdentityProductionCensusCommand.php'),
        ];

        $root = base_path('app/Identity/Census');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', substr($absolute, strlen(base_path()) + 1));
            $files[$relative] = $absolute;
        }

        $this->assertNotEmpty($files);

        return $files;
    }
}
