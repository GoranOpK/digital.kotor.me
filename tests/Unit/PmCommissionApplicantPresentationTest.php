<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\PmCommissionApplicantPresentation;
use App\Support\UserType;
use Tests\TestCase;

class PmCommissionApplicantPresentationTest extends TestCase
{
    public function test_physical_person_uses_the_application_name(): void
    {
        $application = new Application([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Ana Anić',
        ]);
        $application->setRelation('user', new User(['name' => 'Nalog Korisnika']));

        $this->assertSame('Ana Anić', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('Fizičko lice', PmCommissionApplicantPresentation::line($application));
    }

    public function test_entrepreneur_uses_the_application_name(): void
    {
        $application = new Application([
            'applicant_type' => 'preduzetnik',
            'preduzetnik_name' => 'Marko Marković',
        ]);
        $application->setRelation('user', new User(['name' => 'Nalog Korisnika']));

        $this->assertSame('Marko Marković', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('Preduzetnik', PmCommissionApplicantPresentation::line($application));
    }

    public function test_registered_company_uses_the_legal_name_and_registration_form(): void
    {
        $application = $this->company(
            applicantType: 'privredno_drustvo',
            registrationForm: UserType::JOINT_STOCK_COMPANY,
            legalName: 'Jadran AD',
            accountName: 'Nalog Društva',
            registered: true,
        );

        $this->assertSame('Jadran AD', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('AD -', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('Nalog Društva', PmCommissionApplicantPresentation::line($application));
    }

    public function test_historical_ostalo_uses_registration_form_when_it_is_reliable(): void
    {
        $application = $this->company(
            applicantType: 'ostalo',
            registrationForm: UserType::LIMITED_PARTNERSHIP,
            legalName: 'Boka KD',
            accountName: 'Nalog Ostalo',
            registered: true,
        );

        $this->assertSame('Boka KD', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('KD -', PmCommissionApplicantPresentation::line($application));
    }

    public function test_unresolved_historical_ostalo_is_not_labeled_ostalo(): void
    {
        $application = new Application([
            'applicant_type' => 'ostalo',
            'is_registered' => true,
            'registration_form' => 'nepoznat oblik',
        ]);
        $application->setRelation('user', new User(['name' => 'Nalog Ostalo']));

        $this->assertSame('—', PmCommissionApplicantPresentation::line($application));
    }

    public function test_unregistered_company_shows_the_applicant_from_the_application(): void
    {
        $application = new Application([
            'applicant_type' => 'privredno_drustvo',
            'company_legal_form' => 'doo',
            'is_registered' => false,
            'doo_name' => 'Petar Petrović',
        ]);
        $user = new User(['name' => 'Nalog Društva']);
        $legal = new LegalEntityIdentity([
            'legal_form' => 'doo',
            'legal_name' => 'Buduće društvo DOO',
        ]);
        $platform = new PlatformIdentity(['subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY]);
        $platform->setRelation('legalEntity', $legal);
        $user->setRelation('identity', $platform);
        $application->setRelation('user', $user);

        $line = PmCommissionApplicantPresentation::line($application);

        $this->assertSame('Petar Petrović', $line);
        $this->assertStringNotContainsString('DOO', $line);
        $this->assertStringNotContainsString('Nalog Društva', $line);
        $this->assertStringNotContainsString('Buduće društvo DOO', $line);
    }

    public function test_registered_company_without_a_legal_name_does_not_use_the_account_name(): void
    {
        $application = $this->company(
            applicantType: 'privredno_drustvo',
            registrationForm: UserType::LIMITED_LIABILITY_COMPANY,
            legalName: '   ',
            accountName: 'Nalog Društva',
            registered: true,
        );

        $this->assertSame('—', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('DOO', PmCommissionApplicantPresentation::line($application));
    }

    private function company(
        string $applicantType,
        string $registrationForm,
        string $legalName,
        string $accountName,
        bool $registered,
    ): Application {
        $legal = new LegalEntityIdentity([
            'legal_form' => 'doo',
            'legal_name' => $legalName,
        ]);
        $platform = new PlatformIdentity(['subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY]);
        $platform->setRelation('legalEntity', $legal);
        $user = new User(['name' => $accountName]);
        $user->setRelation('identity', $platform);

        $application = new Application([
            'applicant_type' => $applicantType,
            'registration_form' => $registrationForm,
            'is_registered' => $registered,
        ]);
        $application->setRelation('user', $user);

        return $application;
    }

    public function test_company_name_keeps_doo_when_it_is_part_of_the_legal_name(): void
    {
        $application = $this->company(
            applicantType: 'privredno_drustvo',
            registrationForm: UserType::LIMITED_LIABILITY_COMPANY,
            legalName: 'Primjer d.o.o.',
            accountName: 'Nalog Društva',
            registered: true,
        );

        $this->assertSame('Primjer d.o.o.', PmCommissionApplicantPresentation::line($application));
        $this->assertStringNotContainsString('DOO -', PmCommissionApplicantPresentation::line($application));
    }
}
