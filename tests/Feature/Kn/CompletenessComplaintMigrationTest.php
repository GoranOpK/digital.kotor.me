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

    private const MIGRATION_NAME = '2026_09_14_160000_create_completeness_and_complaint_tables';

    public function test_up_creates_complaints_with_required_datetime_deadline(): void
    {
        $this->assertTrue(Schema::hasTable('application_complaints'));
        $this->assertTrue(Schema::hasTable('application_completeness_assessments'));
        $this->assertDecisionDueAtIsRequiredDatetime();
    }

    public function test_up_resumes_when_assessments_exist_without_complaints(): void
    {
        $this->assertTrue(Schema::hasTable('application_completeness_assessments'));
        $this->assertTrue(Schema::hasTable('application_complaints'));
        $this->assertTrue(Schema::hasColumn('application_completeness_assessments', 'mail_sent_at'));

        Schema::dropIfExists('application_complaints');
        DB::table('migrations')->where('migration', self::MIGRATION_NAME)->delete();

        $this->assertTrue(Schema::hasTable('application_completeness_assessments'));
        $this->assertFalse(Schema::hasTable('application_complaints'));
        $this->assertFalse(
            DB::table('migrations')->where('migration', self::MIGRATION_NAME)->exists()
        );

        $assessmentsBefore = $this->tableIdentity('application_completeness_assessments');

        $this->migration()->up();

        $this->assertSame($assessmentsBefore, $this->tableIdentity('application_completeness_assessments'));
        $this->assertTrue(Schema::hasColumn('application_completeness_assessments', 'mail_sent_at'));
        $this->assertTrue(Schema::hasTable('application_complaints'));
        $this->assertDecisionDueAtIsRequiredDatetime();

        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('application_complaints'));
        $this->assertFalse(Schema::hasTable('application_completeness_assessments'));
        $this->assertTrue(Schema::hasTable('applications'));
        $this->assertTrue(Schema::hasTable('competitions'));
        $this->assertTrue(Schema::hasTable('users'));

        $this->migration()->up();

        $this->assertTrue(Schema::hasTable('application_completeness_assessments'));
        $this->assertTrue(Schema::hasTable('application_complaints'));
        $this->assertDecisionDueAtIsRequiredDatetime();
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
        $this->assertDecisionDueAtIsRequiredDatetime();
        $this->assertTrue(Schema::hasColumn('application_complaints', 'submitted_at'));
        $this->assertTrue(Schema::hasColumn('application_complaints', 'application_id'));
        $this->assertTrue(Schema::hasTable('applications'));
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function assertDecisionDueAtIsRequiredDatetime(): void
    {
        $this->assertTrue(Schema::hasColumn('application_complaints', 'decision_due_at'));
        $column = $this->column('application_complaints', 'decision_due_at');
        $this->assertSame('NO', $column->IS_NULLABLE);
        $this->assertSame('datetime', strtolower($column->DATA_TYPE));
    }

    /**
     * @return array{create_time: string, columns: list<array{0: string, 1: string, 2: string, 3: int}>, indexes: list<array{0: string, 1: list<string>}>}
     */
    private function tableIdentity(string $table): array
    {
        $meta = DB::selectOne(
            'SELECT CREATE_TIME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
        $this->assertNotNull($meta, $table);

        $columns = collect(DB::select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, ORDINAL_POSITION
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$table]
        ))->map(fn ($column) => [
            $column->COLUMN_NAME,
            $column->COLUMN_TYPE,
            $column->IS_NULLABLE,
            (int) $column->ORDINAL_POSITION,
        ])->all();

        $indexes = collect(Schema::getIndexes($table))
            ->map(fn (array $index) => [$index['name'], $index['columns']])
            ->sortBy(fn (array $index) => $index[0])
            ->values()
            ->all();

        return [
            'create_time' => (string) $meta->CREATE_TIME,
            'columns' => $columns,
            'indexes' => $indexes,
        ];
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
