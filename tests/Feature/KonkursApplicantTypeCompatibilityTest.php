<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class KonkursApplicantTypeCompatibilityTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Existing Konkurs mapping is a competition classification, not the platform user model.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function existingApplicantMappingProvider(): array
    {
        return [
            'preduzetnik stays preduzetnica' => [UserType::ENTREPRENEUR, 'preduzetnica'],
            'fizicko lice stays fizicko_lice' => [UserType::PHYSICAL_PERSON, 'fizicko_lice'],
            'doo stays doo' => [UserType::LIMITED_LIABILITY_COMPANY, 'doo'],
            'nvo stays ostalo' => [UserType::NGO_ASSOCIATION, 'ostalo'],
            'sportska organizacija stays ostalo' => [UserType::SPORTS_ORGANIZATION, 'ostalo'],
            'legacy association stays ostalo' => [UserType::LEGACY_ASSOCIATION_BUNDLE, 'ostalo'],
        ];
    }

    #[DataProvider('existingApplicantMappingProvider')]
    public function test_existing_competition_applicant_type_mapping_is_unchanged(
        string $userType,
        string $expectedApplicantType
    ): void {
        $user = $this->makeKorisnik([
            'user_type' => $userType,
            'residential_status' => UserType::isNaturalPerson($userType) ? 'resident' : null,
            'pib' => UserType::isLegalEntity($userType) ? '12345678' : null,
            'company_name' => UserType::isLegalEntity($userType) ? 'Subjekt' : null,
            'jmb' => UserType::isNaturalPerson($userType) ? '0202990123456' : null,
        ]);

        $competition = Competition::create([
            'title' => 'Kompatibilnost '.$userType,
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
            ->assertViewHas('applicantType', $expectedApplicantType);
    }
}
