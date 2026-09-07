<?php

namespace Tests\Feature\Identity;

use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class SwitchOffWriteZeroTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
    }

    public function test_registration_does_not_write_canonical_identity(): void
    {
        $this->post('/register', $this->registrationHttpPayload('step2.writezero@example.com', [
            'jmb' => $this->validJmb(3),
        ]))->assertRedirect();

        $this->assertNotNull(User::query()->where('email', 'step2.writezero@example.com')->first());
        $this->assertCanonicalTablesEmpty();
    }

    public function test_profile_identity_update_does_not_write_canonical_identity(): void
    {
        $user = $this->makeKorisnik();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Petrović',
                'email' => $user->email,
                'phone' => '+38267111222',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
                'jmb' => $user->jmb,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame('Petar', $user->fresh()->first_name);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_d10_residential_declaration_does_not_write_canonical_identity(): void
    {
        $this->enableEpIdentityFlows();
        $user = $this->makeKorisnik(['residential_status' => null]);
        $type = \App\Models\PaymentType::factory()->create([
            'code' => 'syn-step2-d10',
            'name' => 'Synthetic user-flow type',
            'is_active' => true,
        ]);
        $account = \App\Models\PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-STEP2-D10-0000000001',
            'is_active' => true,
        ]);
        $this->grantAvailability($type, $account, UserType::PHYSICAL_PERSON, 'resident');

        $this->actingAs($user)->get(route('payments.index'));

        $this->actingAs($user)
            ->post(route('payments.declaration.store'), [
                'residential_status' => 'resident',
            ])
            ->assertRedirect('/payments');

        $this->assertSame('resident', $user->fresh()->residential_status);
        $this->assertCanonicalTablesEmpty();
    }

    private function assertCanonicalTablesEmpty(): void
    {
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
        $this->assertSame(0, ForeignBranchRepresentative::query()->count());
    }
}
