<?php

namespace Tests\Feature;

use App\Models\Competition;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OmladinskoCallNumberAnnualBudgetMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_09_15_120000_add_call_number_and_annual_budget_to_competitions_table.php';

    private const UNIQUE_INDEX = 'competitions_type_year_call_number_unique';

    private const CHECK_CONSTRAINT = 'competitions_call_number_chk';

    public function test_call_number_and_annual_budget_exist_and_are_nullable_decimal_and_tinyint(): void
    {
        $this->assertTrue(Schema::hasColumn('competitions', 'call_number'));
        $this->assertTrue(Schema::hasColumn('competitions', 'annual_budget'));

        $callNumber = $this->column('call_number');
        $annualBudget = $this->column('annual_budget');

        $this->assertSame('YES', $callNumber->IS_NULLABLE);
        $this->assertSame('YES', $annualBudget->IS_NULLABLE);
        $this->assertSame('tinyint', strtolower((string) $callNumber->DATA_TYPE));
        $this->assertSame('decimal', strtolower((string) $annualBudget->DATA_TYPE));
        $this->assertSame(15, (int) $annualBudget->NUMERIC_PRECISION);
        $this->assertSame(2, (int) $annualBudget->NUMERIC_SCALE);
        $this->assertStringContainsString('decimal(15,2)', strtolower((string) $annualBudget->COLUMN_TYPE));
        $this->assertStringContainsString('unsigned', strtolower((string) $callNumber->COLUMN_TYPE));
    }

    public function test_call_number_accepts_null_one_and_two(): void
    {
        foreach ([null, 1, 2] as $index => $callNumber) {
            $id = DB::table('competitions')->insertGetId($this->competitionRow([
                'title' => 'Dozvoljen call '.$index,
                'type' => 'omladinsko',
                'year' => 2026 + $index,
                'call_number' => $callNumber,
                'annual_budget' => $callNumber === null ? null : '100000.00',
            ]));

            $stored = DB::table('competitions')->where('id', $id)->value('call_number');
            $this->assertSame($callNumber, $stored === null ? null : (int) $stored);
        }
    }

    public function test_call_number_rejects_zero_three_and_negative_values(): void
    {
        foreach ([0, 3, -1] as $invalid) {
            try {
                DB::table('competitions')->insert($this->competitionRow([
                    'title' => 'Nedozvoljen call '.$invalid,
                    'type' => 'omladinsko',
                    'year' => 2030,
                    'call_number' => $invalid,
                    'annual_budget' => '100000.00',
                ]));
                $this->fail('Expected call_number '.$invalid.' to be rejected.');
            } catch (QueryException $exception) {
                $this->assertTrue(
                    str_contains($exception->getMessage(), self::CHECK_CONSTRAINT)
                    || str_contains($exception->getMessage(), '1264')
                    || str_contains($exception->getMessage(), '22003')
                    || str_contains($exception->getMessage(), 'Out of range')
                    || str_contains($exception->getMessage(), 'CHECK'),
                    $exception->getMessage()
                );
            }
        }
    }

    public function test_unique_prevents_two_omladinsko_first_or_second_calls_in_the_same_year(): void
    {
        DB::table('competitions')->insert($this->competitionRow([
            'title' => 'Prvi A',
            'type' => 'omladinsko',
            'year' => 2026,
            'call_number' => 1,
            'annual_budget' => '100000.00',
        ]));

        try {
            DB::table('competitions')->insert($this->competitionRow([
                'title' => 'Prvi B',
                'type' => 'omladinsko',
                'year' => 2026,
                'call_number' => 1,
                'annual_budget' => '100000.00',
            ]));
            $this->fail('Expected unique to reject a second first call.');
        } catch (QueryException $exception) {
            $this->assertTrue(
                str_contains($exception->getMessage(), self::UNIQUE_INDEX)
                || str_contains($exception->getMessage(), 'Duplicate'),
                $exception->getMessage()
            );
        }

        DB::table('competitions')->insert($this->competitionRow([
            'title' => 'Drugi A',
            'type' => 'omladinsko',
            'year' => 2026,
            'call_number' => 2,
            'annual_budget' => '100000.00',
        ]));

        try {
            DB::table('competitions')->insert($this->competitionRow([
                'title' => 'Drugi B',
                'type' => 'omladinsko',
                'year' => 2026,
                'call_number' => 2,
                'annual_budget' => '100000.00',
            ]));
            $this->fail('Expected unique to reject a second second call.');
        } catch (QueryException $exception) {
            $this->assertTrue(
                str_contains($exception->getMessage(), self::UNIQUE_INDEX)
                || str_contains($exception->getMessage(), 'Duplicate'),
                $exception->getMessage()
            );
        }
    }

    public function test_multiple_zensko_null_call_numbers_in_the_same_year_are_allowed(): void
    {
        DB::table('competitions')->insert($this->competitionRow([
            'title' => 'Zensko A',
            'type' => 'zensko',
            'year' => 2026,
            'call_number' => null,
            'annual_budget' => null,
            'budget' => '5000.00',
            'competition_number' => 'Z-1',
        ]));
        DB::table('competitions')->insert($this->competitionRow([
            'title' => 'Zensko B',
            'type' => 'zensko',
            'year' => 2026,
            'call_number' => null,
            'annual_budget' => null,
            'budget' => '7000.00',
            'competition_number' => 'Z-2',
        ]));

        $this->assertSame(
            2,
            DB::table('competitions')->where('type', 'zensko')->where('year', 2026)->count()
        );
    }

    public function test_migration_does_not_rewrite_existing_zensko_values_and_down_drops_only_new_objects(): void
    {
        $id = DB::table('competitions')->insertGetId($this->competitionRow([
            'title' => 'Zensko sacuvan',
            'type' => 'zensko',
            'year' => 2026,
            'call_number' => null,
            'annual_budget' => null,
            'budget' => '12345.67',
            'competition_number' => 'KEEP-Z',
            'status' => 'published',
        ]));

        $before = DB::table('competitions')->where('id', $id)->first();
        $this->assertSame('12345.67', $before->budget);
        $this->assertSame('KEEP-Z', $before->competition_number);
        $this->assertNull($before->call_number);
        $this->assertNull($before->annual_budget);

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('competitions', 'call_number'));
        $this->assertFalse(Schema::hasColumn('competitions', 'annual_budget'));
        $this->assertNull($this->indexExists(self::UNIQUE_INDEX));
        $this->assertNull($this->checkExists(self::CHECK_CONSTRAINT));

        $afterDown = DB::table('competitions')->where('id', $id)->first();
        $this->assertSame('12345.67', $afterDown->budget);
        $this->assertSame('KEEP-Z', $afterDown->competition_number);
        $this->assertSame('zensko', $afterDown->type);
        $this->assertSame(2026, (int) $afterDown->year);

        $this->migration()->up();

        $afterUp = DB::table('competitions')->where('id', $id)->first();
        $this->assertSame('12345.67', $afterUp->budget);
        $this->assertSame('KEEP-Z', $afterUp->competition_number);
        $this->assertNull($afterUp->call_number);
        $this->assertNull($afterUp->annual_budget);
        $this->assertNotNull($this->indexExists(self::UNIQUE_INDEX));
        $this->assertNotNull($this->checkExists(self::CHECK_CONSTRAINT));
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function column(string $name): object
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['competitions', $name]
        );

        $this->assertNotNull($row);

        return $row;
    }

    private function indexExists(string $name): ?object
    {
        return DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1',
            ['competitions', $name]
        );
    }

    private function checkExists(string $name): ?object
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['competitions', $name, 'CHECK']
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function competitionRow(array $overrides): array
    {
        return array_merge([
            'title' => 'Konkurs',
            'description' => 'Opis',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-21',
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => 2026,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }
}
