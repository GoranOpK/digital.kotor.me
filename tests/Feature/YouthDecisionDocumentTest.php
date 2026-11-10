<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use App\Services\Competitions\YouthDecisionDocxWriter;
use App\Services\Competitions\YouthDecisionProposal;
use App\Services\Competitions\YouthDecisionProposalBuilder;
use PhpZip\ZipFile;
use Tests\TestCase;

class YouthDecisionDocumentTest extends TestCase
{
    public function test_company_identity_names_one_person_once_and_does_not_invent_missing_data(): void
    {
        $identity = app(YouthDecisionApplicantIdentity::class);

        $this->assertSame(
            'Firma Alfa, koga zastupa osnivač/ica i izvršni direktor/ica Ana Anić',
            $identity->line($this->company('Firma Alfa', 'Ana Anić', 'ana anić'))
        );
        $this->assertSame(
            'Firma Beta, koga zastupa osnivač/ica Ana Anić i izvršni direktor/ica Marko Marković',
            $identity->line($this->company('Firma Beta', 'Ana Anić', 'Marko Marković'))
        );
        $this->assertSame('Ana Anić', $identity->line($this->company('Firma Gama', 'Ana Anić', null)));

        $unregistered = new Application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'business_plan_name' => 'Bidon',
            'doo_name' => 'Milica Milić',
        ]);
        $user = new User(['name' => 'Profil Nalog']);
        $user->setRelation('identity', null);
        $unregistered->setRelation('user', $user);
        $this->assertSame('Milica Milić', $identity->line($unregistered));
        $this->assertStringNotContainsString('Bidon', (string) $identity->line($unregistered));
        $this->assertStringNotContainsString('Profil Nalog', (string) $identity->line($unregistered));

        $person = new Application([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Petar Petrović',
        ]);
        $person->setRelation('user', new User(['name' => 'Drugi Nalog']));
        $this->assertSame('Petar Petrović', $identity->line($person));

        $entrepreneur = new Application([
            'applicant_type' => 'preduzetnik',
            'preduzetnik_name' => 'Jovo Jović',
        ]);
        $entrepreneur->setRelation('user', new User(['name' => 'Nalog Preduzetnika']));
        $this->assertSame('Jovo Jović', $identity->line($entrepreneur));

        $this->assertSame('Ana Anić', $identity->line($this->company('Stefan doo', '  stefan   DOO ', 'Ana Anić')));
        $this->assertSame('Ana Anić', $identity->line($this->company('Stefan doo', 'Ana Anić', 'STEFAN DOO')));
        $this->assertSame(
            'Stefan doo, koga zastupa osnivač/ica i izvršni direktor/ica Ana Anić',
            $identity->line($this->company('Stefan doo', 'Ana Anić', 'ana anić'))
        );
        $this->assertSame(
            'Stefan doo, koga zastupa osnivač/ica i izvršni direktor/ica Stefan Stefanović',
            $identity->line($this->company('Stefan doo', 'Stefan Stefanović', 'Stefan Stefanović'))
        );
        $this->assertSame(
            'Stefan doo, koga zastupa osnivač/ica Ana Anić i izvršni direktor/ica Marko Marković',
            $identity->line($this->company('Stefan doo', 'Ana Anić', 'Marko Marković'))
        );

        $rejected = $this->company('Stefan doo', 'Stefan doo', 'STEFAN DOO');
        $rejected->id = 38;
        $rejected->business_plan_name = 'Plan Stefan';
        $rejected->final_score = '40.00';
        $rejected->approved_amount = '15000.00';
        $rejected->setRelation('businessPlan', new \App\Models\BusinessPlan([
            'required_amount' => '20000.00',
        ]));
        $this->assertNull($identity->line($rejected));
        $method = new \ReflectionMethod(YouthDecisionProposalBuilder::class, 'winnerRow');
        $row = $method->invoke(app(YouthDecisionProposalBuilder::class), $rejected);
        $this->assertSame('application', $row['identity_source']);
        $this->assertSame('Oznaka prijave: prijava 38, naziv projekta „Plan Stefan“', $row['identity']);
        $this->assertSame('Plan Stefan', $row['plan']);
        $this->assertSame('15.000,00', $row['approved']);
        $this->assertStringNotContainsString('Stefan doo', $row['identity']);
        $this->assertStringNotContainsString('Ana Anić', $row['identity']);
    }

    public function test_planned_company_uses_the_saved_natural_person_name_in_the_word_decision(): void
    {
        $identity = app(YouthDecisionApplicantIdentity::class);
        $application = new Application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'business_plan_name' => 'Bidon',
            'doo_name' => 'Milica Milić',
            'final_score' => '48.50',
            'approved_amount' => '15000.00',
        ]);
        $application->id = 38;
        $user = new User(['name' => 'Nalog Koji Se Ne Koristi']);
        $user->setRelation('identity', null);
        $application->setRelation('user', $user);
        $application->setRelation('businessPlan', new \App\Models\BusinessPlan([
            'required_amount' => '20000.00',
        ]));

        $this->assertSame('Milica Milić', $identity->line($application));

        $method = new \ReflectionMethod(YouthDecisionProposalBuilder::class, 'winnerRow');
        $row = $method->invoke(app(YouthDecisionProposalBuilder::class), $application);
        $this->assertSame('applicant', $row['identity_source']);
        $this->assertSame('Milica Milić', $row['identity']);
        $this->assertSame('Bidon', $row['plan']);

        $proposal = new YouthDecisionProposal(
            competitionId: 38,
            decisionNumber: '',
            decisionDate: '10.11.2026.',
            competitionYear: '2140',
            publishedOn: '01.09.2026.',
            endedOn: '21.09.2026.',
            firstSessionDate: '02.02.2026',
            secondSessionDate: '03.03.2026',
            thirdSessionDate: '04.04.2026',
            oralDate: '05.05.2026',
            winners: [$row],
            receivedCount: 1,
            incompleteCount: 0,
            appealsAccepted: 0,
            appealsRejected: 0,
            proceedCount: 1,
            awardedTotal: '15.000,00',
            commissionMemberCount: 3,
        );
        $path = app(YouthDecisionDocxWriter::class)->write($proposal);
        try {
            $text = $this->docxText($path);
        } finally {
            @unlink($path);
        }

        $this->assertStringContainsString('Podnosilac/podnositeljka prijave: Milica Milić', $text);
        $this->assertStringContainsString('Bidon', $text);
        $this->assertStringNotContainsString('Oznaka prijave:', $text);
        $this->assertStringNotContainsString('Podnosilac/podnositeljka prijave: Bidon', $text);
        $this->assertStringNotContainsString('Nalog Koji Se Ne Koristi', $text);

        $sameAsPlan = new Application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'business_plan_name' => 'Bidon',
            'doo_name' => 'Bidon',
        ]);
        $sameAsPlan->setRelation('user', $user);
        $this->assertNull($identity->line($sameAsPlan));

        $canonicalUser = new User(['name' => 'Nalog Koji Se Ne Koristi']);
        $person = new PhysicalPersonIdentity([
            'first_name' => 'Petar',
            'last_name' => 'Petrović',
        ]);
        $platform = new PlatformIdentity(['subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON]);
        $platform->setRelation('physicalPerson', $person);
        $platform->setRelation('legalEntity', null);
        $canonicalUser->setRelation('identity', $platform);
        $fromCanonical = new Application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'business_plan_name' => 'Bidon',
            'doo_name' => null,
        ]);
        $fromCanonical->setRelation('user', $canonicalUser);
        $this->assertSame('Petar Petrović', $identity->line($fromCanonical));

        $naturalPerson = new Application([
            'applicant_type' => 'fizicko_lice',
            'business_plan_name' => 'Plan lica',
            'physical_person_name' => null,
        ]);
        $naturalPerson->setRelation('user', $canonicalUser);
        $this->assertSame('Petar Petrović', $identity->line($naturalPerson));

        $entrepreneur = new Application([
            'applicant_type' => 'preduzetnik',
            'business_plan_name' => 'Plan radnje',
            'preduzetnik_name' => null,
        ]);
        $entrepreneur->setRelation('user', $canonicalUser);
        $this->assertSame('Petar Petrović', $identity->line($entrepreneur));
    }

    public function test_word_document_keeps_the_template_and_manual_dates(): void
    {
        $proposal = new YouthDecisionProposal(
            competitionId: 77,
            decisionNumber: '01-2140/1',
            decisionDate: '29.10.2026.',
            competitionYear: '2140',
            publishedOn: '01.09.2026.',
            endedOn: '21.09.2026.',
            firstSessionDate: '02.02.2026',
            secondSessionDate: '03.03.2026',
            thirdSessionDate: '04.04.2026',
            oralDate: '05.05.2026',
            winners: [
                [
                    'identity' => 'Ana Anić',
                    'plan' => 'Plan Alfa',
                    'score' => '48,50',
                    'approved' => '1.000,00',
                    'total_value' => '2.500,50',
                ],
                [
                    'identity' => 'Firma Alfa, koga zastupa osnivač/ica i izvršni direktor/ica Lana Lanić',
                    'plan' => 'Plan Beta',
                    'score' => '51,00',
                    'approved' => '500,00',
                    'total_value' => '9.000,00',
                ],
            ],
            receivedCount: 4,
            incompleteCount: 1,
            appealsAccepted: 1,
            appealsRejected: 0,
            proceedCount: 3,
            awardedTotal: '1.500,00',
            commissionMemberCount: 3,
        );

        $path = app(YouthDecisionDocxWriter::class)->write($proposal);
        try {
            $text = $this->docxText($path);
            $this->assertEmptyRegistrySlot($path, '29.10.2026.');
            $generated = new ZipFile();
            $generated->openFile($path);
            $documentXml = $generated->getEntryContents('word/document.xml');
            $generated->close();
            $this->assertStringNotContainsString('<w:highlight', $documentXml);
            $this->assertStringContainsString('<w:rPr', $documentXml);
        } finally {
            @unlink($path);
        }

        $template = new ZipFile();
        $template->openFile(app(YouthDecisionDocxWriter::class)->templatePath());
        $templateXml = $template->getEntryContents('word/document.xml');
        $template->close();
        $this->assertStringContainsString('w:highlight w:val="yellow"', $templateXml);

        $this->assertStringContainsString('29.10.2026.', $text);
        $this->assertStringContainsString('Plan Alfa', $text);
        $this->assertStringContainsString('Plan Beta', $text);
        $this->assertSame(1, substr_count($text, 'Lana Lanić'));
        $this->assertStringContainsString('02.02.2026', $text);
        $this->assertStringContainsString('na drugoj sjednici Komisije održanoj dana 03.03.2026. godine', $text);
        $this->assertStringContainsString('04.04.2026', $text);
        $this->assertStringContainsString('05.05.2026', $text);
        $this->assertStringContainsString('Članovima Komisije (x3)', $text);
        $this->assertStringContainsString('Podnosiocima prijave (x2)', $text);
        $this->assertStringContainsString('Sekretarijatu (x2)', $text);
        $this->assertStringContainsString('Vladan Barović', $text);
        $this->assertStringContainsString('sljedećih', $text);
        $this->assertStringContainsString('odluku o prihvatanju (1) ili odbijanju (0)', $text);
        $this->assertStringContainsString('Komisija je na trećoj sjednici', $text);
        $this->assertStringContainsString('Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj', $text);
        $this->assertStringContainsString('preduzetništvu mladih donosi', $text);
        $this->assertStringNotContainsString('ovdje ide', $text);
        $this->assertStringNotContainsString('ženskom preduzetništvu', $text);
        $this->assertStringNotContainsString('u roku od u roku od', $text);
        $this->assertStringNotContainsString('dredstava', $text);
        $this->assertStringNotContainsString('sledećih', $text);
        $this->assertStringNotContainsString('podnosiilaca', $text);
        $this->assertStringContainsString('podnosilaca', $text);
        $this->assertStringNotContainsString('Sekretarijatu 16', $text);
        $this->assertStringNotContainsString('Komisija je dana na trećoj', $text);
        $this->assertStringNotContainsString('22.06. 2026', $text);
    }

    private function company(string $legalName, ?string $founder, ?string $director): Application
    {
        $application = new Application([
            'applicant_type' => 'doo',
            'is_registered' => true,
            'founder_name' => $founder,
            'director_name' => $director,
        ]);
        $legal = new LegalEntityIdentity([
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => $legalName,
        ]);
        $platform = new PlatformIdentity(['subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY]);
        $platform->setRelation('legalEntity', $legal);
        $user = new User(['name' => 'Profil Nalog']);
        $user->setRelation('identity', $platform);
        $application->setRelation('user', $user);

        return $application;
    }

    private function docxText(string $path): string
    {
        $zip = new ZipFile();
        $zip->openFile($path);
        $text = strip_tags($zip->getEntryContents('word/document.xml').$zip->getEntryContents('word/header1.xml'));
        $image = $zip->getEntryContents('word/media/image1.jpeg');
        $zip->close();
        $this->assertNotSame('', $image);

        return $text;
    }

    private function assertEmptyRegistrySlot(string $path, string $headerDate): void
    {
        $zip = new ZipFile();
        $zip->openFile($path);
        $document = $zip->getEntryContents('word/document.xml');
        $zip->close();

        $nodes = $this->brojTextNodes($document);
        $this->assertCount(9, $nodes);
        $this->assertSame('Broj', $nodes[0]);
        $this->assertSame(':  ', $nodes[1]);
        $this->assertSame('', $nodes[2]);
        $this->assertMatchesRegularExpression('/^\s+$/', $nodes[3]);
        $this->assertDoesNotMatchRegularExpression('/XX/', $nodes[1].$nodes[2].$nodes[3]);
        $this->assertDoesNotMatchRegularExpression('/\d/', $nodes[1].$nodes[2].$nodes[3]);
        $this->assertSame('Kotor', $nodes[4]);
        $this->assertSame(', ', $nodes[5]);
        $this->assertSame($headerDate, $nodes[6]);
        $this->assertSame(' ', $nodes[7]);
        $this->assertSame('godine', $nodes[8]);
    }

    /**
     * @return list<string>
     */
    private function brojTextNodes(string $documentXml): array
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($documentXml));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            if (! $paragraph instanceof \DOMElement) {
                continue;
            }
            $nodes = [];
            foreach ($xpath->query('.//w:t', $paragraph) ?: [] as $node) {
                $nodes[] = $node->textContent;
            }
            if (($nodes[0] ?? '') === 'Broj') {
                return $nodes;
            }
        }

        $this->fail('Dokument nema pasus Broj:.');
    }
}
