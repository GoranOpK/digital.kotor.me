<?php

namespace Tests\Feature\Kn;

use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CallPublicationService;
use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CompetitionProgramCatalog;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * KN-SYNC-003 — canonical publication claim / CallPublicationService foundation.
 */
class CallPublicationGuardTest extends TestCase
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
        Carbon::setTestNow();
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_canonical_type_schema_persists_alongside_legacy_values(): void
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE, COLUMN_DEFAULT
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

        $canonical = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-TYPE');
        $this->assertSame(CompetitionProfileResolver::TYPE_CANONICAL, $canonical->fresh()->type);
    }

    public function test_claim_uniqueness_is_year_type_and_slot(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM competition_publication_claims'))
            ->where('Non_unique', 0)
            ->groupBy('Key_name')
            ->map(fn ($rows) => $rows->sortBy('Seq_in_index')->pluck('Column_name')->values()->all());

        $this->assertSame(
            ['calendar_year', 'competition_type', 'publication_slot'],
            $indexes->get('publication_claims_year_type_slot_unique')
        );
        $this->assertFalse(
            $indexes->contains(fn (array $columns) => $columns === ['calendar_year', 'competition_type'])
        );

        $source = file_get_contents(database_path(
            'migrations/2026_09_14_140000_create_competition_publication_claims_table.php'
        ));
        $this->assertIsString($source);
        $this->assertStringNotContainsString('->insert(', $source);
        $this->assertStringNotContainsString('DB::table', $source);
    }

    public function test_policy_window_and_duration_are_data_not_engine_constants(): void
    {
        $probe = new class extends CompetitionProfilePolicy
        {
            public function key(): string
            {
                return 'probe';
            }

            public function displayName(): string
            {
                return 'Probe';
            }

            public function publicationMode(): string
            {
                return self::PUBLICATION_GUARDED;
            }

            public function publicationQuarter(): ?int
            {
                return 4;
            }

            public function publicationSlots(): array
            {
                return ['annual_call'];
            }

            public function deadlineDays(): ?int
            {
                return 15;
            }
        };

        $this->assertFalse($probe->isPublicationAllowedAt(Carbon::parse('2026-09-14 12:00:00', 'Europe/Belgrade')));
        $this->assertTrue($probe->isPublicationAllowedAt(Carbon::parse('2026-10-02 12:00:00', 'Europe/Belgrade')));
        $this->assertSame(
            '2026-10-17',
            $probe->publicationEndDate(Carbon::parse('2026-10-02'))->toDateString()
        );

        $service = file_get_contents(app_path('Services/Competitions/CallPublicationService.php'));
        $this->assertIsString($service);
        $this->assertStringNotContainsString('>= 7', $service);
        $this->assertStringNotContainsString('addDays(20)', $service);
        $this->assertStringNotContainsString('addDays(15)', $service);
        $this->assertStringNotContainsString("'Q3'", $service);
        $this->assertStringNotContainsString('residual', $service);
        $this->assertStringNotContainsString('second_call', $service);
    }

    public function test_canonical_q3_publish_uses_annual_call_slot_and_20_calendar_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 10:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-Q3', [
            'start_date' => '2026-09-14',
            'deadline_days' => 7,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $draft))
            ->post(route('admin.competitions.publish', $draft))
            ->assertRedirect()
            ->assertSessionHas('success');

        $draft->refresh();
        $policy = $draft->profile();

        $this->assertSame('published', $draft->status);
        $this->assertNotNull($draft->published_at);
        $this->assertSame($policy->deadlineDays(), (int) $draft->deadline_days);
        $this->assertSame(20, (int) $draft->deadline_days);
        $this->assertSame('2026-10-04', $draft->end_date->toDateString());
        $this->assertDatabaseHas('competition_publication_claims', [
            'competition_id' => $draft->id,
            'calendar_year' => 2026,
            'competition_type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'publication_slot' => 'annual_call',
        ]);
    }

    public function test_canonical_publish_outside_q3_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-30 23:30:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-OUTSIDE', [
            'start_date' => '2026-06-30',
            'deadline_days' => 7,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $draft))
            ->post(route('admin.competitions.publish', $draft))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $draft->refresh();
        $this->assertSame('draft', $draft->status);
        $this->assertNull($draft->published_at);
        $this->assertSame(7, (int) $draft->deadline_days);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    public function test_first_annual_call_succeeds_and_duplicate_same_year_type_slot_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $waiting = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-DRAFT-ONLY');
        $first = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-FIRST');
        $second = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-SECOND');

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $first))
            ->post(route('admin.competitions.publish', $first))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $second))
            ->post(route('admin.competitions.publish', $second))
            ->assertSessionHasErrors('error');

        $this->assertSame('published', $first->fresh()->status);
        $this->assertSame('draft', $second->fresh()->status);
        $this->assertNull($second->fresh()->published_at);
        $this->assertSame(1, CompetitionPublicationClaim::query()->count());
        $this->assertSame('draft', $waiting->fresh()->status);
    }

    public function test_next_calendar_year_annual_call_is_allowed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $year2026 = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-2026');

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $year2026))
            ->assertSessionHas('success');

        Carbon::setTestNow(Carbon::parse('2027-08-10 09:00:00', 'Europe/Belgrade'));
        $year2027 = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-2027', [
            'year' => 2027,
            'start_date' => '2027-08-10',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $year2027))
            ->assertSessionHas('success');

        $this->assertSame(2, CompetitionPublicationClaim::query()->count());
        $this->assertDatabaseHas('competition_publication_claims', [
            'competition_id' => $year2027->id,
            'calendar_year' => 2027,
            'publication_slot' => 'annual_call',
        ]);
    }

    public function test_second_canonical_call_and_residual_reopen_are_blocked(): void
    {
        $policy = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);

        $this->assertFalse($policy->allowsSecondCall());
        $this->assertFalse($policy->allowsResidualFundsReopen());
        $this->assertSame(['annual_call'], $policy->publicationSlots());
        $this->assertSame('annual_call', $policy->publicationSlotFor('primary'));
        $this->assertNull($policy->publicationSlotFor('residual'));

        Carbon::setTestNow(Carbon::parse('2026-09-20 09:00:00', 'Europe/Belgrade'));
        $occupied = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-OCCUPIED');
        CompetitionPublicationClaim::query()->create([
            'competition_id' => $occupied->id,
            'calendar_year' => 2026,
            'competition_type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'publication_slot' => 'annual_call',
            'claimed_at' => now(),
        ]);
        $occupied->update(['status' => 'published', 'published_at' => now()]);

        $challenger = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-CHALLENGER');
        $result = app(CallPublicationService::class)->publish($challenger, $this->admin());

        $this->assertFalse($result->succeeded);
        $this->assertSame('draft', $challenger->fresh()->status);
        $this->assertSame(1, CompetitionPublicationClaim::query()->count());
    }

    public function test_same_competition_publish_retry_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-RETRY', [
            'start_date' => '2026-09-02',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $draft))
            ->assertSessionHas('success');

        $result = app(CallPublicationService::class)->publish($draft->fresh(), $admin);
        $this->assertTrue($result->succeeded);
        $this->assertTrue($result->idempotent);
        $this->assertSame(1, CompetitionPublicationClaim::query()->count());
    }

    public function test_configured_year_cannot_bypass_publication_calendar_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-YEAR-MISMATCH', [
            'year' => 2027,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $draft))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    public function test_women_publish_unchanged_and_does_not_consume_canonical_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $women = $this->competition('zensko', 'KN-SYNC-003-WOMEN');
        $canonical = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-AFTER-WOMEN');

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $women))
            ->post(route('admin.competitions.publish', $women))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $canonical))
            ->post(route('admin.competitions.publish', $canonical))
            ->assertSessionHas('success');

        $this->assertSame('published', $women->fresh()->status);
        $this->assertSame(20, (int) $women->fresh()->deadline_days);
        $this->assertSame(1, CompetitionPublicationClaim::query()->count());
        $this->assertDatabaseMissing('competition_publication_claims', [
            'competition_type' => 'zensko',
        ]);

        $canonicalDraft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-EDIT-BYPASS', [
            'year' => 2027,
        ]);

        $this->actingAs($admin)->put(route('admin.competitions.update', $canonicalDraft), [
            'title' => 'KN-SYNC-003-EDIT-BYPASS',
            'description' => 'Opis',
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'up_number' => 'UP-SYNC-003',
            'year' => 2027,
            'budget' => 1000,
            'start_date' => '2027-09-01',
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $canonicalDraft->fresh()->status);
        $this->assertNull($canonicalDraft->fresh()->published_at);
    }

    public function test_youth_annual_publish_unaffected_and_does_not_use_publication_claims(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-12 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $youth = Competition::create([
            'title' => 'KN-SYNC-003-YOUTH-FIRST',
            'description' => 'Youth annual',
            'type' => 'omladinsko',
            'call_number' => 1,
            'status' => 'draft',
            'year' => 2026,
            'budget' => 5000,
            'annual_budget' => 5000,
            'deadline_days' => 20,
            'competition_number' => 'UP-SYNC-003-Y',
        ]);

        $policy = $youth->profile();
        $this->assertSame(CompetitionProfilePolicy::PUBLICATION_INACTIVE, $policy->publicationMode());
        $this->assertTrue($policy->allowsSecondCall());
        $this->assertTrue($policy->allowsResidualFundsReopen());

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $youth))
            ->post(route('admin.competitions.publish', $youth))
            ->assertSessionHas('success');

        $this->assertSame('published', $youth->fresh()->status);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
        $this->assertDatabaseMissing('competition_publication_claims', [
            'competition_type' => 'omladinsko',
        ]);
    }

    public function test_canonical_remains_development_and_not_publicly_listed(): void
    {
        $definitions = CompetitionProgramCatalog::definitions();
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            $definitions[CompetitionProfileResolver::TYPE_CANONICAL]['status']
        );
        $this->assertFalse(
            CompetitionProgramCatalog::isPubliclyAvailable(CompetitionProfileResolver::TYPE_CANONICAL)
        );
        $this->assertNotContains(
            CompetitionProfileResolver::TYPE_CANONICAL,
            CompetitionProgramCatalog::publiclyAvailableTypes()
        );

        Carbon::setTestNow(Carbon::parse('2026-09-14 10:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-DEV-PUBLIC');

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $draft))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertDontSee('KN-SYNC-003-DEV-PUBLIC');
        $this->actingAs($admin)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->assertDontSee('KN-SYNC-003-DEV-PUBLIC');
        $this->actingAs($admin)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->assertDontSee('KN-SYNC-003-DEV-PUBLIC');
    }

    public function test_fail_closed_profile_does_not_publish_or_consume_canonical_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $other = $this->competition('ostalo', 'KN-SYNC-003-OSTALO');

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $other))
            ->post(route('admin.competitions.publish', $other))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $other->fresh()->status);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    public function test_fail_closed_draft_cannot_be_published_through_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $other = $this->competition(CompetitionProfileResolver::TYPE_OTHER, 'KN-SYNC-003-OSTALO-UPDATE');

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $other))
            ->put(route('admin.competitions.update', $other), $this->updatePayload($other, ['status' => 'published']))
            ->assertRedirect(route('admin.competitions.edit', $other))
            ->assertSessionHasErrors(['status' => 'Objava nije dozvoljena za ovaj profil.']);

        $fresh = $other->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertSame(CompetitionProfileResolver::TYPE_OTHER, $fresh->type);
        $this->assertNull($fresh->published_at);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    public function test_type_and_status_change_in_one_update_cannot_bypass_fail_closed_publication(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:00:00', 'Europe/Belgrade'));
        $admin = $this->admin();
        $other = $this->competition(CompetitionProfileResolver::TYPE_OTHER, 'KN-SYNC-003-OSTALO-TO-WOMEN');
        $women = $this->competition(CompetitionProfileResolver::TYPE_WOMEN, 'KN-SYNC-003-WOMEN-TO-OSTALO');

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $other))
            ->put(route('admin.competitions.update', $other), $this->updatePayload($other, [
                'type' => CompetitionProfileResolver::TYPE_WOMEN,
                'status' => 'published',
            ]))
            ->assertRedirect(route('admin.competitions.edit', $other))
            ->assertSessionHasErrors(['status' => 'Objava nije dozvoljena za ovaj profil.']);

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $women))
            ->put(route('admin.competitions.update', $women), $this->updatePayload($women, [
                'type' => CompetitionProfileResolver::TYPE_OTHER,
                'status' => 'published',
            ]))
            ->assertRedirect(route('admin.competitions.edit', $women))
            ->assertSessionHasErrors(['status' => 'Objava nije dozvoljena za ovaj profil.']);

        $freshOther = $other->fresh();
        $this->assertSame('draft', $freshOther->status);
        $this->assertSame(CompetitionProfileResolver::TYPE_OTHER, $freshOther->type);
        $this->assertNull($freshOther->published_at);
        $freshWomen = $women->fresh();
        $this->assertSame('draft', $freshWomen->status);
        $this->assertSame(CompetitionProfileResolver::TYPE_WOMEN, $freshWomen->type);
        $this->assertNull($freshWomen->published_at);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    public function test_non_admin_cannot_publish(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:00:00', 'Europe/Belgrade'));
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'KN-SYNC-003-FORBIDDEN');

        $this->actingAs($user)
            ->from(route('admin.competitions.show', $draft))
            ->post(route('admin.competitions.publish', $draft))
            ->assertForbidden();

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $type, string $title, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'KN-SYNC-003',
            'type' => $type,
            'status' => 'draft',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Competition $competition, array $overrides = []): array
    {
        return array_merge([
            'title' => $competition->title,
            'description' => $competition->description,
            'type' => $competition->type,
            'up_number' => 'UP-01-003/26',
            'year' => (int) $competition->year,
            'budget' => 10000,
            'status' => $competition->status,
        ], $overrides);
    }
}
