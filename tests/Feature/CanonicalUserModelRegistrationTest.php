<?php

namespace Tests\Feature;

use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class CanonicalUserModelRegistrationTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const LEGACY_VALUES = [
        UserType::LEGACY_FOREIGN_BRANCH,
        UserType::LEGACY_ASSOCIATION_BUNDLE,
        UserType::LEGACY_INSTITUTION_BUNDLE,
        UserType::LEGACY_OTHER_ORGANIZATIONS,
    ];

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

    public function test_registration_form_offers_exactly_the_canonical_eight_and_not_legacy(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('value="Fizičko lice"', $html);
        $this->assertStringContainsString('value="Registrovan privredni subjekt"', $html);
        $this->assertStringContainsString('value="Dio stranog privrednog društva"', $html);

        foreach (UserType::registrationBusinessStorageValues() as $value) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
        }

        foreach (self::LEGACY_VALUES as $legacy) {
            $this->assertStringNotContainsString('value="'.$legacy.'"', $html);
        }
    }

    public function test_physical_person_resident_can_register(): void
    {
        $this->post('/register', $this->registrationHttpPayload('fl.resident@example.com', [
            'jmb' => $this->validJmb(1),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'fl.resident@example.com')->firstOrFail();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertNull($user->residential_status);
        $this->assertSame('resident', PhysicalPersonIdentity::query()->value('residential_status'));
    }

    public function test_physical_person_non_resident_can_register(): void
    {
        $this->post('/register', $this->registrationHttpPayload('fl.nonresident@example.com', [
            'residential_status' => 'non-resident',
            'id_document_type' => 'passport',
            'jmb' => null,
            'passport_number' => 'XY111111',
            'residence_country_code' => 'DE',
            'address' => 'Main Street 1',
            'city' => 'Berlin',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $this->assertSame('non_resident', PhysicalPersonIdentity::query()->value('residential_status'));
    }

    public function test_entrepreneur_resident_requires_pib_and_crps(): void
    {
        $this->post('/register', $this->registrationHttpPayload('ent.missing@example.com', [
            'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
            'business_type' => UserType::ENTREPRENEUR,
            'jmb' => $this->validJmb(2),
        ]))->assertSessionHasErrors(['pib', 'crps_number', 'entrepreneur_business_name']);
    }

    public function test_entrepreneur_resident_can_register_with_mandatory_business_data(): void
    {
        $this->post('/register', $this->registrationHttpPayload('ent.resident@example.com', [
            'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
            'business_type' => UserType::ENTREPRENEUR,
            'jmb' => $this->validJmb(2),
            'entrepreneur_business_name' => 'Radnja Ana',
            'pib' => $this->validPib(12),
            'crps_number' => $this->validCrps(1, 2),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $entrepreneur = User::query()->where('email', 'ent.resident@example.com')->firstOrFail();
        $this->assertSame(UserType::ENTREPRENEUR, $entrepreneur->user_type);
        $this->assertNull($entrepreneur->pib);
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertTrue($fl->is_entrepreneur);
        $this->assertSame($this->validPib(12), $fl->pib);
        $this->assertTrue($entrepreneur->isNaturalPerson());
        $this->assertFalse($entrepreneur->isLegalEntity());
    }

    /**
     * @return array<string, array{0: string, 1: int|null}>
     */
    public static function legalEntityProvider(): array
    {
        return [
            'doo' => [UserType::LIMITED_LIABILITY_COMPANY, 5],
            'ad' => [UserType::JOINT_STOCK_COMPANY, 4],
            'od' => [UserType::GENERAL_PARTNERSHIP, 2],
            'kd' => [UserType::LIMITED_PARTNERSHIP, 3],
            'nvo' => [UserType::NGO_ASSOCIATION, null],
            'fondacija' => [UserType::NGO_FOUNDATION, null],
            'sportska organizacija' => [UserType::SPORTS_ORGANIZATION, null],
        ];
    }

    #[DataProvider('legalEntityProvider')]
    public function test_legal_entity_registration_does_not_require_or_persist_residential_status(
        string $businessType,
        ?int $crpsMark
    ): void {
        $email = strtolower(preg_replace('/[^a-z]+/i', '', $businessType)).'@legal.example.com';
        $pib = $this->validPib(abs(crc32($businessType)) % 100000 + 20);

        $payload = $this->registrationHttpPayload($email, [
            'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
            'business_type' => $businessType,
            'first_name' => null,
            'last_name' => null,
            'residential_status' => 'resident',
            'jmb' => null,
            'legal_name' => 'Subjekt '.$businessType,
            'pib' => $pib,
            'crps_number' => $crpsMark ? $this->validCrps($crpsMark, 8) : null,
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'authorized_id_document_type' => 'jmb',
            'authorized_jmb' => $this->validJmb(abs(crc32($businessType)) % 200 + 50),
        ]);

        $this->post('/register', $payload)->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', $email)->firstOrFail();
        if (UserType::isNgoFoundation($businessType)) {
            $this->assertNull($user->user_type);
        } else {
            $this->assertSame($businessType, $user->user_type);
            $this->assertTrue($user->isLegalEntity());
        }
        $this->assertNull($user->residential_status);
        $this->assertNull($user->pib);
        $this->assertFalse($user->isNaturalPerson());
        $legal = LegalEntityIdentity::query()->firstOrFail();
        $this->assertSame($pib, $legal->pib);
        if ($crpsMark === null) {
            $this->assertNull($legal->crps_number);
        } else {
            $this->assertSame($this->validCrps($crpsMark, 8), $legal->crps_number);
        }
    }

    public function test_registration_rejects_legacy_values_as_new_writes(): void
    {
        foreach (self::LEGACY_VALUES as $index => $legacy) {
            $email = 'legacy'.$index.'@example.com';

            $this->post('/register', $this->registrationHttpPayload($email, [
                'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
                'business_type' => $legacy,
                'legal_name' => 'Legacy '.$index,
                'pib' => $this->validPib(700 + $index),
            ]))->assertSessionHasErrors('business_type');

            $this->assertFalse(User::query()->where('email', $email)->exists());
        }
    }
}
