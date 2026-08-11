<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicSearchHit;
use App\Services\CulturalCalendar\CulturalPublicSearchQuery;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PHASE 6B-04 — Tip sadržaja + combined Pretraga (PO-6B-01/04/05/10).
 */
class CulturalPublicContentTypeSearchTest extends TestCase
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

    public function test_tip_url_contract_and_ui_options(): void
    {
        $this->makePublishedEvent('EVENT_SVE_ONLY', '2026-08-20');
        $this->makePublishedManifestation('MF_SVE_ONLY', '2026-08-22');

        $sve = $this->actingAs($this->user)->get(route('cultural-calendar.events'));
        $sve->assertOk();
        $sve->assertSee('id="kk-filter-tip"', false);
        $sve->assertSee('>Sve</option>', false);
        $sve->assertSee('value="dogadjaji"', false);
        $sve->assertSee('value="manifestacije"', false);
        $sve->assertSee('EVENT_SVE_ONLY', false);
        $sve->assertSee('MF_SVE_ONLY', false);
        $sve->assertDontSee('kk-filter-category', false);

        $eventsOnly = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));
        $eventsOnly->assertOk();
        $eventsOnly->assertSee('EVENT_SVE_ONLY', false);
        $eventsOnly->assertDontSee('MF_SVE_ONLY', false);
        $eventsOnly->assertSee('kk-filter-category', false);
        $eventsOnly->assertSee('kk-filter-location', false);

        $mfOnly = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'manifestacije']));
        $mfOnly->assertOk();
        $mfOnly->assertSee('MF_SVE_ONLY', false);
        $mfOnly->assertDontSee('EVENT_SVE_ONLY', false);
        $mfOnly->assertDontSee('kk-filter-category', false);

        $invalid = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'xyz']));
        $invalid->assertOk();
        $invalid->assertSee('EVENT_SVE_ONLY', false);
        $invalid->assertSee('MF_SVE_ONLY', false);
    }

    public function test_non_applicable_event_filters_ignored_for_sve_and_manifestacije(): void
    {
        $cat = CulturalCategory::create([
            'naziv' => 'FILTER_CAT_X',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $event = $this->makePublishedEvent('EVENT_CAT_IGNORED', '2026-08-20', [
            'category_id' => $cat->id,
        ]);
        $this->makePublishedEvent('EVENT_OTHER_DAY', '2026-09-01');
        $this->makePublishedManifestation('MF_WITH_FILTERS_IGNORED', '2026-08-25');

        $sve = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'category' => 'FILTER_CAT_X',
            'location' => 'Nepostojeca',
            'date' => '2026-08-20',
            'month' => '2026-08',
        ]));
        $sve->assertOk();
        $sve->assertSee('EVENT_CAT_IGNORED', false);
        $sve->assertSee('EVENT_OTHER_DAY', false);
        $sve->assertSee('MF_WITH_FILTERS_IGNORED', false);

        $mf = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'category' => 'FILTER_CAT_X',
            'location' => 'Palata',
            'date' => '2026-08-20',
            'week_start' => '2026-08-10',
            'week_end' => '2026-08-16',
            'month' => '2026-08',
        ]));
        $mf->assertOk();
        $mf->assertSee('MF_WITH_FILTERS_IGNORED', false);
        $mf->assertDontSee($event->naslov, false);
    }

    public function test_mf_q_matches_naziv_and_opis_only(): void
    {
        $this->makePublishedManifestation('MF_NAZIV_MATCH', '2026-08-20', [
            'opis' => 'Opis bez kljuca',
        ]);
        $this->makePublishedManifestation('Druga MF', '2026-08-21', [
            'opis' => 'Opis sadrzi UNIQUEOPISXYZ',
        ]);
        $nullOpis = $this->makePublishedManifestation('NULL_OPIS_MATCH_TOKEN', '2026-08-22', [
            'opis' => null,
        ]);

        $organizer = $this->makeOrganizer('ORG_SHOULD_NOT_MATCH_Q');
        $this->makePublishedManifestation('MF_ORG_ONLY', '2026-08-23', [
            'organizer_id' => $organizer->id,
            'opis' => 'nista',
        ]);

        $linked = $this->makePublishedManifestation('MF_LINKED_EVENT', '2026-08-24');
        $secretEvent = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'SECRET_EVENT_TITLE_Q', [
            'manifestation_id' => $linked->id,
        ]);
        $this->makeOccurrence($secretEvent, [
            'datum' => '2026-08-24',
            'location_manual_name' => 'SECRET_LOC_Q',
        ]);

        $byNaziv = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => 'naziv_match',
        ]));
        $byNaziv->assertOk();
        $byNaziv->assertSee('MF_NAZIV_MATCH', false);
        $byNaziv->assertDontSee('Druga MF', false);

        $byOpis = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => 'uniqueopisxyz',
        ]));
        $byOpis->assertOk();
        $byOpis->assertSee('Druga MF', false);
        $byOpis->assertDontSee('MF_NAZIV_MATCH', false);

        $nullOpisHit = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => 'NULL_OPIS_MATCH_TOKEN',
        ]));
        $nullOpisHit->assertOk();
        $nullOpisHit->assertSee($nullOpis->naziv, false);

        $anti = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => 'SECRET_EVENT_TITLE_Q',
        ]));
        $anti->assertOk();
        $anti->assertDontSee('MF_LINKED_EVENT', false);
        $anti->assertDontSee('Detalji manifestacije', false);

        $antiOrg = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => 'ORG_SHOULD_NOT_MATCH_Q',
        ]));
        $antiOrg->assertOk();
        $antiOrg->assertDontSee('MF_ORG_ONLY', false);

        $antiLoc = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => 'SECRET_LOC_Q',
        ]));
        $antiLoc->assertOk();
        $antiLoc->assertDontSee('MF_LINKED_EVENT', false);

        $blank = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'manifestacije',
            'q' => '   ',
        ]));
        $blank->assertOk();
        $blank->assertSee('MF_NAZIV_MATCH', false);
        $blank->assertSee('Druga MF', false);
    }

    public function test_sve_q_applies_per_subset_rules(): void
    {
        $this->makePublishedEvent('EVENT_SHARED_TOKEN_AAA', '2026-08-20');
        $this->makePublishedManifestation('MF_SHARED_TOKEN_AAA', '2026-08-21');
        $this->makePublishedEvent('EVENT_ONLY_BBB', '2026-08-22');
        $this->makePublishedManifestation('MF_ONLY_CCC', '2026-08-23');

        $both = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'q' => 'SHARED_TOKEN_AAA',
        ]));
        $both->assertOk();
        $both->assertSee('EVENT_SHARED_TOKEN_AAA', false);
        $both->assertSee('MF_SHARED_TOKEN_AAA', false);
        $both->assertDontSee('EVENT_ONLY_BBB', false);
        $both->assertDontSee('MF_ONLY_CCC', false);

        $eventOnly = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'q' => 'EVENT_ONLY_BBB',
        ]));
        $eventOnly->assertOk();
        $eventOnly->assertSee('EVENT_ONLY_BBB', false);
        $eventOnly->assertDontSee('MF_ONLY_CCC', false);

        $mfOnly = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'q' => 'MF_ONLY_CCC',
        ]));
        $mfOnly->assertOk();
        $mfOnly->assertSee('MF_ONLY_CCC', false);
        $mfOnly->assertDontSee('EVENT_ONLY_BBB', false);
    }

    public function test_po_6b_10_global_ordering_scenarios(): void
    {
        $eventEarly = $this->makePublishedEvent('Zebra Event', '2026-08-10');
        $mfLate = $this->makePublishedManifestation('Alpha MF', '2026-08-12');
        $mfEarly = $this->makePublishedManifestation('Zebra MF', '2026-08-10');
        $eventLate = $this->makePublishedEvent('Alpha Event', '2026-08-12');

        $sameDayA = $this->makePublishedEvent('SameName', '2026-08-15');
        $sameDayB = $this->makePublishedManifestation('SameName', '2026-08-15');

        $titles = app(CulturalPublicSearchQuery::class)
            ->paginateCombined(null, 50, 1)
            ->getCollection()
            ->map(fn (CulturalPublicSearchHit $hit): string => $hit->type.':'.$hit->title.':'.($hit->temporalKey ?? 'null'))
            ->values()
            ->all();

        $idx = fn (string $needle): int => collect($titles)->search(fn (string $row): bool => str_contains($row, $needle));

        $this->assertTrue(
            $idx('event:Zebra Event:') < $idx('manifestation:Alpha MF:'),
            'Order dump: '.implode(' | ', $titles)
        );
        $this->assertTrue($idx('manifestation:Zebra MF:') < $idx('event:Alpha Event:'));
        $this->assertTrue($idx('event:Zebra Event:') < $idx('event:Alpha Event:'));

        // Same temporal + same naziv → type tie-breaker (event before manifestation).
        $this->assertTrue($idx('event:SameName:') < $idx('manifestation:SameName:'));

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Zebra Event', $html);
        $this->assertStringContainsString('Alpha MF', $html);
    }

    public function test_combined_pagination_preserves_global_order(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->makePublishedEvent(sprintf('PAG_EVENT_%02d', $i), sprintf('2026-09-%02d', $i));
            $this->makePublishedManifestation(sprintf('PAG_MF_%02d', $i), sprintf('2026-09-%02d', $i));
        }

        $page1 = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['page' => 1]));
        $page1->assertOk();
        $page1->assertSee('PAG_EVENT_01', false);
        $page1->assertSee('PAG_MF_01', false);
        $page1->assertDontSee('PAG_EVENT_08', false);

        $page2 = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['page' => 2]));
        $page2->assertOk();
        $page2->assertSee('PAG_EVENT_08', false);
        $page2->assertSee('PAG_MF_08', false);
        $page2->assertDontSee('PAG_EVENT_01', false);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = app(CulturalPublicSearchQuery::class)->paginateCombined(null, 12, 1);
        // 8 standalone events + 8 MF + 8 program events linked to MF = 24
        $this->assertSame(24, $paginator->total());
        $this->assertCount(12, $paginator->items());

        $titles = collect($paginator->items())
            ->map(fn (CulturalPublicSearchHit $hit): string => $hit->title)
            ->all();
        $this->assertSame('PAG_EVENT_01', $titles[0]);
        $this->assertContains('PAG_MF_01', $titles);
        $this->assertNotContains('PAG_EVENT_08', $titles);
    }

    public function test_visibility_matrix_for_combined_search(): void
    {
        $this->makePublishedEvent('PUB_EVENT_OK', '2026-08-20');
        $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'DRAFT_EVENT_LEAK');

        $this->makePublishedManifestation('PUB_MF_OK', '2026-08-21');
        $cancelled = $this->makePublishedManifestation('CANCEL_MF_OK', '2026-08-22');
        $cancelled->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        $expired = $this->makePublishedManifestation('EXPIRED_CANCEL_MF', '2026-07-01');
        $expired->update([
            'status' => CulturalManifestation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        $this->makeManifestation(CulturalManifestation::STATUS_DRAFT, 'DRAFT_MF_LEAK');
        $this->makeManifestation(CulturalManifestation::STATUS_PENDING_APPROVAL, 'PENDING_MF_LEAK');
        $archived = $this->makePublishedManifestation('ARCHIVED_MF_LEAK', '2026-07-05');
        $archived->update([
            'status' => CulturalManifestation::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events'));
        $response->assertOk();
        $response->assertSee('PUB_EVENT_OK', false);
        $response->assertSee('PUB_MF_OK', false);
        $response->assertSee('CANCEL_MF_OK', false);
        $response->assertSee('Otkazana', false);
        $response->assertDontSee('Odgođena', false);
        $response->assertDontSee('DRAFT_EVENT_LEAK', false);
        $response->assertDontSee('DRAFT_MF_LEAK', false);
        $response->assertDontSee('PENDING_MF_LEAK', false);
        $response->assertDontSee('EXPIRED_CANCEL_MF', false);
        $response->assertDontSee('ARCHIVED_MF_LEAK', false);
    }

    public function test_combined_search_period_queries_do_not_scale_with_result_count(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->makePublishedEvent('PERF_E_'.$i, sprintf('2026-09-%02d', $i));
            $this->makePublishedManifestation('PERF_M_'.$i, sprintf('2026-09-%02d', $i));
        }

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

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertSee('PERF_E_1', false)
            ->assertSee('PERF_M_1', false);

        $this->assertLessThanOrEqual(
            3,
            $occurrenceJoinQueries,
            "Expected bounded OCC join queries for combined search, got {$occurrenceJoinQueries}"
        );
    }

    public function test_homepage_still_has_no_manifestation_cards(): void
    {
        $this->makePublishedManifestation('HOME_MF_SHOULD_NOT_APPEAR', '2026-08-20');

        $home = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $home->assertOk();
        $home->assertDontSee('HOME_MF_SHOULD_NOT_APPEAR', false);
        $home->assertDontSee('Detalji manifestacije', false);
        $home->assertSee('tip=dogadjaji', false);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedEvent(string $naslov, string $date, array $extra = []): CulturalEventEntry
    {
        $event = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, $naslov, $extra);
        $this->makeOccurrence($event, [
            'datum' => $date,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        return $event->fresh();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedManifestation(string $naziv, string $date, array $extra = []): CulturalManifestation
    {
        $mf = $this->makeManifestation(CulturalManifestation::STATUS_PUBLISHED, $naziv, $extra);
        $event = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Programski dogadjaj #'.$mf->id, [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($event, [
            'datum' => $date,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        return $mf->fresh();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeManifestation(string $status, string $naziv, array $extra = []): CulturalManifestation
    {
        return CulturalManifestation::create(array_merge([
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
        ], $extra));
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
