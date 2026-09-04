<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CanonicalUserTypeEnumMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_08_20_210000_expand_users_user_type_canonical_v1.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_safe_expansion_keeps_legacy_and_adds_canonical_values_without_rewriting_rows(): void
    {
        $columnType = $this->userTypeColumnType();

        foreach (UserType::mysqlEnumValues() as $value) {
            $this->assertNotFalse(
                stripos($columnType, "'".$value."'"),
                "ENUM must contain {$value}"
            );
        }

        DB::table('users')->insert([
            'name' => 'Legacy Association',
            'email' => 'legacy.association@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'user_type' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            UserType::LEGACY_ASSOCIATION_BUNDLE,
            DB::table('users')->where('email', 'legacy.association@example.com')->value('user_type')
        );
    }

    public function test_down_refuses_destructive_rollback_when_new_canonical_rows_exist(): void
    {
        DB::table('users')->insert([
            'name' => 'NVO Test',
            'email' => 'nvo.rollback@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'user_type' => UserType::NGO_ASSOCIATION,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot roll back users.user_type enum');

        $this->migration()->down();
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function userTypeColumnType(): string
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['users', 'user_type']
        );

        $this->assertNotNull($column);

        return (string) $column->COLUMN_TYPE;
    }
}
