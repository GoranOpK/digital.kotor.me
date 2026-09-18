<?php

namespace Tests\Unit;

use App\Models\Application;
use PHPUnit\Framework\TestCase;

/**
 * Patch 5: ŽP V1 document-package / email terminology (no DB).
 */
class ZpV1TerminologyCleanupTest extends TestCase
{
    public function test_commercial_company_document_labels_drop_nositeljka_biznisa(): void
    {
        foreach ([
            Application::registeredStartingCommercialCompanyDocumentLabels(),
            Application::unregisteredStartingCommercialCompanyDocumentLabels(),
            Application::registeredDevelopingCommercialCompanyDocumentLabels(),
        ] as $labels) {
            $blob = implode("\n", $labels);
            $this->assertStringNotContainsString('nositeljke biznisa', $blob);
            $this->assertStringNotContainsString('nositeljka biznisa', $blob);
            $this->assertStringNotContainsString('nosioca biznisa', $blob);
        }
    }

    public function test_registered_starting_company_uses_osnivacica_terminology(): void
    {
        $labels = Application::registeredStartingCommercialCompanyDocumentLabels();
        $this->assertSame(
            'Ovjerena kopija lične karte osnivačice ili jedne od osnivačica i izvršne direktorice',
            $labels['licna_karta']
        );
        $this->assertStringContainsString('podnositeljke prijave/osnivačice ili jedne od osnivačica i izvršne direktorice', $labels['potvrda_neosudjivanost']);
    }

    public function test_unregistered_planned_company_uses_podnositeljka_prijave(): void
    {
        $labels = Application::unregisteredStartingCommercialCompanyDocumentLabels();
        $this->assertSame('Ovjerena kopija lične karte podnositeljke prijave', $labels['licna_karta']);
        $this->assertStringContainsString('podnositeljke prijave', $labels['potvrda_neosudjivanost']);
        $this->assertStringNotContainsString('osnivačice', $labels['licna_karta']);
    }

    public function test_registered_developing_company_uses_osnivacica_and_company(): void
    {
        $labels = Application::registeredDevelopingCommercialCompanyDocumentLabels();
        foreach (['potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi'] as $key) {
            $this->assertStringContainsString('osnivačice ili jedne od osnivačica i izvršne direktorice', $labels[$key]);
            $this->assertStringContainsString('društva', $labels[$key]);
            $this->assertStringNotContainsString('osnivačice ili jedne od osnivačica koja je izvršna direktorica', $labels[$key]);
        }
    }

    public function test_commercial_company_form_titles_use_canonical_1b_name(): void
    {
        $titles = Application::startingCommercialCompanyFormTitles();
        $this->assertSame('Obrazac 1b – privredno društvo', $titles['obrazac_1b']);
        $this->assertSame(
            Application::startingCommercialCompanyFormTitles(),
            Application::developingCommercialCompanyFormTitles()
        );
        $this->assertNotSame('Obrazac 1b – Prijava na konkurs', $titles['obrazac_1b']);
    }

    public function test_active_zp_surfaces_use_same_person_company_wording_and_canonical_form_titles(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = [
            $root.'/app/Models/Application.php',
            $root.'/app/Http/Controllers/CompetitionsController.php',
            $root.'/resources/views/applications/show.blade.php',
            $root.'/resources/views/admin/applications/show.blade.php',
            $root.'/resources/views/competitions/show.blade.php',
        ];
        $patch6Wording = 'osnivačice ili jedne od osnivačica koja je izvršna direktorica';
        $article3Wording = 'osnivačice ili jedne od osnivačica i izvršne direktorice';

        foreach ($paths as $path) {
            $content = file_get_contents($path);
            $this->assertStringNotContainsString($patch6Wording, $content, basename($path));
            $this->assertStringNotContainsString('jedne od osnivača"', $content, basename($path));
            $this->assertStringNotContainsString('jedne od osnivača ', $content, basename($path));
            if (str_contains($content, 'osnivačice ili jedne od osnivačica')) {
                $this->assertStringContainsString($article3Wording, $content, basename($path));
            }
        }

        $competitionShow = file_get_contents($root.'/resources/views/competitions/show.blade.php');
        $controller = file_get_contents($root.'/app/Http/Controllers/CompetitionsController.php');
        $applicationModel = file_get_contents($root.'/app/Models/Application.php');

        $this->assertStringContainsString('Obrazac 1a – preduzetnica', $competitionShow);
        $this->assertStringContainsString('Obrazac 1a – preduzetnica', $controller);
        $this->assertStringContainsString("'obrazac_1b' => 'Obrazac 1b – privredno društvo'", $applicationModel);
        $this->assertStringContainsString('Obrazac 1b – privredno društvo', $controller);
        foreach ([$competitionShow, $controller, $applicationModel] as $surface) {
            $this->assertStringNotContainsString('Obrazac 1b – Prijava na konkurs', $surface);
            $this->assertStringNotContainsString('Prijava na konkurs za podsticaj ženskog preduzetništva (obrazac 1a)', $surface);
        }
    }

    public function test_six_document_package_compositions_unchanged_for_patch_6(): void
    {
        $packages = [
            ['fizicko_lice', 'započinjanje', false],
            ['preduzetnica', 'započinjanje', true],
            ['preduzetnica', 'razvoj', true],
            ['doo', 'započinjanje', false],
            ['doo', 'započinjanje', true],
            ['doo', 'razvoj', true],
        ];

        $expected = [
            ['licna_karta', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_zavod_nezaposleni', 'predracuni_nabavka'],
            ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'potvrda_zavod_nezaposleni', 'predracuni_nabavka'],
            ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'potvrda_zavod_nezaposleni', 'predracuni_nabavka'],
            ['licna_karta', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_zavod_nezaposleni', 'predracuni_nabavka'],
            ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_zavod_nezaposleni', 'predracuni_nabavka'],
            ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'godisnji_racuni', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'potvrda_zavod_nezaposleni', 'predracuni_nabavka'],
        ];

        foreach ($packages as $i => [$type, $stage, $registered]) {
            $docs = Application::getRequiredDocumentsForType($type, $stage, $registered);
            $this->assertSame($expected[$i], $docs, "package {$type}/{$stage}/".($registered ? 'reg' : 'unreg'));
            $this->assertContains(Application::DOCUMENT_POTVRDA_ZAVOD_NEZAPOSLENI, $docs);
        }

        $this->assertSame(
            [Application::DOCUMENT_POTVRDA_ZAVOD_NEZAPOSLENI],
            Application::getConditionallyRequiredDocumentTypes()
        );
    }

    public function test_email_attribute_labels_for_founder_director_and_doo_name(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/Models/Application.php');
        $this->assertStringContainsString("'founder_name' => 'Ime i prezime osnivačice/osnivačica'", $source);
        $this->assertStringContainsString("'director_name' => 'Ime i prezime izvršne direktorice'", $source);
        $this->assertStringContainsString("'doo_name' => 'Ime i prezime podnositeljke prijave'", $source);
        $this->assertStringNotContainsString("'founder_name' => 'Ime i prezime osnivača'", $source);
        $this->assertStringNotContainsString("'director_name' => 'Ime i prezime direktora'", $source);
    }

    public function test_active_zp_runtime_blades_have_no_nositeljka_biznisa(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = [
            $root.'/resources/views/applications/create.blade.php',
            $root.'/resources/views/applications/show.blade.php',
            $root.'/resources/views/business-plans/create.blade.php',
            $root.'/resources/views/evaluation/create.blade.php',
            $root.'/resources/views/evaluation/show.blade.php',
            $root.'/resources/views/admin/applications/show.blade.php',
            $root.'/resources/views/competitions/show.blade.php',
            $root.'/resources/views/competitions/partials/decision-document.blade.php',
            $root.'/app/Http/Controllers/CompetitionsController.php',
        ];

        foreach ($paths as $path) {
            $this->assertFileExists($path);
            $content = file_get_contents($path);
            $this->assertStringNotContainsString('nositeljke biznisa', $content, basename($path));
            $this->assertStringNotContainsString('nositeljka biznisa', $content, basename($path));
            $this->assertStringNotContainsString('nositeljkom biznisa', $content, basename($path));
            // Allow historical comment in Obrazac 2 JS that names the removed term.
            if (! str_ends_with($path, 'business-plans/create.blade.php')) {
                $this->assertStringNotContainsString('nosioca biznisa', $content, basename($path));
            }
        }
    }

    public function test_zp_start_create_and_show_use_canonical_feminine_applicant_labels(): void
    {
        $root = dirname(__DIR__, 2);
        $competitionShow = file_get_contents($root.'/resources/views/competitions/show.blade.php');
        $create = file_get_contents($root.'/resources/views/applications/create.blade.php');
        $show = file_get_contents($root.'/resources/views/applications/show.blade.php');
        $decision = file_get_contents($root.'/resources/views/competitions/partials/decision-document.blade.php');

        $this->assertStringContainsString('Planiram registraciju kao preduzetnica', $competitionShow);
        $this->assertStringContainsString(
            "(\$competition->type === 'omladinsko') ? 'Planiram registraciju kao preduzetnik' : 'Planiram registraciju kao preduzetnica'",
            $competitionShow
        );
        $this->assertStringNotContainsString('<span>Planiram registraciju kao preduzetnik</span>', $competitionShow);

        $this->assertStringContainsString('Planiram registraciju kao preduzetnica', $create);
        $this->assertStringContainsString(
            "(\$isOmladinsko ? 'Planiram registraciju kao preduzetnik' : 'Planiram registraciju kao preduzetnica')",
            $create
        );

        $this->assertStringContainsString('Tip podnositeljke prijave', $show);
        $this->assertStringContainsString(
            "(\$application->competition?->type === 'omladinsko') ? 'Tip podnosioca' : 'Tip podnositeljke prijave'",
            $show
        );
        $this->assertStringNotContainsString('<span class="info-label">Tip podnosioca</span>', $show);

        $this->assertStringContainsString(
            'sa preduzetnicom odnosno sa privrednim društvom u kojem je žena osnivačica ili jedna od osnivačica i izvršna direktorica',
            $decision
        );
        $this->assertStringNotContainsString('nositeljkom biznisa', $decision);
    }
}
