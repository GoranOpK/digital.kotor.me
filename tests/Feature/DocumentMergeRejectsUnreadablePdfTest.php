<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Commission;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use App\Services\PdfOptimizer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PDF-02-FIX. Rejection cases stub getPdfPageCount so they do not depend on Ghostscript.
 * A green rejection test is not evidence that real PDF reading or multi-PDF merge succeeded.
 */
class DocumentMergeRejectsUnreadablePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Process::fake();
    }

    public function test_merge_rejects_when_the_middle_pdf_has_no_readable_pages(): void
    {
        $processor = $this->pageCountDouble([2, 0, 1]);

        $result = $processor->mergeDocuments([
            $this->pdfUpload('ispravan-1.pdf', 'one'),
            $this->pdfUpload('necitljiv.pdf', 'two'),
            $this->pdfUpload('ispravan-3.pdf', 'three'),
        ], 42);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('necitljiv.pdf', $result['error']);
        $this->assertStringContainsString('Spajanje je odbijeno jer svi dokumenti moraju biti uključeni.', $result['error']);
        $this->assertStringNotContainsString(sys_get_temp_dir(), $result['error']);
        $this->assertStringNotContainsString('documents/user_', $result['error']);
        $this->assertSame(2, $processor->calls, 'Merge must stop when the second document cannot be read.');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_application_upload_rejects_partial_merge_and_keeps_existing_documents(): void
    {
        $owner = $this->userWithRole('korisnik');
        $application = $this->createDraftApplicationFor($owner, 'zensko');
        $keptPath = 'documents/user_'.$owner->id.'/kept-licna.pdf';
        Storage::disk('local')->put($keptPath, '%PDF-1.3 kept');
        $kept = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Licna karta',
            'file_path' => $keptPath,
            'document_type' => 'licna_karta',
            'is_required' => true,
        ]);

        $this->app->instance(DocumentProcessor::class, $this->pageCountDouble([2, 0, 1]));

        $response = $this->actingAs($owner)->post(route('applications.upload', $application), [
            'document_type' => 'statut',
            'document_name' => 'Spojeno',
            'files' => [
                $this->pdfUpload('ispravan-1.pdf', 'one'),
                $this->pdfUpload('necitljiv.pdf', 'two'),
                $this->pdfUpload('ispravan-3.pdf', 'three'),
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('files');
        $error = session('errors')->get('files')[0];
        $this->assertStringContainsString('necitljiv.pdf', $error);
        $this->assertStringContainsString('Spajanje je odbijeno', $error);
        $this->assertStringNotContainsString(sys_get_temp_dir(), $error);

        $this->assertSame(1, ApplicationDocument::query()->count());
        $this->assertDatabaseHas('application_documents', [
            'id' => $kept->id,
            'document_type' => 'licna_karta',
            'file_path' => $keptPath,
        ]);
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $application->id,
            'document_type' => 'statut',
        ]);
        $this->assertTrue(Storage::disk('local')->exists($keptPath));
        $this->assertSame(
            ['documents/user_'.$owner->id.'/kept-licna.pdf'],
            Storage::disk('local')->allFiles()
        );
    }

    public function test_single_unreadable_pdf_upload_is_rejected_and_stores_nothing(): void
    {
        $owner = $this->userWithRole('korisnik');
        $application = $this->createDraftApplicationFor($owner, 'zensko');
        $upload = $this->plainTextNamedPdf('lose.pdf');

        $direct = app(DocumentProcessor::class)->processDocument($upload, $owner->id);
        $this->assertFalse($direct['success']);
        $this->assertNotSame('', (string) ($direct['error'] ?? ''));
        $this->assertSame([], Storage::disk('local')->allFiles());

        $response = $this->actingAs($owner)->post(route('applications.upload', $application), [
            'document_type' => 'statut',
            'files' => [$this->plainTextNamedPdf('lose.pdf')],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertSame(0, ApplicationDocument::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_library_merge_failure_keeps_originals_and_does_not_store_a_merged_pdf(): void
    {
        $owner = $this->userWithRole('korisnik');
        $owner->forceFill(['used_storage_bytes' => 0])->save();
        $previousPath = 'documents/user_'.$owner->id.'/previous.pdf';
        Storage::disk('local')->put($previousPath, '%PDF-1.3 previous');
        $previous = UserDocument::create([
            'user_id' => $owner->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Ranije sacuvano',
            'file_path' => $previousPath,
            'original_filename' => 'previous.pdf',
            'file_size' => 18,
            'status' => 'processed',
        ]);

        $this->app->instance(DocumentProcessor::class, $this->pageCountDouble([1, 0, 1]));

        $response = $this->actingAs($owner)->post(route('documents.store'), [
            'files' => [
                $this->pdfUpload('biblioteka-1.pdf', 'lib-one'),
                $this->pdfUpload('biblioteka-necitljiv.pdf', 'lib-two'),
                $this->pdfUpload('biblioteka-3.pdf', 'lib-three'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Spoj biblioteke',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('files');
        $error = session('errors')->get('files')[0];
        $this->assertStringContainsString('nije moguće pročitati', $error);
        $this->assertStringContainsString('Spajanje je odbijeno', $error);
        $this->assertStringNotContainsString(sys_get_temp_dir(), $error);
        $this->assertStringNotContainsString('documents/user_', $error);

        $previous->refresh();
        $this->assertSame('processed', $previous->status);
        $this->assertSame($previousPath, $previous->file_path);
        $this->assertTrue(Storage::disk('local')->exists($previousPath));

        $failed = UserDocument::query()->where('user_id', $owner->id)->where('id', '!=', $previous->id)->first();
        $this->assertNotNull($failed);
        $this->assertSame('failed', $failed->status);
        $this->assertTrue($failed->file_path === null || $failed->file_path === '');

        $originals = array_values(array_filter(
            explode('|', (string) $failed->original_file_path),
            fn (string $path) => $path !== ''
        ));
        $this->assertCount(3, $originals);
        foreach ($originals as $path) {
            $this->assertTrue(Storage::disk('local')->exists($path), $path);
            $this->assertStringContainsString('_original.', $path);
        }

        $merged = array_filter(
            Storage::disk('local')->allFiles(),
            fn (string $path) => ! str_contains($path, '_original.') && $path !== $previousPath
        );
        $this->assertSame([], array_values($merged));
    }

    public function test_mega_preparation_does_not_record_a_failed_merge_as_success(): void
    {
        $owner = $this->userWithRole('korisnik');
        $this->app->instance(DocumentProcessor::class, $this->pageCountDouble([1, 0, 1]));

        $response = $this->actingAs($owner)->postJson(route('documents.process-for-mega'), [
            'files' => [
                $this->pdfUpload('mega-1.pdf', 'mega-one'),
                $this->pdfUpload('mega-necitljiv.pdf', 'mega-two'),
                $this->pdfUpload('mega-3.pdf', 'mega-three'),
            ],
        ]);

        $response->assertStatus(500);
        $response->assertJsonPath('success', false);
        $response->assertJsonMissingPath('download_token');
        $this->assertStringContainsString('mega-necitljiv.pdf', (string) $response->json('error'));
        $this->assertStringContainsString('Spajanje je odbijeno', (string) $response->json('error'));
        $this->assertStringNotContainsString(sys_get_temp_dir(), (string) $response->json('error'));
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, UserDocument::query()->count());
    }

    public function test_real_png_merge_includes_every_input_when_imagick_can_write_pdf(): void
    {
        if (! function_exists('imagepng') || ! extension_loaded('imagick')) {
            $this->markTestSkipped('SKIP local environment: GD or Imagick is missing. Real image merge was not executed.');
        }

        $result = app(DocumentProcessor::class)->mergeDocuments([
            $this->pngUpload('slika-1.png', 20),
            $this->pngUpload('slika-2.png', 80),
            $this->pngUpload('slika-3.png', 160),
        ], 77);

        if (! ($result['success'] ?? false)) {
            $error = (string) ($result['error'] ?? '');
            if (str_contains($error, 'nije moguće pročitati') || str_contains($error, 'Greška pri')) {
                $this->markTestSkipped(
                    'SKIP local environment: Imagick could not merge PNG inputs into a PDF ('.$error.'). This is not a PASS of real document merging.'
                );
            }
        }

        $this->assertTrue($result['success'], (string) ($result['error'] ?? ''));
        $this->assertTrue(Storage::disk('local')->exists($result['file_path']));
        $stored = Storage::disk('local')->get($result['file_path']);
        $this->assertStringStartsWith('%PDF-', $stored);
        $this->assertSame(3, $this->pdfPageCount($stored));
    }

    public function test_real_three_pdf_merge_includes_every_input_or_skips_without_delegate(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('SKIP local environment: Imagick is not loaded. Real multi-PDF merge was not executed.');
        }

        $paths = [
            $this->writeOnePagePdf('white'),
            $this->writeOnePagePdf('#cccccc'),
            $this->writeOnePagePdf('#888888'),
        ];
        $processor = app(DocumentProcessor::class);
        $pageCount = (new \ReflectionMethod(DocumentProcessor::class, 'getPdfPageCount'))
            ->invoke($processor, $paths[0]);

        if ($pageCount < 1) {
            $this->markTestSkipped(
                'SKIP local environment: Imagick cannot read PDF pages because the Ghostscript delegate is not available as gs. Real multi-PDF merge was not executed and must not be reported as PASS.'
            );
        }

        $result = $processor->mergeDocuments([
            new UploadedFile($paths[0], 'valid-1.pdf', 'application/pdf', null, true),
            new UploadedFile($paths[1], 'valid-2.pdf', 'application/pdf', null, true),
            new UploadedFile($paths[2], 'valid-3.pdf', 'application/pdf', null, true),
        ], 88);

        $this->assertTrue($result['success'], (string) ($result['error'] ?? ''));
        $this->assertTrue(Storage::disk('local')->exists($result['file_path']));
        $this->assertSame(3, $this->pdfPageCount((string) Storage::disk('local')->get($result['file_path'])));
    }

    /**
     * @param  list<int>  $sequence
     */
    private function pageCountDouble(array $sequence): Pdf02MergePageCountDouble
    {
        $double = new Pdf02MergePageCountDouble(new PdfOptimizer);
        $double->sequence = $sequence;

        return $double;
    }

    private function pdfUpload(string $name, string $marker): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('pdf02_', true).'.pdf';
        file_put_contents($path, "%PDF-1.3\n% ".$marker." ".uniqid('', true)."\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< >>\n%%EOF\n");

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    private function plainTextNamedPdf(string $name): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('pdf02bad_', true).'.pdf';
        file_put_contents($path, "this is not a pdf document\n");

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    private function pngUpload(string $name, int $red): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('pdf02png_', true).'.png';
        $image = imagecreatetruecolor(40, 40);
        $color = imagecolorallocate($image, $red, 30, 30);
        imagefilledrectangle($image, 0, 0, 39, 39, $color);
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function writeOnePagePdf(string $color): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('pdf02page_', true).'.pdf';
        $output = new \Imagick;
        $page = new \Imagick;
        try {
            $page->newImage(120, 160, new \ImagickPixel($color));
            $page->setImageFormat('pdf');
            $output->addImage($page);
            $ok = $output->writeImages($path, true);
        } finally {
            $page->clear();
            $page->destroy();
            $output->clear();
            $output->destroy();
        }

        if (! $ok || ! is_file($path)) {
            $this->markTestSkipped('SKIP local environment: could not write a one-page PDF fixture.');
        }

        return $path;
    }

    private function pdfPageCount(string $bytes): int
    {
        if (preg_match('/\/Type\s*\/Pages\b.*?\/Count\s+(\d+)/s', $bytes, $match)) {
            return (int) $match[1];
        }

        preg_match_all('/\/Type\s*\/Page(?!s)\b/', $bytes, $pages);

        return count($pages[0]);
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'used_storage_bytes' => 0,
        ]);
    }

    private function createDraftApplicationFor(User $owner, string $type): Application
    {
        $commission = Commission::create([
            'name' => 'Test komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $type,
        ]);

        $competition = Competition::create([
            'title' => 'Test konkurs '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'doo',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);
    }
}

/**
 * Isolated stand-in for the unreadable-page decision. It does not read PDF bytes.
 */
class Pdf02MergePageCountDouble extends DocumentProcessor
{
    /** @var list<int> */
    public array $sequence = [];

    public int $calls = 0;

    protected function getPdfPageCount(string $filePath): int
    {
        $count = $this->sequence[$this->calls] ?? 0;
        $this->calls++;

        return $count;
    }
}
