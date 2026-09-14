<?php

namespace Tests\Unit;

use App\Support\KnApplicationClassification;
use App\Support\UserType;
use PHPUnit\Framework\TestCase;

class KnApplicationClassificationTest extends TestCase
{
    public function test_unregistered_physical_person_defaults_to_fizicko_lice_and_cannot_choose_razvoj(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::PHYSICAL_PERSON);

        $this->assertTrue($kn->isUnregisteredPhysicalPerson);
        $this->assertFalse($kn->isRegisteredBusiness);
        $this->assertTrue($kn->canChoosePlannedForm);
        $this->assertSame(['fizicko_lice', 'doo'], $kn->allowedApplicantTypes);
        $this->assertSame('fizicko_lice', $kn->defaultFormApplicantType());
        $this->assertFalse($kn->allowsStage('razvoj'));
        $this->assertSame('fizicko_lice', $kn->resolveApplicantType('fizicko_lice'));
        $this->assertSame('doo', $kn->resolveApplicantType('doo'));
        $this->assertSame('fizicko_lice', $kn->resolveApplicantType('preduzetnica'));
        $this->assertSame('fizicko_lice', $kn->resolveApplicantType(null));
        $this->assertSame('započinjanje', $kn->resolveBusinessStage('razvoj'));
    }

    public function test_existing_entrepreneur_is_registered_preduzetnica_with_both_stages(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::ENTREPRENEUR);

        $this->assertTrue($kn->isRegisteredBusiness);
        $this->assertFalse($kn->canChoosePlannedForm);
        $this->assertSame(['preduzetnica'], $kn->allowedApplicantTypes);
        $this->assertSame('preduzetnica', $kn->resolveApplicantType('doo'));
        $this->assertSame('preduzetnica', $kn->resolveApplicantType('fizicko_lice'));
        $this->assertTrue($kn->allowsStage('razvoj'));
    }

    public function test_existing_doo_is_registered_doo_with_both_stages(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertTrue($kn->isRegisteredBusiness);
        $this->assertSame(['doo'], $kn->allowedApplicantTypes);
        $this->assertSame('doo', $kn->resolveApplicantType('preduzetnica'));
        $this->assertSame('doo', $kn->resolveApplicantType('fizicko_lice'));
        $this->assertTrue($kn->allowsStage('razvoj'));
    }

    public function test_omladinsko_unregistered_physical_person_allows_fizicko_lice_and_privredno_drustvo(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::PHYSICAL_PERSON, 'omladinsko');

        $this->assertSame(['fizicko_lice', 'privredno_drustvo'], $kn->allowedApplicantTypes);
        $this->assertTrue($kn->supportsOmladinskoDraft());
        $this->assertFalse($kn->allowsApplicantType('doo'));
        $this->assertFalse($kn->allowsApplicantType('ostalo'));
        $this->assertFalse($kn->allowsStage('razvoj'));
        $this->assertTrue(KnApplicationClassification::isM1a('fizicko_lice'));
        $this->assertTrue(KnApplicationClassification::isM1b('privredno_drustvo'));
    }

    public function test_omladinsko_entrepreneur_locks_preduzetnik(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::ENTREPRENEUR, 'omladinsko');

        $this->assertSame(['preduzetnik'], $kn->allowedApplicantTypes);
        $this->assertSame('preduzetnik', $kn->lockedApplicantType);
        $this->assertTrue($kn->allowsStage('razvoj'));
        $this->assertTrue(KnApplicationClassification::isM1a('preduzetnik'));
        $this->assertTrue(KnApplicationClassification::isRegisteredEntrepreneurType('preduzetnik'));
    }

    public function test_omladinsko_commercial_company_locks_privredno_drustvo(): void
    {
        foreach ([
            UserType::LIMITED_LIABILITY_COMPANY,
            UserType::JOINT_STOCK_COMPANY,
            UserType::GENERAL_PARTNERSHIP,
            UserType::LIMITED_PARTNERSHIP,
        ] as $userType) {
            $kn = KnApplicationClassification::fromUserType($userType, 'omladinsko');
            $this->assertSame(['privredno_drustvo'], $kn->allowedApplicantTypes);
            $this->assertTrue($kn->supportsOmladinskoDraft());
            $this->assertFalse($kn->allowsApplicantType('ostalo'));
        }
    }

    public function test_omladinsko_unsupported_identity_cannot_open_draft(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::NGO_ASSOCIATION, 'omladinsko');

        $this->assertTrue($kn->hasIdentity);
        $this->assertSame([], $kn->allowedApplicantTypes);
        $this->assertFalse($kn->supportsOmladinskoDraft());
        $this->assertFalse($kn->allowsApplicantType('ostalo'));
    }

    public function test_mysql_enum_values_keep_existing_and_add_omladinsko_types(): void
    {
        $this->assertSame(
            ['preduzetnica', 'doo', 'fizicko_lice', 'ostalo', 'preduzetnik', 'privredno_drustvo'],
            KnApplicationClassification::mysqlEnumValues()
        );
    }
}
