<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Published content lock — PATCH-063 §4.13 precizira uski izuzetak:
 * Urednik + published + organizer_id null → direct ordinary edit.
 * Registered Org / Moderator published → i dalje read-only (Prijedlog).
 */
class CulturalEventEntryPublishedContentLockTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private CulturalOrganizer $organizer;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private CulturalCategory $category;

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

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->organizer = $this->makeOrganizer('Org Lock');
        $this->grantModerator($this->moderator, $this->organizer);
    }

    public function test_published_direct_flow_allows_urednik_content_update(): void
    {
        $entry = $this->makePublishedWithoutOrganizer('Objavljen');

        $updated = $this->writer->updateContent($entry, $this->editor, [
            'naslov' => 'Izmijenjen naslov',
            'opis' => 'Izmijenjen opis',
            'category_id' => $this->category->id,
        ]);

        $this->assertSame('Izmijenjen naslov', $updated->naslov);
        $this->assertSame('Izmijenjen opis', $updated->opis);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $updated->status);
        $this->assertNull($updated->organizer_id);
    }

    public function test_kk_admin_http_can_update_published_direct_flow(): void
    {
        $entry = $this->makePublishedWithoutOrganizer('Admin unlock');

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Smije',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status', 'Izmjene događaja su sačuvane.');

        $this->assertSame('Smije', $entry->fresh()->naslov);
    }

    public function test_published_with_registered_organizer_remains_content_locked(): void
    {
        $entry = $this->makePublishedForOrganizer('Sa Org lock');

        $this->expectException(CulturalEventDomainException::class);
        $this->expectExceptionMessage('Objavljen Događaj je sadržajno read-only');

        $this->writer->updateContent($entry, $this->editor, [
            'naslov' => 'Hakovan naslov',
            'opis' => 'Hakovan opis',
        ]);
    }

    public function test_moderator_cannot_edit_published_event_content(): void
    {
        $entry = $this->makePublishedForOrganizer('Mod published');

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        try {
            $this->writer->updateContent($entry, $this->moderator, [
                'naslov' => 'Mod hak',
            ]);
            $this->fail('Expected domain exception for moderator');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('read-only', mb_strtolower($e->getMessage()));
        }

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-events.update', $entry), [
                'naslov' => 'Mod HTTP hak',
            ])
            ->assertForbidden();

        $this->assertSame('Mod published', $entry->fresh()->naslov);
    }

    public function test_draft_can_still_be_updated(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Nacrt',
            'category_id' => $this->category->id,
        ]);

        $updated = $this->writer->updateContent($entry, $this->editor, [
            'naslov' => 'Nacrt izmijenjen',
            'opis' => 'Opis',
        ]);

        $this->assertSame('Nacrt izmijenjen', $updated->naslov);
        $this->assertSame('Opis', $updated->opis);
    }

    public function test_pending_remains_content_locked(): void
    {
        $entry = $this->makeReadyDraftWithoutOrganizer('Pending');
        $this->lifecycle->submitForApproval($entry, $this->editor);
        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->status);

        $this->expectException(CulturalEventDomainException::class);
        $this->expectExceptionMessage('na odobrenju je zaključan');

        $this->writer->updateContent($entry, $this->editor, [
            'naslov' => 'Hak pending',
        ]);
    }

    public function test_cancelled_remains_content_locked(): void
    {
        $entry = $this->makePublishedWithoutOrganizer('Za otkaz');
        $this->lifecycle->cancel($entry, $this->editor, 'Otkazano');
        $entry->refresh();
        $this->assertTrue($entry->isCancelled());

        try {
            $this->writer->updateContent($entry, $this->editor, [
                'naslov' => 'Hak cancelled',
            ]);
            $this->fail('Expected cancelled lock');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('read-only', mb_strtolower($e->getMessage()));
        }

        $this->assertSame('Za otkaz', $entry->fresh()->naslov);
    }

    public function test_published_featured_only_update_still_allowed(): void
    {
        $entry = $this->makePublishedWithoutOrganizer('Featured ok');

        $this->writer->updateContent($entry, $this->editor, ['featured' => true]);

        $this->assertTrue($entry->fresh()->featured);
        $this->assertSame('Featured ok', $entry->fresh()->naslov);
    }

    private function makeReadyDraftWithoutOrganizer(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }

    private function makePublishedWithoutOrganizer(string $naslov): CulturalEventEntry
    {
        $entry = $this->makeReadyDraftWithoutOrganizer($naslov);
        $this->lifecycle->publishDirectly($entry, $this->editor);

        return $entry->fresh();
    }

    private function makePublishedForOrganizer(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->lifecycle->approve($entry->fresh(), $this->editor);

        return $entry->fresh();
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
