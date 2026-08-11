<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalModeratorManifestationUiTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private User $modB;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private ManifestationWriter $mfWriter;

    private ManifestationLifecycle $mfLifecycle;

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
        $this->modA = User::factory()->create(['role_id' => $korisnikId, 'activation_status' => 'active']);
        $this->modB = User::factory()->create(['role_id' => $korisnikId, 'activation_status' => 'active']);

        $this->orgA = $this->makeOrganizer('Org A');
        $this->orgB = $this->makeOrganizer('Org B');
        $this->grant($this->modA, $this->orgA);
        $this->grant($this->modB, $this->orgB);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->mfWriter = app(ManifestationWriter::class);
        $this->mfLifecycle = app(ManifestationLifecycle::class);
    }

    public function test_context_required_and_scoped_index(): void
    {
        $this->grant($this->modA, $this->orgB);
        CulturalOrganizerContext::clear();

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-manifestations.index'))
            ->assertOk()
            ->assertSee('Izaberite aktivni Organizator');

        $own = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Own MF',
            'organizer_id' => $this->orgA->id,
        ]);
        $foreign = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Foreign MF',
            'organizer_id' => $this->orgB->id,
        ]);

        $this->setContext($this->modA, $this->orgA);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-manifestations.index'))
            ->assertOk()
            ->assertSee('Own MF')
            ->assertDontSee('Foreign MF');

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-manifestations.edit', $foreign))
            ->assertForbidden();

        $this->assertNotNull($own->fresh());
    }

    public function test_create_forces_organizer_and_rejects_tampering(): void
    {
        $this->setContext($this->modA, $this->orgA);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.store'), [
                'naziv' => 'Mod MF',
                'organizer_id' => $this->orgB->id,
            ])
            ->assertSessionHasErrors('organizer_id');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.store'), ['naziv' => 'Mod MF'])
            ->assertRedirect();

        $mf = CulturalManifestation::query()->where('naziv', 'Mod MF')->firstOrFail();
        $this->assertSame($this->orgA->id, $mf->organizer_id);
    }

    public function test_cross_org_and_null_org_event_linking_allowed(): void
    {
        $this->setContext($this->modA, $this->orgA);
        $mf = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'organizer_id' => $this->orgA->id,
        ]);

        $cross = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Cross org',
            'organizer_id' => $this->orgB->id,
        ]);
        $noOrg = $this->eventWriter->createDraft($this->editor, ['naslov' => 'No org']);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.events.link', $mf), ['event_entry_id' => $cross->id])
            ->assertRedirect();
        $this->assertSame($mf->id, $cross->fresh()->manifestation_id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.events.link', $mf), ['event_entry_id' => $noOrg->id])
            ->assertRedirect();
        $this->assertSame($mf->id, $noOrg->fresh()->manifestation_id);
    }

    public function test_submit_pending_lock_no_return_no_publish_returned_resubmit(): void
    {
        $this->setContext($this->modA, $this->orgA);
        $event = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'E',
            'organizer_id' => $this->orgA->id,
        ]);
        $mf = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'organizer_id' => $this->orgA->id,
            'event_entry_ids' => [$event->id],
        ]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.submit', $mf))
            ->assertRedirect();
        $this->assertTrue($mf->fresh()->isPendingApproval());

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-manifestations.update', $mf->fresh()), ['naziv' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->post(route('cultural-manifestations.return', $mf->fresh()))
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->post(route('cultural-manifestations.publish', $mf->fresh()))
            ->assertForbidden();

        $this->mfLifecycle->returnToRevision($mf->fresh(), $this->editor);
        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-manifestations.update', $mf->fresh()), [
                'naziv' => 'Returned edit',
            ])
            ->assertRedirect();
        $this->assertSame('Returned edit', $mf->fresh()->naziv);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.submit', $mf->fresh()))
            ->assertRedirect();
    }

    public function test_published_content_readonly_links_and_cancel_own_platform_denied(): void
    {
        $this->setContext($this->modA, $this->orgA);
        $published = $this->makePublishedEvent('Pub', $this->orgA->id);
        $extra = $this->makePublishedEvent('Extra', $this->orgA->id);

        $mf = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Pub MF',
            'organizer_id' => $this->orgA->id,
            'event_entry_ids' => [$published->id, $extra->id],
        ]);
        $this->mfLifecycle->submitForApproval($mf, $this->editor);
        $this->mfLifecycle->publish($mf->fresh(), $this->editor);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-manifestations.update', $mf->fresh()), ['naziv' => 'Hack'])
            ->assertForbidden();

        $free = $this->eventWriter->createDraft($this->editor, ['naslov' => 'Free link']);
        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.events.link', $mf->fresh()), [
                'event_entry_id' => $free->id,
            ])
            ->assertRedirect();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.cancel', $mf->fresh()))
            ->assertRedirect();
        $this->assertTrue($mf->fresh()->isCancelled());

        $platform = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Platform',
            'event_entry_ids' => [$this->makePublishedEvent('P2')->id],
        ]);
        $this->mfLifecycle->submitForApproval($platform, $this->editor);
        $this->mfLifecycle->publish($platform->fresh(), $this->editor);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.cancel', $platform->fresh()))
            ->assertForbidden();
    }

    public function test_cancelled_archived_new_link_rejected_for_moderator(): void
    {
        $this->setContext($this->modA, $this->orgA);
        $mf = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'organizer_id' => $this->orgA->id,
        ]);

        $cancelled = $this->makePublishedEvent('C');
        $this->eventLifecycle->cancel($cancelled->fresh(), $this->editor, 'x');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-manifestations.events.link', $mf), [
                'event_entry_id' => $cancelled->id,
            ])
            ->assertSessionHasErrors('domain');
    }

    private function setContext(User $mod, CulturalOrganizer $org): void
    {
        $this->actingAs($mod)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $org->id]);
    }

    private function grant(User $user, CulturalOrganizer $organizer): void
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

    private function makeOrganizer(string $name): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $name,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => $name,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    private function makePublishedEvent(string $title, ?int $organizerId = null): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Kat '.uniqid(),
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => $title,
            'category_id' => $category->id,
            'organizer_id' => $organizerId,
        ]);
        $this->occurrenceWriter->create($entry, ['datum' => '2026-09-01', 'cjelodnevno' => true]);

        if ($organizerId === null) {
            $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);
        } else {
            $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
            $this->eventLifecycle->approve($entry->fresh(), $this->editor);
        }

        return $entry->fresh();
    }
}
