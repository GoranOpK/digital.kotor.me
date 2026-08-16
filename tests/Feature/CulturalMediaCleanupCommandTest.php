<?php

namespace Tests\Feature;

use App\Models\CulturalMedia;
use App\Services\CulturalMedia\CulturalMediaCleanup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CulturalMediaCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_empty_db_and_folder_has_zero_orphans(): void
    {
        $report = app(CulturalMediaCleanup::class)->inspect();

        $this->assertSame(0, $report->dbReferences);
        $this->assertSame(0, $report->physicalFiles);
        $this->assertSame([], $report->filesystemOrphans);
        $this->assertSame([], $report->missingFileRows);
        $this->assertFalse($report->applied);
        $this->assertSame([], $report->deleted);
    }

    public function test_valid_db_row_and_file_is_not_orphan(): void
    {
        Storage::disk('public')->put('cultural-media/kept.jpg', 'ok');
        $this->makeMedia('cultural-media/kept.jpg');

        $report = app(CulturalMediaCleanup::class)->inspect();

        $this->assertSame(1, $report->dbReferences);
        $this->assertSame(1, $report->physicalFiles);
        $this->assertSame([], $report->filesystemOrphans);
    }

    public function test_physical_file_without_db_row_is_orphan(): void
    {
        Storage::disk('public')->put('cultural-media/orphan.jpg', 'x');

        $report = app(CulturalMediaCleanup::class)->inspect();

        $this->assertSame(['cultural-media/orphan.jpg'], $report->filesystemOrphans);
    }

    public function test_default_command_is_dry_run_and_does_not_delete(): void
    {
        Storage::disk('public')->put('cultural-media/orphan.jpg', 'x');

        $this->artisan('cultural-media:cleanup')
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('Deleted: 0')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('cultural-media/orphan.jpg');
    }

    public function test_delete_flag_removes_orphan_and_keeps_valid_file(): void
    {
        Storage::disk('public')->put('cultural-media/orphan.jpg', 'x');
        Storage::disk('public')->put('cultural-media/kept.jpg', 'ok');
        $this->makeMedia('cultural-media/kept.jpg');

        $this->artisan('cultural-media:cleanup', ['--delete' => true])
            ->expectsOutputToContain('DELETE MODE')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('cultural-media/orphan.jpg');
        Storage::disk('public')->assertExists('cultural-media/kept.jpg');
        $this->assertDatabaseCount('cultural_media', 1);
    }

    public function test_multiple_orphans_are_reported_and_deleted(): void
    {
        Storage::disk('public')->put('cultural-media/a.jpg', 'a');
        Storage::disk('public')->put('cultural-media/b.jpg', 'b');

        $report = app(CulturalMediaCleanup::class)->apply();

        $this->assertSame(['cultural-media/a.jpg', 'cultural-media/b.jpg'], $report->filesystemOrphans);
        $this->assertSame(['cultural-media/a.jpg', 'cultural-media/b.jpg'], $report->deleted);
        Storage::disk('public')->assertMissing('cultural-media/a.jpg');
        Storage::disk('public')->assertMissing('cultural-media/b.jpg');
    }

    public function test_one_delete_failure_does_not_stop_other_orphans(): void
    {
        Storage::disk('public')->put('cultural-media/fail.jpg', 'f');
        Storage::disk('public')->put('cultural-media/ok.jpg', 'o');

        $cleanup = new class extends CulturalMediaCleanup
        {
            protected function deletePublicFile(string $relative): bool
            {
                if ($relative === 'cultural-media/fail.jpg') {
                    return false;
                }

                return parent::deletePublicFile($relative);
            }
        };

        $report = $cleanup->apply();

        $this->assertContains('cultural-media/fail.jpg', $report->deleteFailures);
        $this->assertContains('cultural-media/ok.jpg', $report->deleted);
        Storage::disk('public')->assertExists('cultural-media/fail.jpg');
        Storage::disk('public')->assertMissing('cultural-media/ok.jpg');
    }

    public function test_db_row_with_missing_file_is_anomaly_and_row_is_kept(): void
    {
        $this->makeMedia('cultural-media/missing.jpg');

        $report = app(CulturalMediaCleanup::class)->apply();

        $this->assertSame(['cultural-media/missing.jpg'], $report->missingFileRows);
        $this->assertSame([], $report->deleted);
        $this->assertDatabaseCount('cultural_media', 1);
    }

    public function test_suspicious_db_path_is_reported_and_not_used_as_delete_target(): void
    {
        Storage::disk('public')->put('cultural-events/legacy.jpg', 'legacy');
        $this->makeMedia('../secret.jpg');
        $this->makeMedia('cultural-events/legacy.jpg');
        $this->makeMedia('/tmp/abs.jpg');

        $report = app(CulturalMediaCleanup::class)->apply();

        $this->assertCount(3, $report->suspiciousDbPaths);
        $this->assertSame([], $report->deleted);
        Storage::disk('public')->assertExists('cultural-events/legacy.jpg');
        $this->assertDatabaseCount('cultural_media', 3);
    }

    public function test_outside_folders_and_private_disk_are_untouched(): void
    {
        Storage::disk('public')->put('cultural-events/keep.jpg', 'e');
        Storage::disk('public')->put('documents/keep.pdf', 'd');
        Storage::disk('local')->put('private-keep.txt', 'p');
        Storage::disk('public')->put('cultural-media/orphan.jpg', 'o');

        $this->artisan('cultural-media:cleanup', ['--delete' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing('cultural-media/orphan.jpg');
        Storage::disk('public')->assertExists('cultural-events/keep.jpg');
        Storage::disk('public')->assertExists('documents/keep.pdf');
        Storage::disk('local')->assertExists('private-keep.txt');
    }

    public function test_delete_command_fails_when_a_delete_fails(): void
    {
        Storage::disk('public')->put('cultural-media/fail.jpg', 'f');

        $this->app->instance(CulturalMediaCleanup::class, new class extends CulturalMediaCleanup
        {
            protected function deletePublicFile(string $relative): bool
            {
                return false;
            }
        });

        $this->artisan('cultural-media:cleanup', ['--delete' => true])->assertFailed();
        Storage::disk('public')->assertExists('cultural-media/fail.jpg');
    }

    private function makeMedia(string $storagePath): CulturalMedia
    {
        return CulturalMedia::create([
            'naziv' => 'Row',
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => basename($storagePath),
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 10,
            'storage_path' => $storagePath,
        ]);
    }
}
