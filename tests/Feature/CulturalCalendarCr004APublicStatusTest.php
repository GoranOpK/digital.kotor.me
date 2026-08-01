<?php

namespace Tests\Feature;

use App\Models\CulturalEvent;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalCalendarCr004APublicStatusTest extends TestCase
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

    private function makeEvent(array $overrides = []): CulturalEvent
    {
        return CulturalEvent::create(array_merge([
            'naslov' => 'Badge događaj',
            'opis' => 'Opis',
            'datum_od' => '2026-08-10',
            'datum_do' => null,
            'vrijeme' => null,
            'vrijeme_do' => null,
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides));
    }

    public function test_badge_on_home_featured_upcoming_and_selected_day(): void
    {
        $this->makeEvent([
            'naslov' => 'Istaknuti badge',
            'featured' => true,
            'datum_od' => '2026-08-10',
        ]);
        $this->makeEvent([
            'naslov' => 'Naredni badge',
            'datum_od' => '2026-08-15',
        ]);

        $home = $this->asUser()->get(route('cultural-calendar.index'));
        $home->assertOk();
        $home->assertSee('Istaknuti badge', false);
        $home->assertSee('U toku', false);
        $home->assertSee('Naredni badge', false);
        $home->assertSee('Predstoji', false);
        $home->assertSee('kk-public-status-badge', false);
        $home->assertDontSee('>published<', false);
        $home->assertDontSee('Odgođen', false);

        $day = $this->asUser()->get(route('cultural-calendar.index', [
            'month' => '2026-08',
            'date' => '2026-08-10',
        ]));
        $day->assertOk();
        $day->assertSee('Istaknuti badge', false);
        $day->assertSee('U toku', false);
    }

    public function test_badge_on_events_list_and_archive(): void
    {
        $this->makeEvent([
            'naslov' => 'Lista badge',
            'datum_od' => '2026-08-20',
        ]);
        $this->makeEvent([
            'naslov' => 'Arhiva badge',
            'datum_od' => '2026-08-01',
        ]);

        $events = $this->asUser()->get(route('cultural-calendar.events'));
        $events->assertOk();
        $events->assertSee('Lista badge', false);
        $events->assertSee('Predstoji', false);
        $events->assertSee('kk-status-upcoming', false);

        $archive = $this->asUser()->get(route('cultural-calendar.archive'));
        $archive->assertOk();
        $archive->assertSee('Arhiva badge', false);
        $archive->assertSee('Završen', false);
        $archive->assertSee('kk-status-finished', false);
        $archive->assertDontSee('archived', false);
    }

    public function test_badge_on_event_details_below_title(): void
    {
        $event = $this->makeEvent([
            'naslov' => 'Detalj badge',
            'datum_od' => '2026-08-10',
            'vrijeme' => '10:00:00',
            'vrijeme_do' => '14:00:00',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.show', $event));
        $response->assertOk();
        $response->assertSee('Detalj badge', false);
        $response->assertSee('U toku', false);
        $response->assertSee('kk-public-status-badge--detail', false);

        $html = $response->getContent();
        $titlePos = strpos($html, 'Detalj badge');
        $badgePos = strpos($html, 'kk-public-status-badge--detail');
        $datePos = strpos($html, '<strong>Datum:</strong>');

        $this->assertNotFalse($titlePos);
        $this->assertNotFalse($badgePos);
        $this->assertNotFalse($datePos);
        $this->assertTrue($titlePos < $badgePos);
        $this->assertTrue($badgePos < $datePos);
    }

    public function test_unsafe_event_has_no_badge_on_public_list(): void
    {
        $this->makeEvent([
            'naslov' => 'Nesiguran badge',
            'datum_od' => '2026-08-20',
            'vrijeme' => null,
            'vrijeme_do' => '18:00:00',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.events'));
        $response->assertOk();
        $response->assertSee('Nesiguran badge', false);
        $response->assertDontSee('Unknown', false);
        $this->assertDoesNotMatchRegularExpression(
            '/<span[^>]*class="[^"]*kk-public-status-badge/',
            $response->getContent()
        );
    }

    public function test_day_view_does_not_receive_cr004a_badge_requirement_change(): void
    {
        $this->makeEvent([
            'naslov' => 'Dan view događaj',
            'datum_od' => '2026-08-10',
        ]);

        $response = $this->asUser()->get(route('cultural-calendar.day', ['date' => '2026-08-10']));
        $response->assertOk();
        $response->assertSee('Dan view događaj', false);
        $response->assertDontSee('kk-public-status-badge', false);
    }
}
