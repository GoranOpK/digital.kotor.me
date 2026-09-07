<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\CompetitionOfficialDecisionLifecycleEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class CompetitionOfficialDecisionLifecycleAuditFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_event_table_exists_without_updated_at(): void
    {
        $this->assertTrue(Schema::hasTable('competition_official_decision_lifecycle_events'));
        $this->assertTrue(Schema::hasColumns('competition_official_decision_lifecycle_events', [
            'id',
            'competition_official_decision_copy_id',
            'competition_id',
            'action',
            'actor_user_id',
            'payload',
            'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('competition_official_decision_lifecycle_events', 'updated_at'));
    }

    public function test_lifecycle_event_can_be_created_with_structured_payload_and_relations(): void
    {
        $actor = User::factory()->create();
        $competition = $this->createCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/audited.bin',
        ]);

        $event = CompetitionOfficialDecisionLifecycleEvent::create([
            'competition_official_decision_copy_id' => $copy->id,
            'competition_id' => $competition->id,
            'action' => 'foundation_probe',
            'actor_user_id' => $actor->id,
            'payload' => [
                'business_title' => [
                    'from' => 'Stari naziv',
                    'to' => 'Novi naziv',
                ],
            ],
        ]);

        $event->refresh();

        $this->assertSame($copy->id, $event->competition_official_decision_copy_id);
        $this->assertSame($competition->id, $event->competition_id);
        $this->assertSame('foundation_probe', $event->action);
        $this->assertSame($actor->id, $event->actor_user_id);
        $this->assertSame('Stari naziv', $event->payload['business_title']['from']);
        $this->assertSame('Novi naziv', $event->payload['business_title']['to']);
        $this->assertNotNull($event->created_at);
        $this->assertTrue($event->copy->is($copy));
        $this->assertTrue($event->competition->is($competition));
        $this->assertTrue($event->actor->is($actor));
        $this->assertTrue($copy->lifecycleEvents->contains($event));
    }

    public function test_lifecycle_event_cannot_be_updated_through_the_model_api(): void
    {
        $event = $this->createEvent();

        try {
            $event->update(['action' => 'tampered']);
            $this->fail('Lifecycle audit update must be rejected.');
        } catch (LogicException $e) {
            $this->assertSame('KN official decision lifecycle events are append-only.', $e->getMessage());
        }

        $this->assertSame('foundation_probe', $event->fresh()->action);
    }

    public function test_lifecycle_event_cannot_be_deleted_through_the_model_api(): void
    {
        $event = $this->createEvent();

        try {
            $event->delete();
            $this->fail('Lifecycle audit delete must be rejected.');
        } catch (LogicException $e) {
            $this->assertSame('KN official decision lifecycle events are append-only.', $e->getMessage());
        }

        $this->assertDatabaseHas('competition_official_decision_lifecycle_events', [
            'id' => $event->id,
            'action' => 'foundation_probe',
        ]);
    }

    private function createEvent(): CompetitionOfficialDecisionLifecycleEvent
    {
        $actor = User::factory()->create();
        $competition = $this->createCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/append-only.bin',
        ]);

        return CompetitionOfficialDecisionLifecycleEvent::create([
            'competition_official_decision_copy_id' => $copy->id,
            'competition_id' => $competition->id,
            'action' => 'foundation_probe',
            'actor_user_id' => $actor->id,
            'payload' => ['probe' => true],
        ]);
    }

    private function createCompetition(): Competition
    {
        return Competition::create([
            'title' => 'Konkurs za lifecycle audit foundation',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'completed',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'closed_at' => now()->subDay(),
        ]);
    }
}
