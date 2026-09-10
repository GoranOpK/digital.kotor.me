<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriter;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Runtime\DerivedUserTypeMirror;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class PhysicalPersonEntrepreneurProfileLifecycleTest extends TestCase
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

    public function test_ordinary_physical_person_remains_ordinary_after_normal_profile_edit(): void
    {
        $user = $this->seedPhysicalPerson();
        $platformId = $this->platformId($user);
        $flId = $this->physicalPersonId($user);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'first_name' => 'Petar',
                'registers_as_entrepreneur' => '0',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $fl = $this->physicalPerson($user);

        $this->assertSame($platformId, $this->platformId($user));
        $this->assertSame($flId, $fl->id);
        $this->assertFalse((bool) $fl->is_entrepreneur);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertSame('Petar', $fl->first_name);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $this->platform($user)->subject_type);
    }

    public function test_physical_person_can_become_entrepreneur_with_valid_business_data(): void
    {
        $user = $this->seedPhysicalPerson();
        $platformId = $this->platformId($user);
        $flId = $this->physicalPersonId($user);
        $pib = $this->validPib(31);
        $crps = $this->validCrps(1, 31);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Petar',
                'pib' => $pib,
                'crps_number' => $crps,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $fl = $this->physicalPerson($user);

        $this->assertSame($platformId, $this->platformId($user));
        $this->assertSame($flId, $fl->id);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->where('platform_identity_id', $platformId)->count());
        $this->assertTrue((bool) $fl->is_entrepreneur);
        $this->assertSame('Radnja Petar', $fl->entrepreneur_business_name);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame($crps, $fl->crps_number);
        $this->assertSame(UserType::ENTREPRENEUR, $user->user_type);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $this->platform($user)->subject_type);
    }

    public function test_upgrade_to_entrepreneur_fails_without_business_name(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => '',
                'pib' => $this->validPib(32),
                'crps_number' => $this->validCrps(1, 32),
            ]))
            ->assertSessionHasErrors('entrepreneur_business_name');

        $this->assertFalse((bool) $this->physicalPerson($user)->is_entrepreneur);
    }

    public function test_upgrade_to_entrepreneur_fails_without_pib(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Petar',
                'pib' => '',
                'crps_number' => $this->validCrps(1, 33),
            ]))
            ->assertSessionHasErrors('pib');

        $this->assertFalse((bool) $this->physicalPerson($user)->is_entrepreneur);
    }

    public function test_upgrade_to_entrepreneur_fails_without_crps_number(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Petar',
                'pib' => $this->validPib(34),
                'crps_number' => '',
            ]))
            ->assertSessionHasErrors('crps_number');

        $this->assertFalse((bool) $this->physicalPerson($user)->is_entrepreneur);
    }

    public function test_upgrade_to_entrepreneur_rejects_invalid_pib(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Petar',
                'pib' => '12345670',
                'crps_number' => $this->validCrps(1, 35),
            ]))
            ->assertSessionHasErrors('pib');

        $this->assertFalse((bool) $this->physicalPerson($user)->is_entrepreneur);
    }

    public function test_upgrade_to_entrepreneur_rejects_invalid_crps_mark(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Petar',
                'pib' => $this->validPib(36),
                'crps_number' => '20000001',
            ]))
            ->assertSessionHasErrors('crps_number');

        $this->assertFalse((bool) $this->physicalPerson($user)->is_entrepreneur);
    }

    public function test_existing_entrepreneur_can_edit_business_name_pib_and_crps(): void
    {
        $user = $this->seedPhysicalPerson([
            'isEntrepreneur' => true,
            'entrepreneurBusinessName' => 'Radnja Ana',
            'pib' => $this->validPib(41),
            'crpsNumber' => $this->validCrps(1, 41),
        ]);
        $flId = $this->physicalPersonId($user);
        $pib = $this->validPib(42);
        $crps = $this->validCrps(1, 42);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Nova',
                'pib' => $pib,
                'crps_number' => $crps,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $fl = $this->physicalPerson($user);
        $this->assertSame($flId, $fl->id);
        $this->assertTrue((bool) $fl->is_entrepreneur);
        $this->assertSame('Radnja Nova', $fl->entrepreneur_business_name);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame($crps, $fl->crps_number);
        $this->assertSame(UserType::ENTREPRENEUR, $user->refresh()->user_type);
    }

    public function test_entrepreneur_can_return_to_ordinary_physical_person_and_retain_business_data(): void
    {
        $pib = $this->validPib(51);
        $crps = $this->validCrps(1, 51);
        $user = $this->seedPhysicalPerson([
            'isEntrepreneur' => true,
            'entrepreneurBusinessName' => 'Radnja Ana',
            'pib' => $pib,
            'crpsNumber' => $crps,
        ]);
        $platformId = $this->platformId($user);
        $flId = $this->physicalPersonId($user);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '0',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $fl = $this->physicalPerson($user);

        $this->assertSame($platformId, $this->platformId($user));
        $this->assertSame($flId, $fl->id);
        $this->assertFalse((bool) $fl->is_entrepreneur);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertSame('Radnja Ana', $fl->entrepreneur_business_name);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame($crps, $fl->crps_number);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $this->platform($user)->subject_type);
    }

    public function test_dashboard_shows_pib_for_current_entrepreneur(): void
    {
        $pib = $this->validPib(52);
        $crps = $this->validCrps(1, 52);
        $user = $this->seedPhysicalPerson([
            'isEntrepreneur' => true,
            'entrepreneurBusinessName' => 'Radnja Ana',
            'pib' => $pib,
            'crpsNumber' => $crps,
        ]);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Informacije o korisniku', $html);
        $this->assertStringContainsString('Preduzetnik', $html);
        $this->assertStringNotContainsString('<span class="info-label">Newsletter</span>', $html);
        $this->assertStringNotContainsString('Upravljaj pretplatom', $html);
        $this->assertDashboardLabeledValue($html, 'Poslovno ime', 'Radnja Ana');
        $this->assertDashboardLabeledValue($html, 'PIB', $pib);
        $this->assertDashboardLabeledValue($html, 'CRPS', $crps);
    }

    public function test_dashboard_hides_retained_business_data_after_return_to_physical_person(): void
    {
        $pib = $this->validPib(53);
        $crps = $this->validCrps(1, 53);
        $user = $this->seedPhysicalPerson([
            'isEntrepreneur' => true,
            'entrepreneurBusinessName' => 'Radnja Ana',
            'pib' => $pib,
            'crpsNumber' => $crps,
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '0',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $fl = $this->physicalPerson($user);
        $this->assertFalse((bool) $fl->is_entrepreneur);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame('Radnja Ana', $fl->entrepreneur_business_name);
        $this->assertSame($crps, $fl->crps_number);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Informacije o korisniku', $html);
        $this->assertStringContainsString('Fizičko lice', $html);
        $this->assertStringNotContainsString('<span class="info-label">Poslovno ime</span>', $html);
        $this->assertStringNotContainsString('<span class="info-label">PIB</span>', $html);
        $this->assertStringNotContainsString('<span class="info-label">CRPS</span>', $html);
        $this->assertStringNotContainsString('Radnja Ana', $html);
        $this->assertStringNotContainsString($crps, $html);
        $this->assertStringNotContainsString($pib, $html);
    }

    public function test_entrepreneur_status_round_trip_reuses_retained_values(): void
    {
        $pib = $this->validPib(61);
        $crps = $this->validCrps(1, 61);
        $user = $this->seedPhysicalPerson();
        $flId = $this->physicalPersonId($user);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Krug',
                'pib' => $pib,
                'crps_number' => $crps,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '0',
            ]))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="Radnja Krug"', $html);
        $this->assertStringContainsString('value="'.$pib.'"', $html);
        $this->assertStringContainsString('value="'.$crps.'"', $html);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Krug',
                'pib' => $pib,
                'crps_number' => $crps,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $fl = $this->physicalPerson($user);
        $this->assertSame($flId, $fl->id);
        $this->assertTrue((bool) $fl->is_entrepreneur);
        $this->assertSame('Radnja Krug', $fl->entrepreneur_business_name);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame($crps, $fl->crps_number);
        $this->assertSame(UserType::ENTREPRENEUR, $user->refresh()->user_type);

        $dashboard = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertDashboardLabeledValue($dashboard, 'Poslovno ime', 'Radnja Krug');
        $this->assertDashboardLabeledValue($dashboard, 'PIB', $pib);
        $this->assertDashboardLabeledValue($dashboard, 'CRPS', $crps);
    }

    public function test_ordinary_profile_update_does_not_erase_retained_entrepreneur_data(): void
    {
        $pib = $this->validPib(71);
        $crps = $this->validCrps(1, 71);
        $user = $this->seedPhysicalPerson([
            'isEntrepreneur' => false,
            'entrepreneurBusinessName' => 'Zadržana radnja',
            'pib' => $pib,
            'crpsNumber' => $crps,
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'first_name' => 'Ivana',
                'registers_as_entrepreneur' => '0',
                'entrepreneur_business_name' => '',
                'pib' => '',
                'crps_number' => '',
            ]))
            ->assertSessionHasNoErrors();

        $fl = $this->physicalPerson($user);
        $this->assertFalse((bool) $fl->is_entrepreneur);
        $this->assertSame('Ivana', $fl->first_name);
        $this->assertSame('Zadržana radnja', $fl->entrepreneur_business_name);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame($crps, $fl->crps_number);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->refresh()->user_type);
    }

    public function test_existing_entrepreneur_can_resave_own_canonical_pib(): void
    {
        $pib = $this->validPib(101);
        $crps = $this->validCrps(1, 101);
        $user = $this->seedPhysicalPerson([
            'isEntrepreneur' => true,
            'entrepreneurBusinessName' => 'Radnja Ana',
            'pib' => $pib,
            'crpsNumber' => $crps,
        ]);

        $uniqueness = app(CanonicalIdentifierUniqueness::class);
        $this->assertTrue($uniqueness->pibTaken($pib));
        $this->assertFalse($uniqueness->pibTaken($pib, (int) $user->id));

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Ana',
                'pib' => $pib,
                'crps_number' => $crps,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $fl = $this->physicalPerson($user);
        $this->assertTrue((bool) $fl->is_entrepreneur);
        $this->assertSame($pib, $fl->pib);
        $this->assertSame(UserType::ENTREPRENEUR, $user->refresh()->user_type);
    }

    public function test_profile_rejects_pib_owned_by_another_canonical_identity(): void
    {
        $pib = $this->validPib(102);
        $owner = $this->seedPhysicalPerson([
            'isEntrepreneur' => true,
            'entrepreneurBusinessName' => 'Radnja Vlasnik',
            'pib' => $pib,
            'crpsNumber' => $this->validCrps(1, 102),
        ]);
        $actor = $this->seedPhysicalPerson([
            'jmb' => $this->validJmb(21),
        ]);

        $uniqueness = app(CanonicalIdentifierUniqueness::class);
        $this->assertTrue($uniqueness->pibTaken($pib));
        $this->assertTrue($uniqueness->pibTaken($pib, (int) $actor->id));
        $this->assertFalse($uniqueness->pibTaken($pib, (int) $owner->id));

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($actor, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Druga',
                'pib' => $pib,
                'crps_number' => $this->validCrps(1, 103),
            ]))
            ->assertSessionHasErrors('pib');

        $this->assertFalse((bool) $this->physicalPerson($actor)->is_entrepreneur);
        $this->assertSame($pib, $this->physicalPerson($owner)->pib);
    }

    public function test_orphan_canonical_pib_without_platform_identity_is_structurally_impossible(): void
    {
        $platformColumn = collect(Schema::getColumns('physical_person_identities'))
            ->firstWhere('name', 'platform_identity_id');
        $this->assertNotNull($platformColumn);
        $this->assertFalse((bool) ($platformColumn['nullable'] ?? true));

        $actor = $this->seedPhysicalPerson();
        $orphanPib = $this->validPib(199);
        $created = false;

        try {
            PhysicalPersonIdentity::query()->create([
                'first_name' => 'Orphan',
                'last_name' => 'Pib',
                'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
                'street_and_number' => 'Njegoševa 12',
                'city' => 'Podgorica',
                'is_entrepreneur' => true,
                'pib' => $orphanPib,
            ]);
            $created = true;
        } catch (QueryException $e) {
            $this->assertMatchesRegularExpression(
                '/platform_identity|Integrity constraint|cannot be null|doesn\'t have a default value|1048|1364|23000/i',
                $e->getMessage().' '.$e->getCode()
            );
        }

        if ($created) {
            $this->assertTrue(
                app(CanonicalIdentifierUniqueness::class)->pibTaken($orphanPib, (int) $actor->id),
                'An orphan canonical PIB row must remain taken under self-exclusion.'
            );

            return;
        }

        $this->assertFalse(PhysicalPersonIdentity::query()->where('pib', $orphanPib)->exists());
    }

    public function test_physical_person_cannot_convert_to_legal_entity_from_profile(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
                'company_name' => 'Firma DOO',
                'pib' => $this->validPib(81),
            ]))
            ->assertSessionHasErrors('user_type');

        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $this->platform($user)->subject_type);
        $this->assertFalse((bool) $this->physicalPerson($user)->is_entrepreneur);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->refresh()->user_type);
    }

    public function test_legal_entity_profile_update_still_works(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'jmb' => null,
            'pib' => '12345672',
            'company_name' => 'Firma DOO',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));
        (new DerivedUserTypeMirror)->sync($user, $this->plSnapshot($user));

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Marko',
                'last_name' => 'Marković',
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
                'pib' => '12345672',
                'company_name' => 'Firma DOO Nova',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame(UserType::LIMITED_LIABILITY_COMPANY, $user->user_type);
        $this->assertSame(PlatformIdentity::SUBJECT_LEGAL_ENTITY, $this->platform($user)->subject_type);
        $this->assertSame('Firma DOO Nova', $this->platform($user)->legalEntity->legal_name);
    }

    public function test_dashboard_shows_pib_for_legal_entity(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'jmb' => null,
            'pib' => '12345672',
            'company_name' => 'Firma DOO',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));
        (new DerivedUserTypeMirror)->sync($user, $this->plSnapshot($user));

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Informacije o korisniku', $html);
        $this->assertDashboardLabeledValue($html, 'PIB', '12345672');
        $this->assertStringNotContainsString('<span class="info-label">Poslovno ime</span>', $html);
        $this->assertStringNotContainsString('<span class="info-label">CRPS</span>', $html);
    }

    public function test_validation_failure_preserves_entrepreneur_selection_and_entered_values(): void
    {
        $user = $this->seedPhysicalPerson();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($user, [
                'registers_as_entrepreneur' => '1',
                'entrepreneur_business_name' => 'Radnja Unos',
                'pib' => '',
                'crps_number' => $this->validCrps(1, 91),
            ]))
            ->assertSessionHasErrors('pib')
            ->assertRedirect(route('profile.edit'));

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/name="registers_as_entrepreneur"[^>]*>[\s\S]*<option value="1"[^>]*selected/', $html);
        $this->assertStringContainsString('value="Radnja Unos"', $html);
        $this->assertStringContainsString('value="'.$this->validCrps(1, 91).'"', $html);
        $this->assertStringContainsString('id="entrepreneurFields"', $html);
    }

    /**
     * @param  array<string, mixed>  $person
     */
    private function seedPhysicalPerson(array $person = []): User
    {
        $jmb = $person['jmb'] ?? $this->validJmb(20);
        $user = $this->makeKorisnik([
            'user_type' => ($person['isEntrepreneur'] ?? false) ? UserType::ENTREPRENEUR : UserType::PHYSICAL_PERSON,
            'jmb' => $jmb,
        ]);

        $snapshot = $this->flSnapshot($user, [
            'mobilePhone' => $user->phone,
            'person' => array_merge([
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'streetAndNumber' => $user->address,
                'city' => $user->city,
                'jmb' => $user->jmb,
            ], $person),
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

    private function platform(User $user): PlatformIdentity
    {
        return PlatformIdentity::query()->where('user_id', $user->id)->firstOrFail();
    }

    private function platformId(User $user): int
    {
        return (int) $this->platform($user)->id;
    }

    private function physicalPerson(User $user): PhysicalPersonIdentity
    {
        return PhysicalPersonIdentity::query()
            ->where('platform_identity_id', $this->platformId($user))
            ->firstOrFail();
    }

    private function physicalPersonId(User $user): int
    {
        return (int) $this->physicalPerson($user)->id;
    }

    private function assertDashboardLabeledValue(string $html, string $label, string $value): void
    {
        $this->assertMatchesRegularExpression(
            '/<span class="info-label">'.preg_quote($label, '/').'<\/span>\s*<span class="info-value">'.preg_quote($value, '/').'<\/span>/',
            $html
        );
    }
}
