<?php

namespace Tests\Unit\Identity;

use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use Tests\TestCase;

class IdentitySnapshotTest extends TestCase
{
    public function test_snapshot_keeps_code_and_label_concerns_separate_and_has_no_nationality(): void
    {
        $person = new PhysicalPersonSnapshot(
            firstName: 'Ana',
            lastName: 'Anić',
            residentialStatus: PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT,
            streetAndNumber: 'Via Roma 1',
            city: 'Rome',
            residenceCountryCode: 'IT',
        );

        $snapshot = new IdentitySnapshot(
            userId: 1,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: '+38267000001',
            streetAndNumber: 'Via Roma 1',
            city: 'Rome',
            physicalPerson: $person,
        );

        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $snapshot->subjectType);
        $this->assertSame('IT', $snapshot->physicalPerson?->residenceCountryCode);
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT, $snapshot->physicalPerson?->residentialStatus);
        $this->assertNull($snapshot->legacyFacts);
        $this->assertObjectNotHasProperty('nationality', $snapshot);
        $this->assertObjectNotHasProperty('citizenship', $snapshot);
        $this->assertObjectNotHasProperty('nationality', $person);
    }
}
