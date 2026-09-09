<?php

namespace Tests\Feature;

use App\Mail\SpisakKandidataMail;
use App\Models\Application;
use App\Models\Competition;
use App\Models\UpNumber;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class CandidatesListEmailDoesNotIncludeJmbTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_candidate_list_email_omits_jmb_for_fizicko_lice_and_keeps_other_fields(): void
    {
        $jmb = $this->validJmb(801);
        $owner = $this->makeKorisnik([
            'email' => 'fl-list-'.uniqid().'@example.test',
            'jmb' => $jmb,
        ]);
        $competition = $this->openCompetitionWithUpNumber('UP-FL-LIST');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'FL plan za spisak',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'submitted',
            'redni_broj' => 1,
            'is_registered' => false,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $jmb,
            'physical_person_phone' => '067000001',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
        ]);

        $body = $this->candidateListBody($competition);

        $this->assertStringContainsString('UP-FL-LIST/1', $body);
        $this->assertStringContainsString('Fizičko lice (nema registrovanu djelatnost)', $body);
        $this->assertStringContainsString('FL plan za spisak', $body);
        $this->assertStringContainsString($owner->name, $body);
        $this->assertStringContainsString('067000001', $body);
        $this->assertStringContainsString($owner->email, $body);
        $this->assertStringNotContainsString($jmb, $body);
        $this->assertStringNotContainsString('JMBG', $body);
        $this->assertStringNotContainsString('JMB', $body);

        $this->assertSame($jmb, $application->fresh()->physical_person_jmbg);
        $this->assertSame($jmb, $owner->fresh()->jmb);
    }

    public function test_candidate_list_email_omits_applicant_jmbg_for_preduzetnica_and_doo(): void
    {
        $preduzetnicaJmb = $this->validJmb(802);
        $dooJmb = $this->validJmb(803);
        $competition = $this->openCompetitionWithUpNumber('UP-BP-LIST');

        $preduzetnica = $this->makeKorisnik([
            'email' => 'pred-list-'.uniqid().'@example.test',
            'jmb' => $preduzetnicaJmb,
            'user_type' => UserType::ENTREPRENEUR,
        ]);
        $dooOwner = $this->makeKorisnik([
            'email' => 'doo-list-'.uniqid().'@example.test',
            'jmb' => $dooJmb,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);

        $preduzetnicaApplication = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $preduzetnica->id,
            'business_plan_name' => 'Preduzetnica plan za spisak',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'submitted',
            'redni_broj' => 1,
            'is_registered' => true,
            'preduzetnik_name' => $preduzetnica->name,
            'preduzetnik_phone' => '067000002',
            'preduzetnik_email' => $preduzetnica->email,
            'applicant_jmbg' => $preduzetnicaJmb,
            'accuracy_declaration' => true,
        ]);

        $dooApplication = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $dooOwner->id,
            'business_plan_name' => 'DOO plan za spisak',
            'applicant_type' => 'doo',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'submitted',
            'redni_broj' => 2,
            'is_registered' => true,
            'doo_name' => $dooOwner->name,
            'doo_phone' => '067000003',
            'doo_email' => $dooOwner->email,
            'applicant_jmbg' => $dooJmb,
            'accuracy_declaration' => true,
        ]);

        $body = $this->candidateListBody($competition);

        $this->assertStringContainsString('Preduzetnica plan za spisak', $body);
        $this->assertStringContainsString('DOO plan za spisak', $body);
        $this->assertStringContainsString($preduzetnica->name, $body);
        $this->assertStringContainsString($dooOwner->name, $body);
        $this->assertStringNotContainsString($preduzetnicaJmb, $body);
        $this->assertStringNotContainsString($dooJmb, $body);
        $this->assertStringNotContainsString('JMBG', $body);
        $this->assertStringNotContainsString('JMB', $body);

        $this->assertSame($preduzetnicaJmb, $preduzetnicaApplication->fresh()->applicant_jmbg);
        $this->assertSame($dooJmb, $dooApplication->fresh()->applicant_jmbg);
    }

    private function candidateListBody(Competition $competition): string
    {
        $mail = new SpisakKandidataMail($competition->fresh());

        return $mail->body."\n".$mail->render();
    }

    private function openCompetitionWithUpNumber(string $upNumber): Competition
    {
        $competition = Competition::create([
            'title' => 'Test konkurs spisak '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => $upNumber,
        ]);

        return $competition->fresh();
    }
}
