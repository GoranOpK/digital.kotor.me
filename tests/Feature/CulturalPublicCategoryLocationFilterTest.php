<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-04 — kanonske kategorije + lokacijski filter adapter (TS-009 §3.3.3–3.3.4).
 */
class CulturalPublicCategoryLocationFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    private CulturalPublicEventQuery $publicQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->creator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
        $this->publicQuery = app(CulturalPublicEventQuery::class);
    }

    public function test_active_canonical_category_is_in_filter_options(): void
    {
        $active = $this->makeCategory('Koncert', CulturalCategory::STATUS_ACTIVE);

        $names = $this->publicQuery->categoryOptions()->pluck('naziv')->all();

        $this->assertContains($active->naziv, $names);
    }

    public function test_inactive_category_is_not_in_filter_options(): void
    {
        $this->makeCategory('Stara', CulturalCategory::STATUS_INACTIVE);

        $names = $this->publicQuery->categoryOptions()->pluck('naziv')->all();

        $this->assertNotContains('Stara', $names);
    }

    public function test_category_options_are_ordered_by_name(): void
    {
        $this->makeCategory('Zbor', CulturalCategory::STATUS_ACTIVE);
        $this->makeCategory('Arhitektura', CulturalCategory::STATUS_ACTIVE);
        $this->makeCategory('Muzika', CulturalCategory::STATUS_ACTIVE);

        $names = $this->publicQuery->categoryOptions()->pluck('naziv')->all();

        $this->assertSame(['Arhitektura', 'Muzika', 'Zbor'], $names);
    }

    public function test_filter_by_canonical_category_name_returns_matching_entries(): void
    {
        $koncert = $this->makeCategory('Koncert', CulturalCategory::STATUS_ACTIVE);
        $izlozba = $this->makeCategory('Izložba', CulturalCategory::STATUS_ACTIVE);

        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'K1', $koncert->id);
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'I1', $izlozba->id);

        $ids = $this->publicQuery->filterByCategoryName('Koncert')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_filter_excludes_entry_of_other_category(): void
    {
        $a = $this->makeCategory('A', CulturalCategory::STATUS_ACTIVE);
        $b = $this->makeCategory('B', CulturalCategory::STATUS_ACTIVE);
        $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Only B', $b->id);

        $ids = $this->publicQuery->filterByCategoryName('A')->pluck('id')->all();

        $this->assertSame([], $ids);
    }

    public function test_legacy_category_name_has_no_automatic_alias(): void
    {
        // Legacy lista sadrži „Koncerti“; kanonski zapis ovdje ima drugi naziv — bez alias mape.
        $canonical = $this->makeCategory('Muzički program', CulturalCategory::STATUS_ACTIVE);
        $other = $this->makeCategory('Izložbeni program', CulturalCategory::STATUS_ACTIVE);
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Canonical', $canonical->id);
        $otherEntry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Other', $other->id);

        $legacyName = 'Koncerti';
        $this->assertContains($legacyName, CulturalEvent::CATEGORIES);
        $this->assertNull(
            CulturalCategory::query()->active()->where('naziv', $legacyName)->value('id')
        );

        $ids = $this->publicQuery->filterByCategoryName($legacyName)->pluck('id')->all();

        // Bez aliasa → nevalidan kanonski naziv → ignore (oba Entry-ja ostaju).
        $this->assertContains($entry->id, $ids);
        $this->assertContains($otherEntry->id, $ids);
        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $ids
        );
    }

    public function test_invalid_category_is_ignored_per_ts009(): void
    {
        $cat = $this->makeCategory('Validna', CulturalCategory::STATUS_ACTIVE);
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'E', $cat->id);

        $ids = $this->publicQuery->filterByCategoryName('Nepostojeća')->pluck('id')->all();

        $this->assertContains($entry->id, $ids);
        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $ids
        );
    }

    public function test_inactive_category_name_is_ignored_not_empty_result(): void
    {
        $inactive = $this->makeCategory('Neaktivna', CulturalCategory::STATUS_INACTIVE);
        $active = $this->makeCategory('Aktivna', CulturalCategory::STATUS_ACTIVE);
        $withInactive = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Hist', $inactive->id);
        $withActive = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Now', $active->id);

        $ids = $this->publicQuery->filterByCategoryName('Neaktivna')->pluck('id')->all();

        $this->assertContains($withInactive->id, $ids);
        $this->assertContains($withActive->id, $ids);
    }

    public function test_category_filter_cannot_bypass_public_visibility(): void
    {
        $cat = $this->makeCategory('Javna', CulturalCategory::STATUS_ACTIVE);
        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Draft', $cat->id);
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Pub', $cat->id);

        $ids = $this->publicQuery->filterByCategoryName('Javna')->pluck('id')->all();

        $this->assertContains($published->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_catalog_location_display_name(): void
    {
        $location = $this->makeLocation('Crkva Sv. Luke', CulturalLocation::STATUS_ACTIVE);
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'CatLoc');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_id' => $location->id,
        ]);

        $this->assertSame('Crkva Sv. Luke', $occ->fresh()->publicLocationDisplayName());
    }

    public function test_manual_location_display_name(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'ManLoc');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Trg od Oružja',
        ]);

        $this->assertSame('Trg od Oružja', $occ->publicLocationDisplayName());
    }

    public function test_location_display_priority_follows_domain_xor(): void
    {
        $location = $this->makeLocation('Katalog', CulturalLocation::STATUS_ACTIVE);
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Xor');

        $catalogOcc = $this->makeOccurrence($entry, [
            'datum' => '2026-08-21',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_id' => $location->id,
        ]);
        $manualOcc = $this->makeOccurrence($entry, [
            'datum' => '2026-08-22',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Ručna',
        ]);
        $none = $this->makeOccurrence($entry, [
            'datum' => '2026-08-23',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertSame('Katalog', $catalogOcc->fresh()->publicLocationDisplayName());
        $this->assertTrue($catalogOcc->fresh()->hasCatalogLocation());
        $this->assertFalse($catalogOcc->fresh()->hasManualLocation());

        $this->assertSame('Ručna', $manualOcc->fresh()->publicLocationDisplayName());
        $this->assertTrue($manualOcc->fresh()->hasManualLocation());
        $this->assertFalse($manualOcc->fresh()->hasCatalogLocation());

        $this->assertNull($none->fresh()->publicLocationDisplayName());
    }

    public function test_duplicate_location_display_appears_once_in_options(): void
    {
        $location = $this->makeLocation('Isti trg', CulturalLocation::STATUS_ACTIVE);

        $e1 = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'E1');
        $this->makeOccurrence($e1, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_id' => $location->id,
        ]);

        $e2 = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'E2');
        $this->makeOccurrence($e2, [
            'datum' => '2026-08-21',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Isti trg',
        ]);

        $options = $this->publicQuery->locationDisplayOptions();

        $this->assertSame(1, count(array_filter($options, fn ($v) => $v === 'Isti trg')));
    }

    public function test_location_options_are_sorted_a_to_z(): void
    {
        $e = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Sort');
        $this->makeOccurrence($e, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Zappa',
        ]);
        $this->makeOccurrence($e, [
            'datum' => '2026-08-21',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Alpha',
        ]);
        $this->makeOccurrence($e, [
            'datum' => '2026-08-22',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Museum',
        ]);

        $this->assertSame(['Alpha', 'Museum', 'Zappa'], $this->publicQuery->locationDisplayOptions());
    }

    public function test_filter_by_catalog_location_returns_matching_entry(): void
    {
        $location = $this->makeLocation('Palata', CulturalLocation::STATUS_ACTIVE);
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Match');
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Other');

        $this->makeOccurrence($match, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_id' => $location->id,
        ]);
        $this->makeOccurrence($other, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Drugo mjesto',
        ]);

        $ids = $this->publicQuery->filterByLocationDisplayName('Palata')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_filter_by_manual_location_returns_matching_entry(): void
    {
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'ManualMatch');
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'ManualOther');

        $this->makeOccurrence($match, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Bastion',
        ]);
        $this->makeOccurrence($other, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Drugo',
        ]);

        $ids = $this->publicQuery->filterByLocationDisplayName('Bastion')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_manual_location_with_padding_matches_trimmed_display_and_filter(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'PaddedManual');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => '  Kulturni centar  ',
        ]);

        $this->assertSame('Kulturni centar', $occ->fresh()->publicLocationDisplayName());

        $options = $this->publicQuery->locationDisplayOptions();
        $this->assertContains('Kulturni centar', $options);
        $this->assertNotContains('  Kulturni centar  ', $options);

        $ids = $this->publicQuery->filterByLocationDisplayName('Kulturni centar')->pluck('id')->all();

        $this->assertContains($entry->id, $ids);
    }

    public function test_location_filter_cannot_bypass_public_visibility(): void
    {
        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'HiddenLoc');
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'VisibleLoc');

        $this->makeOccurrence($draft, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Tajna',
        ]);
        $this->makeOccurrence($published, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Tajna',
        ]);

        // Draft alone would not put "Tajna" in options; published does.
        $ids = $this->publicQuery->filterByLocationDisplayName('Tajna')->pluck('id')->all();

        $this->assertContains($published->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_null_and_blank_locations_are_excluded_from_options(): void
    {
        $e = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Blank');
        $this->makeOccurrence($e, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($e, [
            'datum' => '2026-08-21',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => '   ',
        ]);
        $this->makeOccurrence($e, [
            'datum' => '2026-08-22',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Validna',
        ]);

        $options = $this->publicQuery->locationDisplayOptions();

        $this->assertSame(['Validna'], $options);
        $this->assertNotContains('', $options);
        $this->assertNotContains('   ', $options);
    }

    public function test_deactivated_historical_catalog_location_still_displays_and_is_in_options(): void
    {
        $location = $this->makeLocation('Istorijska', CulturalLocation::STATUS_DEACTIVATED);
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'HistLoc');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_id' => $location->id,
        ]);

        $this->assertSame('Istorijska', $occ->fresh()->publicLocationDisplayName());
        $this->assertContains('Istorijska', $this->publicQuery->locationDisplayOptions());

        $ids = $this->publicQuery->filterByLocationDisplayName('Istorijska')->pluck('id')->all();
        $this->assertContains($entry->id, $ids);
    }

    public function test_invalid_location_is_ignored_per_ts009(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'NoLocFilter');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Postoji',
        ]);

        $ids = $this->publicQuery->filterByLocationDisplayName('Nepostojeća lokacija')->pluck('id')->all();

        $this->assertContains($entry->id, $ids);
        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $ids
        );
    }

    public function test_location_options_come_from_published_not_cancelled_only_set(): void
    {
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'PubLoc');
        $cancelled = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'CanLoc');

        $this->makeOccurrence($published, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Iz objavljenog',
        ]);
        $this->makeOccurrence($cancelled, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Samo otkazani',
        ]);

        $options = $this->publicQuery->locationDisplayOptions();

        $this->assertContains('Iz objavljenog', $options);
        $this->assertNotContains('Samo otkazani', $options);
    }

    private function makeCategory(string $naziv, string $status): CulturalCategory
    {
        return CulturalCategory::create([
            'naziv' => $naziv,
            'status' => $status,
        ]);
    }

    private function makeLocation(string $naziv, string $status): CulturalLocation
    {
        return CulturalLocation::create([
            'naziv' => $naziv,
            'status' => $status,
        ]);
    }

    private function makeEntry(string $status, string $naslov, ?int $categoryId = null): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => $naslov,
            'status' => $status,
            'created_by' => $this->creator->id,
            'category_id' => $categoryId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOccurrence(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
        ], $attributes));
    }
}
