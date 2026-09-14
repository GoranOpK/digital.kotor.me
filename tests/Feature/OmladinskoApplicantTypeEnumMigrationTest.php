<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Support\KnApplicationClassification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class OmladinskoApplicantTypeEnumMigrationTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_09_10_120000_add_omladinsko_applicant_types_and_company_legal_form.php';

    private const CANONICAL_ENUM = "enum('preduzetnica','doo','fizicko_lice','ostalo','preduzetnik','privredno_drustvo')";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_fresh_mysql_history_converts_applicant_type_to_canonical_enum(): void
    {
        $this->assertCanonicalApplicantTypeEnum($this->applicantTypeColumnType());
        $companyLegalFormType = strtolower($this->companyLegalFormColumnType());
        $this->assertTrue(str_starts_with($companyLegalFormType, 'enum('));
        preg_match_all("/'([^']+)'/", $companyLegalFormType, $legalFormMatches);
        $this->assertSame(['doo', 'ad', 'od', 'kd'], $legalFormMatches[1]);
        $this->assertTrue(
            (bool) DB::selectOne(
                'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['applications', 'company_legal_form']
            )->IS_NULLABLE
        );
    }

    public function test_all_six_enum_values_and_company_legal_forms_can_be_stored(): void
    {
        $competition = $this->openCompetition('zensko');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(401)]);

        foreach (KnApplicationClassification::mysqlEnumValues() as $type) {
            Application::create([
                'competition_id' => $competition->id,
                'user_id' => $user->id,
                'business_plan_name' => 'Enum '.$type,
                'applicant_type' => $type,
                'company_legal_form' => $type === 'privredno_drustvo' ? 'ad' : null,
                'business_stage' => 'započinjanje',
                'business_area' => 'Usluge',
                'status' => 'draft',
            ]);
            $this->assertSame(
                $type,
                Application::query()->where('business_plan_name', 'Enum '.$type)->value('applicant_type')
            );
        }

        foreach (['doo', 'ad', 'od', 'kd'] as $form) {
            $row = Application::create([
                'competition_id' => $competition->id,
                'user_id' => $user->id,
                'business_plan_name' => 'Form '.$form,
                'applicant_type' => 'privredno_drustvo',
                'company_legal_form' => $form,
                'business_stage' => 'započinjanje',
                'status' => 'draft',
            ]);
            $this->assertSame($form, $row->fresh()->company_legal_form);
        }
    }

    public function test_existing_zensko_ostalo_row_is_not_rewritten_by_enum_expansion(): void
    {
        $competition = $this->openCompetition('zensko');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(402)]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Zensko ostalo',
            'applicant_type' => 'ostalo',
            'company_legal_form' => null,
            'business_stage' => 'razvoj',
            'status' => 'draft',
        ]);

        $application->refresh();
        $this->assertSame('ostalo', $application->applicant_type);
        $this->assertNull($application->company_legal_form);
    }

    public function test_varchar_applicant_type_is_converted_to_canonical_enum_without_rewriting_rows(): void
    {
        $competition = $this->openCompetition('zensko');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(404)]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Varchar ostalo',
            'applicant_type' => 'ostalo',
            'company_legal_form' => null,
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        DB::statement('ALTER TABLE applications MODIFY COLUMN applicant_type VARCHAR(255) NOT NULL');
        $this->assertStringContainsString('varchar', strtolower($this->applicantTypeColumnType()));

        $this->migration()->up();

        $this->assertCanonicalApplicantTypeEnum($this->applicantTypeColumnType());
        $this->assertSame('ostalo', $application->fresh()->applicant_type);
        $this->assertNull($application->fresh()->company_legal_form);
    }

    public function test_existing_enum_is_expanded_and_never_converted_to_varchar(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN applicant_type ENUM('preduzetnica','doo','fizicko_lice','ostalo') NULL");
        $before = strtolower($this->applicantTypeColumnType());
        $this->assertTrue(str_starts_with($before, 'enum('));
        $this->assertStringNotContainsString('preduzetnik', $before);

        $this->migration()->up();

        $after = $this->applicantTypeColumnType();
        $this->assertCanonicalApplicantTypeEnum($after);
        $this->assertStringNotContainsString('varchar', strtolower($after));
    }

    public function test_varchar_conversion_refuses_unknown_values_without_changing_data(): void
    {
        $competition = $this->openCompetition('zensko');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(405)]);
        DB::statement('ALTER TABLE applications MODIFY COLUMN applicant_type VARCHAR(255) NOT NULL');
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nepoznat tip',
            'applicant_type' => 'nije_dozvoljeno',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        try {
            $this->migration()->up();
            $this->fail('Expected conversion of unknown applicant_type values to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('outside the allowed set', $exception->getMessage());
            $this->assertStringContainsString('nije_dozvoljeno', $exception->getMessage());
        }

        $this->assertStringContainsString('varchar', strtolower($this->applicantTypeColumnType()));
        $this->assertSame(
            'nije_dozvoljeno',
            Application::query()->where('business_plan_name', 'Nepoznat tip')->value('applicant_type')
        );

        DB::table('applications')->where('applicant_type', 'nije_dozvoljeno')->delete();
        $this->migration()->up();
        $this->assertCanonicalApplicantTypeEnum($this->applicantTypeColumnType());
    }

    public function test_down_refuses_to_shrink_enum_when_new_values_are_in_use(): void
    {
        $competition = $this->openCompetition('omladinsko');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(403)]);
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Tok C',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot roll back applications.applicant_type ENUM');

        $this->migration()->down();
    }

    public function test_down_does_not_shrink_enum_when_new_values_are_unused(): void
    {
        $before = $this->applicantTypeColumnType();
        $this->assertFalse(
            DB::table('applications')->whereIn('applicant_type', ['preduzetnik', 'privredno_drustvo'])->exists()
        );

        $this->migration()->down();

        $after = $this->applicantTypeColumnType();
        $this->assertCanonicalApplicantTypeEnum($after);
        $this->assertFalse(Schema::hasColumn('applications', 'company_legal_form'));
        $this->assertSame(strtolower($before), strtolower($after));

        $this->migration()->up();
        $this->assertTrue(Schema::hasColumn('applications', 'company_legal_form'));
        $this->assertCanonicalApplicantTypeEnum($this->applicantTypeColumnType());
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function assertCanonicalApplicantTypeEnum(string $columnType): void
    {
        $this->assertSame(self::CANONICAL_ENUM, strtolower($columnType));
        $this->assertTrue(str_starts_with(strtolower($columnType), 'enum('));
        $this->assertStringNotContainsString('varchar', strtolower($columnType));

        preg_match_all("/'([^']+)'/", strtolower($columnType), $matches);
        $this->assertSame(KnApplicationClassification::mysqlEnumValues(), $matches[1]);
    }

    private function applicantTypeColumnType(): string
    {
        return $this->columnType('applicant_type');
    }

    private function companyLegalFormColumnType(): string
    {
        return $this->columnType('company_legal_form');
    }

    private function columnType(string $column): string
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['applications', $column]
        );

        $this->assertNotNull($row);

        return (string) $row->COLUMN_TYPE;
    }

    private function openCompetition(string $type): Competition
    {
        return Competition::create([
            'title' => 'ENUM '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
    }
}
