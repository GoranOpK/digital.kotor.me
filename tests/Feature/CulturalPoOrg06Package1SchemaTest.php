<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PO-ORG-06 Package 1 — schema / domain foundation (no privacy UI / resolver / mail).
 */
class CulturalPoOrg06Package1SchemaTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $submitter;

    private User $boundModerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->submitter = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->boundModerator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email' => 'bound.moderator@example.com',
            'name' => 'Bound Moderator',
        ]);
    }

    public function test_migration_adds_privacy_safe_columns_and_nullable_bindings(): void
    {
        $this->assertTrue(Schema::hasColumn('cultural_organizer_creation_requests', 'proposed_moderator_name'));
        $this->assertTrue(Schema::hasColumn('cultural_organizer_creation_requests', 'proposed_moderator_email'));
        $this->assertTrue(Schema::hasColumn('cultural_moderator_requests', 'proposed_moderator_name'));
        $this->assertTrue(Schema::hasColumn('cultural_moderator_requests', 'proposed_moderator_email'));

        $this->assertSame('YES', $this->columnNullability('cultural_organizer_creation_requests', 'proposed_moderator_user_id'));
        $this->assertSame('YES', $this->columnNullability('cultural_moderator_requests', 'target_user_id'));
        $this->assertSame('YES', $this->columnNullability('cultural_organizer_creation_requests', 'proposed_moderator_name'));
        $this->assertSame('YES', $this->columnNullability('cultural_organizer_creation_requests', 'proposed_moderator_email'));

        $this->assertTrue($this->indexExists('cultural_organizer_creation_requests', 'cocr_pmod_email_status_idx'));
        $this->assertTrue($this->indexExists('cultural_moderator_requests', 'cmr_pmod_email_status_idx'));
        $this->assertTrue($this->indexExists('cultural_moderator_requests', 'cmr_org_pmod_email_status_idx'));

        $this->assertTrue($this->foreignKeyExists('cultural_organizer_creation_requests', 'cocr_proposed_mod_fk'));
        $this->assertTrue($this->foreignKeyExists('cultural_moderator_requests', 'cmr_target_fk'));
    }

    public function test_organizer_request_can_persist_unbound_awaiting_row(): void
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => null,
            'proposed_moderator_name' => 'Nova Moderatorica',
            'proposed_moderator_email' => 'nova.moderatorica@example.com',
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Privacy Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);

        $fresh = $request->fresh();

        $this->assertNull($fresh->proposed_moderator_user_id);
        $this->assertSame('Nova Moderatorica', $fresh->proposed_moderator_name);
        $this->assertSame('nova.moderatorica@example.com', $fresh->proposed_moderator_email);
        $this->assertSame(
            CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
            $fresh->status
        );
        $this->assertTrue($fresh->isAwaitingModeratorEligibility());
        $this->assertFalse($fresh->isSubmitted());
        $this->assertNull($fresh->proposedModerator);
        $this->assertSame('Čeka registraciju Moderatora', $fresh->statusLabel());
    }

    public function test_moderator_add_request_can_persist_unbound_awaiting_row(): void
    {
        $organizer = $this->approveOrganizer();

        $request = CulturalModeratorRequest::create([
            'organizer_id' => $organizer->id,
            'submitter_user_id' => $this->boundModerator->id,
            'target_user_id' => null,
            'proposed_moderator_name' => 'Add Candidate',
            'proposed_moderator_email' => 'add.candidate@example.com',
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);

        $fresh = $request->fresh();

        $this->assertNull($fresh->target_user_id);
        $this->assertSame('Add Candidate', $fresh->proposed_moderator_name);
        $this->assertSame('add.candidate@example.com', $fresh->proposed_moderator_email);
        $this->assertSame(CulturalModeratorRequest::TYPE_ADD, $fresh->type);
        $this->assertTrue($fresh->isAwaitingModeratorEligibility());
        $this->assertNull($fresh->targetUser);
    }

    public function test_existing_resolved_organizer_and_moderator_rows_still_work(): void
    {
        $organizerRequest = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->boundModerator->id,
            'proposed_moderator_name' => null,
            'proposed_moderator_email' => null,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Legacy Bound Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);

        $this->assertSame($this->boundModerator->id, $organizerRequest->fresh()->proposed_moderator_user_id);
        $this->assertTrue($organizerRequest->proposedModerator()->is($this->boundModerator));
        $this->assertTrue($organizerRequest->isSubmitted());
        $this->assertFalse($organizerRequest->isAwaitingModeratorEligibility());

        $organizer = app(OrganizerCreationDecisionService::class)
            ->approve($organizerRequest, $this->editor);

        $moderatorRequest = CulturalModeratorRequest::create([
            'organizer_id' => $organizer->id,
            'submitter_user_id' => $this->boundModerator->id,
            'target_user_id' => $this->submitter->id,
            'proposed_moderator_name' => null,
            'proposed_moderator_email' => null,
            'type' => CulturalModeratorRequest::TYPE_ADD,
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
        ]);

        $this->assertSame($this->submitter->id, $moderatorRequest->fresh()->target_user_id);
        $this->assertTrue($moderatorRequest->targetUser()->is($this->submitter));
        $this->assertNull($moderatorRequest->proposed_moderator_email);
    }

    public function test_legacy_statuses_and_awaiting_status_persist(): void
    {
        foreach ([
            CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
            CulturalOrganizerCreationRequest::STATUS_APPROVED,
            CulturalOrganizerCreationRequest::STATUS_REJECTED,
            CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ] as $status) {
            $row = CulturalOrganizerCreationRequest::create([
                'submitter_user_id' => $this->submitter->id,
                'proposed_moderator_user_id' => $status === CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
                    ? null
                    : $this->boundModerator->id,
                'proposed_moderator_name' => $status === CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
                    ? 'Waiting Name'
                    : null,
                'proposed_moderator_email' => $status === CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
                    ? 'waiting@example.com'
                    : null,
                'proposed_moderator_is_submitter' => false,
                'proposed_naziv' => 'Status '.$status,
                'status' => $status,
            ]);

            $this->assertSame($status, $row->fresh()->status);
        }
    }

    public function test_fk_restrict_still_blocks_delete_of_bound_user(): void
    {
        CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->boundModerator->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'FK Guard Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->boundModerator->delete();
    }

    public function test_approved_and_rejected_remain_terminal_via_existing_helpers(): void
    {
        $approved = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->boundModerator->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Approved Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        $rejected = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->boundModerator->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Rejected Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
            'decision_note' => 'Razlog',
        ]);

        $this->assertFalse($approved->isSubmitted());
        $this->assertFalse($approved->isAwaitingModeratorEligibility());
        $this->assertFalse($rejected->isSubmitted());
        $this->assertFalse($rejected->isAwaitingModeratorEligibility());
        $this->assertSame('Odobren', $approved->statusLabel());
        $this->assertSame('Odbijen', $rejected->statusLabel());
    }

    private function approveOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->submitter->id,
            'proposed_moderator_user_id' => $this->boundModerator->id,
            'proposed_moderator_is_submitter' => false,
            'proposed_naziv' => 'Schema Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);

        return app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);
    }

    private function columnNullability(string $table, string $column): string
    {
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $table);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $column);

        $row = collect(DB::select('SHOW COLUMNS FROM `'.$table.'`'))
            ->first(fn (object $col): bool => $col->Field === $column);

        $this->assertNotNull($row);

        return $row->Null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $table);

        $rows = collect(DB::select('SHOW INDEX FROM `'.$table.'`'))
            ->where('Key_name', $indexName);

        return $rows->isNotEmpty();
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$table, $constraintName, 'FOREIGN KEY']
        );

        return $row !== null;
    }
}
