<?php

namespace Tests\Unit;

use App\Support\KnApplicationClassification;
use App\Support\UserType;
use PHPUnit\Framework\TestCase;

class KnApplicationClassificationTest extends TestCase
{
    public function test_unregistered_physical_person_is_not_registered_and_cannot_choose_razvoj(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::PHYSICAL_PERSON);

        $this->assertTrue($kn->isUnregisteredPhysicalPerson);
        $this->assertFalse($kn->isRegisteredBusiness);
        $this->assertTrue($kn->canChoosePlannedForm);
        $this->assertSame(['preduzetnica', 'doo'], $kn->allowedApplicantTypes);
        $this->assertFalse($kn->allowsStage('razvoj'));
        $this->assertSame('preduzetnica', $kn->resolveApplicantType('preduzetnica'));
        $this->assertSame('doo', $kn->resolveApplicantType('doo'));
        $this->assertSame('preduzetnica', $kn->resolveApplicantType('fizicko_lice'));
        $this->assertSame('započinjanje', $kn->resolveBusinessStage('razvoj'));
    }

    public function test_existing_entrepreneur_is_registered_preduzetnica_with_both_stages(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::ENTREPRENEUR);

        $this->assertTrue($kn->isRegisteredBusiness);
        $this->assertFalse($kn->canChoosePlannedForm);
        $this->assertSame('preduzetnica', $kn->resolveApplicantType('doo'));
        $this->assertTrue($kn->allowsStage('razvoj'));
    }

    public function test_existing_doo_is_registered_doo_with_both_stages(): void
    {
        $kn = KnApplicationClassification::fromUserType(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertTrue($kn->isRegisteredBusiness);
        $this->assertSame('doo', $kn->resolveApplicantType('preduzetnica'));
        $this->assertTrue($kn->allowsStage('razvoj'));
    }
}
