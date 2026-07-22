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

    public function test_multi_file_json_flag_on_returns_queued_contract_not_redirect(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

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

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => $files,
            'category' => 'Ostali dokumenti',
            'name' => 'Merged JSON',
        ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', true)
            ->assertJsonPath('queued', true)
            ->assertJsonStructure(['document_id', 'status', 'message'])
            ->assertJsonMissing(['redirect']);

        $this->assertFalse($response->isRedirect());
        Bus::assertDispatched(function (ProcessDocumentJob $job) {
            return $job->archiveOnly === true;
        });
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Merged JSON',
            'status' => 'processed',
        ]);
        $this->assertSame(
            UserDocument::where('user_id', $this->user->id)->value('id'),
            $response->json('document_id')
        );
        $this->assertSame('processed', $response->json('status'));
    }

    public function test_multi_file_html_keeps_existing_success_flash(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

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

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Merged HTML',
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas(
            'success',
            'Fajlovi su uspješno spojeni u jedan PDF dokument i obrađeni.'
        );
    }

    public function test_multi_file_json_storage_error_returns_422_not_redirect(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(false);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'No space JSON',
        ]);

        $response->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['message', 'errors']);

        $this->assertFalse($response->isRedirect());
        $this->assertStringContainsString('Nemate dovoljno prostora', (string) $response->json('message'));
        $this->assertStringContainsString('Nemate dovoljno prostora', (string) data_get($response->json(), 'errors.files.0'));
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
    }

    public function test_multi_file_html_storage_error_keeps_session_errors(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(false);
        });

        $response = $this->actingAs($this->user)->from(route('documents.index'))->post(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'No space HTML',
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHasErrors('files');
        Bus::assertNotDispatched(ProcessDocumentJob::class);
    }

    public function test_multi_file_json_flag_off_returns_processed_contract(): void
    {
        config(['external_archive.library_upload' => false]);
        Bus::fake();

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

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Merged flag off',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'processed')
            ->assertJsonStructure(['document_id', 'status', 'message'])
            ->assertJsonMissing(['queued']);

        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Merged flag off',
            'status' => 'processed',
        ]);
    }
}
