<?php

namespace Tests\Unit\Identity;

use App\Identity\IdentityCanonicalGraphFingerprint;
use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use Tests\TestCase;

class IdentityCanonicalGraphFingerprintTest extends TestCase
{
    public function test_snapshot_and_persisted_fingerprints_use_the_same_keys_and_step4_trim_rules(): void
    {
        $snapshot = new IdentitySnapshot(
            userId: 12,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: ' +38267000001 ',
            streetAndNumber: 'Njegoševa 12',
            city: 'Kotor',
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: 'Ana',
                lastName: 'Anić',
                residentialStatus: PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
                streetAndNumber: 'Njegoševa 12',
                city: 'Kotor',
                idDocumentType: PhysicalPersonIdentity::DOCUMENT_JMB,
                jmb: '0202990123456',
                passportNumber: null,
                residenceCountryCode: null,
                isEntrepreneur: false,
                entrepreneurBusinessName: null,
                pib: null,
                crpsNumber: null,
            ),
        );

        $platform = new PlatformIdentity([
            'user_id' => 12,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => ' +38267000001 ',
        ]);
        $fl = new PhysicalPersonIdentity([
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => '0202990123456',
            'passport_number' => '  ',
            'residence_country_code' => '',
            'is_entrepreneur' => false,
            'entrepreneur_business_name' => ' ',
            'pib' => '',
            'crps_number' => '   ',
            'street_and_number' => 'Njegoševa 12',
            'city' => 'Kotor',
        ]);

        $fromSnapshot = IdentityCanonicalGraphFingerprint::fromSnapshot($snapshot);
        $fromPersisted = IdentityCanonicalGraphFingerprint::fromPersistedPhysicalPersonGraph($platform, $fl);

        $this->assertSame([
            'user_id',
            'subject_type',
            'mobile_phone',
            'first_name',
            'last_name',
            'residential_status',
            'id_document_type',
            'jmb',
            'passport_number',
            'residence_country_code',
            'is_entrepreneur',
            'entrepreneur_business_name',
            'pib',
            'crps_number',
            'street_and_number',
            'city',
        ], array_keys($fromSnapshot));
        $this->assertSame(array_keys($fromSnapshot), array_keys($fromPersisted));
        $this->assertSame($fromSnapshot, $fromPersisted);
        $this->assertSame('+38267000001', $fromPersisted['mobile_phone']);
        $this->assertNull($fromPersisted['passport_number']);
        $this->assertNull($fromPersisted['pib']);
        $this->assertFalse($fromPersisted['is_entrepreneur']);
    }
}
