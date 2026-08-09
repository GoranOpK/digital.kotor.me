<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-11 — badge SSOT na canonical Pretrazi / naslovnoj / detalju.
 */
class CulturalPublicStatusBadgeSurfacesTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_canonical_events_search_shows_upcoming_badge(): void
    {
        $entry = $this->makeEntry('Badge Pretraga Predstoji');
        $this->makeOcc($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['q' => 'Badge Pretraga Predstoji']))
            ->assertOk()
            ->assertSee('Predstoji', false)
            ->assertSee('kk-status-upcoming', false);
    }

    public function test_canonical_index_featured_shows_ongoing_badge(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', config('app.timezone')));

        $entry = $this->makeEntry('Badge Naslovna U toku', [
            'featured' => true,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('Badge Naslovna U toku', false)
            ->assertSee('U toku', false)
            ->assertSee('kk-status-ongoing', false);
    }

    public function test_canonical_show_entry_badge_independent_of_occ_labels(): void
    {
        $entry = $this->makeEntry('Badge Detalj Mix');
        $this->makeOcc($entry, [
            'datum' => '2026-08-05',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
            'location_manual_name' => 'Odgođen termin',
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
            'location_manual_name' => 'Budući termin',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id));

        $response->assertOk();
        $response->assertSee('Predstoji', false);
        $response->assertSee('kk-status-upcoming', false);
        $response->assertSee('Odgođeno', false);
        $response->assertSee('Odgođen termin', false);
        $response->assertSee('Budući termin', false);
        $response->assertSee('kk-public-status-badge--detail kk-status-upcoming', false);
    }

    public function test_canonical_show_cancelled_entry_badge(): void
    {
        $entry = $this->makeEntry('Badge Detalj Otkazan', [
            'status' => CulturalEventEntry::STATUS_CANCELLED,
        ]);
        $this->makeOcc($entry, [
            'datum' => '2026-08-20',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Otkazan', false)
            ->assertSee('kk-status-cancelled', false);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeEntry(string $naslov, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'status' => CulturalEventEntry::STATUS_PUBLISHED,
            'created_by' => $this->user->id,
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOcc(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }
}
