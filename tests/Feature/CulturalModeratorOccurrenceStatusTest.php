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
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Direktne statusne akcije Moderatora nad Održavanjem Objavljenog Događaja (BR-132).
 */
class CulturalModeratorOccurrenceStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private User $modA2;

    private User $modB;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private CulturalCategory $category;

    private OccurrenceLifecycle $occurrenceLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private EventLifecycle $eventLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->modA = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
        $this->modA2 = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
        $this->modB = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->orgA = $this->makeOrganizer('Org A Status');
        $this->orgB = $this->makeOrganizer('Org B Status');
        $this->grantModerator($this->modA, $this->orgA);
        $this->grantModerator($this->modA2, $this->orgA);
        $this->grantModerator($this->modB, $this->orgB);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti Status',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
    }

    public function test_moderator_postpones_planned_occurrence(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence]))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_moderator_cancels_planned_occurrence(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.cancel', [$entry, $occurrence]))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_moderator_cancels_postponed_occurrence(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.cancel', [$entry, $occurrence->fresh()]))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
    }

    public function test_moderator_resumes_postponed_with_new_termin(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence([
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $originalLocationId = $occurrence->location_id;
        $this->occurrenceLifecycle->postpone($occurrence);

        $newDatum = now()->addDays(40)->toDateString();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => $newDatum,
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
        $this->assertSame($newDatum, $occurrence->datum->toDateString());
        $this->assertSame($originalLocationId, $occurrence->location_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_resume_rejects_location_fields(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();
        $this->occurrenceLifecycle->postpone($occurrence);

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $entry))
            ->post(route('cultural-moderator-events.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => now()->addDays(50)->toDateString(),
                'cjelodnevno' => '1',
                'location_id' => 999,
            ])
            ->assertRedirect(route('cultural-moderator-events.edit', $entry))
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_cancelled_occurrence_is_terminal(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();
        $this->occurrenceLifecycle->cancel($occurrence);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence->fresh()]))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry))
            ->assertSessionHasErrors('occurrence');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.resume', [$entry, $occurrence->fresh()]), [
                'datum' => now()->addDays(60)->toDateString(),
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-moderator-events.edit', $entry))
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
    }

    public function test_cancelled_event_blocks_all_status_actions(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();
        $this->eventLifecycle->cancel($entry, $this->editor, 'Stop');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry->fresh(), $occurrence]))
            ->assertSessionHasErrors('occurrence');

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceLifecycle->cancel($occurrence->fresh());
    }

    public function test_archived_event_blocks_status_actions(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();
        $entry->update(['status' => CulturalEventEntry::STATUS_ARCHIVED]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry->fresh(), $occurrence]))
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_cross_org_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entryB, $occurrenceB] = $this->makePublishedWithOccurrence(organizer: $this->orgB, actor: $this->modB);

        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entryB, $occurrenceB]))
            ->assertForbidden();
    }

    public function test_cross_context_forbidden(): void
    {
        $this->grantModerator($this->modA, $this->orgB);
        [$entryA, $occurrenceA] = $this->makePublishedWithOccurrence();

        CulturalOrganizerContext::set($this->modA, $this->orgB->id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entryA, $occurrenceA]))
            ->assertForbidden();
    }

    public function test_inactive_authorization_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();

        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->modA->id)
            ->where('organizer_id', $this->orgA->id)
            ->update([
                'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence]))
            ->assertForbidden();
    }

    public function test_second_status_action_fails_after_recheck(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence]))
            ->assertRedirect();

        CulturalOrganizerContext::set($this->modA2, $this->orgA->id);
        $this->actingAs($this->modA2)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence->fresh()]))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry))
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_direct_writer_update_still_blocked_on_published(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [, $occurrence] = $this->makePublishedWithOccurrence();

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceWriter->update($occurrence, [
            'datum' => now()->addDays(99)->toDateString(),
            'cjelodnevno' => true,
        ]);
    }

    /**
     * @param  array{datum?: string, cjelodnevno?: bool, vrijeme_od?: ?string, vrijeme_do?: ?string}  $occurrencePayload
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makePublishedWithOccurrence(
        array $occurrencePayload = [],
        ?CulturalOrganizer $organizer = null,
        ?User $actor = null,
    ): array {
        $organizer ??= $this->orgA;
        $actor ??= $this->modA;

        $writer = app(EventWriter::class);
        $entry = $writer->createDraft($this->editor, [
            'naslov' => 'Status event '.$organizer->id,
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
        ]);

        $payload = array_merge([
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ], $occurrencePayload);

        $occurrence = $this->occurrenceWriter->create($entry, $payload);
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
