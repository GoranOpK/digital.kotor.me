<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceGenerator;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PO-N-TR-02-04 / T10-GEN-01 — generator Održavanja (TM-GEN-01…05).
 */
class CulturalOccurrenceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private User $regular;

    private CulturalOrganizer $organizer;

    private CulturalOrganizer $otherOrganizer;

    private CulturalCategory $category;

    private OccurrenceGenerator $generator;

    private OccurrenceWriter $writer;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regular = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->organizer = $this->makeOrganizer('Org Gen');
        $this->otherOrganizer = $this->makeOrganizer('Org Other');
        $this->grantModerator($this->moderator, $this->organizer);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->generator = app(OccurrenceGenerator::class);
        $this->writer = app(OccurrenceWriter::class);
        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
    }

    // ─── TM-GEN-01 ───────────────────────────────────────────────

    public function test_tm_gen_01_daily_weekly_monthly_planned_independent(): void
    {
        $entry = $this->makeDraft();

        $daily = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'count' => 3,
            'cjelodnevno' => true,
        ]));
        $this->assertSame(['2026-08-10', '2026-08-11', '2026-08-12'], $daily->map->datum->map->toDateString()->all());
        $daily->each(fn (CulturalOccurrence $o) => $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $o->status));

        $entry2 = $this->makeDraft('Sedmicni');
        $weekly = $this->generator->generate($entry2, $this->payload([
            'recurrence_type' => 'weekly',
            'start_date' => '2026-08-10',
            'count' => 3,
            'cjelodnevno' => true,
        ]));
        $this->assertSame(['2026-08-10', '2026-08-17', '2026-08-24'], $weekly->map->datum->map->toDateString()->all());

        $entry3 = $this->makeDraft('Mjesecni');
        $monthly = $this->generator->generate($entry3, $this->payload([
            'recurrence_type' => 'monthly',
            'start_date' => '2026-01-15',
            'count' => 3,
            'cjelodnevno' => true,
        ]));
        $this->assertSame(['2026-01-15', '2026-02-15', '2026-03-15'], $monthly->map->datum->map->toDateString()->all());

        $this->assertFalse(Schema::hasColumn('cultural_occurrences', 'series_id'));
        $this->assertFalse(Schema::hasColumn('cultural_occurrences', 'generator_id'));

        $items = $entry3->fresh()->occurrences()->orderBy('id')->get();
        $this->writer->update($items[1], [
            'datum' => '2026-02-20',
            'cjelodnevno' => true,
        ]);
        $this->assertSame('2026-01-15', $items[0]->fresh()->datum->toDateString());
        $this->assertSame('2026-02-20', $items[1]->fresh()->datum->toDateString());
        $this->assertSame('2026-03-15', $items[2]->fresh()->datum->toDateString());
    }

    public function test_tm_gen_01_monthly_preserves_original_day_across_short_months(): void
    {
        $entry = $this->makeDraft();

        $created = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'monthly',
            'start_date' => '2026-01-31',
            'count' => 5,
            'cjelodnevno' => true,
        ]));

        $this->assertSame([
            '2026-01-31',
            '2026-02-28',
            '2026-03-31',
            '2026-04-30',
            '2026-05-31',
        ], $created->map->datum->map->toDateString()->all());

        $leap = $this->makeDraft('Prestupna');
        $leapDates = $this->generator->generate($leap, $this->payload([
            'recurrence_type' => 'monthly',
            'start_date' => '2028-01-31',
            'count' => 3,
            'cjelodnevno' => true,
        ]));
        $this->assertSame(['2028-01-31', '2028-02-29', '2028-03-31'], $leapDates->map->datum->map->toDateString()->all());
    }

    // ─── TM-GEN-02 ───────────────────────────────────────────────

    public function test_tm_gen_02_count_and_end_date_xor(): void
    {
        $entry = $this->makeDraft();

        $byCount = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'count' => 2,
            'cjelodnevno' => true,
        ]));
        $this->assertCount(2, $byCount);

        $entryB = $this->makeDraft('End');
        $byEnd = $this->generator->generate($entryB, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'cjelodnevno' => true,
        ]));
        $this->assertSame(['2026-08-10', '2026-08-11', '2026-08-12'], $byEnd->map->datum->map->toDateString()->all());

        $entryC = $this->makeDraft('One');
        $one = $this->generator->generate($entryC, $this->payload([
            'recurrence_type' => 'weekly',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'cjelodnevno' => true,
        ]));
        $this->assertCount(1, $one);

        try {
            $this->generator->generate($this->makeDraft('Both'), $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 2,
                'end_date' => '2026-08-20',
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected XOR reject for both');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('tačno jedan', $e->getMessage());
        }

        try {
            $this->generator->generate($this->makeDraft('None'), [
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'cjelodnevno' => true,
            ]);
            $this->fail('Expected XOR reject for none');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('tačno jedan', $e->getMessage());
        }

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.generate', $entry), [
                'recurrence_type' => 'daily',
                'start_date' => '2026-09-01',
                'count' => 2,
                'end_date' => '2026-09-10',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('count');
    }

    // ─── TM-GEN-03 ───────────────────────────────────────────────

    public function test_tm_gen_03_max_100_and_no_partial(): void
    {
        $entry = $this->makeDraft();
        $ok = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-01-01',
            'count' => 100,
            'cjelodnevno' => true,
        ]));
        $this->assertCount(100, $ok);

        $before = CulturalOccurrence::query()->count();
        try {
            $this->generator->generate($this->makeDraft('101'), $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-01-01',
                'count' => 101,
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected count 101 reject');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('1 i 100', $e->getMessage());
        }
        $this->assertSame($before, CulturalOccurrence::query()->count());

        $entryOver = $this->makeDraft('EndOver');
        try {
            $this->generator->generate($entryOver, $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-01-01',
                'end_date' => '2026-04-15',
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected end-date over 100 reject');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('najviše 100', $e->getMessage());
        }
        $this->assertSame(0, $entryOver->occurrences()->count());

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.generate', $this->makeDraft('Http101')), [
                'recurrence_type' => 'daily',
                'start_date' => '2026-01-01',
                'count' => 101,
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('count');
    }

    // ─── TM-GEN-04 ───────────────────────────────────────────────

    public function test_tm_gen_04_rejects_unsupported_recurrence(): void
    {
        $entry = $this->makeDraft();

        try {
            $this->generator->generate($entry, $this->payload([
                'recurrence_type' => 'every_2_days',
                'start_date' => '2026-08-10',
                'count' => 3,
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected invalid type');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('dnevno, sedmično ili mjesečno', $e->getMessage());
        }

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.generate', $entry), [
                'recurrence_type' => 'rrule',
                'start_date' => '2026-08-10',
                'count' => 3,
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('recurrence_type');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.generate', $entry), [
                'recurrence_type' => 'interval',
                'start_date' => '2026-08-10',
                'count' => 3,
                'interval' => 2,
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('recurrence_type');

        $this->assertSame(0, $entry->occurrences()->count());
    }

    // ─── TM-GEN-05 ───────────────────────────────────────────────

    public function test_tm_gen_05_manual_edit_does_not_mutate_siblings(): void
    {
        $entry = $this->makeDraft();
        $created = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'count' => 3,
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
        ]));

        $o2 = $created[1];
        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.occurrences.update', [$entry, $o2]), [
                'datum' => '2026-08-11',
                'vrijeme_od' => '21:00',
                'vrijeme_do' => '22:00',
            ])
            ->assertRedirect();

        $this->assertSame('18:00:00', $created[0]->fresh()->vrijeme_od);
        $this->assertSame('21:00:00', $o2->fresh()->vrijeme_od);
        $this->assertSame('18:00:00', $created[2]->fresh()->vrijeme_od);
    }

    // ─── Duplikati ───────────────────────────────────────────────

    public function test_duplicate_existing_rejects_entire_operation(): void
    {
        $entry = $this->makeDraft();
        $this->writer->create($entry, [
            'datum' => '2026-08-11',
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
        ]);

        try {
            $this->generator->generate($entry, $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 3,
                'vrijeme_od' => '18:00',
                'vrijeme_do' => '20:00',
            ]));
            $this->fail('Expected duplicate reject');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('identično postojeće', $e->getMessage());
        }

        $this->assertSame(1, $entry->occurrences()->count());
    }

    public function test_same_date_different_time_allowed(): void
    {
        $entry = $this->makeDraft();
        $this->writer->create($entry, [
            'datum' => '2026-08-10',
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
        ]);

        $created = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'count' => 1,
            'vrijeme_od' => '21:00',
            'vrijeme_do' => '22:00',
        ]));

        $this->assertCount(1, $created);
        $this->assertSame(2, $entry->occurrences()->count());
    }

    public function test_manual_location_normalization_duplicate_consistent(): void
    {
        $entry = $this->makeDraft();
        $this->writer->create($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => true,
            'location_manual_name' => '  Sala A  ',
        ]);

        try {
            $this->generator->generate($entry, $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 1,
                'cjelodnevno' => true,
                'location_manual_name' => 'Sala A',
            ]));
            $this->fail('Expected manual location duplicate');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('identično postojeće', $e->getMessage());
        }

        $this->assertSame(1, $entry->occurrences()->count());
    }

    public function test_batch_fingerprint_uniqueness_for_supported_patterns(): void
    {
        $tz = (string) config('app.timezone');
        $start = Carbon::parse('2026-01-31', $tz)->startOfDay();
        $dates = $this->generator->computeDates('monthly', $start, 12, null);
        $this->assertCount(12, $dates);
        $this->assertSame(count($dates), count(array_unique($dates)));
    }

    // ─── Atomičnost ─────────────────────────────────────────────

    public function test_atomic_rollback_when_create_fails_mid_batch(): void
    {
        $entry = $this->makeDraft();
        $calls = 0;

        CulturalOccurrence::creating(function () use (&$calls) {
            $calls++;
            if ($calls === 3) {
                throw new CulturalEventDomainException('Simulirani pad trećeg Održavanja.');
            }
        });

        try {
            $this->generator->generate($entry, $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 3,
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected mid-batch failure');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('Simulirani pad', $e->getMessage());
        }

        $this->assertSame(0, $entry->fresh()->occurrences()->count());
    }

    public function test_sequential_second_generator_rejects_duplicate_set(): void
    {
        $entry = $this->makeDraft();
        $payload = $this->payload([
            'recurrence_type' => 'weekly',
            'start_date' => '2026-08-10',
            'count' => 3,
            'cjelodnevno' => true,
        ]);

        $this->generator->generate($entry, $payload);
        $this->assertSame(3, $entry->occurrences()->count());

        try {
            $this->generator->generate($entry->fresh(), $payload);
            $this->fail('Expected second generator reject');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('identično postojeće', $e->getMessage());
        }

        $this->assertSame(3, $entry->fresh()->occurrences()->count());
    }

    // ─── Statusi Eventa ──────────────────────────────────────────

    public function test_generator_rejected_for_non_draft_statuses_domain_and_http(): void
    {
        $pending = $this->makeReadyDraft('Pending', $this->organizer);
        $this->eventLifecycle->submitForApproval($pending, $this->editor);
        $pending = $pending->fresh();

        $this->assertDomainRejectsNonDraft($pending);
        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.generate', $pending), $this->httpPayload())
            ->assertRedirect(route('cultural-event-entries.index'))
            ->assertSessionHasErrors('domain');

        $published = $this->makeReadyDraft('Pub', $this->organizer);
        $this->eventLifecycle->submitForApproval($published, $this->editor);
        $this->eventLifecycle->approve($published->fresh(), $this->editor);
        $published = $published->fresh();
        $this->assertDomainRejectsNonDraft($published);
        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.generate', $published), $this->httpPayload())
            ->assertSessionHasErrors('domain');

        $cancelled = $this->makeReadyDraft('Can', $this->organizer);
        $this->eventLifecycle->submitForApproval($cancelled, $this->editor);
        $this->eventLifecycle->approve($cancelled->fresh(), $this->editor);
        $this->eventLifecycle->cancel($cancelled->fresh(), $this->editor, 'Otkaz');
        $cancelled = $cancelled->fresh();
        $this->assertDomainRejectsNonDraft($cancelled);

        $archived = $this->makeDraft('Arch');
        $archived->update(['status' => CulturalEventEntry::STATUS_ARCHIVED]);
        $this->assertDomainRejectsNonDraft($archived->fresh());
    }

    public function test_stale_pending_status_recheck_under_lock(): void
    {
        $entry = $this->makeDraft();
        $entry->update(['status' => CulturalEventEntry::STATUS_PENDING_APPROVAL]);

        try {
            $this->generator->generate($entry, $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 2,
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected pending reject');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('isključivo dok je Događaj Nacrt', $e->getMessage());
        }

        $this->assertSame(0, $entry->occurrences()->count());
    }

    public function test_returned_draft_allows_generator(): void
    {
        $entry = $this->makeReadyDraft('Return', $this->organizer);
        $this->eventLifecycle->submitForApproval($entry, $this->editor);
        $this->eventLifecycle->returnToDraft($entry->fresh(), $this->editor, 'Dorada');
        $entry = $entry->fresh();
        $this->assertTrue($entry->isDraft());

        $created = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-10-01',
            'count' => 2,
            'vrijeme_od' => '10:00',
            'vrijeme_do' => '11:00',
        ]));
        $this->assertCount(2, $created);
    }

    // ─── Autorzacija ─────────────────────────────────────────────

    public function test_editor_http_generate_success_and_ui(): void
    {
        $entry = $this->makeDraft();

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $entry))
            ->assertOk()
            ->assertSee('Generiši Održavanja', false);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.generate', $entry), [
                'recurrence_type' => 'weekly',
                'start_date' => '2026-08-10',
                'count' => 3,
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status');

        $this->assertSame(3, $entry->occurrences()->count());
    }

    public function test_regular_user_cannot_use_editor_generator_route(): void
    {
        $entry = $this->makeDraft();

        $this->actingAs($this->regular)
            ->post(route('cultural-event-entries.occurrences.generate', $entry), $this->httpPayload())
            ->assertForbidden();
    }

    public function test_moderator_cannot_use_editor_generator_route(): void
    {
        $entry = $this->makeDraftForOrganizer($this->organizer, $this->moderator);
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        $this->actingAs($this->moderator)
            ->post(route('cultural-event-entries.occurrences.generate', $entry), $this->httpPayload())
            ->assertForbidden();
    }

    public function test_moderator_generate_success_with_context(): void
    {
        $entry = $this->makeDraftForOrganizer($this->organizer, $this->moderator);
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertSee('Generiši Održavanja', false);

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-events.occurrences.generate', $entry), [
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 2,
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $this->assertSame(2, $entry->occurrences()->count());
    }

    public function test_moderator_auth_matrix_for_generator(): void
    {
        $entry = $this->makeDraftForOrganizer($this->organizer, $this->moderator);
        $payload = $this->httpPayload();

        // Event drugog Org-a bez ovlašćenja
        $foreign = $this->makeDraftForOrganizer($this->otherOrganizer, $this->editor);
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-events.occurrences.generate', $foreign), $payload)
            ->assertForbidden();

        // cross-context: ovlašćen za oba, aktivan kontekst Org A, Event Org B
        $this->grantModerator($this->moderator, $this->otherOrganizer);
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-events.occurrences.generate', $foreign), $payload)
            ->assertForbidden();

        // neaktivno ovlašćenje
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->moderator->id)
            ->where('organizer_id', $this->organizer->id)
            ->update([
                'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
                'removed_at' => now(),
            ]);
        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-events.occurrences.generate', $entry), $payload)
            ->assertForbidden();

        // restore + neaktivan Org
        $this->grantModerator($this->moderator, $this->organizer);
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);
        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-events.occurrences.generate', $entry), $payload)
            ->assertForbidden();
    }

    public function test_timezone_calendar_math_uses_app_timezone(): void
    {
        config(['app.timezone' => 'Europe/Podgorica']);
        $tz = (string) config('app.timezone');
        $start = Carbon::parse('2026-03-28', $tz)->startOfDay();
        $dates = $this->generator->computeDates('daily', $start, 3, null);
        $this->assertSame(['2026-03-28', '2026-03-29', '2026-03-30'], $dates);

        $entry = $this->makeDraft('TZ');
        $created = $this->generator->generate($entry, $this->payload([
            'recurrence_type' => 'daily',
            'start_date' => '2026-03-28',
            'count' => 2,
            'vrijeme_od' => '20:00',
            'vrijeme_do' => '21:00',
        ]));
        $this->assertSame('20:00:00', $created[0]->vrijeme_od);
        $this->assertSame('20:00:00', $created[1]->vrijeme_od);
    }

    // ─── helpers ─────────────────────────────────────────────────

    private function assertDomainRejectsNonDraft(CulturalEventEntry $entry): void
    {
        try {
            $this->generator->generate($entry, $this->payload([
                'recurrence_type' => 'daily',
                'start_date' => '2026-08-10',
                'count' => 2,
                'cjelodnevno' => true,
            ]));
            $this->fail('Expected non-draft reject for '.$entry->status);
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('isključivo dok je Događaj Nacrt', $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'count' => null,
            'end_date' => null,
            'vrijeme_od' => null,
            'vrijeme_do' => null,
            'cjelodnevno' => false,
            'location_id' => null,
            'location_manual_name' => null,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function httpPayload(): array
    {
        return [
            'recurrence_type' => 'daily',
            'start_date' => '2026-08-10',
            'count' => 2,
            'cjelodnevno' => '1',
        ];
    }

    private function makeDraft(string $naslov = 'Gen nacrt'): CulturalEventEntry
    {
        return $this->eventWriter->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);
    }

    private function makeReadyDraft(string $naslov = 'Ready', ?CulturalOrganizer $organizer = null): CulturalEventEntry
    {
        $payload = [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ];
        if ($organizer !== null) {
            $payload['organizer_id'] = $organizer->id;
        }

        $entry = $this->eventWriter->createDraft($this->editor, $payload);
        $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }

    private function makeDraftForOrganizer(CulturalOrganizer $org, User $creator): CulturalEventEntry
    {
        return $this->eventWriter->createDraft($creator, [
            'naslov' => 'Mod gen',
            'organizer_id' => $org->id,
            'category_id' => $this->category->id,
        ]);
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

    private function grantModerator(User $user, CulturalOrganizer $organizer): void
    {
        CulturalModeratorAuthorization::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'organizer_id' => $organizer->id,
            ],
            [
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]
        );
    }
}
