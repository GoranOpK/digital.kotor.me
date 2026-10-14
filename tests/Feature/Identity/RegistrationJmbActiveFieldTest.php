<?php

namespace Tests\Feature\Identity;

use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\PhysicalPersonIdentity;
use App\Models\User;
use App\Rules\ValidJmb;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class RegistrationJmbActiveFieldTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private const INVALID_JMB = '1234567890123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
    }

    public function test_active_invalid_physical_person_jmb_is_rejected_with_local_message(): void
    {
        $this->post('/register', $this->registrationHttpPayload('fl.res.bad@example.test', [
            'jmb' => self::INVALID_JMB,
        ]))->assertSessionHasErrors(['jmb' => 'JMB nije ispravan.']);

        $this->post('/register', $this->registrationHttpPayload('fl.nr.bad@example.test', [
            'residential_status' => 'non-resident',
            'id_document_type' => 'jmb',
            'residence_country_code' => 'DE',
            'jmb' => self::INVALID_JMB,
        ]))->assertSessionHasErrors(['jmb' => 'JMB nije ispravan.']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_active_invalid_entrepreneur_jmb_is_rejected_with_local_message(): void
    {
        $this->post('/register', $this->entrepreneurPayload('ent.bad@example.test', [
            'jmb' => self::INVALID_JMB,
        ]))->assertSessionHasErrors(['jmb' => 'JMB nije ispravan.']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_active_invalid_doo_authorized_person_jmb_is_rejected_with_local_message(): void
    {
        $this->post('/register', $this->legalPayload('doo.bad@example.test', [
            'authorized_jmb' => self::INVALID_JMB,
        ]))->assertSessionHasErrors(['authorized_jmb' => 'JMB nije ispravan.']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_active_invalid_dspd_representative_jmb_is_rejected_with_local_message(): void
    {
        $this->post('/register', $this->dspdPayload('dspd.bad@example.test', [
            'representative_jmb' => self::INVALID_JMB,
        ]))->assertSessionHasErrors(['representative_jmb' => 'JMB nije ispravan.']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_valid_jmb_rule_reports_local_message(): void
    {
        $validator = Validator::make(['authorized_jmb' => '0000000000001'], ['authorized_jmb' => [new ValidJmb]]);

        $this->assertTrue($validator->fails());
        $this->assertSame(['JMB nije ispravan.'], $validator->errors()->get('authorized_jmb'));
    }

    public function test_inactive_invalid_jmb_fields_do_not_block_other_categories(): void
    {
        $this->assertRegistered($this->registrationHttpPayload('fl.res.stale@example.test', [
            'authorized_jmb' => self::INVALID_JMB,
            'representative_jmb' => self::INVALID_JMB,
        ]));

        $this->assertRegistered($this->entrepreneurPayload('ent.stale@example.test', [
            'authorized_jmb' => self::INVALID_JMB,
            'representative_jmb' => self::INVALID_JMB,
        ]));

        $this->assertRegistered($this->legalPayload('doo.stale@example.test', [
            'jmb' => self::INVALID_JMB,
            'representative_jmb' => self::INVALID_JMB,
        ], 1));

        $this->assertRegistered($this->legalPayload('doo.passport.stale@example.test', [
            'authorized_id_document_type' => 'passport',
            'authorized_passport_number' => 'CD123456',
            'authorized_passport_issuing_country_code' => 'DE',
            'authorized_jmb' => self::INVALID_JMB,
        ], 2));

        $this->assertRegistered($this->dspdPayload('dspd.stale@example.test', [
            'jmb' => self::INVALID_JMB,
            'authorized_jmb' => self::INVALID_JMB,
        ], 1));

        $this->assertRegistered($this->dspdPayload('dspd.passport.stale@example.test', [
            'representative_id_document_type' => 'passport',
            'representative_passport_number' => 'EF123456',
            'representative_passport_issuing_country_code' => 'DE',
            'representative_jmb' => self::INVALID_JMB,
        ], 2));

        $this->assertSame(6, User::query()->count());

        $ap = LegalEntityAuthorizedPerson::query()->where('id_document_type', 'passport')->firstOrFail();
        $this->assertNull($ap->jmb);
        $this->assertSame('CD123456', $ap->passport_number);

        $rep = ForeignBranchRepresentative::query()->where('id_document_type', 'passport')->firstOrFail();
        $this->assertNull($rep->jmb);
        $this->assertSame('EF123456', $rep->passport_number);
    }

    public function test_non_resident_passport_with_stale_invalid_jmb_completes_registration(): void
    {
        $this->assertRegistered($this->nonResidentPassportPayload('nr.pass.bad@example.test', 'GH123456', [
            'jmb' => self::INVALID_JMB,
        ]));

        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_PASSPORT, $fl->id_document_type);
        $this->assertSame('GH123456', $fl->passport_number);
        $this->assertNull($fl->jmb);
    }

    public function test_non_resident_passport_with_stale_valid_jmb_persists_passport_only(): void
    {
        $this->assertRegistered($this->nonResidentPassportPayload('nr.pass.valid@example.test', 'jk123456', [
            'jmb' => $this->validJmb(401),
        ]));

        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT, $fl->residential_status);
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_PASSPORT, $fl->id_document_type);
        $this->assertSame('JK123456', $fl->passport_number);
        $this->assertSame('DE', $fl->residence_country_code);
        $this->assertNull($fl->jmb);
        $this->assertNull($fl->jmb_lookup);
    }

    public function test_inactive_jmb_uniqueness_does_not_block_registration(): void
    {
        $taken = $this->validJmb(402);

        $this->assertRegistered($this->registrationHttpPayload('jmb.owner@example.test', ['jmb' => $taken]));

        $this->assertRegistered($this->nonResidentPassportPayload('jmb.stale.nr@example.test', 'LM123456', [
            'jmb' => $taken,
        ]));

        $this->assertRegistered($this->legalPayload('jmb.stale.doo@example.test', ['jmb' => $taken], 3));

        $this->assertSame(3, User::query()->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->whereNotNull('jmb')->count());
    }

    public function test_inactive_passport_uniqueness_does_not_block_registration(): void
    {
        $this->assertRegistered($this->nonResidentPassportPayload('passport.owner@example.test', 'NP123456'));

        $this->assertRegistered($this->registrationHttpPayload('passport.stale.res@example.test', [
            'jmb' => $this->validJmb(403),
            'passport_number' => 'NP123456',
        ]));

        $this->assertRegistered($this->registrationHttpPayload('passport.stale.nrjmb@example.test', [
            'residential_status' => 'non-resident',
            'id_document_type' => 'jmb',
            'residence_country_code' => 'DE',
            'jmb' => $this->validJmb(404),
            'passport_number' => 'NP123456',
        ]));

        $this->assertSame(3, User::query()->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->where('passport_number', 'NP123456')->count());
    }

    public function test_active_jmb_validation_is_unchanged(): void
    {
        $this->post('/register', $this->registrationHttpPayload('active.missing@example.test', ['jmb' => null]))
            ->assertSessionHasErrors(['jmb' => 'Unesite JMB.']);

        $this->post('/register', $this->registrationHttpPayload('active.short@example.test', ['jmb' => '12345']))
            ->assertSessionHasErrors(['jmb' => 'JMB mora imati tačno 13 cifara.']);

        $this->post('/register', $this->legalPayload('active.ap.short@example.test', ['authorized_jmb' => '12345']))
            ->assertSessionHasErrors(['authorized_jmb' => 'JMB mora imati tačno 13 cifara.']);

        $this->post('/register', $this->dspdPayload('active.rep.missing@example.test', ['representative_jmb' => null]))
            ->assertSessionHasErrors(['representative_jmb' => 'Unesite JMB zastupnika.']);

        $jmb = $this->validJmb(405);
        $this->assertRegistered($this->registrationHttpPayload('active.ok@example.test', ['jmb' => $jmb]));

        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $fl->id_document_type);
        $this->assertSame($jmb, $fl->jmb);

        $this->post('/register', $this->registrationHttpPayload('active.dup@example.test', ['jmb' => $jmb]))
            ->assertSessionHasErrors(['jmb' => 'JMB je već registrovan.']);

        $this->assertSame(1, User::query()->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertRegistered(array $payload): void
    {
        $this->post('/register', $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->post(route('logout'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function nonResidentPassportPayload(string $email, string $passport, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'residential_status' => 'non-resident',
            'id_document_type' => 'passport',
            'residence_country_code' => 'DE',
            'passport_number' => $passport,
            'jmb' => null,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function entrepreneurPayload(string $email, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'registers_as_entrepreneur' => '1',
            'jmb' => $this->validJmb(410),
            'entrepreneur_business_name' => 'Radnja Test',
            'pib' => $this->validPib(410),
            'crps_number' => $this->validCrps(1, 1),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function legalPayload(string $email, array $overrides = [], int $seq = 0): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'user_type' => UserType::REGISTRATION_GROUP_LEGAL_ENTITY,
            'business_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'first_name' => null,
            'last_name' => null,
            'residential_status' => null,
            'jmb' => null,
            'legal_name' => 'Subjekt '.$email,
            'pib' => $this->validPib(420 + $seq),
            'crps_number' => $this->validCrps(5, 1 + $seq),
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'authorized_id_document_type' => 'jmb',
            'authorized_jmb' => $this->validJmb(420 + $seq),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dspdPayload(string $email, array $overrides = [], int $seq = 0): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'user_type' => UserType::FOREIGN_BRANCH,
            'first_name' => null,
            'last_name' => null,
            'residential_status' => null,
            'jmb' => null,
            'legal_name' => null,
            'foreign_company_name' => 'Foreign Co Ltd',
            'branch_name_in_montenegro' => 'Ogranak Kotor',
            'pib' => $this->validPib(430 + $seq),
            'crps_number' => $this->validCrps(6, 1 + $seq),
            'representative_first_name' => 'Jelena',
            'representative_last_name' => 'Jović',
            'representative_id_document_type' => 'jmb',
            'representative_jmb' => $this->validJmb(430 + $seq),
        ], $overrides));
    }
}
