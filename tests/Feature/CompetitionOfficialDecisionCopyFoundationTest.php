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
            'business_title',
            'business_published_on',
            'permanent_delete_pending_at',
            'permanently_deleted_at',
            'permanently_deleted_by',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('notices', [
            'publicly_available',
            'superseded_notice_id',
            'source_object_id',
            'public_display_date',
        ]));
        $this->assertTrue(Schema::hasColumn('notices', 'visible_in_active_panel'));
        $this->assertTrue(Schema::hasColumn('notices', 'published_at'));
        $this->assertTrue(Schema::hasTable('competition_official_decision_lifecycle_events'));
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

    public function test_copy_can_still_be_created_with_only_original_attributes(): void
    {
        $competition = $this->createCompetition();

        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/legacy-shape.bin',
        ]);

        $this->assertNotNull($copy->id);
        $this->assertSame('competitions/decisions/legacy-shape.bin', $copy->storage_path);
        $this->assertNull($copy->business_title);
        $this->assertNull($copy->business_published_on);
        $this->assertNull($copy->permanent_delete_pending_at);
        $this->assertNull($copy->permanently_deleted_at);
        $this->assertNull($copy->permanently_deleted_by);
    }

    public function test_copy_nullable_foundation_attributes_cast_correctly(): void
    {
        $actor = User::factory()->create();
        $competition = $this->createCompetition();
        $pendingAt = now()->subMinutes(5);
        $deletedAt = now()->subMinute();

        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/cast.bin',
            'business_title' => 'Odluka o dodjeli sredstava 2026',
            'business_published_on' => '2026-08-20',
            'permanent_delete_pending_at' => $pendingAt,
            'permanently_deleted_at' => $deletedAt,
            'permanently_deleted_by' => $actor->id,
        ]);

        $copy->refresh();

        $this->assertSame('Odluka o dodjeli sredstava 2026', $copy->business_title);
        $this->assertSame('2026-08-20', $copy->business_published_on?->toDateString());
        $this->assertSame('00:00:00', $copy->business_published_on?->format('H:i:s'));
        $this->assertSame('date', Schema::getColumnType('competition_official_decision_copies', 'business_published_on'));
        $this->assertSame($pendingAt->format('Y-m-d H:i:s'), $copy->permanent_delete_pending_at->format('Y-m-d H:i:s'));
        $this->assertSame($deletedAt->format('Y-m-d H:i:s'), $copy->permanently_deleted_at->format('Y-m-d H:i:s'));
        $this->assertTrue($copy->permanentlyDeletedBy->is($actor));
        $this->assertNotSame(
            $copy->permanent_delete_pending_at->format('Y-m-d H:i:s'),
            $copy->permanently_deleted_at->format('Y-m-d H:i:s')
        );
    }

    public function test_storage_path_can_be_null_on_a_persisted_copy(): void
    {
        $competition = $this->createCompetition();

        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => null,
        ]);

        $this->assertNull($copy->fresh()->storage_path);
        $this->assertDatabaseHas('competition_official_decision_copies', [
            'id' => $copy->id,
            'storage_path' => null,
        ]);
    }

    public function test_pending_and_permanently_deleted_are_separate_attributes(): void
    {
        $competition = $this->createCompetition();

        $pendingOnly = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/pending.bin',
            'permanent_delete_pending_at' => now(),
        ]);
        $tombstone = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => null,
            'permanently_deleted_at' => now(),
        ]);

        $this->assertNotNull($pendingOnly->permanent_delete_pending_at);
        $this->assertNull($pendingOnly->permanently_deleted_at);
        $this->assertNotNull($pendingOnly->storage_path);
        $this->assertNull($tombstone->permanent_delete_pending_at);
        $this->assertNotNull($tombstone->permanently_deleted_at);
        $this->assertNull($tombstone->storage_path);
    }

    public function test_notice_public_display_date_is_nullable_and_does_not_overwrite_published_at(): void
    {
        $publishedAt = now()->subHours(3);

        $withoutDisplayDate = Notice::factory()->create([
            'published_at' => $publishedAt,
        ]);
        $withDisplayDate = Notice::factory()->create([
            'published_at' => $publishedAt,
            'public_display_date' => '2026-08-15',
        ]);

        $this->assertNull($withoutDisplayDate->public_display_date);
        $this->assertSame('date', Schema::getColumnType('notices', 'public_display_date'));
        $this->assertSame('2026-08-15', $withDisplayDate->public_display_date?->toDateString());
        $this->assertSame($publishedAt->format('Y-m-d H:i:s'), $withoutDisplayDate->published_at->format('Y-m-d H:i:s'));
        $this->assertSame($publishedAt->format('Y-m-d H:i:s'), $withDisplayDate->published_at->format('Y-m-d H:i:s'));
        $this->assertContains(Schema::getColumnType('notices', 'published_at'), ['datetime', 'timestamp']);
        $this->assertNotSame(
            $withDisplayDate->public_display_date?->toDateString(),
            $withDisplayDate->published_at->toDateTimeString()
        );
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
