<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\User;
use App\Services\DocumentProcessor;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnOmladinskoDocumentPackage;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class OmladinskoBusinessPlanFlowTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private int $jmbSerial = 730;

    protected function setUp(): void
    {
        parent::setUp();
        CompetitionProgramCatalog::clearTestOverrides();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_omladinsko_submit_warns_about_missing_documents_but_does_not_block(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft();
        $this->assertSame(0, $application->documents()->count());
        $this->assertNotEmpty($application->getMissingRequiredDocumentTypes());

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('omladinsko_confirm_missing_documents');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);

        $confirmed = $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_missing_documents' => '1',
        ]);
        $confirmed->assertRedirect(route('applications.show', $application));
        $confirmed->assertSessionHas('document_warning');
        $this->assertStringContainsString('nisu priloženi svi obavezni prilozi', session('document_warning'));
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNotSame('rejected', $application->fresh()->status);
    }

    public function test_omladinsko_documents_lock_after_submit(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft();
        $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_missing_documents' => '1',
        ]);
        $application->refresh();
        $this->assertSame('submitted', $application->status);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'licna_karta',
                'files' => [UploadedFile::fake()->create('lk.pdf', 20, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame(0, $application->documents()->count());
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $application->id,
            'document_type' => 'licna_karta',
        ]);
    }

    public function test_omladinsko_point_seven_requires_one_answer_and_drugo_text(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft([
            'realization_type' => null,
            'product_service' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);

        $application->businessPlan->update([
            'realization_type' => KnOmladinskoDocumentPackage::REALIZATION_DRUGO,
            'product_service' => '',
        ]);
        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);

        $application->businessPlan->update([
            'realization_type' => KnOmladinskoDocumentPackage::REALIZATION_DRUGO,
            'product_service' => 'Objašnjenje za Drugo',
        ]);
        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_omladinsko_requires_one_purchase_and_does_not_equate_total_to_requested_amount(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft([
            'funding_sources_table' => [['type' => '', 'price' => '']],
            'requested_amount' => 9999,
        ]);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);

        $application->businessPlan->update([
            'funding_sources_table' => [
                ['type' => 'Oprema', 'price' => '100'],
            ],
            'requested_amount' => 9999,
        ]);
        $this->assertSame(1, KnOmladinskoDocumentPackage::filledPurchaseCount($application->businessPlan->fresh()->funding_sources_table));

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('UKUPNO', $html);
        $this->assertStringContainsString('>Drugo<', $html);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNotEquals(
            9999.0,
            (float) $application->businessPlan->fresh()->funding_sources_table[0]['price']
        );
    }

    public function test_second_call_creates_new_application_without_copying_m1_m2_or_documents(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $first] = $this->createOmladinskoReadyDraft();
        $this->enableControlledFakePdfUploadProcessor();
        $this->actingAs($owner)->post(route('applications.upload', $first), [
            'document_type' => 'licna_karta',
            'files' => [UploadedFile::fake()->create('lk.pdf', 20, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('applications.final-submit', $first), [
            'confirm_missing_documents' => '1',
        ]);
        $first->refresh();
        $this->assertSame('submitted', $first->status);
        $this->assertSame(1, $first->documents()->count());
        $this->assertNotNull($first->businessPlan);

        $secondCompetition = $this->openCompetition('omladinsko', 'Drugi Poziv', 2);
        $second = Application::create([
            'competition_id' => $secondCompetition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Novi plan drugog Poziva',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(0, $second->documents()->count());
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $second->id,
            'document_type' => 'licna_karta',
        ]);
        $this->assertNull($second->businessPlan);
        $this->assertNotSame($first->business_plan_name, $second->business_plan_name);
        $this->assertSame(1, $first->fresh()->documents()->count());
        $this->assertDatabaseHas('application_documents', [
            'application_id' => $first->id,
            'document_type' => 'licna_karta',
        ]);
    }

    public function test_zensko_submit_and_m2_remain_without_youth_gates(): void
    {
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb(799)]);
        $competition = $this->openCompetition('zensko', 'Poziv žene');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Ženski plan',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);
        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $owner->jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'finances_notice_confirmed' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('document_warning');
        $this->assertSame('submitted', $application->fresh()->status);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application->fresh()), [
                'document_type' => 'potvrda_zavod_nezaposleni',
                'files' => [UploadedFile::fake()->create('zavod.pdf', 20, 'application/pdf')],
            ])
            ->assertSessionHasErrors('error');
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $application->id,
            'document_type' => 'potvrda_zavod_nezaposleni',
        ]);
    }

    public function test_youth_m2_defaults_for_preduzetnik_and_privredno_drustvo(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        $owner = $this->makeKorisnik([
            'user_type' => 'Preduzetnik',
            'pib' => $this->validPib(740),
            'jmb' => $this->validJmb(741),
        ]);
        $competition = $this->openCompetition('omladinsko');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan preduzetnika',
            'business_area' => 'Usluge',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'razvoj',
            'is_registered' => true,
            'registration_form' => 'Preduzetnik',
            'crps_number' => 'CRPS-1',
            'pib' => $this->validPib(740),
            'accuracy_declaration' => true,
            'applicant_jmbg' => $owner->jmb,
            'preduzetnik_name' => $owner->name,
            'preduzetnik_phone' => '067000000',
            'preduzetnik_email' => $owner->email,
            'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($owner->name, $html);
        $this->assertStringContainsString('podnosiocu', $html);
        $this->assertStringNotContainsString('podnositeljki', $html);
    }

    private function makeOmladinskoPubliclyAvailable(): void
    {
        CompetitionProgramCatalog::overrideStatusForTests(
            'omladinsko',
            CompetitionProgramCatalog::STATUS_ACTIVE
        );
    }

    private function openCompetition(string $type, ?string $title = null, ?int $callNumber = null): Competition
    {
        return Competition::create([
            'title' => $title ?? ('KN tok '.$type),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
            'call_number' => $type === 'omladinsko' ? ($callNumber ?? 1) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $planOverrides
     * @return array{0: User, 1: Application}
     */
    private function createOmladinskoReadyDraft(array $planOverrides = []): array
    {
        $this->jmbSerial++;
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb($this->jmbSerial)]);
        $competition = $this->openCompetition('omladinsko');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Omladinski plan',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        BusinessPlan::create(array_merge([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $owner->jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'realization_type' => 'stvori_novi',
            'funding_sources_table' => [
                ['type' => 'Laptop', 'price' => '1200'],
            ],
            'requested_amount' => 500,
            'finances_notice_confirmed' => true,
        ], $planOverrides));

        $application->refresh();
        $application->load(['businessPlan', 'competition', 'documents']);
        $this->assertTrue($application->isObrazacComplete());

        return [$owner, $application];
    }

    /**
     * Process::fake + DocumentProcessor stub only for tests that POST a fake PDF
     * through the real upload route. Does not skip document_type, ownership, or lock.
     */
    private function enableControlledFakePdfUploadProcessor(): void
    {
        Process::fake();
        Storage::disk('local')->put('documents/test-upload.pdf', '%PDF-1.4 test');
        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('processDocument')->andReturn([
                'success' => true,
                'file_path' => 'documents/test-upload.pdf',
                'file_size' => 100,
                'cloud_path' => null,
                'error' => null,
            ]);
        });
    }
}
