<?php

namespace Tests\Feature\Identity;

use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase1ExpandSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const NEW_TABLES = [
        'platform_identities',
        'physical_person_identities',
        'legal_entity_identities',
        'legal_entity_authorized_persons',
        'foreign_branch_identities',
        'foreign_branch_representatives',
    ];

    public function test_phase_1_tables_exist(): void
    {
        foreach (self::NEW_TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table), $table.' must exist');
        }
    }

    public function test_legacy_users_identity_columns_and_uniques_remain(): void
    {
        foreach ([
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
            'email',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), 'users.'.$column.' must remain');
        }

        $uniqueColumns = $this->uniqueColumns('users');

        $this->assertContains('email', $uniqueColumns);
        $this->assertContains('jmb', $uniqueColumns);
        $this->assertContains('pib', $uniqueColumns);
        $this->assertContains('passport_number', $uniqueColumns);
    }

    public function test_new_identifier_columns_are_not_unique(): void
    {
        $this->assertNotContains('jmb', $this->uniqueColumns('physical_person_identities'));
        $this->assertNotContains('pib', $this->uniqueColumns('physical_person_identities'));
        $this->assertNotContains('passport_number', $this->uniqueColumns('physical_person_identities'));
        $this->assertNotContains('crps_number', $this->uniqueColumns('physical_person_identities'));
        $this->assertNotContains('pib', $this->uniqueColumns('legal_entity_identities'));
        $this->assertNotContains('crps_number', $this->uniqueColumns('legal_entity_identities'));
        $this->assertNotContains('jmb', $this->uniqueColumns('legal_entity_authorized_persons'));
        $this->assertNotContains('passport_number', $this->uniqueColumns('legal_entity_authorized_persons'));
        $this->assertNotContains('pib', $this->uniqueColumns('foreign_branch_identities'));
        $this->assertNotContains('crps_number', $this->uniqueColumns('foreign_branch_identities'));
        $this->assertNotContains('jmb', $this->uniqueColumns('foreign_branch_representatives'));
        $this->assertNotContains('passport_number', $this->uniqueColumns('foreign_branch_representatives'));
    }

    public function test_physical_person_has_no_passport_issuing_country_column(): void
    {
        $this->assertFalse(Schema::hasColumn('physical_person_identities', 'passport_issuing_country_code'));
        $this->assertTrue(Schema::hasColumn('physical_person_identities', 'residence_country_code'));
        $this->assertTrue(Schema::hasColumn('legal_entity_authorized_persons', 'passport_issuing_country_code'));
        $this->assertTrue(Schema::hasColumn('foreign_branch_representatives', 'passport_issuing_country_code'));
    }

    public function test_users_to_platform_identity_delete_is_restrict(): void
    {
        $this->assertContains($this->deleteRule('platform_identities', 'user_id'), ['RESTRICT', 'NO ACTION']);
    }

    public function test_internal_aggregate_deletes_are_cascade(): void
    {
        $this->assertSame('CASCADE', $this->deleteRule('physical_person_identities', 'platform_identity_id'));
        $this->assertSame('CASCADE', $this->deleteRule('legal_entity_identities', 'platform_identity_id'));
        $this->assertSame('CASCADE', $this->deleteRule('foreign_branch_identities', 'platform_identity_id'));
        $this->assertSame('CASCADE', $this->deleteRule('legal_entity_authorized_persons', 'legal_entity_identity_id'));
        $this->assertSame('CASCADE', $this->deleteRule('foreign_branch_representatives', 'foreign_branch_identity_id'));
    }

    public function test_second_platform_identity_for_same_user_fails_unique(): void
    {
        $user = User::factory()->create();
        $this->makeIdentity($user, PlatformIdentity::SUBJECT_PHYSICAL_PERSON);

        $this->expectException(QueryException::class);
        $this->makeIdentity($user, PlatformIdentity::SUBJECT_LEGAL_ENTITY);
    }

    public function test_second_physical_person_row_for_same_identity_fails_unique(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_PHYSICAL_PERSON);
        $this->makePhysicalPerson($identity);

        $this->expectException(QueryException::class);
        $this->makePhysicalPerson($identity);
    }

    public function test_second_legal_entity_row_for_same_identity_fails_unique(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_LEGAL_ENTITY);
        $this->makeLegalEntity($identity);

        $this->expectException(QueryException::class);
        $this->makeLegalEntity($identity);
    }

    public function test_second_foreign_branch_row_for_same_identity_fails_unique(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_FOREIGN_BRANCH);
        $this->makeForeignBranch($identity);

        $this->expectException(QueryException::class);
        $this->makeForeignBranch($identity);
    }

    public function test_phase_1_schema_does_not_enforce_cross_branch_exclusivity(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_PHYSICAL_PERSON);
        $this->makePhysicalPerson($identity);
        $this->makeLegalEntity($identity);

        $this->assertDatabaseHas('physical_person_identities', [
            'platform_identity_id' => $identity->id,
        ]);
        $this->assertDatabaseHas('legal_entity_identities', [
            'platform_identity_id' => $identity->id,
        ]);
    }

    public function test_second_authorized_person_fails_unique_and_zero_rows_are_allowed(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_LEGAL_ENTITY);
        $legalEntity = $this->makeLegalEntity($identity);

        $this->assertDatabaseCount('legal_entity_authorized_persons', 0);

        $this->makeAuthorizedPerson($legalEntity);
        $this->assertDatabaseCount('legal_entity_authorized_persons', 1);

        $this->expectException(QueryException::class);
        $this->makeAuthorizedPerson($legalEntity);
    }

    public function test_second_representative_fails_unique_and_zero_rows_are_allowed(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_FOREIGN_BRANCH);
        $branch = $this->makeForeignBranch($identity);

        $this->assertDatabaseCount('foreign_branch_representatives', 0);

        $this->makeRepresentative($branch);
        $this->assertDatabaseCount('foreign_branch_representatives', 1);

        $this->expectException(QueryException::class);
        $this->makeRepresentative($branch);
    }

    public function test_deleting_user_is_restricted_while_platform_identity_exists(): void
    {
        $user = User::factory()->create();
        $identity = $this->makeIdentity($user, PlatformIdentity::SUBJECT_PHYSICAL_PERSON);

        try {
            $user->delete();
            $this->fail('Deleting a user with a platform identity must be restricted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
            $this->assertDatabaseHas('platform_identities', ['id' => $identity->id]);
        }
    }

    public function test_deleting_platform_identity_cascades_internal_aggregate(): void
    {
        $identity = $this->makeIdentity(User::factory()->create(), PlatformIdentity::SUBJECT_LEGAL_ENTITY);
        $legalEntity = $this->makeLegalEntity($identity);
        $this->makeAuthorizedPerson($legalEntity);

        $identity->delete();

        $this->assertDatabaseMissing('legal_entity_identities', ['id' => $legalEntity->id]);
        $this->assertDatabaseCount('legal_entity_authorized_persons', 0);
        $this->assertDatabaseHas('users', ['id' => $identity->user_id]);
    }

    public function test_user_identity_relation_is_has_one(): void
    {
        $user = User::factory()->create();
        $identity = $this->makeIdentity($user, PlatformIdentity::SUBJECT_PHYSICAL_PERSON);

        $this->assertTrue($user->identity()->is($identity));
        $this->assertTrue($identity->user()->is($user));
    }

    /**
     * @return list<string>
     */
    private function uniqueColumns(string $table): array
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Non_unique = 0');
        $columns = [];

        foreach ($indexes as $index) {
            if (($index->Key_name ?? '') === 'PRIMARY') {
                continue;
            }
            $columns[] = (string) $index->Column_name;
        }

        return array_values(array_unique($columns));
    }

    private function deleteRule(string $table, string $column): string
    {
        $row = DB::selectOne(
            'SELECT rc.DELETE_RULE
             FROM information_schema.KEY_COLUMN_USAGE kcu
             JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
               ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
             WHERE kcu.TABLE_SCHEMA = DATABASE()
               AND kcu.TABLE_NAME = ?
               AND kcu.COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );

        $this->assertNotNull($row, $table.'.'.$column.' foreign key must exist');

        return (string) $row->DELETE_RULE;
    }

    private function makeIdentity(User $user, string $subjectType): PlatformIdentity
    {
        return PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => $subjectType,
            'mobile_phone' => null,
        ]);
    }

    private function makePhysicalPerson(PlatformIdentity $identity): PhysicalPersonIdentity
    {
        return PhysicalPersonIdentity::query()->create([
            'platform_identity_id' => $identity->id,
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'is_entrepreneur' => false,
            'street_and_number' => 'Njegoševa 12',
            'city' => 'Podgorica',
        ]);
    }

    private function makeLegalEntity(PlatformIdentity $identity): LegalEntityIdentity
    {
        return LegalEntityIdentity::query()->create([
            'platform_identity_id' => $identity->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Primjer DOO',
            'street_and_number' => 'Njegoševa 12',
            'city' => 'Podgorica',
        ]);
    }

    private function makeForeignBranch(PlatformIdentity $identity): ForeignBranchIdentity
    {
        return ForeignBranchIdentity::query()->create([
            'platform_identity_id' => $identity->id,
            'foreign_company_name' => 'Foreign Co',
            'branch_name_in_montenegro' => 'Ogranak CG',
            'street_and_number' => 'Slobode 1',
            'city' => 'Podgorica',
        ]);
    }

    private function makeAuthorizedPerson(LegalEntityIdentity $legalEntity): LegalEntityAuthorizedPerson
    {
        return LegalEntityAuthorizedPerson::query()->create([
            'legal_entity_identity_id' => $legalEntity->id,
            'first_name' => 'Marko',
            'last_name' => 'Marković',
            'id_document_type' => LegalEntityAuthorizedPerson::DOCUMENT_JMB,
            'jmb' => '0000000000000',
        ]);
    }

    private function makeRepresentative(ForeignBranchIdentity $branch): ForeignBranchRepresentative
    {
        return ForeignBranchRepresentative::query()->create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => 'Jelena',
            'last_name' => 'Jovanović',
            'id_document_type' => ForeignBranchRepresentative::DOCUMENT_PASSPORT,
            'passport_number' => 'AB123456',
            'passport_issuing_country_code' => 'IT',
        ]);
    }
}
