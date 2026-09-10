<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Identity\ForeignBranchSnapshot;
use App\Identity\IdentitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Runtime\DerivedUserTypeMirror;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbDualWrite;
use App\Security\JmbEncryptedReadException;
use App\Security\JmbEncryptedReadService;
use App\Security\JmbEncryptionService;
use App\Security\JmbLookupService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class JmbPlaintextRetirementWriteTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private int $jmbSerial = 900;

    /**
     * @var list<string>
     */
    private array $loggedMessages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'jmb.plaintext_retirement.enabled' => false,
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
        $this->app->forgetInstance(JmbEncryptionService::class);
        $this->app->forgetInstance(JmbLookupService::class);
        $this->loggedMessages = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->loggedMessages[] = $event->message.' '.json_encode($event->context, JSON_UNESCAPED_UNICODE);
        });
    }

    public function test_retirement_false_writes_plaintext_encrypted_and_lookup(): void
    {
        $this->assertFalse(JmbDualWrite::isRetirementEnabled());

        $userJmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $userJmb, 'email' => 'ret-false-user@example.test']);
        $this->assertSame($userJmb, $user->jmb);
        $this->assertEncryptedMatches($user->jmb_encrypted, $userJmb);
        $this->assertSame($this->digest($userJmb), $user->jmb_lookup);

        $writer = new CanonicalIdentityWriter;
        $flUser = $this->makeKorisnik(['jmb' => null, 'email' => 'ret-false-fl@example.test']);
        $flJmb = $this->nextJmb();
        $writer->createForUser($flUser, $this->flSnapshot($flUser, ['person' => ['jmb' => $flJmb]]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame($flJmb, $fl->jmb);
        $this->assertEncryptedMatches($fl->jmb_encrypted, $flJmb);
        $this->assertSame($this->digest($flJmb), $fl->jmb_lookup);

        $leUser = $this->makeKorisnik([
            'email' => 'ret-false-le@example.test',
            'jmb' => null,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);
        $leJmb = $this->nextJmb();
        $writer->createForUser($leUser, $this->plSnapshot($leUser, ['jmb' => $leJmb]));
        $authorized = LegalEntityAuthorizedPerson::query()->firstOrFail();
        $this->assertSame($leJmb, $authorized->jmb);
        $this->assertEncryptedMatches($authorized->jmb_encrypted, $leJmb);

        $fbUser = $this->makeKorisnik([
            'email' => 'ret-false-fb@example.test',
            'jmb' => null,
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
        ]);
        $fbJmb = $this->nextJmb();
        $writer->createForUser($fbUser, $this->foreignBranchSnapshot($fbUser, $fbJmb));
        $rep = ForeignBranchRepresentative::query()->firstOrFail();
        $this->assertSame($fbJmb, $rep->jmb);
        $this->assertEncryptedMatches($rep->jmb_encrypted, $fbJmb);

        $application = $this->makeApplication();
        $personJmb = $this->nextJmb();
        $applicantJmb = $this->nextJmb();
        JmbDualWrite::assignLogical($application, 'physical_person_jmbg', $personJmb);
        JmbDualWrite::assignLogical($application, 'applicant_jmbg', $applicantJmb);
        $application->save();
        $application->refresh();
        $this->assertSame($personJmb, $application->physical_person_jmbg);
        $this->assertSame($applicantJmb, $application->applicant_jmbg);
        $this->assertEncryptedMatches($application->physical_person_jmbg_encrypted, $personJmb);
        $this->assertEncryptedMatches($application->applicant_jmbg_encrypted, $applicantJmb);

        $planJmb = $this->nextJmb();
        $plan = new BusinessPlan(['application_id' => $application->id, 'applicant_name' => 'Ana']);
        JmbDualWrite::assignLogical($plan, 'applicant_jmbg', $planJmb);
        $plan->save();
        $this->assertSame($planJmb, $plan->fresh()->applicant_jmbg);
        $this->assertEncryptedMatches($plan->fresh()->applicant_jmbg_encrypted, $planJmb);
    }

    public function test_retirement_true_persists_encrypted_only_for_all_seven_pairs(): void
    {
        $this->enableRetirement();

        $userJmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $userJmb, 'email' => 'ret-true-user@example.test']);
        $this->assertNull($user->jmb);
        $this->assertEncryptedMatches($user->jmb_encrypted, $userJmb);
        $this->assertSame($this->digest($userJmb), $user->jmb_lookup);

        $writer = new CanonicalIdentityWriter;
        $flUser = $this->makeKorisnik(['jmb' => null, 'email' => 'ret-true-fl@example.test']);
        $flJmb = $this->nextJmb();
        $writer->createForUser($flUser, $this->flSnapshot($flUser, ['person' => ['jmb' => $flJmb]]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertNull($fl->jmb);
        $this->assertEncryptedMatches($fl->jmb_encrypted, $flJmb);
        $this->assertSame($this->digest($flJmb), $fl->jmb_lookup);

        $leUser = $this->makeKorisnik([
            'email' => 'ret-true-le@example.test',
            'jmb' => null,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);
        $leJmb = $this->nextJmb();
        $writer->createForUser($leUser, $this->plSnapshot($leUser, ['jmb' => $leJmb]));
        $authorized = LegalEntityAuthorizedPerson::query()->firstOrFail();
        $this->assertNull($authorized->jmb);
        $this->assertEncryptedMatches($authorized->jmb_encrypted, $leJmb);

        $fbUser = $this->makeKorisnik([
            'email' => 'ret-true-fb@example.test',
            'jmb' => null,
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
        ]);
        $fbJmb = $this->nextJmb();
        $writer->createForUser($fbUser, $this->foreignBranchSnapshot($fbUser, $fbJmb));
        $rep = ForeignBranchRepresentative::query()->firstOrFail();
        $this->assertNull($rep->jmb);
        $this->assertEncryptedMatches($rep->jmb_encrypted, $fbJmb);

        $application = $this->makeApplication();
        $personJmb = $this->nextJmb();
        $applicantJmb = $this->nextJmb();
        JmbDualWrite::assignLogical($application, 'physical_person_jmbg', $personJmb);
        JmbDualWrite::assignLogical($application, 'applicant_jmbg', $applicantJmb);
        $application->save();
        $application->refresh();
        $this->assertNull($application->physical_person_jmbg);
        $this->assertNull($application->applicant_jmbg);
        $this->assertEncryptedMatches($application->physical_person_jmbg_encrypted, $personJmb);
        $this->assertEncryptedMatches($application->applicant_jmbg_encrypted, $applicantJmb);

        $planJmb = $this->nextJmb();
        $plan = new BusinessPlan(['application_id' => $application->id, 'applicant_name' => 'Ana']);
        JmbDualWrite::assignLogical($plan, 'applicant_jmbg', $planJmb);
        $plan->save();
        $this->assertNull($plan->fresh()->applicant_jmbg);
        $this->assertEncryptedMatches($plan->fresh()->applicant_jmbg_encrypted, $planJmb);

        $user->update(['first_name' => 'Nova']);
        $this->assertNull($user->fresh()->jmb);
        $this->assertLogsDoNotLeak([$userJmb, $flJmb, $leJmb, $fbJmb, $personJmb, $applicantJmb, $planJmb]);
    }

    public function test_unrelated_saves_preserve_encrypted_only_rows(): void
    {
        $this->enableRetirement();

        $userJmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $userJmb, 'email' => 'ret-preserve-user@example.test']);
        $userCipher = $user->jmb_encrypted;
        $userLookup = $user->jmb_lookup;
        $user->update(['phone' => '+38267000999']);
        $freshUser = $user->fresh();
        $this->assertNull($freshUser->jmb);
        $this->assertSame($userCipher, $freshUser->jmb_encrypted);
        $this->assertSame($userLookup, $freshUser->jmb_lookup);

        $writer = new CanonicalIdentityWriter;
        $flUser = $this->makeKorisnik(['jmb' => null, 'email' => 'ret-preserve-fl@example.test']);
        $flJmb = $this->nextJmb();
        $writer->createForUser($flUser, $this->flSnapshot($flUser, ['person' => ['jmb' => $flJmb]]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $flCipher = $fl->jmb_encrypted;
        $flLookup = $fl->jmb_lookup;
        $writer->updateLiveGraph($flUser, $this->flSnapshot($flUser, [
            'person' => ['jmb' => $flJmb, 'city' => 'Budva'],
        ]));
        $flAfter = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame('Budva', $flAfter->city);
        $this->assertNull($flAfter->jmb);
        $this->assertSame($flCipher, $flAfter->jmb_encrypted);
        $this->assertSame($flLookup, $flAfter->jmb_lookup);

        $application = $this->makeApplication();
        $personJmb = $this->nextJmb();
        $applicantJmb = $this->nextJmb();
        JmbDualWrite::assignLogical($application, 'physical_person_jmbg', $personJmb);
        JmbDualWrite::assignLogical($application, 'applicant_jmbg', $applicantJmb);
        $application->save();
        $personCipher = $application->fresh()->physical_person_jmbg_encrypted;
        $applicantCipher = $application->fresh()->applicant_jmbg_encrypted;
        $application->update(['business_plan_name' => 'Nova ideja', 'physical_person_jmbg' => null]);
        $freshApp = $application->fresh();
        $this->assertNull($freshApp->physical_person_jmbg);
        $this->assertNull($freshApp->applicant_jmbg);
        $this->assertSame($personCipher, $freshApp->physical_person_jmbg_encrypted);
        $this->assertSame($applicantCipher, $freshApp->applicant_jmbg_encrypted);

        $plan = new BusinessPlan(['application_id' => $application->id, 'applicant_name' => 'Ana']);
        JmbDualWrite::assignLogical($plan, 'applicant_jmbg', $this->nextJmb());
        $plan->save();
        $planCipher = $plan->fresh()->applicant_jmbg_encrypted;
        $plan->update(['applicant_name' => 'Ana Nova', 'applicant_jmbg' => null]);
        $this->assertNull($plan->fresh()->applicant_jmbg);
        $this->assertSame($planCipher, $plan->fresh()->applicant_jmbg_encrypted);
    }

    public function test_intentional_logical_clear_removes_encrypted_and_lookup(): void
    {
        $this->enableRetirement();
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb, 'email' => 'ret-clear@example.test']);
        $this->assertNotNull($user->jmb_encrypted);
        $this->assertNotNull($user->jmb_lookup);

        JmbDualWrite::assignLogical($user, 'jmb', null);
        $user->save();
        $cleared = $user->fresh();
        $this->assertNull($cleared->jmb);
        $this->assertNull($cleared->jmb_encrypted);
        $this->assertNull($cleared->jmb_lookup);
    }

    public function test_retirement_disables_plaintext_fallback_and_decrypt_still_fails_closed(): void
    {
        $this->enableRetirement();
        $plaintext = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $plaintext, 'email' => 'ret-fallback@example.test']);
        DB::table('users')->where('id', $user->id)->update([
            'jmb' => $plaintext,
            'jmb_encrypted' => null,
        ]);
        $fresh = $user->fresh();
        $this->assertNull(app(JmbEncryptedReadService::class)->readValue(
            $fresh->jmb_encrypted,
            $fresh->jmb,
            'users',
            $fresh->id,
            'jmb/jmb_encrypted',
        ));

        $valid = $this->nextJmb();
        $failUser = User::factory()->create(['jmb' => $valid, 'email' => 'ret-fail@example.test']);
        DB::table('users')->where('id', $failUser->id)->update(['jmb_encrypted' => 'jmb:v1:tampered']);
        $this->expectException(JmbEncryptedReadException::class);
        app(JmbEncryptedReadService::class)->readValue(
            $failUser->fresh()->jmb_encrypted,
            $valid,
            'users',
            $failUser->id,
            'jmb/jmb_encrypted',
        );
    }

    public function test_uniqueness_and_profile_work_when_plaintext_is_null(): void
    {
        $this->enableRetirement();
        $taken = $this->nextJmb();
        $holder = User::factory()->create(['jmb' => $taken, 'email' => 'ret-uniq-holder@example.test']);
        $this->assertNull($holder->jmb);
        $this->assertTrue((new CanonicalIdentifierUniqueness)->jmbTaken($taken));

        $this->post('/register', $this->registrationHttpPayload('ret.dup@example.test', [
            'jmb' => $taken,
        ]))->assertSessionHasErrors('jmb');
        $this->assertFalse(User::query()->where('email', 'ret.dup@example.test')->exists());

        $own = $this->nextJmb();
        $user = $this->seedPhysicalPerson($own, 'ret-profile-own@example.test');
        $this->assertNull(PhysicalPersonIdentity::query()->whereHas(
            'platformIdentity',
            fn ($q) => $q->where('user_id', $user->id)
        )->value('jmb'));

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, ['jmb' => $own]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertNull(PhysicalPersonIdentity::query()->whereHas(
            'platformIdentity',
            fn ($q) => $q->where('user_id', $user->id)
        )->value('jmb'));
        $this->assertEncryptedMatches(
            PhysicalPersonIdentity::query()->whereHas(
                'platformIdentity',
                fn ($q) => $q->where('user_id', $user->id)
            )->value('jmb_encrypted'),
            $own
        );

        $actor = $this->seedPhysicalPerson($this->nextJmb(), 'ret-profile-actor@example.test');
        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($actor, ['jmb' => $taken]))
            ->assertSessionHasErrors('jmb')
            ->assertRedirect(route('profile.edit'));

        $this->assertContains('jmb', $this->uniqueColumns('users'));
        $this->assertLogsDoNotLeak([$taken, $own]);
    }

    public function test_declare_on_use_uniqueness_with_plaintext_null(): void
    {
        $this->enableRetirement();
        $taken = $this->nextJmb();
        User::factory()->create(['jmb' => $taken, 'email' => 'ret-declare-holder@example.test']);

        $actor = $this->makeKorisnik([
            'email' => 'ret-declare-actor@example.test',
            'user_type' => UserType::ENTREPRENEUR,
            'jmb' => null,
            'company_name' => 'Radnja Ana',
            'pib' => $this->validPib($this->jmbSerial + 50),
        ]);
        $this->actingAs($actor)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Anić',
                'residential_status' => 'resident',
                'jmb' => $taken,
                'entrepreneur_business_name' => 'Radnja Ana',
                'pib' => $this->validPib(801),
                'crps_number' => $this->validCrps(1, 91),
                'street_and_number' => 'Obala 4',
                'city' => 'Bar',
                'phone_calling_code' => '+382',
                'phone_national' => '67111001',
            ])
            ->assertSessionHasErrors('jmb');

        $unique = $this->nextJmb();
        $this->actingAs($actor)
            ->post(route('identity.completion.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Anić',
                'residential_status' => 'resident',
                'jmb' => $unique,
                'entrepreneur_business_name' => 'Radnja Ana',
                'pib' => $this->validPib(802),
                'crps_number' => $this->validCrps(1, 92),
                'street_and_number' => 'Obala 4',
                'city' => 'Bar',
                'phone_calling_code' => '+382',
                'phone_national' => '67111001',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, PhysicalPersonIdentity::query()->where('jmb_lookup', $this->digest($unique))->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->where('jmb', $unique)->count());
    }

    public function test_backfill_commands_refuse_when_retirement_enabled(): void
    {
        $this->enableRetirement();
        $encrypted = Artisan::call('jmb:backfill-encrypted', ['--dry-run' => true, '--scope' => 'users']);
        $encryptedOutput = Artisan::output();
        $this->assertSame(1, $encrypted);
        $this->assertStringContainsString('refused', $encryptedOutput);
        $this->assertStringNotContainsString('jmb:', $encryptedOutput);

        $lookup = Artisan::call('jmb:backfill-lookup', ['--dry-run' => true, '--scope' => 'users']);
        $lookupOutput = Artisan::output();
        $this->assertSame(1, $lookup);
        $this->assertStringContainsString('refused', $lookupOutput);
    }

    public function test_leftover_profile_path_is_retirement_safe(): void
    {
        config([
            'identity.canonical_write' => false,
            'jmb.plaintext_retirement.enabled' => true,
        ]);
        $jmb = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => null, 'email' => 'ret-leftover@example.test']);
        JmbDualWrite::assignLogical($user, 'jmb', $jmb);
        $user->save();
        $this->assertNull($user->fresh()->jmb);
        $this->assertEncryptedMatches($user->fresh()->jmb_encrypted, $jmb);
        $this->assertSame($this->digest($jmb), $user->fresh()->jmb_lookup);
    }

    private function enableRetirement(): void
    {
        config(['jmb.plaintext_retirement.enabled' => true]);
    }

    private function seedPhysicalPerson(string $jmb, string $email): User
    {
        $user = $this->makeKorisnik([
            'email' => $email,
            'user_type' => UserType::PHYSICAL_PERSON,
            'jmb' => null,
        ]);
        $snapshot = $this->flSnapshot($user, [
            'mobilePhone' => $user->phone,
            'person' => [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'streetAndNumber' => $user->address,
                'city' => $user->city,
                'jmb' => $jmb,
            ],
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        (new DerivedUserTypeMirror)->sync($user, $snapshot);

        return $user->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'user_type' => UserType::PHYSICAL_PERSON,
            'registers_as_entrepreneur' => '0',
            'residential_status' => 'resident',
            'jmb' => $overrides['jmb'] ?? $user->jmb,
        ], $overrides);
    }

    private function foreignBranchSnapshot(User $user, string $jmb): IdentitySnapshot
    {
        return new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
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
                    jmb: $jmb,
                ),
                pib: '00000007',
                crpsNumber: '60000001',
            ),
        );
    }

    private function makeApplication(): Application
    {
        $user = $this->makeKorisnik(['jmb' => null, 'email' => 'ret-app-'.uniqid('', true).'@example.test']);
        $competition = Competition::create([
            'title' => 'Retirement write',
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);

        return new Application([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);
    }

    private function assertEncryptedMatches(?string $ciphertext, string $plaintext): void
    {
        $this->assertIsString($ciphertext);
        $this->assertStringStartsWith('jmb:', $ciphertext);
        $this->assertSame($plaintext, app(JmbEncryptionService::class)->decrypt($ciphertext));
        $this->assertStringNotContainsString($plaintext, $ciphertext);
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

    /**
     * @param  list<string>  $secrets
     */
    private function assertLogsDoNotLeak(array $secrets): void
    {
        $combined = implode("\n", $this->loggedMessages);
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $combined);
        }
        $this->assertStringNotContainsString((string) config('jmb.encryption.key'), $combined);
        $this->assertStringNotContainsString((string) config('jmb.lookup.key'), $combined);
    }

    /**
     * @return list<string>
     */
    private function uniqueColumns(string $table): array
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Non_unique = 0');
        $columns = [];
        foreach ($indexes as $index) {
            if (($index->Key_name ?? '') === 'PRIMARY') {
                continue;
            }
            $columns[] = (string) $index->Column_name;
        }

        return array_values(array_unique($columns));
    }
}
