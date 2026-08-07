<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalMedia;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\CulturalTag;
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
 * TS-003 Korak 1 — kanonski Događaj (PO-EV-01).
 */
class CulturalEventDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
    }

    public function test_creates_canonical_event_as_draft(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Ljetni koncert',
            'opis' => 'Opis',
        ]);

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
        $this->assertSame('Ljetni koncert', $entry->naslov);
        $this->assertSame($this->editor->id, $entry->created_by);
        $this->assertSame($this->editor->id, $entry->last_modified_by);
        $this->assertNull($entry->first_submitted_at);
        $this->assertDatabaseCount('cultural_event_entries', 1);
        $this->assertDatabaseCount('cultural_events', 0);
    }

    public function test_organizer_relation(): void
    {
        $organizer = $this->makeActiveOrganizer();

        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Dogadjaj',
            'organizer_id' => $organizer->id,
        ]);

        $this->assertTrue($entry->organizer->is($organizer));
        $this->assertSame($organizer->id, $entry->organizer_id);
    }

    public function test_category_relation(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Dogadjaj',
            'category_id' => $category->id,
        ]);

        $this->assertTrue($entry->category->is($category));
    }

    public function test_cover_media_relation_when_allowed(): void
    {
        $media = $this->makeActiveCoverMedia();

        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Dogadjaj',
            'cover_media_id' => $media->id,
        ]);

        $this->assertTrue($entry->coverMedia->is($media));
        $this->assertSame(1, $media->fresh()->businessLinkCount());
    }

    public function test_draft_is_initial_status(): void
    {
        $entry = $this->writer->createDraft($this->editor, ['naslov' => 'X']);

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
        $this->assertSame('Nacrt', $entry->statusLabel());
    }

    public function test_invalid_event_status_is_rejected(): void
    {
        $entry = $this->writer->createDraft($this->editor, ['naslov' => 'X']);

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->transitionTo($entry, 'legacy_enum_value', $this->editor);
    }

    public function test_cancelled_is_terminal_no_republish(): void
    {
        $entry = $this->makePublishedEvent();

        $this->lifecycle->cancel($entry, $this->editor, 'Kiša');
        $entry->refresh();

        $this->assertTrue($entry->isCancelled());
        $this->assertFalse($entry->canTransitionTo(CulturalEventEntry::STATUS_PUBLISHED));

        try {
            $this->lifecycle->republish($entry, $this->editor);
            $this->fail('Expected republish to fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('nije dozvoljen', $e->getMessage());
        }

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->transitionTo($entry->fresh(), CulturalEventEntry::STATUS_PUBLISHED, $this->editor);
    }

    public function test_deactivated_organizer_cannot_be_used_for_new_event(): void
    {
        $organizer = $this->makeActiveOrganizer();
        $organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->createDraft($this->editor, [
            'naslov' => 'X',
            'organizer_id' => $organizer->id,
        ]);
    }

    public function test_inactive_category_cannot_be_selected_for_new_link(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Stara',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->createDraft($this->editor, [
            'naslov' => 'X',
            'category_id' => $category->id,
        ]);
    }

    public function test_inactive_cover_media_is_rejected(): void
    {
        $media = $this->makeActiveCoverMedia();
        $media->update(['status' => CulturalMedia::STATUS_INACTIVE]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->createDraft($this->editor, [
            'naslov' => 'X',
            'cover_media_id' => $media->id,
        ]);
    }

    public function test_wrong_media_purpose_is_rejected(): void
    {
        $media = $this->makeActiveCoverMedia([
            'namjena' => CulturalMedia::PURPOSE_CATEGORY_DEFAULT,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->createDraft($this->editor, [
            'naslov' => 'X',
            'cover_media_id' => $media->id,
        ]);
    }

    public function test_tags_zero_and_many_and_inactive_new_link(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Bez oznaka',
            'tag_ids' => [],
        ]);
        $this->assertCount(0, $entry->tags);

        $t1 = CulturalTag::create(['naziv' => 'A', 'status' => CulturalTag::STATUS_ACTIVE]);
        $t2 = CulturalTag::create(['naziv' => 'B', 'status' => CulturalTag::STATUS_ACTIVE]);
        $inactive = CulturalTag::create(['naziv' => 'C', 'status' => CulturalTag::STATUS_INACTIVE]);

        $entry = $this->writer->updateContent($entry, $this->editor, [
            'tag_ids' => [$t1->id, $t2->id],
        ]);
        $this->assertCount(2, $entry->tags);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->updateContent($entry, $this->editor, [
            'tag_ids' => [$t1->id, $t2->id, $inactive->id],
        ]);
    }

    public function test_historical_tag_link_remains_after_tag_deactivation(): void
    {
        $tag = CulturalTag::create(['naziv' => 'Historija', 'status' => CulturalTag::STATUS_ACTIVE]);
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'X',
            'tag_ids' => [$tag->id],
        ]);

        $tag->update(['status' => CulturalTag::STATUS_INACTIVE]);

        $entry->refresh();
        $this->assertTrue($entry->tags()->where('cultural_tags.id', $tag->id)->exists());
        $this->assertCount(1, $entry->tags);
    }

    public function test_valid_status_transitions_submit_and_approve(): void
    {
        $entry = $this->makeReadyDraftWithoutOrganizer();

        $this->lifecycle->submitForApproval($entry, $this->editor);
        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->status);
        $this->assertNotNull($entry->first_submitted_at);

        $this->lifecycle->approve($entry, $this->editor);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    private function makePublishedEvent(): CulturalEventEntry
    {
        $entry = $this->makeReadyDraftWithoutOrganizer();
        $this->lifecycle->publishDirectly($entry, $this->editor);

        return $entry->fresh();
    }

    private function makeReadyDraftWithoutOrganizer(): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Spremno',
            'category_id' => $category->id,
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => '2026-09-01',
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }

    private function makeActiveOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => 'Org',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeActiveCoverMedia(array $overrides = []): CulturalMedia
    {
        return CulturalMedia::create(array_merge([
            'naziv' => 'Naslovna',
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => 'a.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 100,
            'storage_path' => 'cultural-media/a.jpg',
            'creator_id' => $this->editor->id,
        ], $overrides));
    }
}
