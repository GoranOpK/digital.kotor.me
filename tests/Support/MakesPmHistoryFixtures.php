<?php

namespace Tests\Support;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;

trait MakesPmHistoryFixtures
{
    public function makePmHistoryFixture(string $type, bool $registered): array
    {
        $user = User::factory()->create(['role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active', 'first_name' => 'Profil', 'last_name' => 'Korisnik', 'address' => 'Njegoseva 1', 'city' => 'Kotor']);
        $competition = Competition::create(['title' => 'PM istorija', 'description' => 'Test', 'type' => 'omladinsko',
            'status' => 'published', 'year' => 2026, 'start_date' => now()->subDays(2), 'end_date' => now()->addDays(18),
            'published_at' => now()->subDays(2), 'deadline_days' => 20]);
        $app = Application::create(['user_id' => $user->id, 'competition_id' => $competition->id, 'status' => 'draft',
            'business_plan_name' => 'Projekat', 'business_stage' => 'zapocinjanje', 'business_area' => 'Usluge',
            'applicant_type' => $type, 'company_legal_form' => $type === 'privredno_drustvo' ? 'doo' : null, 'is_registered' => $registered, 'accuracy_declaration' => true,
            'physical_person_name' => 'Sacuvana osoba', 'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000', 'physical_person_email' => $user->email, 'physical_person_address' => 'Njegoseva 1, Kotor',
            'preduzetnik_name' => 'Sacuvani preduzetnik', 'preduzetnik_phone' => '067000000', 'preduzetnik_email' => $user->email,
            'preduzetnik_address' => 'Njegoseva 1, Kotor', 'doo_name' => 'Podnosilac', 'doo_phone' => '067000000',
            'doo_email' => $user->email, 'doo_address' => 'Njegoseva 1, Kotor', 'founder_name' => 'Osnivac', 'director_name' => 'Direktor',
            'company_seat' => 'Njegoseva 1, Kotor', 'registration_form' => $registered ? ($type === 'privredno_drustvo' ? \App\Support\UserType::LIMITED_LIABILITY_COMPANY : 'Preduzetnik') : null,
            'pib' => $registered ? '12345672' : null, 'crps_number' => $registered ? ($type === 'privredno_drustvo' ? '50000001' : '10000001') : null,
            'applicant_jmbg' => '0101990123456']);
        $plan = BusinessPlan::create(['application_id' => $app->id, 'business_idea_name' => 'Projekat', 'applicant_name' => 'Podnosilac M2',
            'applicant_jmbg' => '0101990123456', 'applicant_address' => 'Njegoseva 1, Kotor', 'applicant_phone' => '067000000',
            'applicant_email' => $user->email, 'company_name' => $registered && $type === 'privredno_drustvo' ? 'Primjer DOO' : 'Slobodni poslovni naziv', 'summary' => 'Opis', 'finances_notice_confirmed' => true]);
        return [$user, $app, $plan];
    }
}
