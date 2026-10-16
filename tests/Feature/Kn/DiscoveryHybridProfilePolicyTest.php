<?php

namespace Tests\Feature\Kn;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CanonicalMmspPolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\Profile\FailClosedProfilePolicy;
use App\Services\Competitions\Profile\WomenPolicy;
use App\Services\Competitions\Profile\YouthPolicy;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * KN-SYNC-002 — Catalog visibility SSOT + profile policy wiring.
 */
class DiscoveryHybridProfilePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_schema_accepts_canonical_type_and_keeps_existing_values(): void
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['competitions', 'type']
        );

        $this->assertNotNull($column);
        $this->assertStringContainsString("'zensko'", $column->COLUMN_TYPE);
        $this->assertStringContainsString("'omladinsko'", $column->COLUMN_TYPE);
        $this->assertStringContainsString("'ostalo'", $column->COLUMN_TYPE);
        $this->assertStringContainsString("'".CompetitionProfileResolver::TYPE_CANONICAL."'", $column->COLUMN_TYPE);
        $this->assertSame('ostalo', $column->COLUMN_DEFAULT);
        $this->assertSame('NO', $column->IS_NULLABLE);

        $women = $this->competition('zensko', 'KN-SYNC-002-ZENSKO');
        $youth = $this->competition('omladinsko', 'KN-SYNC-002-OMLADINSKO');
        $other = $this->competition('ostalo', 'KN-SYNC-002-OSTALO');
        $canonical = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-002-CANONICAL');

        $this->assertSame('zensko', $women->fresh()->type);
        $this->assertSame('omladinsko', $youth->fresh()->type);
        $this->assertSame('ostalo', $other->fresh()->type);
        $this->assertSame(CompetitionProfileResolver::TYPE_CANONICAL, $canonical->fresh()->type);
    }

    public function test_resolver_maps_known_profiles_and_fails_closed(): void
    {
        $this->assertInstanceOf(WomenPolicy::class, CompetitionProfileResolver::resolve('zensko'));
        $this->assertInstanceOf(YouthPolicy::class, CompetitionProfileResolver::resolve('omladinsko'));
        $this->assertInstanceOf(
            CanonicalMmspPolicy::class,
            CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL)
        );
        $this->assertInstanceOf(FailClosedProfilePolicy::class, CompetitionProfileResolver::resolve('ostalo'));
        $this->assertInstanceOf(FailClosedProfilePolicy::class, CompetitionProfileResolver::resolve('mmsp'));
        $this->assertInstanceOf(FailClosedProfilePolicy::class, CompetitionProfileResolver::resolve(null));

        $other = CompetitionProfileResolver::resolve('ostalo');
        $this->assertTrue($other->isFailClosed());
        $this->assertSame('ostalo', $other->key());
        $this->assertNotSame(CompetitionProfileResolver::TYPE_CANONICAL, $other->key());
    }

    public function test_canonical_policy_declares_publication_rules_without_public_catalog_activation(): void
    {
        $policy = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);

        $this->assertSame('Preduzetnici mikro, mala i srednja preduzeća', $policy->displayName());
        $this->assertSame(
            \App\Services\Competitions\Profile\CompetitionProfilePolicy::PUBLICATION_GUARDED,
            $policy->publicationMode()
        );
        $this->assertSame(3, $policy->publicationQuarter());
        $this->assertSame(['annual_call'], $policy->publicationSlots());
        $this->assertSame(20, $policy->deadlineDays());
        $this->assertFalse($policy->allowsSecondCall());
        $this->assertFalse($policy->allowsResidualFundsReopen());

        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()[CompetitionProfileResolver::TYPE_CANONICAL]['status']
        );
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable(CompetitionProfileResolver::TYPE_CANONICAL));
        $this->assertSame(['zensko', 'omladinsko'], CompetitionProfileResolver::publiclyDiscoverableTypeKeys());
    }

    public function test_women_and_youth_catalog_active_and_public_index_hides_canonical(): void
    {
        $this->competition('zensko', 'KN-SYNC-002-PUBLIC-WOMEN', [
            'status' => 'published',
            'start_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ]);
        $youth = $this->competition('omladinsko', 'KN-SYNC-002-PUBLIC-YOUTH', [
            'status' => 'published',
            'start_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ]);
        $canonical = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-002-PUBLIC-CANONICAL', [
            'status' => 'published',
            'start_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('zensko'));
        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable(CompetitionProfileResolver::TYPE_CANONICAL));

        $viewer = User::factory()->create();
        $this->actingAs($viewer)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertSee('KN-SYNC-002-PUBLIC-WOMEN')
            ->assertSee('KN-SYNC-002-PUBLIC-YOUTH')
            ->assertDontSee('KN-SYNC-002-PUBLIC-CANONICAL');

        $this->actingAs($viewer)
            ->get(route('competitions.show', $youth))
            ->assertOk()
            ->assertSee('KN-SYNC-002-PUBLIC-YOUTH');
        $this->actingAs($viewer)
            ->get(route('competitions.show', $canonical))
            ->assertNotFound();
    }

    public function test_admin_type_options_include_canonical_and_validation_accepts_storable_keys(): void
    {
        $options = CompetitionProfileResolver::adminTypeOptions();
        $this->assertArrayHasKey('zensko', $options);
        $this->assertArrayHasKey('omladinsko', $options);
        $this->assertArrayHasKey('ostalo', $options);
        $this->assertArrayHasKey(CompetitionProfileResolver::TYPE_CANONICAL, $options);
        $this->assertSame(
            'Preduzetnici mikro, mala i srednja preduzeća',
            $options[CompetitionProfileResolver::TYPE_CANONICAL]
        );

        foreach (CompetitionProfileResolver::storableKeys() as $type) {
            $this->assertTrue(
                Validator::make(['type' => $type], ['type' => CompetitionProfileResolver::validationRule()])->passes(),
                $type
            );
        }
        $this->assertTrue(
            Validator::make(['type' => 'mmsp'], ['type' => CompetitionProfileResolver::validationRule()])->fails()
        );

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.competitions.create'))
            ->assertOk()
            ->assertSee('value="zensko"', false)
            ->assertSee('value="omladinsko"', false)
            ->assertSee('value="ostalo"', false)
            ->assertSee('value="'.CompetitionProfileResolver::TYPE_CANONICAL.'"', false)
            ->assertSee('Preduzetnici mikro, mala i srednja preduzeća', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $type, string $title, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'KN-SYNC-002',
            'type' => $type,
            'status' => 'draft',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
        ], $overrides));
    }
}
