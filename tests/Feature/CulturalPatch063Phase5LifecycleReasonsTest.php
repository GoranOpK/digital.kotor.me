<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH-063 Phase 5 — opcioni razlozi odgađanja / otkazivanja OCC i Entry.
 */
class CulturalPatch063Phase5LifecycleReasonsTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
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
    }

    public function test_postpone_without_reason_keeps_date_and_entry_status(): void
    {
        $entry = $this->makePublished('Odgodi bez razloga');
        $occurrence = $entry->occurrences()->firstOrFail();
        $originalDate = $occurrence->datum?->toDateString();
        $occurrenceId = $occurrence->id;

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]))
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status', 'Održavanje je odgođeno.');

        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->status);
        $this->assertNull($occurrence->postponement_reason);
        $this->assertSame($originalDate, $occurrence->datum?->toDateString());
        $this->assertSame($occurrenceId, $occurrence->id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_postpone_with_reason_persists_and_resume_keeps_reason(): void
    {
        $entry = $this->makePublished('Odgodi sa razlogom');
        $occurrence = $entry->occurrences()->firstOrFail();
        $originalDate = $occurrence->datum?->toDateString();
        $occurrenceId = $occurrence->id;
        $newDate = now()->addDays(40)->toDateString();

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]), [
                'postponement_reason' => '  Tehnički problemi  ',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->status);
        $this->assertSame('Tehnički problemi', $occurrence->postponement_reason);
        $this->assertSame($originalDate, $occurrence->datum?->toDateString());
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.resume', [$entry, $occurrence]), [
                'datum' => $newDate,
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $occurrence->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
        $this->assertSame($newDate, $occurrence->datum?->toDateString());
        $this->assertSame('Tehnički problemi', $occurrence->postponement_reason);
        $this->assertSame($occurrenceId, $occurrence->id);
        $this->assertDatabaseCount('cultural_occurrences', 1);
    }

    public function test_postpone_rejects_tampered_lifecycle_fields(): void
    {
        $entry = $this->makePublished('Tamper postpone');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]), [
                'postponement_reason' => 'ok',
                'status' => CulturalOccurrence::STATUS_CANCELLED,
                'datum' => now()->addYear()->toDateString(),
            ])
            ->assertSessionHasErrors(['status', 'datum']);

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_occurrence_cancel_optional_reason_does_not_cancel_entry_or_sibling(): void
    {
        $entry = $this->makePublishedWithTwoOccurrences('Dva OCC');
        $first = $entry->occurrences()->orderBy('id')->firstOrFail();
        $second = $entry->occurrences()->orderBy('id')->skip(1)->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.cancel', [$entry, $first]))
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status', 'Održavanje je otkazano.');

        $first->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $first->status);
        $this->assertNull($first->cancellation_reason);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $second->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.cancel', [$entry, $second]), [
                'cancellation_reason' => '  Nema publike  ',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $second->refresh();
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $second->status);
        $this->assertSame('Nema publike', $second->cancellation_reason);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
        $this->assertNull($entry->fresh()->cancellation_reason);
    }

    public function test_occurrence_cancel_rejects_status_tamper(): void
    {
        $entry = $this->makePublished('Tamper occ cancel');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.occurrences.cancel', [$entry, $occurrence]), [
                'cancellation_reason' => 'x',
                'status' => CulturalOccurrence::STATUS_FINISHED,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_entry_cancel_optional_reason_cascades_and_stays_terminal(): void
    {
        $entry = $this->makePublishedWithTwoOccurrences('Entry cancel');
        $planned = $entry->occurrences()->orderBy('id')->firstOrFail();
        $postponed = $entry->occurrences()->orderBy('id')->skip(1)->firstOrFail();
        $this->occurrenceLifecycle->postpone($postponed, 'Privremeno');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.cancel', $entry))
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status', 'Događaj je otkazan.');

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertNull($entry->cancellation_reason);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $planned->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $postponed->fresh()->status);
        $this->assertSame('Privremeno', $postponed->fresh()->postponement_reason);

        $this->assertFalse($entry->canTransitionTo(CulturalEventEntry::STATUS_PUBLISHED));
        try {
            $this->eventLifecycle->republish($entry, $this->editor);
            $this->fail('Expected no republish');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('nije dozvoljen', $e->getMessage());
        }

        try {
            $this->writer->updateContent($entry, $this->editor, ['naslov' => 'Hack']);
            $this->fail('Expected cancelled content lock');
        } catch (CulturalEventDomainException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect()
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
    }

    public function test_entry_cancel_with_reason_persists(): void
    {
        $entry = $this->makePublished('Sa razlogom');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.cancel', $entry), [
                'cancellation_reason' => '  Program povučen  ',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame('Program povučen', $entry->cancellation_reason);
    }

    public function test_unauthorized_user_cannot_postpone_or_cancel(): void
    {
        $entry = $this->makePublished('Auth fence');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]), [
                'postponement_reason' => 'ne smije',
            ])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.occurrences.cancel', [$entry, $occurrence]), [
                'cancellation_reason' => 'ne smije',
            ])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.cancel', $entry), [
                'cancellation_reason' => 'ne smije',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_ui_shows_optional_reason_labels(): void
    {
        $entry = $this->makePublished('UI labels');

        $html = $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $entry))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Razlog odgađanja (opciono)', $html);
        $this->assertStringContainsString('Razlog otkazivanja (opciono)', $html);
        $this->assertStringContainsString('Otkaži održavanje', $html);
        $this->assertStringContainsString('Otkaži događaj', $html);
        $this->assertStringNotContainsString('Razlog otkazivanja (obavezno)', $html);
        $this->assertStringNotContainsString('>postponement_reason<', $html);
        $this->assertStringNotContainsString('>cancellation_reason<', $html);
    }

    private function makePublished(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }

    private function makePublishedWithTwoOccurrences(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(12)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }
}
