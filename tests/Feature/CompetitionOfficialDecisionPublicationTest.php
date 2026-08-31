<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class CompetitionOfficialDecisionPublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_signed_copy_delivers_the_exact_referenced_source_object(): void
    {
        $competition = $this->createCompletedCompetition();

        $first = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/first.bin',
        ]);
        $second = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/second.bin',
        ]);

        Storage::disk('local')->put($first->storage_path, 'FIRST-COPY-BYTES');
        Storage::disk('local')->put($second->storage_path, 'SECOND-COPY-BYTES');

        $notice = Notice::factory()->create([
            'title' => 'Objava drugog primjerka',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $second->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $this->assertNull($response->headers->get('Location'));
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame(
            'SECOND-COPY-BYTES',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
        $this->assertStringNotContainsString('FIRST-COPY-BYTES', $this->servedFileContents($response));
        $response->assertDontSee('ODLUKU', false);
    }

    public function test_signed_copy_is_delivered_to_guest_without_authentication(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/guest.bin',
        ]);
        Storage::disk('local')->put($copy->storage_path, 'GUEST-SIGNED-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $this->assertGuest();
        $this->assertSame('GUEST-SIGNED-COPY', $this->servedFileContents($response));
    }

    public function test_signed_copy_does_not_serve_when_source_object_id_is_missing(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/orphan.bin',
        ]);
        Storage::disk('local')->put($copy->storage_path, 'SHOULD-NOT-SERVE-WITHOUT-SOURCE-OBJECT');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => null,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'SHOULD-NOT-SERVE-WITHOUT-SOURCE-OBJECT');
    }

    public function test_signed_copy_does_not_serve_nonexistent_source_object(): void
    {
        $competition = $this->createCompletedCompetition();
        $existing = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/existing.bin',
        ]);
        $dangling = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/dangling.bin',
        ]);
        Storage::disk('local')->put($existing->storage_path, 'SHOULD-NOT-SERVE-LATEST-COPY');
        Storage::disk('local')->put($dangling->storage_path, 'SHOULD-NOT-SERVE-DELETED-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $dangling->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $missingId = $dangling->id;

        Schema::disableForeignKeyConstraints();
        try {
            $dangling->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->assertNull(CompetitionOfficialDecisionCopy::query()->find($missingId));

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'SHOULD-NOT-SERVE-LATEST-COPY');
        $this->assertStringNotContainsString('SHOULD-NOT-SERVE-DELETED-COPY', $response->getContent());
    }

    public function test_signed_copy_does_not_serve_copy_from_another_competition(): void
    {
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');

        $copyB = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competitionB->id,
            'storage_path' => 'competitions/decisions/competition-b.bin',
        ]);
        Storage::disk('local')->put($copyB->storage_path, 'COMPETITION-B-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competitionA->id,
            'source_object_id' => $copyB->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'COMPETITION-B-COPY');
    }

    public function test_signed_copy_does_not_serve_when_file_is_missing_from_disk(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/missing.bin',
        ]);

        $this->assertFalse(Storage::disk('local')->exists($copy->storage_path));

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response);
    }

    public function test_signed_copy_does_not_serve_when_notice_is_not_publicly_available(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/revoked.bin',
        ]);
        Storage::disk('local')->put($copy->storage_path, 'REVOKED-SIGNED-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => false,
            'publicly_available' => false,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'REVOKED-SIGNED-COPY');
    }

    private function assertSignedCopyDeliveryFailed($response, ?string $forbiddenBytes = null): void
    {
        $response->assertNotFound();
        $this->assertNull($response->headers->get('Location'));
        $this->assertNotInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $response->assertDontSee('ODLUKU', false);

        if ($forbiddenBytes !== null) {
            $this->assertStringNotContainsString($forbiddenBytes, $response->getContent());
        }
    }

    private function servedFileContents($response): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        return file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    private function createCompletedCompetition(string $title = 'Konkurs za potpisani primjerak'): Competition
    {
        return Competition::create([
            'title' => $title,
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
