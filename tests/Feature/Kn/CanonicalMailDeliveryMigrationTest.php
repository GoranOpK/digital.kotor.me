<?php

namespace Tests\Feature\Kn;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CanonicalMailDeliveryMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_13_100000_add_canonical_mail_delivery_state.php';

    private const TABLES = ['application_completeness_assessments', 'application_complaints'];

    private const LEGAL_COLUMNS = [
        'application_completeness_assessments' => ['notice_sent_at', 'recorded_at', 'outcome', 'notes'],
        'application_complaints' => ['submitted_at', 'decision_due_at', 'decided_at', 'decision'],
    ];

    public function test_delivery_columns_are_nullable_without_default(): void
    {
        foreach (self::TABLES as $table) {
            foreach (['mail_sent_at', 'mail_failed_at'] as $column) {
                $definition = DB::selectOne(
                    'SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                );
                $this->assertSame('YES', $definition?->IS_NULLABLE, "$table.$column");
                $this->assertNull($definition->COLUMN_DEFAULT, "$table.$column");
            }
        }
    }

    public function test_down_drops_only_delivery_columns_and_up_restores_them(): void
    {
        $migration = require base_path(self::MIGRATION);

        $migration->down();
        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'mail_sent_at'));
            $this->assertFalse(Schema::hasColumn($table, 'mail_failed_at'));
            $this->assertTrue(Schema::hasColumns($table, self::LEGAL_COLUMNS[$table]));
        }

        $migration->up();
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasColumns($table, ['mail_sent_at', 'mail_failed_at', ...self::LEGAL_COLUMNS[$table]]));
        }
    }
}
