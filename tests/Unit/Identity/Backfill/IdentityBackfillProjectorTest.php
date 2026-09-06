<?php

namespace Tests\Unit\Identity\Backfill;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\Census\IdentityCensusService;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Support\UserType;
use Tests\TestCase;

class IdentityBackfillProjectorTest extends TestCase
{
    public function test_backfillable_resident_fl_projects_canonical_fields_without_legacy_facts(): void
    {
        $user = $this->subject();
        $snapshot = $this->project($user);

        $this->assertNotNull($snapshot);
        $this->assertSame($user->id, $snapshot->userId);
        $this->assertTrue($snapshot->isRegisteredSubject);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $snapshot->subjectType);
        $this->assertSame('+38267000001', $snapshot->mobilePhone);
        $this->assertNull($snapshot->legacyFacts);
        $this->assertNull($snapshot->legalEntity);
        $this->assertNull($snapshot->foreignBranch);

        $fl = $snapshot->physicalPerson;
        $this->assertNotNull($fl);
        $this->assertSame('Ana', $fl->firstName);
        $this->assertSame('Anić', $fl->lastName);
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_RESIDENT, $fl->residentialStatus);
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $fl->idDocumentType);
        $this->assertSame('0000000000000', $fl->jmb);
        $this->assertNull($fl->passportNumber);
        $this->assertNull($fl->residenceCountryCode);
        $this->assertFalse($fl->isEntrepreneur);
        $this->assertNull($fl->entrepreneurBusinessName);
        $this->assertNull($fl->pib);
        $this->assertNull($fl->crpsNumber);
        $this->assertSame('Njegoševa 12', $fl->streetAndNumber);
        $this->assertSame('Kotor', $fl->city);
    }

    public function test_missing_phone_projects_null_mobile_phone(): void
    {
        $snapshot = $this->project($this->subject(['phone' => null]));

        $this->assertNotNull($snapshot);
        $this->assertNull($snapshot->mobilePhone);
    }

    public function test_outer_trim_is_applied_to_projected_values(): void
    {
        $snapshot = $this->project($this->subject([
            'first_name' => ' Ana ',
            'jmb' => ' 0000000000000 ',
            'phone' => ' +38267000001 ',
        ]));

        $this->assertNotNull($snapshot);
        $this->assertSame('Ana', $snapshot->physicalPerson?->firstName);
        $this->assertSame('0000000000000', $snapshot->physicalPerson?->jmb);
        $this->assertSame('+38267000001', $snapshot->mobilePhone);
    }

    public function test_leftover_pib_and_passport_are_not_projected(): void
    {
        $snapshot = $this->project($this->subject([
            'pib' => '12345672',
            'passport_number' => 'AB123456',
        ]));

        $this->assertNotNull($snapshot);
        $this->assertNull($snapshot->physicalPerson?->pib);
        $this->assertNull($snapshot->physicalPerson?->passportNumber);
        $this->assertFalse($snapshot->physicalPerson?->isEntrepreneur);
    }

    public function test_staff_is_not_projected(): void
    {
        $this->assertNull($this->project($this->subject([
            'role_name' => 'admin',
            'user_type' => UserType::PHYSICAL_PERSON,
        ])));
    }

    public function test_city_missing_is_not_projected(): void
    {
        $this->assertNull($this->project($this->subject(['city' => null])));
    }

    public function test_invalid_jmb_is_not_projected(): void
    {
        $this->assertNull($this->project($this->subject(['jmb' => '0000000000001'])));
    }

    public function test_nonresident_is_not_projected(): void
    {
        $this->assertNull($this->project($this->subject([
            'residential_status' => 'non-resident',
            'passport_number' => 'AB123456',
            'jmb' => null,
        ])));
    }

    public function test_legal_entity_is_not_projected(): void
    {
        $this->assertNull($this->project($this->subject([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
        ])));
    }

    public function test_entrepreneur_is_not_projected(): void
    {
        $this->assertNull($this->project($this->subject([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => '12345672',
        ])));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function subject(array $overrides = []): User
    {
        $roleName = $overrides['role_name'] ?? 'korisnik';
        unset($overrides['role_name']);
        $id = $overrides['id'] ?? 1;
        unset($overrides['id']);

        $user = new User(array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
            'jmb' => '0000000000000',
            'phone' => '+38267000001',
        ], $overrides));
        $user->id = $id;

        $role = new Role;
        $role->name = $roleName;
        $user->setRelation('role', $role);

        return $user;
    }

    private function project(User $user): ?\App\Identity\IdentitySnapshot
    {
        $row = (new IdentityCensusService)->classify($user);

        return (new IdentityBackfillProjector)->project($user, $row);
    }
}
