<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-08 — canonical javni detalj Događaja (Phase B1: flag removed).
 */
class CulturalPublicShowDataSwitchTest extends TestCase
{
    use RefreshDatabase;

    private const CANCELLED_NOTICE = 'Ovaj događaj je otkazan i neće biti održan u planiranom terminu.';

    private const SECRET_REASON = 'INTERNI_RAZLOG_OTKAZIVANJA_NE_U_HTML';

    private User $user;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
        $this->creator = $this->user;

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_show_uses_canonical_detail(): void
    {

        $category = $this->makeCategory('Pozorište');
        $entry = $this->makePublishedEntry('CANONICAL_DETAIL_TITLE', [
            'opis' => 'Canonical opis detalja',
            'category_id' => $category->id,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-22',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:30:00',
            'vrijeme_do' => '20:00:00',
            'location_manual_name' => 'Scena Park',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('CANONICAL_DETAIL_TITLE', false);
        $response->assertSee('Canonical opis detalja', false);
        $response->assertSee('Pozorište', false);
        $response->assertSee('22.08.2026', false);
        $response->assertSee('18:30', false);
        $response->assertSee('20:00', false);
        $response->assertSee('Scena Park', false);
        $response->assertSee('Nazad', false);
    }

    public function test_legacy_event_not_opened_without_matching_entry(): void
    {

        $legacy = $this->makeLegacyEvent(['naslov' => 'LEGACY_ONLY_NO_ENTRY']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $legacy->id))
            ->assertNotFound();
    }

    public function test_canonical_published_returns_200(): void
    {

        $entry = $this->makePublishedEntry('Pub Show');
        $this->makeOccurrence($entry, ['datum' => '2026-08-12']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Pub Show', false);
    }

    public function test_canonical_cancelled_entry_returns_200_with_br272(): void
    {

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Cancelled Show', [
            'cancellation_reason' => self::SECRET_REASON,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('Cancelled Show', false);
        $response->assertSee(self::CANCELLED_NOTICE, false);
        $response->assertSee('Otkazan', false);
        $response->assertSee(self::SECRET_REASON, false);
        $response->assertSee('Napomena:', false);
    }

    public function test_canonical_draft_returns_404(): void
    {

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Draft Show');
        $this->makeOccurrence($entry, ['datum' => '2026-08-12']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertNotFound();
    }

    public function test_canonical_pending_approval_returns_404(): void
    {

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PENDING_APPROVAL, 'Pending Show');
        $this->makeOccurrence($entry, ['datum' => '2026-08-12']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertNotFound();
    }

    public function test_canonical_archived_returns_404(): void
    {

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'Archived Show');
        $this->makeOccurrence($entry, ['datum' => '2026-08-12']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertNotFound();
    }

    public function test_canonical_missing_id_returns_404(): void
    {

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', 999999))
            ->assertNotFound();
    }

    public function test_planned_occurrence_is_shown(): void
    {

        $entry = $this->makePublishedEntry('Planiran Detail');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-18',
            'cjelodnevno' => false,
            'vrijeme_od' => '17:00:00',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Trg',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('18.08.2026', false);
        $response->assertSee('17:00', false);
        $response->assertSee('Trg', false);
        $response->assertDontSee('Odgođeno', false);
        $response->assertDontSee('Otkazano', false);
        $response->assertDontSee('Završeno', false);
    }

    public function test_multiple_occurrences_all_shown_in_chronological_order(): void
    {

        $entry = $this->makePublishedEntry('Multi OCC Detail');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-25',
            'cjelodnevno' => false,
            'vrijeme_od' => '20:00:00',
            'location_manual_name' => 'Treći',
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '19:00:00',
            'location_manual_name' => 'Drugi',
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '10:00:00',
            'location_manual_name' => 'Prvi',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $html = $response->getContent();
        $posPrvi = strpos($html, 'Prvi');
        $posDrugi = strpos($html, 'Drugi');
        $posTreci = strpos($html, 'Treći');

        $this->assertNotFalse($posPrvi);
        $this->assertNotFalse($posDrugi);
        $this->assertNotFalse($posTreci);
        $this->assertLessThan($posDrugi, $posPrvi);
        $this->assertLessThan($posTreci, $posDrugi);
    }

    public function test_postponed_occurrence_shown_with_label(): void
    {

        $entry = $this->makePublishedEntry('Postponed Detail');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-11',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
            'location_manual_name' => 'Stari termin',
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-19',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Novi termin',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('Odgođeno', false);
        $response->assertSee('Stari termin', false);
        $response->assertSee('Novi termin', false);
        $response->assertSee('11.08.2026', false);
        $response->assertSee('19.08.2026', false);
    }

    public function test_cancelled_occurrence_shown_with_label(): void
    {

        $entry = $this->makePublishedEntry('Occ Cancelled Detail');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-13',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
            'location_manual_name' => 'Otkazan termin',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('Otkazano', false);
        $response->assertSee('Otkazan termin', false);
        $response->assertDontSee(self::CANCELLED_NOTICE, false);
    }

    public function test_finished_occurrence_shown_with_label(): void
    {

        $entry = $this->makePublishedEntry('Finished Detail');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
            'location_manual_name' => 'Prošli termin',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('Završeno', false);
        $response->assertSee('Prošli termin', false);
        $response->assertSee('01.08.2026', false);
    }

    public function test_catalog_and_manual_locations_and_all_day(): void
    {

        $location = CulturalLocation::create([
            'naziv' => 'Crkva Sv. Luke',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $entry = $this->makePublishedEntry('Loc Detail');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-14',
            'cjelodnevno' => true,
            'vrijeme_od' => null,
            'vrijeme_do' => null,
            'location_id' => $location->id,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-16',
            'cjelodnevno' => false,
            'vrijeme_od' => '09:15:00',
            'vrijeme_do' => '11:45:00',
            'location_manual_name' => 'Ručna sala',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('Crkva Sv. Luke', false);
        $response->assertSee('Cjelodnevno', false);
        $response->assertSee('Ručna sala', false);
        $response->assertSee('09:15', false);
        $response->assertSee('11:45', false);
    }

    public function test_ui_parity_elements_present_for_canonical(): void
    {

        $category = $this->makeCategory('Film');
        $entry = $this->makePublishedEntry('UI Parity Title', [
            'opis' => 'UI opis tekst',
            'category_id' => $category->id,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-21',
            'location_manual_name' => 'Kino',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', [
                'event' => $entry->id,
                'back' => '/kalendar-kulture/pregled-dogadjaja',
            ]));

        $response->assertOk();
        $response->assertSee('UI Parity Title', false);
        $response->assertSee('UI opis tekst', false);
        $response->assertSee('Film', false);
        $response->assertSee('Nazad', false);
        $response->assertSee('kk-show-card', false);
        $response->assertSee('kk-show-photo', false);
        $response->assertSee('href="/kalendar-kulture/pregled-dogadjaja"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLegacyEvent(array $overrides = []): CulturalEvent
    {
        return CulturalEvent::create(array_merge([
            'naslov' => 'Legacy event',
            'opis' => 'Opis',
            'datum_od' => '2026-08-20',
            'datum_do' => null,
            'vrijeme' => '18:00:00',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedEntry(string $naslov, array $extra = []): CulturalEventEntry
    {
        return $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, $naslov, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeEntry(string $status, string $naslov, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'status' => $status,
            'created_by' => $this->creator->id,
        ], $extra));
    }

    private function makeCategory(string $naziv): CulturalCategory
    {
        return CulturalCategory::create([
            'naziv' => $naziv,
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOccurrence(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }
}
