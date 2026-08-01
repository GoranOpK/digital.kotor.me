<?php

namespace Tests\Feature;

use App\Models\CulturalEvent;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalCalendarCr001Phase1Test extends TestCase
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
            'datum_od' => Carbon::today()->toDateString(),
            'datum_do' => null,
            'vrijeme' => '18:00:00',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides));
    }

    public function test_navigation_shows_pretraga_i_pregled(): void
    {
        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('Pretraga i pregled', false);
        $response->assertDontSee('Pregled događaja', false);
    }

    public function test_events_page_title_is_pretraga_i_pregled(): void
    {
        $response = $this->asUser()->get(route('cultural-calendar.events'));

        $response->assertOk();
        $response->assertSee('<h1 class="text-2xl font-bold text-gray-900">', false);
        $response->assertSee('Pretraga i pregled', false);
        $response->assertDontSee('Pregled događaja', false);
    }

    public function test_hero_is_still_rendered(): void
    {
        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('class="kk-hero"', false);
        $response->assertSee('img/heroKK.jpg', false);
        $response->assertSee('img/KKLOGOC.png', false);
        $response->assertSee('Logo Kalendara kulture', false);
    }

    public function test_featured_empty_state_is_neutral(): void
    {
        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('Trenutno nema istaknutih događaja.', false);
        $response->assertDontSee('Dodajte istaknuti događaj iz administracije', false);
    }

    public function test_featured_events_are_limited_to_three(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->makeEvent([
                'naslov' => "Istaknuti {$i}",
                'featured' => true,
                'datum_od' => Carbon::today()->addDays($i)->toDateString(),
            ]);
        }

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('Istaknuti 1', false);
        $response->assertSee('Istaknuti 2', false);
        $response->assertSee('Istaknuti 3', false);
        $response->assertDontSee('Istaknuti 4', false);
    }

    public function test_today_stat_card_links_with_date_parameter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00', 'Europe/Belgrade'));

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $today = Carbon::today()->toDateString();
        $expectedUrl = route('cultural-calendar.events', ['date' => $today]);
        $response->assertSee('href="'.e($expectedUrl).'"', false);
        $response->assertSee('>Danas</div>', false);

        Carbon::setTestNow();
    }

    public function test_week_stat_card_links_with_week_parameters(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00', 'Europe/Belgrade'));

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $today = Carbon::today();
        $weekEnd = Carbon::today()->endOfWeek();
        $expectedUrl = route('cultural-calendar.events', [
            'week_start' => $today->toDateString(),
            'week_end' => $weekEnd->toDateString(),
        ]);
        $response->assertSee('href="'.e($expectedUrl).'"', false);
        $response->assertSee('>Ove sedmice</div>', false);

        Carbon::setTestNow();
    }

    public function test_stat_cards_remain_links_when_counts_are_zero(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00', 'Europe/Belgrade'));

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('0 događaja', false);

        $today = Carbon::today()->toDateString();
        $weekEnd = Carbon::today()->endOfWeek()->toDateString();

        $response->assertSee(
            'href="'.e(route('cultural-calendar.events', ['date' => $today])).'"',
            false
        );
        $response->assertSee(
            'href="'.e(route('cultural-calendar.events', [
                'week_start' => $today,
                'week_end' => $weekEnd,
            ])).'"',
            false
        );

        Carbon::setTestNow();
    }

    public function test_selected_month_stat_shows_month_and_year_and_is_a_link(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00', 'Europe/Belgrade'));
        Carbon::setLocale('sr');

        $monthLabel = ucfirst(Carbon::today()->startOfMonth()->translatedFormat('F Y'));
        $monthValue = Carbon::today()->format('Y-m');

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('>'.$monthLabel.'</div>', false);
        $response->assertDontSee('Ovog mjeseca', false);
        $response->assertSee(
            'href="'.e(route('cultural-calendar.events', ['month' => $monthValue])).'"',
            false
        );

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<a\s[^>]*class="kk-stat-card"[^>]*>\s*<div class="kk-stat-label">'.preg_quote($monthLabel, '/').'<\/div>/u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<article class="kk-stat-card">\s*<div class="kk-stat-label">'.preg_quote($monthLabel, '/').'<\/div>/u',
            $html
        );

        Carbon::setTestNow();
    }

    public function test_upcoming_events_are_limited_to_three(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->makeEvent([
                'naslov' => "Naredni {$i}",
                'datum_od' => Carbon::today()->addDays($i)->toDateString(),
            ]);
        }

        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('Naredni događaji', false);
        $response->assertSee('Naredni 1', false);
        $response->assertSee('Naredni 2', false);
        $response->assertSee('Naredni 3', false);
        $response->assertDontSee('Naredni 4', false);
    }

    public function test_selected_date_shows_all_events_for_that_day(): void
    {
        $date = Carbon::today()->addDays(3)->toDateString();

        for ($i = 1; $i <= 4; $i++) {
            $this->makeEvent([
                'naslov' => "Dan {$i}",
                'datum_od' => $date,
            ]);
        }

        $response = $this->asUser()->get(route('cultural-calendar.index', [
            'month' => Carbon::parse($date)->format('Y-m'),
            'date' => $date,
        ]));

        $response->assertOk();
        $response->assertSee('Događaji za '.Carbon::parse($date)->format('d.m.Y'), false);
        $response->assertSee('Dan 1', false);
        $response->assertSee('Dan 2', false);
        $response->assertSee('Dan 3', false);
        $response->assertSee('Dan 4', false);
    }

    public function test_show_all_without_date_has_no_date_filter(): void
    {
        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $expectedUrl = route('cultural-calendar.events');
        $response->assertSee('href="'.e($expectedUrl).'"', false);
        $response->assertSee('>Prikaži sve događaje</a>', false);
    }

    public function test_show_all_with_date_passes_same_date(): void
    {
        $date = Carbon::today()->addDays(2)->toDateString();
        $this->makeEvent([
            'naslov' => 'Događaj za dugme',
            'datum_od' => $date,
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.index', [
            'month' => Carbon::parse($date)->format('Y-m'),
            'date' => $date,
        ]));

        $response->assertOk();
        $expectedUrl = route('cultural-calendar.events', ['date' => $date]);
        $response->assertSee('href="'.e($expectedUrl).'"', false);
        $response->assertSee('>Prikaži sve događaje</a>', false);
    }

    public function test_day_page_is_not_added_to_public_navigation(): void
    {
        $response = $this->asUser()->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertDontSee(route('cultural-calendar.day', ['date' => Carbon::today()->toDateString()]), false);
        $html = $response->getContent();
        $this->assertStringNotContainsString('/kalendar-kulture/dan/', $html);
    }

    public function test_existing_public_flows_still_work(): void
    {
        $event = $this->makeEvent([
            'naslov' => 'Tok događaj',
            'datum_od' => Carbon::today()->addDay()->toDateString(),
        ]);

        $this->asUser()->get(route('cultural-calendar.index'))->assertOk();
        $this->asUser()->get(route('cultural-calendar.events'))->assertOk();
        $this->asUser()->get(route('cultural-calendar.show', $event))->assertOk()->assertSee('Tok događaj', false);
        $this->asUser()->get(route('cultural-calendar.archive'))->assertOk();
    }
}
