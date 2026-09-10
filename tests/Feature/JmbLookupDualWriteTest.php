<?php

namespace Tests\Feature;

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
use App\Security\JmbLookupException;
use App\Security\JmbLookupService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class JmbLookupDualWriteTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private int $jmbSerial = 80;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->forgetInstance(JmbLookupService::class);
    }

    public function test_new_user_with_valid_jmb_writes_lookup_digest(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);

        $this->assertSame($jmb, $user->jmb);
        $this->assertSame($this->digest($jmb), $user->jmb_lookup);
        $this->assertSame($this->digest($jmb), DB::table('users')->where('id', $user->id)->value('jmb_lookup'));
        $this->assertStringNotContainsString($jmb, (string) $user->jmb_lookup);
    }

    public function test_new_physical_person_with_valid_jmb_writes_lookup_digest(): void
    {
        $jmb = $this->nextJmb();
        $fl = $this->createPhysicalPerson($jmb);

        $this->assertSame($jmb, $fl->jmb);
        $this->assertSame($this->digest($jmb), $fl->jmb_lookup);
        $this->assertSame(
            $this->digest($jmb),
            DB::table('physical_person_identities')->where('id', $fl->id)->value('jmb_lookup')
        );
    }

    public function test_jmb_change_updates_encrypted_and_lookup(): void
    {
        $original = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $original]);
        $originalCipher = $user->jmb_encrypted;
        $originalLookup = $user->jmb_lookup;

        $updated = $this->nextJmb();
        $user->update(['jmb' => $updated]);
        $fresh = $user->fresh();

        $this->assertSame($updated, $fresh->jmb);
        $this->assertSame($this->digest($updated), $fresh->jmb_lookup);
        $this->assertNotSame($originalLookup, $fresh->jmb_lookup);
        $this->assertNotSame($originalCipher, $fresh->jmb_encrypted);
        $this->assertSame($updated, app(JmbEncryptionService::class)->decrypt($fresh->jmb_encrypted));
    }

    public function test_unrelated_user_update_preserves_lookup(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);
        $lookup = $user->jmb_lookup;
        $cipher = $user->jmb_encrypted;

        $user->update(['first_name' => 'Nova']);
        $fresh = $user->fresh();

        $this->assertSame($jmb, $fresh->jmb);
        $this->assertSame($lookup, $fresh->jmb_lookup);
        $this->assertSame($cipher, $fresh->jmb_encrypted);
    }

    public function test_unrelated_physical_person_update_preserves_lookup(): void
    {
        $jmb = $this->nextJmb();
        $fl = $this->createPhysicalPerson($jmb);
        $lookup = $fl->jmb_lookup;
        $cipher = $fl->jmb_encrypted;

        $fl->update(['city' => 'Budva']);
        $fresh = $fl->fresh();

        $this->assertSame('Budva', $fresh->city);
        $this->assertSame($jmb, $fresh->jmb);
        $this->assertSame($lookup, $fresh->jmb_lookup);
        $this->assertSame($cipher, $fresh->jmb_encrypted);
    }

    public function test_clearing_user_jmb_nulls_encrypted_and_lookup(): void
    {
        $user = User::factory()->create(['jmb' => $this->nextJmb()]);
        $user->update(['jmb' => null]);
        $fresh = $user->fresh();

        $this->assertNull($fresh->jmb);
        $this->assertNull($fresh->jmb_encrypted);
        $this->assertNull($fresh->jmb_lookup);
    }

    public function test_clearing_physical_person_jmb_nulls_encrypted_and_lookup(): void
    {
        $fl = $this->createPhysicalPerson($this->nextJmb());
        $fl->update(['jmb' => null]);
        $fresh = $fl->fresh();

        $this->assertNull($fresh->jmb);
        $this->assertNull($fresh->jmb_encrypted);
        $this->assertNull($fresh->jmb_lookup);
    }

    public function test_null_jmb_user_remains_valid_without_lookup(): void
    {
        $user = User::factory()->create(['jmb' => null]);

        $this->assertNull($user->jmb);
        $this->assertNull($user->jmb_lookup);
        $this->assertNull($user->jmb_encrypted);
    }

    public function test_invalid_non_empty_jmb_blocks_save_without_partial_persist(): void
    {
        $email = 'lookup-invalid@example.test';
        try {
            User::factory()->create(['email' => $email, 'jmb' => '123']);
            $this->fail('Invalid JMB must block persist.');
        } catch (JmbLookupException $e) {
            $this->assertStringNotContainsString('123', $e->getMessage());
        }

        $this->assertSame(0, User::query()->where('email', $email)->count());
        $this->assertSame(0, DB::table('users')->where('jmb', '123')->count());
        $this->assertSame(0, DB::table('users')->whereNotNull('jmb_lookup')->where('email', $email)->count());
    }

    public function test_missing_lookup_key_blocks_non_empty_jmb_save(): void
    {
        $this->assertLookupConfigFailureDoesNotPersist('', 'lookup-missing@example.test');
    }

    public function test_malformed_lookup_key_blocks_save(): void
    {
        $this->assertLookupConfigFailureDoesNotPersist('base64:@@@not-base64@@@', 'lookup-malformed@example.test');
    }

    public function test_users_lookup_unique_collision_is_not_swallowed(): void
    {
        $jmb = $this->nextJmb();
        $digest = $this->digest($jmb);
        $holder = User::factory()->create(['email' => 'lookup-holder@example.test', 'jmb' => null]);
        DB::table('users')->where('id', $holder->id)->update(['jmb_lookup' => $digest]);

        try {
            User::factory()->create(['email' => 'lookup-collider@example.test', 'jmb' => $jmb]);
            $this->fail('users.jmb_lookup UNIQUE must not be swallowed.');
        } catch (QueryException) {
            $this->assertSame(0, User::query()->where('email', 'lookup-collider@example.test')->count());
            $this->assertSame($digest, DB::table('users')->where('id', $holder->id)->value('jmb_lookup'));
            $this->assertNull(DB::table('users')->where('id', $holder->id)->value('jmb'));
        }
    }

    public function test_snapshots_and_other_identity_models_do_not_gain_lookup_sync(): void
    {
        $this->assertFalse(Schema::hasColumn('legal_entity_authorized_persons', 'jmb_lookup'));
        $this->assertFalse(Schema::hasColumn('foreign_branch_representatives', 'jmb_lookup'));
        $this->assertFalse(Schema::hasColumn('applications', 'physical_person_jmbg_lookup'));
        $this->assertFalse(Schema::hasColumn('applications', 'applicant_jmbg_lookup'));
        $this->assertFalse(Schema::hasColumn('business_plans', 'applicant_jmbg_lookup'));

        $authJmb = $this->nextJmb();
        $authorized = $this->createAuthorizedPerson($authJmb);
        $this->assertSame($authJmb, $authorized->jmb);
        $this->assertNotNull($authorized->jmb_encrypted);
        $this->assertArrayNotHasKey('jmb_lookup', $authorized->getAttributes());

        $repJmb = $this->nextJmb();
        $rep = $this->createRepresentative($repJmb);
        $this->assertSame($repJmb, $rep->jmb);
        $this->assertNotNull($rep->jmb_encrypted);
        $this->assertArrayNotHasKey('jmb_lookup', $rep->getAttributes());

        $user = $this->makeKorisnik(['jmb' => null]);
        $competition = Competition::create([
            'title' => 'Lookup dual-write',
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
        $personJmb = $this->nextJmb();
        $applicantJmb = $this->nextJmb();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
            'physical_person_jmbg' => $personJmb,
            'applicant_jmbg' => $applicantJmb,
        ]);
        $this->assertArrayNotHasKey('physical_person_jmbg_lookup', $application->getAttributes());
        $this->assertArrayNotHasKey('applicant_jmbg_lookup', $application->getAttributes());

        $plan = BusinessPlan::updateOrCreate(
            ['application_id' => $application->id],
            ['applicant_jmbg' => $this->nextJmb(), 'applicant_name' => 'Ana']
        );
        $this->assertArrayNotHasKey('applicant_jmbg_lookup', $plan->getAttributes());
    }

    private function assertLookupConfigFailureDoesNotPersist(string $key, string $email): void
    {
        $original = $this->nextJmb();
        $user = User::factory()->create(['email' => $email, 'jmb' => $original]);
        $before = DB::table('users')->where('id', $user->id)->first();

        config(['jmb.lookup.key' => $key]);
        $this->app->forgetInstance(JmbLookupService::class);

        $attempted = $this->nextJmb();
        try {
            $user->update(['jmb' => $attempted]);
            $this->fail('Lookup key failure must block persist.');
        } catch (JmbLookupException $e) {
            $this->assertStringNotContainsString($attempted, $e->getMessage());
            $this->assertStringNotContainsString($original, $e->getMessage());
        }

        $after = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame($before->jmb, $after->jmb);
        $this->assertSame($before->jmb_encrypted, $after->jmb_encrypted);
        $this->assertSame($before->jmb_lookup, $after->jmb_lookup);
        $this->assertSame($original, $after->jmb);
        $this->assertNotSame($attempted, $after->jmb);
    }

    private function createPhysicalPerson(string $jmb): PhysicalPersonIdentity
    {
        $user = User::factory()->create([
            'email' => 'lookup-fl-'.uniqid('', true).'@example.test',
            'jmb' => null,
        ]);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000011',
        ]);

        return PhysicalPersonIdentity::create([
            'platform_identity_id' => $platform->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => $jmb,
            'street_and_number' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }

    private function createAuthorizedPerson(string $jmb): LegalEntityAuthorizedPerson
    {
        $user = User::factory()->create([
            'email' => 'lookup-le-'.uniqid('', true).'@example.test',
            'jmb' => null,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000012',
        ]);
        $legal = LegalEntityIdentity::create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Lookup Dual DOO',
            'pib' => $this->validPib($this->jmbSerial),
            'crps_number' => $this->validCrps(5, $this->jmbSerial),
            'street_and_number' => 'Stari grad 1',
            'city' => 'Kotor',
        ]);

        return LegalEntityAuthorizedPerson::create([
            'legal_entity_identity_id' => $legal->id,
            'first_name' => 'Mila',
            'last_name' => 'Test',
            'id_document_type' => LegalEntityAuthorizedPerson::DOCUMENT_JMB,
            'jmb' => $jmb,
        ]);
    }

    private function createRepresentative(string $jmb): ForeignBranchRepresentative
    {
        $user = User::factory()->create([
            'email' => 'lookup-fb-'.uniqid('', true).'@example.test',
            'jmb' => null,
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
        ]);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            'mobile_phone' => '+38267000013',
        ]);
        $branch = ForeignBranchIdentity::create([
            'platform_identity_id' => $platform->id,
            'foreign_company_name' => 'Lookup Dual Foreign',
            'branch_name_in_montenegro' => 'Lookup Dual Branch',
            'pib' => $this->validPib($this->jmbSerial + 50),
            'crps_number' => $this->validCrps(6, $this->jmbSerial),
            'street_and_number' => 'Obala 2',
            'city' => 'Kotor',
        ]);

        return ForeignBranchRepresentative::create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => 'Iva',
            'last_name' => 'Test',
            'id_document_type' => ForeignBranchRepresentative::DOCUMENT_JMB,
            'jmb' => $jmb,
        ]);
    }

    private function digest(string $jmb): string
    {
        return (string) app(JmbLookupService::class)->digest($jmb);
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }
}
