<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalMedia;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalApplicator;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-010.3b — Occurrence ops unutar CulturalEventChangeProposal.
 */
class CulturalEventChangeProposalOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private User $modB;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private CulturalCategory $category;

    private CulturalTag $tag;

    private CulturalLocation $location;

    private EventChangeProposalWriter $writer;

    private EventChangeProposalLifecycle $lifecycle;

    private EventChangeProposalApplicator $applicator;

    private EventLifecycle $eventLifecycle;

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
        $this->modA = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
        $this->modB = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->orgA = $this->makeOrganizer('Org A OccProp');
        $this->orgB = $this->makeOrganizer('Org B OccProp');
        $this->grantModerator($this->modA, $this->orgA);
        $this->grantModerator($this->modB, $this->orgB);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti Occ',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $this->tag = CulturalTag::create([
            'naziv' => 'Jazz Occ',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);
        $this->location = CulturalLocation::create([
            'naziv' => 'Trg Occ',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventChangeProposalWriter::class);
        $this->lifecycle = app(EventChangeProposalLifecycle::class);
        $this->applicator = app(EventChangeProposalApplicator::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
    }

    public function test_proposal_occurrence_add_does_not_mutate_event_until_approve(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Add occ event');
        $beforeCount = $entry->occurrences()->count();
        $beforeNaslov = $entry->naslov;

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Naslov poslije',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);

        $op = $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => now()->addDays(20)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $this->assertSame(CulturalEventChangeProposalOccurrence::OPERATION_ADD, $op->operation);
        $this->assertNull($op->source_occurrence_id);

        $entry->refresh();
        $this->assertSame($beforeNaslov, $entry->naslov);
        $this->assertSame($beforeCount, $entry->occurrences()->count());
        $this->assertSame(1, $proposal->fresh()->occurrenceOps()->count());
    }

    public function test_proposal_occurrence_update_does_not_mutate_until_approve(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Update occ event');
        $occurrence = $entry->occurrences->first();
        $originalDatum = $occurrence->datum->toDateString();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $newDatum = now()->addDays(30)->toDateString();

        $op = $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $newDatum,
            'cjelodnevno' => true,
            'location_manual_name' => 'Nova sala',
        ]);

        $this->assertSame(CulturalEventChangeProposalOccurrence::OPERATION_UPDATE, $op->operation);
        $this->assertSame($occurrence->id, $op->source_occurrence_id);
        $this->assertSame($originalDatum, $occurrence->fresh()->datum->toDateString());
        $this->assertNull($occurrence->fresh()->location_manual_name);
    }

    public function test_approve_applies_occurrence_add_and_update(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Approve occ', [
            'opis' => 'Stari',
        ]);
        $occurrence = $entry->occurrences->first();
        $beforeCount = $entry->occurrences()->count();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Approve occ NEW',
            'proposed_opis' => 'Novi opis',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [$this->tag->id],
        ]);

        $updatedDatum = now()->addDays(40)->toDateString();
        $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $updatedDatum,
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $addDatum = now()->addDays(45)->toDateString();
        $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => $addDatum,
            'cjelodnevno' => true,
            'location_manual_name' => 'Dodata sala',
        ]);

        $this->assertSame('Approve occ', $entry->fresh()->naslov);
        $this->assertSame($beforeCount, $entry->fresh()->occurrences()->count());

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);
        $this->applicator->approve($proposal->fresh(), $this->editor);

        $entry->refresh();
        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $proposal->fresh()->status);
        $this->assertSame('Approve occ NEW', $entry->naslov);
        $this->assertSame('Novi opis', $entry->opis);
        $this->assertSame($beforeCount + 1, $entry->occurrences()->count());
        $this->assertSame($updatedDatum, $occurrence->fresh()->datum->toDateString());
        $this->assertSame($this->location->id, $occurrence->fresh()->location_id);
        $this->assertTrue(
            $entry->occurrences()->whereDate('datum', $addDatum)->where('location_manual_name', 'Dodata sala')->exists()
        );
    }

    public function test_published_occurrence_create_and_update_blocked_outside_proposal(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Direct lock');
        $occurrence = $entry->occurrences->first();

        try {
            $this->occurrenceWriter->create($entry, [
                'datum' => now()->addDays(12)->toDateString(),
                'cjelodnevno' => true,
            ]);
            $this->fail('Direct create on published should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('prijedlog', mb_strtolower($e->getMessage()));
        }

        try {
            $this->occurrenceWriter->update($occurrence, [
                'datum' => now()->addDays(15)->toDateString(),
                'cjelodnevno' => true,
            ]);
            $this->fail('Direct update on published should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('prijedlog', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(1, $entry->fresh()->occurrences()->count());
    }

    public function test_br012_still_one_active_proposal_with_occurrence_ops(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('BR012 occ');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => now()->addDays(22)->toDateString(),
            'cjelodnevno' => true,
        ]);

        try {
            $this->writer->createFromPublished($entry->fresh(), $this->modA);
            $this->fail('Second active proposal should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('aktivan prijedlog', mb_strtolower($e->getMessage()));
        }
    }

    public function test_g_w02_cancel_makes_proposal_with_occurrence_ops_inoperable(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('GW02 occ');
        $occurrence = $entry->occurrences->first();
        $originalDatum = $occurrence->datum->toDateString();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => now()->addDays(50)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => now()->addDays(51)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->submit($proposal->fresh(), $this->modA);

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Stop occ');
        $proposal->refresh();

        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame($originalDatum, $occurrence->fresh()->datum->toDateString());
        $this->assertSame(1, $entry->fresh()->occurrences()->count());

        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Approve after cancel should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_cross_org_moderator_cannot_mutate_proposal_occurrence_ops(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Cross org occ');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        CulturalOrganizerContext::set($this->modB, $this->orgB->id);

        $this->actingAs($this->modB)
            ->post(route('cultural-moderator-proposals.occurrences.store', $proposal), [
                'datum' => now()->addDays(18)->toDateString(),
                'cjelodnevno' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(0, $proposal->fresh()->occurrenceOps()->count());
    }

    public function test_add_with_active_location_allowed(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Add active loc');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        $op = $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => now()->addDays(19)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $this->assertSame($this->location->id, $op->proposed_location_id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-proposals.occurrences.store', $proposal), [
                'datum' => now()->addDays(20)->toDateString(),
                'cjelodnevno' => '1',
                'location_id' => $this->location->id,
            ])
            ->assertRedirect(route('cultural-moderator-proposals.edit', $proposal));
    }

    public function test_add_with_deactivated_location_rejected(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Add inactive loc');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        $inactive = CulturalLocation::create([
            'naziv' => 'Mrtva lokacija add',
            'status' => CulturalLocation::STATUS_DEACTIVATED,
        ]);

        try {
            $this->writer->addOccurrenceOp($proposal, $this->modA, [
                'datum' => now()->addDays(19)->toDateString(),
                'cjelodnevno' => true,
                'location_id' => $inactive->id,
            ]);
            $this->fail('Inactive location should be rejected on add');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('deaktiviran', mb_strtolower($e->getMessage()));
        }

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-proposals.occurrences.store', $proposal), [
                'datum' => now()->addDays(19)->toDateString(),
                'cjelodnevno' => '1',
                'location_id' => $inactive->id,
            ])
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(0, $proposal->fresh()->occurrenceOps()->count());
    }

    public function test_update_keeps_same_later_deactivated_location(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Keep deactivated', [
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();
        $this->assertSame($this->location->id, $occurrence->location_id);

        $this->location->update(['status' => CulturalLocation::STATUS_DEACTIVATED]);

        $proposal = $this->writer->createFromPublished($entry->fresh(), $this->modA);
        $newDatum = now()->addDays(33)->toDateString();

        $op = $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence->fresh(), [
            'datum' => $newDatum,
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $this->assertSame($this->location->id, $op->proposed_location_id);
        $this->assertSame($newDatum, $op->proposed_datum->toDateString());

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.occurrences.update-canonical', [$proposal, $occurrence]), [
                'datum' => now()->addDays(34)->toDateString(),
                'cjelodnevno' => '1',
                'location_id' => $this->location->id,
            ])
            ->assertRedirect(route('cultural-moderator-proposals.edit', $proposal))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_update_to_new_deactivated_location_rejected(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('New deactivated', [
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();

        $inactive = CulturalLocation::create([
            'naziv' => 'Nova mrtva',
            'status' => CulturalLocation::STATUS_DEACTIVATED,
        ]);

        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        try {
            $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
                'datum' => now()->addDays(35)->toDateString(),
                'cjelodnevno' => true,
                'location_id' => $inactive->id,
            ]);
            $this->fail('New deactivated location should be rejected');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('deaktiviran', mb_strtolower($e->getMessage()));
        }

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.occurrences.update-canonical', [$proposal, $occurrence]), [
                'datum' => now()->addDays(35)->toDateString(),
                'cjelodnevno' => '1',
                'location_id' => $inactive->id,
            ])
            ->assertSessionHasErrors('occurrence');
    }

    public function test_update_from_deactivated_to_new_active_location_allowed(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Swap to active', [
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();
        $this->location->update(['status' => CulturalLocation::STATUS_DEACTIVATED]);

        $newActive = CulturalLocation::create([
            'naziv' => 'Nova aktivna',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $proposal = $this->writer->createFromPublished($entry->fresh(), $this->modA);
        $op = $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence->fresh(), [
            'datum' => now()->addDays(36)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $newActive->id,
        ]);

        $this->assertSame($newActive->id, $op->proposed_location_id);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.occurrences.update-canonical', [$proposal, $occurrence]), [
                'datum' => now()->addDays(37)->toDateString(),
                'cjelodnevno' => '1',
                'location_id' => $newActive->id,
            ])
            ->assertRedirect(route('cultural-moderator-proposals.edit', $proposal));
    }

    public function test_approve_revalidates_new_location_link(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Approve revalidate loc', [
            'opis' => 'Original',
            'tag_ids' => [$this->tag->id],
        ]);
        $originalTagIds = $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Ne smije',
            'proposed_opis' => 'Ne smije opis',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);
        $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => now()->addDays(41)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->location->update(['status' => CulturalLocation::STATUS_DEACTIVATED]);

        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Approve should revalidate new location link');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('deaktiviran', mb_strtolower($e->getMessage()));
        }

        $entry->refresh();
        $proposal->refresh();
        $this->assertSame('Approve revalidate loc', $entry->naslov);
        $this->assertSame('Original', $entry->opis);
        $this->assertEqualsCanonicalizing($originalTagIds, $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(1, $entry->occurrences()->count());
        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->status);
        $this->assertSame($entry->id, $proposal->active_for_event_id);
    }

    public function test_partial_apply_is_rolled_back_when_occurrence_apply_fails(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Partial apply', [
            'opis' => 'Original opis',
            'tag_ids' => [$this->tag->id],
        ]);
        $beforeCount = $entry->occurrences()->count();
        $originalTagIds = $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Ne smije ostati',
            'proposed_opis' => 'Ne smije opis',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);
        $this->writer->addOccurrenceOp($proposal, $this->modA, [
            'datum' => now()->addDays(60)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        // Domain path: nova location veza postane nevalidna nakon submit/review,
        // approve primijeni Event snapshot pa padne na occurrence revalidaciji → full rollback.
        $this->location->update(['status' => CulturalLocation::STATUS_DEACTIVATED]);

        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Approve should fail when occurrence apply revalidation fails');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('deaktiviran', mb_strtolower($e->getMessage()));
        }

        $entry->refresh();
        $proposal->refresh();
        $this->assertSame('Partial apply', $entry->naslov);
        $this->assertSame('Original opis', $entry->opis);
        $this->assertEqualsCanonicalizing(
            $originalTagIds,
            $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame($beforeCount, $entry->occurrences()->count());
        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->status);
        $this->assertSame($entry->id, $proposal->active_for_event_id);
        $this->assertFalse($proposal->isApproved());
    }

    /**
     * @param  array{opis?: string, tag_ids?: list<int>, occurrence_location_id?: int}  $extra
     */
    private function makePublished(string $naslov, array $extra = []): CulturalEventEntry
    {
        $writer = app(EventWriter::class);
        $entry = $writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'opis' => $extra['opis'] ?? null,
            'category_id' => $this->category->id,
            'organizer_id' => $this->orgA->id,
            'tag_ids' => $extra['tag_ids'] ?? [],
        ]);

        $occurrencePayload = [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ];
        if (isset($extra['occurrence_location_id'])) {
            $occurrencePayload['location_id'] = $extra['occurrence_location_id'];
        }

        $this->occurrenceWriter->create($entry, $occurrencePayload);

        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return $entry->fresh(['tags', 'occurrences']);
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
