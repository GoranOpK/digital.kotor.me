<?php

namespace Tests\Feature\Identity;

use App\Enums\PaymentAvailabilityOutcome;
use App\Identity\CanonicalIdentityWriter;
use App\Models\Competition;
use App\Services\Payments\PaymentAvailabilityService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class SwitchOffReadZeroTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));
    }

    public function test_profile_display_uses_legacy_name_while_canonical_differs(): void
    {
        $user = $this->makeKorisnik(['first_name' => 'Ana', 'name' => 'Ana Anić']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['firstName' => 'ZxCanonicalOnlyName', 'lastName' => 'Canonical'],
        ]));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Ana', false)
            ->assertDontSee('ZxCanonicalOnlyName', false);
    }

    public function test_ep_availability_uses_legacy_user_type_while_canonical_differs(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));

        $type = \App\Models\PaymentType::factory()->create([
            'code' => 'syn-step2-readzero',
            'name' => 'Synthetic read-zero',
            'is_active' => true,
        ]);
        $this->grantTypeAvailability($type, UserType::PHYSICAL_PERSON, 'resident');

        $engine = $this->app->make(PaymentAvailabilityService::class);

        $this->assertSame(PaymentAvailabilityOutcome::Available, $engine->evaluateType($user->fresh(), $type));
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->fresh()->user_type);
    }

    public function test_kn_applicant_mapping_uses_legacy_user_type_while_canonical_differs(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));

        $competition = Competition::create([
            'title' => 'Step2 read-zero',
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('applicantType', 'fizicko_lice');
    }
}
