<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CR-004B / PO-CR4B-01…10 — javni prikaz otkazanih događaja (canonical).
 */
class CulturalCalendarCr004BCancelledVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const CANCELLED_NOTICE = 'Ovaj događaj je otkazan i neće biti održan u planiranom terminu.';

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

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function asUser()
    {
        return $this->actingAs($this->user);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeEvent(array $overrides = []): CulturalEventEntry
    {
        $datum = $overrides['datum_od'] ?? '2026-08-15';
        $legacyStatus = $overrides['status'] ?? 'published';
        $lokacija = $overrides['lokacija'] ?? 'Kotor';
        $kategorija = $overrides['kategorija'] ?? null;
        unset(
            $overrides['datum_od'],
            $overrides['datum_do'],
            $overrides['vrijeme'],
            $overrides['vrijeme_do'],
            $overrides['lokacija'],
            $overrides['kategorija'],
            $overrides['status']
        );

        $entryStatus = match ($legacyStatus) {
            'cancelled' => CulturalEventEntry::STATUS_CANCELLED,
            'draft' => CulturalEventEntry::STATUS_DRAFT,
            'archived' => CulturalEventEntry::STATUS_ARCHIVED,
            default => CulturalEventEntry::STATUS_PUBLISHED,
        };

        $extra = $overrides;
        if (
            $entryStatus === CulturalEventEntry::STATUS_ARCHIVED
            && ! array_key_exists('archived_from_status', $extra)
        ) {
            $extra['archived_from_status'] = CulturalEventEntry::STATUS_PUBLISHED;
        }

        $categoryId = $this->category->id;
        if (is_string($kategorija) && $kategorija !== '' && $kategorija !== $this->category->naziv) {
            $categoryId = CulturalCategory::query()->firstOrCreate(
                ['naziv' => $kategorija],
                ['status' => CulturalCategory::STATUS_ACTIVE]
            )->id;
        }

        $entry = CulturalEventEntry::create(array_merge([
            'naslov' => 'CR-004B događaj',
            'opis' => 'Opis',
            'status' => $entryStatus,
            'category_id' => $categoryId,
            'created_by' => $this->user->id,
            'featured' => false,
        ], $extra));

        $occStatus = match ($entryStatus) {
            CulturalEventEntry::STATUS_CANCELLED => CulturalOccurrence::STATUS_CANCELLED,
            CulturalEventEntry::STATUS_ARCHIVED => CulturalOccurrence::STATUS_FINISHED,
            default => CulturalOccurrence::STATUS_PLANNED,
        };

        CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => $datum,
            'cjelodnevno' => true,
            'status' => $occStatus,
            'location_manual_name' => $lokacija,
        ]);

        return $entry;
    }

    public function test_cancelled_event_is_publicly_visible_on_home_events_and_day(): void
    {
        $this->makeEvent([
            'naslov' => 'Otkazan naredni',
            'status' => 'cancelled',
            'datum_od' => '2026-08-15',
        ]);

        $homeDay = $this->asUser()->get(route('cultural-calendar.index', [
            'month' => '2026-08',
            'date' => '2026-08-15',
        ]));
        $homeDay->assertOk();
        $homeDay->assertSee('Otkazan naredni', false);

        $events = $this->asUser()->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));
        $events->assertOk();
        $events->assertSee('Otkazan naredni', false);
        $events->assertSee('Otkazan', false);
        $events->assertSee('kk-status-cancelled', false);

        $day = $this->asUser()->get(route('cultural-calendar.day', ['date' => '2026-08-15']));
        $day->assertOk();
        $day->assertSee('Otkazan naredni', false);
    }

    public function test_cancelled_show_returns_200_with_notice_and_badge(): void
    {
        $event = $this->makeEvent([
            'naslov' => 'Otkazan detalj',
            'status' => 'cancelled',
            'datum_od' => '2026-08-20',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.show', $event));
        $response->assertOk();
        $response->assertSee('Otkazan detalj', false);
        $response->assertSee('Otkazan', false);
        $response->assertSee('kk-status-cancelled', false);
        $response->assertSee(self::CANCELLED_NOTICE, false);
        $response->assertSee('kk-show-cancelled-notice', false);
    }

    public function test_draft_and_archived_show_return_404(): void
    {
        $draft = $this->makeEvent([
            'naslov' => 'Nacrt događaj',
            'status' => 'draft',
            'datum_od' => '2026-08-20',
        ]);
        $archived = $this->makeEvent([
            'naslov' => 'Arhiviran događaj',
            'status' => 'archived',
            'archived_from_status' => null,
            'datum_od' => '2026-08-01',
        ]);

        $this->asUser()->get(route('cultural-calendar.show', $draft))->assertNotFound();
        $this->asUser()->get(route('cultural-calendar.show', $archived))->assertNotFound();
    }

    public function test_featured_cancelled_is_excluded_from_featured_section(): void
    {
        $this->makeEvent([
            'naslov' => 'Istaknuti otkazani',
            'status' => 'cancelled',
            'featured' => true,
            'datum_od' => '2026-08-20',
        ]);
        $this->makeEvent([
            'naslov' => 'Istaknuti objavljeni',
            'status' => 'published',
            'featured' => true,
            'datum_od' => '2026-08-18',
        ]);

        $home = $this->asUser()->get(route('cultural-calendar.index'));
        $home->assertOk();
        $home->assertSee('Istaknuti objavljeni', false);

        $html = $home->getContent();
        $this->assertTrue(
            preg_match(
                '/<aside class="kk-card kk-featured-wrap">([\s\S]*?)<\/aside>/u',
                $html,
                $featuredMatches
            ) === 1,
            'Sekcija Istaknuti mora postojati.'
        );
        $featuredHtml = $featuredMatches[1];
        $this->assertStringContainsString('Istaknuti objavljeni', $featuredHtml);
        $this->assertStringNotContainsString(
            'Istaknuti otkazani',
            $featuredHtml,
            'Cancelled featured ne smije biti u sekciji Istaknuti (PO-CR4B-03).'
        );

        $events = $this->asUser()->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));
        $events->assertOk();
        $events->assertSee('Istaknuti otkazani', false);

        $event = CulturalEventEntry::where('naslov', 'Istaknuti otkazani')->firstOrFail();
        $this->assertTrue($event->featured);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $event->status);
    }

    public function test_archive_shows_past_cancelled_with_otkazan_badge(): void
    {
        $this->makeEvent([
            'naslov' => 'Prošli otkazani',
            'status' => 'archived',
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
            'datum_od' => '2026-08-01',
        ]);

        CulturalEventEntry::where('naslov', 'Prošli otkazani')->firstOrFail()
            ->occurrences()->update(['status' => CulturalOccurrence::STATUS_CANCELLED]);

        $archive = $this->asUser()->get(route('cultural-calendar.archive'));
        $archive->assertOk();
        $archive->assertSee('Prošli otkazani', false);
        $archive->assertSee('Otkazan', false);
        $archive->assertSee('kk-status-cancelled', false);

        $home = $this->asUser()->get(route('cultural-calendar.index'));
        $home->assertOk();
        $home->assertDontSee('Prošli otkazani', false);
    }

    public function test_statistics_include_cancelled_events(): void
    {
        $this->makeEvent([
            'naslov' => 'Otkazan danas',
            'status' => 'cancelled',
            'datum_od' => '2026-08-10',
        ]);

        $home = $this->asUser()->get(route('cultural-calendar.index', ['month' => '2026-08']));
        $home->assertOk();

        $html = $home->getContent();
        $this->assertMatchesRegularExpression(
            '/Danas[\s\S]*?>\s*1\s*</u',
            $html,
            'Statistika Danas mora uključiti cancelled događaj.'
        );
        $this->assertMatchesRegularExpression(
            '/Ove sedmice[\s\S]*?>\s*[1-9]\d*\s*</u',
            $html,
            'Statistika Ove sedmice mora uključiti cancelled događaj.'
        );
    }

    public function test_cancelled_participates_in_search_without_new_url_params(): void
    {
        $this->makeEvent([
            'naslov' => 'Otkazan koncert pretrage',
            'status' => 'cancelled',
            'datum_od' => '2026-08-22',
            'kategorija' => 'Koncerti',
            'lokacija' => 'Kotor',
        ]);

        $url = route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'q' => 'pretrage',
            'category' => 'Koncerti',
        ]);
        $response = $this->asUser()->get($url);
        $response->assertOk();
        $response->assertSee('Otkazan koncert pretrage', false);
        $response->assertSee('Otkazan', false);

        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        $this->assertArrayNotHasKey('status', $params);
        $this->assertArrayNotHasKey('cancelled', $params);
    }
}
