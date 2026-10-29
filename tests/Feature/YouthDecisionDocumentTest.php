<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use App\Services\Competitions\YouthDecisionDocxWriter;
use App\Services\Competitions\YouthDecisionProposal;
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
        $this->assertNull($identity->line($this->company('Firma Gama', 'Ana Anić', null)));

        $unregistered = new Application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'doo_name' => 'Milica Milić',
        ]);
        $user = new User(['name' => 'Profil Nalog']);
        $user->setRelation('identity', null);
        $unregistered->setRelation('user', $user);
        $this->assertNull($identity->line($unregistered));

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
        } finally {
            @unlink($path);
        }

        $this->assertStringContainsString('01-2140/1', $text);
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
}
