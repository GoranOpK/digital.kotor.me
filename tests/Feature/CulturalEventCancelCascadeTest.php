<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PO-AUTO-01 — cascade otkazivanja Održavanja pri otkazivanju Događaja.
 */
class CulturalEventCancelCascadeTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

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

        $this->category = CulturalCategory::create([
            'naziv' => 'Teatar',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_cancel_cascades_open_occurrences_and_preserves_terminal(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Cascade A',
            'category_id' => $this->category->id,
        ]);

        $planned = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $postponed = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(11)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $finished = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(12)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $cancelled = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(13)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->occurrenceLifecycle->postpone($postponed->fresh());
        $this->occurrenceLifecycle->markFinished($finished->fresh());
        $this->occurrenceLifecycle->cancel($cancelled->fresh());

        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Cijeli program');

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $planned->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $postponed->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $finished->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $cancelled->fresh()->status);
    }

    public function test_cancel_with_active_proposal_keeps_g_w02_and_cascades(): void
    {
        $mod = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $request = \App\Models\CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $mod->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Org Cascade',
            'status' => \App\Models\CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);
        $org = CulturalOrganizer::create([
            'naziv' => 'Org Cascade',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
        \App\Models\CulturalModeratorAuthorization::query()->updateOrCreate(
            [
                'user_id' => $mod->id,
                'organizer_id' => $org->id,
            ],
            [
                'status' => \App\Models\CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => \App\Models\CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]
        );

        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Sa prijedlogom',
            'category_id' => $this->category->id,
            'organizer_id' => $org->id,
        ]);
        $occurrence = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(20)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->lifecycle->approve($entry->fresh(), $this->editor);

        CulturalOrganizerContext::set($mod, $org->id);
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry->fresh(), $mod);
        $this->assertNotNull($proposal->active_for_event_id);

        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Stop + G-W02');

        $proposal->refresh();
        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
    }

    public function test_editor_http_cancel_cascades_occurrences(): void
    {
        $entry = $this->makePublished('HTTP editor cancel');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.cancel', $entry), [
                'cancellation_reason' => 'HTTP cascade',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
    }

    public function test_cancel_transaction_rolls_back_when_save_fails_after_event_cancel(): void
    {
        $entry = $this->makePublished('Rollback');
        $occurrence = $entry->occurrences()->firstOrFail();

        DB::listen(function ($query) use ($occurrence): void {
            if (
                str_contains(strtolower($query->sql), 'update `cultural_occurrences`')
                && (int) ($query->bindings[array_key_last($query->bindings)] ?? 0) === (int) $occurrence->id
            ) {
                throw new \RuntimeException('Simulirani pad cascade save.');
            }
        });

        try {
            $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Ne smije ostati djelimično');
            $this->fail('Očekivan RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Simulirani pad', $e->getMessage());
        }

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
}
