<?php

namespace Tests\Feature;

use App\Enums\PaymentAvailabilityOutcome;
use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\User;
use App\Services\Payments\PaymentAvailabilityService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class PaymentAvailabilityEngineTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private PaymentAvailabilityService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->engine = $this->app->make(PaymentAvailabilityService::class);
    }

    #[DataProvider('canonicalLegalEntities')]
    public function test_matching_canonical_legal_entity_is_available(string $userType): void
    {
        [$type, $account] = $this->syntheticPair('syn-legal-'.UserType::canonicalCode($userType));
        $this->grantType($type, $userType);
        $this->grantAccount($account, $userType);

        $user = $this->legalUser($userType);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateAccount($user, $account));
        $this->assertTrue($this->engine->isTypeUsable($user, $type));
    }

    public function test_non_matching_legal_entity_is_not_available(): void
    {
        [$type, $account] = $this->syntheticPair('syn-legal-mismatch');
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($account, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::JOINT_STOCK_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
        $this->assertFalse($this->engine->isTypeUsable($user, $type));
    }

    public function test_physical_person_resident_match(): void
    {
        $this->assertNaturalMatch(UserType::PHYSICAL_PERSON, 'resident');
    }

    public function test_physical_person_non_resident_match(): void
    {
        $this->assertNaturalMatch(UserType::PHYSICAL_PERSON, 'non-resident');
    }

    public function test_entrepreneur_resident_match(): void
    {
        $this->assertNaturalMatch(UserType::ENTREPRENEUR, 'resident');
    }

    public function test_entrepreneur_non_resident_match(): void
    {
        $this->assertNaturalMatch(UserType::ENTREPRENEUR, 'non-resident');
    }

    public function test_physical_person_null_residential_requires_declaration(): void
    {
        [$type, $account] = $this->syntheticPair('syn-fl-null');
        $this->grantType($type, UserType::PHYSICAL_PERSON, 'resident');
        $this->grantAccount($account, UserType::PHYSICAL_PERSON, 'resident');

        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => null,
        ]);

        $this->assertSame(
            PaymentAvailabilityOutcome::ResidentialDeclarationRequired,
            $this->engine->evaluateType($user, $type)
        );
        $this->assertSame(
            PaymentAvailabilityOutcome::ResidentialDeclarationRequired,
            $this->engine->evaluateAccount($user, $account)
        );
        $this->assertFalse($this->engine->isTypeUsable($user, $type));
    }

    public function test_entrepreneur_null_residential_requires_declaration(): void
    {
        [$type, $account] = $this->syntheticPair('syn-ent-null');
        $this->grantType($type, UserType::ENTREPRENEUR, 'non-resident');
        $this->grantAccount($account, UserType::ENTREPRENEUR, 'non-resident');

        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => null,
        ]);

        $this->assertSame(
            PaymentAvailabilityOutcome::ResidentialDeclarationRequired,
            $this->engine->evaluateType($user, $type)
        );
        $this->assertSame(
            PaymentAvailabilityOutcome::ResidentialDeclarationRequired,
            $this->engine->evaluateAccount($user, $account)
        );
    }

    public function test_legal_entity_user_with_leftover_residential_still_matches_type_only_rule(): void
    {
        [$type, $account] = $this->syntheticPair('syn-legal-leftover');
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($account, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY, [
            'residential_status' => 'resident',
        ]);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateAccount($user, $account));
    }

    public function test_legacy_user_type_is_fail_closed(): void
    {
        [$type, $account] = $this->syntheticPair('syn-legacy');
        $this->grantType($type, UserType::NGO_ASSOCIATION);
        $this->grantAccount($account, UserType::NGO_ASSOCIATION);

        $user = $this->legalUser(UserType::NGO_ASSOCIATION);
        $user->user_type = UserType::LEGACY_ASSOCIATION_BUNDLE;

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
    }

    public function test_ex_non_resident_is_fail_closed(): void
    {
        [$type, $account] = $this->syntheticPair('syn-ex-non');
        $this->grantType($type, UserType::PHYSICAL_PERSON, 'non-resident');
        $this->grantAccount($account, UserType::PHYSICAL_PERSON, 'non-resident');

        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]);
        $user->residential_status = 'ex-non-resident';

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
    }

    public function test_inactive_type_is_unavailable_even_with_matching_rules(): void
    {
        [$type, $account] = $this->syntheticPair('syn-inactive-type', typeActive: false);
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($account, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
    }

    public function test_inactive_account_is_unavailable_even_with_matching_rules(): void
    {
        [$type, $account] = $this->syntheticPair('syn-inactive-acc', accountActive: false);
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($account, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
        $this->assertFalse($this->engine->isTypeUsable($user, $type));
    }

    public function test_missing_type_rule_is_unavailable(): void
    {
        [$type, $account] = $this->syntheticPair('syn-no-type-rule');
        $this->grantAccount($account, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
    }

    public function test_missing_account_rule_is_unavailable(): void
    {
        [$type, $account] = $this->syntheticPair('syn-no-acc-rule');
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
        $this->assertFalse($this->engine->isTypeUsable($user, $type));
    }

    public function test_type_match_account_no_match_is_account_unavailable(): void
    {
        [$type, $account] = $this->syntheticPair('syn-acc-nomatch');
        $this->grantType($type, UserType::PHYSICAL_PERSON, 'resident');
        $this->grantType($type, UserType::PHYSICAL_PERSON, 'non-resident');
        $this->grantAccount($account, UserType::PHYSICAL_PERSON, 'non-resident');

        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateType($user, $type));
        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
    }

    public function test_type_and_account_match_is_available(): void
    {
        [$type, $account] = $this->syntheticPair('syn-both-match');
        $this->grantType($type, UserType::PHYSICAL_PERSON, 'resident');
        $this->grantAccount($account, UserType::PHYSICAL_PERSON, 'resident');

        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateAccount($user, $account));
        $this->assertTrue($this->engine->isAccountAvailable($user, $account));
    }

    public function test_multiple_matching_accounts_are_supported(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-type-business',
            'name' => 'Synthetic multi-account type',
            'is_active' => true,
        ]);
        $first = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-ACCOUNT-001',
            'is_active' => true,
        ]);
        $second = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-ACCOUNT-002',
            'is_active' => true,
        ]);
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($first, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($second, UserType::LIMITED_LIABILITY_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);
        $accounts = $this->engine->usableAccountsFor($user, $type);

        $this->assertCount(2, $accounts);
        $this->assertTrue($accounts->contains('id', $first->id));
        $this->assertTrue($accounts->contains('id', $second->id));
    }

    public function test_no_matching_accounts_means_type_is_not_usable(): void
    {
        [$type, $account] = $this->syntheticPair('syn-unusable');
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY);
        $this->grantAccount($account, UserType::JOINT_STOCK_COMPANY);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateType($user, $type));
        $this->assertFalse($this->engine->isTypeUsable($user, $type));
        $this->assertCount(0, $this->engine->usableTypesFor($user));
    }

    public function test_inactive_availability_rule_does_not_grant_access(): void
    {
        [$type, $account] = $this->syntheticPair('syn-inactive-rule');
        $this->grantType($type, UserType::LIMITED_LIABILITY_COMPANY, active: false);
        $this->grantAccount($account, UserType::LIMITED_LIABILITY_COMPANY, active: false);

        $user = $this->legalUser(UserType::LIMITED_LIABILITY_COMPANY);

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateAccount($user, $account));
    }

    public function test_null_residential_without_any_type_rule_is_not_available(): void
    {
        [$type] = $this->syntheticPair('syn-null-norule');

        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => null,
        ]);

        $this->assertSame(PaymentAvailabilityOutcome::NotAvailable, $this->engine->evaluateType($user, $type));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function canonicalLegalEntities(): array
    {
        $cases = [];
        foreach (UserType::canonicalLegalEntityStorageValues() as $value) {
            $cases[UserType::canonicalCode($value) ?? $value] = [$value];
        }

        return $cases;
    }

    private function assertNaturalMatch(string $userType, string $residential): void
    {
        [$type, $account] = $this->syntheticPair('syn-nat-'.md5($userType.$residential));
        $this->grantType($type, $userType, $residential);
        $this->grantAccount($account, $userType, $residential);

        $user = $this->makeKorisnik([
            'user_type' => $userType,
            'residential_status' => $residential,
        ]);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $this->engine->evaluateAccount($user, $account));
        $this->assertTrue($this->engine->usableTypesFor($user)->contains('id', $type->id));
    }

    /**
     * @return array{0: PaymentType, 1: PaymentAccount}
     */
    private function syntheticPair(string $code, bool $typeActive = true, bool $accountActive = true): array
    {
        $type = PaymentType::factory()->create([
            'code' => $code,
            'name' => 'Synthetic '.$code,
            'is_active' => $typeActive,
        ]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-'.strtoupper(substr(md5($code), 0, 20)),
            'is_active' => $accountActive,
        ]);

        return [$type, $account];
    }

    private function grantType(PaymentType $type, string $userType, ?string $residential = null, bool $active = true): PaymentTypeAvailability
    {
        return PaymentTypeAvailability::factory()->create([
            'payment_type_id' => $type->id,
            'user_type' => $userType,
            'residential_status' => $residential,
            'is_active' => $active,
        ]);
    }

    private function grantAccount(PaymentAccount $account, string $userType, ?string $residential = null, bool $active = true): PaymentAccountAvailability
    {
        return PaymentAccountAvailability::factory()->create([
            'payment_account_id' => $account->id,
            'user_type' => $userType,
            'residential_status' => $residential,
            'is_active' => $active,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function legalUser(string $userType, array $overrides = []): User
    {
        return $this->makeKorisnik(array_merge([
            'user_type' => $userType,
            'residential_status' => null,
            'company_name' => 'Synthetic '.$userType,
            'pib' => '12345678',
            'jmb' => null,
        ], $overrides));
    }
}
