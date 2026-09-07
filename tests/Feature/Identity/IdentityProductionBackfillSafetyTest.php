<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityProductionBackfillCommand;
use App\Identity\Backfill\IdentityBackfillProtectedOutputPath;
use App\Identity\Backfill\IdentityBackfillUserOutcome;
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

class IdentityProductionBackfillSafetyTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertNotSame(
            IdentityCensusService::READONLY_CONNECTION,
            (string) config('database.default')
        );
    }

    public function test_production_command_refuses_non_production_environment(): void
    {
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->expectsOutputToContain('identity:backfill-production requires the production environment.')
            ->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_command_has_no_user_or_force_bypass_flags(): void
    {
        $options = array_keys((new IdentityProductionBackfillCommand)->getDefinition()->getOptions());

        $this->assertNotContains('user', $options);
        $this->assertNotContains('force', $options);
        $this->assertNotContains('id', $options);
        $this->assertNotContains('users', $options);
    }

    public function test_apply_without_exact_confirmation_token_fails(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--apply' => true,
            '--confirm' => 'FORCE',
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_dry_run_and_apply_together_fails(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--apply' => true,
            '--confirm' => IdentityProductionBackfillCommand::APPLY_CONFIRMATION,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_write_on_fails_before_write(): void
    {
        $this->app['env'] = 'production';
        config(['identity.canonical_write' => true]);
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_read_on_fails_before_write(): void
    {
        $this->app['env'] = 'production';
        config(['identity.canonical_read' => true]);
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_public_output_path_is_rejected(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => public_path('backfill-aggregate.json'),
            '--rows' => public_path('backfill-rows.jsonl'),
        ])->assertFailed();
    }

    public function test_public_storage_output_path_is_rejected(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => storage_path('app/public/backfill-aggregate.json'),
            '--rows' => storage_path('app/public/backfill-rows.jsonl'),
        ])->assertFailed();
    }

    public function test_path_traversal_into_public_is_rejected(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => 'storage/app/private/identity-backfill/../../../public/backfill-aggregate.json',
            '--rows' => 'storage/app/private/identity-backfill/../../../public/backfill-rows.jsonl',
        ])->assertFailed();
    }

    public function test_production_dry_run_writes_protected_pii_safe_output_without_canonical_rows(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => '0000000000000',
            'email' => 'prod-backfill-pii@example.test',
            'passport_number' => 'PRODPASSPORT1',
            'pib' => '12345672',
        ]);
        $before = $this->legacyPayload($user);

        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
            '--census-reference-date' => '2026-09-06',
            '--census-max-user-id' => (string) $user->id,
        ])->assertSuccessful();

        $this->assertCanonicalTablesEmpty();
        $this->assertSame($before, $this->legacyPayload($user->fresh()));

        $aggregateJson = (string) file_get_contents($aggregate);
        $rowJson = (string) file_get_contents($rows);
        $combined = $aggregateJson.$rowJson;
        $this->assertStringNotContainsString('prod-backfill-pii@example.test', $combined);
        $this->assertStringNotContainsString('0000000000000', $combined);
        $this->assertStringNotContainsString('PRODPASSPORT1', $combined);
        $this->assertStringNotContainsString('12345672', $combined);

        $decoded = json_decode($aggregateJson, true);
        $this->assertSame('dry-run', $decoded['metadata']['mode']);
        $this->assertSame('2026-09-06', $decoded['metadata']['census_reference_date']);
        $this->assertSame((int) $user->id, $decoded['metadata']['census_max_user_id']);
        $this->assertSame('production', $decoded['metadata']['environment']);
        $this->assertSame(IdentityBackfillUserOutcome::WOULD_CREATE, json_decode(trim($rowJson), true)['status']);
        $this->assertSame($user->id, json_decode(trim($rowJson), true)['user_id']);

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_apply_with_confirmation_creates_eligible_graph(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => '0000000000000',
            'email' => 'prod-backfill-apply@example.test',
        ]);
        $before = $this->legacyPayload($user);

        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--apply' => true,
            '--confirm' => IdentityProductionBackfillCommand::APPLY_CONFIRMATION,
            '--census-max-user-id' => (string) $user->id,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertSuccessful();

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame($before, $this->legacyPayload($user->fresh()));

        $decoded = json_decode((string) file_get_contents($aggregate), true);
        $this->assertSame('apply', $decoded['metadata']['mode']);
        $row = json_decode(trim((string) file_get_contents($rows)), true);
        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $row['status']);

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_dry_run_without_census_max_user_id_aborts(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--dry-run' => true,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->expectsOutputToContain('census-max-user-id')
            ->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_apply_without_census_max_user_id_aborts(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:backfill-production', [
            '--apply' => true,
            '--confirm' => IdentityProductionBackfillCommand::APPLY_CONFIRMATION,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->expectsOutputToContain('census-max-user-id')
            ->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_command_rejects_invalid_census_max_user_id(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        foreach (['0', '-1', 'abc'] as $invalid) {
            $this->artisan('identity:backfill-production', [
                '--dry-run' => true,
                '--census-max-user-id' => $invalid,
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        }

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function protectedPaths(): array
    {
        IdentityBackfillProtectedOutputPath::ensureBaseDirectory();

        return [
            storage_path('app/private/identity-backfill/test-aggregate.json'),
            storage_path('app/private/identity-backfill/test-rows.jsonl'),
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
            'role_id',
            'activation_status',
        ]);
    }

    private function assertCanonicalTablesEmpty(): void
    {
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
        $this->assertSame(0, ForeignBranchRepresentative::query()->count());
    }

    private function cleanupPaths(string $aggregate, string $rows): void
    {
        @unlink($aggregate);
        @unlink($rows);
    }
}
