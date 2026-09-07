<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriteException;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class CanonicalRegistrationContractTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
    }

    public function test_a_resident_physical_person_registers_to_canonical_graph(): void
    {
        $jmb = $this->validJmb(101);
        $this->post('/register', $this->registrationHttpPayload('fl.resident@example.com', [
            'jmb' => $jmb,
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'fl.resident@example.com')->firstOrFail();
        $this->assertCanonicalAccountOnly($user);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame($jmb, $fl->jmb);
        $this->assertSame('resident', $fl->residential_status);
        $this->assertNull($fl->residence_country_code);
        $this->assertSame('+38267000001', PlatformIdentity::query()->value('mobile_phone'));
    }

    public function test_b_non_resident_physical_person_with_jmb(): void
    {
        $jmb = $this->validJmb(102);
        $this->post('/register', $this->registrationHttpPayload('fl.nr.jmb@example.com', [
            'residential_status' => 'non-resident',
            'id_document_type' => 'jmb',
            'jmb' => $jmb,
            'residence_country_code' => 'DE',
            'city' => 'Berlin',
            'address' => 'Main Street 1',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'fl.nr.jmb@example.com')->firstOrFail();
        $this->assertCanonicalAccountOnly($user);
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame($jmb, $fl->jmb);
        $this->assertSame('non_resident', $fl->residential_status);
        $this->assertSame('DE', $fl->residence_country_code);
        $this->assertSame('Berlin', $fl->city);
    }

    public function test_c_non_resident_physical_person_with_passport(): void
    {
        $this->post('/register', $this->registrationHttpPayload('fl.nr.pass@example.com', [
            'residential_status' => 'non-resident',
            'id_document_type' => 'passport',
            'jmb' => null,
            'passport_number' => 'XY987654',
            'residence_country_code' => 'DE',
            'city' => 'Berlin',
            'address' => 'Main Street 1',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertSame('passport', $fl->id_document_type);
        $this->assertSame('XY987654', $fl->passport_number);
        $this->assertNull($fl->jmb);
        $this->assertSame('DE', $fl->residence_country_code);
        $this->assertNotSame('ME', $fl->residence_country_code);
    }

    public function test_d_entrepreneur_requires_and_persists_business_data(): void
    {
        $this->post('/register', $this->entrepreneurPayload('ent.ok@example.com'))
            ->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'ent.ok@example.com')->firstOrFail();
        $this->assertCanonicalAccountOnly($user);
        $this->assertSame(UserType::ENTREPRENEUR, $user->user_type);
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertTrue($fl->is_entrepreneur);
        $this->assertSame('Radnja Test', $fl->entrepreneur_business_name);
        $this->assertSame($this->validPib(201), $fl->pib);
        $this->assertSame($this->validCrps(1, 1), $fl->crps_number);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, PlatformIdentity::query()->value('subject_type'));
    }

    public function test_e_doo_registers_with_crps_and_distinct_authorized_person(): void
    {
        $this->post('/register', $this->legalPayload(UserType::LIMITED_LIABILITY_COMPANY, 'doo.ok@example.com', [
            'pib' => $this->validPib(301),
            'crps_number' => $this->validCrps(5, 1),
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'first_name' => 'ShouldNotBecomeAp',
            'last_name' => 'Holder',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'doo.ok@example.com')->firstOrFail();
        $this->assertCanonicalAccountOnly($user);
        $this->assertSame(UserType::LIMITED_LIABILITY_COMPANY, $user->user_type);
        $legal = LegalEntityIdentity::query()->firstOrFail();
        $this->assertSame(LegalEntityIdentity::FORM_DOO, $legal->legal_form);
        $this->assertSame($this->validCrps(5, 1), $legal->crps_number);
        $ap = LegalEntityAuthorizedPerson::query()->firstOrFail();
        $this->assertSame('Marko', $ap->first_name);
        $this->assertSame('Marković', $ap->last_name);
        $this->assertNotSame('ShouldNotBecomeAp', $ap->first_name);
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
    }

    public function test_f_nvo_association_registers_without_crps(): void
    {
        $this->post('/register', $this->legalPayload(UserType::NGO_ASSOCIATION, 'nvo.ok@example.com', [
            'pib' => $this->validPib(302),
            'crps_number' => $this->validCrps(5, 2),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $legal = LegalEntityIdentity::query()->firstOrFail();
        $this->assertSame(LegalEntityIdentity::FORM_NVO_ASSOCIATION, $legal->legal_form);
        $this->assertNull($legal->crps_number);
    }

    public function test_g_nvo_foundation_is_registerable_without_user_type_mirror(): void
    {
        $this->post('/register', $this->legalPayload(UserType::NGO_FOUNDATION, 'fondacija@example.com', [
            'pib' => $this->validPib(303),
            'legal_name' => 'Fondacija Primjer',
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'fondacija@example.com')->firstOrFail();
        $this->assertNull($user->user_type);
        $this->assertCanonicalAccountOnly($user);
        $legal = LegalEntityIdentity::query()->firstOrFail();
        $this->assertSame(LegalEntityIdentity::FORM_NVO_FOUNDATION, $legal->legal_form);
        $this->assertNull($legal->crps_number);
    }

    public function test_h_dspd_registers_as_foreign_branch_subject(): void
    {
        $this->post('/register', $this->dspdPayload('dspd.ok@example.com'))
            ->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'dspd.ok@example.com')->firstOrFail();
        $this->assertCanonicalAccountOnly($user);
        $this->assertSame(UserType::LEGACY_FOREIGN_BRANCH, $user->user_type);
        $this->assertSame(PlatformIdentity::SUBJECT_FOREIGN_BRANCH, PlatformIdentity::query()->value('subject_type'));
        $branch = ForeignBranchIdentity::query()->firstOrFail();
        $this->assertSame('Foreign Co Ltd', $branch->foreign_company_name);
        $this->assertSame('Ogranak Kotor', $branch->branch_name_in_montenegro);
        $this->assertSame($this->validCrps(6, 1), $branch->crps_number);
        $rep = ForeignBranchRepresentative::query()->firstOrFail();
        $this->assertSame('Jelena', $rep->first_name);
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
    }

    public function test_i_invalid_jmb_is_rejected_before_canonical_write(): void
    {
        $this->post('/register', $this->registrationHttpPayload('bad.jmb@example.com', [
            'jmb' => '1234567890123',
        ]))->assertSessionHasErrors('jmb');

        $this->assertFalse(User::query()->where('email', 'bad.jmb@example.com')->exists());
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_j_duplicate_jmb_is_rejected_against_canonical_table(): void
    {
        $jmb = $this->validJmb(110);
        $this->post('/register', $this->registrationHttpPayload('first.jmb@example.com', ['jmb' => $jmb]))
            ->assertRedirect();
        $this->post(route('logout'));
        $this->post('/register', $this->registrationHttpPayload('dup.jmb@example.com', ['jmb' => $jmb]))
            ->assertSessionHasErrors('jmb');

        $this->assertFalse(User::query()->where('email', 'dup.jmb@example.com')->exists());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
    }

    public function test_k_invalid_pib_checksum_is_rejected_before_writer(): void
    {
        $this->post('/register', $this->entrepreneurPayload('bad.pib@example.com', [
            'pib' => '11111111',
        ]))->assertSessionHasErrors('pib');

        $this->assertFalse(User::query()->where('email', 'bad.pib@example.com')->exists());
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_l_duplicate_pib_is_rejected_against_canonical_table(): void
    {
        $pib = $this->validPib(401);
        $this->post('/register', $this->entrepreneurPayload('first.pib@example.com', ['pib' => $pib]))
            ->assertRedirect();
        $this->post(route('logout'));
        $this->post('/register', $this->legalPayload(UserType::LIMITED_LIABILITY_COMPANY, 'dup.pib@example.com', [
            'pib' => $pib,
            'crps_number' => $this->validCrps(5, 9),
        ]))->assertSessionHasErrors('pib');

        $this->assertFalse(User::query()->where('email', 'dup.pib@example.com')->exists());
    }

    public function test_m_invalid_crps_is_rejected(): void
    {
        $this->post('/register', $this->legalPayload(UserType::LIMITED_LIABILITY_COMPANY, 'bad.crps@example.com', [
            'pib' => $this->validPib(402),
            'crps_number' => $this->validCrps(2, 1),
        ]))->assertSessionHasErrors('crps_number');

        $this->assertFalse(User::query()->where('email', 'bad.crps@example.com')->exists());
    }

    public function test_n_missing_authorized_person_is_rejected(): void
    {
        $payload = $this->legalPayload(UserType::LIMITED_LIABILITY_COMPANY, 'no.ap@example.com', [
            'pib' => $this->validPib(403),
            'crps_number' => $this->validCrps(5, 3),
        ]);
        unset($payload['authorized_first_name'], $payload['authorized_last_name'], $payload['authorized_id_document_type'], $payload['authorized_jmb']);

        $this->post('/register', $payload)->assertSessionHasErrors(['authorized_first_name', 'authorized_last_name']);
        $this->assertFalse(User::query()->where('email', 'no.ap@example.com')->exists());
    }

    public function test_o_passport_issuing_country_required_for_authorized_person(): void
    {
        $this->post('/register', $this->legalPayload(UserType::NGO_ASSOCIATION, 'ap.pass@example.com', [
            'pib' => $this->validPib(404),
            'authorized_id_document_type' => 'passport',
            'authorized_jmb' => null,
            'authorized_passport_number' => 'P123456',
        ]))->assertSessionHasErrors('authorized_passport_issuing_country_code');
    }

    public function test_p_residence_country_is_not_silently_montenegro(): void
    {
        $this->post('/register', $this->registrationHttpPayload('silent.me@example.com', [
            'residential_status' => 'non-resident',
            'id_document_type' => 'passport',
            'jmb' => null,
            'passport_number' => 'AB111222',
        ]))->assertSessionHasErrors('residence_country_code');

        $this->assertFalse(User::query()->where('email', 'silent.me@example.com')->exists());
    }

    public function test_q_phone_is_not_silently_normalized_to_382(): void
    {
        $this->post('/register', $this->registrationHttpPayload('silent.phone@example.com', [
            'phone_calling_code' => '',
            'phone_national' => '67000001',
        ]))->assertSessionHasErrors('phone_calling_code');

        $this->assertFalse(User::query()->where('email', 'silent.phone@example.com')->exists());
    }

    public function test_r_city_is_not_locked_to_kotor(): void
    {
        $this->post('/register', $this->registrationHttpPayload('free.city@example.com', [
            'city' => 'Tivat',
            'jmb' => $this->validJmb(120),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $this->assertSame('Tivat', PhysicalPersonIdentity::query()->value('city'));
    }

    public function test_s_authenticated_user_cannot_register_another_account(): void
    {
        $existing = $this->makeKorisnik();

        $this->actingAs($existing)->get('/register')->assertRedirect();
        $this->actingAs($existing)->post('/register', $this->registrationHttpPayload('second@example.com', [
            'jmb' => $this->validJmb(121),
        ]))->assertRedirect();

        $this->assertAuthenticatedAs($existing);
        $this->assertFalse(User::query()->where('email', 'second@example.com')->exists());
    }

    public function test_t_writer_failure_rolls_back_account_and_graph(): void
    {
        $thrown = false;
        \App\Models\PlatformIdentity::creating(function () use (&$thrown): void {
            if ($thrown) {
                return;
            }
            $thrown = true;
            throw new CanonicalIdentityWriteException('Canonical identity create failed.');
        });

        $this->post('/register', $this->registrationHttpPayload('rollback.writer@example.com', [
            'jmb' => $this->validJmb(122),
        ]))->assertRedirect();

        $this->assertFalse(User::query()->where('email', 'rollback.writer@example.com')->exists());
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
    }

    public function test_u_mirror_failure_rolls_back_account_and_graph(): void
    {
        $thrown = false;
        User::saving(function (User $user) use (&$thrown): void {
            if ($thrown) {
                return;
            }
            if ($user->exists && $user->isDirty('user_type')) {
                $thrown = true;
                throw new \RuntimeException('mirror fail');
            }
        });

        $this->post('/register', $this->registrationHttpPayload('rollback.mirror@example.com', [
            'jmb' => $this->validJmb(123),
        ]))->assertRedirect();

        $this->assertFalse(User::query()->where('email', 'rollback.mirror@example.com')->exists());
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_v_email_verification_requires_prior_authentication(): void
    {
        Notification::fake();
        $this->post('/register', $this->registrationHttpPayload('verify.d12@example.com', [
            'jmb' => $this->validJmb(124),
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'verify.d12@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->post('/logout');
        $this->assertGuest();
        $this->get($url)->assertRedirect(route('login'));
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $other = $this->makeKorisnik(['email' => 'other.verify@example.com']);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->assertAuthenticatedAs($other);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $verifiedAt = $user->fresh()->email_verified_at;

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard', absolute: false));
        $this->assertEquals($verifiedAt, $user->fresh()->email_verified_at);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $html = view('emails.verify-email', ['url' => 'https://example.test/verify'])->render();
        $this->assertStringContainsString('Poštovani/a,', $html);
        $this->assertStringContainsString('Verifikujte e-mail adresu', $html);
        $this->assertStringNotContainsString("If you're having trouble", $html);
    }

    public function test_w_post_verification_canonical_dashboard_and_profile_read(): void
    {
        $this->post('/register', $this->registrationHttpPayload('canon.dash@example.com', [
            'jmb' => $this->validJmb(125),
            'first_name' => 'Ana',
            'last_name' => 'Anić',
        ]))->assertRedirect();

        $user = User::query()->where('email', 'canon.dash@example.com')->firstOrFail();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ana');

        $this->actingAs($user->fresh())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Ana');

        $this->assertNull($user->fresh()->first_name);
        $this->assertSame('Ana', PhysicalPersonIdentity::query()->value('first_name'));
    }

    public function test_registration_form_exposes_v1_branches_without_kotor_lock(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();
        $this->assertStringContainsString('value="Dio stranog privrednog društva"', $html);
        $this->assertStringContainsString('value="Pravno lice"', $html);
        $this->assertStringContainsString('Da li se registrujete kao preduzetnik?', $html);
        $this->assertStringContainsString('value="Nevladina fondacija"', $html);
        $this->assertStringNotContainsString('value="Preduzetnik"', $html);
        $this->assertStringNotContainsString('value="Registrovan privredni subjekt"', $html);
        $this->assertStringContainsString('name="phone_calling_code"', $html);
        $this->assertStringContainsString('umjesto', $html);
        $this->assertStringNotContainsString('Opštine Kotor', $html);
        $this->assertStringNotContainsString('name="phone_full"', $html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function entrepreneurPayload(string $email, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
            'business_type' => UserType::ENTREPRENEUR,
            'jmb' => $this->validJmb(130),
            'entrepreneur_business_name' => 'Radnja Test',
            'pib' => $this->validPib(201),
            'crps_number' => $this->validCrps(1, 1),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function legalPayload(string $businessType, string $email, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'user_type' => UserType::REGISTRATION_GROUP_BUSINESS,
            'business_type' => $businessType,
            'first_name' => null,
            'last_name' => null,
            'residential_status' => null,
            'jmb' => null,
            'legal_name' => 'Subjekt '.$email,
            'pib' => $this->validPib(310),
            'crps_number' => UserType::requiresCrps($businessType) ? $this->validCrps(5, 1) : null,
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'authorized_id_document_type' => 'jmb',
            'authorized_jmb' => $this->validJmb(140),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dspdPayload(string $email, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'user_type' => UserType::FOREIGN_BRANCH,
            'first_name' => null,
            'last_name' => null,
            'residential_status' => null,
            'jmb' => null,
            'foreign_company_name' => 'Foreign Co Ltd',
            'branch_name_in_montenegro' => 'Ogranak Kotor',
            'pib' => $this->validPib(501),
            'crps_number' => $this->validCrps(6, 1),
            'representative_first_name' => 'Jelena',
            'representative_last_name' => 'Jović',
            'representative_id_document_type' => 'jmb',
            'representative_jmb' => $this->validJmb(150),
        ], $overrides));
    }

    private function assertCanonicalAccountOnly(User $user): void
    {
        $this->assertNull($user->jmb);
        $this->assertNull($user->pib);
        $this->assertNull($user->passport_number);
        $this->assertNull($user->residential_status);
        $this->assertNull($user->address);
        $this->assertNull($user->city);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
    }
}
