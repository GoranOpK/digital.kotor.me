<?php

namespace Tests\Feature;

use App\Console\Commands\JmbPlaintextRetirementCommand;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptionService;
use App\Security\JmbLookupService;
use App\Security\JmbPlaintextRetirementPrecheckService;
use App\Security\JmbPlaintextRetirementService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class JmbPlaintextRetirementCommandTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const SEVEN_SCOPES = [
        'users',
        'physical-identities',
        'authorized-persons',
        'foreign-branch-representatives',
        'applications-physical-person',
        'applications-applicant',
        'business-plans',
    ];

    private int $jmbSerial = 500;

    /**
     * @var list<string>
     */
    private array $writeQueries = [];

    /**
     * @var list<string>
     */
    private array $seededSecrets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['jmb.plaintext_retirement.enabled' => false]);
        $this->app->forgetInstance(JmbEncryptionService::class);
        $this->app->forgetInstance(JmbLookupService::class);
        $this->seededSecrets = [];
        $this->writeQueries = [];
    }

    public function test_dry_run_works_with_retirement_flag_off_and_writes_nothing(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->fingerprint();
        $this->listenForWrites();

        $result = $this->runCommand(['--dry-run' => true]);

        $this->assertSame(0, $result['exit']);
        $this->assertFalse((bool) config('jmb.plaintext_retirement.enabled'));
        $this->assertStringContainsString('mode=dry-run', $result['output']);
        $this->assertStringContainsString('precheck=PASS', $result['output']);
        $this->assertStringContainsString('empty_string_rows_are_absent=1', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::READY_LINE, $result['output']);
        $this->assertSame($before, $this->fingerprint());
        $this->assertSame([], $this->writeQueries);
        $this->assertNotNull($this->raw('users', $ids['user'])->jmb);
        $this->assertOutputSanitized($result['output']);
        foreach (self::SEVEN_SCOPES as $scope) {
            $this->assertStringContainsString('scope='.$scope.' ', $result['output']);
        }
        $this->assertMatchesRegularExpression('/total_would_null=[1-9]\\d*/', $result['output']);
    }

    public function test_apply_refuses_with_retirement_flag_off(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->fingerprint();

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('refused', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::FAIL_LINE, $result['output']);
        $this->assertFalse((bool) config('jmb.plaintext_retirement.enabled'));
        $this->assertSame($before, $this->fingerprint());
        $this->assertNotNull($this->raw('users', $ids['user'])->jmb);
    }

    public function test_apply_requires_precheck_pass(): void
    {
        $ids = $this->seedValidSevenPairs();
        DB::table('users')->where('id', $ids['user'])->update([
            'jmb_encrypted' => null,
            'jmb_lookup' => null,
        ]);
        $before = $this->fingerprint();
        $this->enableRetirement();

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=precheck_failed', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::FAIL_LINE, $result['output']);
        $this->assertSame($before, $this->fingerprint());
        $this->assertNotNull($this->raw('users', $ids['user'])->jmb);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_apply_all_nulls_only_seven_plaintext_columns_and_preserves_the_rest(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->columnSnapshot($ids);
        $this->enableRetirement();

        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::PASS_LINE, $result['output']);
        $this->assertStringContainsString('total_nulled=', $result['output']);
        $this->assertOutputSanitized($result['output']);

        $after = $this->columnSnapshot($ids);
        foreach ($this->plaintextPaths() as $path) {
            $this->assertNull(data_get($after, $path));
            $this->assertNotNull(data_get($before, $path));
        }
        $this->assertEncryptedLookupUnchanged($before, $after);
        $this->assertSame($before['users']->email, $after['users']->email);
        $this->assertSame($before['users']->name, $after['users']->name);
        $this->assertSame($before['fl']->first_name, $after['fl']->first_name);
        $this->assertSame($before['authorized']->first_name, $after['authorized']->first_name);
        $this->assertSame($before['representative']->first_name, $after['representative']->first_name);
        $this->assertSame($before['application']->business_plan_name, $after['application']->business_plan_name);
        $this->assertSame($before['plan']->application_id, $after['plan']->application_id);
    }

    public function test_individual_scope_affects_only_selected_scope(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->columnSnapshot($ids);
        $this->enableRetirement();

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('scope=users ', $result['output']);
        $this->assertStringNotContainsString('scope=physical-identities ', $result['output']);

        $after = $this->columnSnapshot($ids);
        $this->assertNull($after['users']->jmb);
        $this->assertSame($before['fl']->jmb, $after['fl']->jmb);
        $this->assertSame($before['authorized']->jmb, $after['authorized']->jmb);
        $this->assertSame($before['representative']->jmb, $after['representative']->jmb);
        $this->assertSame($before['application']->physical_person_jmbg, $after['application']->physical_person_jmbg);
        $this->assertSame($before['application']->applicant_jmbg, $after['application']->applicant_jmbg);
        $this->assertSame($before['plan']->applicant_jmbg, $after['plan']->applicant_jmbg);
        $this->assertEncryptedLookupUnchanged($before, $after);
    }

    public function test_invalid_scope_fails_before_writes(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->fingerprint();
        $this->enableRetirement();

        $result = $this->runCommand(['--scope' => 'not-a-scope']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=invalid_scope', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::FAIL_LINE, $result['output']);
        $this->assertSame($before, $this->fingerprint());
        $this->assertNotNull($this->raw('users', $ids['user'])->jmb);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_apply_is_idempotent_and_second_run_writes_zero_rows(): void
    {
        $ids = $this->seedValidSevenPairs();
        $this->enableRetirement();
        $this->assertSame(0, $this->runCommand()['exit']);

        $afterFirst = $this->columnSnapshot($ids);
        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('total_nulled=0', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::PASS_LINE, $result['output']);
        $afterSecond = $this->columnSnapshot($ids);
        $this->assertEncryptedLookupUnchanged($afterFirst, $afterSecond);
        foreach ($this->plaintextPaths() as $path) {
            $this->assertNull(data_get($afterSecond, $path));
        }
    }

    public function test_post_verification_failure_rolls_back(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->fingerprint();
        $this->enableRetirement();
        $this->bindRetirementHook(afterWrites: function (): void {
            DB::table('users')->whereNotNull('jmb_encrypted')->update([
                'jmb_encrypted' => 'tampered-not-an-envelope',
            ]);
        });

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=post_verification_failed', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementCommand::FAIL_LINE, $result['output']);
        $this->assertSame($before, $this->fingerprint());
        $this->assertNotNull($this->raw('users', $ids['user'])->jmb);
        $this->assertStringNotContainsString('tampered-not-an-envelope', $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_injected_exception_during_later_scope_rolls_back_earlier_writes(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->fingerprint();
        $this->enableRetirement();
        $this->bindRetirementHook(afterScope: function (string $scope): void {
            if ($scope === 'users') {
                throw new \RuntimeException('injected-scope-failure');
            }
        });

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=apply_failed', $result['output']);
        $this->assertStringNotContainsString('injected-scope-failure', $result['output']);
        $this->assertSame($before, $this->fingerprint());
        $this->assertNotNull($this->raw('users', $ids['user'])->jmb);
        $this->assertNotNull($this->raw('physical_person_identities', $ids['fl'])->jmb);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_no_eloquent_lifecycle_clears_encrypted_or_lookup(): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->columnSnapshot($ids);
        $this->enableRetirement();
        $this->assertSame(0, $this->runCommand()['exit']);

        $user = User::query()->findOrFail($ids['user']);
        $user->name = 'Retired Name';
        $user->save();

        $after = $this->columnSnapshot($ids);
        $this->assertNull($after['users']->jmb);
        $this->assertSame($before['users']->jmb_encrypted, $after['users']->jmb_encrypted);
        $this->assertSame($before['users']->jmb_lookup, $after['users']->jmb_lookup);
        $this->assertSame('Retired Name', $after['users']->name);
    }

    #[DataProvider('retirementScopes')]
    public function test_scope_nulls_only_selected_plaintext_column(string $scope, string $column): void
    {
        $ids = $this->seedValidSevenPairs();
        $before = $this->columnSnapshot($ids);
        $this->enableRetirement();

        $result = $this->runCommand(['--scope' => $scope]);
        $this->assertSame(0, $result['exit'], $scope.' should succeed');

        $after = $this->columnSnapshot($ids);
        $this->assertNull($this->valueForColumn($after, $column), $column.' must be nulled');
        $this->assertEncryptedLookupUnchanged($before, $after);
        foreach (JmbPlaintextRetirementService::SCOPES as $otherScope => $otherColumn) {
            if ($otherScope === $scope) {
                continue;
            }
            $this->assertSame(
                $this->valueForColumn($before, $otherColumn),
                $this->valueForColumn($after, $otherColumn),
                $otherColumn.' must stay when retiring '.$column
            );
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function retirementScopes(): array
    {
        $cases = [];
        foreach ([
            'users' => 'users.jmb',
            'physical-identities' => 'physical_person_identities.jmb',
            'authorized-persons' => 'legal_entity_authorized_persons.jmb',
            'foreign-branch-representatives' => 'foreign_branch_representatives.jmb',
            'applications-physical-person' => 'applications.physical_person_jmbg',
            'applications-applicant' => 'applications.applicant_jmbg',
            'business-plans' => 'business_plans.applicant_jmbg',
        ] as $scope => $column) {
            $cases[$scope] = [$scope, $column];
        }

        return $cases;
    }

    public function test_retirement_source_writes_only_plaintext_null_via_query_builder(): void
    {
        $service = (string) file_get_contents(base_path('app/Security/JmbPlaintextRetirementService.php'));
        $command = (string) file_get_contents(base_path('app/Console/Commands/JmbPlaintextRetirementCommand.php'));
        $this->assertStringContainsString('->update([$plaintext => null])', $service);
        $this->assertStringNotContainsString('->save(', $service);
        $this->assertStringNotContainsString('->create(', $service);
        $this->assertStringNotContainsString('->insert(', $service);
        $this->assertStringNotContainsString('->delete(', $service);
        $this->assertStringNotContainsString('dropColumn', $service);
        $this->assertStringNotContainsString('dropUnique', $service);
        $this->assertStringNotContainsString('dropIndex', $service);
        $this->assertStringNotContainsString("'jmb_encrypted' =>", $service);
        $this->assertStringNotContainsString("'jmb_lookup' =>", $service);
        $this->assertStringNotContainsString('->update(', $command);
        $this->assertSame(7, count(JmbPlaintextRetirementService::SCOPES));
        $this->assertSame(
            array_values(JmbPlaintextRetirementPrecheckService::PAIRS),
            array_map(
                fn (string $column) => collect(JmbPlaintextRetirementPrecheckService::PAIRS)->firstWhere('column', $column),
                array_values(JmbPlaintextRetirementService::SCOPES)
            )
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{exit: int, output: string}
     */
    private function runCommand(array $options = []): array
    {
        return [
            'exit' => Artisan::call('jmb:retire-plaintext', $options),
            'output' => Artisan::output(),
        ];
    }

    private function enableRetirement(): void
    {
        config(['jmb.plaintext_retirement.enabled' => true]);
    }

    private function bindRetirementHook(?\Closure $afterScope = null, ?\Closure $afterWrites = null): void
    {
        $service = $this->app->make(JmbPlaintextRetirementService::class);
        $service->afterScopeCallback = $afterScope;
        $service->afterWritesCallback = $afterWrites;
        $this->app->instance(JmbPlaintextRetirementService::class, $service);
    }

    /**
     * @return array{user: int, fl: int, authorized: int, representative: int, application: int, plan: int}
     */
    private function seedValidSevenPairs(): array
    {
        $userJmb = $this->nextJmb();
        $user = User::factory()->create([
            'jmb' => $userJmb,
            'email' => 'retire-user-'.uniqid('', true).'@example.test',
            'name' => 'Retire User',
        ]);

        $flJmb = $this->nextJmb();
        $flUser = User::factory()->create(['jmb' => null, 'email' => 'retire-fl-'.uniqid('', true).'@example.test']);
        $flPlatform = PlatformIdentity::create([
            'user_id' => $flUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000011',
        ]);
        $fl = PhysicalPersonIdentity::create([
            'platform_identity_id' => $flPlatform->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => $flJmb,
            'street_and_number' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);

        $authJmb = $this->nextJmb();
        $leUser = User::factory()->create(['jmb' => null, 'email' => 'retire-le-'.uniqid('', true).'@example.test']);
        $lePlatform = PlatformIdentity::create([
            'user_id' => $leUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000012',
        ]);
        $legal = LegalEntityIdentity::create([
            'platform_identity_id' => $lePlatform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Retire DOO',
            'pib' => $this->validPib($this->jmbSerial),
            'crps_number' => $this->validCrps(5, $this->jmbSerial),
            'street_and_number' => 'Stari grad 1',
            'city' => 'Kotor',
        ]);
        $authorized = LegalEntityAuthorizedPerson::create([
            'legal_entity_identity_id' => $legal->id,
            'first_name' => 'Mila',
            'last_name' => 'Test',
            'id_document_type' => LegalEntityAuthorizedPerson::DOCUMENT_JMB,
            'jmb' => $authJmb,
        ]);

        $repJmb = $this->nextJmb();
        $fbUser = User::factory()->create(['jmb' => null, 'email' => 'retire-fb-'.uniqid('', true).'@example.test']);
        $fbPlatform = PlatformIdentity::create([
            'user_id' => $fbUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            'mobile_phone' => '+38267000013',
        ]);
        $branch = ForeignBranchIdentity::create([
            'platform_identity_id' => $fbPlatform->id,
            'foreign_company_name' => 'Retire Foreign Co',
            'branch_name_in_montenegro' => 'Retire Branch',
            'pib' => $this->validPib($this->jmbSerial + 50),
            'crps_number' => $this->validCrps(6, $this->jmbSerial),
            'street_and_number' => 'Obala 2',
            'city' => 'Kotor',
        ]);
        $representative = ForeignBranchRepresentative::create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => 'Iva',
            'last_name' => 'Test',
            'id_document_type' => ForeignBranchRepresentative::DOCUMENT_JMB,
            'jmb' => $repJmb,
        ]);

        $physicalJmbg = $this->nextJmb();
        $applicantJmbg = $this->nextJmb();
        $appUser = User::factory()->create(['jmb' => null, 'email' => 'retire-app-'.uniqid('', true).'@example.test']);
        $competition = Competition::create([
            'title' => 'Retire JMB',
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $appUser->id,
            'business_plan_name' => 'Retire plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
            'physical_person_jmbg' => $physicalJmbg,
            'applicant_jmbg' => $applicantJmbg,
        ]);

        $planJmbg = $this->nextJmb();
        $plan = BusinessPlan::create([
            'application_id' => $application->id,
            'applicant_jmbg' => $planJmbg,
        ]);

        $this->rememberSecrets([
            $userJmb,
            $flJmb,
            $authJmb,
            $repJmb,
            $physicalJmbg,
            $applicantJmbg,
            $planJmbg,
            (string) $user->jmb_encrypted,
            (string) $fl->jmb_encrypted,
            (string) $authorized->jmb_encrypted,
            (string) $representative->jmb_encrypted,
            (string) $application->physical_person_jmbg_encrypted,
            (string) $application->applicant_jmbg_encrypted,
            (string) $plan->applicant_jmbg_encrypted,
            (string) $user->jmb_lookup,
            (string) $fl->jmb_lookup,
        ]);

        return [
            'user' => (int) $user->id,
            'fl' => (int) $fl->id,
            'authorized' => (int) $authorized->id,
            'representative' => (int) $representative->id,
            'application' => (int) $application->id,
            'plan' => (int) $plan->id,
        ];
    }

    /**
     * @param  array{user: int, fl: int, authorized: int, representative: int, application: int, plan: int}  $ids
     * @return array<string, object>
     */
    private function columnSnapshot(array $ids): array
    {
        return [
            'users' => $this->raw('users', $ids['user']),
            'fl' => $this->raw('physical_person_identities', $ids['fl']),
            'authorized' => $this->raw('legal_entity_authorized_persons', $ids['authorized']),
            'representative' => $this->raw('foreign_branch_representatives', $ids['representative']),
            'application' => $this->raw('applications', $ids['application']),
            'plan' => $this->raw('business_plans', $ids['plan']),
        ];
    }

    /**
     * @return list<string>
     */
    private function plaintextPaths(): array
    {
        return [
            'users.jmb',
            'fl.jmb',
            'authorized.jmb',
            'representative.jmb',
            'application.physical_person_jmbg',
            'application.applicant_jmbg',
            'plan.applicant_jmbg',
        ];
    }

    /**
     * @param  array<string, object>  $before
     * @param  array<string, object>  $after
     */
    private function assertEncryptedLookupUnchanged(array $before, array $after): void
    {
        $this->assertSame($before['users']->jmb_encrypted, $after['users']->jmb_encrypted);
        $this->assertSame($before['users']->jmb_lookup, $after['users']->jmb_lookup);
        $this->assertSame($before['fl']->jmb_encrypted, $after['fl']->jmb_encrypted);
        $this->assertSame($before['fl']->jmb_lookup, $after['fl']->jmb_lookup);
        $this->assertSame($before['authorized']->jmb_encrypted, $after['authorized']->jmb_encrypted);
        $this->assertSame($before['representative']->jmb_encrypted, $after['representative']->jmb_encrypted);
        $this->assertSame($before['application']->physical_person_jmbg_encrypted, $after['application']->physical_person_jmbg_encrypted);
        $this->assertSame($before['application']->applicant_jmbg_encrypted, $after['application']->applicant_jmbg_encrypted);
        $this->assertSame($before['plan']->applicant_jmbg_encrypted, $after['plan']->applicant_jmbg_encrypted);
        $this->assertNotNull($after['users']->jmb_encrypted);
        $this->assertNotNull($after['users']->jmb_lookup);
        $this->assertNotNull($after['fl']->jmb_encrypted);
        $this->assertNotNull($after['fl']->jmb_lookup);
    }

    /**
     * @param  array<string, object>  $snapshot
     */
    private function valueForColumn(array $snapshot, string $column): mixed
    {
        return match ($column) {
            'users.jmb' => $snapshot['users']->jmb,
            'physical_person_identities.jmb' => $snapshot['fl']->jmb,
            'legal_entity_authorized_persons.jmb' => $snapshot['authorized']->jmb,
            'foreign_branch_representatives.jmb' => $snapshot['representative']->jmb,
            'applications.physical_person_jmbg' => $snapshot['application']->physical_person_jmbg,
            'applications.applicant_jmbg' => $snapshot['application']->applicant_jmbg,
            'business_plans.applicant_jmbg' => $snapshot['plan']->applicant_jmbg,
            default => null,
        };
    }

    private function raw(string $table, int $id): object
    {
        $row = DB::table($table)->where('id', $id)->first();
        $this->assertNotNull($row);

        return $row;
    }

    private function fingerprint(): string
    {
        $parts = [];
        foreach (JmbPlaintextRetirementPrecheckService::PAIRS as $pair) {
            $columns = ['id', $pair['plaintext'], $pair['encrypted']];
            if ($pair['lookup'] !== null) {
                $columns[] = $pair['lookup'];
            }
            $parts[] = json_encode(DB::table($pair['table'])->orderBy('id')->get($columns)->toArray());
        }

        return hash('sha256', implode('|', $parts));
    }

    private function listenForWrites(): void
    {
        DB::listen(function ($query): void {
            if (preg_match('/^\s*(insert|update|delete|replace|alter|drop|create|truncate|rename)\b/i', $query->sql) === 1) {
                $this->writeQueries[] = $query->sql;
            }
        });
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }

    /**
     * @param  list<string>  $secrets
     */
    private function rememberSecrets(array $secrets): void
    {
        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $this->seededSecrets[] = $secret;
            }
        }
    }

    private function assertOutputSanitized(string $output): void
    {
        $this->assertDoesNotMatchRegularExpression('/\\bid=\\d+/', $output);
        $this->assertStringNotContainsString('jmb:v1:', $output);
        $this->assertStringNotContainsString((string) config('jmb.encryption.key'), $output);
        $this->assertStringNotContainsString((string) config('jmb.lookup.key'), $output);
        foreach ($this->seededSecrets as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
    }
}
