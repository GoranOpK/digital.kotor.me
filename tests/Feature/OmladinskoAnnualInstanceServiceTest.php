<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Support\CompetitionAnnualInstance;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class OmladinskoAnnualInstanceServiceTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private CompetitionAnnualInstance $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = new CompetitionAnnualInstance;
    }

    public function test_first_call_with_annual_budget_and_budget_is_valid_and_findable(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);

        $this->service->validateFirstCall($first);

        $found = $this->service->findFirstCall('omladinsko', 2026);
        $this->assertNotNull($found);
        $this->assertTrue($found->isFirstCall());
        $this->assertTrue($found->usesAnnualCallSequence());
        $this->assertSame($first->id, $found->id);
        $this->assertSame('100000.00', $found->annual_budget);
        $this->assertSame('100000.00', $found->budget);
    }

    public function test_second_call_without_first_is_rejected(): void
    {
        $second = $this->newOmladinskoCall(2, '100000.00', '40000.00', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Drugi Poziv zahtijeva postojeći prvi Poziv iste godišnje instance.');

        $this->service->validateSecondCall($second);
    }

    public function test_second_call_budget_zero_is_rejected(): void
    {
        $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $second = $this->newOmladinskoCall(2, '100000.00', '0.00', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('budget mora biti veći od nule.');

        $this->service->validateSecondCall($second);
    }

    public function test_negative_second_call_budget_is_rejected(): void
    {
        $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $second = $this->newOmladinskoCall(2, '100000.00', '-1.00', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('budget mora biti veći od nule.');

        $this->service->validateSecondCall($second);
    }

    public function test_second_call_above_remaining_after_first_is_rejected(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');
        $second = $this->newOmladinskoCall(2, '100000.00', '40000.01', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Budžet drugog Poziva ne smije premašiti preostala godišnja sredstva.');

        $this->service->validateSecondCall($second);
    }

    public function test_second_call_equal_to_remaining_after_first_is_allowed(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');
        $second = $this->newOmladinskoCall(2, '100000.00', '40000.00', 2026);

        $this->service->validateSecondCall($second);
        $this->assertSame('40000.00', $this->service->remainingAfterFirst('omladinsko', 2026));
    }

    public function test_second_call_below_remaining_after_first_is_allowed(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');
        $second = $this->newOmladinskoCall(2, '100000.00', '25000.00', 2026);

        $this->service->validateSecondCall($second);
        $this->assertTrue($second->isSecondCall());
    }

    public function test_second_first_call_of_same_instance_is_rejected(): void
    {
        $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $duplicate = $this->newOmladinskoCall(1, '100000.00', '100000.00', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Prvi Poziv iste godišnje instance već postoji.');

        $this->service->validateFirstCall($duplicate);
    }

    public function test_second_second_call_of_same_instance_is_rejected(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');
        $this->createOmladinskoCall(2, '100000.00', '40000.00', 2026);
        $duplicate = $this->newOmladinskoCall(2, '100000.00', '10000.00', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Drugi Poziv iste godišnje instance već postoji.');

        $this->service->validateSecondCall($duplicate);
    }

    public function test_third_call_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Treći Poziv nije dozvoljen');

        $this->service->assertCallNumberAllowed(3, 'omladinsko');
    }

    public function test_second_call_annual_budget_different_from_first_is_rejected(): void
    {
        $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $second = $this->newOmladinskoCall(2, '90000.00', '10000.00', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('annual_budget drugog Poziva mora biti isti kao na prvom Pozivu.');

        $this->service->validateSecondCall($second);
    }

    public function test_published_first_call_budget_remains_unchanged_when_second_is_validated(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '80000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');
        $second = $this->newOmladinskoCall(2, '100000.00', '40000.00', 2026);

        $this->service->validateSecondCall($second);

        $this->assertSame('80000.00', $first->fresh()->budget);
        $this->assertSame('80000.00', $this->service->findFirstCall('omladinsko', 2026)?->budget);
    }

    public function test_confirmed_allocation_includes_only_finally_confirmed_approved_applications(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '25000.00');
        $this->createConfirmedApplication($first, '15000.00');
        $this->storeAnnualInstanceApplication($first, 'submitted', 'podrzava_potpuno', '9000.00');
        $this->storeAnnualInstanceApplication($first, 'evaluated', 'podrzava_potpuno', '8000.00');
        $this->storeAnnualInstanceApplication($first, 'rejected', 'podrzava_potpuno', '7000.00');
        $this->storeAnnualInstanceApplication($first, 'approved', 'odbija', '6000.00');
        $this->storeAnnualInstanceApplication($first, 'approved', null, '5000.00');
        $this->storeAnnualInstanceApplication($first, 'approved', 'podrzava_potpuno', null);

        $this->assertSame('40000.00', $this->service->confirmedAllocation($first));
        $this->assertSame('60000.00', $this->service->remainingAfterFirst('omladinsko', 2026));
    }

    public function test_annual_overflow_is_a_controlled_domain_error(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '100000.00');
        $this->createConfirmedApplication($first, '1.00');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Konačno potvrđena raspodjela prvog Poziva premašuje godišnji okvir.');

        $this->service->remainingAfterFirst('omladinsko', 2026);
    }

    public function test_example_one_hundred_thousand_sixty_thousand_forty_thousand(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');

        $this->assertSame('40000.00', $this->service->remainingAfterFirst('omladinsko', 2026));

        $second = $this->newOmladinskoCall(2, '100000.00', '40000.00', 2026);
        $this->service->validateSecondCall($second);
        $this->assertSame('100000.00', $first->fresh()->budget);
    }

    public function test_example_annual_one_hundred_first_budget_eighty_allocation_sixty_remaining_forty(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '80000.00', 2026);
        $this->createConfirmedApplication($first, '60000.00');

        $this->assertSame('40000.00', $this->service->remainingAfterFirst('omladinsko', 2026));
        $this->assertNotSame('20000.00', $this->service->remainingAfterFirst('omladinsko', 2026));

        $second = $this->newOmladinskoCall(2, '100000.00', '40000.00', 2026);
        $this->service->validateSecondCall($second);
        $this->assertSame('80000.00', $first->fresh()->budget);
    }

    public function test_zensko_profile_is_not_subject_to_call_number_and_annual_budget_rules(): void
    {
        $zensko = Competition::create([
            'title' => 'Zensko',
            'description' => 'Opis',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-21',
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => '50000.00',
            'call_number' => null,
            'annual_budget' => null,
            'deadline_days' => 20,
            'published_at' => now(),
            'competition_number' => 'Z-1',
        ]);

        $this->service->validateFirstCall($zensko);
        $this->service->validateSecondCall($zensko);
        $this->service->assertCallNumberAllowed(null, 'zensko');
        $this->service->assertCallNumberAllowed(3, 'zensko');

        $this->assertFalse($zensko->isOmladinskoProfile());
        $this->assertFalse($zensko->isFirstCall());
        $this->assertFalse($zensko->usesAnnualCallSequence());
        $this->assertNull($zensko->call_number);
        $this->assertNull($zensko->annual_budget);
        $this->assertNull($this->service->findFirstCall('zensko', 2026));
        $this->assertSame('Z-1', $zensko->competition_number);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('samo na profil omladinsko');
        $this->service->remainingAfterFirst('zensko', 2026);
    }

    public function test_remaining_after_first_is_zero_when_first_allocation_equals_annual_budget(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '100000.00');

        $this->assertSame('0.00', $this->service->remainingAfterFirst('omladinsko', 2026));
    }

    public function test_second_call_is_rejected_when_remaining_after_first_is_zero(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '100000.00');
        $second = $this->newOmladinskoCall(2, '100000.00', '0.01', 2026);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Budžet drugog Poziva ne smije premašiti preostala godišnja sredstva.');

        $this->service->validateSecondCall($second);
    }

    public function test_partial_support_decision_is_excluded_from_confirmed_allocation(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $this->createConfirmedApplication($first, '25000.00');
        $this->storeAnnualInstanceApplication($first, 'approved', 'podrzava_djelimicno', '10000.00');

        $this->assertSame('25000.00', $this->service->confirmedAllocation($first));
        $this->assertSame('75000.00', $this->service->remainingAfterFirst('omladinsko', 2026));
    }

    public function test_second_call_applications_do_not_enter_first_call_confirmed_allocation(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $second = $this->createOmladinskoCall(2, '100000.00', '40000.00', 2026);
        $this->createConfirmedApplication($first, '15000.00');
        $this->createConfirmedApplication($second, '40000.00');

        $this->assertSame('15000.00', $this->service->confirmedAllocation($first));
        $this->assertSame('85000.00', $this->service->remainingAfterFirst('omladinsko', 2026));
    }

    public function test_other_profile_or_year_applications_do_not_affect_first_call_allocation(): void
    {
        $first = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2026);
        $otherYear = $this->createOmladinskoCall(1, '100000.00', '100000.00', 2027);
        $zensko = Competition::create([
            'title' => 'Zensko druga instanca',
            'description' => 'Opis',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-21',
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => '50000.00',
            'call_number' => null,
            'annual_budget' => null,
            'deadline_days' => 20,
            'published_at' => now(),
            'competition_number' => 'Z-OTHER',
        ]);

        $this->createConfirmedApplication($first, '20000.00');
        $this->createConfirmedApplication($otherYear, '70000.00');
        $this->createConfirmedApplication($zensko, '30000.00');

        $this->assertSame('20000.00', $this->service->confirmedAllocation($first));
        $this->assertSame('80000.00', $this->service->remainingAfterFirst('omladinsko', 2026));
    }

    private function createOmladinskoCall(int $callNumber, string $annualBudget, string $budget, int $year): Competition
    {
        return Competition::create([
            'title' => 'Omladinsko call '.$callNumber.' '.$year,
            'description' => 'Opis',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-21',
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => $callNumber,
            'annual_budget' => $annualBudget,
            'budget' => $budget,
            'deadline_days' => 20,
            'published_at' => now()->subDay(),
        ]);
    }

    private function newOmladinskoCall(int $callNumber, string $annualBudget, string $budget, int $year): Competition
    {
        $competition = new Competition;
        $competition->title = 'Nacrt call '.$callNumber;
        $competition->description = 'Opis';
        $competition->start_date = '2026-07-01';
        $competition->end_date = '2026-07-21';
        $competition->type = 'omladinsko';
        $competition->status = 'draft';
        $competition->year = $year;
        $competition->call_number = $callNumber;
        $competition->annual_budget = $annualBudget;
        $competition->budget = $budget;
        $competition->deadline_days = 20;

        return $competition;
    }

    private function createConfirmedApplication(Competition $competition, string $amount): Application
    {
        return $this->storeAnnualInstanceApplication(
            $competition,
            CompetitionAnnualInstance::CONFIRMED_APPLICATION_STATUS,
            CompetitionAnnualInstance::CONFIRMED_COMMISSION_DECISION,
            $amount
        );
    }

    private function storeAnnualInstanceApplication(
        Competition $competition,
        string $status,
        ?string $decision,
        ?string $amount
    ): Application {
        static $serial = 500;

        $user = $this->makeKorisnik([
            'email' => 'omladinsko-instance-'.$serial.'@example.test',
            'jmb' => $this->nextUniqueValidJmb(),
        ]);
        $serial++;

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan '.$serial,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => $status,
            'commission_decision' => $decision,
            'approved_amount' => $amount,
            'requested_amount' => $amount ?? '1.00',
        ]);
    }

    private function nextUniqueValidJmb(): string
    {
        static $serial = 500;
        static $issued = [];

        do {
            $jmb = $this->validJmb($serial);
            $serial++;
        } while (isset($issued[$jmb]));

        $issued[$jmb] = true;

        return $jmb;
    }
}
