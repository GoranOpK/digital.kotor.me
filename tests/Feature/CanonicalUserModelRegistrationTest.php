<?php

namespace Tests\Feature;

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
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_form_offers_exactly_the_canonical_eight_and_not_legacy(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('value="Fizičko lice"', $html);
        $this->assertStringContainsString('value="Registrovan privredni subjekt"', $html);

        foreach (UserType::registrationBusinessStorageValues() as $value) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
        }

        foreach (self::LEGACY_VALUES as $legacy) {
            $this->assertStringNotContainsString('value="'.$legacy.'"', $html);
        }
    }

    public function test_physical_person_resident_can_register(): void
    {
        $this->post('/register', $this->physicalPayload([
            'email' => 'fl.resident@example.com',
            'email_confirmation' => 'fl.resident@example.com',
            'residential_status' => 'resident',
            'jmb' => $this->validJmb(1),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'fl.resident@example.com')->firstOrFail();
        $this->assertSame('resident', $user->residential_status);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
    }

    public function test_physical_person_non_resident_can_register(): void
    {
        $this->post('/register', $this->physicalPayload([
            'email' => 'fl.nonresident@example.com',
            'email_confirmation' => 'fl.nonresident@example.com',
            'residential_status' => 'non-resident',
            'address' => 'Main Street 1',
            'city' => 'Berlin',
            'non_resident_id_type' => 'passport',
            'passport_number' => 'XY111111',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $this->assertSame(
            'non-resident',
            User::query()->where('email', 'fl.nonresident@example.com')->value('residential_status')
        );
    }

    public function test_entrepreneur_resident_can_register_without_pib(): void
    {
        $this->post('/register', $this->businessPayload(UserType::ENTREPRENEUR, [
            'email' => 'ent.resident@example.com',
            'email_confirmation' => 'ent.resident@example.com',
            'residential_status' => 'resident',
            'jmb' => $this->validJmb(2),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $entrepreneur = User::query()->where('email', 'ent.resident@example.com')->firstOrFail();
        $this->assertSame(UserType::ENTREPRENEUR, $entrepreneur->user_type);
        $this->assertSame('resident', $entrepreneur->residential_status);
        $this->assertNull($entrepreneur->pib);
        $this->assertTrue($entrepreneur->isNaturalPerson());
        $this->assertFalse($entrepreneur->isLegalEntity());
    }

    public function test_entrepreneur_non_resident_can_register(): void
    {
        $this->post('/register', $this->businessPayload(UserType::ENTREPRENEUR, [
            'email' => 'ent.nonresident@example.com',
            'email_confirmation' => 'ent.nonresident@example.com',
            'residential_status' => 'non-resident',
            'address' => 'Main Street 1',
            'city' => 'Berlin',
            'non_resident_id_type' => 'passport',
            'passport_number' => 'XY222222',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $this->assertSame(
            'non-resident',
            User::query()->where('email', 'ent.nonresident@example.com')->value('residential_status')
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function legalEntityProvider(): array
    {
        return [
            'doo' => [UserType::LIMITED_LIABILITY_COMPANY, '11111111'],
            'ad' => [UserType::JOINT_STOCK_COMPANY, '22222222'],
            'od' => [UserType::GENERAL_PARTNERSHIP, '33333333'],
            'kd' => [UserType::LIMITED_PARTNERSHIP, '44444444'],
            'nvo' => [UserType::NGO_ASSOCIATION, '55555555'],
            'sportska organizacija' => [UserType::SPORTS_ORGANIZATION, '66666666'],
        ];
    }

    #[DataProvider('legalEntityProvider')]
    public function test_legal_entity_registration_does_not_require_or_persist_residential_status(
        string $businessType,
        string $pib
    ): void {
        $email = strtolower($pib).'@legal.example.com';

        $this->post('/register', $this->businessPayload($businessType, [
            'email' => $email,
            'email_confirmation' => $email,
            'company_name' => 'Subjekt '.$pib,
            'pib' => $pib,
            'residential_status' => 'resident',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertSame($businessType, $user->user_type);
        $this->assertNull($user->residential_status);
        $this->assertTrue($user->isLegalEntity());
        $this->assertFalse($user->isNaturalPerson());
        $this->assertSame($pib, $user->pib);
    }

    public function test_registration_rejects_legacy_values_as_new_writes(): void
    {
        foreach (self::LEGACY_VALUES as $index => $legacy) {
            $email = 'legacy'.$index.'@example.com';

            $this->post('/register', $this->businessPayload($legacy, [
                'email' => $email,
                'email_confirmation' => $email,
                'company_name' => 'Legacy '.$index,
                'pib' => (string) (70000000 + $index),
            ]))->assertSessionHasErrors('business_type');

            $this->assertFalse(User::query()->where('email', $email)->exists());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function physicalPayload(array $overrides = []): array
    {
        return array_merge([
            'user_type' => UserType::PHYSICAL_PERSON,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.fl@example.com',
            'email_confirmation' => 'test.fl@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone_full' => '+38267000001',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'residential_status' => 'resident',
            'jmb' => $this->validJmb(10),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function businessPayload(string $businessType, array $overrides = []): array
    {
        return array_merge([
            'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
            'business_type' => $businessType,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.biz@example.com',
            'email_confirmation' => 'test.biz@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone_full' => '+38267000001',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
        ], $overrides);
    }
}
