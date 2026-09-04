<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class RemoveExNonResidentResidentialStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_08_18_140000_remove_ex_non_resident_from_users_residential_status.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_clean_database_has_only_resident_and_non_resident_enum_values(): void
    {
        $columnType = $this->residentialStatusColumnType();

        $this->assertNotFalse(stripos($columnType, "'resident'"));
        $this->assertNotFalse(stripos($columnType, "'non-resident'"));
        $this->assertFalse(stripos($columnType, 'ex-non-resident'));
    }

    public function test_migration_refuses_to_narrow_enum_when_legacy_rows_exist(): void
    {
        $this->widenResidentialStatusEnum();

        DB::table('users')->insert([
            'name' => 'Legacy Ex Non Resident',
            'email' => 'legacy.ex.non.resident@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'residential_status' => 'ex-non-resident',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ex-non-resident');

        $this->migration()->up();
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function widenResidentialStatusEnum(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `residential_status` ENUM('resident','non-resident','ex-non-resident') NULL");
    }

    private function residentialStatusColumnType(): string
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['users', 'residential_status']
        );

        $this->assertNotNull($column);

        return (string) $column->COLUMN_TYPE;
    }
}
