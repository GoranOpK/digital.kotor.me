<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MmspP1bProfileAndContinueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_new_p1b_copies_the_legal_entity_profile_and_keeps_later_edits(): void
    {
        $owner = $this->legalEntity();
        $application = $this->start($owner, $this->competition(), CanonicalLegalForm::DRUSTVO);

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame('Profil d.o.o.', $form->company_name);
        $this->assertSame('Ana Anić', $form->person_name);
        $this->assertSame('Njegoševa 1, Kotor', $form->address);
        $this->assertSame('067111222', $form->phone);
        $this->assertSame('doo-profil@example.com', $form->email);
        $this->assertNull($form->activity_code);
        $this->assertNull($form->registration_date);
        $this->assertNull($form->bank_account);
        $this->assertSame('02345678', $application->fresh()->pib);
        $this->assertSame('Profil d.o.o.', $owner->fresh()->company_name);

        $page = $this->actingAs($owner)
            ->get(route('applications.canonical-p1.edit', $application))
            ->assertOk();
        $page->assertSee('value="Profil d.o.o."', false);
        $page->assertSee('value="Ana Anić"', false);
        $page->assertSee('value="Njegoševa 1, Kotor"', false);
        $page->assertSee('value="067111222"', false);
        $page->assertSee('value="doo-profil@example.com"', false);
        $page->assertSee('value="02345678"', false);
        $page->assertDontSee('name="crps_number"', false);
        $this->assertButtonLabel($page->getContent(), 'Sačuvaj kao nacrt', false);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), [
                'company_name' => 'Izmijenjeno d.o.o.',
                'person_name' => 'Ana Anić',
                'address' => 'Njegoševa 1, Kotor',
                'phone' => '067111222',
                'email' => 'doo-profil@example.com',
                'pib' => '02345678',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $owner->forceFill(['company_name' => 'Drugo društvo', 'phone' => '068000000'])->save();
        $application->refresh();
        $form->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertSame('Izmijenjeno d.o.o.', $form->company_name);
        $this->assertSame('067111222', $form->phone);
        $this->assertSame('Drugo društvo', $owner->fresh()->company_name);

        $reopened = $this->actingAs($owner)
            ->get(route('applications.canonical-p1.edit', $application))
            ->assertOk();
        $reopened->assertSee('value="Izmijenjeno d.o.o."', false);
        $reopened->assertDontSee('value="Drugo društvo"', false);
        $this->assertButtonLabel($reopened->getContent(), 'Sačuvaj kao nacrt', false);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->completePayload('Izmijenjeno d.o.o.'))
            ->assertRedirect(route('applications.canonical-p2.edit', $application))
            ->assertSessionHas('success', 'Obrazac P1b je sačuvan. Nastavite sa Planom ulaganja (Obrazac P2).');

        $application->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertSame(0, FinalApplicationClaim::query()->where('application_id', $application->id)->count());
        $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))->assertOk()->assertSee('Obrazac P2');

        $ready = $this->actingAs($owner)
            ->get(route('applications.canonical-p1.edit', $application))
            ->assertOk();
        $this->assertButtonLabel($ready->getContent(), 'Sačuvaj i nastavi na plan ulaganja', true);

        $this->actingAs($owner)
            ->from(route('applications.canonical-p1.edit', $application))
            ->put(route('applications.canonical-p1.update', $application), $this->completePayload(''))
            ->assertRedirect(route('applications.canonical-p1.edit', $application))
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
        $draftAgain = $this->actingAs($owner)
            ->get(route('applications.canonical-p1.edit', $application))
            ->assertOk();
        $this->assertButtonLabel($draftAgain->getContent(), 'Sačuvaj kao nacrt', false);
    }

    public function test_p1a_is_not_seeded_from_a_company_profile_and_other_programmes_stay_away(): void
    {
        $owner = $this->legalEntity();
        $p1a = $this->start($owner, $this->competition(), CanonicalLegalForm::PREDUZETNIK);
        $form = CanonicalApplicantForm::query()->where('application_id', $p1a->id)->firstOrFail();
        $this->assertNull($form->company_name);
        $this->assertNull($form->person_name);
        $this->assertNull($form->address);
        $this->assertNull($p1a->fresh()->pib);

        $page = $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $p1a))->assertOk();
        $page->assertSee('id="p1a-save-button"', false);
        $page->assertDontSee('id="p1b-save-button"', false);

        $person = User::factory()->create([
            'email' => 'fizicko-p1b@example.com',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => UserType::PHYSICAL_PERSON,
            'company_name' => 'Ne smije se prepisati',
            'first_name' => 'Petar',
            'last_name' => 'Petrović',
            'pib' => '10987654',
            'phone' => '067333444',
            'address' => 'Njegoševa 2',
            'city' => 'Kotor',
        ]);
        $empty = $this->start($person, $this->competition(), CanonicalLegalForm::DRUSTVO);
        $emptyForm = CanonicalApplicantForm::query()->where('application_id', $empty->id)->firstOrFail();
        $this->assertNull($emptyForm->company_name);
        $this->assertNull($emptyForm->person_name);
        $this->assertNull($emptyForm->address);
        $this->assertNull($emptyForm->phone);
        $this->assertNull($emptyForm->email);
        $this->assertNull($empty->fresh()->pib);

        foreach ([CompetitionProfileResolver::TYPE_WOMEN, CompetitionProfileResolver::TYPE_YOUTH] as $type) {
            $call = $this->competition($type);
            $this->actingAs($owner)
                ->post(route('applications.canonical.start', $call), ['legal_form' => CanonicalLegalForm::DRUSTVO])
                ->assertNotFound();
        }
        $this->assertSame(2, Application::query()->count());
    }

    private function assertButtonLabel(string $html, string $label, bool $continues): void
    {
        $name = $continues
            ? '(?=[^>]*name="continue_to_plan")'
            : '(?![^>]*name="continue_to_plan")';
        $this->assertMatchesRegularExpression(
            '/id="p1b-save-button"'.$name.'[^>]*>\s*'.preg_quote($label, '/').'\s*</',
            $html
        );
    }

    private function legalEntity(): User
    {
        return User::factory()->create([
            'email' => 'doo-profil@example.com',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Profil d.o.o.',
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'pib' => '02345678',
            'phone' => '067111222',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }

    private function competition(string $type = CompetitionProfileResolver::TYPE_CANONICAL): Competition
    {
        return Competition::create([
            'title' => 'MMSP P1b '.$type.' '.uniqid(),
            'description' => 'Test',
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->format('Y'),
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }

    private function start(User $owner, Competition $call, string $legalForm): Application
    {
        $before = Application::query()->where('user_id', $owner->id)->pluck('id')->all();
        $this->actingAs($owner)
            ->post(route('applications.canonical.start', $call), ['legal_form' => $legalForm])
            ->assertSessionHasNoErrors();

        return Application::query()->where('user_id', $owner->id)->whereNotIn('id', $before)->sole();
    }

    /**
     * @return array<string, string>
     */
    private function completePayload(string $company): array
    {
        return [
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'company_name' => $company,
            'person_name' => 'Ana Anić',
            'address' => 'Njegoševa 1, 85330 Kotor',
            'registration_date' => '2020-03-15',
            'activity_code' => '56.10',
            'employee_count' => '2',
            'bank_account' => '510-0000000000001-11',
            'phone' => '067111222',
            'email' => 'doo-profil@example.com',
            'pib' => '02345678',
            'accuracy_confirmed' => '1',
            'continue_to_plan' => '1',
        ];
    }
}
