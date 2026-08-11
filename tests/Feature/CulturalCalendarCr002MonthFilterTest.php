<?php

namespace Tests\Feature;

use App\Models\CulturalEvent;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalCalendarCr002MonthFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
    }

    private function asUser()
    {
        return $this->actingAs($this->user);
    }

    private function makeEvent(array $overrides = []): CulturalEvent
    {
        return CulturalEvent::create(array_merge([
            'naslov' => 'Test događaj',
            'opis' => 'Opis događaja',
            'datum_od' => '2026-08-15',
            'datum_do' => null,
            'vrijeme' => '18:00:00',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides));
    }

    public function test_selected_month_card_is_link_with_month_parameter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));
        Carbon::setLocale('sr');

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee(
            'href="'.e(route('cultural-calendar.events', ['tip' => 'dogadjaji', 'month' => '2026-08'])).'"',
            false
        );

        Carbon::setTestNow();
    }

    public function test_month_card_remains_link_when_count_is_zero(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('0 događaja', false);
        $response->assertSee(
            'href="'.e(route('cultural-calendar.events', ['tip' => 'dogadjaji', 'month' => '2026-08'])).'"',
            false
        );

        Carbon::setTestNow();
    }

    public function test_valid_month_shows_month_context_and_keeps_main_title(): void
    {
        Carbon::setLocale('sr');

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Pretraga i pregled', false);
        $response->assertSee('Izabrani mjesec: Avgust 2026', false);
        $response->assertDontSee('Događaji za Avgust', false);
    }

    public function test_month_filter_returns_event_with_datum_od_inside_month(): void
    {
        $this->makeEvent([
            'naslov' => 'Unutar mjeseca',
            'datum_od' => '2026-08-15',
            'datum_do' => null,
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Unutar mjeseca', false);
    }

    public function test_month_filter_returns_multiday_event_crossing_month_start(): void
    {
        $this->makeEvent([
            'naslov' => 'Presijeca početak',
            'datum_od' => '2026-07-28',
            'datum_do' => '2026-08-03',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Presijeca početak', false);
    }

    public function test_month_filter_returns_multiday_event_crossing_month_end(): void
    {
        $this->makeEvent([
            'naslov' => 'Presijeca kraj',
            'datum_od' => '2026-08-28',
            'datum_do' => '2026-09-02',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Presijeca kraj', false);
    }

    public function test_month_filter_excludes_event_outside_month(): void
    {
        $this->makeEvent([
            'naslov' => 'Van mjeseca',
            'datum_od' => '2026-07-10',
            'datum_do' => null,
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertDontSee('Van mjeseca', false);
    }

    public function test_current_month_includes_earlier_published_events_in_same_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 12:00:00', 'Europe/Belgrade'));

        $this->makeEvent([
            'naslov' => 'Ranije u mjesecu',
            'datum_od' => '2026-08-05',
            'datum_do' => null,
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Ranije u mjesecu', false);

        Carbon::setTestNow();
    }

    public function test_month_card_count_matches_month_filter_result_set(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 12:00:00', 'Europe/Belgrade'));

        $this->makeEvent(['naslov' => 'A', 'datum_od' => '2026-08-02']);
        $this->makeEvent(['naslov' => 'B', 'datum_od' => '2026-08-10']);
        $this->makeEvent([
            'naslov' => 'C',
            'datum_od' => '2026-07-30',
            'datum_do' => '2026-08-02',
        ]);
        $this->makeEvent(['naslov' => 'D van', 'datum_od' => '2026-09-01']);

        $index = $this->asUser()->get(route('cultural-calendar.index'));
        $index->assertOk();
        $index->assertSee('3 događaja', false);

        $events = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
        ]));
        $events->assertOk();
        $events->assertSee('A', false);
        $events->assertSee('B', false);
        $events->assertSee('C', false);
        $events->assertDontSee('D van', false);

        Carbon::setTestNow();
    }

    public function test_invalid_month_does_not_activate_month_context(): void
    {
        $this->makeEvent([
            'naslov' => 'Buduci dogadjaj',
            'datum_od' => Carbon::today()->addDays(5)->toDateString(),
        ]);

        foreach (['2026-13', '2026-00', '08-2026', '2026-8', 'abc'] as $invalid) {
            $response = $this->asUser()->get(route('cultural-calendar.events', [
                'tip' => 'dogadjaji',
                'month' => $invalid,
            ]));

            $response->assertOk();
            $response->assertSee('Pretraga i pregled', false);
            $response->assertDontSee('Izabrani mjesec:', false);
        }
    }

    public function test_date_has_priority_over_week_and_month(): void
    {
        $this->makeEvent([
            'naslov' => 'Samo datum',
            'datum_od' => '2026-08-15',
            'datum_do' => null,
        ]);
        $this->makeEvent([
            'naslov' => 'Samo kasnije u mjesecu',
            'datum_od' => '2026-08-25',
            'datum_do' => null,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00', 'Europe/Belgrade'));

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'date' => '2026-08-15',
            'week_start' => '2026-08-01',
            'week_end' => '2026-08-07',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Događaji za 15.08.2026', false);
        $response->assertDontSee('Izabrani mjesec:', false);
        $response->assertSee('Samo datum', false);
        $response->assertDontSee('Samo kasnije u mjesecu', false);

        Carbon::setTestNow();
    }

    public function test_week_has_priority_over_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 10:00:00', 'Europe/Belgrade'));

        $this->makeEvent([
            'naslov' => 'U sedmici',
            'datum_od' => '2026-08-04',
        ]);
        $this->makeEvent([
            'naslov' => 'Kasnije u mjesecu',
            'datum_od' => '2026-08-25',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'week_start' => '2026-08-03',
            'week_end' => '2026-08-09',
            'month' => '2026-08',
        ]));

        $response->assertOk();
        $response->assertSee('Događaji za narednu sedmicu', false);
        $response->assertDontSee('Izabrani mjesec:', false);
        $response->assertSee('U sedmici', false);
        $response->assertDontSee('Kasnije u mjesecu', false);

        Carbon::setTestNow();
    }

    public function test_month_is_preserved_through_pagination(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->makeEvent([
                'naslov' => "Mjesec dogadjaj {$i}",
                'datum_od' => sprintf('2026-08-%02d', min($i, 28)),
            ]);
        }

        $response = $this->asUser()->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'month' => '2026-08',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Izabrani mjesec: Avgust 2026', false);
        $response->assertSee('month=2026-08', false);
    }

    public function test_existing_public_flows_still_work(): void
    {
        $event = $this->makeEvent([
            'naslov' => 'Tok CR002',
            'datum_od' => Carbon::today()->addDay()->toDateString(),
        ]);

        $this->asUser()->get(route('cultural-calendar.index'))->assertOk();
        $this->asUser()->get(route('cultural-calendar.events'))->assertOk();
        $this->asUser()->get(route('cultural-calendar.show', $event))->assertOk()->assertSee('Tok CR002', false);
        $this->asUser()->get(route('cultural-calendar.archive'))->assertOk();
    }
}
