<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH-063 Phase 6 — javni portal: ručni Org / Prvobitni termin / opcione napomene.
 */
class CulturalPatch063Phase6PublicDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const CANCELLED_NOTICE = 'Ovaj događaj je otkazan i neće biti održan u planiranom terminu.';

    private User $user;

    private User $editor;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    private CulturalPublicEventQuery $publicQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->publicQuery = app(CulturalPublicEventQuery::class);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_manual_organizer_shown_on_detail_and_escaped(): void
    {
        $entry = $this->makePublishedDirect('Manual Org Show', [
            'organizer_manual_name' => 'KUD <script>alert(1)</script> Perast',
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Organizator:', false)
            ->assertSee('KUD &lt;script&gt;alert(1)&lt;/script&gt; Perast', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->getContent();

        $this->assertStringNotContainsString('ručni organizator', mb_strtolower($html));
        $this->assertStringNotContainsString('neregistrovani', mb_strtolower($html));
    }

    public function test_without_organizer_hides_empty_section(): void
    {
        $entry = $this->makePublishedDirect('Bez Org');

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertDontSee('Organizator:', false);
    }

    public function test_registered_organizer_preferred_over_manual_corrupt_data(): void
    {
        $organizer = $this->makeOrganizer('Registrovani Teatar');
        $entry = $this->makePublishedWithOrganizer('Sa Org', $organizer);
        // Fail-safe: korumpirani dual zapis — registered ima prioritet.
        $entry->forceFill(['organizer_manual_name' => 'Ručni duplikat'])->save();

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk();

        $response->assertSee('Organizator:', false);
        $response->assertSee('Registrovani Teatar', false);
        $response->assertDontSee('Ručni duplikat', false);
    }

    public function test_postponed_shows_original_date_optional_note_and_not_upcoming(): void
    {
        $entry = $this->makePublishedDirect('Odgođen Detail');
        $occurrence = $entry->occurrences()->firstOrFail();
        $originalDate = $occurrence->datum?->toDateString();

        $this->occurrenceLifecycle->postpone($occurrence, '  Kiša  ');

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk();

        $response->assertSee('Odgođeno', false);
        $response->assertSee('Prvobitni termin:', false);
        $response->assertSee(Carbon::parse((string) $originalDate)->format('d.m.Y'), false);
        $response->assertSee('Napomena:', false);
        $response->assertSee('Kiša', false);

        $withoutReason = $this->makePublishedDirect('Odgođen bez napomene');
        $this->occurrenceLifecycle->postpone($withoutReason->occurrences()->firstOrFail());

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $withoutReason->id))
            ->assertOk()
            ->assertSee('Odgođeno', false)
            ->assertSee('Prvobitni termin:', false)
            ->getContent();

        $this->assertStringNotContainsString('Napomena:', $html);

        $upcomingTitles = $this->publicQuery->upcomingForPublicIndex()->pluck('naslov')->all();
        $this->assertNotContains('Odgođen Detail', $upcomingTitles);
        $this->assertNotContains('Odgođen bez napomene', $upcomingTitles);
        $this->assertNull($entry->fresh(['occurrences'])->nextRelevantOccurrence());
    }

    public function test_resume_shows_planned_without_postponed_block(): void
    {
        $entry = $this->makePublishedDirect('Resume public');
        $occurrence = $entry->occurrences()->firstOrFail();
        $occurrenceId = $occurrence->id;
        $this->occurrenceLifecycle->postpone($occurrence, 'Privremeno');

        $newDate = '2026-09-01';
        $this->occurrenceLifecycle->resumeWithNewTermin($occurrence->fresh(), [
            'datum' => $newDate,
            'cjelodnevno' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk();

        $response->assertSee('01.09.2026', false);
        $response->assertDontSee('Odgođeno', false);
        $response->assertDontSee('Prvobitni termin:', false);
        $response->assertDontSee('Privremeno', false);
        $this->assertSame($occurrenceId, $occurrence->fresh()->id);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_occ_cancel_shows_note_keeps_entry_published_and_sibling_active(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Dva termina',
            'category_id' => $this->category->id,
        ]);
        $first = $this->occurrenceWriter->create($entry, [
            'datum' => '2026-08-12',
            'cjelodnevno' => true,
        ]);
        $second = $this->occurrenceWriter->create($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
        ]);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        $this->occurrenceLifecycle->cancel($first->fresh(), '  Nema prostora  ');

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk();

        $response->assertSee('Otkazano', false);
        $response->assertSee(Carbon::parse((string) $first->datum)->format('d.m.Y'), false);
        $response->assertSee('Napomena:', false);
        $response->assertSee('Nema prostora', false);
        $response->assertSee('20.08.2026', false);
        $response->assertDontSee(self::CANCELLED_NOTICE, false);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $second->fresh()->status);

        $next = $entry->fresh(['occurrences'])->nextRelevantOccurrence();
        $this->assertNotNull($next);
        $this->assertSame($second->id, $next->id);
    }

    public function test_entry_cancel_optional_reason_and_historical_content(): void
    {
        $withReason = $this->makePublishedDirect('Otkazan sa razlogom', [
            'opis' => 'Istorijski opis ostaje',
            'organizer_manual_name' => 'KUD Stari',
        ]);
        $this->eventLifecycle->cancel($withReason, $this->editor, '  Program povučen  ');

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $withReason->id))
            ->assertOk();

        $response->assertSee('Otkazan', false);
        $response->assertSee(self::CANCELLED_NOTICE, false);
        $response->assertSee('Napomena:', false);
        $response->assertSee('Program povučen', false);
        $response->assertSee('Istorijski opis ostaje', false);
        $response->assertSee('Organizator:', false);
        $response->assertSee('KUD Stari', false);

        $withoutReason = $this->makePublishedDirect('Otkazan bez razloga');
        $this->eventLifecycle->cancel($withoutReason, $this->editor, null);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $withoutReason->id))
            ->assertOk()
            ->assertSee(self::CANCELLED_NOTICE, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, self::CANCELLED_NOTICE));
        $this->assertStringNotContainsString('Napomena:', $html);

        $upcoming = $this->publicQuery->upcomingForPublicIndex()->pluck('naslov')->all();
        $this->assertNotContains('Otkazan sa razlogom', $upcoming);
        $this->assertNotContains('Otkazan bez razloga', $upcoming);
    }

    public function test_calendar_and_date_filter_skip_postponed_and_published_cancelled_occ(): void
    {
        $planned = $this->makePublishedDirect('Planiran dan', [], '2026-08-15');
        $postponed = $this->makePublishedDirect('Odgođen dan', [], '2026-08-15');
        $this->occurrenceLifecycle->postpone($postponed->occurrences()->firstOrFail());

        $publishedCancelledOcc = $this->makePublishedDirect('OCC cancel dan', [], '2026-08-15');
        $this->occurrenceLifecycle->cancel($publishedCancelledOcc->occurrences()->firstOrFail());

        $entryCancelled = $this->makePublishedDirect('Entry cancel dan', [], '2026-08-15');
        $this->eventLifecycle->cancel($entryCancelled, $this->editor, null);

        $dateIds = $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all();
        $this->assertContains($planned->id, $dateIds);
        $this->assertNotContains($postponed->id, $dateIds);
        $this->assertNotContains($publishedCancelledOcc->id, $dateIds);
        $this->assertContains($entryCancelled->id, $dateIds);

        $counts = $this->publicQuery->distinctPublicEntryCountsByOccurrenceDate('2026-08-15', '2026-08-15');
        $this->assertSame(2, $counts['2026-08-15'] ?? 0);

        $index = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $days = collect($index->viewData('calendarDays'))->keyBy('date');
        $this->assertSame(2, $days['2026-08-15']['event_count'] ?? null);
    }

    public function test_events_card_does_not_add_organizer_field(): void
    {
        $entry = $this->makePublishedDirect('Kartica Org', [
            'organizer_manual_name' => 'Ne na kartici',
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertSee('Kartica Org', false)
            ->getContent();

        $this->assertStringNotContainsString('Organizator:', $html);
        $this->assertStringNotContainsString('Ne na kartici', $html);
        $this->assertNotNull($entry->fresh(['occurrences'])->nextRelevantOccurrence());
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedDirect(string $naslov, array $extra = [], string $datum = '2026-08-18'): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, array_merge([
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ], $extra));
        $this->occurrenceWriter->create($entry, [
            'datum' => $datum,
            'cjelodnevno' => true,
        ]);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }

    private function makePublishedWithOrganizer(string $naslov, CulturalOrganizer $organizer): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => '2026-08-18',
            'cjelodnevno' => true,
        ]);
        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences', 'organizer']);
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
