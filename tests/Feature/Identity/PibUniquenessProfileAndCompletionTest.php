<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Runtime\DerivedUserTypeMirror;
use App\Identity\Runtime\ExistingSubjectIdentityCompletionService;
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

class PibUniquenessProfileAndCompletionTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private const PIB_A = '12345672';

    private const PIB_B = '87654321';

    private const NON_ISO_PIB = '12345670';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
            'identity.ep_identity_flows' => false,
        ]);
    }

    public function test_profile_doo_cannot_take_pib_of_other_canonical_doo(): void
    {
        $owner = $this->seedCanonicalDoo('owner@example.test');
        $this->assertNull($owner->pib);
        $actor = $this->seedCanonicalDoo('actor@example.test', self::PIB_B);

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->legalProfilePayload($actor, ['pib' => self::PIB_A]))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('pib');

        $this->assertSame([CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE], session('errors')->get('pib'));
        $this->assertSame(self::PIB_B, $this->legalPibOf($actor));
        $this->assertSame(1, LegalEntityIdentity::query()->where('pib', self::PIB_A)->count());
    }

    public function test_profile_doo_cannot_take_pib_of_canonical_entrepreneur(): void
    {
        $this->seedCanonicalEntrepreneur('entrepreneur@example.test', self::PIB_A);
        $actor = $this->seedCanonicalDoo('actor@example.test', self::PIB_B);

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->legalProfilePayload($actor, ['pib' => self::PIB_A]))
            ->assertSessionHasErrors('pib');

        $this->assertSame([CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE], session('errors')->get('pib'));
        $this->assertSame(self::PIB_B, $this->legalPibOf($actor));
    }

    public function test_profile_doo_can_save_its_own_pib(): void
    {
        $this->seedCanonicalDoo('owner@example.test');
        $actor = $this->seedCanonicalDoo('actor@example.test', self::PIB_B);

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->legalProfilePayload($actor, ['pib' => self::PIB_B]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame(self::PIB_B, $this->legalPibOf($actor));
    }

    public function test_profile_doo_pib_present_only_in_users_table_is_still_taken(): void
    {
        $this->makeKorisnik(['email' => 'legacy@example.test', 'pib' => '22223333']);
        $actor = $this->seedCanonicalDoo('actor@example.test', self::PIB_B);

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->legalProfilePayload($actor, ['pib' => '22223333']))
            ->assertSessionHasErrors('pib');

        $this->assertSame([CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE], session('errors')->get('pib'));
        $this->assertSame(self::PIB_B, $this->legalPibOf($actor));
    }

    public function test_profile_doo_accepts_free_eight_digit_pib_without_iso_check_digit(): void
    {
        $this->seedCanonicalDoo('owner@example.test');
        $actor = $this->seedCanonicalDoo('actor@example.test', self::PIB_B);

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->legalProfilePayload($actor, ['pib' => self::NON_ISO_PIB]))
            ->assertSessionHasNoErrors();

        $this->assertSame(self::NON_ISO_PIB, $this->legalPibOf($actor));
    }

    public function test_legacy_doo_cannot_complete_identity_with_pib_of_canonical_doo(): void
    {
        $this->seedCanonicalDoo('owner@example.test');
        $legacy = $this->makeLegacyDoo('11112222');

        $this->actingAs($legacy)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooCompletionPayload(['pib' => self::PIB_A]))
            ->assertRedirect(route('identity.completion.create'))
            ->assertSessionHasErrors('pib');

        $this->assertSame([CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE], session('errors')->get('pib'));
        $this->assertFalse(PlatformIdentity::query()->where('user_id', $legacy->id)->exists());
        $this->assertSame(1, LegalEntityIdentity::query()->where('pib', self::PIB_A)->count());
    }

    public function test_legacy_preduzetnik_cannot_complete_identity_with_pib_of_other_identity(): void
    {
        $this->seedCanonicalEntrepreneur('entrepreneur@example.test', self::PIB_A);
        $legacy = $this->makeLegacyPreduzetnik('11113333');

        $this->actingAs($legacy)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikCompletionPayload(['pib' => self::PIB_A]))
            ->assertRedirect(route('identity.completion.create'))
            ->assertSessionHasErrors('pib');

        $this->assertSame([CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE], session('errors')->get('pib'));
        $this->assertFalse(PlatformIdentity::query()->where('user_id', $legacy->id)->exists());
        $this->assertSame(1, PhysicalPersonIdentity::query()->where('pib', self::PIB_A)->count());
    }

    public function test_legacy_doo_can_complete_identity_with_its_own_users_pib(): void
    {
        $this->seedCanonicalDoo('owner@example.test');
        $legacy = $this->makeLegacyDoo('11112222');

        $this->actingAs($legacy)
            ->post(route('identity.completion.store'), $this->dooCompletionPayload(['pib' => '11112222']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame('11112222', $this->legalPibOf($legacy));
    }

    public function test_legacy_doo_cannot_complete_identity_with_pib_present_only_in_other_users_row(): void
    {
        $this->makeKorisnik(['email' => 'legacy-holder@example.test', 'pib' => '22223333']);
        $legacy = $this->makeLegacyDoo('11112222');

        $this->actingAs($legacy)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooCompletionPayload(['pib' => '22223333']))
            ->assertSessionHasErrors('pib');

        $this->assertSame([CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE], session('errors')->get('pib'));
        $this->assertFalse(PlatformIdentity::query()->where('user_id', $legacy->id)->exists());
    }

    public function test_legacy_doo_completion_accepts_free_eight_digit_pib_without_iso_check_digit(): void
    {
        $this->seedCanonicalDoo('owner@example.test');
        $legacy = $this->makeLegacyDoo('11112222');

        $this->actingAs($legacy)
            ->post(route('identity.completion.store'), $this->dooCompletionPayload(['pib' => self::NON_ISO_PIB]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(self::NON_ISO_PIB, $this->legalPibOf($legacy));
    }

    public function test_completion_service_rejects_taken_pib_without_http_validation(): void
    {
        $this->seedCanonicalDoo('owner@example.test');
        $legacyDoo = $this->makeLegacyDoo('11112222');
        $this->seedCanonicalEntrepreneur('entrepreneur@example.test', '33334444');
        $legacyPreduzetnik = $this->makeLegacyPreduzetnik('11113333');
        $service = $this->app->make(ExistingSubjectIdentityCompletionService::class);

        foreach ([
            [$legacyDoo, $this->dooCompletionPayload(['pib' => self::PIB_A])],
            [$legacyPreduzetnik, $this->preduzetnikCompletionPayload(['pib' => '33334444'])],
        ] as [$user, $validated]) {
            try {
                $service->complete($user, $validated);
                $this->fail('Completion service must reject a PIB that belongs to another identity.');
            } catch (CanonicalIdentityWriteException $e) {
                $this->assertSame(CanonicalIdentifierUniqueness::PIB_TAKEN_MESSAGE, $e->getMessage());
            }

            $this->assertFalse(PlatformIdentity::query()->where('user_id', $user->id)->exists());
        }

        $this->assertSame(1, LegalEntityIdentity::query()->where('pib', self::PIB_A)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->where('pib', '33334444')->count());
    }

    private function seedCanonicalDoo(string $email, string $pib = self::PIB_A): User
    {
        $user = $this->makeKorisnik([
            'email' => $email,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'jmb' => null,
            'pib' => null,
            'company_name' => 'Primjer DOO',
        ]);
        $snapshot = $this->plSnapshot($user);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        (new DerivedUserTypeMirror)->sync($user, $snapshot);

        if ($pib !== self::PIB_A) {
            LegalEntityIdentity::query()
                ->whereHas('platformIdentity', fn ($owner) => $owner->where('user_id', $user->id))
                ->update(['pib' => $pib]);
        }

        return $user->refresh();
    }

    private function seedCanonicalEntrepreneur(string $email, string $pib): User
    {
        $jmb = $this->validJmb(70);
        $user = $this->makeKorisnik(['email' => $email, 'jmb' => $jmb, 'pib' => null]);
        $snapshot = $this->flSnapshot($user, [
            'person' => [
                'jmb' => $jmb,
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Vlasnik',
                'pib' => $pib,
                'crpsNumber' => $this->validCrps(1, 70),
            ],
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        (new DerivedUserTypeMirror)->sync($user, $snapshot);

        return $user->refresh();
    }

    private function makeLegacyDoo(string $pib): User
    {
        return $this->makeKorisnik([
            'email' => 'legacy-doo@example.test',
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Legacy DOO',
            'pib' => $pib,
            'first_name' => 'Nosioc',
            'last_name' => 'Naloga',
            'jmb' => null,
            'residential_status' => 'resident',
        ]);
    }

    private function makeLegacyPreduzetnik(string $pib): User
    {
        return $this->makeKorisnik([
            'email' => 'legacy-preduzetnik@example.test',
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => $pib,
            'jmb' => $this->validJmb(21),
            'residential_status' => 'resident',
        ]);
    }

    private function legalPibOf(User $user): ?string
    {
        return LegalEntityIdentity::query()
            ->whereHas('platformIdentity', fn ($owner) => $owner->where('user_id', $user->id))
            ->value('pib');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function legalProfilePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'email' => $user->email,
            'phone' => '+38267000002',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dooCompletionPayload(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Legacy DOO',
            'pib' => self::PIB_A,
            'crps_number' => '50000001',
            'street_and_number' => 'Slobode 1',
            'city' => 'Budva',
            'phone_calling_code' => '+381',
            'phone_national' => '64123456',
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'authorized_id_document_type' => 'jmb',
            'authorized_jmb' => '0000000000000',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function preduzetnikCompletionPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'residential_status' => 'resident',
            'jmb' => '0000000000000',
            'entrepreneur_business_name' => 'Radnja Ana',
            'pib' => self::PIB_A,
            'crps_number' => '10000001',
            'street_and_number' => 'Obala 4',
            'city' => 'Bar',
            'phone_calling_code' => '+382',
            'phone_national' => '67111001',
        ], $overrides);
    }
}
