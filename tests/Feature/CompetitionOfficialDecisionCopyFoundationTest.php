<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompetitionOfficialDecisionCopyFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_and_notice_publication_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('competition_official_decision_copies'));
        $this->assertTrue(Schema::hasColumns('competition_official_decision_copies', [
            'id',
            'competition_id',
            'storage_path',
            'uploaded_by',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('notices', [
            'publicly_available',
            'superseded_notice_id',
            'source_object_id',
        ]));
        $this->assertTrue(Schema::hasColumn('notices', 'visible_in_active_panel'));
    }

    public function test_competition_can_have_multiple_official_decision_copies_with_distinct_identities(): void
    {
        $competition = $this->createCompetition();

        $first = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/first.bin',
        ]);
        $second = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/second.bin',
        ]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue($competition->officialDecisionCopies->contains($first));
        $this->assertTrue($competition->officialDecisionCopies->contains($second));
        $this->assertCount(2, $competition->officialDecisionCopies);
        $this->assertTrue($first->competition->is($competition));
        $this->assertTrue($second->competition->is($competition));
    }

    public function test_notice_can_reference_competition_id_and_exact_copy_id(): void
    {
        $competition = $this->createCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/signed.bin',
        ]);

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
        ]);

        $this->assertSame($competition->id, $notice->source_id);
        $this->assertSame($copy->id, $notice->source_object_id);
        $this->assertTrue($notice->sourceObject->is($copy));
        $this->assertNull($notice->storage_path ?? null);
        $this->assertArrayNotHasKey('storage_path', $notice->getAttributes());
    }

    public function test_legacy_notice_keeps_null_source_object_id(): void
    {
        $notice = Notice::factory()->create();

        $this->assertNull($notice->source_object_id);
        $this->assertSame('competition_decision_html', $notice->content_delivery);
        $this->assertNull($notice->sourceObject);
    }

    public function test_publicly_available_defaults_to_true(): void
    {
        $notice = Notice::factory()->create();

        $this->assertTrue($notice->publicly_available);
        $this->assertDatabaseHas('notices', [
            'id' => $notice->id,
            'publicly_available' => true,
        ]);
    }

    public function test_hidden_from_panel_does_not_revoke_public_availability(): void
    {
        $notice = Notice::factory()->hiddenFromPanel()->create();

        $this->assertFalse($notice->visible_in_active_panel);
        $this->assertTrue($notice->publicly_available);
    }

    public function test_notice_can_persist_predecessor_relation(): void
    {
        $predecessor = Notice::factory()->create([
            'title' => 'Prethodna objava',
        ]);
        $successor = Notice::factory()->create([
            'title' => 'Nova objava',
            'superseded_notice_id' => $predecessor->id,
        ]);

        $this->assertSame($predecessor->id, $successor->superseded_notice_id);
        $this->assertTrue($successor->supersededNotice->is($predecessor));
    }

    public function test_copy_uploaded_by_relation_is_optional(): void
    {
        $user = User::factory()->create();
        $competition = $this->createCompetition();

        $withUploader = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/with-uploader.bin',
            'uploaded_by' => $user->id,
        ]);
        $withoutUploader = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/anonymous.bin',
        ]);

        $this->assertTrue($withUploader->uploadedBy->is($user));
        $this->assertNull($withoutUploader->uploaded_by);
        $this->assertNull($withoutUploader->uploadedBy);
    }

    public function test_deleting_competition_is_restricted_while_copies_exist(): void
    {
        $competition = $this->createCompetition();
        CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/keep.bin',
        ]);

        $this->expectException(QueryException::class);
        $competition->delete();
    }

    private function createCompetition(): Competition
    {
        return Competition::create([
            'title' => 'Konkurs za foundation Odluke',
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
