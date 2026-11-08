<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Support\BusinessPlanM2Completeness;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPlanM2CompletenessTest extends TestCase
{
    use RefreshDatabase;

    private const DRAFT_BUTTON = 'id="bpSubmitBtnText">Sačuvaj nacrt biznis plana</span>';

    private const FULL_BUTTON = 'id="bpSubmitBtnText">Sačuvaj biznis plan</span>';

    private const PARTIAL_NOTICE = 'Status „Popunjen“ potvrđuje da su uneseni osnovni obavezni podaci biznis plana.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_partial_save_stays_allowed_and_uses_the_draft_button(): void
    {
        foreach (['omladinsko', 'zensko'] as $type) {
            [$owner, $application] = $this->application($type);

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), [
                    'save_as_draft' => '1',
                    'has_registered_business' => '0',
                    'business_idea_name' => 'Djelimična ideja',
                ])
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHasNoErrors();

            $this->assertSame('draft', $application->fresh()->status);
            $this->assertFalse($application->fresh()->businessPlan->isComplete());

            $html = $this->actingAs($owner)
                ->get(route('applications.business-plan.create', $application))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString(self::DRAFT_BUTTON, $html);
            $this->assertStringNotContainsString(self::FULL_BUTTON, $html);
        }
    }

    public function test_full_displayed_plan_changes_the_button_and_the_status_notice(): void
    {
        foreach (['omladinsko', 'zensko'] as $type) {
            [$owner, $application] = $this->application($type);
            $plan = $this->fullPlan($application, $owner);

            $this->assertTrue(BusinessPlanM2Completeness::isFullyFilled($plan->fresh(), $application));
            $this->assertSame('draft', $application->fresh()->status);

            $form = $this->actingAs($owner)
                ->get(route('applications.business-plan.create', $application))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString(self::FULL_BUTTON, $form);

            $page = $this->actingAs($owner)
                ->get(route('applications.show', $application))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString(BusinessPlanM2Completeness::SAVED_NOTICE, $page);
            $this->assertStringNotContainsString(self::PARTIAL_NOTICE, $page);

            $plan->update(['promotion' => null]);
            $this->assertTrue($plan->fresh()->isComplete());
            $this->assertFalse(BusinessPlanM2Completeness::isFullyFilled($plan->fresh(), $application));

            $reopened = $this->actingAs($owner)
                ->get(route('applications.business-plan.create', $application))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString(self::DRAFT_BUTTON, $reopened);

            $after = $this->actingAs($owner)
                ->get(route('applications.show', $application))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString(self::PARTIAL_NOTICE, $after);
            $this->assertStringNotContainsString(BusinessPlanM2Completeness::SAVED_NOTICE, $after);
            $this->assertSame('draft', $application->fresh()->status);
        }
    }

    public function test_missing_asterisk_blocks_final_submit_and_empty_optional_fields_do_not(): void
    {
        foreach (['omladinsko', 'zensko'] as $type) {
            [$owner, $application] = $this->application($type);
            $plan = $this->fullPlan($application, $owner);
            $plan->update([
                'promotion' => null,
                'required_amount' => 0,
                'annual_sales_volume' => 0,
            ]);

            $payload = $type === 'omladinsko' ? ['confirm_missing_documents' => '1'] : [];
            $this->actingAs($owner)
                ->post(route('applications.final-submit', $application), $payload)
                ->assertSessionHasNoErrors();
            $this->assertSame('submitted', $application->fresh()->status);

            [$blockedOwner, $blocked] = $this->application($type);
            $blockedPlan = $this->fullPlan($blocked, $blockedOwner);
            $blockedPlan->update(['business_idea_name' => null]);

            $this->actingAs($blockedOwner)
                ->post(route('applications.final-submit', $blocked), [
                    'confirm_incomplete_forms' => '1',
                    'confirm_missing_documents' => '1',
                ])
                ->assertSessionHasErrors('error');
            $this->assertSame('draft', $blocked->fresh()->status);
            $this->assertSame(
                BusinessPlanM2Completeness::SUBMIT_BLOCKED_MESSAGE,
                session('errors')->first('error')
            );
        }
    }

    public function test_question_16_keeps_unanswered_yes_and_no_apart(): void
    {
        [$owner, $application] = $this->application('omladinsko');
        $plan = BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Sezona',
            'has_seasonal_workers' => null,
        ]);

        $unanswered = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="seasonal_yes"[^>]*\schecked/', $unanswered);
        $this->assertDoesNotMatchRegularExpression('/id="seasonal_no"[^>]*\schecked/', $unanswered);
        $this->assertNull($plan->fresh()->has_seasonal_workers);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Sezona',
                'has_seasonal_workers' => '0',
            ])
            ->assertSessionHasNoErrors();
        $this->assertFalse($plan->fresh()->has_seasonal_workers);

        $no = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="seasonal_yes"[^>]*\schecked/', $no);
        $this->assertMatchesRegularExpression('/id="seasonal_no"[^>]*\schecked/', $no);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Sezona',
                'has_seasonal_workers' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->assertTrue($plan->fresh()->has_seasonal_workers);

        $yes = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression('/id="seasonal_yes"[^>]*\schecked/', $yes);
        $this->assertDoesNotMatchRegularExpression('/id="seasonal_no"[^>]*\schecked/', $yes);
    }

    public function test_pm_summary_blocks_submit_while_points_7_10_16_and_zp_summary_stay_optional(): void
    {
        foreach (['omladinsko', 'zensko'] as $type) {
            [$owner, $application] = $this->application($type);

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), [
                    'save_as_draft' => '1',
                    'has_registered_business' => '0',
                    'business_idea_name' => 'Puna ideja',
                    'applicant_name' => $owner->name,
                    'applicant_jmbg' => '0101990123456',
                    'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                    'applicant_phone' => '067000000',
                    'applicant_email' => $owner->email,
                    'summary' => '',
                    'finances_notice_confirmed' => '1',
                ])
                ->assertSessionHasNoErrors();

            $saved = $application->fresh()->businessPlan;
            $summaryRequired = $type === 'omladinsko';
            $this->assertNull($saved->summary);
            $this->assertNull($saved->realization_type);
            $this->assertNull($saved->has_business_space);
            $this->assertNull($saved->has_seasonal_workers);
            $this->assertSame(! $summaryRequired, BusinessPlanM2Completeness::hasRequiredAsterisks($saved));
            $this->assertSame(! $summaryRequired, $saved->isComplete());

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), [
                    'save_as_draft' => '1',
                    'has_registered_business' => '0',
                    'business_idea_name' => 'Puna ideja',
                    'applicant_name' => $owner->name,
                    'applicant_jmbg' => '0101990123456',
                    'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                    'applicant_phone' => '067000000',
                    'applicant_email' => $owner->email,
                    'summary' => 'Sačuvan rezime',
                    'realization_type' => 'stvori_novi',
                    'has_business_space' => 'nemam',
                    'has_seasonal_workers' => '0',
                    'finances_notice_confirmed' => '1',
                ])
                ->assertSessionHasNoErrors();
            $filled = $saved->fresh();
            $this->assertSame('Sačuvan rezime', $filled->summary);
            $this->assertSame('stvori_novi', $filled->realization_type);
            $this->assertSame('nemam', $filled->has_business_space);
            $this->assertFalse($filled->has_seasonal_workers);

            $html = $this->actingAs($owner)
                ->get(route('applications.business-plan.create', $application))
                ->assertOk()
                ->getContent();
            $this->assertStringContainsString('Sačuvan rezime', $html);
            $this->assertMatchesRegularExpression('/id="realization_new"[^>]*\schecked/', $html);
            $this->assertMatchesRegularExpression('/id="space_none"[^>]*\schecked/', $html);
            $this->assertMatchesRegularExpression('/id="seasonal_no"[^>]*\schecked/', $html);

            $filled->update([
                'summary' => null,
                'realization_type' => null,
                'has_business_space' => null,
                'has_seasonal_workers' => null,
                'expense_projection' => null,
            ]);

            if ($type === 'omladinsko') {
                $this->assertFalse($filled->fresh()->isComplete());
                $this->assertNull($filled->fresh()->expense_projection);
                $this->actingAs($owner)
                    ->post(route('applications.final-submit', $application), [
                        'confirm_incomplete_forms' => '1',
                        'confirm_missing_documents' => '1',
                    ])
                    ->assertSessionHasErrors([
                        'error' => BusinessPlanM2Completeness::SUBMIT_BLOCKED_MESSAGE,
                    ]);
                $this->assertSame('draft', $application->fresh()->status);

                $filled->update(['summary' => 'Sažetak']);
                $this->assertTrue($filled->fresh()->isComplete());
                $this->assertNull($filled->fresh()->realization_type);
                $this->assertNull($filled->fresh()->expense_projection);
                $this->actingAs($owner)
                    ->post(route('applications.final-submit', $application), [
                        'confirm_missing_documents' => '1',
                    ])
                    ->assertSessionHasNoErrors();
                $this->assertSame('submitted', $application->fresh()->status);
            } else {
                $this->actingAs($owner)
                    ->post(route('applications.final-submit', $application))
                    ->assertSessionHasNoErrors();
                $this->assertSame('submitted', $application->fresh()->status);
                $this->assertNull($filled->fresh()->summary);
                $this->assertNull($filled->fresh()->realization_type);
                $this->assertNull($filled->fresh()->has_business_space);
                $this->assertNull($filled->fresh()->has_seasonal_workers);
            }

            [$blockedOwner, $blocked] = $this->application($type);
            $blockedPlan = $this->fullPlan($blocked, $blockedOwner);
            $blockedPlan->update([
                'summary' => null,
                'realization_type' => null,
                'has_business_space' => null,
                'has_seasonal_workers' => null,
                'applicant_phone' => null,
            ]);
            $this->actingAs($blockedOwner)
                ->post(route('applications.final-submit', $blocked), [
                    'confirm_incomplete_forms' => '1',
                    'confirm_missing_documents' => '1',
                ])
                ->assertSessionHasErrors('error');
            $this->assertSame('draft', $blocked->fresh()->status);
            $this->assertSame(
                BusinessPlanM2Completeness::SUBMIT_BLOCKED_MESSAGE,
                session('errors')->first('error')
            );
        }
    }

    public function test_pm_completed_save_requires_summary_and_draft_may_omit_it(): void
    {
        [$owner, $application] = $this->application('omladinsko');
        $identity = [
            'has_registered_business' => '0',
            'business_idea_name' => 'Puna ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'finances_notice_confirmed' => '1',
        ];

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), $identity + [
                'save_as_draft' => '1',
                'summary' => '',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasNoErrors();
        $this->assertNull($application->fresh()->businessPlan->summary);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), $identity + [
                'save_as_draft' => '1',
                'summary' => 'Sačuvan rezime',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('Sačuvan rezime', $application->fresh()->businessPlan->summary);

        $this->actingAs($owner)
            ->from(route('applications.business-plan.create', $application))
            ->post(route('applications.business-plan.store', $application), array_merge($identity, [
                'business_idea_name' => 'Nova ideja koja ne smije biti sačuvana',
                'summary' => '   ',
            ]))
            ->assertRedirect(route('applications.business-plan.create', $application))
            ->assertSessionHasErrors(['summary' => 'Rezime je obavezan.']);
        $this->assertSame('Sačuvan rezime', $application->fresh()->businessPlan->summary);
        $this->assertSame('Puna ideja', $application->fresh()->businessPlan->business_idea_name);
        $rejected = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Rezime je obavezan.', $rejected);
        $this->assertStringContainsString('Nova ideja koja ne smije biti sačuvana', $rejected);
        $this->assertMatchesRegularExpression(
            '/5\. Rezime \(ukratko opišite ideju, proširite tabelu koliko je potrebno\):\s*<span class="required">\*<\/span>/',
            $rejected
        );

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), $identity + [
                'save_as_draft' => '1',
                'summary' => '   ',
            ])
            ->assertSessionHasNoErrors();
        $this->assertNull($application->fresh()->businessPlan->summary);
        $this->assertFalse($application->fresh()->businessPlan->isComplete());

        [$zpOwner, $zp] = $this->application('zensko');
        $zpIdentity = $identity;
        $zpIdentity['applicant_name'] = $zpOwner->name;
        $zpIdentity['applicant_email'] = $zpOwner->email;
        $this->actingAs($zpOwner)
            ->post(route('applications.business-plan.store', $zp), $zpIdentity + [
                'summary' => '   ',
            ])
            ->assertSessionHasNoErrors();
        $this->assertNull($zp->fresh()->businessPlan->summary);
        $this->assertTrue($zp->fresh()->businessPlan->isComplete());

        $zpForm = $this->actingAs($zpOwner)
            ->get(route('applications.business-plan.create', $zp))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString(
            '5. Rezime (ukratko opišite ideju, proširite tabelu koliko je potrebno): <span class="required">*</span>',
            $zpForm
        );
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
            'title' => 'M2 '.$type.' '.uniqid(),
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
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        return [$owner, $application];
    }

    private function fullPlan(Application $application, User $owner): BusinessPlan
    {
        return BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Puna ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'summary' => 'Sažetak',
            'realization_type' => 'stvori_novi',
            'has_business_space' => 'nemam',
            'has_seasonal_workers' => false,
            'finances_notice_confirmed' => true,
            'promotion' => 'Promocija',
            'required_resources' => 'Resursi',
            'work_experience' => 'Iskustvo',
            'personal_strengths_weaknesses' => 'Osobine',
            'biggest_support' => 'Porodica',
            'annual_sales_volume' => 0,
            'annual_purchases_volume' => 0,
            'required_amount' => 0,
            'requested_amount' => 0,
            'funding_alternative' => 'ne_znam',
            'competition_analysis' => "Lokalno postoje\n\n---\n\nSlabe strane",
            'products_services_table' => [['product' => 'Usluga', 'description' => 'Opis']],
            'target_customers' => [['group' => 'Mladi', 'description' => 'Kotor']],
            'sales_locations' => [['location' => 'Kotor', 'description' => 'Centar']],
            'pricing_table' => [['product' => 'Usluga', 'price' => '0']],
            'revenue_share_table' => [['product' => 'Usluga', 'share' => '0']],
            'employment_structure' => [['year' => '2026', 'employees' => '0', 'qualifications' => 'Srednja']],
            'suppliers_table' => [['item' => 'Materijal', 'supplier' => 'Dobavljač']],
            'funding_sources_table' => [['type' => 'Alat', 'price' => '0']],
            'revenue_projection' => [['product' => 'Usluga', 'year1' => '0', 'year2' => '0', 'year3' => '0']],
            'expense_projection' => [
                ['category' => 'investment', 'type' => 'Alat', 'year1' => '0', 'year2' => '0', 'year3' => '0'],
                ['category' => 'operating', 'type' => 'Kirija', 'year1' => '0', 'year2' => '0', 'year3' => '0'],
            ],
            'risk_matrix' => [[
                'risk' => 'Potražnja',
                'probability' => 'Niska',
                'impact' => 'Srednji',
                'measures' => 'Plan',
                'responsible' => 'Ja',
            ]],
            'job_schedule' => [
                ['part' => 'Marketing', 'initially' => 'Ja', 'later' => 'Saradnik'],
                ['part' => 'Poslovanje', 'initially' => 'Ja', 'later' => 'Saradnik'],
                ['part' => 'Finansije', 'initially' => 'Ja', 'later' => 'Saradnik'],
            ],
        ]);
    }
}
