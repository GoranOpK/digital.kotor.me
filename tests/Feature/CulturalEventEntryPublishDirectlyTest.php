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
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PO-DG-05 / BR-018 — Direktna objava Urednika (Nacrt bez Organizatora) + lock/re-check.
 */
class CulturalEventEntryPublishDirectlyTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private User $moderator;

    private CulturalCategory $category;

    private CulturalOrganizer $organizer;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

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

        $this->regularUser = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti Publish',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->organizer = $this->makeOrganizer('Org Publish');
        $this->grantModerator($this->moderator, $this->organizer);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
    }

    public function test_publish_directly_succeeds_for_draft_without_organizer(): void
    {
        [$entry, $occurrence] = $this->makeReadyDraftWithoutOrganizer('Direktna OK');

        $published = $this->lifecycle->publishDirectly($entry, $this->editor);

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $published->status);
        $this->assertNull($published->organizer_id);
        $this->assertSame($this->editor->id, $published->last_modified_by);
        $this->assertNotNull($published->first_submitted_at);
        $this->assertFalse((bool) $published->featured);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_publish_directly_rejects_when_organizer_present(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Sa Org',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->expectExceptionMessage('bez Organizatora');
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
    }

    public function test_publish_directly_rejects_non_draft_status(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('Pending path');
        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
    }

    public function test_publish_directly_rejects_missing_title_category_or_occurrence(): void
    {
        $noTitle = $this->writer->createDraft($this->editor, [
            'naslov' => null,
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($noTitle, [
            'datum' => now()->addDays(3)->toDateString(),
            'cjelodnevno' => true,
        ]);
        try {
            $this->lifecycle->publishDirectly($noTitle->fresh(), $this->editor);
            $this->fail('Expected rejection for empty title');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('Naslov', $e->getMessage());
        }

        $noCategory = $this->writer->createDraft($this->editor, [
            'naslov' => 'Bez kategorije',
            'category_id' => null,
        ]);
        $this->occurrenceWriter->create($noCategory, [
            'datum' => now()->addDays(3)->toDateString(),
            'cjelodnevno' => true,
        ]);
        try {
            $this->lifecycle->publishDirectly($noCategory->fresh(), $this->editor);
            $this->fail('Expected rejection for missing category');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('kategorij', mb_strtolower($e->getMessage()));
        }

        $inactive = CulturalCategory::create([
            'naziv' => 'Neaktivna',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $badCat = $this->writer->createDraft($this->editor, [
            'naslov' => 'Neaktivna kat',
            'category_id' => $inactive->id,
        ]);
        $this->occurrenceWriter->create($badCat, [
            'datum' => now()->addDays(3)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $inactive->update(['status' => CulturalCategory::STATUS_INACTIVE]);
        try {
            $this->lifecycle->publishDirectly($badCat->fresh(), $this->editor);
            $this->fail('Expected rejection for inactive category');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('aktivna', mb_strtolower($e->getMessage()));
        }

        $noOcc = $this->writer->createDraft($this->editor, [
            'naslov' => 'Bez odrzavanja',
            'category_id' => $this->category->id,
        ]);
        try {
            $this->lifecycle->publishDirectly($noOcc->fresh(), $this->editor);
            $this->fail('Expected rejection for missing occurrence');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('Održavanje', $e->getMessage());
        }
    }

    public function test_first_submitted_at_set_only_when_null(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('Submitted at');
        $fixed = now()->subDay()->startOfSecond();
        $entry->update(['first_submitted_at' => $fixed]);

        $published = $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);

        $this->assertTrue($published->first_submitted_at->equalTo($fixed));
    }

    public function test_stale_model_recheck_rejects_after_status_changed_in_db(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('Stale publish');
        $stale = CulturalEventEntry::query()->findOrFail($entry->id);

        CulturalEventEntry::query()->whereKey($entry->id)->update([
            'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->publishDirectly($stale, $this->editor);
    }

    public function test_stale_model_recheck_rejects_when_organizer_assigned_in_db(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('Stale org');
        $stale = CulturalEventEntry::query()->findOrFail($entry->id);
        $this->assertNull($stale->organizer_id);

        CulturalEventEntry::query()->whereKey($entry->id)->update([
            'organizer_id' => $this->organizer->id,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->expectExceptionMessage('bez Organizatora');
        $this->lifecycle->publishDirectly($stale, $this->editor);

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
        $this->assertSame($this->organizer->id, $entry->fresh()->organizer_id);
    }

    public function test_editor_http_publish_succeeds_and_leaves_du03(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('HTTP publish');

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                foreach ($cards as $card) {
                    if (($card['id'] ?? null) === 'DU-03') {
                        return (int) $card['count'] === 1;
                    }
                }

                return false;
            });

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.publish', $entry))
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->status);
        $this->assertNull($entry->organizer_id);

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                foreach ($cards as $card) {
                    if (($card['id'] ?? null) === 'DU-03') {
                        return (int) $card['count'] === 0;
                    }
                }

                return false;
            });
    }

    public function test_http_auth_blocks_regular_user_and_moderator(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('Auth publish');

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.publish', $entry))
            ->assertForbidden();

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->post(route('cultural-event-entries.publish', $entry))
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_http_rejects_draft_with_organizer(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'HTTP sa Org',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(4)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.publish', $entry))
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_edit_ui_shows_publish_only_without_organizer(): void
    {
        [$withoutOrg] = $this->makeReadyDraftWithoutOrganizer('UI bez Org');
        $withOrg = $this->writer->createDraft($this->editor, [
            'naslov' => 'UI sa Org',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $withoutOrg))
            ->assertOk()
            ->assertSee('Objavi', false)
            ->assertSee(route('cultural-event-entries.publish', $withoutOrg), false)
            ->assertDontSee('Pošalji na odobrenje', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $withOrg))
            ->assertOk()
            ->assertSee('Pošalji na odobrenje', false)
            ->assertDontSee(route('cultural-event-entries.publish', $withOrg), false);
    }

    public function test_published_via_direct_remains_compatible_with_cancel_and_featured(): void
    {
        [$entry] = $this->makeReadyDraftWithoutOrganizer('Compat cancel featured');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.publish', $entry))
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.featured', $entry), ['featured' => '1'])
            ->assertRedirect();

        $this->assertTrue((bool) $entry->fresh()->featured);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.cancel', $entry), [
                'cancellation_reason' => 'Otkaz nakon direktne objave',
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertFalse((bool) $entry->featured);
        $this->assertSame('Otkaz nakon direktne objave', $entry->cancellation_reason);
    }

    /**
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makeReadyDraftWithoutOrganizer(string $naslov): array
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $occurrence = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return [$entry->fresh(), $occurrence->fresh()];
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
