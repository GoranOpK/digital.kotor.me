<?php

namespace Tests\Feature\Kn;

use App\Http\Controllers\ApplicationController;
use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\ApplicationDocument;
use App\Models\BusinessPlan;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalDocumentEvidence;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Security\JmbDualWrite;
use App\Services\Competitions\ApplicationEvidenceFreezer;
use App\Services\Competitions\CanonicalApplicantFormService;
use App\Services\Competitions\CanonicalBeneficiaryApplications;
use App\Services\Competitions\CanonicalDocumentService;
use App\Services\Competitions\CanonicalReviewBasis;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Profile\CanonicalMmspPolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalDocumentCatalog as Catalog;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LogicException;
use RuntimeException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-015 — canonical Obrazac P1a / P1b (čl.16), čl.14 document catalog, applicant flow,
 * FINAL validation and freeze, two-application model, deadline locks and Women/Youth isolation.
 */
class CanonicalApplicantFormsAndDocumentsTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    private const WOMEN_WORDING = ['preduzetnic', 'Preduzetnic', 'podnositeljk', 'Podnositeljk', 'žensk', 'Žensk', 'osnivačic', 'Obrazac 1a', 'Obrazac 1b', 'biznis plan', 'Biznis plan'];

    private const INVENTED_LIABILITY_WORDING = ['materijaln', 'krivičn', 'odgovornošću', 'građansk', 'pod punom'];

    private const P1A_UPLOADS = [
        'licna_karta',
        'crps_resenje',
        'pib_resenje',
        'pdv_resenje',
        'potvrda_neosudjivanost',
        'uvjerenje_opstina_porezi',
        'uvjerenje_opstina_nepokretnost',
        'potvrda_upc_porezi',
        'ioppd_obrazac',
        'dokaz_ziro_racun',
        'predracuni_nabavka',
    ];

    private const P1B_UPLOADS = [
        'licna_karta',
        'crps_resenje',
        'pib_resenje',
        'pdv_resenje',
        'statut',
        'karton_potpisa',
        'godisnji_racuni',
        'izvjestaj_registar_kase',
        'potvrda_neosudjivanost',
        'uvjerenje_opstina_porezi',
        'uvjerenje_opstina_nepokretnost',
        'potvrda_upc_porezi',
        'ioppd_obrazac',
        'predracuni_nabavka',
    ];

    private const THIRTY_DAY_TYPES = ['uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi'];

    private int $jmbSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        CompetitionProgramCatalog::overrideStatusForTests(self::CANONICAL, CompetitionProgramCatalog::STATUS_ACTIVE);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- 1. P1a fields and wording

    public function test_preduzetnik_starts_through_explicit_legal_form_and_gets_obrazac_p1a(): void
    {
        $owner = $this->makeOwner();
        $call = $this->openCall();

        $this->actingAs($owner)->post(route('applications.start', $call))
            ->assertRedirect(route('applications.canonical.legal-form', $call));
        $this->actingAs($owner)->get(route('applications.create', $call))
            ->assertRedirect(route('applications.canonical.legal-form', $call));

        $this->actingAs($owner)->get(route('applications.canonical.legal-form', $call))
            ->assertOk()
            ->assertSee('name="legal_form" value="preduzetnik"', false)
            ->assertSee('name="legal_form" value="drustvo"', false)
            ->assertSee('Obrazac P1a')
            ->assertSee('Obrazac P1b');

        $response = $this->actingAs($owner)->post(route('applications.canonical.start', $call), ['legal_form' => CanonicalLegalForm::PREDUZETNIK]);
        $application = Application::query()->where('user_id', $owner->id)->sole();
        $response->assertRedirect(route('applications.canonical-p1.edit', $application));

        $this->assertSame('draft', $application->status);
        $this->assertSame('preduzetnik', $application->applicant_type);
        $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $application->canonicalApplicantForm->legal_form);
        $this->assertNull($application->business_plan_name);

        $html = $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))
            ->assertOk()
            ->assertSee('Obrazac P1a')
            ->assertSee('PRIJAVA')
            ->assertSee('na javni poziv za podršku mikro, malim i srednjim preduzećima')
            ->assertSee('PREDUZETNIK')
            ->assertDontSee((string) $owner->jmb)
            ->getContent();
        $form = $this->formSection($html, route('applications.canonical-p1.update', $application));

        foreach ([
            'Vrsta subvencije',
            'Ime i prezime preduzetnika',
            'JMBG/PIB — PIB preduzetnika',
            'Adresa',
            'Šifra djelatnosti',
            'Broj zaposlenih',
            'Broj žiro računa',
            'Kontakt telefon',
            'E-mail',
            'Website',
            'Datum registracije u CRPS',
            'Za tačnost datih podataka odgovara preduzetnik.',
            'Potpis preduzetnika',
        ] as $label) {
            $this->assertStringContainsString($label, $form, $label);
        }
        $this->assertSame('Za tačnost datih podataka odgovara preduzetnik.', CanonicalApplicantFormService::accuracyStatement(CanonicalLegalForm::PREDUZETNIK));
        foreach (['Naziv društva', 'Datum osnivanja', 'odgovornog lica', 'Sjedište', CanonicalApplicantFormService::ACCURACY_STATEMENT_P1B, ...self::INVENTED_LIABILITY_WORDING, ...self::WOMEN_WORDING] as $foreign) {
            $this->assertStringNotContainsString($foreign, $form, $foreign);
        }
        foreach (CanonicalSubsidyType::codes() as $code) {
            $this->assertStringContainsString('value="'.$code.'"', $form);
        }
    }

    public function test_p1a_draft_save_normalizes_values_and_rejects_invalid_input_without_partial_write(): void
    {
        $owner = $this->makeOwner();
        $application = $this->startedDraft($owner, $this->openCall(), CanonicalLegalForm::PREDUZETNIK);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->p1aPayload('02345678', CanonicalSubsidyType::INNOVATIVE, [
                'activity_code' => '5610',
                'legal_form' => CanonicalLegalForm::DRUSTVO,
                'applicant_type' => 'privredno_drustvo',
            ]))
            ->assertRedirect(route('applications.canonical-p1.edit', $application))
            ->assertSessionHasNoErrors();

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->sole();
        $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $form->legal_form);
        $this->assertSame('56.10', $form->activity_code);
        $this->assertSame(3, $form->employee_count);
        $this->assertSame('2021-05-10', $form->registration_date->toDateString());
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, $form->subsidy_type);
        $this->assertNotNull($form->accuracy_confirmed_at);
        $this->assertSame('02345678', $application->fresh()->pib);
        $this->assertSame('preduzetnik', $application->fresh()->applicant_type);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->p1aPayload('123', 'ostalo', [
                'person_name' => 'Promijenjeno Ime',
                'activity_code' => 'abc',
                'employee_count' => '-1',
                'email' => 'nije-email',
                'registration_date' => now()->addDay()->toDateString(),
                'address' => 'Bulevar Svetog Petra Cetinjskog 1, 81000 Podgorica',
            ]))
            ->assertSessionHasErrors(['pib', 'subsidy_type', 'activity_code', 'employee_count', 'email', 'registration_date', 'address']);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->p1aPayload('02345678', CanonicalSubsidyType::INNOVATIVE, [
                'person_name' => 'Promijenjeno Ime',
                'registration_date' => '2021-1x-10',
            ]))
            ->assertSessionHasErrors(['registration_date' => 'Datum unesite u obliku GGGG-MM-DD.']);

        $form->refresh();
        $this->assertSame('Marko Marković', $form->person_name);
        $this->assertSame('02345678', $application->fresh()->pib);

        try {
            $form->update(['legal_form' => CanonicalLegalForm::DRUSTVO]);
            $this->fail('Legal form must be immutable after the explicit choice.');
        } catch (LogicException) {
            $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $form->fresh()->legal_form);
        }
    }

    // ---------------------------------------------------------------- 2. P1b fields and wording

    public function test_drustvo_starts_obrazac_p1b_with_company_fields_and_wording(): void
    {
        $owner = $this->makeOwner();
        $application = $this->startedDraft($owner, $this->openCall(), CanonicalLegalForm::DRUSTVO);
        $this->assertSame('privredno_drustvo', $application->applicant_type);

        $html = $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))
            ->assertOk()
            ->assertSee('Obrazac P1b')
            ->assertSee('PRIJAVA')
            ->assertSee('na javni poziv za podršku mikro, malim i srednjim preduzećima')
            ->assertSee('DRUŠTVO (DOO)')
            ->getContent();
        $form = $this->formSection($html, route('applications.canonical-p1.update', $application));

        foreach ([
            'Vrsta subvencije',
            'Naziv društva',
            'Ime i prezime odgovornog lica',
            'PIB društva',
            'Sjedište',
            'Datum osnivanja',
            'Šifra djelatnosti',
            'Broj zaposlenih',
            'Broj žiro računa',
            'Kontakt telefon odgovornog lica',
            'E-mail odgovornog lica',
            'Website',
            'Za tačnost datih podataka odgovara podnosilac prijave.',
            'Potpis odgovornog lica',
        ] as $label) {
            $this->assertStringContainsString($label, $form, $label);
        }
        $this->assertSame('Za tačnost datih podataka odgovara podnosilac prijave.', CanonicalApplicantFormService::accuracyStatement(CanonicalLegalForm::DRUSTVO));
        foreach (['Ime i prezime preduzetnika', 'Datum registracije u CRPS', 'Potpis preduzetnika', 'JMBG', CanonicalApplicantFormService::ACCURACY_STATEMENT_P1A, ...self::INVENTED_LIABILITY_WORDING, ...self::WOMEN_WORDING] as $foreign) {
            $this->assertStringNotContainsString($foreign, $form, $foreign);
        }

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), [
                'subsidy_type' => CanonicalSubsidyType::DIGITALIZATION,
                'company_name' => 'Primjer d.o.o.',
                'person_name' => 'Odgovorno Lice',
                'pib' => '03123456',
                'address' => 'Stari grad 1, 85330 Kotor',
                'registration_date' => '2019-02-01',
                'activity_code' => '62.01',
                'employee_count' => '5',
                'bank_account' => '510-0000000000002-22',
                'phone' => '067111222',
                'email' => 'drustvo@example.com',
                'accuracy_confirmed' => '1',
            ])
            ->assertSessionHasNoErrors();

        $stored = CanonicalApplicantForm::query()->where('application_id', $application->id)->sole();
        $this->assertSame('Primjer d.o.o.', $stored->company_name);
        $this->assertSame('P1b', $stored->formCode());
        $this->assertNull($stored->website);
        $this->assertSame('03123456', $application->fresh()->pib);
    }

    public function test_p1b_final_requires_company_specific_facts(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall(), ['legal_form' => CanonicalLegalForm::DRUSTVO]);
        CanonicalApplicantForm::query()->where('application_id', $application->id)->sole()
            ->update(['company_name' => null, 'registration_date' => null]);

        $this->assertSame(
            'Obrazac P1b nije kompletan. Nedostaje: naziv društva, datum osnivanja.',
            app(CanonicalApplicantFormService::class)->finalBlockReason($application)
        );
        $this->actingAs($owner)->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors(['error' => 'Obrazac P1b nije kompletan. Nedostaje: naziv društva, datum osnivanja.']);
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_legal_form_must_be_chosen_explicitly_and_legacy_paths_stay_closed(): void
    {
        $owner = $this->makeOwner();
        $call = $this->openCall();

        foreach ([null, '', 'preduzetnica', 'privredno_drustvo', 'fizicko_lice', ['preduzetnik']] as $choice) {
            $payload = $choice === null ? [] : ['legal_form' => $choice];
            $this->actingAs($owner)->post(route('applications.canonical.start', $call), $payload)
                ->assertRedirect(route('applications.canonical.legal-form', $call))
                ->assertSessionHasErrors(['legal_form' => CanonicalApplicantFormService::LEGAL_FORM_REQUIRED_MESSAGE]);
        }

        $this->actingAs($owner)->post(route('applications.store', $call), [
            'applicant_type' => 'preduzetnik',
            'business_plan_name' => 'Legacy',
        ])->assertSessionHasErrors(['error' => ApplicationController::CANONICAL_OBRAZAC_1_MESSAGE]);
        $this->assertSame(0, Application::query()->where('competition_id', $call->id)->count());

        $formless = Application::create([
            'competition_id' => $call->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'draft',
            'pib' => '02345678',
        ]);
        $this->assertSame(CanonicalApplicantFormService::LEGAL_FORM_REQUIRED_MESSAGE, app(CanonicalApplicantFormService::class)->finalBlockReason($formless));
        $this->assertSame(CanonicalApplicantFormService::LEGAL_FORM_REQUIRED_MESSAGE, app(CanonicalDocumentService::class)->uploadBlockReason($formless->fresh('competition'), $owner));
        $this->assertSame([], $formless->fresh()->allowedUploadDocumentTypes());
        $this->actingAs($owner)->post(route('applications.final-submit', $formless))->assertSessionHasErrors('error');
        $this->assertSame('draft', $formless->fresh()->status);

        CompetitionProgramCatalog::clearTestOverrides();
        $this->actingAs($this->makeOwner())->get(route('applications.canonical.legal-form', $call))
            ->assertRedirect(route('competitions.index'))
            ->assertSessionHasErrors(['error' => CompetitionProgramCatalog::PROFILE_UNAVAILABLE_FOR_APPLICATIONS_MESSAGE]);
    }

    // ---------------------------------------------------------------- 3. P1 / P2 consistency

    public function test_p1_and_p2_subsidy_type_must_agree_and_final_claims_the_same_type(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall(), ['subsidy_type' => CanonicalSubsidyType::INNOVATIVE]);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->p2Payload(CanonicalSubsidyType::DIGITALIZATION))
            ->assertSessionHasErrors(['subsidy_type' => CanonicalApplicantFormService::SUBSIDY_MISMATCH_MESSAGE]);
        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->canonicalP1Payload($application, ['subsidy_type' => CanonicalSubsidyType::DIGITALIZATION]))
            ->assertSessionHasErrors(['subsidy_type' => CanonicalApplicantFormService::SUBSIDY_MISMATCH_MESSAGE]);
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, CanonicalInvestmentPlan::query()->where('application_id', $application->id)->value('subsidy_type'));
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, CanonicalApplicantForm::query()->where('application_id', $application->id)->value('subsidy_type'));

        // A divergence that bypassed the draft checks is still refused at FINAL.
        CanonicalApplicantForm::query()->where('application_id', $application->id)->update(['subsidy_type' => CanonicalSubsidyType::DIGITALIZATION]);
        $this->actingAs($owner)->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors(['error' => CanonicalApplicantFormService::SUBSIDY_MISMATCH_MESSAGE]);
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());

        CanonicalApplicantForm::query()->where('application_id', $application->id)->update(['subsidy_type' => CanonicalSubsidyType::INNOVATIVE]);
        $this->actingAs($owner)->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));

        $claim = FinalApplicationClaim::query()->where('application_id', $application->id)->sole();
        $basis = app(CanonicalReviewBasis::class);
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, $claim->subsidy_type);
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, $basis->frozenP1($application->fresh())['subsidy_type']);
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, $basis->snapshot($application->fresh())['canonical_p2']['subsidy_type']);
    }

    public function test_untyped_p1_blocks_final(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall(), ['p1_subsidy_type' => null]);

        $this->actingAs($owner)->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors(['error' => 'Obrazac P1a nije kompletan. Nedostaje: vrsta subvencije.']);
        $this->assertSame('draft', $application->fresh()->status);
    }

    // ---------------------------------------------------------------- 4. P1a catalog

    public function test_p1a_catalog_has_thirteen_items_with_conditional_metadata(): void
    {
        $catalog = Catalog::forLegalForm(CanonicalLegalForm::PREDUZETNIK);
        $items = $catalog->items();

        $this->assertSame(CanonicalMmspPolicy::DOCUMENT_CATALOG_HOOK, Catalog::HOOK);
        $this->assertCount(13, $items);
        $this->assertSame(range(1, 13), array_column($items, 'item'));
        $this->assertSame([Catalog::FORM_P1, Catalog::FORM_P2], array_column(array_slice($items, 0, 2), 'key'));
        $this->assertSame([Catalog::SOURCE_FORM, Catalog::SOURCE_FORM], array_column(array_slice($items, 0, 2), 'source'));
        $this->assertStringContainsString('Obrazac P1a', $items[0]['label']);
        $this->assertSame(self::P1A_UPLOADS, $catalog->uploadTypes());

        $this->assertSame([Catalog::VARIANT_VAT_REGISTERED, Catalog::VARIANT_VAT_NOT_REGISTERED], array_keys($catalog->variantsFor(Catalog::TYPE_VAT)));
        $this->assertSame([Catalog::VARIANT_IOPPD, Catalog::VARIANT_NO_EMPLOYEES], array_keys($catalog->variantsFor(Catalog::TYPE_IOPPD)));
        $ioppd = collect($items)->firstWhere('key', Catalog::TYPE_IOPPD);
        $this->assertSame(Catalog::CONDITIONAL, $ioppd['requirement']);
        $this->assertStringContainsString('broja zaposlenih', (string) $ioppd['condition']);

        $thirtyDay = array_values(array_filter($catalog->uploadTypes(), fn (string $type) => $catalog->requiresIssueDate($type)));
        $this->assertSame(self::THIRTY_DAY_TYPES, $thirtyDay);
        foreach (self::THIRTY_DAY_TYPES as $type) {
            $this->assertSame(30, collect($items)->firstWhere('key', $type)['validity_days']);
        }

        $this->assertStringContainsString('na ime preduzetnika', $catalog->label('potvrda_neosudjivanost'));
        foreach (['statut', 'karton_potpisa', 'godisnji_racuni', 'izvjestaj_registar_kase', 'ostalo', 'biznis_plan', Catalog::FORM_P1, '', 'nepoznato'] as $foreign) {
            $this->assertFalse($catalog->allowsUploadType($foreign), $foreign);
        }
        $this->assertFalse($catalog->allowsUploadType(null));
        $this->assertWithoutWomenWording($catalog->labels());

        foreach ([null, '', 'preduzetnica', 'privredno_drustvo'] as $invalid) {
            $this->assertSame([], Catalog::forLegalForm($invalid)->items());
            $this->assertFalse(Catalog::forLegalForm($invalid)->allowsUploadType('licna_karta'));
        }
    }

    public function test_p1a_upload_rejects_unknown_types_and_records_declared_alternatives_and_issue_dates(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall(), [
            'without_documents' => ['pdv_resenje', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost'],
        ]);
        $before = $application->documents()->count();

        foreach (['statut', 'ostalo', 'izvjestaj_registar_kase', 'biznis_plan'] as $unknown) {
            $this->uploadFromLibrary($owner, $application, $unknown)
                ->assertSessionHasErrors(['document_type' => CanonicalDocumentService::UNKNOWN_TYPE_MESSAGE]);
        }
        $this->assertSame($before, $application->documents()->count());

        $this->uploadFromLibrary($owner, $application, Catalog::TYPE_VAT)->assertSessionHasErrors('document_variant');
        $this->uploadFromLibrary($owner, $application, Catalog::TYPE_VAT, ['document_variant' => 'izmisljeno'])->assertSessionHasErrors('document_variant');
        $this->uploadFromLibrary($owner, $application, Catalog::TYPE_VAT, ['document_variant' => Catalog::VARIANT_VAT_NOT_REGISTERED])
            ->assertSessionHasNoErrors();
        $vat = CanonicalDocumentEvidence::query()->where('application_id', $application->id)->where('document_type', Catalog::TYPE_VAT)->sole();
        $this->assertSame(Catalog::VARIANT_VAT_NOT_REGISTERED, $vat->variant);
        $this->assertNull($vat->issued_on);
        $this->uploadFromLibrary($owner, $application, Catalog::TYPE_VAT, ['document_variant' => Catalog::VARIANT_VAT_REGISTERED])
            ->assertSessionHasErrors('document_type');

        $porezi = 'uvjerenje_opstina_porezi';
        $this->uploadFromLibrary($owner, $application, $porezi)->assertSessionHasErrors('issued_on');
        $this->uploadFromLibrary($owner, $application, $porezi, ['issued_on' => 'jucer'])->assertSessionHasErrors('issued_on');
        $this->uploadFromLibrary($owner, $application, $porezi, ['issued_on' => now()->subDays(31)->toDateString()])->assertSessionHasErrors('issued_on');
        $this->uploadFromLibrary($owner, $application, $porezi, ['issued_on' => now()->addDay()->toDateString()])->assertSessionHasErrors('issued_on');
        $this->assertFalse($application->documents()->where('document_type', $porezi)->exists());

        $this->uploadFromLibrary($owner, $application, $porezi, ['issued_on' => now()->subDays(30)->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(
            now()->subDays(30)->toDateString(),
            CanonicalDocumentEvidence::query()->where('application_id', $application->id)->where('document_type', $porezi)->sole()->issued_on->toDateString()
        );

        // PDF only: a library image and an uploaded image are both refused.
        $image = $this->libraryDocument($owner, 'nekretnine.jpg');
        $this->uploadFromLibrary($owner, $application, 'uvjerenje_opstina_nepokretnost', ['issued_on' => now()->toDateString()], $image)
            ->assertSessionHasErrors('user_document_id');
        $this->actingAs($owner)->post(route('applications.upload', $application), [
            'document_type' => 'uvjerenje_opstina_nepokretnost',
            'issued_on' => now()->toDateString(),
            'files' => [UploadedFile::fake()->create('nekretnine.jpg', 10, 'image/jpeg')],
        ])->assertSessionHasErrors('files.0');
        $this->assertFalse($application->documents()->where('document_type', 'uvjerenje_opstina_nepokretnost')->exists());

        $this->uploadFromLibrary($owner, $application, 'uvjerenje_opstina_nepokretnost', ['issued_on' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect(route('applications.show', $application));
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_processed_library_documents_are_available_and_can_be_attached(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall(), [
            'without_documents' => ['licna_karta'],
        ]);
        $processed = $this->libraryDocument($owner, 'obradjeni-prilog.pdf');
        $processed->update(['status' => 'processed']);
        $pending = $this->libraryDocument($owner, 'prilog-u-obradi.pdf');
        $pending->update(['status' => 'pending']);

        $this->actingAs($owner)->get(route('applications.canonical-documents.index', $application))
            ->assertOk()
            ->assertSee('obradjeni-prilog.pdf')
            ->assertDontSee('prilog-u-obradi.pdf');

        $this->uploadFromLibrary($owner, $application, 'licna_karta', [], $pending)
            ->assertSessionHasErrors('user_document_id');
        $this->uploadFromLibrary($owner, $application, 'licna_karta', [], $processed)
            ->assertSessionHasNoErrors();

        $this->assertTrue($application->documents()->where('document_type', 'licna_karta')->exists());
    }

    public function test_p1a_employee_alternative_follows_obrazac_p1a_employee_count(): void
    {
        $call = $this->openCall();
        $documents = app(CanonicalDocumentService::class);

        $withStaff = $this->canonicalDraft($this->makeOwner(), $call, ['variants' => [Catalog::TYPE_IOPPD => Catalog::VARIANT_NO_EMPLOYEES]]);
        $this->assertStringContainsString('prilaže se IOPPD obrazac', (string) $documents->finalBlockReason($withStaff, now()));
        $this->actingAs($withStaff->user)->post(route('applications.final-submit', $withStaff))->assertSessionHasErrors('error');
        $this->assertSame('draft', $withStaff->fresh()->status);

        $noStaffWrong = $this->canonicalDraft($this->makeOwner(), $call, ['employee_count' => 0, 'variants' => [Catalog::TYPE_IOPPD => Catalog::VARIANT_IOPPD]]);
        $this->assertStringContainsString('nema zaposlenih', (string) $documents->finalBlockReason($noStaffWrong, now()));

        $noStaff = $this->canonicalDraft($this->makeOwner(), $call, ['employee_count' => 0]);
        $this->assertSame(Catalog::VARIANT_NO_EMPLOYEES, CanonicalDocumentEvidence::query()->where('application_id', $noStaff->id)->where('document_type', Catalog::TYPE_IOPPD)->value('variant'));
        $this->assertNull($documents->finalBlockReason($noStaff, now()));
        $this->actingAs($noStaff->user)->post(route('applications.final-submit', $noStaff))->assertRedirect(route('applications.show', $noStaff));
    }

    public function test_thirty_day_validity_and_missing_items_are_rechecked_at_final(): void
    {
        $call = $this->openCall();

        $stale = $this->canonicalDraft($this->makeOwner(), $call, ['issued_on' => now()->subDays(31)->toDateString()]);
        $reason = (string) app(CanonicalDocumentService::class)->finalBlockReason($stale, now());
        $this->assertStringContainsString('stariji od 30 dana', $reason);
        $this->actingAs($stale->user)->post(route('applications.final-submit', $stale))->assertSessionHasErrors('error');
        $this->assertSame('draft', $stale->fresh()->status);

        $missing = $this->canonicalDraft($this->makeOwner(), $call, ['without_documents' => ['dokaz_ziro_racun']]);
        $this->assertStringContainsString('Nedostaje prilog: Dokaz o broju poslovnog žiro računa.', (string) app(CanonicalDocumentService::class)->finalBlockReason($missing, now()));
        $this->actingAs($missing->user)->post(route('applications.final-submit', $missing))->assertSessionHasErrors('error');

        $boundary = $this->canonicalDraft($this->makeOwner(), $call, ['issued_on' => now()->subDays(30)->toDateString()]);
        $this->actingAs($boundary->user)->post(route('applications.final-submit', $boundary))->assertRedirect(route('applications.show', $boundary));
        $this->assertSame('submitted', $boundary->fresh()->status);
    }

    // ---------------------------------------------------------------- 5. P1b catalog

    public function test_p1b_catalog_has_fifteen_items_with_current_year_and_cash_register_rules(): void
    {
        $catalog = Catalog::forLegalForm(CanonicalLegalForm::DRUSTVO);
        $items = $catalog->items();

        $this->assertCount(15, $items);
        $this->assertSame(range(1, 15), array_column($items, 'item'));
        $this->assertStringContainsString('Obrazac P1b', $items[0]['label']);
        $this->assertSame(self::P1B_UPLOADS, $catalog->uploadTypes());
        $this->assertStringContainsString('ovlašćenog lica', $catalog->label('licna_karta'));
        $this->assertStringContainsString('društva, izvršnog direktora i osnivača', $catalog->label('potvrda_neosudjivanost'));
        $this->assertSame([Catalog::VARIANT_VAT_REGISTERED, Catalog::VARIANT_VAT_NOT_REGISTERED], array_keys($catalog->variantsFor(Catalog::TYPE_VAT)));
        $this->assertSame([], $catalog->variantsFor(Catalog::TYPE_IOPPD));
        $this->assertSame([Catalog::VARIANT_WITH_CUSTOMER_ANALYTICS, Catalog::VARIANT_WITHOUT_CUSTOMER_ANALYTICS], array_keys($catalog->variantsFor(Catalog::TYPE_ANNUAL_ACCOUNTS)));

        $annual = collect($items)->firstWhere('key', Catalog::TYPE_ANNUAL_ACCOUNTS);
        $this->assertSame(9, $annual['item']);
        $this->assertSame(Catalog::CONDITIONAL, $annual['requirement']);
        $this->assertSame(Catalog::TYPE_CASH_REGISTER, $annual['supplement']['key']);
        $this->assertSame(self::THIRTY_DAY_TYPES, array_values(array_filter($catalog->uploadTypes(), fn (string $type) => $catalog->requiresIssueDate($type))));
        foreach (['dokaz_ziro_racun', 'ostalo', 'biznis_plan', Catalog::FORM_P2] as $foreign) {
            $this->assertFalse($catalog->allowsUploadType($foreign), $foreign);
        }
        $this->assertWithoutWomenWording($catalog->labels());

        $callYear = (int) now()->format('Y');
        $forCall = Catalog::forLegalForm(CanonicalLegalForm::DRUSTVO, $callYear);
        $established = new CanonicalApplicantForm(['legal_form' => CanonicalLegalForm::DRUSTVO, 'registration_date' => '2020-03-15']);
        $newThisYear = new CanonicalApplicantForm(['legal_form' => CanonicalLegalForm::DRUSTVO, 'registration_date' => now()->startOfYear()->toDateString()]);
        $withoutAnalytics = [Catalog::TYPE_ANNUAL_ACCOUNTS => new CanonicalDocumentEvidence(['variant' => Catalog::VARIANT_WITHOUT_CUSTOMER_ANALYTICS])];

        $required = $forCall->requiredUploadTypes($established, [], now());
        $this->assertContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $required);
        $this->assertNotContains(Catalog::TYPE_CASH_REGISTER, $required);
        $this->assertContains(Catalog::TYPE_CASH_REGISTER, $forCall->requiredUploadTypes($established, $withoutAnalytics, now()));
        $this->assertNotContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $forCall->requiredUploadTypes($newThisYear, [], now()));
        // The exception follows the call year, not the date of the check; without a call year it never applies.
        $this->assertNotContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $forCall->requiredUploadTypes($newThisYear, [], now()->addYears(2)));
        $this->assertContains(Catalog::TYPE_ANNUAL_ACCOUNTS, Catalog::forLegalForm(CanonicalLegalForm::DRUSTVO, $callYear - 1)->requiredUploadTypes($newThisYear, [], now()));
        $this->assertContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $catalog->requiredUploadTypes($newThisYear, [], now()));
        $this->assertFalse($catalog->foundedInCurrentYear($newThisYear));
        $this->assertNotContains(Catalog::TYPE_ANNUAL_ACCOUNTS, Catalog::forLegalForm(CanonicalLegalForm::PREDUZETNIK, $callYear)->requiredUploadTypes($established, [], now()));
    }

    public function test_p1b_final_applies_current_year_exception_and_cash_register_alternative(): void
    {
        $call = $this->openCall();
        $documents = app(CanonicalDocumentService::class);

        $founded = $this->canonicalDraft($this->makeOwner(), $call, [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
            'without_documents' => [Catalog::TYPE_ANNUAL_ACCOUNTS],
        ]);
        $this->assertStringContainsString('Nedostaje prilog: Komplet obrazaca za godišnje račune', (string) $documents->finalBlockReason($founded, now()));

        $newThisYear = $this->canonicalDraft($this->makeOwner(), $call, [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
            'registration_date' => now()->startOfYear()->toDateString(),
            'without_documents' => [Catalog::TYPE_ANNUAL_ACCOUNTS],
        ]);
        $this->assertNull($documents->finalBlockReason($newThisYear, now()));
        $this->actingAs($newThisYear->user)->post(route('applications.final-submit', $newThisYear))->assertRedirect(route('applications.show', $newThisYear));

        $cashOnly = $this->canonicalDraft($this->makeOwner(), $call, [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
            'variants' => [Catalog::TYPE_ANNUAL_ACCOUNTS => Catalog::VARIANT_WITHOUT_CUSTOMER_ANALYTICS],
            'without_documents' => [Catalog::TYPE_CASH_REGISTER],
        ]);
        $this->assertStringContainsString('Nedostaje prilog: Periodični izvještaj sa registra kase.', (string) $documents->finalBlockReason($cashOnly, now()));
        $this->actingAs($cashOnly->user)->post(route('applications.final-submit', $cashOnly))->assertSessionHasErrors('error');

        $this->uploadFromLibrary($cashOnly->user, $cashOnly, 'dokaz_ziro_racun')
            ->assertSessionHasErrors(['document_type' => CanonicalDocumentService::UNKNOWN_TYPE_MESSAGE]);
        $this->uploadFromLibrary($cashOnly->user, $cashOnly, Catalog::TYPE_CASH_REGISTER)->assertSessionHasNoErrors();
        $this->assertNull($documents->finalBlockReason($cashOnly->fresh(), now()));
        $this->actingAs($cashOnly->user)->post(route('applications.final-submit', $cashOnly))->assertRedirect(route('applications.show', $cashOnly));
    }

    public function test_p1b_current_year_exception_uses_the_call_year_not_the_server_date(): void
    {
        $callYear = (int) now()->format('Y');
        $foundedThisYear = now()->startOfYear()->toDateString();

        $sameYearCall = $this->canonicalDraft($this->makeOwner(), $this->openCall(['year' => $callYear]), [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
            'registration_date' => $foundedThisYear,
            'without_documents' => [Catalog::TYPE_ANNUAL_ACCOUNTS],
        ]);
        $previousYearCall = $this->canonicalDraft($this->makeOwner(), $this->openCall(['year' => $callYear - 1]), [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
            'registration_date' => $foundedThisYear,
            'without_documents' => [Catalog::TYPE_ANNUAL_ACCOUNTS],
        ]);

        $this->assertSame($callYear, $sameYearCall->canonicalDocumentCatalog()->callYear);
        $this->assertNotContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $sameYearCall->getStrictlyRequiredDocuments());
        $this->assertContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $previousYearCall->getStrictlyRequiredDocuments());

        // Evaluated after New Year (e.g. commission review), the same call keeps the same answer.
        $this->travelTo(now()->addYear()->startOfYear()->addDays(9));
        try {
            $this->assertNotContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $sameYearCall->fresh()->getStrictlyRequiredDocuments());
            $row = collect(app(CanonicalDocumentService::class)->checklist($sameYearCall->fresh(), now()))->firstWhere('key', Catalog::TYPE_ANNUAL_ACCOUNTS);
            $this->assertFalse($row['required_now']);
            $this->assertContains(Catalog::TYPE_ANNUAL_ACCOUNTS, $previousYearCall->fresh()->getStrictlyRequiredDocuments());
        } finally {
            $this->travelBack();
        }
    }

    // ---------------------------------------------------------------- 6. FINAL validation and freeze

    public function test_p1a_jmbg_is_bound_at_final_encrypted_and_cannot_be_replaced_afterwards(): void
    {
        config(['jmb.plaintext_retirement.enabled' => false]);
        $owner = $this->makeOwner();
        $verifiedJmb = (string) $owner->jmb;
        $application = $this->canonicalDraft($owner, $this->openCall());
        $this->assertFalse($application->hasApplicantJmbgSnapshot());

        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect(route('applications.show', $application));
        $application->refresh();
        $this->assertTrue($application->hasApplicantJmbgSnapshot());
        $this->assertNotNull($application->applicant_jmbg_encrypted);
        $this->assertNotSame($verifiedJmb, $application->applicant_jmbg_encrypted);
        $this->assertSame($verifiedJmb, $application->applicantJmbgForRead());

        $owner->update(['jmb' => '0202991000777']);
        $this->assertSame($verifiedJmb, $application->fresh()->resolvedApplicantJmbg());

        $replace = $application->fresh();
        JmbDualWrite::assignLogical($replace, 'applicant_jmbg', '0202991000777');
        $this->assertThrowsImmutable(fn () => $replace->save(), Application::CANONICAL_FINAL_JMBG_LOCKED_MESSAGE);
        $this->assertThrowsImmutable(fn () => $application->fresh()->update(['applicant_jmbg' => '0303992000888']), Application::CANONICAL_FINAL_JMBG_LOCKED_MESSAGE);
        $this->assertThrowsImmutable(fn () => $application->fresh()->update(['applicant_jmbg' => '']), Application::CANONICAL_FINAL_JMBG_LOCKED_MESSAGE);
        $this->assertSame($verifiedJmb, $application->fresh()->applicantJmbgForRead());

        // Plaintext retirement keeps the encrypted value and stays possible.
        config(['jmb.plaintext_retirement.enabled' => true]);
        $application->fresh()->update(['applicant_jmbg' => null]);
        $retired = $application->fresh();
        $this->assertNull($retired->applicant_jmbg);
        $this->assertSame($verifiedJmb, $retired->applicantJmbgForRead());

        $this->assertStringNotContainsString($verifiedJmb, json_encode(app(CanonicalReviewBasis::class)->snapshot($retired), JSON_UNESCAPED_UNICODE));
        $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->assertDontSee($verifiedJmb);
        $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))->assertOk()->assertDontSee($verifiedJmb);

        // Women applications keep their existing behaviour.
        config(['jmb.plaintext_retirement.enabled' => false]);
        $women = Application::create([
            'competition_id' => $this->openCall(['type' => 'zensko'])->id,
            'user_id' => $this->makeOwner()->id,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        JmbDualWrite::assignLogical($women, 'applicant_jmbg', '0404993000999');
        $women->save();
        JmbDualWrite::assignLogical($women, 'applicant_jmbg', '0505994000111');
        $women->save();
        $this->assertSame('0505994000111', $women->fresh()->applicantJmbgForRead());
    }

    public function test_final_freezes_p1_p2_and_document_evidence_and_blocks_every_applicant_write(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall());
        $licna = $application->documents()->where('document_type', 'licna_karta')->sole();

        $this->actingAs($owner)->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));
        $application->refresh();

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->sole();
        $this->assertNotNull($form->frozen_at);
        $this->assertNotNull(CanonicalInvestmentPlan::query()->where('application_id', $application->id)->value('frozen_at'));
        $this->assertSame($application->documents()->count(), CanonicalDocumentEvidence::query()->where('application_id', $application->id)->whereNotNull('frozen_at')->count());
        $this->assertNull($application->businessPlan()->first());
        $this->assertTrue($application->hasApplicantJmbgSnapshot());

        $snapshot = app(CanonicalReviewBasis::class)->snapshot($application);
        $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $snapshot['canonical_p1']['legal_form']);
        $this->assertSame('P1a', $snapshot['canonical_p1']['form']);
        $this->assertSame('Marko Marković', $snapshot['canonical_p1']['person_name']);
        $this->assertSame('56.10', $snapshot['canonical_p1']['activity_code']);
        $documentsByType = collect($snapshot['documents'])->keyBy('document_type');
        $this->assertSame(Catalog::VARIANT_VAT_REGISTERED, $documentsByType[Catalog::TYPE_VAT]['variant']);
        $this->assertSame(now()->toDateString(), $documentsByType['potvrda_upc_porezi']['issued_on']);
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString((string) $owner->jmb, $json);
        $this->assertStringNotContainsString('jmb', strtolower($json));

        $this->assertThrowsImmutable(fn () => $form->update(['person_name' => 'Izmjena']), CanonicalApplicantForm::FROZEN_MESSAGE);
        $this->assertThrowsImmutable(fn () => $form->delete(), CanonicalApplicantForm::FROZEN_MESSAGE);
        $evidence = CanonicalDocumentEvidence::query()->where('application_document_id', $licna->id)->sole();
        $this->assertThrowsImmutable(fn () => $evidence->update(['variant' => 'x']), CanonicalDocumentEvidence::FROZEN_MESSAGE);
        $this->assertThrowsImmutable(fn () => $evidence->delete(), CanonicalDocumentEvidence::FROZEN_MESSAGE);

        $this->actingAs($owner)->put(route('applications.canonical-p1.update', $application), $this->canonicalP1Payload($application, ['person_name' => 'Nakon FINAL']))
            ->assertSessionHasErrors(['error' => CanonicalApplicantForm::FROZEN_MESSAGE]);
        $this->actingAs($owner)->put(route('applications.canonical-p2.update', $application), $this->p2Payload(CanonicalSubsidyType::INNOVATIVE))
            ->assertSessionHasErrors('error');
        $this->uploadFromLibrary($owner, $application, 'predracuni_nabavka')->assertSessionHasErrors('error');
        $this->actingAs($owner)->delete(route('applications.document.destroy', [$application, $licna]))->assertSessionHasErrors('error');
        $this->assertSame('Marko Marković', $form->fresh()->person_name);
        $this->assertTrue(ApplicationDocument::query()->whereKey($licna->id)->exists());

        $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))
            ->assertOk()
            ->assertSee(CanonicalApplicantForm::FROZEN_MESSAGE)
            ->assertDontSee('Sačuvaj Obrazac P1a');
        $this->actingAs($owner)->get(route('applications.canonical-documents.index', $application))->assertOk();
    }

    public function test_p1b_final_persists_no_jmbg_and_keeps_pib_protection(): void
    {
        config(['jmb.plaintext_retirement.enabled' => false]);
        $call = $this->openCall();
        $pib = '05777888';
        $owner = $this->makeOwner();
        $accountJmb = (string) $owner->jmb;
        $application = $this->canonicalDraft($owner, $call, ['legal_form' => CanonicalLegalForm::DRUSTVO, 'pib' => $pib]);

        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect(route('applications.show', $application));
        $application->refresh();

        $this->assertSame('submitted', $application->status);
        $this->assertFalse($application->hasApplicantJmbgSnapshot());
        $row = (array) \Illuminate\Support\Facades\DB::table('applications')->where('id', $application->id)
            ->first(['applicant_jmbg', 'applicant_jmbg_encrypted', 'physical_person_jmbg', 'physical_person_jmbg_encrypted']);
        $this->assertSame([null, null, null, null], array_values($row));

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->sole();
        $this->assertNotNull($form->frozen_at);
        $this->assertSame('Primjer d.o.o.', $form->company_name);
        $this->assertSame('Odgovorno Lice', $form->person_name);
        $this->assertSame($pib, $application->pib);
        $this->assertTrue($application->documents()->where('document_type', 'licna_karta')->exists());
        $this->assertContains('licna_karta', Catalog::forLegalForm(CanonicalLegalForm::DRUSTVO)->uploadTypes());

        $snapshot = app(CanonicalReviewBasis::class)->snapshot($application);
        $this->assertSame('P1b', $snapshot['canonical_p1']['form']);
        $json = strtolower((string) json_encode($snapshot, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString($accountJmb, $json);
        $this->assertStringNotContainsString('jmb', $json);
        $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->assertDontSee($accountJmb);
        $p1Html = $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))->assertOk()->assertDontSee($accountJmb)->getContent();
        $this->assertStringNotContainsString('JMBG', $this->formSection($p1Html, route('applications.canonical-p1.update', $application)));

        // PIB-based limits still bind društvo applications across accounts.
        $sameType = $this->canonicalDraft($this->makeOwner(), $call, ['legal_form' => CanonicalLegalForm::DRUSTVO, 'pib' => $pib]);
        $this->actingAs($sameType->user)->post(route('applications.final-submit', $sameType))->assertSessionHasErrors('error');
        $otherType = $this->canonicalDraft($this->makeOwner(), $call, ['legal_form' => CanonicalLegalForm::DRUSTVO, 'pib' => $pib, 'subsidy_type' => CanonicalSubsidyType::DIGITALIZATION]);
        $this->actingAs($otherType->user)->post(route('applications.final-submit', $otherType))->assertRedirect(route('applications.show', $otherType));
        $third = $this->canonicalDraft($this->makeOwner(), $call, ['legal_form' => CanonicalLegalForm::DRUSTVO, 'pib' => $pib, 'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL]);
        $this->actingAs($third->user)->post(route('applications.final-submit', $third))->assertSessionHasErrors('error');
        $this->assertSame(2, FinalApplicationClaim::query()->where('competition_id', $call->id)->where('beneficiary_pib', $pib)->count());
        $this->assertSame(0, Application::query()->where('competition_id', $call->id)->whereNotNull('applicant_jmbg_encrypted')->count());
    }

    public function test_freeze_failure_rolls_back_p1_and_document_freeze(): void
    {
        $this->app->instance(ApplicationEvidenceFreezer::class, new class extends ApplicationEvidenceFreezer
        {
            public function freeze(Application $application): void
            {
                parent::freeze($application);
                throw new RuntimeException('freeze failed after canonical freeze');
            }
        });

        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall());

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($owner)->post(route('applications.final-submit', $application));
            $this->fail('Freeze failure should surface.');
        } catch (RuntimeException $exception) {
            $this->assertSame('freeze failed after canonical freeze', $exception->getMessage());
        }

        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
        $this->assertNull(CanonicalApplicantForm::query()->where('application_id', $application->id)->value('frozen_at'));
        $this->assertSame(0, CanonicalDocumentEvidence::query()->where('application_id', $application->id)->whereNotNull('frozen_at')->count());
    }

    // ---------------------------------------------------------------- 7. Two-application model and PIB

    public function test_second_different_type_application_through_the_ui_and_third_is_refused(): void
    {
        $owner = $this->makeOwner();
        $call = $this->openCall();
        $pib = $this->canonicalPibFor($owner);
        $first = $this->canonicalDraft($owner, $call, ['subsidy_type' => CanonicalSubsidyType::INNOVATIVE]);

        $this->actingAs($owner)->get(route('applications.canonical.legal-form', $call))
            ->assertRedirect(route('applications.show', $first));
        $this->actingAs($owner)->post(route('applications.canonical.start', $call), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])
            ->assertRedirect(route('applications.show', $first));
        $this->assertSame(1, Application::query()->where('user_id', $owner->id)->count());

        $this->actingAs($owner)->post(route('applications.final-submit', $first))->assertRedirect(route('applications.show', $first));
        $this->actingAs($owner)->get(route('applications.show', $first))
            ->assertOk()
            ->assertSee('Započni drugu prijavu')
            ->assertSee('Status provjere potpunosti / prigovor');

        $second = $this->startedDraft($owner, $call, CanonicalLegalForm::PREDUZETNIK);
        $this->assertNotSame($first->id, $second->id);

        $this->actingAs($owner)->put(route('applications.canonical-p1.update', $second), $this->p1aPayload($pib, CanonicalSubsidyType::INNOVATIVE))
            ->assertSessionHasErrors(['subsidy_type' => CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE]);
        $this->actingAs($owner)->put(route('applications.canonical-p1.update', $second), $this->p1aPayload($pib, CanonicalSubsidyType::DIGITALIZATION))
            ->assertSessionHasNoErrors();
        $this->actingAs($owner)->put(route('applications.canonical-p2.update', $second), $this->p2Payload(CanonicalSubsidyType::DIGITALIZATION))
            ->assertSessionHasNoErrors();
        $this->attachCanonicalDocuments($second);

        $this->actingAs($owner)->post(route('applications.final-submit', $second))->assertRedirect(route('applications.show', $second));
        $this->assertEqualsCanonicalizing(
            [CanonicalSubsidyType::INNOVATIVE, CanonicalSubsidyType::DIGITALIZATION],
            FinalApplicationClaim::query()->where('user_id', $owner->id)->pluck('subsidy_type')->all()
        );

        $this->actingAs($owner)->get(route('applications.canonical.legal-form', $call))
            ->assertRedirect(route('competitions.show', $call))
            ->assertSessionHasErrors(['error' => CanonicalApplicantFormService::START_LIMIT_MESSAGE]);
        $this->actingAs($owner)->post(route('applications.canonical.start', $call), ['legal_form' => CanonicalLegalForm::DRUSTVO])
            ->assertSessionHasErrors(['error' => CanonicalApplicantFormService::START_LIMIT_MESSAGE]);
        $this->actingAs($owner)->get(route('applications.show', $second))->assertOk()->assertDontSee('Započni drugu prijavu');
        $this->assertSame(2, Application::query()->where('user_id', $owner->id)->count());
    }

    public function test_same_type_duplicate_is_refused_at_final(): void
    {
        $owner = $this->makeOwner();
        $call = $this->openCall();
        $first = $this->canonicalDraft($owner, $call, ['subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL]);
        $this->actingAs($owner)->post(route('applications.final-submit', $first))->assertRedirect();

        $duplicate = $this->canonicalDraft($owner, $call, ['subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL]);
        $this->actingAs($owner)->post(route('applications.final-submit', $duplicate))->assertSessionHasErrors('error');

        $this->assertSame('draft', $duplicate->fresh()->status);
        $this->assertNull(CanonicalApplicantForm::query()->where('application_id', $duplicate->id)->value('frozen_at'));
        $this->assertSame(1, FinalApplicationClaim::query()->where('competition_id', $call->id)->count());
    }

    public function test_pib_is_protected_across_accounts(): void
    {
        $call = $this->openCall();
        $pib = '04555666';

        $first = $this->canonicalDraft($this->makeOwner(), $call, ['pib' => $pib, 'subsidy_type' => CanonicalSubsidyType::INNOVATIVE]);
        $this->actingAs($first->user)->post(route('applications.final-submit', $first))->assertRedirect(route('applications.show', $first));

        $sameType = $this->canonicalDraft($this->makeOwner(), $call, ['pib' => $pib, 'subsidy_type' => CanonicalSubsidyType::INNOVATIVE]);
        $this->actingAs($sameType->user)->put(route('applications.canonical-p1.update', $sameType), $this->canonicalP1Payload($sameType))
            ->assertSessionHasErrors(['subsidy_type' => CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE]);
        $this->actingAs($sameType->user)->post(route('applications.final-submit', $sameType))->assertSessionHasErrors('error');
        $this->assertSame('draft', $sameType->fresh()->status);

        $otherType = $this->canonicalDraft($this->makeOwner(), $call, ['pib' => $pib, 'subsidy_type' => CanonicalSubsidyType::DIGITALIZATION]);
        $this->actingAs($otherType->user)->post(route('applications.final-submit', $otherType))->assertRedirect(route('applications.show', $otherType));

        $third = $this->canonicalDraft($this->makeOwner(), $call, ['pib' => $pib, 'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL]);
        $this->actingAs($third->user)->post(route('applications.final-submit', $third))->assertSessionHasErrors('error');
        $this->assertSame('draft', $third->fresh()->status);

        $this->assertSame(2, FinalApplicationClaim::query()->where('competition_id', $call->id)->where('beneficiary_pib', $pib)->count());
    }

    // ---------------------------------------------------------------- 8. Deadline and freeze locks

    public function test_post_deadline_blocks_start_p1_uploads_and_final(): void
    {
        $owner = $this->makeOwner();
        $call = $this->openCall(['start_date' => now()->subDays(30)->toDateString()]);
        $application = $this->canonicalDraft($owner, $call, ['without_documents' => ['predracuni_nabavka']]);
        $this->assertFalse($call->fresh()->is_open);

        $this->actingAs($owner)->put(route('applications.canonical-p1.update', $application), $this->canonicalP1Payload($application, ['person_name' => 'Kasna Izmjena']))
            ->assertSessionHasErrors(['error' => 'Prijava je zaključana i Obrazac P1 više nije moguće mijenjati.']);
        $this->assertSame('Marko Marković', CanonicalApplicantForm::query()->where('application_id', $application->id)->value('person_name'));

        $this->uploadFromLibrary($owner, $application, 'predracuni_nabavka')->assertSessionHasErrors('error');
        $this->assertFalse($application->documents()->where('document_type', 'predracuni_nabavka')->exists());
        $this->assertSame(CanonicalDocumentService::LOCKED_MESSAGE, app(CanonicalDocumentService::class)->uploadBlockReason($application->fresh('competition'), $owner));

        $this->actingAs($this->makeOwner())->get(route('applications.canonical.legal-form', $call))
            ->assertRedirect(route('competitions.show', $call))
            ->assertSessionHasErrors(['error' => 'Rok za prijave je istekao.']);
        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);

        $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))
            ->assertOk()
            ->assertSee('Prijava je zaključana i Obrazac P1a više nije moguće mijenjati.')
            ->assertDontSee('Sačuvaj Obrazac P1a');
    }

    public function test_other_users_cannot_read_or_write_a_canonical_application(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall());
        $intruder = $this->makeOwner();

        $this->actingAs($intruder)->get(route('applications.canonical-p1.edit', $application))->assertForbidden();
        $this->actingAs($intruder)->put(route('applications.canonical-p1.update', $application), $this->canonicalP1Payload($application, ['person_name' => 'Uljez']))->assertForbidden();
        $this->actingAs($intruder)->get(route('applications.canonical-documents.index', $application))->assertForbidden();
        $this->actingAs($intruder)->get(route('applications.show', $application))->assertForbidden();
        $this->actingAs($intruder)->post(route('applications.upload', $application), ['document_type' => 'licna_karta'])->assertForbidden();
        $this->assertSame('Marko Marković', CanonicalApplicantForm::query()->where('application_id', $application->id)->value('person_name'));
    }

    // ---------------------------------------------------------------- 9. Profile isolation

    public function test_women_and_youth_never_reach_canonical_p1_or_catalog(): void
    {
        foreach (['zensko', 'omladinsko'] as $type) {
            $owner = $this->makeOwner();
            $call = $this->openCall(['type' => $type]);
            $application = Application::create([
                'competition_id' => $call->id,
                'user_id' => $owner->id,
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'status' => 'draft',
            ]);

            $this->actingAs($owner)->get(route('applications.canonical.legal-form', $call))->assertNotFound();
            $this->actingAs($owner)->post(route('applications.canonical.start', $call), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])->assertNotFound();
            $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))->assertNotFound();
            $this->actingAs($owner)->put(route('applications.canonical-p1.update', $application), ['person_name' => 'X'])->assertNotFound();
            $this->actingAs($owner)->get(route('applications.canonical-documents.index', $application))->assertNotFound();

            $fresh = $application->fresh();
            $this->assertFalse($fresh->isCanonicalProfile(), $type);
            $this->assertNull($fresh->canonicalDocumentCatalog(), $type);
            $this->assertNotSame(self::P1A_UPLOADS, $fresh->allowedUploadDocumentTypes(), $type);
            $this->assertNotSame(self::P1B_UPLOADS, $fresh->allowedUploadDocumentTypes(), $type);
        }

        $this->assertSame(0, CanonicalApplicantForm::query()->count());
        $this->assertSame(0, CanonicalDocumentEvidence::query()->count());
    }

    public function test_canonical_has_no_women_business_plan(): void
    {
        $owner = $this->makeOwner();
        $application = $this->canonicalDraft($owner, $this->openCall());

        $this->actingAs($owner)->get(route('applications.business-plan.create', $application))
            ->assertRedirect(route('applications.canonical-p2.edit', $application));
        $this->actingAs($owner)->post(route('applications.business-plan.store', $application), ['business_idea_name' => 'Ženski plan'])
            ->assertRedirect(route('applications.canonical-p2.edit', $application));
        $this->actingAs($this->makeOwner())->get(route('applications.business-plan.create', $application))->assertForbidden();
        $this->assertSame(0, BusinessPlan::query()->where('application_id', $application->id)->count());

        $this->actingAs($owner)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Obrazac P1a')
            ->assertSee('Plan ulaganja (Obrazac P2)')
            ->assertDontSee('Biznis plan');
    }

    // ---------------------------------------------------------------- 10. Completeness handoff (KN-SYNC-014 UI)

    public function test_completeness_review_shows_frozen_p1_and_catalog_evidence(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $owner = $this->makeOwner();
        $call = $this->openCall(['commission_id' => $commission->id]);
        $application = $this->canonicalDraft($owner, $call);

        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect(route('applications.show', $application));
        $application->refresh();
        $basis = app(CanonicalReviewBasis::class);
        $fingerprint = $basis->fingerprint($basis->snapshot($application));

        $this->travel(25)->days();
        $this->confirmOrdinarySession($call);

        $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $application))
            ->assertOk()
            ->assertSee('data-section="canonical-review-p1"', false)
            ->assertSee('Obrazac P1a — podaci o podnosiocu')
            ->assertSee('Ime i prezime preduzetnika: Marko Marković')
            ->assertSee('Šifra djelatnosti: 56.10')
            ->assertSee('Rješenje o registraciji za PDV ili potvrda da nije PDV obveznik')
            ->assertSee('Rješenje o registraciji za PDV (PDV obveznik)')
            ->assertSee('izdat')
            ->assertSee($fingerprint)
            ->assertSee('data-form="completeness"', false)
            ->assertDontSee((string) $owner->jmb);

        $this->actingAs($chair)
            ->from(route('evaluation.canonical-review.show', $application))
            ->post(route('evaluation.completeness', $application), ['outcome' => 'complete', 'notes' => 'Kompletno po čl.14.'])
            ->assertRedirect(route('evaluation.canonical-review.show', $application))
            ->assertSessionHas('success');

        $assessment = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->sole();
        $this->assertSame($fingerprint, $assessment->evidence_fingerprint);

        $this->actingAs($owner)->get(route('applications.canonical-review.show', $application))->assertOk();
    }

    // ---------------------------------------------------------------- Helpers

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function openCall(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => 'KN-SYNC-015-'.uniqid(),
            'description' => 'Kanonski P1a/P1b',
            'type' => self::CANONICAL,
            'status' => 'published',
            'year' => (int) now()->format('Y'),
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ], $overrides))->fresh();
    }

    private function makeOwner(): User
    {
        $this->jmbSequence++;

        return User::factory()->create([
            'email' => 'p1-owner-'.uniqid().'@example.com',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Test Podnosilac',
            'jmb' => '0101990'.str_pad((string) $this->jmbSequence, 6, '0', STR_PAD_LEFT),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
            'user_type' => 'Fizičko lice',
        ]);
    }

    private function startedDraft(User $owner, Competition $call, string $legalForm): Application
    {
        $before = Application::query()->where('user_id', $owner->id)->pluck('id')->all();
        $this->actingAs($owner)->post(route('applications.canonical.start', $call), ['legal_form' => $legalForm])
            ->assertSessionHasNoErrors();

        return Application::query()->where('user_id', $owner->id)->whereNotIn('id', $before)->sole()->fresh(['competition']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function p1aPayload(string $pib, ?string $subsidyType, array $overrides = []): array
    {
        return array_merge([
            'subsidy_type' => $subsidyType,
            'person_name' => 'Marko Marković',
            'pib' => $pib,
            'address' => 'Njegoševa 1, 85330 Kotor',
            'activity_code' => '56.10',
            'employee_count' => '3',
            'bank_account' => '510-0000000000001-11',
            'phone' => '067 123 456',
            'email' => 'marko@example.com',
            'website' => 'primjer.me',
            'registration_date' => '2021-05-10',
            'accuracy_confirmed' => '1',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function p2Payload(string $subsidyType): array
    {
        return [
            'subsidy_type' => $subsidyType,
            'total_investment' => '1000.00',
            'own_contribution' => '200.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [
                ['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => '1000.00'],
            ],
        ];
    }

    private function libraryDocument(User $owner, string $file = 'prilog.pdf'): UserDocument
    {
        return UserDocument::create([
            'user_id' => $owner->id,
            'category' => 'Ostali dokumenti',
            'name' => $file,
            'file_path' => 'documents/'.uniqid().'-'.$file,
            'original_filename' => $file,
            'file_size' => 3,
            'status' => 'active',
            'processed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function uploadFromLibrary(User $owner, Application $application, string $type, array $extra = [], ?UserDocument $document = null): \Illuminate\Testing\TestResponse
    {
        $document ??= $this->libraryDocument($owner);

        return $this->actingAs($owner)
            ->from(route('applications.canonical-documents.index', $application))
            ->post(route('applications.upload', $application), array_merge([
                'document_type' => $type,
                'user_document_id' => $document->id,
            ], $extra));
    }

    private function formSection(string $html, string $action): string
    {
        $start = strpos($html, 'action="'.$action.'"');
        $this->assertNotFalse($start, 'Form '.$action.' not rendered.');
        $end = strpos($html, '</form>', $start);

        return substr($html, $start, $end - $start);
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function assertWithoutWomenWording(array $labels): void
    {
        foreach ($labels as $key => $label) {
            foreach (self::WOMEN_WORDING as $word) {
                $this->assertStringNotContainsString($word, $label, $key);
            }
        }
    }

    private function assertThrowsImmutable(\Closure $mutation, string $message): void
    {
        try {
            $mutation();
            $this->fail('Expected immutability guard: '.$message);
        } catch (LogicException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
