<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-10 — canonical kartica → detail link + URL/404 ID-space (Phase B1: flag removed).
 */
class CulturalPublicCardDetailLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CulturalCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_canonical_events_card_has_detail_link_and_opens_200(): void
    {

        $entry = $this->makePublishedEntry('CANONICAL_EVENTS_LINK');
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $href = route('cultural-calendar.show', $entry->id);
        $list = $this->actingAs($this->user)->get(route('cultural-calendar.events'));
        $list->assertOk();
        $list->assertSee($href, false);
        $list->assertSee('CANONICAL_EVENTS_LINK', false);

        $detail = $this->actingAs($this->user)->get($href);
        $detail->assertOk();
        $detail->assertSee('CANONICAL_EVENTS_LINK', false);
    }

    public function test_canonical_index_featured_card_has_detail_link_and_opens_200(): void
    {

        $entry = $this->makePublishedEntry('CANONICAL_FEATURED_LINK', ['featured' => true]);
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $href = route('cultural-calendar.show', $entry->id);
        $home = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $home->assertOk();
        $home->assertSee($href, false);
        $home->assertSee('CANONICAL_FEATURED_LINK', false);

        $detail = $this->actingAs($this->user)->get($href);
        $detail->assertOk();
        $detail->assertSee('CANONICAL_FEATURED_LINK', false);
    }

    public function test_legacy_id_without_matching_entry_is_404(): void
    {
        $legacy = $this->makeLegacyEvent(['naslov' => 'LEGACY_ONLY_ID']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $legacy->id))
            ->assertNotFound();
    }

    public function test_colliding_id_resolves_canonical_entry(): void
    {
        $legacy = $this->makeLegacyEvent(['naslov' => 'COLLIDE_LEGACY_TITLE']);
        $entry = $this->makeEntryWithId($legacy->id, CulturalEventEntry::STATUS_PUBLISHED, 'COLLIDE_CANONICAL_TITLE');
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $this->assertSame($legacy->id, $entry->id);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('COLLIDE_CANONICAL_TITLE', false);
        $response->assertDontSee('COLLIDE_LEGACY_TITLE', false);
    }

    public function test_canonical_draft_and_pending_direct_url_are_404(): void
    {

        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'DRAFT_LINK');
        $pending = $this->makeEntry(CulturalEventEntry::STATUS_PENDING_APPROVAL, 'PENDING_LINK');

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $draft->id))
            ->assertNotFound();
        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $pending->id))
            ->assertNotFound();
    }

    public function test_archive_public_detail_200_and_null_history_404(): void
    {

        $public = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'ARCH_PUBLIC_LINK', [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOccurrence($public, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $nullHistory = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'ARCH_NULL_LINK', [
            'archived_from_status' => null,
        ]);
        $this->makeOccurrence($nullHistory, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $public->id))
            ->assertOk()
            ->assertSee('ARCH_PUBLIC_LINK', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $nullHistory->id))
            ->assertNotFound();
    }

    public function test_nonexistent_id_is_404(): void
    {
        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', 999999))
            ->assertNotFound();
    }

    public function test_invalid_id_format_is_fail_closed_404(): void
    {
        $this->actingAs($this->user)
            ->get('/kalendar-kulture/dogadjaj/abc')
            ->assertNotFound();
    }

    public function test_canonical_archive_card_link_opens_archive_public_show(): void
    {

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'ARCHIVE_CARD_TO_SHOW', [
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $href = route('cultural-calendar.show', $entry->id);
        $archive = $this->actingAs($this->user)->get(route('cultural-calendar.archive'));
        $archive->assertOk();
        $archive->assertSee($href, false);
        $archive->assertSee('ARCHIVE_CARD_TO_SHOW', false);

        $detail = $this->actingAs($this->user)->get($href);
        $detail->assertOk();
        $detail->assertSee('ARCHIVE_CARD_TO_SHOW', false);
        $detail->assertSee('Otkazan', false);
    }

    public function test_archived_null_history_not_on_archive_page(): void
    {

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'ARCH_NULL_NO_CARD', [
            'archived_from_status' => null,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.archive'));
        $response->assertOk();
        $response->assertDontSee('ARCH_NULL_NO_CARD', false);
        $response->assertDontSee(route('cultural-calendar.show', $entry->id), false);
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
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'featured' => false,
        ], $extra));
    }

    private function makeEntryWithId(int $id, string $status, string $naslov): CulturalEventEntry
    {
        $entry = new CulturalEventEntry([
            'naslov' => $naslov,
            'status' => $status,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'featured' => false,
        ]);
        $entry->id = $id;
        $entry->save();

        return $entry->fresh();
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
