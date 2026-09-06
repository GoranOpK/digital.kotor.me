<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class CanonicalIdentityWriterTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private CanonicalIdentityWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->writer = new CanonicalIdentityWriter;
    }

    public function test_creates_physical_person_graph_without_legacy_writes(): void
    {
        $user = $this->makeKorisnik();
        $before = $this->legacyIdentityPayload($user);

        $this->writer->createForUser($user, $this->flSnapshot($user, [
            'person' => ['residenceCountryCode' => null],
        ]));

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
        $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, ForeignBranchRepresentative::query()->count());

        $platform = PlatformIdentity::query()->where('user_id', $user->id)->first();
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $platform->subject_type);
        $this->assertSame('+38267000001', $platform->mobile_phone);

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertSame($platform->id, $fl->platform_identity_id);
        $this->assertSame('Ana', $fl->first_name);
        $this->assertSame('Anić', $fl->last_name);
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_RESIDENT, $fl->residential_status);
        $this->assertSame('0000000000000', $fl->jmb);
        $this->assertSame('Njegoševa 12', $fl->street_and_number);
        $this->assertSame('Podgorica', $fl->city);
        $this->assertNull($fl->residence_country_code);
        $this->assertFalse($fl->is_entrepreneur);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('physical_person_identities', 'passport_issuing_country_code'));

        $this->assertSame($before, $this->legacyIdentityPayload($user->fresh()));
    }

    public function test_creates_entrepreneur_as_physical_person_context(): void
    {
        $user = $this->makeKorisnik(['user_type' => UserType::ENTREPRENEUR]);

        $this->writer->createForUser($user, $this->entrepreneurSnapshot($user));

        $platform = PlatformIdentity::query()->where('user_id', $user->id)->first();
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $platform->subject_type);
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertTrue($fl->is_entrepreneur);
        $this->assertSame('Radnja Ana', $fl->entrepreneur_business_name);
        $this->assertSame('12345672', $fl->pib);
        $this->assertSame('10000001', $fl->crps_number);
        $this->assertSame(UserType::ENTREPRENEUR, $user->fresh()->user_type);
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
    }

    public function test_creates_nonresident_physical_person_with_residence_country(): void
    {
        $user = $this->makeKorisnik(['residential_status' => 'non-resident']);

        $this->writer->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'residentialStatus' => PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT,
                'idDocumentType' => PhysicalPersonIdentity::DOCUMENT_PASSPORT,
                'jmb' => null,
                'passportNumber' => 'AB123456',
                'residenceCountryCode' => 'IT',
            ],
        ]));

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT, $fl->residential_status);
        $this->assertSame('IT', $fl->residence_country_code);
        $this->assertSame('AB123456', $fl->passport_number);
        $this->assertSame('non-resident', $user->fresh()->residential_status);
    }

    public function test_creates_legal_entity_with_exactly_one_authorized_person(): void
    {
        $user = $this->makeKorisnik(['user_type' => UserType::LIMITED_LIABILITY_COMPANY, 'jmb' => null, 'pib' => '11111111']);
        $before = $this->legacyIdentityPayload($user);

        $this->writer->createForUser($user, $this->plSnapshot($user));

        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(1, LegalEntityIdentity::query()->count());
        $this->assertSame(1, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());

        $pl = LegalEntityIdentity::query()->first();
        $this->assertSame(LegalEntityIdentity::FORM_DOO, $pl->legal_form);
        $this->assertSame('Primjer DOO', $pl->legal_name);
        $this->assertSame('12345672', $pl->pib);
        $this->assertSame('50000001', $pl->crps_number);
        $this->assertSame('Slobode 1', $pl->street_and_number);
        $this->assertSame('Podgorica', $pl->city);
        $this->assertSame('+38267000002', PlatformIdentity::query()->first()->mobile_phone);

        $person = LegalEntityAuthorizedPerson::query()->first();
        $this->assertSame($pl->id, $person->legal_entity_identity_id);
        $this->assertSame('Marko', $person->first_name);
        $this->assertSame('0000000000000', $person->jmb);
        $this->assertSame($before, $this->legacyIdentityPayload($user->fresh()));
        $this->assertSame(UserType::LIMITED_LIABILITY_COMPANY, $user->fresh()->user_type);
    }

    public function test_creates_foreign_branch_with_exactly_one_representative(): void
    {
        $user = $this->makeKorisnik();
        $before = $this->legacyIdentityPayload($user);

        $this->writer->createForUser($user, $this->dspdSnapshot($user));

        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(1, ForeignBranchIdentity::query()->count());
        $this->assertSame(1, ForeignBranchRepresentative::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());

        $branch = ForeignBranchIdentity::query()->first();
        $this->assertSame('Foreign Co', $branch->foreign_company_name);
        $this->assertSame('Ogranak CG', $branch->branch_name_in_montenegro);
        $this->assertSame('00000007', $branch->pib);
        $this->assertSame('60000001', $branch->crps_number);
        $this->assertSame('Bulevar 8', $branch->street_and_number);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('foreign_branch_identities', 'legal_form'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('foreign_branch_identities', 'residential_status'));

        $rep = ForeignBranchRepresentative::query()->first();
        $this->assertSame('Jelena', $rep->first_name);
        $this->assertSame('IT', $rep->passport_issuing_country_code);
        $this->assertSame($before, $this->legacyIdentityPayload($user->fresh()));
    }

    public function test_second_create_for_same_user_fails_and_does_not_add_a_branch(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->flSnapshot($user));

        $this->expectException(CanonicalIdentityWriteException::class);
        try {
            $this->writer->createForUser($user, $this->plSnapshot($user));
        } finally {
            $this->assertSame(1, PlatformIdentity::query()->count());
            $this->assertSame(1, PhysicalPersonIdentity::query()->count());
            $this->assertSame(0, LegalEntityIdentity::query()->count());
        }
    }

    public function test_mixed_branch_snapshot_fails_with_zero_canonical_rows(): void
    {
        $user = $this->makeKorisnik();
        $fl = $this->flSnapshot($user);
        $pl = $this->plSnapshot($user);
        $mixed = new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $fl->mobilePhone,
            streetAndNumber: $fl->streetAndNumber,
            city: $fl->city,
            physicalPerson: $fl->physicalPerson,
            legalEntity: $pl->legalEntity,
        );

        try {
            $this->writer->createForUser($user, $mixed);
            $this->fail('Mixed branch snapshot must be rejected.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_legal_entity_without_authorized_person_fails_with_zero_rows(): void
    {
        $user = $this->makeKorisnik();

        try {
            $this->writer->createForUser($user, $this->plSnapshotWithoutAuthorizedPerson($user));
            $this->fail('PL without authorized person must be rejected.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_foreign_branch_without_representative_fails_with_zero_rows(): void
    {
        $user = $this->makeKorisnik();

        try {
            $this->writer->createForUser($user, $this->dspdSnapshot($user, withRepresentative: false));
            $this->fail('DSPD without representative must be rejected.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_child_insert_failure_rolls_back_entire_aggregate(): void
    {
        $user = $this->makeKorisnik();
        $base = $this->flSnapshot($user);
        $person = $base->physicalPerson;
        $oversized = new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $base->mobilePhone,
            streetAndNumber: $person->streetAndNumber,
            city: str_repeat('X', 300),
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: $person->firstName,
                lastName: $person->lastName,
                residentialStatus: $person->residentialStatus,
                streetAndNumber: $person->streetAndNumber,
                city: str_repeat('X', 300),
                idDocumentType: $person->idDocumentType,
                jmb: $person->jmb,
            ),
        );

        try {
            $this->writer->createForUser($user, $oversized);
            $this->fail('Oversized city must fail persistence.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_update_physical_person_graph_is_in_place_and_does_not_mutate_users(): void
    {
        $user = $this->makeKorisnik();
        $before = $this->legacyIdentityPayload($user);
        $this->writer->createForUser($user, $this->flSnapshot($user, [
            'person' => ['residenceCountryCode' => null],
        ]));
        $platformId = (int) PlatformIdentity::query()->where('user_id', $user->id)->value('id');
        $flId = (int) PhysicalPersonIdentity::query()->value('id');

        $updated = $this->flSnapshot($user, [
            'mobilePhone' => '+38267000999',
            'person' => [
                'firstName' => 'Nova',
                'lastName' => 'Anić',
                'streetAndNumber' => 'Njegoševa 99',
                'city' => 'Budva',
                'jmb' => '0000000000000',
                'residenceCountryCode' => null,
            ],
        ]);
        $this->writer->updatePhysicalPersonGraph($user, $updated);

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame($platformId, (int) PlatformIdentity::query()->where('user_id', $user->id)->value('id'));
        $this->assertSame($flId, (int) PhysicalPersonIdentity::query()->value('id'));
        $this->assertSame('+38267000999', PlatformIdentity::query()->first()->mobile_phone);
        $this->assertSame('Nova', PhysicalPersonIdentity::query()->first()->first_name);
        $this->assertSame('Budva', PhysicalPersonIdentity::query()->first()->city);
        $this->assertSame($before, $this->legacyIdentityPayload($user->fresh()));
        $this->assertSame(0, LegalEntityIdentity::query()->count());
    }

    public function test_update_physical_person_graph_refuses_non_fl_snapshot(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->flSnapshot($user, [
            'person' => ['residenceCountryCode' => null],
        ]));
        $before = PhysicalPersonIdentity::query()->first()->toArray();

        try {
            $this->writer->updatePhysicalPersonGraph($user, $this->plSnapshot($user));
            $this->fail('Legal entity snapshot must not update an FL graph.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertSame($before, PhysicalPersonIdentity::query()->first()->toArray());
            $this->assertSame(0, LegalEntityIdentity::query()->count());
        }
    }

    public function test_create_for_user_still_refuses_existing_platform_identity(): void
    {
        $user = $this->makeKorisnik();
        $snapshot = $this->flSnapshot($user, [
            'person' => ['residenceCountryCode' => null],
        ]);
        $this->writer->createForUser($user, $snapshot);

        try {
            $this->writer->createForUser($user, $snapshot);
            $this->fail('createForUser must remain create-only.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
            $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyIdentityPayload(User $user): array
    {
        return $user->only([
            'user_type',
            'residential_status',
            'first_name',
            'last_name',
            'company_name',
            'jmb',
            'pib',
            'passport_number',
            'phone',
            'address',
            'city',
        ]);
    }

    private function assertCanonicalTablesEmpty(): void
    {
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
        $this->assertSame(0, ForeignBranchRepresentative::query()->count());
    }
}
