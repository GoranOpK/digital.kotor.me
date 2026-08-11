<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PHASE 6B-03 — javni portal Manifestacija (PO-6B-08 / PO-6B-09).
 */
class CulturalPublicManifestationPortalTest extends TestCase
{
    use RefreshDatabase;

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
        $this->creator = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_published_manifestation_appears_on_active_list(): void
    {
        $mf = $this->makePublishedManifestation('PUBLISHED_MF_LIST', '2026-09-01');

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestations'));

        $response->assertOk();
        $response->assertSee('PUBLISHED_MF_LIST', false);
        $response->assertSee('Detalji manifestacije', false);
        $response->assertSee(route('cultural-calendar.manifestation', $mf), false);
    }

    public function test_cancelled_non_expired_manifestation_appears_with_badge(): void
    {
        $mf = $this->makePublishedManifestation('CANCELLED_MF_LIST', '2026-09-20');
        $mf->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestations'));

        $response->assertOk();
        $response->assertSee('CANCELLED_MF_LIST', false);
        $response->assertSee('Otkazana', false);
        $response->assertDontSee('Odgođena', false);
    }

    public function test_private_and_archived_manifestations_absent_from_active_list(): void
    {
        $this->makeManifestation(CulturalManifestation::STATUS_DRAFT, 'DRAFT_MF_SECRET');
        $this->makeManifestation(CulturalManifestation::STATUS_PENDING_APPROVAL, 'PENDING_MF_SECRET');
        $this->makeManifestation(CulturalManifestation::STATUS_RETURNED_FOR_REVISION, 'RETURNED_MF_SECRET');
        $archived = $this->makePublishedManifestation('ARCHIVED_MF_LIST', '2026-07-01');
        $archived->update([
            'status' => CulturalManifestation::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestations'));

        $response->assertOk();
        $response->assertDontSee('DRAFT_MF_SECRET', false);
        $response->assertDontSee('PENDING_MF_SECRET', false);
        $response->assertDontSee('RETURNED_MF_SECRET', false);
        $response->assertDontSee('ARCHIVED_MF_LIST', false);
        $response->assertSee('Trenutno nema objavljenih manifestacija.', false);
    }

    public function test_expired_cancelled_manifestation_hidden_from_active_list(): void
    {
        $mf = $this->makePublishedManifestation('EXPIRED_CANCELLED_MF', '2026-07-01');
        $mf->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestations'));

        $response->assertOk();
        $response->assertDontSee('EXPIRED_CANCELLED_MF', false);
    }

    public function test_list_orders_by_period_start_then_name(): void
    {
        $this->makePublishedManifestation('MF_B_LATER', '2026-09-10');
        $this->makePublishedManifestation('MF_A_EARLIER', '2026-09-01');
        $this->makePublishedManifestation('MF_C_SAME_DAY', '2026-09-01');

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestations'));

        $response->assertOk();
        $html = $response->getContent();
        $posA = strpos($html, 'MF_A_EARLIER');
        $posC = strpos($html, 'MF_C_SAME_DAY');
        $posB = strpos($html, 'MF_B_LATER');

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posC);
        $this->assertNotFalse($posB);
        $this->assertTrue($posA < $posC);
        $this->assertTrue($posC < $posB);
    }

    public function test_public_detail_statuses(): void
    {
        $published = $this->makePublishedManifestation('DETAIL_PUBLISHED', '2026-09-01');
        $cancelled = $this->makePublishedManifestation('DETAIL_CANCELLED', '2026-09-15');
        $cancelled->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        $archived = $this->makePublishedManifestation('DETAIL_ARCHIVED', '2026-07-01');
        $archived->update([
            'status' => CulturalManifestation::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestation', $published))
            ->assertOk()
            ->assertSee('DETAIL_PUBLISHED', false)
            ->assertSee('Program', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestation', $cancelled))
            ->assertOk()
            ->assertSee('DETAIL_CANCELLED', false)
            ->assertSee('Otkazana', false)
            ->assertDontSee('Odgođena', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestation', $archived))
            ->assertOk()
            ->assertSee('DETAIL_ARCHIVED', false)
            ->assertSee('Arhivirana', false);

        foreach ([
            $this->makeManifestation(CulturalManifestation::STATUS_DRAFT, 'DETAIL_DRAFT'),
            $this->makeManifestation(CulturalManifestation::STATUS_PENDING_APPROVAL, 'DETAIL_PENDING'),
            $this->makeManifestation(CulturalManifestation::STATUS_RETURNED_FOR_REVISION, 'DETAIL_RETURNED'),
        ] as $private) {
            $this->actingAs($this->user)
                ->get(route('cultural-calendar.manifestation', $private))
                ->assertNotFound();
        }
    }

    public function test_detail_shows_organizer_web_period_and_no_location_block(): void
    {
        $org = $this->makeOrganizer('Org Public MF');
        $mf = $this->makePublishedManifestation('MF_DETAIL_FIELDS', '2026-09-05');
        $mf->update([
            'opis' => 'Opis manifestacije za javni portal.',
            'organizer_id' => $org->id,
            'web_stranica' => 'https://example.test/mf',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestation', $mf));

        $response->assertOk();
        $response->assertSee('MF_DETAIL_FIELDS', false);
        $response->assertSee('Opis manifestacije za javni portal.', false);
        $response->assertSee('Org Public MF', false);
        $response->assertSee('https://example.test/mf', false);
        $response->assertSee('05.09.2026', false);
        $response->assertDontSee('<strong>Lokacija:</strong> Org Public MF', false);
        $html = $response->getContent();
        $this->assertStringNotContainsString('Lokacija Manifestacije', $html);
    }

    public function test_program_shows_public_events_hides_draft_and_preserves_occ_statuses(): void
    {
        $mf = $this->makeManifestation(CulturalManifestation::STATUS_PUBLISHED, 'MF_PROGRAM');
        $public = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'PUBLIC_EVENT_IN_PROGRAM', [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($public, [
            'datum' => '2026-09-02',
            'vrijeme_od' => '18:00:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($public, [
            'datum' => '2026-09-03',
            'vrijeme_od' => '19:00:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);
        $this->makeOccurrence($public, [
            'datum' => '2026-09-04',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'PRIVATE_DRAFT_EVENT_SECRET', [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($draft, ['datum' => '2026-09-01']);

        $cancelledEvent = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'CANCELLED_EVENT_IN_PROGRAM', [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($cancelledEvent, [
            'datum' => '2026-09-01',
            'vrijeme_od' => '10:00:00',
            'cjelodnevno' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestation', $mf));

        $response->assertOk();
        $response->assertSee('PUBLIC_EVENT_IN_PROGRAM', false);
        $response->assertSee('CANCELLED_EVENT_IN_PROGRAM', false);
        $response->assertSee('Odgođeno', false);
        $response->assertSee('Otkazano', false);
        $response->assertDontSee('PRIVATE_DRAFT_EVENT_SECRET', false);
        $response->assertSee(route('cultural-calendar.show', $public), false);
        $response->assertDontSee(route('cultural-calendar.show', $draft), false);
    }

    public function test_empty_program_message(): void
    {
        $mf = $this->makeManifestation(CulturalManifestation::STATUS_PUBLISHED, 'MF_EMPTY_PROGRAM');

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestation', $mf));

        $response->assertOk();
        $response->assertSee('Trenutno nema javno dostupnog programa.', false);
    }

    public function test_event_to_manifestation_visibility_matrix(): void
    {
        $cases = [
            [CulturalManifestation::STATUS_DRAFT, false, 'MF_DRAFT_LEAK'],
            [CulturalManifestation::STATUS_PENDING_APPROVAL, false, 'MF_PENDING_LEAK'],
            [CulturalManifestation::STATUS_RETURNED_FOR_REVISION, false, 'MF_RETURNED_LEAK'],
            [CulturalManifestation::STATUS_PUBLISHED, true, 'MF_PUBLISHED_LINK'],
            [CulturalManifestation::STATUS_CANCELLED, true, 'MF_CANCELLED_LINK'],
            [CulturalManifestation::STATUS_ARCHIVED, true, 'MF_ARCHIVED_LINK'],
        ];

        foreach ($cases as [$status, $shouldSee, $naziv]) {
            $mf = $this->makeManifestation($status, $naziv);
            $event = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'PUBLIC_EVENT_'.$status, [
                'manifestation_id' => $mf->id,
            ]);
            $this->makeOccurrence($event, ['datum' => '2026-09-12']);

            $response = $this->actingAs($this->user)
                ->get(route('cultural-calendar.show', $event));

            $response->assertOk();
            if ($shouldSee) {
                $response->assertSee('Manifestacija:', false);
                $response->assertSee($naziv, false);
                $response->assertSee(route('cultural-calendar.manifestation', $mf), false);
                $response->assertDontSee($naziv.' — Otkazana', false);
                $response->assertDontSee('Otkazana Manifestacija', false);
            } else {
                $response->assertDontSee($naziv, false);
                $response->assertDontSee('Manifestacija:', false);
                $response->assertDontSee('data-kk-public-manifestation-link', false);
            }
        }
    }

    public function test_cancelled_manifestation_does_not_propagate_status_to_event(): void
    {
        $mf = $this->makePublishedManifestation('MF_CANCEL_NO_PROP', '2026-09-18');
        $mf->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        $event = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'EVENT_STILL_PUBLISHED', [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($event, [
            'datum' => '2026-09-18',
            'vrijeme_od' => '20:00:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $event));

        $response->assertOk();
        $response->assertSee('EVENT_STILL_PUBLISHED', false);
        $response->assertSee('Manifestacija:', false);
        $response->assertSee('MF_CANCEL_NO_PROP', false);
        $html = $response->getContent();
        $this->assertStringNotContainsString('MF_CANCEL_NO_PROP — Otkazana', $html);
        $this->assertStringNotContainsString('Ovaj događaj je otkazan', $html);
    }

    public function test_navigation_contains_manifestations_link(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('Manifestacije', false);
        $response->assertSee(route('cultural-calendar.manifestations'), false);
    }

    public function test_search_tip_and_homepage_mf_remain_out_of_scope(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'manifestacije']));

        $response->assertOk();
        $response->assertSee('name="tip"', false);
        $response->assertDontSee('kk-filter-category', false);
        $response->assertDontSee('kk-filter-location', false);

        $home = $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'));
        $home->assertOk();
        $home->assertDontSee('Detalji manifestacije', false);
    }

    /**
     * 6B-03A: list must not issue per-MF period OCC queries (card + cancelled expiry).
     */
    public function test_active_list_period_queries_do_not_scale_with_manifestation_count(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->makePublishedManifestation('PERF_MF_'.$i, sprintf('2026-09-%02d', $i));
        }

        $activeCancelled = $this->makePublishedManifestation('PERF_CANCEL_ACTIVE', '2026-09-25');
        $activeCancelled->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $expiredCancelled = $this->makePublishedManifestation('PERF_CANCEL_EXPIRED', '2026-07-01');
        $expiredCancelled->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $occurrenceJoinQueries = 0;
        DB::listen(function ($query) use (&$occurrenceJoinQueries): void {
            $sql = strtolower($query->sql);
            if (
                str_contains($sql, 'from `cultural_occurrences`')
                && str_contains($sql, 'cultural_event_entries')
                && str_contains($sql, 'manifestation_id')
            ) {
                $occurrenceJoinQueries++;
            }
        });

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.manifestations'));

        $response->assertOk();
        $response->assertSee('PERF_MF_1', false);
        $response->assertSee('PERF_MF_10', false);
        $response->assertSee('PERF_CANCEL_ACTIVE', false);
        $response->assertDontSee('PERF_CANCEL_EXPIRED', false);

        // Main list SELECT (with correlated MIN/MAX subqueries) + at most one
        // batched cancelled-expiry OCC load — must not grow ~1 per MF.
        $this->assertLessThanOrEqual(
            2,
            $occurrenceJoinQueries,
            "Expected <=2 OCC join queries for list of 10+ MF, got {$occurrenceJoinQueries}"
        );
    }

    private function makePublishedManifestation(string $naziv, string $date): CulturalManifestation
    {
        $mf = $this->makeManifestation(CulturalManifestation::STATUS_PUBLISHED, $naziv);
        $event = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Event for '.$naziv, [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($event, [
            'datum' => $date,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        return $mf->fresh();
    }

    private function makeManifestation(string $status, string $naziv): CulturalManifestation
    {
        return CulturalManifestation::create([
            'naziv' => $naziv,
            'opis' => null,
            'status' => $status,
            'created_by' => $this->creator->id,
            'published_at' => in_array($status, [
                CulturalManifestation::STATUS_PUBLISHED,
                CulturalManifestation::STATUS_CANCELLED,
                CulturalManifestation::STATUS_ARCHIVED,
            ], true) ? now() : null,
            'cancelled_at' => $status === CulturalManifestation::STATUS_CANCELLED ? now() : null,
            'archived_at' => $status === CulturalManifestation::STATUS_ARCHIVED ? now() : null,
        ]);
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

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->creator->id,
            'proposed_moderator_user_id' => $this->creator->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->creator->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
