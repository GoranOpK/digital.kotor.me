<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Identity\ForeignBranchSnapshot;
use App\Identity\IdentitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\PhysicalPersonIdentity;
use App\Models\User;
use App\Security\JmbEncryptionException;
use App\Security\JmbEncryptionService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class JmbDualWriteTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private int $jmbSerial = 40;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->forgetInstance(JmbEncryptionService::class);
    }

    public function test_user_create_and_update_dual_write_and_preserve_ciphertext_on_unrelated_change(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);

        $this->assertSame($jmb, $user->jmb);
        $this->assertEncryptedMatches($user->jmb_encrypted, $jmb);

        $ciphertext = $user->jmb_encrypted;
        $user->update(['first_name' => 'Nova']);
        $this->assertSame($jmb, $user->fresh()->jmb);
        $this->assertSame($ciphertext, $user->fresh()->jmb_encrypted);

        $updated = $this->nextJmb();
        $user->update(['jmb' => $updated]);
        $fresh = $user->fresh();
        $this->assertSame($updated, $fresh->jmb);
        $this->assertNotSame($ciphertext, $fresh->jmb_encrypted);
        $this->assertEncryptedMatches($fresh->jmb_encrypted, $updated);

        $user->update(['jmb' => null]);
        $cleared = $user->fresh();
        $this->assertNull($cleared->jmb);
        $this->assertNull($cleared->jmb_encrypted);
    }

    public function test_canonical_identity_writer_dual_writes_all_person_jmb_mappings(): void
    {
        $writer = new CanonicalIdentityWriter;
        $encryption = app(JmbEncryptionService::class);

        $flUser = $this->makeKorisnik(['jmb' => null, 'email' => 'c1-fl@example.test']);
        $flJmb = $this->nextJmb();
        $writer->createForUser($flUser, $this->flSnapshot($flUser, ['person' => ['jmb' => $flJmb]]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame($flJmb, $fl->jmb);
        $this->assertEncryptedMatches($fl->jmb_encrypted, $flJmb);
        $this->assertNull($flUser->fresh()->jmb);

        $flCipher = $fl->jmb_encrypted;
        $writer->updateLiveGraph($flUser, $this->flSnapshot($flUser, [
            'person' => ['jmb' => $flJmb, 'city' => 'Budva'],
        ]));
        $flAfterUnrelated = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame('Budva', $flAfterUnrelated->city);
        $this->assertSame($flJmb, $flAfterUnrelated->jmb);
        $this->assertSame($flCipher, $flAfterUnrelated->jmb_encrypted);

        $flUpdated = $this->nextJmb();
        $writer->updateLiveGraph($flUser, $this->flSnapshot($flUser, [
            'person' => ['jmb' => $flUpdated, 'city' => 'Budva'],
        ]));
        $flAfterJmb = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame($flUpdated, $flAfterJmb->jmb);
        $this->assertEncryptedMatches($flAfterJmb->jmb_encrypted, $flUpdated);

        $leUser = $this->makeKorisnik([
            'email' => 'c1-le@example.test',
            'jmb' => null,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);
        $leJmb = $this->nextJmb();
        $writer->createForUser($leUser, $this->plSnapshot($leUser, ['jmb' => $leJmb]));
        $authorized = LegalEntityAuthorizedPerson::query()->firstOrFail();
        $this->assertSame($leJmb, $authorized->jmb);
        $this->assertEncryptedMatches($authorized->jmb_encrypted, $leJmb);

        $fbUser = $this->makeKorisnik([
            'email' => 'c1-fb@example.test',
            'jmb' => null,
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
        ]);
        $fbJmb = $this->nextJmb();
        $writer->createForUser($fbUser, new IdentitySnapshot(
            userId: $fbUser->id,
            isRegisteredSubject: true,
            subjectType: \App\Models\PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            mobilePhone: '+38267000003',
            streetAndNumber: 'Bulevar 8',
            city: 'Podgorica',
            foreignBranch: new ForeignBranchSnapshot(
                foreignCompanyName: 'Foreign Co',
                branchNameInMontenegro: 'Ogranak CG',
                streetAndNumber: 'Bulevar 8',
                city: 'Podgorica',
                representative: new PersonInRoleSnapshot(
                    firstName: 'Jelena',
                    lastName: 'Jovanović',
                    idDocumentType: 'jmb',
                    jmb: $fbJmb,
                ),
                pib: '00000007',
                crpsNumber: '60000001',
            ),
        ));
        $rep = ForeignBranchRepresentative::query()->firstOrFail();
        $this->assertSame($fbJmb, $rep->jmb);
        $this->assertEncryptedMatches($rep->jmb_encrypted, $fbJmb);
    }

    public function test_application_and_business_plan_dual_write(): void
    {
        $user = $this->makeKorisnik(['jmb' => null]);
        $competition = Competition::create([
            'title' => 'C1 dual-write',
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

        $this->assertSame($personJmb, $application->physical_person_jmbg);
        $this->assertSame($applicantJmb, $application->applicant_jmbg);
        $this->assertEncryptedMatches($application->physical_person_jmbg_encrypted, $personJmb);
        $this->assertEncryptedMatches($application->applicant_jmbg_encrypted, $applicantJmb);

        $personCipher = $application->physical_person_jmbg_encrypted;
        $applicantCipher = $application->applicant_jmbg_encrypted;
        $application->update(['business_plan_name' => 'Nova ideja']);
        $freshApp = $application->fresh();
        $this->assertSame($personCipher, $freshApp->physical_person_jmbg_encrypted);
        $this->assertSame($applicantCipher, $freshApp->applicant_jmbg_encrypted);
        $this->assertSame($personJmb, $freshApp->physical_person_jmbg);

        $newPerson = $this->nextJmb();
        $application->update(['physical_person_jmbg' => $newPerson]);
        $updatedApp = $application->fresh();
        $this->assertSame($newPerson, $updatedApp->physical_person_jmbg);
        $this->assertEncryptedMatches($updatedApp->physical_person_jmbg_encrypted, $newPerson);
        $this->assertSame($applicantCipher, $updatedApp->applicant_jmbg_encrypted);

        $planJmb = $this->nextJmb();
        $plan = BusinessPlan::updateOrCreate(
            ['application_id' => $application->id],
            ['applicant_jmbg' => $planJmb, 'applicant_name' => 'Ana']
        );
        $this->assertSame($planJmb, $plan->applicant_jmbg);
        $this->assertEncryptedMatches($plan->applicant_jmbg_encrypted, $planJmb);
        $planCipher = $plan->applicant_jmbg_encrypted;

        $plan->update(['applicant_name' => 'Ana Nova']);
        $this->assertSame($planCipher, $plan->fresh()->applicant_jmbg_encrypted);

        $plan->update(['applicant_jmbg' => null]);
        $this->assertNull($plan->fresh()->applicant_jmbg);
        $this->assertNull($plan->fresh()->applicant_jmbg_encrypted);
    }

    public function test_encryption_failure_prevents_plaintext_persist(): void
    {
        config(['jmb.encryption.key' => '']);
        $this->app->forgetInstance(JmbEncryptionService::class);

        $jmb = $this->nextJmb();
        try {
            User::factory()->create([
                'email' => 'c1-fail@example.test',
                'jmb' => $jmb,
            ]);
            $this->fail('Missing JMB key must block persist.');
        } catch (JmbEncryptionException $e) {
            $this->assertStringNotContainsString($jmb, $e->getMessage());
        }

        $this->assertSame(0, User::query()->where('email', 'c1-fail@example.test')->count());
        $this->assertSame(0, DB::table('users')->where('jmb', $jmb)->count());
    }

    public function test_plaintext_reads_and_uniqueness_remain_unchanged(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);

        $this->assertSame($jmb, $user->fresh()->jmb);
        $this->assertSame($jmb, DB::table('users')->where('id', $user->id)->value('jmb'));
        $this->assertNotSame($jmb, $user->fresh()->jmb_encrypted);

        $this->assertTrue((new CanonicalIdentifierUniqueness)->jmbTaken($jmb));
        $this->assertFalse((new CanonicalIdentifierUniqueness)->jmbTaken($this->nextJmb()));

        $duplicate = $this->nextJmb();
        User::factory()->create(['email' => 'c1-unique-a@example.test', 'jmb' => $duplicate]);
        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'c1-unique-b@example.test', 'jmb' => $duplicate]);
    }

    private function assertEncryptedMatches(?string $ciphertext, string $plaintext): void
    {
        $this->assertIsString($ciphertext);
        $this->assertStringStartsWith('jmb:', $ciphertext);
        $this->assertSame($plaintext, app(JmbEncryptionService::class)->decrypt($ciphertext));
        $this->assertStringNotContainsString($plaintext, $ciphertext);
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }
}
