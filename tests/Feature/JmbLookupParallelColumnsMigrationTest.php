<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JmbLookupParallelColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_09_10_210000_add_jmb_lookup_parallel_columns.php';

    private const USERS_UNIQUE = 'users_jmb_lookup_unique';

    private const PHYSICAL_PERSON_INDEX = 'physical_person_identities_jmb_lookup_index';

    /**
     * @var list<array{table: string, encrypted: string, plaintext: string}>
     */
    private const ENCRYPTED_COLUMNS = [
        ['table' => 'users', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'physical_person_identities', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'legal_entity_authorized_persons', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'foreign_branch_representatives', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'applications', 'encrypted' => 'physical_person_jmbg_encrypted', 'plaintext' => 'physical_person_jmbg'],
        ['table' => 'applications', 'encrypted' => 'applicant_jmbg_encrypted', 'plaintext' => 'applicant_jmbg'],
        ['table' => 'business_plans', 'encrypted' => 'applicant_jmbg_encrypted', 'plaintext' => 'applicant_jmbg'],
    ];

    /**
     * @var list<string>
     */
    private const TABLES_WITHOUT_LOOKUP = [
        'legal_entity_authorized_persons',
        'foreign_branch_representatives',
        'applications',
        'business_plans',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_users_jmb_lookup_is_nullable_char64_and_unique(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'jmb_lookup'));

        $column = $this->column('users', 'jmb_lookup');
        $this->assertSame('YES', $column->IS_NULLABLE);
        $this->assertSame('char', strtolower((string) $column->DATA_TYPE));
        $this->assertSame(64, (int) $column->CHARACTER_MAXIMUM_LENGTH);
        $this->assertSame('UNI', (string) $column->COLUMN_KEY);

        $this->assertTrue($this->indexIsUnique('users', self::USERS_UNIQUE, 'jmb_lookup'));
        $this->assertContains('jmb_lookup', $this->uniqueColumns('users'));
    }

    public function test_physical_person_jmb_lookup_is_nullable_char64_indexed_and_not_unique(): void
    {
        $this->assertTrue(Schema::hasColumn('physical_person_identities', 'jmb_lookup'));

        $column = $this->column('physical_person_identities', 'jmb_lookup');
        $this->assertSame('YES', $column->IS_NULLABLE);
        $this->assertSame('char', strtolower((string) $column->DATA_TYPE));
        $this->assertSame(64, (int) $column->CHARACTER_MAXIMUM_LENGTH);
        $this->assertSame('MUL', (string) $column->COLUMN_KEY);

        $this->assertTrue($this->indexExists('physical_person_identities', self::PHYSICAL_PERSON_INDEX, 'jmb_lookup'));
        $this->assertFalse($this->indexIsUnique('physical_person_identities', self::PHYSICAL_PERSON_INDEX, 'jmb_lookup'));
        $this->assertNotContains('jmb_lookup', $this->uniqueColumns('physical_person_identities'));
    }

    public function test_existing_users_jmb_unique_and_encrypted_columns_remain(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'jmb'));
        $this->assertContains('jmb', $this->uniqueColumns('users'));

        foreach (self::ENCRYPTED_COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['encrypted']),
                $column['table'].'.'.$column['encrypted'].' must remain'
            );
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['plaintext']),
                $column['table'].'.'.$column['plaintext'].' must remain'
            );
        }

        foreach (self::TABLES_WITHOUT_LOOKUP as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'jmb_lookup'),
                $table.' must not gain jmb_lookup'
            );
        }
    }

    public function test_rollback_drops_only_lookup_columns_and_preserves_jmb_and_encrypted(): void
    {
        $email = 'jmb-lookup-rollback@example.test';
        $plaintextJmb = '0202990123456';

        DB::table('users')->insert([
            'name' => 'Lookup Rollback',
            'email' => $email,
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'jmb' => $plaintextJmb,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('users')->where('email', $email)->value('jmb_lookup'));

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('users', 'jmb_lookup'));
        $this->assertFalse(Schema::hasColumn('physical_person_identities', 'jmb_lookup'));
        $this->assertFalse($this->indexExists('users', self::USERS_UNIQUE, 'jmb_lookup'));
        $this->assertFalse($this->indexExists('physical_person_identities', self::PHYSICAL_PERSON_INDEX, 'jmb_lookup'));

        $this->assertTrue(Schema::hasColumn('users', 'jmb'));
        $this->assertContains('jmb', $this->uniqueColumns('users'));
        $this->assertSame($plaintextJmb, DB::table('users')->where('email', $email)->value('jmb'));

        foreach (self::ENCRYPTED_COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['encrypted']),
                $column['table'].'.'.$column['encrypted'].' must survive lookup rollback'
            );
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['plaintext']),
                $column['table'].'.'.$column['plaintext'].' must survive lookup rollback'
            );
        }
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function column(string $table, string $column): object
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE, IS_NULLABLE, COLUMN_KEY, CHARACTER_MAXIMUM_LENGTH
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        $this->assertNotNull($row, $table.'.'.$column.' metadata missing');

        return $row;
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

    private function indexExists(string $table, string $indexName, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $indexes = DB::select('SHOW INDEX FROM `'.$table.'`');
        foreach ($indexes as $index) {
            if ((string) $index->Key_name === $indexName && (string) $index->Column_name === $column) {
                return true;
            }
        }

        return false;
    }

    private function indexIsUnique(string $table, string $indexName, string $column): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'`');
        foreach ($indexes as $index) {
            if ((string) $index->Key_name === $indexName && (string) $index->Column_name === $column) {
                return (int) $index->Non_unique === 0;
            }
        }

        return false;
    }
}
