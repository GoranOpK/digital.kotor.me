<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PATCH-063 Phase 1 — schema + model fillable (bez business flow).
 */
class CulturalPatch063Phase1SchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_migration_adds_patch_063_columns_without_duplicating_entry_cancellation_reason(): void
    {
        $this->assertTrue(Schema::hasColumn('cultural_event_entries', 'organizer_manual_name'));
        $this->assertTrue(Schema::hasColumn('cultural_event_entries', 'cancellation_reason'));
        $this->assertTrue(Schema::hasColumn('cultural_occurrences', 'postponement_reason'));
        $this->assertTrue(Schema::hasColumn('cultural_occurrences', 'cancellation_reason'));

        $entryColumns = Schema::getColumnListing('cultural_event_entries');
        $this->assertSame(
            1,
            count(array_filter($entryColumns, fn (string $c): bool => $c === 'cancellation_reason'))
        );
    }

    public function test_models_can_mass_assign_new_patch_063_columns(): void
    {
        $editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $entry = CulturalEventEntry::query()->create([
            'naslov' => 'Phase 1 schema',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'created_by' => $editor->id,
            'organizer_manual_name' => 'Ručni naziv Org',
        ]);

        $this->assertSame('Ručni naziv Org', $entry->fresh()->organizer_manual_name);

        $occurrence = CulturalOccurrence::query()->create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-09-01',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'postponement_reason' => 'Opcioni razlog odgađanja',
            'cancellation_reason' => 'Opcioni razlog OCC otkazivanja',
        ]);

        $fresh = $occurrence->fresh();
        $this->assertSame('Opcioni razlog odgađanja', $fresh->postponement_reason);
        $this->assertSame('Opcioni razlog OCC otkazivanja', $fresh->cancellation_reason);
    }
}
