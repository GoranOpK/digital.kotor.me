<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
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

class OmladinskoDocumentPackagesTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

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

    public function test_omladinsko_remains_development(): void
    {
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }

    public function test_four_packages_match_locked_flows_a_to_d(): void
    {
        $a = KnOmladinskoDocumentPackage::resolve('fizicko_lice', 'započinjanje', false);
        $this->assertSame(1, $a->number);
        $this->assertFalse($a->isM1b());
        $this->assertStringContainsString('M1a', $a->formTitles()['m1']);
        $this->assertStringContainsString('M2', $a->formTitles()['m2']);
        $this->assertSame(['licna_karta', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'predracuni_nabavka'], $a->displayedDocumentTypes());

        $b = KnOmladinskoDocumentPackage::resolve('privredno_drustvo', 'započinjanje', false);
        $this->assertSame(3, $b->number);
        $this->assertTrue($b->isM1b());
        $this->assertSame(['licna_karta', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'predracuni_nabavka'], $b->displayedDocumentTypes());
        $this->assertNotContains('dokaz_ziro_racun', $b->displayedDocumentTypes());

        $cStart = KnOmladinskoDocumentPackage::resolve('preduzetnik', 'započinjanje', true);
        $this->assertSame(1, $cStart->number);
        $this->assertContains('dokaz_ziro_racun', $cStart->displayedDocumentTypes());
        $this->assertNotContains('dokaz_ziro_racun', $cStart->strictlyRequiredDocumentTypes());

        $cRazvoj = KnOmladinskoDocumentPackage::resolve('preduzetnik', 'razvoj', true);
        $this->assertSame(2, $cRazvoj->number);
        $this->assertContains('ioppd_obrazac', $cRazvoj->displayedDocumentTypes());
        $this->assertSame(1, substr_count(implode(' ', $cRazvoj->displayedDocumentTypes()), 'ioppd_obrazac'));
        $this->assertStringContainsString('ili potvrdu', $cRazvoj->labels()['ioppd_obrazac']);

        $dStart = KnOmladinskoDocumentPackage::resolve('privredno_drustvo', 'započinjanje', true);
        $this->assertSame(3, $dStart->number);
        $this->assertContains('statut', $dStart->displayedDocumentTypes());
        $this->assertNotContains('dokaz_ziro_racun', $dStart->displayedDocumentTypes());

        $dRazvoj = KnOmladinskoDocumentPackage::resolve('privredno_drustvo', 'razvoj', true);
        $this->assertSame(4, $dRazvoj->number);
        $this->assertContains('godisnji_racuni', $dRazvoj->displayedDocumentTypes());
        $this->assertStringContainsString('registar kase', $dRazvoj->labels()['godisnji_racuni']);
        $this->assertNotContains('izvjestaj_registar_kase', $dRazvoj->displayedDocumentTypes());
        $this->assertNotContains('izvjestaj_registar_kase', $dRazvoj->strictlyRequiredDocumentTypes());
        $this->assertStringContainsString('ili potvrdu', $dRazvoj->labels()['ioppd_obrazac']);
    }

    public function test_youth_packages_never_include_zavod_or_previous_support_or_form_uploads(): void
    {
        foreach ([
            ['fizicko_lice', 'započinjanje', false],
            ['preduzetnik', 'započinjanje', true],
            ['preduzetnik', 'razvoj', true],
            ['privredno_drustvo', 'započinjanje', false],
            ['privredno_drustvo', 'započinjanje', true],
            ['privredno_drustvo', 'razvoj', true],
        ] as [$type, $stage, $registered]) {
            $package = KnOmladinskoDocumentPackage::resolve($type, $stage, $registered);
            $all = $package->displayedDocumentTypes();
            $this->assertNotContains('potvrda_zavod_nezaposleni', $all);
            $this->assertNotContains('izvjestaj_realizacija', $all);
            $this->assertNotContains('finansijski_izvjestaj', $all);
            $this->assertNotContains('ostalo', $all);
            $this->assertNotContains('m1a', $all);
            $this->assertNotContains('m1b', $all);
            $this->assertNotContains('M2', $all);
            $labels = implode(' ', $package->labels());
            $this->assertStringNotContainsString('podnositeljka', $labels);
            $this->assertStringNotContainsString('preduzetnica', mb_strtolower($labels));
        }
    }

    public function test_application_dispatcher_uses_native_youth_types_not_womens_mapping(): void
    {
        $competition = $this->openCompetition('omladinsko');
        $application = $this->makeApplication($competition, [
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'razvoj',
            'is_registered' => true,
        ]);

        $docs = $application->getRequiredDocuments();
        $this->assertNotContains('potvrda_zavod_nezaposleni', $docs);
        $this->assertContains('ioppd_obrazac', $docs);
        $this->assertNotContains('dokaz_ziro_racun', $application->getStrictlyRequiredDocuments());
        $this->assertContains('dokaz_ziro_racun', $docs);
        $this->assertArrayNotHasKey('potvrda_zavod_nezaposleni', $application->getDocumentLabelsMap());
        $this->assertSame(
            $docs,
            Application::getRequiredDocumentsForType('preduzetnik', 'razvoj', true, 'omladinsko')
        );
    }

    public function test_planned_company_omits_company_block_documents(): void
    {
        $competition = $this->openCompetition('omladinsko');
        $application = $this->makeApplication($competition, [
            'applicant_type' => 'privredno_drustvo',
            'company_legal_form' => 'doo',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
        ]);

        $docs = $application->getRequiredDocuments();
        $this->assertSame(3, $application->omladinskoDocumentPackage()?->number);
        foreach (['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'dokaz_ziro_racun'] as $type) {
            $this->assertNotContains($type, $docs);
        }
    }

    public function test_registered_company_includes_registration_documents_without_giro(): void
    {
        $competition = $this->openCompetition('omladinsko');
        $application = $this->makeApplication($competition, [
            'applicant_type' => 'privredno_drustvo',
            'company_legal_form' => 'ad',
            'business_stage' => 'započinjanje',
            'is_registered' => true,
        ]);

        $docs = $application->getRequiredDocuments();
        foreach (['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa'] as $type) {
            $this->assertContains($type, $docs);
        }
        $this->assertNotContains('dokaz_ziro_racun', $docs);
    }

    public function test_direct_post_rejects_zavod_ostalo_and_kasa_for_omladinsko(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft();

        foreach (['potvrda_zavod_nezaposleni', 'ostalo', 'izvjestaj_registar_kase', 'izvjestaj_realizacija'] as $type) {
            $this->actingAs($owner)
                ->post(route('applications.upload', $application), [
                    'document_type' => $type,
                    'files' => [UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf')],
                ])
                ->assertRedirect()
                ->assertSessionHasErrors('document_type');
            $this->assertDatabaseMissing('application_documents', [
                'application_id' => $application->id,
                'document_type' => $type,
            ]);
        }
        $this->assertSame(0, $application->documents()->count());

        $this->enableControlledFakePdfUploadProcessor();

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'licna_karta',
                'files' => [UploadedFile::fake()->create('lk.pdf', 20, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $application->documents()->where('document_type', 'licna_karta')->count());
        $this->assertSame(1, $application->documents()->count());
    }

    public function test_preview_uses_youth_labels_without_womens_mapping(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(611)]);
        $competition = $this->openCompetition('omladinsko', 'Poziv mladi paketi');

        $html = $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('obrazac M1a', $html);
        $this->assertStringContainsString('obrazac M2', $html);
        $this->assertStringNotContainsString('podnositeljke prijave odnosno preduzetnice', $html);
        $this->assertStringNotContainsString('Potvrda Zavoda za zapošljavanje', $html);
    }

    public function test_show_page_lists_package_one_without_m1_m2_as_uploads(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft();

        $html = $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Dokumentacioni paket 1', $html);
        $this->assertStringContainsString('nisu upload prilozi', $html);
        $this->assertStringNotContainsString('option value="potvrda_zavod_nezaposleni"', $html);
        $this->assertStringNotContainsString('option value="ostalo"', $html);
    }

    public function test_zensko_catalog_and_labels_remain_unchanged(): void
    {
        $preduzetnicaStart = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', true);
        $this->assertContains('potvrda_zavod_nezaposleni', $preduzetnicaStart);
        $this->assertContains('dokaz_ziro_racun', $preduzetnicaStart);

        $dooRazvoj = Application::getRequiredDocumentsForType('doo', 'razvoj', true);
        $this->assertContains('potvrda_zavod_nezaposleni', $dooRazvoj);
        $this->assertContains('godisnji_racuni', $dooRazvoj);

        $ostaloStart = Application::getRequiredDocumentsForType('ostalo', 'započinjanje', false);
        $this->assertContains('potvrda_zavod_nezaposleni', $ostaloStart);
        $this->assertContains('licna_karta', $ostaloStart);

        $fizicko = Application::getRequiredDocumentsForType('fizicko_lice', 'započinjanje', false);
        $this->assertContains('potvrda_zavod_nezaposleni', $fizicko);

        $zensko = $this->openCompetition('zensko');
        $application = $this->makeApplication($zensko, [
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'is_registered' => true,
        ]);
        $this->assertContains('dokaz_ziro_racun', $application->getStrictlyRequiredDocuments());
        $this->assertContains('potvrda_zavod_nezaposleni', $application->getRequiredDocuments());
        $this->assertStringContainsString('podnositeljke prijave odnosno preduzetnice', $application->getDocumentLabelsMap()['potvrda_neosudjivanost']);
        $this->assertSame(Application::getZavodNezaposleniDocumentLabel(), $application->getDocumentLabelsMap()['potvrda_zavod_nezaposleni']);
    }

    public function test_omladinsko_missing_documents_require_explicit_confirmation_before_submit(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft();
        $this->assertSame(0, $application->documents()->count());
        $missingLabels = $application->getMissingRequiredDocumentLabels();
        $this->assertNotEmpty($missingLabels);
        $this->assertStringNotContainsString('žiro', mb_strtolower(implode(' ', $missingLabels)));

        $first = $this->actingAs($owner)
            ->followingRedirects()
            ->post(route('applications.final-submit', $application));

        $first->assertOk();
        $application->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertNotSame('rejected', $application->status);
        $html = $first->getContent();
        $this->assertStringContainsString('Upozorenje prije podnošenja', $html);
        foreach ($missingLabels as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('Vrati se na uređivanje', $html);
        $this->assertStringContainsString('name="confirm_missing_documents"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('Podnesi prijavu uprkos nedostajućim prilozima', $html);
        $this->assertStringContainsString('#application-documents', $html);

        $direct = $this->actingAs($owner)->post(route('applications.final-submit', $application));
        $direct->assertRedirect(route('applications.show', $application));
        $direct->assertSessionHas('omladinsko_confirm_missing_documents', true);
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);

        $confirmed = $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_missing_documents' => '1',
        ]);
        $confirmed->assertRedirect(route('applications.show', $application));
        $confirmed->assertSessionHasNoErrors();
        $confirmed->assertSessionHas('document_warning');
        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertNotSame('rejected', $application->status);
        $this->assertSame(0, $application->evaluationScores()->count());
        $this->assertNull($application->eliminatoryCheck);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'licna_karta',
                'files' => [UploadedFile::fake()->create('lk.pdf', 20, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(0, $application->documents()->count());
    }

    public function test_omladinsko_giro_is_not_a_blocking_missing_document(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        $competition = $this->openCompetition('omladinsko');
        $application = $this->makeApplication($competition, [
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'započinjanje',
            'is_registered' => true,
        ]);

        $displayed = $application->getRequiredDocuments();
        $this->assertContains('dokaz_ziro_racun', $displayed);
        $this->assertNotContains('dokaz_ziro_racun', $application->getStrictlyRequiredDocuments());
        $this->assertNotContains('dokaz_ziro_racun', $application->getMissingRequiredDocumentTypes());
        $this->assertStringNotContainsString('žiro', mb_strtolower(implode(' ', $application->getMissingRequiredDocumentLabels())));
    }

    public function test_omladinsko_complete_documents_submit_without_confirmation_step(): void
    {
        $this->makeOmladinskoPubliclyAvailable();
        [$owner, $application] = $this->createOmladinskoReadyDraft();
        foreach ($application->getStrictlyRequiredDocuments() as $type) {
            $this->attachDocument($application, $type);
        }
        $application->unsetRelation('documents');
        $this->assertSame([], $application->getMissingRequiredDocumentTypes());

        $response = $this->actingAs($owner)
            ->followingRedirects()
            ->post(route('applications.final-submit', $application));

        $response->assertOk();
        $response->assertDontSee('Upozorenje prije podnošenja', false);
        $response->assertDontSee('name="confirm_missing_documents"', false);
        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertNotSame('rejected', $application->status);
        $this->assertSame(0, $application->evaluationScores()->count());
    }

    public function test_zensko_submit_does_not_use_omladinsko_missing_document_confirmation(): void
    {
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb(802)]);
        $competition = $this->openCompetition('zensko', 'Poziv žene paketi');
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
        $application->refresh();
        $this->assertTrue($application->isObrazacComplete());
        $this->assertNotEmpty($application->getMissingRequiredDocumentTypes());

        $html = $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('name="confirm_missing_documents"', $html);
        $this->assertStringContainsString('Podnesi prijavu', $html);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));
        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('omladinsko_confirm_missing_documents');
        $response->assertSessionMissing('document_warning');
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNotNull($application->fresh()->submitted_at);
    }

    private function attachDocument(Application $application, string $documentType): void
    {
        $path = 'documents/'.$application->id.'_'.$documentType.'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => $documentType.'.pdf',
            'file_path' => $path,
            'document_type' => $documentType,
            'is_required' => true,
        ]);
    }

    private function makeOmladinskoPubliclyAvailable(): void
    {
        CompetitionProgramCatalog::overrideStatusForTests(
            'omladinsko',
            CompetitionProgramCatalog::STATUS_ACTIVE
        );
    }

    private function openCompetition(string $type, ?string $title = null): Competition
    {
        return Competition::create([
            'title' => $title ?? ('KN paketi '.$type),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private int $jmbSerial = 620;

    private function makeApplication(Competition $competition, array $overrides = []): Application
    {
        $this->jmbSerial++;
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb($this->jmbSerial)]);
        $application = Application::create(array_merge([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
        ], $overrides));
        $application->setRelation('competition', $competition);
        $application->setRelation('user', $owner);

        return $application;
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createOmladinskoReadyDraft(): array
    {
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb(701)]);
        $competition = $this->openCompetition('omladinsko');
        $jmb = $owner->jmb;
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
            'physical_person_jmbg' => $jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'realization_type' => 'stvori_novi',
            'funding_sources_table' => [
                ['type' => 'Laptop', 'price' => '1200'],
            ],
            'requested_amount' => 500,
            'finances_notice_confirmed' => true,
        ]);

        $application->refresh();
        $application->load(['businessPlan', 'competition', 'documents']);

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
