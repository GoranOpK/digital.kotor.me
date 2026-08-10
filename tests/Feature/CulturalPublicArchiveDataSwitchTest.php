<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-09 — archive-only query, badge, show, UI DATA SWITCH.
 */
class CulturalPublicArchiveDataSwitchTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_REASON = 'INTERNI_RAZLOG_OTKAZIVANJA_NE_U_HTML';

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

    public function test_past_published_before_maintenance_enters_archive_query(): void
    {
        $entry = $this->makeEntry('PAST_PUBLISHED', CulturalEventEntry::STATUS_PUBLISHED);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $ids = app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all();
        $this->assertContains($entry->id, $ids);
    }

    public function test_past_cancelled_before_maintenance_enters_archive_query(): void
    {
        $entry = $this->makeEntry('PAST_CANCELLED', CulturalEventEntry::STATUS_CANCELLED);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $ids = app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all();
        $this->assertContains($entry->id, $ids);
    }

    public function test_archived_from_published_enters_archive_query(): void
    {
        $entry = $this->makeEntry('ARCH_FROM_PUB', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertContains(
            $entry->id,
            app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all()
        );
    }

    public function test_archived_from_cancelled_enters_archive_query(): void
    {
        $entry = $this->makeEntry('ARCH_FROM_CANC', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $this->assertContains(
            $entry->id,
            app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all()
        );
    }

    public function test_archived_null_history_excluded_from_archive_query(): void
    {
        $entry = $this->makeEntry('ARCH_NULL', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => null,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertNotContains(
            $entry->id,
            app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all()
        );
    }

    public function test_draft_and_pending_excluded_from_archive_query(): void
    {
        $draft = $this->makeEntry('DRAFT_ARCH', CulturalEventEntry::STATUS_DRAFT);
        $this->makeOcc($draft, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $pending = $this->makeEntry('PENDING_ARCH', CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $this->makeOcc($pending, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $ids = app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all();
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($pending->id, $ids);
    }

    public function test_future_published_excluded_from_archive_query(): void
    {
        $entry = $this->makeEntry('FUTURE_PUB', CulturalEventEntry::STATUS_PUBLISHED);
        $this->makeOcc($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertNotContains(
            $entry->id,
            app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all()
        );
    }

    public function test_base_and_search_do_not_return_archived(): void
    {
        $entry = $this->makeEntry('ARCH_NOT_ACTIVE', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $query = app(CulturalPublicEventQuery::class);
        $this->assertNotContains($entry->id, $query->base()->pluck('id')->all());
        $this->assertNotContains(
            $entry->id,
            $query->filterByQ('ARCH_NOT_ACTIVE')->pluck('id')->all()
        );
    }

    public function test_archived_from_published_with_zero_historical_occ_excluded_from_archive_query(): void
    {
        $entry = $this->makeEntry('ARCH_PUB_ZERO_OCC', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);

        $this->assertSame(0, $entry->occurrences()->count());
        $this->assertNotContains(
            $entry->id,
            app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all()
        );
    }

    public function test_archived_from_cancelled_with_zero_historical_occ_excluded_from_archive_query(): void
    {
        $entry = $this->makeEntry('ARCH_CANC_ZERO_OCC', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
        ]);

        $this->assertSame(0, $entry->occurrences()->count());
        $this->assertNotContains(
            $entry->id,
            app(CulturalPublicEventQuery::class)->archive()->pluck('id')->all()
        );
    }

    public function test_show_archived_with_history_but_zero_historical_occ_returns_404(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $entry = $this->makeEntry('SHOW_ARCH_ZERO_OCC', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertNotFound();
    }

    public function test_badge_archived_from_published_is_zavrsen(): void
    {
        $entry = $this->makeEntry('BADGE_PUB', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);

        $this->assertSame([
            'key' => 'finished',
            'label' => 'Završen',
            'class' => 'kk-status-finished',
        ], $entry->publicStatus());
    }

    public function test_badge_archived_from_cancelled_is_otkazan(): void
    {
        $entry = $this->makeEntry('BADGE_CANC', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
        ]);

        $this->assertSame([
            'key' => 'cancelled',
            'label' => 'Otkazan',
            'class' => 'kk-status-cancelled',
        ], $entry->publicStatus());
    }

    public function test_badge_archived_null_history_is_null(): void
    {
        $entry = $this->makeEntry('BADGE_NULL', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => null,
        ]);

        $this->assertNull($entry->publicStatus());
    }

    public function test_show_archived_from_published_returns_200(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $entry = $this->makeEntry('SHOW_ARCH_PUB', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('SHOW_ARCH_PUB', false);
        $response->assertSee('Završen', false);
    }

    public function test_show_archived_from_cancelled_returns_200_with_otkazan(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $entry = $this->makeEntry('SHOW_ARCH_CANC', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
            'cancellation_reason' => self::SECRET_REASON,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('SHOW_ARCH_CANC', false);
        $response->assertSee('Otkazan', false);
        $response->assertSee(self::SECRET_REASON, false);
        $response->assertSee('Napomena:', false);
    }

    public function test_show_archived_null_history_returns_404(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $entry = $this->makeEntry('SHOW_ARCH_NULL', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => null,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertNotFound();
    }

    public function test_show_draft_and_pending_return_404(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $draft = $this->makeEntry('SHOW_DRAFT', CulturalEventEntry::STATUS_DRAFT);
        $pending = $this->makeEntry('SHOW_PENDING', CulturalEventEntry::STATUS_PENDING_APPROVAL);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $draft->id))
            ->assertNotFound();
        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $pending->id))
            ->assertNotFound();
    }

    public function test_legacy_archive_unchanged_ignores_canonical_entries(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::LEGACY]);

        $legacy = \App\Models\CulturalEvent::create([
            'naslov' => 'LEGACY_ARCHIVE_ONLY',
            'opis' => 'x',
            'kategorija' => 'Koncerti',
            'lokacija' => 'Stari grad',
            'datum_od' => '2026-08-01',
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        $canonical = $this->makeEntry('CANONICAL_ARCHIVE_ONLY', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOcc($canonical, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.archive'));
        $response->assertOk();
        $response->assertSee('LEGACY_ARCHIVE_ONLY', false);
        $response->assertDontSee('CANONICAL_ARCHIVE_ONLY', false);
        $this->assertTrue($legacy->exists);
    }

    public function test_canonical_archive_uses_archive_only_data_sorted_desc(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        \App\Models\CulturalEvent::create([
            'naslov' => 'LEGACY_MUST_NOT_APPEAR',
            'opis' => 'x',
            'kategorija' => 'Koncerti',
            'lokacija' => 'Stari grad',
            'datum_od' => '2026-08-01',
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        $older = $this->makeEntry('ARCHIVE_OLDER', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOcc($older, [
            'datum' => '2026-07-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $newer = $this->makeEntry('ARCHIVE_NEWER', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOcc($newer, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.archive'));
        $response->assertOk();
        $response->assertSee('ARCHIVE_NEWER', false);
        $response->assertSee('ARCHIVE_OLDER', false);
        $response->assertDontSee('LEGACY_MUST_NOT_APPEAR', false);
        $response->assertSee('01.08.2026', false);
        $response->assertSee(route('cultural-calendar.show', $newer->id), false);

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'ARCHIVE_OLDER'),
            strpos($html, 'ARCHIVE_NEWER')
        );
    }

    public function test_canonical_archive_empty_does_not_crash(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.archive'));
        $response->assertOk();
        $response->assertSee('U arhivi trenutno nema događaja.', false);
    }

    public function test_canonical_archive_paginates_twelve(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        for ($i = 1; $i <= 13; $i++) {
            $entry = $this->makeEntry("PAGE_ITEM_{$i}", CulturalEventEntry::STATUS_ARCHIVED, [
                'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
            ]);
            $this->makeOcc($entry, [
                'datum' => sprintf('2026-07-%02d', min($i, 28)),
                'cjelodnevno' => true,
                'status' => CulturalOccurrence::STATUS_FINISHED,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.archive'));
        $response->assertOk();
        $response->assertSee('PAGE_ITEM_', false);
        $this->assertSame(12, substr_count($response->getContent(), '<article class="kk-archive-card">'));
        $response->assertSee('?page=2', false);
    }

    private function makeEntry(string $naslov, string $status, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'opis' => 'Opis '.$naslov,
            'status' => $status,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ], $extra));
    }

    private function makeOcc(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }
}
