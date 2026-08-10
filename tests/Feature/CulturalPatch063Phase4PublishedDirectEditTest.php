<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
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
use App\Support\CulturalPublicReadSource;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH-063 Phase 4 — direktan content edit Objavljenog Urednik direct-flow Događaja.
 */
class CulturalPatch063Phase4PublishedDirectEditTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private User $regularUser;

    private CulturalOrganizer $organizer;

    private CulturalCategory $category;

    private CulturalCategory $categoryB;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

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

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $this->categoryB = CulturalCategory::create([
            'naziv' => 'Izložbe',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->organizer = $this->makeOrganizer('Org Reg');
        CulturalModeratorAuthorization::query()->updateOrCreate(
            [
                'user_id' => $this->moderator->id,
                'organizer_id' => $this->organizer->id,
            ],
            [
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]
        );
    }

    public function test_kk_admin_can_directly_edit_published_direct_flow_content(): void
    {
        $entry = $this->makePublishedDirect('Stari naslov');
        $submittedAt = $entry->first_submitted_at?->toDateTimeString();

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Novi naslov',
                'opis' => 'Novi opis',
                'category_id' => $this->categoryB->id,
                'organizer_manual_name' => '  Ansambl  ',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status', 'Izmjene događaja su sačuvane.');

        $entry->refresh();
        $this->assertSame('Novi naslov', $entry->naslov);
        $this->assertSame('Novi opis', $entry->opis);
        $this->assertSame($this->categoryB->id, $entry->category_id);
        $this->assertSame('Ansambl', $entry->organizer_manual_name);
        $this->assertNull($entry->organizer_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->status);
        $this->assertSame($submittedAt, $entry->first_submitted_at?->toDateTimeString());
        $this->assertSame(0, CulturalEventChangeProposal::query()->where('event_entry_id', $entry->id)->count());
    }

    public function test_manual_organizer_add_update_remove_on_published(): void
    {
        $entry = $this->makePublishedDirect('Org manual');

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Org manual',
                'category_id' => $this->category->id,
                'organizer_manual_name' => 'Prvi',
            ])
            ->assertRedirect();

        $this->assertSame('Prvi', $entry->fresh()->organizer_manual_name);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Org manual',
                'category_id' => $this->category->id,
                'organizer_manual_name' => 'Drugi',
            ])
            ->assertRedirect();

        $this->assertSame('Drugi', $entry->fresh()->organizer_manual_name);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Org manual',
                'category_id' => $this->category->id,
                'organizer_manual_name' => '',
            ])
            ->assertRedirect();

        $this->assertNull($entry->fresh()->organizer_manual_name);
        $this->assertNull($entry->fresh()->organizer_id);
    }

    public function test_organizer_id_and_status_tamper_rejected(): void
    {
        $entry = $this->makePublishedDirect('Tamper');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Tamper',
                'category_id' => $this->category->id,
                'organizer_id' => $this->organizer->id,
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHasErrors('organizer_id');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Tamper',
                'category_id' => $this->category->id,
                'status' => CulturalEventEntry::STATUS_DRAFT,
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHasErrors('status');

        $entry->refresh();
        $this->assertNull($entry->organizer_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->status);
    }

    public function test_published_with_registered_organizer_remains_locked(): void
    {
        $entry = $this->makePublishedWithOrganizer('Sa Org');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.index'))
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Hak',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('domain');

        $this->assertSame('Sa Org', $entry->fresh()->naslov);

        $this->expectException(CulturalEventDomainException::class);
        $this->expectExceptionMessage('sadržajno read-only');
        $this->writer->updateContent($entry, $this->editor, ['naslov' => 'Domain hak']);
    }

    public function test_cancelled_and_archived_ordinary_edit_blocked(): void
    {
        $cancelled = $this->makePublishedDirect('Za otkaz');
        $this->lifecycle->cancel($cancelled, $this->editor, 'Razlog');
        $cancelled->refresh();

        $archived = $this->makePublishedDirect('Za arhivu');
        $archived->update([
            'status' => CulturalEventEntry::STATUS_ARCHIVED,
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $cancelled), [
                'naslov' => 'Ne',
                'category_id' => $this->category->id,
            ])
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $archived), [
                'naslov' => 'Ne',
                'category_id' => $this->category->id,
            ])
            ->assertSessionHasErrors('domain');

        $this->assertSame('Za otkaz', $cancelled->fresh()->naslov);
        $this->assertSame('Za arhivu', $archived->fresh()->naslov);
    }

    public function test_ui_edit_cta_and_delete_absence_for_published(): void
    {
        $direct = $this->makePublishedDirect('UI direct');
        $withOrg = $this->makePublishedWithOrganizer('UI org');
        $cancelled = $this->makePublishedDirect('UI cancel');
        $this->lifecycle->cancel($cancelled, $this->editor, 'Razlog');

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->assertSee('Uredi', false)
            ->assertSee('Upravljaj', false);

        $index = $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->getContent();

        $this->assertStringNotContainsString(
            'action="'.route('cultural-event-entries.destroy', $direct).'"',
            $index
        );
        $this->assertStringNotContainsString(
            'action="'.route('cultural-event-entries.destroy', $cancelled).'"',
            $index
        );

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $direct))
            ->assertOk()
            ->assertSee('Uredi događaj', false)
            ->assertSee('Status: Objavljen', false)
            ->assertSee('Sačuvaj izmjene', false)
            ->assertDontSee('Sačuvaj i nastavi', false)
            ->assertDontSee('Brisanje događaja', false)
            ->assertDontSee(route('cultural-event-entries.publish', $direct), false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $withOrg))
            ->assertOk()
            ->assertSee('Objavljen događaj', false)
            ->assertDontSee('Sačuvaj izmjene', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $cancelled))
            ->assertOk()
            ->assertDontSee('Sačuvaj izmjene', false)
            ->assertDontSee('Brisanje događaja', false);
    }

    public function test_public_canonical_show_sees_updated_title(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $entry = $this->makePublishedDirect('Javni stari');

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Javni novi',
                'opis' => 'Opis javni',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect();

        $this->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Javni novi', false);
    }

    public function test_moderator_cannot_use_direct_published_update(): void
    {
        $entry = $this->makePublishedDirect('Mod blocked');
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        $this->actingAs($this->moderator)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Hack',
                'category_id' => $this->category->id,
            ])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Hack',
                'category_id' => $this->category->id,
            ])
            ->assertForbidden();

        $this->assertSame('Mod blocked', $entry->fresh()->naslov);
    }

    private function makePublishedDirect(string $naslov): CulturalEventEntry
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

        return $entry->fresh();
    }

    private function makePublishedWithOrganizer(string $naslov): CulturalEventEntry
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
}
