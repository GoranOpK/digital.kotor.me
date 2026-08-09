<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Services\CulturalCategory\CanonicalCulturalCategoryCatalog;
use App\Services\CulturalCategory\CanonicalCulturalCategorySync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * CAT-14 — idempotentni cultural-categories:sync.
 */
class CanonicalCulturalCategorySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_table_creates_fourteen_active_canonical_categories(): void
    {
        $this->assertSame(0, CulturalCategory::count());

        $exit = Artisan::call('cultural-categories:sync');

        $this->assertSame(0, $exit);
        $this->assertSame(14, CulturalCategory::count());
        $this->assertSame(14, CulturalCategory::query()->where('status', CulturalCategory::STATUS_ACTIVE)->count());
        $this->assertCanonicalActiveNames(CanonicalCulturalCategoryCatalog::names());
        $this->assertSame(0, CulturalCategory::query()->where('naziv', CulturalCategory::FORBIDDEN_NAME)->count());
    }

    public function test_second_run_is_noop_without_new_rows(): void
    {
        Artisan::call('cultural-categories:sync');
        $ids = CulturalCategory::query()->orderBy('id')->pluck('id')->all();

        $exit = Artisan::call('cultural-categories:sync');

        $this->assertSame(0, $exit);
        $this->assertSame(14, CulturalCategory::count());
        $this->assertSame($ids, CulturalCategory::query()->orderBy('id')->pluck('id')->all());
    }

    public function test_partial_catalog_creates_only_missing(): void
    {
        CulturalCategory::create([
            'naziv' => 'Koncerti',
            'opis' => 'već postoji',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        CulturalCategory::create([
            'naziv' => 'Sajmovi',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $exit = Artisan::call('cultural-categories:sync');

        $this->assertSame(0, $exit);
        $this->assertSame(14, CulturalCategory::count());
        $this->assertSame('već postoji', CulturalCategory::query()->where('naziv', 'Koncerti')->value('opis'));
        $this->assertCanonicalActiveNames(CanonicalCulturalCategoryCatalog::names());
    }

    public function test_extra_user_category_is_left_untouched(): void
    {
        $extra = CulturalCategory::create([
            'naziv' => 'Lokalna korisnička',
            'opis' => 'custom',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        Artisan::call('cultural-categories:sync');

        $extra->refresh();
        $this->assertSame('Lokalna korisnička', $extra->naziv);
        $this->assertSame('custom', $extra->opis);
        $this->assertTrue($extra->isActive());
        $this->assertSame(15, CulturalCategory::count());
    }

    public function test_existing_opis_is_not_modified_on_skip(): void
    {
        CulturalCategory::create([
            'naziv' => 'Predstave',
            'opis' => 'sačuvaj me',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        Artisan::call('cultural-categories:sync');

        $this->assertSame(
            'sačuvaj me',
            CulturalCategory::query()->where('naziv', 'Predstave')->value('opis')
        );
    }

    public function test_inactive_canonical_without_flag_reports_conflict_and_writes_nothing_for_it(): void
    {
        CulturalCategory::create([
            'naziv' => 'Koncerti',
            'opis' => 'stari opis',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        $this->artisan('cultural-categories:sync')
            ->expectsOutputToContain('inactive_conflict')
            ->expectsOutputToContain('CAT-14 blocker NIJE zatvoren')
            ->assertFailed();

        $koncerti = CulturalCategory::query()->where('naziv', 'Koncerti')->get();
        $this->assertCount(1, $koncerti);
        $this->assertTrue($koncerti->first()->isInactive());
        $this->assertSame('stari opis', $koncerti->first()->opis);
        $this->assertSame(14, CulturalCategory::count());
    }

    public function test_inactive_canonical_with_reactivate_flag_only_sets_status_active(): void
    {
        $row = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'opis' => 'ostavi opis',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        $this->artisan('cultural-categories:sync', [
            '--reactivate-inactive' => true,
        ])->assertSuccessful();

        $row->refresh();
        $this->assertTrue($row->isActive());
        $this->assertSame('Koncerti', $row->naziv);
        $this->assertSame('ostavi opis', $row->opis);
        $this->assertSame(14, CulturalCategory::count());
    }

    public function test_active_duplicates_report_conflict_without_destructive_changes(): void
    {
        $a = CulturalCategory::create([
            'naziv' => 'Izložbe',
            'opis' => 'prva',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $b = CulturalCategory::create([
            'naziv' => 'izložbe',
            'opis' => 'druga',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->artisan('cultural-categories:sync')
            ->expectsOutputToContain('duplicate_active_conflict')
            ->assertFailed();

        $a->refresh();
        $b->refresh();
        $this->assertSame('prva', $a->opis);
        $this->assertSame('druga', $b->opis);
        $this->assertTrue($a->isActive());
        $this->assertTrue($b->isActive());
        $this->assertSame(15, CulturalCategory::count()); // 2 Izložbe + 13 ostalih
    }

    public function test_dry_run_does_not_write(): void
    {
        $this->assertSame(0, CulturalCategory::count());

        $preview = app(CanonicalCulturalCategorySync::class)->sync(dryRun: true);
        $this->assertCount(14, $preview['created']);
        $this->assertTrue($preview['complete']);
        $this->assertSame(0, CulturalCategory::count());

        $this->artisan('cultural-categories:sync', [
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain('created: 14')
            ->assertSuccessful();

        $this->assertSame(0, CulturalCategory::count());
    }

    public function test_does_not_create_forbidden_or_legacy_names(): void
    {
        Artisan::call('cultural-categories:sync');

        $legacyOrForbidden = [
            CulturalCategory::FORBIDDEN_NAME,
            'Književne večeri',
            'Promocije publikacija',
            'Prezentacije',
            'Paneli o kulturi',
            'Filmski festivali',
            'Likovne manifestacije',
            'Manifestacije u organizaciji Mjesnih zajednica',
            'Manifestacije u organizaciji NVU',
        ];

        foreach ($legacyOrForbidden as $name) {
            $this->assertSame(
                0,
                CulturalCategory::query()
                    ->whereRaw('LOWER(TRIM(naziv)) = ?', [CulturalCategory::normalizeName($name)])
                    ->count(),
                "Ne očekuje se kreiranje: {$name}"
            );
        }

        $this->assertEqualsCanonicalizing(
            CanonicalCulturalCategoryCatalog::names(),
            CulturalCategory::query()->orderBy('id')->pluck('naziv')->all()
        );
        $this->assertSame(14, count(CanonicalCulturalCategoryCatalog::names()));
    }

    public function test_sync_service_complete_flag_matches_coverage(): void
    {
        $result = app(CanonicalCulturalCategorySync::class)->sync();

        $this->assertTrue($result['complete']);
        $this->assertSame(14, $result['coverage']);
        $this->assertCount(14, $result['created']);
    }

    /**
     * @param  list<string>  $expected
     */
    private function assertCanonicalActiveNames(array $expected): void
    {
        $actual = CulturalCategory::query()
            ->where('status', CulturalCategory::STATUS_ACTIVE)
            ->whereIn('naziv', $expected)
            ->pluck('naziv')
            ->all();

        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
