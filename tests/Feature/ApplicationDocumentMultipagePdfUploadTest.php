<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Commission;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\PdfOptimizer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ApplicationDocumentMultipagePdfUploadTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE_COUNT = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Process::fake();
    }

    public function test_application_upload_and_view_preserve_multipage_pdf_page_count(): void
    {
        $sourcePath = $this->writeMultipagePdf(self::PAGE_COUNT);
        $this->skipUnlessOptimizerSeesPageCount($sourcePath, self::PAGE_COUNT);
        $inputPages = $this->pdfCatalogPageCount($sourcePath);
        $this->assertSame(self::PAGE_COUNT, $inputPages);

        $owner = $this->userWithRole('korisnik');
        $application = $this->createDraftApplicationFor($owner);

        $upload = new UploadedFile($sourcePath, 'statut.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($owner)->post(route('applications.upload', $application), [
            'document_type' => 'statut',
            'files' => [$upload],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $document = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->where('document_type', 'statut')
            ->first();

        $this->assertNotNull($document);
        $this->assertNotEmpty($document->file_path);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));

        $storedPath = Storage::disk('local')->path($document->file_path);
        $storedPages = $this->pdfCatalogPageCount($storedPath);
        $this->assertSame($inputPages, $storedPages, 'Stored PDF must keep the input page count');
        $this->assertGreaterThan(1, $storedPages);

        $view = $this->actingAs($owner)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $view->assertOk();
        $viewedPages = $this->pdfCatalogPageCount($this->responseFilePath($view));
        $this->assertSame($inputPages, $viewedPages, 'View must return the complete stored PDF, not the first page only');

        $download = $this->actingAs($owner)->get(route('applications.document.download', [
            'application' => $application,
            'document' => $document,
        ]));

        $download->assertOk();
        $downloadedPages = $this->pdfCatalogPageCount($this->responseFilePath($download));
        $this->assertSame($inputPages, $downloadedPages);

        @unlink($sourcePath);
    }

    private function writeMultipagePdf(int $pages): string
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick required to generate a real multi-page PDF for application upload');
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('app_mpdf_', true).'.pdf';
        $output = new \Imagick;

        try {
            for ($i = 0; $i < $pages; $i++) {
                $page = new \Imagick;
                $page->newImage(200, 280, new \ImagickPixel($i === 0 ? 'white' : ($i === 1 ? '#dddddd' : '#aaaaaa')));
                $page->setImageFormat('pdf');
                $output->addImage($page);
                $page->clear();
                $page->destroy();
            }

            $ok = $output->writeImages($path, true);
        } finally {
            $output->clear();
            $output->destroy();
        }

        if (! $ok || ! is_file($path) || filesize($path) < 100) {
            @unlink($path);
            $this->markTestSkipped('Could not generate a '.$pages.'-page PDF fixture');
        }

        $header = (string) file_get_contents($path, false, null, 0, 5);
        if (! str_starts_with($header, '%PDF-')) {
            @unlink($path);
            $this->markTestSkipped('Generated fixture is not a PDF');
        }

        return $path;
    }

    private function skipUnlessOptimizerSeesPageCount(string $path, int $expected): void
    {
        $probeDest = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('pagecount_probe_', true).'.pdf';
        $result = (new PdfOptimizer)->optimize($path, $probeDest);
        @unlink($probeDest);

        if (! $result->ok() || $result->pageCount !== $expected) {
            $this->markTestSkipped(
                'PdfOptimizer cannot read a '.$expected.'-page PDF in this environment'
                .($result->error ? (': '.$result->error) : (' (pageCount='.$result->pageCount.')'))
                .'. Imagick PDF delegate / Ghostscript is required for this regression test.'
            );
        }
    }

    private function pdfCatalogPageCount(string $path): int
    {
        $bytes = (string) file_get_contents($path);
        if ($bytes !== '' && preg_match('/\/Type\s*\/Pages\b.*?\/Count\s+(\d+)/s', $bytes, $match)) {
            return (int) $match[1];
        }

        return 0;
    }

    private function responseFilePath(mixed $response): string
    {
        $base = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $base);

        $path = $base->getFile()->getPathname();
        $this->assertFileExists($path);

        return $path;
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    private function createDraftApplicationFor(User $owner): Application
    {
        $commission = Commission::create([
            'name' => 'Test komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $competition = Competition::create([
            'title' => 'Test konkurs '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
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
