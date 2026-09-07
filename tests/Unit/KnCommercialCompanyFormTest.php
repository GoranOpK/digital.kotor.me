<?php

namespace Tests\Unit;

use App\Support\KnCommercialCompanyForm;
use App\Support\UserType;
use PHPUnit\Framework\TestCase;

class KnCommercialCompanyFormTest extends TestCase
{
    public function test_maps_canonical_legal_forms_to_codes_and_applicant_types(): void
    {
        $this->assertSame('doo', KnCommercialCompanyForm::fromUserType(UserType::LIMITED_LIABILITY_COMPANY));
        $this->assertSame('ad', KnCommercialCompanyForm::fromUserType(UserType::JOINT_STOCK_COMPANY));
        $this->assertSame('od', KnCommercialCompanyForm::fromUserType(UserType::GENERAL_PARTNERSHIP));
        $this->assertSame('kd', KnCommercialCompanyForm::fromUserType(UserType::LIMITED_PARTNERSHIP));
        $this->assertNull(KnCommercialCompanyForm::fromUserType(UserType::NGO_ASSOCIATION));
        $this->assertNull(KnCommercialCompanyForm::fromUserType(UserType::PHYSICAL_PERSON));

        $this->assertSame('doo', KnCommercialCompanyForm::applicantTypeFor('doo'));
        $this->assertSame('ostalo', KnCommercialCompanyForm::applicantTypeFor('ad'));
        $this->assertSame('ostalo', KnCommercialCompanyForm::applicantTypeFor('od'));
        $this->assertSame('ostalo', KnCommercialCompanyForm::applicantTypeFor('kd'));

        $this->assertSame(UserType::JOINT_STOCK_COMPANY, KnCommercialCompanyForm::registrationFormLabel('ad'));
        $this->assertSame('ad', KnCommercialCompanyForm::fromRegistrationForm(UserType::JOINT_STOCK_COMPANY));
        $this->assertSame('AD', KnCommercialCompanyForm::headingCode('ad'));
        $this->assertSame('(za oblik registracije AD)', KnCommercialCompanyForm::obrazacRegistracijaHeading('ad', 'ostalo'));
        $this->assertSame('(za oblik registracije OD)', KnCommercialCompanyForm::obrazacRegistracijaHeading('od', 'ostalo'));
        $this->assertSame('(za oblik registracije KD)', KnCommercialCompanyForm::obrazacRegistracijaHeading('kd', 'ostalo'));
        $this->assertSame('(za oblik registracije DOO)', KnCommercialCompanyForm::obrazacRegistracijaHeading('doo', 'doo'));
        $this->assertSame('(za oblik registracije AD)', KnCommercialCompanyForm::obrazacRegistracijaHeading(null, 'ostalo', UserType::JOINT_STOCK_COMPANY));
        $this->assertSame(
            '(za ostale pravne subjekte)',
            KnCommercialCompanyForm::obrazacRegistracijaHeading(null, 'ostalo', UserType::LEGACY_ASSOCIATION_BUNDLE)
        );
        $this->assertSame(
            '(za oblik registracije PREDUZETNIK)',
            KnCommercialCompanyForm::obrazacRegistracijaHeading(null, 'fizicko_lice')
        );
    }
}
