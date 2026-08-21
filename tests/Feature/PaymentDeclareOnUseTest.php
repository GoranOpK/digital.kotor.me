<?php

namespace Tests\Feature;

use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class PaymentDeclareOnUseTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_physical_person_with_null_residential_sees_declaration(): void
    {
        $user = $this->makeKorisnik(['residential_status' => null]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertRedirect(route('payments.declaration.create'));

        $this->actingAs($user)
            ->get(route('payments.declaration.create'))
            ->assertOk()
            ->assertSee('Odaberite status rezidentnosti')
            ->assertSee('Rezident')
            ->assertSee('Nerezident');
    }

    public function test_entrepreneur_with_null_residential_sees_declaration(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => null,
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertRedirect(route('payments.declaration.create'));
    }

    public function test_legal_entity_does_not_see_declaration(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'company_name' => 'Syn DOO',
            'pib' => '12345678',
            'jmb' => null,
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertDontSee('Odaberite status rezidentnosti');
    }

    public function test_valid_declaration_persists_and_returns_to_payments(): void
    {
        $user = $this->makeKorisnik(['residential_status' => null]);
        $type = \App\Models\PaymentType::factory()->create([
            'code' => 'syn-decl',
            'name' => 'Synthetic user-flow type',
            'is_active' => true,
        ]);
        $account = \App\Models\PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-DECL-00000000000001',
            'is_active' => true,
        ]);
        $this->grantAvailability($type, $account, UserType::PHYSICAL_PERSON, 'resident');

        $this->actingAs($user)->get(route('payments.index'));

        $this->actingAs($user)
            ->post(route('payments.declaration.store'), [
                'residential_status' => 'resident',
                'jmb' => 'hacked',
            ])
            ->assertRedirect('/payments');

        $this->assertSame('resident', $user->fresh()->residential_status);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Synthetic user-flow type');
    }

    public function test_invalid_declaration_is_rejected(): void
    {
        $user = $this->makeKorisnik(['residential_status' => null]);

        $this->actingAs($user)
            ->from(route('payments.declaration.create'))
            ->post(route('payments.declaration.store'), [
                'residential_status' => 'ex-non-resident',
            ])
            ->assertSessionHasErrors('residential_status');

        $this->assertNull($user->fresh()->residential_status);
    }
}
