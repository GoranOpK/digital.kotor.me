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

class CulturalCalendarCr003FiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<string, CulturalCategory> */
    private array $categories = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
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

    private function category(string $naziv): CulturalCategory
    {
        if (! isset($this->categories[$naziv])) {
            $this->categories[$naziv] = CulturalCategory::create([
                'naziv' => $naziv,
                'status' => CulturalCategory::STATUS_ACTIVE,
            ]);
        }

        return $this->categories[$naziv];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeEvent(array $overrides = []): CulturalEventEntry
    {
        $datum = $overrides['datum_od'] ?? '2026-08-15';
        $lokacija = array_key_exists('lokacija', $overrides) ? $overrides['lokacija'] : 'Kotor';
        $kategorija = $overrides['kategorija'] ?? 'Koncerti';
        $status = $overrides['status'] ?? CulturalEventEntry::STATUS_PUBLISHED;
        $opis = $overrides['opis'] ?? 'Opis događaja';
        unset(
            $overrides['datum_od'],
            $overrides['datum_do'],
            $overrides['vrijeme'],
            $overrides['lokacija'],
            $overrides['kategorija'],
            $overrides['status'],
            $overrides['opis']
        );

        $entryStatus = match ($status) {
            'draft' => CulturalEventEntry::STATUS_DRAFT,
            'cancelled' => CulturalEventEntry::STATUS_CANCELLED,
            'archived' => CulturalEventEntry::STATUS_ARCHIVED,
            default => CulturalEventEntry::STATUS_PUBLISHED,
        };

        $entry = CulturalEventEntry::create(array_merge([
            'naslov' => 'Test događaj',
            'opis' => $opis,
            'status' => $entryStatus,
            'category_id' => $this->category($kategorija)->id,
            'created_by' => $this->user->id,
            'featured' => false,
        ], $overrides));

        $occAttrs = [
            'event_entry_id' => $entry->id,
            'datum' => $datum,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ];
        if ($lokacija !== null && $lokacija !== '') {
            $occAttrs['location_manual_name'] = $lokacija;
        }

        CulturalOccurrence::create($occAttrs);

        return $entry;
    }

    private function eventsUrl(array $params = []): string
    {
        return route('cultural-calendar.events', array_merge(['tip' => 'dogadjaji'], $params));
    }

    public function test_filter_zone_is_always_visible_with_get_form(): void
    {
        $response = $this->asUser()->get($this->eventsUrl());

        $response->assertOk();
        $response->assertSee('name="q"', false);
        $response->assertSee('name="category"', false);
        $response->assertSee('name="location"', false);
        $response->assertSee('Pretraži', false);
        $response->assertSee('method="GET"', false);
        $response->assertSee('action="'.e(route('cultural-calendar.events')).'"', false);
    }

    public function test_q_searches_naslov_opis_and_lokacija_case_insensitive(): void
    {
        $this->makeEvent([
            'naslov' => 'Ljetnji Festival',
            'opis' => 'Običan opis',
            'lokacija' => 'Budva',
            'datum_od' => '2026-08-20',
        ]);
        $this->makeEvent([
            'naslov' => 'Drugo',
            'opis' => 'Poseban OPIS koncerta',
            'lokacija' => 'Tivat',
            'datum_od' => '2026-08-21',
        ]);
        $this->makeEvent([
            'naslov' => 'Treće',
            'opis' => 'Ništa',
            'lokacija' => 'Kotor Stari Grad',
            'datum_od' => '2026-08-22',
        ]);
        $this->makeEvent([
            'naslov' => 'Van',
            'opis' => 'Van opsega',
            'lokacija' => 'Herceg Novi',
            'datum_od' => '2026-08-23',
        ]);

        $byTitle = $this->asUser()->get($this->eventsUrl(['q' => 'festival']));
        $byTitle->assertOk();
        $byTitle->assertSee('Ljetnji Festival', false);
        $byTitle->assertDontSee('Van opsega', false);

        $byOpis = $this->asUser()->get($this->eventsUrl(['q' => 'opis koncerta']));
        $byOpis->assertOk();
        $byOpis->assertSee('Drugo', false);

        $byLokacija = $this->asUser()->get($this->eventsUrl(['q' => 'stari grad']));
        $byLokacija->assertOk();
        $byLokacija->assertSee('Treće', false);
    }

    public function test_q_does_not_search_category(): void
    {
        $this->makeEvent([
            'naslov' => 'Bez pogodaka u tekstu',
            'opis' => 'Bez pogodaka',
            'lokacija' => 'Perast',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-08-18',
        ]);

        $response = $this->asUser()->get($this->eventsUrl([
            'q' => 'Koncerti',
        ]));

        $response->assertOk();
        $response->assertDontSee('Bez pogodaka u tekstu', false);
    }

    public function test_category_filter_uses_exact_category(): void
    {
        $this->makeEvent([
            'naslov' => 'Koncert A',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-08-16',
        ]);
        $this->makeEvent([
            'naslov' => 'Izložba B',
            'kategorija' => 'Izložbe',
            'datum_od' => '2026-08-17',
        ]);

        $response = $this->asUser()->get($this->eventsUrl([
            'category' => 'Koncerti',
        ]));

        $response->assertOk();
        $response->assertSee('Koncert A', false);
        $response->assertDontSee('Izložba B', false);
        $response->assertSee('value="Koncerti" selected', false);
    }

    public function test_invalid_category_is_ignored(): void
    {
        $this->makeEvent([
            'naslov' => 'Validan dogadjaj',
            'datum_od' => '2026-08-16',
        ]);

        $response = $this->asUser()->get($this->eventsUrl([
            'category' => 'Nepostojeca',
        ]));

        $response->assertOk();
        $response->assertSee('Validan dogadjaj', false);
        $response->assertDontSee('Kategorija: Nepostojeca', false);
    }

    public function test_location_dropdown_lists_unique_published_sorted_locations(): void
    {
        $this->makeEvent(['naslov' => 'A', 'lokacija' => 'Tivat', 'datum_od' => '2026-08-16']);
        $this->makeEvent(['naslov' => 'B', 'lokacija' => 'Budva', 'datum_od' => '2026-08-17']);
        $this->makeEvent(['naslov' => 'C', 'lokacija' => 'Budva', 'datum_od' => '2026-08-18']);
        $this->makeEvent(['naslov' => 'D', 'lokacija' => null, 'datum_od' => '2026-08-19']);
        $this->makeEvent(['naslov' => 'E', 'lokacija' => '', 'datum_od' => '2026-08-20']);
        $this->makeEvent([
            'naslov' => 'Draft',
            'lokacija' => 'Zelenika',
            'status' => 'draft',
            'datum_od' => '2026-08-21',
        ]);

        $response = $this->asUser()->get($this->eventsUrl());
        $html = $response->getContent();

        $response->assertOk();
        $budvaPos = strpos($html, 'value="Budva"');
        $tivatPos = strpos($html, 'value="Tivat"');
        $this->assertNotFalse($budvaPos);
        $this->assertNotFalse($tivatPos);
        $this->assertLessThan($tivatPos, $budvaPos);
        $response->assertDontSee('value="Zelenika"', false);
    }

    public function test_location_filter_and_invalid_location_ignored(): void
    {
        $this->makeEvent(['naslov' => 'U Kotoru', 'lokacija' => 'Kotor', 'datum_od' => '2026-08-16']);
        $this->makeEvent(['naslov' => 'U Tivtu', 'lokacija' => 'Tivat', 'datum_od' => '2026-08-17']);

        $filtered = $this->asUser()->get($this->eventsUrl([
            'location' => 'Kotor',
        ]));
        $filtered->assertOk();
        $filtered->assertSee('U Kotoru', false);
        $filtered->assertDontSee('U Tivtu', false);

        $invalid = $this->asUser()->get($this->eventsUrl([
            'location' => 'Nepostojeca',
        ]));
        $invalid->assertOk();
        $invalid->assertSee('U Kotoru', false);
        $invalid->assertSee('U Tivtu', false);
        $invalid->assertDontSee('Lokacija: Nepostojeca', false);
    }

    public function test_and_combines_q_category_location_with_month(): void
    {
        $this->makeEvent([
            'naslov' => 'Jazz Night Match',
            'opis' => 'Večernji program',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-08-12',
        ]);
        $this->makeEvent([
            'naslov' => 'Jazz Night Other Loc',
            'opis' => 'Večernji program',
            'lokacija' => 'Tivat',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-08-13',
        ]);
        $this->makeEvent([
            'naslov' => 'Jazz Night Other Cat',
            'opis' => 'Večernji program',
            'lokacija' => 'Kotor',
            'kategorija' => 'Izložbe',
            'datum_od' => '2026-08-14',
        ]);
        $this->makeEvent([
            'naslov' => 'Jazz Night Other Month',
            'opis' => 'Večernji program',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-09-05',
        ]);

        $response = $this->asUser()->get($this->eventsUrl([
            'month' => '2026-08',
            'q' => 'jazz',
            'category' => 'Koncerti',
            'location' => 'Kotor',
        ]));

        $response->assertOk();
        $response->assertSee('Jazz Night Match', false);
        $response->assertDontSee('Jazz Night Other Loc', false);
        $response->assertDontSee('Jazz Night Other Cat', false);
        $response->assertDontSee('Jazz Night Other Month', false);
        $response->assertSee('Izabrani mjesec: Avgust 2026', false);
        $response->assertSee('value="jazz"', false);
        $response->assertSee('value="Koncerti" selected', false);
        $response->assertSee('value="Kotor" selected', false);
    }

    public function test_active_filters_and_individual_remove_and_reset(): void
    {
        $this->makeEvent([
            'naslov' => 'Aktivni filter dogadjaj',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-08-16',
        ]);

        $response = $this->asUser()->get($this->eventsUrl([
            'month' => '2026-08',
            'q' => 'Aktivni',
            'category' => 'Koncerti',
            'location' => 'Kotor',
        ]));

        $response->assertOk();
        $response->assertSee('Pretraga: Aktivni', false);
        $response->assertSee('Kategorija: Koncerti', false);
        $response->assertSee('Lokacija: Kotor', false);
        $response->assertSee('Mjesec: Avgust 2026', false);
        $response->assertSee('Poništi sve filtere', false);
        $response->assertSee(
            'href="'.e(route('cultural-calendar.events')).'"',
            false
        );

        $withoutQ = $this->eventsUrl([
            'month' => '2026-08',
            'category' => 'Koncerti',
            'location' => 'Kotor',
        ]);
        $response->assertSee('href="'.e($withoutQ).'"', false);
    }

    public function test_pagination_preserves_filter_query_string(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->makeEvent([
                'naslov' => "Filter page {$i}",
                'lokacija' => 'Kotor',
                'kategorija' => 'Koncerti',
                'datum_od' => sprintf('2026-08-%02d', min($i, 28)),
            ]);
        }

        $response = $this->asUser()->get($this->eventsUrl([
            'q' => 'Filter page',
            'category' => 'Koncerti',
            'location' => 'Kotor',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('value="Filter page"', false);
        $response->assertSee('q=Filter%20page', false);
        $response->assertSee('category=Koncerti', false);
        $response->assertSee('location=Kotor', false);
    }

    public function test_back_preserves_filter_context_on_show(): void
    {
        $event = $this->makeEvent([
            'naslov' => 'Back kontekst',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'datum_od' => '2026-08-16',
        ]);

        $list = $this->asUser()->get($this->eventsUrl([
            'q' => 'Back',
            'category' => 'Koncerti',
            'location' => 'Kotor',
        ]));
        $list->assertOk();
        $list->assertSee('back=', false);
        $list->assertSee(urlencode('/kalendar-kulture/pregled-dogadjaja'), false);

        $back = '/kalendar-kulture/pregled-dogadjaja?tip=dogadjaji&q=Back&category=Koncerti&location=Kotor';
        $show = $this->asUser()->get(route('cultural-calendar.show', [
            'event' => $event,
            'back' => $back,
        ]));
        $show->assertOk();
        $show->assertSee('href="'.e($back).'"', false);
    }

    public function test_cr001_and_cr002_date_flows_still_work_with_filter_zone(): void
    {
        $this->makeEvent([
            'naslov' => 'Danas dogadjaj',
            'datum_od' => '2026-08-10',
        ]);
        $this->makeEvent([
            'naslov' => 'Mjesec dogadjaj',
            'datum_od' => '2026-08-05',
        ]);

        $date = $this->asUser()->get($this->eventsUrl([
            'date' => '2026-08-10',
        ]));
        $date->assertOk();
        $date->assertSee('Događaji za 10.08.2026', false);
        $date->assertSee('name="q"', false);
        $date->assertSee('name="date"', false);
        $date->assertSee('value="2026-08-10"', false);

        $month = $this->asUser()->get($this->eventsUrl([
            'month' => '2026-08',
        ]));
        $month->assertOk();
        $month->assertSee('Pretraga i pregled', false);
        $month->assertSee('Izabrani mjesec: Avgust 2026', false);
        $month->assertSee('Mjesec dogadjaj', false);
        $month->assertSee('name="month"', false);
        $month->assertSee('value="2026-08"', false);
    }
}
