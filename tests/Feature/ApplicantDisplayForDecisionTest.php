<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Tests\TestCase;

class ApplicantDisplayForDecisionTest extends TestCase
{
    private function applicationWithUser(array $applicationAttrs, string $userName = 'Profil Korisnica'): Application
    {
        $user = new User(['name' => $userName]);
        $user->setRelation('identity', null);
        $application = new Application($applicationAttrs);
        $application->setRelation('user', $user);
        $application->setRelation('businessPlan', null);

        return $application;
    }

    private function withCanonicalLegalEntity(Application $application, string $legalName): Application
    {
        $legal = new LegalEntityIdentity([
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => $legalName,
        ]);
        $platform = new PlatformIdentity([
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
        ]);
        $platform->setRelation('legalEntity', $legal);

        $user = $application->user;
        $user->setRelation('identity', $platform);
        $application->setRelation('user', $user);

        return $application;
    }

    public function test_fizicko_lice_prefers_physical_person_name_over_user_name(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Snapshot Fizicko',
        ], 'Profil Korisnica');

        $this->assertSame('Snapshot Fizicko', $application->getApplicantDisplayForDecision());
    }

    public function test_fizicko_lice_falls_back_to_user_name_when_snapshot_missing(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => null,
        ], 'Profil Korisnica');

        $this->assertSame('Profil Korisnica', $application->getApplicantDisplayForDecision());
    }

    public function test_fizicko_lice_final_fallback_is_na(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => null,
        ], '');
        $application->setRelation('user', new User(['name' => null]));

        $this->assertSame('N/A', $application->getApplicantDisplayForDecision());
    }

    public function test_preduzetnica_prefers_preduzetnik_name_and_ignores_founder_director_and_bp_company(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => 'Snapshot Preduzetnica',
            'founder_name' => 'Osnivac Pogresan',
            'director_name' => 'Direktor Pogresan',
            'business_area' => 'turizam',
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Trgovacki Naziv',
        ]));

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            'Preduzetnica Snapshot Preduzetnica koja obavlja djelatnost u turizam.',
            $display
        );
        $this->assertStringNotContainsString('Osnivac Pogresan', $display);
        $this->assertStringNotContainsString('Direktor Pogresan', $display);
        $this->assertStringNotContainsString('BP Trgovacki Naziv', $display);
        $this->assertStringNotContainsString('Profil Korisnica', $display);
    }

    public function test_preduzetnica_falls_back_to_user_name_not_founder_or_director(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => null,
            'founder_name' => 'Osnivac Legacy',
            'director_name' => 'Direktor Legacy',
            'business_area' => null,
        ], 'Profil Preduzetnica');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            'Preduzetnica Profil Preduzetnica koja obavlja djelatnost.',
            $display
        );
        $this->assertStringNotContainsString('Osnivac Legacy', $display);
        $this->assertStringNotContainsString('Direktor Legacy', $display);
    }

    public function test_registered_doo_uses_canonical_legal_name_and_doo_name_ignoring_bp_company(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'doo',
            'doo_name' => 'Nositeljka Snapshot',
            'founder_name' => 'Osnivac Fallback',
            'director_name' => 'Direktor Fallback',
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Kombinovani Tekst',
        ]));
        $this->withCanonicalLegalEntity($application, 'Firma Alfa');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            '"Firma Alfa" DOO, koga zastupa osnivačica i izvršna direktorica Nositeljka Snapshot.',
            $display
        );
        $this->assertStringNotContainsString('BP Kombinovani Tekst', $display);
        $this->assertStringNotContainsString('"Profil Korisnica"', $display);
    }

    public function test_doo_nositeljka_fallback_order_with_canonical_legal_name(): void
    {
        $withoutDooName = $this->applicationWithUser([
            'applicant_type' => 'doo',
            'doo_name' => null,
            'founder_name' => 'Osnivac Fallback',
            'director_name' => 'Direktor Fallback',
        ], 'Profil Korisnica');
        $withoutDooName->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Ignorisati',
        ]));
        $this->withCanonicalLegalEntity($withoutDooName, 'Firma Beta');

        $this->assertSame(
            '"Firma Beta" DOO, koga zastupa osnivačica i izvršna direktorica Osnivac Fallback.',
            $withoutDooName->getApplicantDisplayForDecision()
        );

        $founderMissing = $this->applicationWithUser([
            'applicant_type' => 'doo',
            'doo_name' => null,
            'founder_name' => null,
            'director_name' => 'Direktor Fallback',
        ], 'Profil Korisnica');
        $this->withCanonicalLegalEntity($founderMissing, 'Firma Gama');

        $this->assertSame(
            '"Firma Gama" DOO, koga zastupa osnivačica i izvršna direktorica Direktor Fallback.',
            $founderMissing->getApplicantDisplayForDecision()
        );

        $onlyUser = $this->applicationWithUser([
            'applicant_type' => 'doo',
            'doo_name' => null,
            'founder_name' => null,
            'director_name' => null,
        ], 'Profil Korisnica');
        $this->withCanonicalLegalEntity($onlyUser, 'Firma Delta');

        $this->assertSame(
            '"Firma Delta" DOO, koga zastupa osnivačica i izvršna direktorica Profil Korisnica.',
            $onlyUser->getApplicantDisplayForDecision()
        );
    }

    public function test_planned_doo_without_canonical_legal_entity_does_not_invent_company_name(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'doo',
            'doo_name' => 'Nositeljka Bez Firme',
            'founder_name' => null,
            'director_name' => null,
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Ne Koristiti Kao Naziv',
        ]));

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame('Nositeljka Bez Firme', $display);
        $this->assertStringNotContainsString('BP Ne Koristiti Kao Naziv', $display);
        $this->assertStringNotContainsString('"Profil Korisnica"', $display);
        $this->assertStringNotContainsString('Profil Korisnica DOO', $display);
        $this->assertStringNotContainsString(' DOO,', $display);
    }

    public function test_ostalo_uses_canonical_legal_name_without_doo_suffix(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'ostalo',
            'doo_name' => 'Nositeljka Ostalo',
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Ignorisati',
        ]));
        $this->withCanonicalLegalEntity($application, 'Udruzenje Epsilon');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            '"Udruzenje Epsilon", koga zastupa osnivačica i izvršna direktorica Nositeljka Ostalo.',
            $display
        );
        $this->assertStringNotContainsString('BP Ignorisati', $display);
    }
}
