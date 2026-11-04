<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmBusinessPlanFormTitleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pm_form_shows_m2_on_first_open_and_on_a_saved_draft(): void
    {
        [$owner, $application] = $this->application('omladinsko');
        $this->assertTrue($application->isObrazacComplete());
        $this->assertNull($application->businessPlan);

        $first = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac M2</div>', $first);
        $this->assertStringNotContainsString('class="obrazac-1a-1b">Obrazac 2</div>', $first);
        $this->assertPmMemorandum($first, '—/—');

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Sačuvana PM ideja',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasNoErrors();

        $saved = $application->fresh()->businessPlan;
        $this->assertNotNull($saved);
        $this->assertSame('Sačuvana PM ideja', $saved->business_idea_name);
        $this->assertSame('draft', $application->fresh()->status);

        $again = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac M2</div>', $again);
        $this->assertStringContainsString('Sačuvana PM ideja', $again);
        $this->assertSame('Sačuvana PM ideja', $application->fresh()->businessPlan->business_idea_name);
    }

    public function test_zensko_form_keeps_obrazac_2_title(): void
    {
        [$owner, $application] = $this->application('zensko');

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac 2</div>', $html);
        $this->assertStringNotContainsString('Obrazac M2', $html);
        $this->assertStringNotContainsString('Broj prijave:', $html);
        $this->assertStringNotContainsString('class="obrazac-zaglavlje obrazac-zaglavlje-pm"', $html);
        $this->assertSame(1, substr_count($html, '<div class="obrazac-zaglavlje">'));
        $this->assertStringContainsString('FORMA ZA BIZNIS PLAN', $html);
        $this->assertStringContainsString('1. Naziv biznis ideje:', $html);
    }

    public function test_pm_m2_shows_article_19_note_and_zp_keeps_its_finance_note(): void
    {
        [$pmOwner, $pm] = $this->application('omladinsko');
        $pmHtml = $this->actingAs($pmOwner)
            ->get(route('applications.business-plan.create', $pm))
            ->assertOk()
            ->getContent();

        $this->assertFinanceQuestionRemains($pmHtml);
        $this->assertArticle19Notice($pmHtml);
        $this->assertZpFinanceNoteAbsent($pmHtml);
        $this->assertNoticeConfirmationUnlocksFollowingQuestions($pmHtml);

        $this->actingAs($pmOwner)
            ->post(route('applications.business-plan.store', $pm), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'PM finansije',
                'required_amount' => '1500.50',
                'requested_amount' => '800.25',
            ])
            ->assertRedirect(route('applications.show', $pm))
            ->assertSessionHasNoErrors();

        $saved = $pm->fresh()->businessPlan;
        $this->assertNotNull($saved);
        $this->assertSame('1500.50', number_format((float) $saved->required_amount, 2, '.', ''));
        $this->assertSame('800.25', number_format((float) $saved->requested_amount, 2, '.', ''));

        $reopened = $this->actingAs($pmOwner)
            ->get(route('applications.business-plan.create', $pm))
            ->assertOk()
            ->getContent();
        $this->assertFinanceQuestionRemains($reopened);
        $this->assertArticle19Notice($reopened);
        $this->assertZpFinanceNoteAbsent($reopened);
        $this->assertStringContainsString('value="1500.50"', $reopened);
        $this->assertStringContainsString('value="800.25"', $reopened);

        [$zpOwner, $zp] = $this->application('zensko');
        $zpHtml = $this->actingAs($zpOwner)
            ->get(route('applications.business-plan.create', $zp))
            ->assertOk()
            ->getContent();
        $this->assertFinanceQuestionRemains($zpHtml);
        $this->assertZpFinanceNote($zpHtml);
        $this->assertNoticeConfirmationUnlocksFollowingQuestions($zpHtml);
        $zpNotice = $this->financesNoticeHtml($zpHtml);
        $this->assertStringNotContainsString('Napomena — član 19 Odluke', $zpNotice);
        $this->assertStringNotContainsString('preduzetništvu mladih', $zpNotice);
        $this->assertStringNotContainsString('<strong>30%</strong>', $zpNotice);
        $this->assertStringNotContainsString('<strong>15%</strong>', $zpNotice);
    }

    private function assertFinanceQuestionRemains(string $html): void
    {
        $this->assertStringContainsString(
            '21. Potrebna sredstva — koliki iznos Vam je potreban za realizaciju biznis ideje?',
            $html
        );
        $this->assertStringContainsString('name="required_amount"', $html);
        $this->assertStringContainsString('name="requested_amount"', $html);
        $this->assertStringContainsString('name="funding_sources_table[0][type]"', $html);
        $this->assertStringContainsString('name="funding_sources_table[0][price]"', $html);
    }

    private function assertArticle19Notice(string $html): void
    {
        $notice = $this->financesNoticeHtml($html);
        foreach ([
            'Napomena — član 19 Odluke',
            'Fizičko lice, preduzetnik ili privredno društvo može konkurisati za cjelokupan iznos ili dio sredstava potrebnih za realizaciju biznis plana.',
            'Maksimalan iznos dodijeljenih sredstava za jedan biznis plan ne može biti veći od:',
            '<strong>30%</strong> ukupnog iznosa definisanog u Javnom konkursu za tekuću godinu — za start-up (inovativni tehnološki biznis).',
            '<strong>20%</strong> ukupnog iznosa definisanog u Javnom konkursu za tekuću godinu — za fizička lica, preduzetnike i društva kojima ranije nijesu dodjeljivana budžetska sredstva Opštine Kotor za podršku preduzetništvu mladih.',
            '<strong>15%</strong> ukupnog iznosa definisanog u Javnom konkursu za tekuću godinu — za preduzetnike i društva kojima su ranije dodjeljivana budžetska sredstva Opštine Kotor za podršku preduzetništvu mladih.',
        ] as $marker) {
            $this->assertStringContainsString($marker, $notice);
        }
    }

    private function assertZpFinanceNote(string $html): void
    {
        $notice = $this->financesNoticeHtml($html);
        foreach ($this->zpFinanceNoteMarkers() as $marker) {
            $this->assertStringContainsString($marker, $notice);
        }
    }

    private function assertZpFinanceNoteAbsent(string $html): void
    {
        foreach ($this->zpFinanceNoteMarkers() as $marker) {
            $this->assertStringNotContainsString($marker, $html);
        }
    }

    private function assertNoticeConfirmationUnlocksFollowingQuestions(string $html): void
    {
        $this->assertStringContainsString('Potvrđujem da je napomena pročitana', $html);
        $this->assertStringContainsString('id="finances_notice_confirmed"', $html);
        $this->assertMatchesRegularExpression(
            '/id="financesAfterNotice" class="finances-after-notice locked"/',
            $html
        );
        $noticeAt = strpos($html, 'id="financesNoticeBox"');
        $confirmAt = strpos($html, 'id="finances_notice_confirmed"');
        $questionsAt = strpos($html, 'id="financesAfterNotice"');
        $question22At = strpos($html, '22. Koliki iznos podrške tražite od Opštine Kotor');
        $question25At = strpos($html, '25. Projekcija rashoda u naredne 3 godine');
        $this->assertNotFalse($noticeAt);
        $this->assertNotFalse($confirmAt);
        $this->assertNotFalse($questionsAt);
        $this->assertNotFalse($question22At);
        $this->assertNotFalse($question25At);
        $this->assertLessThan($confirmAt, $noticeAt);
        $this->assertLessThan($questionsAt, $confirmAt);
        $this->assertLessThan($question22At, $questionsAt);
        $this->assertLessThan($question25At, $question22At);
        $this->assertStringContainsString("financesAfterNotice.classList.remove('locked')", $html);
    }

    private function financesNoticeHtml(string $html): string
    {
        $this->assertSame(
            1,
            preg_match('/<div class="finances-notice-box" id="financesNoticeBox">[\s\S]*?<\/div>/', $html, $matches)
        );

        return $matches[0];
    }

    /**
     * @return list<string>
     */
    private function zpFinanceNoteMarkers(): array
    {
        return [
            'Maksimalan iznos dodijeljenih sredstava za jedan biznis plan ne može iznositi više od:',
            '<strong>20%</strong> od ukupnog iznosa definisanog u Javnim konkursima za tekuću godinu za:',
            '<strong>10%</strong> od ukupnog iznosa definisanog u Javnim konkursima za tekuću godinu za fizička lica/preduzetnice/društva kojima ranije nisu dodjeljivana budžetska sredstva Opštine Kotor za podršku ženskom preduzetništvu.',
            '<strong>5%</strong> od ukupnog iznosa definisanog u Javnim konkursima za tekuću godinu za preduzetnice/društva kojima su ranije dodjeljivana budžetska sredstva Opštine Kotor za podršku ženskom preduzetništvu.',
            'zeleno preduzetništvo',
        ];
    }

    public function test_pm_memorandum_shows_the_applications_existing_number(): void
    {
        [$owner, $application] = $this->application('omladinsko');
        UpNumber::create([
            'competition_id' => $application->competition_id,
            'number' => 'UP-M2-01',
        ]);
        $application->update(['redni_broj' => 4]);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertPmMemorandum($html, 'UP-M2-01/4');
        $this->assertStringNotContainsString('—/—', $html);
    }

    public function test_mmsp_business_plan_route_does_not_open_this_form(): void
    {
        [$owner, $application] = $this->application(CompetitionProfileResolver::TYPE_CANONICAL);

        $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertRedirect();
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function application(string $type): array
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $competition = Competition::create([
            'title' => 'Naslov plana '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan '.$type,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, Kotor',
        ]);

        return [$owner, $application];
    }

    private function assertPmMemorandum(string $html, string $brojPrijave): void
    {
        $this->assertSame(1, substr_count($html, '<div class="obrazac-zaglavlje obrazac-zaglavlje-pm">'));
        $this->assertStringContainsString('images/srednji_grb.png', $html);
        $this->assertStringContainsString('height: 2cm', $html);
        $this->assertStringContainsString('Crna Gora', $html);
        $this->assertStringContainsString('Opština Kotor', $html);
        $this->assertStringContainsString('Sekretarijat za razvoj preduzetništva', $html);
        $this->assertStringContainsString('komunalne poslove i saobraćaj', $html);
        $this->assertStringContainsString('Stari grad 317', $html);
        $this->assertStringContainsString('85330 Kotor, Crna Gora', $html);
        $this->assertStringContainsString('tel. +382(0)32 325 865', $html);
        $this->assertStringContainsString('privreda@kotor.me', $html);
        $this->assertStringContainsString('www.kotor.me', $html);
        $this->assertStringContainsString('Broj prijave: '.$brojPrijave, $html);
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac M2</div>', $html);
        $this->assertStringContainsString('FORMA ZA BIZNIS PLAN', $html);
        $this->assertStringContainsString('1. Naziv biznis ideje:', $html);
        $this->assertStringContainsString('class="form-card no-print"', $html);
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.no-print,\s*\n\s*button,/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.obrazac-zaglavlje-pm\s*\{[^}]*border:\s*1px solid #ccc;/s',
            $html
        );
        $this->assertStringContainsString('size: A4;', $html);
    }
}
