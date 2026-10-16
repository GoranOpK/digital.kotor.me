<?php

namespace Tests\Feature\Kn;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompletenessComplaintMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_14_160000_create_completeness_and_complaint_tables.php';

    public function test_up_creates_complaints_with_required_datetime_deadline(): void
    {
        $this->assertTrue(Schema::hasTable('application_complaints'));
        $this->assertTrue(Schema::hasTable('application_completeness_assessments'));
        $this->assertTrue(Schema::hasColumn('application_complaints', 'decision_due_at'));

        $column = $this->column('application_complaints', 'decision_due_at');
        $this->assertSame('NO', $column->IS_NULLABLE);
        $this->assertSame('datetime', strtolower($column->DATA_TYPE));
    }

    public function test_down_drops_only_completeness_complaint_tables_and_up_reruns(): void
    {
        $this->assertTrue(Schema::hasTable('applications'));
        $this->assertTrue(Schema::hasTable('competitions'));

        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('application_complaints'));
        $this->assertFalse(Schema::hasTable('application_completeness_assessments'));
        $this->assertTrue(Schema::hasTable('applications'));
        $this->assertTrue(Schema::hasTable('competitions'));
        $this->assertTrue(Schema::hasTable('users'));

        $this->migration()->up();

        $this->assertTrue(Schema::hasTable('application_complaints'));
        $this->assertTrue(Schema::hasTable('application_completeness_assessments'));
        $this->assertTrue(Schema::hasColumn('application_complaints', 'decision_due_at'));
        $column = $this->column('application_complaints', 'decision_due_at');
        $this->assertSame('NO', $column->IS_NULLABLE);
        $this->assertSame('datetime', strtolower($column->DATA_TYPE));
        $this->assertTrue(Schema::hasColumn('application_complaints', 'submitted_at'));
        $this->assertTrue(Schema::hasColumn('application_complaints', 'application_id'));
        $this->assertTrue(Schema::hasTable('applications'));
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function column(string $table, string $column): object
    {
        $definition = DB::selectOne(
            'SELECT IS_NULLABLE, DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        $this->assertNotNull($definition, "$table.$column");

        return $definition;
    }
}
