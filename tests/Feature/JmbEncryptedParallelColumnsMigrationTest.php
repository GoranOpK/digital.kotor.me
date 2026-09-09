<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JmbEncryptedParallelColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_09_09_190000_add_jmb_encrypted_parallel_columns.php';

    /**
     * @var list<array{table: string, encrypted: string, plaintext: string}>
     */
    private const COLUMNS = [
        ['table' => 'users', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'physical_person_identities', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'legal_entity_authorized_persons', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'foreign_branch_representatives', 'encrypted' => 'jmb_encrypted', 'plaintext' => 'jmb'],
        ['table' => 'applications', 'encrypted' => 'physical_person_jmbg_encrypted', 'plaintext' => 'physical_person_jmbg'],
        ['table' => 'applications', 'encrypted' => 'applicant_jmbg_encrypted', 'plaintext' => 'applicant_jmbg'],
        ['table' => 'business_plans', 'encrypted' => 'applicant_jmbg_encrypted', 'plaintext' => 'applicant_jmbg'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_phase_a_adds_nullable_encrypted_columns_and_keeps_plaintext(): void
    {
        foreach (self::COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['encrypted']),
                $column['table'].'.'.$column['encrypted'].' must exist after migration'
            );
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['plaintext']),
                $column['table'].'.'.$column['plaintext'].' must remain'
            );

            $encrypted = $this->column($column['table'], $column['encrypted']);
            $this->assertSame('YES', $encrypted->IS_NULLABLE, $column['table'].'.'.$column['encrypted'].' must be nullable');
            $this->assertSame('text', strtolower((string) $encrypted->DATA_TYPE));
            $this->assertSame('', (string) $encrypted->COLUMN_KEY);

            $plaintext = $this->column($column['table'], $column['plaintext']);
            $this->assertSame('YES', $plaintext->IS_NULLABLE);
        }
    }

    public function test_phase_a_rollback_drops_only_encrypted_columns_and_preserves_plaintext_values(): void
    {
        $email = 'jmb-phase-a-rollback@example.test';
        $plaintextJmb = '0202990123456';

        DB::table('users')->insert([
            'name' => 'Phase A Rollback',
            'email' => $email,
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'jmb' => $plaintextJmb,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('users')->where('email', $email)->value('jmb_encrypted'));

        $this->migration()->down();

        foreach (self::COLUMNS as $column) {
            $this->assertFalse(
                Schema::hasColumn($column['table'], $column['encrypted']),
                $column['table'].'.'.$column['encrypted'].' must be removed on rollback'
            );
            $this->assertTrue(
                Schema::hasColumn($column['table'], $column['plaintext']),
                $column['table'].'.'.$column['plaintext'].' must survive rollback'
            );
        }

        $this->assertSame(
            $plaintextJmb,
            DB::table('users')->where('email', $email)->value('jmb')
        );
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function column(string $table, string $column): object
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE, IS_NULLABLE, COLUMN_KEY
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        $this->assertNotNull($row, $table.'.'.$column.' metadata missing');

        return $row;
    }
}
