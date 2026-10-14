<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Runtime\DerivedUserTypeMirror;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class PibValidationMessagesTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
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

    public function test_registration_pib_with_wrong_length_or_format_has_single_montenegrin_message(): void
    {
        foreach (['1234567', '123456789', '1234567A'] as $i => $pib) {
            $this->from('/register')
                ->post('/register', $this->entrepreneurPayload("len{$i}@example.com", ['pib' => $pib]))
                ->assertSessionHasErrors('pib');

            $this->assertSame(['PIB mora imati tačno 8 cifara.'], $this->sessionErrors('pib'));
            $this->assertFalse(User::query()->where('email', "len{$i}@example.com")->exists());
        }
    }

    public function test_registration_pib_with_invalid_check_digit_has_single_montenegrin_message(): void
    {
        $this->from('/register')
            ->post('/register', $this->entrepreneurPayload('checksum@example.com', ['pib' => '12345670']))
            ->assertSessionHasErrors('pib');

        $this->assertSame(['PIB nije ispravan.'], $this->sessionErrors('pib'));
        $this->assertNotContains('The pib is invalid.', session('errors')->all());
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_active_pib_is_still_validated_for_legal_entity_and_foreign_branch(): void
    {
        $this->from('/register')
            ->post('/register', $this->legalPayload('doo.bad@example.com', ['pib' => '12345670']))
            ->assertSessionHasErrors('pib');
        $this->assertSame(['PIB nije ispravan.'], $this->sessionErrors('pib'));

        $this->from('/register')
            ->post('/register', $this->dspdPayload('dspd.bad@example.com', ['pib' => '12345670']))
            ->assertSessionHasErrors('pib');
        $this->assertSame(['PIB nije ispravan.'], $this->sessionErrors('pib'));

        $this->from('/register')
            ->post('/register', $this->legalPayload('doo.empty@example.com', ['pib' => '']))
            ->assertSessionHasErrors('pib');
        $this->assertSame(['Unesite PIB.'], $this->sessionErrors('pib'));

        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_inactive_pib_does_not_block_physical_person_registration(): void
    {
        $this->post('/register', $this->registrationHttpPayload('fl.stale@example.com', [
            'registers_as_entrepreneur' => '0',
            'entrepreneur_business_name' => 'Stari unos',
            'pib' => '12345670',
            'crps_number' => '1234',
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice', absolute: false));

        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        $this->assertFalse((bool) $fl->is_entrepreneur);
        $this->assertNull($fl->pib);
    }

    public function test_inactive_pib_already_registered_does_not_block_physical_person_registration(): void
    {
        $pib = $this->validPib(611);
        $this->post('/register', $this->entrepreneurPayload('ent.first@example.com', ['pib' => $pib]))
            ->assertRedirect(route('verification.notice', absolute: false));
        $this->post(route('logout'));

        $this->post('/register', $this->registrationHttpPayload('fl.stale.taken@example.com', [
            'registers_as_entrepreneur' => '0',
            'jmb' => $this->validJmb(612),
            'pib' => $pib,
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->assertSame(1, PhysicalPersonIdentity::query()->whereNotNull('pib')->count());
    }

    public function test_active_pib_already_registered_is_still_rejected(): void
    {
        $pib = $this->validPib(613);
        $this->post('/register', $this->entrepreneurPayload('ent.owner@example.com', ['pib' => $pib]))
            ->assertRedirect(route('verification.notice', absolute: false));
        $this->post(route('logout'));

        $this->from('/register')
            ->post('/register', $this->entrepreneurPayload('ent.dup@example.com', [
                'jmb' => $this->validJmb(614),
                'pib' => $pib,
            ]))
            ->assertSessionHasErrors('pib');

        $this->assertSame(['PIB je već registrovan.'], $this->sessionErrors('pib'));
        $this->assertFalse(User::query()->where('email', 'ent.dup@example.com')->exists());
    }

    public function test_registration_pib_error_is_rendered_next_to_pib_field(): void
    {
        $this->from('/register')
            ->post('/register', $this->entrepreneurPayload('render@example.com', ['pib' => '12345670']))
            ->assertRedirect('/register');

        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="pib" id="pib" class="form-control error"/', $html);
        $this->assertMatchesRegularExpression('/<div class="form-error show" id="pib_error">PIB nije ispravan\.<\/div>/', $html);
    }

    public function test_registration_writer_pib_rejection_is_shown_in_montenegrin_on_pib_field(): void
    {
        LegalEntityIdentity::creating(function (): void {
            throw new CanonicalIdentityWriteException('Invalid PIB.');
        });

        $this->from('/register')
            ->post('/register', $this->legalPayload('writer.pib@example.com', ['pib' => $this->validPib(621)]))
            ->assertSessionHasErrors('pib');

        $this->assertSame(['PIB nije ispravan.'], $this->sessionErrors('pib'));
        $this->assertNotContains('Invalid PIB.', session('errors')->all());
        $this->assertFalse(User::query()->where('email', 'writer.pib@example.com')->exists());
    }

    public function test_profile_writer_pib_rejection_is_shown_in_montenegrin_on_pib_field(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'jmb' => null,
            'company_name' => 'Primjer DOO',
        ]);
        $snapshot = $this->plSnapshot($user);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        (new DerivedUserTypeMirror)->sync($user, $snapshot);

        $this->actingAs($user->refresh())
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'first_name' => 'Ana',
                'last_name' => 'Anić',
                'email' => $user->email,
                'phone' => '+38267000002',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
                'company_name' => 'Primjer DOO',
                'pib' => '12345670',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('pib')
            ->assertSessionDoesntHaveErrors('user_type');

        $this->assertSame(['PIB nije ispravan.'], $this->sessionErrors('pib'));
        $this->assertNotContains('Invalid PIB.', session('errors')->all());
        $this->assertSame('12345672', LegalEntityIdentity::query()->value('pib'));
    }

    public function test_profile_entrepreneur_pib_messages_are_single_and_montenegrin(): void
    {
        $user = $this->seedPhysicalPerson();
        $this->makeKorisnik([
            'email' => 'legacy.holder@example.com',
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'jmb' => null,
            'pib' => '12345670',
        ]);

        foreach (['1234567' => 'PIB mora imati tačno 8 cifara.', '12345670' => 'PIB nije ispravan.'] as $pib => $expected) {
            $this->actingAs($user)
                ->from(route('profile.edit'))
                ->put(route('profile.update'), $this->profilePayload($user, [
                    'registers_as_entrepreneur' => '1',
                    'entrepreneur_business_name' => 'Radnja Ana',
                    'pib' => (string) $pib,
                    'crps_number' => $this->validCrps(1, 41),
                ]))
                ->assertSessionHasErrors('pib');

            $this->assertSame([$expected], $this->sessionErrors('pib'));
        }

        $this->assertFalse((bool) PhysicalPersonIdentity::query()->value('is_entrepreneur'));
    }

    public function test_profile_entrepreneur_with_valid_pib_still_saves(): void
    {
        $user = $this->seedPhysicalPerson();
        $pib = $this->validPib(631);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Ana',
                'pib' => $pib,
                'crps_number' => $this->validCrps(1, 42),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame($pib, PhysicalPersonIdentity::query()->value('pib'));
    }

    /**
     * @return list<string>
     */
    private function sessionErrors(string $field): array
    {
        return session('errors')->get($field);
    }

    private function seedPhysicalPerson(): User
    {
        $jmb = $this->validJmb(40);
        $user = $this->makeKorisnik(['jmb' => $jmb]);
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
            'jmb' => $user->jmb,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function entrepreneurPayload(string $email, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'registers_as_entrepreneur' => '1',
            'jmb' => $this->validJmb(601),
            'entrepreneur_business_name' => 'Radnja Test',
            'pib' => $this->validPib(601),
            'crps_number' => $this->validCrps(1, 1),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function legalPayload(string $email, array $overrides = []): array
    {
        return $this->registrationHttpPayload($email, array_merge([
            'user_type' => UserType::REGISTRATION_GROUP_LEGAL_ENTITY,
            'business_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'first_name' => null,
            'last_name' => null,
            'residential_status' => null,
            'jmb' => null,
            'legal_name' => 'Subjekt '.$email,
            'pib' => $this->validPib(602),
            'crps_number' => $this->validCrps(5, 1),
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'authorized_id_document_type' => 'jmb',
            'authorized_jmb' => $this->validJmb(602),
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
            'legal_name' => null,
            'foreign_company_name' => 'Foreign Co Ltd',
            'branch_name_in_montenegro' => 'Ogranak Kotor',
            'pib' => $this->validPib(603),
            'crps_number' => $this->validCrps(6, 1),
            'representative_first_name' => 'Jelena',
            'representative_last_name' => 'Jović',
            'representative_id_document_type' => 'jmb',
            'representative_jmb' => $this->validJmb(603),
        ], $overrides));
    }
}
