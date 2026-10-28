<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use App\Models\ZenskoDecisionProposalSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ZpDecisionProposalSnapshotSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_can_be_created_with_valid_data(): void
    {
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $this->assertTrue(Schema::hasTable('zensko_decision_proposal_snapshots'));
        $this->assertTrue(Schema::hasColumns('zensko_decision_proposal_snapshots', [
            'id',
            'competition_id',
            'storage_path',
            'content_sha256',
            'closed_by',
            'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('zensko_decision_proposal_snapshots', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('competitions', 'zensko_decision_proposal_snapshot_id'));

        $snapshot = $this->createSnapshot();
        $snapshot->refresh();

        $this->assertNotNull($snapshot->id);
        $this->assertSame(64, strlen($snapshot->content_sha256));
        $this->assertNotNull($snapshot->created_at);
        $this->assertTrue($snapshot->closedBy->is(User::query()->findOrFail($snapshot->closed_by)));
    }

    public function test_competition_id_is_unique(): void
    {
        $snapshot = $this->createSnapshot();

        try {
            ZenskoDecisionProposalSnapshot::query()->create([
                'competition_id' => $snapshot->competition_id,
                'storage_path' => 'zensko-decision-proposals/duplicate.html',
                'content_sha256' => str_repeat('b', 64),
                'closed_by' => $snapshot->closed_by,
            ]);
            $this->fail('Drugi snimak za isti konkurs mora biti odbijen.');
        } catch (QueryException) {
            $this->assertSame(1, ZenskoDecisionProposalSnapshot::query()->count());
        }
    }

    public function test_snapshot_relation_points_at_its_competition(): void
    {
        $snapshot = $this->createSnapshot();

        $this->assertTrue($snapshot->competition->is(
            Competition::query()->findOrFail($snapshot->competition_id)
        ));
    }

    public function test_missing_competition_id_is_rejected(): void
    {
        $user = User::factory()->create();

        try {
            ZenskoDecisionProposalSnapshot::query()->create([
                'competition_id' => 999999999,
                'storage_path' => 'zensko-decision-proposals/missing-competition.html',
                'content_sha256' => str_repeat('c', 64),
                'closed_by' => $user->id,
            ]);
            $this->fail('Nepostojeći konkurs mora biti odbijen.');
        } catch (QueryException) {
            $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        }
    }

    public function test_missing_closed_by_is_rejected(): void
    {
        $competition = $this->createCompetition();

        try {
            ZenskoDecisionProposalSnapshot::query()->create([
                'competition_id' => $competition->id,
                'storage_path' => 'zensko-decision-proposals/missing-user.html',
                'content_sha256' => str_repeat('d', 64),
                'closed_by' => 999999999,
            ]);
            $this->fail('Nepostojeći korisnik mora biti odbijen.');
        } catch (QueryException) {
            $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        }
    }

    public function test_existing_snapshot_cannot_be_updated_through_eloquent(): void
    {
        $snapshot = $this->createSnapshot();
        $originalPath = $snapshot->storage_path;

        try {
            $snapshot->update(['storage_path' => 'zensko-decision-proposals/tampered.html']);
            $this->fail('Izmjena snimka mora biti odbijena.');
        } catch (LogicException $exception) {
            $this->assertSame('Snimak Predloga odluke ne može da se mijenja.', $exception->getMessage());
        }

        $this->assertSame($originalPath, $snapshot->fresh()->storage_path);
    }

    public function test_existing_snapshot_cannot_be_deleted_through_eloquent(): void
    {
        $snapshot = $this->createSnapshot();

        try {
            $snapshot->delete();
            $this->fail('Brisanje snimka mora biti odbijeno.');
        } catch (LogicException $exception) {
            $this->assertSame('Snimak Predloga odluke ne može da se briše.', $exception->getMessage());
        }

        $this->assertDatabaseHas('zensko_decision_proposal_snapshots', [
            'id' => $snapshot->id,
        ]);
    }

    public function test_existing_competition_without_snapshot_keeps_null(): void
    {
        $competition = $this->createCompetition();

        $this->assertNull($competition->fresh()->zensko_decision_proposal_snapshot_id);
        $this->assertNull($competition->zenskoDecisionProposalSnapshot);
    }

    public function test_competition_can_be_linked_through_the_new_column(): void
    {
        $snapshot = $this->createSnapshot();
        $competition = $snapshot->competition;
        $competition->zensko_decision_proposal_snapshot_id = $snapshot->id;
        $competition->save();

        $linked = $competition->fresh();

        $this->assertSame($snapshot->id, $linked->zensko_decision_proposal_snapshot_id);
        $this->assertTrue($linked->zenskoDecisionProposalSnapshot->is($snapshot));
    }

    private function createSnapshot(): ZenskoDecisionProposalSnapshot
    {
        $competition = $this->createCompetition();
        $user = User::factory()->create();

        return ZenskoDecisionProposalSnapshot::query()->create([
            'competition_id' => $competition->id,
            'storage_path' => 'zensko-decision-proposals/'.$competition->id.'/proposal.html',
            'content_sha256' => str_repeat('a', 64),
            'closed_by' => $user->id,
        ]);
    }

    private function createCompetition(): Competition
    {
        return Competition::query()->create([
            'title' => 'Žensko preduzetništvo — struktura snimka',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
        ]);
    }
}
