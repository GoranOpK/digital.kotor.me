<?php

namespace Tests\Feature;

use App\Contracts\MegaArchiveClient;
use App\Jobs\ProcessDocumentJob;
use App\Models\ExternalFileArchive;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use App\Services\ExternalArchive\MegaUploadResult;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MegaArchiveFakeClient;
use Tests\TestCase;

class DocumentLibraryStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $role = Role::where('name', 'korisnik')->firstOrFail();
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'used_storage_bytes' => 0,
        ]);

        Storage::fake('local');
    }

    public function test_flag_off_does_not_dispatch_archive_and_keeps_legacy_sync_path(): void
    {
        config(['external_archive.library_upload' => false]);
        Bus::fake();

        $outPath = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($outPath, 'pdf');

        $this->mock(DocumentProcessor::class, function ($mock) use ($outPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('processDocument')->andReturn([
                'success' => true,
                'file_path' => $outPath,
                'file_size' => 100,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Test doc',
        ]);

        $response->assertRedirect(route('documents.index'));
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('external_file_archives', 0);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'status' => 'processed',
        ]);
    }

    public function test_flag_on_always_dispatches_process_document_job(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Queued doc',
        ]);

        $response->assertRedirect(route('documents.index'));
        Bus::assertDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_flag_on_json_request_returns_document_id(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'JSON doc',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('queued', true)
            ->assertJsonStructure(['document_id', 'status', 'message']);

        Bus::assertDispatched(ProcessDocumentJob::class);
    }

    public function test_flag_on_html_redirect_uses_same_flash_message_as_legacy_queue(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'HTML queued',
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success', 'Dokument je uspješno upload-ovan. Obrada je u toku.');
    }

    public function test_multi_file_merge_with_flag_on_dispatches_archive_only_job_not_sync_mega(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $files = [
            UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
        ];

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => $files,
            'category' => 'Ostali dokumenti',
            'name' => 'Merged',
        ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertSame(0, $fake->uploadCalls);
        Bus::assertDispatched(function (ProcessDocumentJob $job) {
            return $job->archiveOnly === true;
        });
        Bus::assertDispatchedTimes(ProcessDocumentJob::class, 1);
        $this->assertDatabaseCount('user_documents', 1);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Merged',
            'status' => 'processed',
        ]);
    }
}
