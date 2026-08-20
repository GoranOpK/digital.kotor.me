<?php

namespace Tests\Unit;

use App\Support\UserType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserTypeSemanticsTest extends TestCase
{
    public function test_canonical_set_has_exactly_eight_types(): void
    {
        $this->assertCount(8, UserType::canonicalStorageValues());
        $this->assertSame(UserType::canonicalStorageValues(), UserType::allowedNewWriteValues());
    }

    /**
     * @return array<string, array{0: string, 1: bool, 2: bool, 3: bool}>
     */
    public static function canonicalSemanticsProvider(): array
    {
        return [
            'fizicko lice' => [UserType::PHYSICAL_PERSON, true, false, false],
            'preduzetnik' => [UserType::ENTREPRENEUR, true, true, false],
            'doo' => [UserType::LIMITED_LIABILITY_COMPANY, false, false, true],
            'ad' => [UserType::JOINT_STOCK_COMPANY, false, false, true],
            'od' => [UserType::GENERAL_PARTNERSHIP, false, false, true],
            'kd' => [UserType::LIMITED_PARTNERSHIP, false, false, true],
            'nvo' => [UserType::NGO_ASSOCIATION, false, false, true],
            'sportska organizacija' => [UserType::SPORTS_ORGANIZATION, false, false, true],
        ];
    }

    #[DataProvider('canonicalSemanticsProvider')]
    public function test_canonical_identity_semantics(
        string $type,
        bool $natural,
        bool $entrepreneur,
        bool $legal
    ): void {
        $this->assertSame($natural, UserType::isNaturalPerson($type));
        $this->assertSame($entrepreneur, UserType::isEntrepreneur($type));
        $this->assertSame($legal, UserType::isLegalEntity($type));
        $this->assertSame($natural, UserType::requiresResidentialStatus($type));
        $this->assertTrue(UserType::isCanonical($type));
    }

    public function test_legacy_bundles_remain_legal_entities_and_are_not_new_write_values(): void
    {
        foreach (UserType::retainedLegacyStorageValues() as $type) {
            $this->assertTrue(UserType::isLegalEntity($type));
            $this->assertFalse(UserType::isNaturalPerson($type));
            $this->assertFalse(UserType::isCanonical($type));
            $this->assertTrue(UserType::isRetainedLegacy($type));
            $this->assertNotContains($type, UserType::allowedNewWriteValues());
        }
    }

    public function test_eligibility_attributes_are_not_user_types(): void
    {
        $notUserTypes = [
            'Poljoprivrednik',
            'Registrovani poljoprivredni proizvođač',
            'Ribar',
            'Marikulturista',
            'Mladi preduzetnik',
            'Mikro preduzeće',
            'Malo preduzeće',
            'Srednje preduzeće',
            'Individualni sportista',
            'DOO sa jednim članom',
            'Nevladina fondacija',
        ];

        $storage = UserType::mysqlEnumValues();

        foreach ($notUserTypes as $value) {
            $this->assertNotContains($value, $storage);
            $this->assertFalse(UserType::isCanonical($value));
        }
    }

    public function test_profile_write_set_keeps_current_legacy_value_only(): void
    {
        $current = UserType::LEGACY_ASSOCIATION_BUNDLE;
        $allowed = UserType::allowedProfileWriteValues($current);

        $this->assertContains($current, $allowed);
        $this->assertContains(UserType::NGO_ASSOCIATION, $allowed);
        $this->assertNotContains(UserType::LEGACY_INSTITUTION_BUNDLE, $allowed);
        $this->assertSame(
            UserType::canonicalStorageValues(),
            UserType::allowedProfileWriteValues(UserType::LIMITED_LIABILITY_COMPANY)
        );
    }
}
