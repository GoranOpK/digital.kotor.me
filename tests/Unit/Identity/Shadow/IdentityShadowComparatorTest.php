<?php

namespace Tests\Unit\Identity\Shadow;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Identity\Shadow\Comparators\DashboardDisplayComparator;
use App\Identity\Shadow\Comparators\EpAvailabilityComparator;
use App\Identity\Shadow\Comparators\KnApplicantTypeComparator;
use App\Identity\Shadow\Comparators\KnApplicationPrefillComparator;
use App\Identity\Shadow\Comparators\ProfileDisplayComparator;
use App\Identity\Shadow\IdentityShadowStatus;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Support\ApplicationCreateApplicantTypeDefault;
use App\Support\CompetitionApplicantType;
use App\Support\KotorAddress;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class IdentityShadowComparatorTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    private int $jmbSerial = 70;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_ep_availability_match_and_forced_mismatch(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        [$type] = $this->syntheticUsablePair($user, 'syn-shadow-ep', 'SYN-SHADOW-EP-00000001');
        $type->load(['availabilities', 'accounts.availabilities']);
        $catalog = collect([$type]);

        $match = (new EpAvailabilityComparator)->compare($user, $snapshot, $catalog);
        $this->assertSame(IdentityShadowStatus::MATCH, $match->status);
        $this->assertSame(1, $match->coverage['evaluated_type_count']);
        $this->assertSame(1, $match->coverage['evaluated_account_count']);
        $this->assertSame(2, $match->coverage['compared_decision_count']);

        $mismatched = (new EpAvailabilityComparator)->compare(
            $user,
            $this->withResidential($snapshot, PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT),
            $catalog,
        );
        $this->assertSame(IdentityShadowStatus::MISMATCH, $mismatched->status);
        $this->assertContains('eligibility', $mismatched->reasonCodes);
        $this->assertSame(2, $mismatched->coverage['compared_decision_count']);
    }

    public function test_ep_availability_empty_catalog_is_not_evaluable(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);

        $result = (new EpAvailabilityComparator)->compare($user, $snapshot, collect());

        $this->assertSame(IdentityShadowStatus::NOT_EVALUABLE, $result->status);
        $this->assertContains('no_catalog_decisions', $result->reasonCodes);
        $this->assertSame(0, $result->coverage['evaluated_type_count']);
        $this->assertSame(0, $result->coverage['evaluated_account_count']);
        $this->assertSame(0, $result->coverage['compared_decision_count']);
        $this->assertNotSame(IdentityShadowStatus::MATCH, $result->status);
    }

    public function test_kn_applicant_type_match_and_forced_mismatch(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $comparator = new KnApplicantTypeComparator;

        $this->assertSame('fizicko_lice', CompetitionApplicantType::fromUserType(UserType::PHYSICAL_PERSON));
        $this->assertSame('fizicko_lice', CompetitionApplicantType::fromUserType('Rezident'));
        $this->assertSame('preduzetnica', CompetitionApplicantType::fromUserType('Preduzetnik'));
        $this->assertSame('doo', CompetitionApplicantType::fromUserType('DOO'));
        $this->assertSame('fizicko_lice', ApplicationCreateApplicantTypeDefault::forUser(UserType::PHYSICAL_PERSON, 'resident'));
        $this->assertSame(
            CompetitionApplicantType::fromUserType(UserType::PHYSICAL_PERSON),
            ApplicationCreateApplicantTypeDefault::forUser(UserType::PHYSICAL_PERSON, 'resident')
        );
        $this->assertSame(IdentityShadowStatus::MATCH, $comparator->compare($user, $snapshot)->status);

        $user->user_type = UserType::LIMITED_LIABILITY_COMPANY;
        $this->assertSame(IdentityShadowStatus::MISMATCH, $comparator->compare($user, $snapshot)->status);
        $this->assertContains('classification', $comparator->compare($user, $snapshot)->reasonCodes);
    }

    public function test_kn_application_prefill_match_forced_mismatch_leftover_pib_and_no_invented_trim(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $comparator = new KnApplicationPrefillComparator;

        $this->assertSame(IdentityShadowStatus::MATCH, $comparator->compare($user, $snapshot)->status);

        $user->pib = '12345672';
        $leftover = $comparator->compare($user, $snapshot);
        $this->assertSame(IdentityShadowStatus::MATCH, $leftover->status);
        $this->assertContains('not_in_canonical_contract', $leftover->reasonCodes);

        $user->pib = null;
        $user->jmb = $this->validJmb($this->jmbSerial++);
        $this->assertSame(IdentityShadowStatus::MISMATCH, $comparator->compare($user, $snapshot)->status);
        $this->assertContains('prefill_dto', $comparator->compare($user, $snapshot)->reasonCodes);

        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $user->phone = ' '.$user->phone;
        $this->assertSame(IdentityShadowStatus::MISMATCH, $comparator->compare($user, $snapshot)->status);
        $this->assertContains('phone', $comparator->compare($user, $snapshot)->fieldCategories);
        $this->assertSame(
            KotorAddress::formatStreetAndCity($snapshot->streetAndNumber, $snapshot->city),
            KotorAddress::formatStreetAndCity($snapshot->physicalPerson?->streetAndNumber, $snapshot->physicalPerson?->city)
        );
    }

    public function test_profile_display_match_forced_mismatch_and_no_invented_trim(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $comparator = new ProfileDisplayComparator;

        $this->assertSame(IdentityShadowStatus::MATCH, $comparator->compare($user, $snapshot)->status);

        $user->first_name = 'Changed';
        $this->assertSame(IdentityShadowStatus::MISMATCH, $comparator->compare($user, $snapshot)->status);

        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $user->address = $user->address.' ';
        $this->assertSame(IdentityShadowStatus::MISMATCH, $comparator->compare($user, $snapshot)->status);
        $this->assertContains('address', $comparator->compare($user, $snapshot)->fieldCategories);
    }

    public function test_dashboard_display_match_forced_mismatch_and_n_a_semantics(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $comparator = new DashboardDisplayComparator;

        $this->assertSame(IdentityShadowStatus::MATCH, $comparator->compare($user, $snapshot)->status);
        $this->assertSame('Fizičko lice (Rezident)', $this->dashboardLabel($user));

        $user->city = 'Budva';
        $this->assertSame(IdentityShadowStatus::MISMATCH, $comparator->compare($user, $snapshot)->status);
        $this->assertContains('city', $comparator->compare($user, $snapshot)->fieldCategories);
    }

    public function test_create_form_default_applicant_type_for_fl_is_preduzetnica_not_fizicko_lice(): void
    {
        $user = $this->flUser();
        $snapshot = $this->projectedSnapshot($user);
        $result = (new KnApplicationPrefillComparator)->compare($user, $snapshot);
        $this->assertSame(IdentityShadowStatus::MATCH, $result->status);
        $this->assertNotContains('fizicko_lice', $result->reasonCodes);
    }

    private function flUser(): \App\Models\User
    {
        return $this->makeKorisnik([
            'jmb' => $this->validJmb($this->jmbSerial++),
            'email' => 'shadow-cmp-'.uniqid('', true).'@example.test',
        ]);
    }

    private function projectedSnapshot(\App\Models\User $user): IdentitySnapshot
    {
        $user->load('role');
        $classified = (new IdentityCensusService)->classify($user);
        $snapshot = (new IdentityBackfillProjector)->project($user, $classified);
        $this->assertNotNull($snapshot);

        return $snapshot;
    }

    private function withResidential(IdentitySnapshot $snapshot, string $residential): IdentitySnapshot
    {
        $fl = $snapshot->physicalPerson;
        $this->assertNotNull($fl);

        return new IdentitySnapshot(
            userId: $snapshot->userId,
            isRegisteredSubject: $snapshot->isRegisteredSubject,
            subjectType: $snapshot->subjectType,
            mobilePhone: $snapshot->mobilePhone,
            streetAndNumber: $snapshot->streetAndNumber,
            city: $snapshot->city,
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: $fl->firstName,
                lastName: $fl->lastName,
                residentialStatus: $residential,
                streetAndNumber: $fl->streetAndNumber,
                city: $fl->city,
                idDocumentType: $fl->idDocumentType,
                jmb: $fl->jmb,
                passportNumber: $fl->passportNumber,
                residenceCountryCode: $fl->residenceCountryCode,
                isEntrepreneur: $fl->isEntrepreneur,
                entrepreneurBusinessName: $fl->entrepreneurBusinessName,
                pib: $fl->pib,
                crpsNumber: $fl->crpsNumber,
            ),
        );
    }

    private function dashboardLabel(\App\Models\User $user): string
    {
        $isPhysicalPerson = UserType::isNaturalPerson($user->user_type);
        $isResident = $user->residential_status === 'resident';
        if ($isPhysicalPerson && $isResident) {
            return 'Fizičko lice (Rezident)';
        }

        return (string) ($user->user_type ?? 'Pravno lice');
    }
}
