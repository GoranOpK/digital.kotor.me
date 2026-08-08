<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GAP-RESUME-01 / BR-133 / TM-OCC-17 — Urednik: Odgođen → Planiran + novi termin.
 */
class CulturalEditorOccurrenceResumeTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regular;

    private User $moderator;

    private CulturalCategory $category;

    private CulturalOrganizer $organizer;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $adminId = Role::where('name', 'kk_admin')->firstOrFail()->id;
        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => $adminId,
            'activation_status' => 'active',
        ]);

        $this->regular = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->organizer = $this->makeOrganizer('Org Resume');
        $this->grantModerator($this->moderator, $this->organizer);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_tm_occ_17_editor_resumes_org_null_published_postponed(): void
    {
        $location = CulturalLocation::create([
            'naziv' => 'Trg od Oružja',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        [$entry, $occurrence] = $this->makePublishedOrgNull([
            'datum' => '2026-09-10',
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
            'location_id' => $location->id,
        ]);

        $this->assertNull($entry->organizer_id);
        $this->occurrenceLifecycle->postpone($occurrence);
        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->status);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $entry))
            ->assertOk()
            ->assertSee('Vrati u Planirano', false);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence]), [
                'datum' => '2026-10-15',
                'vrijeme_od' => '19:00',
                'vrijeme_do' => '21:00',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status');

        $occurrence->refresh();
        $entry->refresh();

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
        $this->assertSame('2026-10-15', $occurrence->datum->toDateString());
        $this->assertSame('19:00:00', $occurrence->vrijeme_od);
        $this->assertSame('21:00:00', $occurrence->vrijeme_do);
        $this->assertFalse($occurrence->cjelodnevno);
        $this->assertSame($location->id, $occurrence->location_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->status);
        $this->assertNull($entry->organizer_id);
        $this->assertFalse($entry->featured);
        $this->assertSame('Org null resume', $entry->naslov);
    }

    public function test_editor_resumes_event_with_organizer(): void
    {
        // BR-133 je Org-null; Uredničke statusne rute već rade i sa Org (kao postpone/cancel).
        [$entry, $occurrence] = $this->makePublishedWithOrganizer([
            'datum' => '2026-09-01',
            'cjelodnevno' => true,
        ]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-11-01',
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
        $this->assertSame('2026-11-01', $occurrence->fresh()->datum->toDateString());
        $this->assertSame($this->organizer->id, $entry->fresh()->organizer_id);
    }

    public function test_manual_location_preserved_on_resume(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull([
            'datum' => '2026-09-10',
            'cjelodnevno' => true,
            'location_manual_name' => 'Privremena sala',
        ]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-09-20',
                'cjelodnevno' => '1',
            ])
            ->assertRedirect();

        $occurrence->refresh();
        $this->assertNull($occurrence->location_id);
        $this->assertSame('Privremena sala', $occurrence->location_manual_name);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
    }

    public function test_location_payload_rejected_and_unchanged(): void
    {
        $location = CulturalLocation::create([
            'naziv' => 'Stara',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);
        $other = CulturalLocation::create([
            'naziv' => 'Nova',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        [$entry, $occurrence] = $this->makePublishedOrgNull([
            'datum' => '2026-09-10',
            'cjelodnevno' => true,
            'location_id' => $location->id,
        ]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-09-20',
                'cjelodnevno' => '1',
                'location_id' => $other->id,
            ])
            ->assertSessionHasErrors('occurrence');

        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->status);
        $this->assertSame($location->id, $occurrence->location_id);
    }

    public function test_cancelled_occurrence_resume_rejected(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->cancel($occurrence);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
    }

    public function test_planned_occurrence_resume_rejected(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
        $this->assertSame('2026-09-10', $occurrence->fresh()->datum->toDateString());
    }

    public function test_finished_occurrence_resume_rejected(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $occurrence->update(['status' => CulturalOccurrence::STATUS_FINISHED]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $occurrence->fresh()->status);
    }

    public function test_cancelled_parent_blocks_resume(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->postpone($occurrence);
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Otkaz Eventa');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry->fresh(), $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');
    }

    public function test_archived_parent_blocks_resume(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->postpone($occurrence);
        $entry->update(['status' => CulturalEventEntry::STATUS_ARCHIVED]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry->fresh(), $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_stale_occurrence_cancelled_before_resume(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $occurrence->update(['status' => CulturalOccurrence::STATUS_CANCELLED]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
        $this->assertSame('2026-09-10', $occurrence->fresh()->datum->toDateString());
    }

    public function test_stale_event_cancelled_before_resume(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $entry->update([
            'status' => CulturalEventEntry::STATUS_CANCELLED,
            'cancellation_reason' => 'Stale cancel',
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry->fresh(), $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_auth_matrix_for_editor_resume_route(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->postpone($occurrence);
        $payload = [
            'datum' => '2026-10-01',
            'cjelodnevno' => '1',
        ];

        $this->actingAs($this->regular)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), $payload)
            ->assertForbidden();

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), $payload)
            ->assertForbidden();

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_invalid_time_relation_rejected(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull([
            'datum' => '2026-09-10',
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
        ]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'vrijeme_od' => '20:00',
                'vrijeme_do' => '18:00',
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_all_day_resume_allowed(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull([
            'datum' => '2026-09-10',
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
        ]);
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertRedirect();

        $occurrence->refresh();
        $this->assertTrue($occurrence->cjelodnevno);
        $this->assertNull($occurrence->vrijeme_od);
        $this->assertNull($occurrence->vrijeme_do);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
    }

    public function test_direct_writer_update_still_blocked_on_published(): void
    {
        [$entry, $occurrence] = $this->makePublishedOrgNull(['datum' => '2026-09-10', 'cjelodnevno' => true]);
        $this->occurrenceLifecycle->postpone($occurrence);

        try {
            $this->occurrenceWriter->update($occurrence->fresh(), [
                'datum' => '2026-12-01',
                'cjelodnevno' => true,
            ]);
            $this->fail('Expected published content lock');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('prijedlog', $e->getMessage());
        }

        $this->assertSame('2026-09-10', $occurrence->fresh()->datum->toDateString());
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $occurrenceData
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makePublishedOrgNull(array $occurrenceData): array
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Org null resume',
            'category_id' => $this->category->id,
        ]);
        $occurrence = $this->occurrenceWriter->create($entry, $occurrenceData);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return [$entry->fresh(['occurrences']), $occurrence->fresh()];
    }

    /**
     * @param  array<string, mixed>  $occurrenceData
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makePublishedWithOrganizer(array $occurrenceData): array
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Sa Org resume',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);
        $occurrence = $this->occurrenceWriter->create($entry, $occurrenceData);
        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return [$entry->fresh(['occurrences']), $occurrence->fresh()];
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
