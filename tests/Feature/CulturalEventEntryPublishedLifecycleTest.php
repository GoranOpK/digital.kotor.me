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
 * Sprint 3A.4 — Published / Cancelled Event + Occurrence lifecycle (nije TS-010).
 */
class CulturalEventEntryPublishedLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

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
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_published_can_be_cancelled_with_reason(): void
    {
        $entry = $this->makePublished('Za otkaz');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.cancel', $entry), [
                'cancellation_reason' => 'Loše vrijeme',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame('Loše vrijeme', $entry->cancellation_reason);
        $this->assertDatabaseCount('cultural_events', 0);
    }

    public function test_cancel_without_reason_is_allowed(): void
    {
        $entry = $this->makePublished('Bez razloga');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.cancel', $entry), [])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertNull($entry->cancellation_reason);

        $entryWhitespace = $this->makePublished('Whitespace razlog');
        $this->lifecycle->cancel($entryWhitespace, $this->editor, '   ');
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entryWhitespace->fresh()->status);
        $this->assertNull($entryWhitespace->fresh()->cancellation_reason);
    }

    public function test_cancelled_cannot_become_published_or_draft(): void
    {
        $entry = $this->makeCancelled('Terminal');

        $this->assertFalse($entry->canTransitionTo(CulturalEventEntry::STATUS_PUBLISHED));
        $this->assertFalse($entry->canTransitionTo(CulturalEventEntry::STATUS_DRAFT));

        try {
            $this->lifecycle->republish($entry, $this->editor);
            $this->fail('Expected republish rejection');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('nije dozvoljen', $e->getMessage());
        }

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->transitionTo($entry->fresh(), CulturalEventEntry::STATUS_PUBLISHED, $this->editor);
    }

    public function test_cancelled_content_update_rejected_but_reason_can_be_updated(): void
    {
        $entry = $this->makeCancelled('Istorija', 'Prvi razlog');

        try {
            $this->writer->updateContent($entry, $this->editor, ['naslov' => 'Hak']);
            $this->fail('Expected content lock');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('read-only', mb_strtolower($e->getMessage()));
        }

        $this->assertSame('Istorija', $entry->fresh()->naslov);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.cancellation-reason', $entry), [
                'cancellation_reason' => 'Dopunjen razlog za javnost',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame('Dopunjen razlog za javnost', $entry->fresh()->cancellation_reason);
    }

    public function test_cancelled_clears_featured(): void
    {
        $entry = $this->makePublished('Featured cancel');
        $this->writer->updateContent($entry, $this->editor, ['featured' => true]);
        $this->assertTrue($entry->fresh()->featured);

        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Otkaz');
        $this->assertFalse($entry->fresh()->featured);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
    }

    public function test_featured_on_off_on_published_and_max_three(): void
    {
        $a = $this->makePublished('A');
        $b = $this->makePublished('B');
        $c = $this->makePublished('C');
        $d = $this->makePublished('D');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.featured', $a), ['featured' => '1'])
            ->assertRedirect(route('cultural-event-entries.edit', $a));
        $this->assertTrue($a->fresh()->featured);

        $this->writer->updateContent($b, $this->editor, ['featured' => true]);
        $this->writer->updateContent($c, $this->editor, ['featured' => true]);

        try {
            $this->writer->updateContent($d, $this->editor, ['featured' => true]);
            $this->fail('Expected max featured rejection');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('tri', mb_strtolower($e->getMessage()));
        }

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.featured', $a), ['featured' => '0'])
            ->assertRedirect(route('cultural-event-entries.edit', $a));
        $this->assertFalse($a->fresh()->featured);
        $this->assertTrue($b->fresh()->featured);
        $this->assertTrue($c->fresh()->featured);
    }

    public function test_planned_can_be_postponed(): void
    {
        $entry = $this->makePublished('Odgodi');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]))
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_single_occurrence_cancel_does_not_cancel_event(): void
    {
        $entry = $this->makePublished('Jedno');
        $occurrence = $entry->occurrences()->firstOrFail();

        $result = $this->occurrenceLifecycle->cancelWithoutAffectingEvent($occurrence);

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $result['occurrence']->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $result['event_status_before']);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $result['event_status_after']);

        $this->actingAs($this->editor);
        $entry2 = $this->makePublished('HTTP cancel occ');
        $occ2 = $entry2->occurrences()->firstOrFail();

        $this->post(route('cultural-event-entries.occurrences.cancel', [$entry2, $occ2]))
            ->assertRedirect(route('cultural-event-entries.edit', $entry2));

        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occ2->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry2->fresh()->status);
    }

    public function test_cancelled_parent_blocks_occurrence_mutations(): void
    {
        $entry = $this->makeCancelled('Lock parent');
        $occurrence = $entry->occurrences()->firstOrFail();

        try {
            $this->occurrenceWriter->create($entry, [
                'datum' => now()->addDays(20)->toDateString(),
                'cjelodnevno' => true,
            ]);
            $this->fail('create should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('otkazan', mb_strtolower($e->getMessage()));
        }

        try {
            $this->occurrenceWriter->update($occurrence, [
                'datum' => now()->addDays(30)->toDateString(),
                'cjelodnevno' => true,
            ]);
            $this->fail('update should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('istorijski', mb_strtolower($e->getMessage()));
        }

        try {
            $this->occurrenceWriter->deletePhysically($occurrence->fresh());
            $this->fail('delete should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertTrue(
                str_contains(mb_strtolower($e->getMessage()), 'istorijski')
                || str_contains(mb_strtolower($e->getMessage()), 'nacrt')
            );
        }

        $this->assertDatabaseCount('cultural_occurrences', 1);
    }

    public function test_cancelled_parent_blocks_occurrence_lifecycle(): void
    {
        $entry = $this->makePublished('Lifecycle lock');
        $occurrence = $entry->occurrences()->firstOrFail();
        $this->lifecycle->cancel($entry, $this->editor, 'Stop');
        $occurrence->refresh();

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceLifecycle->postpone($occurrence);
    }

    public function test_cancelled_parent_blocks_occurrence_cancel(): void
    {
        $entry = $this->makePublished('Occ cancel lock');
        $occurrence = $entry->occurrences()->firstOrFail();
        $this->lifecycle->cancel($entry, $this->editor, 'Stop');

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceLifecycle->cancel($occurrence->fresh());
    }

    public function test_cancelled_parent_blocks_resume_and_finish(): void
    {
        $entry = $this->makePublished('Resume lock');
        $occurrence = $entry->occurrences()->firstOrFail();
        $this->occurrenceLifecycle->postpone($occurrence);
        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Stop');

        try {
            $this->occurrenceLifecycle->resumeWithNewTermin($occurrence->fresh(), [
                'datum' => now()->addDays(40)->toDateString(),
                'cjelodnevno' => true,
            ]);
            $this->fail('resume should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('istorijski', mb_strtolower($e->getMessage()));
        }

        $entry2 = $this->makePublished('Finish lock');
        $occ2 = $entry2->occurrences()->firstOrFail();
        $this->lifecycle->cancel($entry2, $this->editor, 'Stop');

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceLifecycle->markFinished($occ2->fresh());
    }

    public function test_ordinary_user_cannot_published_lifecycle_actions(): void
    {
        $entry = $this->makePublished('Zabranjeno');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.cancel', $entry), [
                'cancellation_reason' => 'Ne smije',
            ])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.featured', $entry), ['featured' => '1'])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]))
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
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

        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }

    private function makeCancelled(string $naslov, string $reason = 'Otkazano'): CulturalEventEntry
    {
        $entry = $this->makePublished($naslov);
        $this->lifecycle->cancel($entry, $this->editor, $reason);

        return $entry->fresh(['occurrences']);
    }
}
