<?php

namespace Tests\Unit\Identity;

use App\Identity\Runtime\ExistingSubjectIdentityEligibility;
use App\Identity\Runtime\ExistingSubjectIdentityEligibilityResult;
use App\Identity\CanonicalIdentityWriter;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class ExistingSubjectIdentityEligibilityTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private ExistingSubjectIdentityEligibility $eligibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => false,
            'identity.canonical_write' => false,
            'identity.identity_write_freeze' => false,
        ]);
        $this->eligibility = new ExistingSubjectIdentityEligibility;
    }

    public function test_unauthenticated_is_denied(): void
    {
        $result = $this->eligibility->inspect(null);

        $this->assertFalse($result->eligible);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_UNAUTHENTICATED, $result->denyReason);
    }

    public function test_inactive_user_is_denied(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser(['activation_status' => 'deactivated']);

        $result = $this->eligibility->inspect($user);

        $this->assertFalse($result->eligible);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_INACTIVE, $result->denyReason);
    }

    public function test_staff_and_superadmin_are_denied_even_with_doo_leftover(): void
    {
        $this->enableCanonicalHttp();

        $admin = $this->makeDooUser([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
        ]);
        $superadmin = $this->makeDooUser([
            'role_id' => Role::where('name', 'superadmin')->firstOrFail()->id,
        ]);

        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_STAFF, $this->eligibility->inspect($admin)->denyReason);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_STAFF, $this->eligibility->inspect($superadmin)->denyReason);
    }

    public function test_flags_off_and_freeze_on_are_denied(): void
    {
        $user = $this->makeDooUser();
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_FLAGS, $this->eligibility->inspect($user)->denyReason);

        $this->enableCanonicalHttp();
        config(['identity.identity_write_freeze' => true]);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_FLAGS, $this->eligibility->inspect($user)->denyReason);
    }

    public function test_eligible_doo_and_preduzetnik_branches(): void
    {
        $this->enableCanonicalHttp();

        $doo = $this->eligibility->inspect($this->makeDooUser());
        $this->assertTrue($doo->eligible);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::BRANCH_DOO, $doo->branch);

        $pred = $this->eligibility->inspect($this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => $this->uniqueValidPib(),
            'jmb' => $this->validJmb($this->jmbSeq++),
        ]));
        $this->assertTrue($pred->eligible);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::BRANCH_PREDUZETNIK, $pred->branch);
    }

    public function test_ordinary_physical_person_and_unsupported_and_account_only(): void
    {
        $this->enableCanonicalHttp();

        $fl = $this->eligibility->inspect($this->makeKorisnik(['jmb' => $this->validJmb($this->jmbSeq++)]));
        $this->assertFalse($fl->eligible);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_ORDINARY_FL, $fl->denyReason);

        $ad = $this->eligibility->inspect($this->makeKorisnik([
            'user_type' => UserType::JOINT_STOCK_COMPANY,
            'jmb' => $this->validJmb($this->jmbSeq++),
        ]));
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_UNSUPPORTED, $ad->denyReason);

        $account = $this->eligibility->inspect($this->makeKorisnik(['user_type' => null, 'jmb' => null]));
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::DENY_ACCOUNT_ONLY, $account->denyReason);
    }

    public function test_current_graph_and_malformed_graph_are_distinguished(): void
    {
        $this->enableCanonicalHttp();
        $currentUser = $this->makeDooUser();
        (new CanonicalIdentityWriter)->createForUser($currentUser, $this->plSnapshot($currentUser));

        $current = $this->eligibility->inspect($currentUser);
        $this->assertFalse($current->eligible);
        $this->assertTrue($current->isCurrent());
        $this->assertFalse($current->isMalformed());

        $malformedUser = $this->makeDooUser();
        PlatformIdentity::query()->create([
            'user_id' => $malformedUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000002',
        ]);

        $malformed = $this->eligibility->inspect($malformedUser);
        $this->assertFalse($malformed->eligible);
        $this->assertTrue($malformed->isMalformed());
        $this->assertFalse($malformed->isCurrent());
    }

    public function test_does_not_classify_from_request_input(): void
    {
        $this->enableCanonicalHttp();
        request()->merge([
            'user_type' => UserType::PHYSICAL_PERSON,
            'subject_type' => 'physical_person',
            'branch' => 'preduzetnik',
        ]);

        $result = $this->eligibility->inspect($this->makeDooUser());

        $this->assertTrue($result->eligible);
        $this->assertSame(ExistingSubjectIdentityEligibilityResult::BRANCH_DOO, $result->branch);
    }

    private function enableCanonicalHttp(): void
    {
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
    }

    private int $pibSeq = 1;

    private int $jmbSeq = 1;

    private function uniqueValidPib(): string
    {
        $prefix = str_pad((string) $this->pibSeq++, 7, '0', STR_PAD_LEFT);
        $product = 10;
        for ($i = 0; $i < 7; $i++) {
            $product = ($product + (int) $prefix[$i]) % 10;
            if ($product === 0) {
                $product = 10;
            }
            $product = ($product * 2) % 11;
        }

        return $prefix.((11 - $product) % 10);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeDooUser(array $overrides = []): User
    {
        return $this->makeKorisnik(array_merge([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => $this->uniqueValidPib(),
            'first_name' => 'Nosioc',
            'last_name' => 'Naloga',
            'jmb' => null,
            'residential_status' => 'resident',
        ], $overrides));
    }
}
