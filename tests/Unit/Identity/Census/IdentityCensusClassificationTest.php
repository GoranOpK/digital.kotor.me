<?php

namespace Tests\Unit\Identity\Census;

use App\Identity\Census\IdentityCensusFieldStatus;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Support\UserType;
use Tests\TestCase;

class IdentityCensusClassificationTest extends TestCase
{
    public function test_valid_resident_non_entrepreneur_fl_may_be_backfillable(): void
    {
        $row = $this->classify($this->subject());

        $this->assertSame(IdentityCensusRow::BACKFILLABLE, $row->rowStatus);
        $this->assertTrue($row->fullyBackfillable);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $row->subjectType);
        $this->assertFalse($row->isEntrepreneur);
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $row->idDocumentType);
        $this->assertSame(IdentityCensusFieldStatus::NOT_APPLICABLE, $row->fieldStatuses['pib']);
        $this->assertSame(IdentityCensusFieldStatus::NOT_APPLICABLE, $row->fieldStatuses['crps']);
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $row->fieldStatuses['phone']);
    }

    public function test_missing_phone_does_not_block_resident_fl_backfill(): void
    {
        $row = $this->classify($this->subject(['phone' => null]));

        $this->assertSame(IdentityCensusRow::BACKFILLABLE, $row->rowStatus);
        $this->assertSame(IdentityCensusFieldStatus::MISSING, $row->fieldStatuses['phone']);
        $this->assertNotContains('phone_missing', $row->d10MissingCodes);
    }

    public function test_resident_fl_missing_jmb_is_missing_required(): void
    {
        $row = $this->classify($this->subject(['jmb' => null]));

        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertFalse($row->fullyBackfillable);
        $this->assertContains('jmb_missing', $row->d10MissingCodes);
        $this->assertSame(IdentityCensusFieldStatus::MISSING, $row->fieldStatuses['jmb']);
    }

    public function test_resident_fl_invalid_jmb_checksum_is_invalid_legacy(): void
    {
        $row = $this->classify($this->subject(['jmb' => '0000000000001']));

        $this->assertSame(IdentityCensusRow::INVALID_LEGACY, $row->rowStatus);
        $this->assertContains('jmb_invalid', $row->d14InvalidCodes);
        $this->assertSame(IdentityCensusFieldStatus::INVALID, $row->fieldStatuses['jmb']);
    }

    public function test_nonresident_fl_with_jmb_only_projects_jmb_but_is_not_backfillable(): void
    {
        $row = $this->classify($this->subject([
            'residential_status' => 'non-resident',
            'passport_number' => null,
        ]));

        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $row->idDocumentType);
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $row->fieldStatuses['id_document_type']);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertContains('residence_country_code_source_unavailable', $row->d10MissingCodes);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_nonresident_fl_with_passport_only_projects_passport_but_is_not_backfillable(): void
    {
        $row = $this->classify($this->subject([
            'residential_status' => 'non-resident',
            'jmb' => null,
            'passport_number' => 'AB123456',
        ]));

        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_PASSPORT, $row->idDocumentType);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertContains('residence_country_code_source_unavailable', $row->d10MissingCodes);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_nonresident_fl_with_both_usable_identifiers_is_ambiguous(): void
    {
        $row = $this->classify($this->subject([
            'residential_status' => 'non-resident',
            'passport_number' => 'AB123456',
        ]));

        $this->assertSame(IdentityCensusRow::AMBIGUOUS_MAPPING, $row->rowStatus);
        $this->assertSame(IdentityCensusFieldStatus::AMBIGUOUS, $row->fieldStatuses['id_document_type']);
        $this->assertContains('id_document_type_ambiguous', $row->reasonCodes);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_entrepreneur_valid_pib_is_deterministic_but_not_backfillable_without_crps(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => '12345672',
        ]));

        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $row->subjectType);
        $this->assertTrue($row->isEntrepreneur);
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $row->fieldStatuses['pib']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['crps']);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertContains('crps_source_unavailable', $row->d10MissingCodes);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_entrepreneur_invalid_pib_is_invalid_legacy(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => '12345670',
        ]));

        $this->assertSame(IdentityCensusRow::INVALID_LEGACY, $row->rowStatus);
        $this->assertContains('pib_invalid', $row->d14InvalidCodes);
        $this->assertTrue($row->isEntrepreneur);
    }

    public function test_deterministic_doo_is_not_backfillable_without_crps_and_authorized_person(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
        ]));

        $this->assertSame(PlatformIdentity::SUBJECT_LEGAL_ENTITY, $row->subjectType);
        $this->assertSame(LegalEntityIdentity::FORM_DOO, $row->legalForm);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['crps']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['authorized_person']);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertFalse($row->fullyBackfillable);
        $this->assertNull($row->idDocumentType);
    }

    public function test_nvo_association_has_crps_not_applicable_and_is_not_backfillable(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::NGO_ASSOCIATION,
            'company_name' => 'Udruzenje Primjer',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
        ]));

        $this->assertSame(LegalEntityIdentity::FORM_NVO_ASSOCIATION, $row->legalForm);
        $this->assertSame(IdentityCensusFieldStatus::NOT_APPLICABLE, $row->fieldStatuses['crps']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['authorized_person']);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_nvo_bundle_is_ambiguous_mapping(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'company_name' => 'Udruzenje bundle',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
        ]));

        $this->assertSame(IdentityCensusRow::AMBIGUOUS_MAPPING, $row->rowStatus);
        $this->assertSame(PlatformIdentity::SUBJECT_LEGAL_ENTITY, $row->subjectType);
        $this->assertNull($row->legalForm);
        $this->assertContains('legal_form_unresolved', $row->reasonCodes);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_sports_organization_requires_pib_not_crps_and_is_not_backfillable(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::SPORTS_ORGANIZATION,
            'company_name' => 'Klub Primjer',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
        ]));

        $this->assertSame(LegalEntityIdentity::FORM_SPORTS_ORGANIZATION, $row->legalForm);
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $row->fieldStatuses['pib']);
        $this->assertSame(IdentityCensusFieldStatus::NOT_APPLICABLE, $row->fieldStatuses['crps']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['authorized_person']);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
    }

    public function test_ustanova_is_unsupported(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::LEGACY_INSTITUTION_BUNDLE,
        ]));

        $this->assertSame(IdentityCensusRow::UNSUPPORTED, $row->rowStatus);
        $this->assertNull($row->subjectType);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_other_organizations_and_typographic_twin_are_unsupported(): void
    {
        $canonical = $this->classify($this->subject([
            'user_type' => UserType::LEGACY_OTHER_ORGANIZATIONS,
        ]));
        $twin = $this->classify($this->subject([
            'user_type' => 'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)',
        ]));

        $this->assertSame(IdentityCensusRow::UNSUPPORTED, $canonical->rowStatus);
        $this->assertSame(IdentityCensusRow::UNSUPPORTED, $twin->rowStatus);
    }

    public function test_known_staff_with_null_type_is_non_subject(): void
    {
        $row = $this->classify($this->subject([
            'role_name' => 'komisija',
            'user_type' => null,
            'residential_status' => null,
            'jmb' => null,
        ]));

        $this->assertTrue($row->isStaffAccount);
        $this->assertSame(IdentityCensusRow::NON_SUBJECT_ACCOUNT, $row->rowStatus);
        $this->assertNull($row->subjectType);
        $this->assertFalse($row->fullyBackfillable);
        $this->assertContains('staff_account', $row->reasonCodes);
    }

    public function test_commission_leftover_fl_resident_is_non_subject_without_subject_backfill(): void
    {
        $row = $this->classify($this->subject([
            'role_name' => 'superadmin',
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]));

        $this->assertSame(IdentityCensusRow::NON_SUBJECT_ACCOUNT, $row->rowStatus);
        $this->assertNull($row->subjectType);
        $this->assertFalse($row->isEntrepreneur);
        $this->assertFalse($row->fullyBackfillable);
        $this->assertContains('leftover_legacy_user_type', $row->reasonCodes);
        $this->assertSame(IdentityCensusFieldStatus::NOT_APPLICABLE, $row->fieldStatuses['jmb']);
    }

    public function test_non_staff_null_user_type_is_unresolved_not_inferred_fl(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => null,
        ]));

        $this->assertFalse($row->isStaffAccount);
        $this->assertSame(IdentityCensusRow::AMBIGUOUS_MAPPING, $row->rowStatus);
        $this->assertNull($row->subjectType);
        $this->assertContains('subject_unresolved', $row->reasonCodes);
        $this->assertFalse($row->fullyBackfillable);
    }

    public function test_missing_city_is_missing_required_where_city_is_required(): void
    {
        $row = $this->classify($this->subject(['city' => null]));

        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertContains('city_missing', $row->d10MissingCodes);
        $this->assertSame(IdentityCensusFieldStatus::MISSING, $row->fieldStatuses['city']);
    }

    public function test_outer_trim_valid_jmb_is_present_valid(): void
    {
        $row = $this->classify($this->subject(['jmb' => ' 0000000000000 ']));

        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $row->fieldStatuses['jmb']);
        $this->assertSame(IdentityCensusRow::BACKFILLABLE, $row->rowStatus);
    }

    public function test_jmb_with_internal_separators_is_invalid(): void
    {
        $row = $this->classify($this->subject(['jmb' => '000000 0000000']));

        $this->assertSame(IdentityCensusFieldStatus::INVALID, $row->fieldStatuses['jmb']);
        $this->assertSame(IdentityCensusRow::INVALID_LEGACY, $row->rowStatus);
        $this->assertContains('jmb_invalid', $row->d14InvalidCodes);
    }

    public function test_leftover_pib_on_non_entrepreneur_does_not_infer_entrepreneur_or_requiredness(): void
    {
        $row = $this->classify($this->subject(['pib' => '12345672']));

        $this->assertFalse($row->isEntrepreneur);
        $this->assertSame(IdentityCensusRow::BACKFILLABLE, $row->rowStatus);
        $this->assertContains('leftover_pib_on_non_entrepreneur', $row->reasonCodes);
        $this->assertNotContains('pib_missing', $row->d10MissingCodes);
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $row->fieldStatuses['pib']);
    }

    public function test_dspd_maps_to_foreign_branch_and_is_not_backfillable(): void
    {
        $row = $this->classify($this->subject([
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
            'company_name' => 'Foreign Co / Branch',
            'pib' => '00000007',
            'jmb' => null,
            'residential_status' => null,
        ]));

        $this->assertSame(PlatformIdentity::SUBJECT_FOREIGN_BRANCH, $row->subjectType);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['foreign_company_name']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['branch_name_in_montenegro']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['crps']);
        $this->assertSame(IdentityCensusFieldStatus::SOURCE_UNAVAILABLE, $row->fieldStatuses['representative']);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $row->rowStatus);
        $this->assertFalse($row->fullyBackfillable);
        $this->assertContains('dspd_names_unresolved', $row->reasonCodes);
    }

    public function test_repeated_classify_on_unchanged_subject_is_identical(): void
    {
        $user = $this->subject();
        $first = $this->classify($user)->toArray();
        $second = $this->classify($user)->toArray();

        $this->assertSame($first, $second);
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

    private function classify(User $user): IdentityCensusRow
    {
        return (new IdentityCensusService)->classify($user);
    }
}
