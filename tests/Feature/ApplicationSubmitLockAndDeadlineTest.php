<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\BusinessPlan;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Ciljani testovi za konačnu predaju, zaključavanje podnesene prijave
 * i draft nakon isteka roka (žensko preduzetništvo).
 */
class ApplicationSubmitLockAndDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_submit_succeeds_with_complete_obrazac_existing_bp_finances_notice_and_incomplete_docs(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $this->assertTrue($application->isObrazacComplete());
        $this->assertTrue((bool) $application->businessPlan->finances_notice_confirmed);
        $this->assertSame(0, $application->documents()->count());

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
    }

    public function test_submit_fails_when_obrazac_incomplete(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $application->update(['physical_person_address' => null]);
        $this->assertFalse($application->fresh()->isObrazacComplete());

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_submit_fails_when_finances_notice_not_confirmed(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: false);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_submit_fails_when_obrazac_territorial_address_not_in_kotor(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $application->update(['physical_person_address' => 'Ulica 1, Podgorica']);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_show_is_ready_to_submit_false_when_kotor_territorial_condition_fails(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $application->update(['physical_person_address' => 'Ulica 1, Podgorica']);

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertViewHas('isReadyToSubmit', false);
    }

    public function test_show_is_ready_to_submit_true_when_all_submit_conditions_including_kotor_are_met(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertViewHas('isReadyToSubmit', true);
    }

    public function test_preduzetnica_submit_uses_preduzetnik_address_for_kotor_gate(): void
    {
        [$ownerPass, $passApplication] = $this->createReadyDraftByCategory('preduzetnica');
        $passApplication->update([
            'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
            'physical_person_address' => 'Ulica 1, Podgorica',
            'doo_address' => 'Ulica 1, Podgorica',
            'company_seat' => 'Podgorica',
        ]);
        $passApplication->businessPlan->update(['applicant_address' => 'Ulica 1, Podgorica']);

        $this->actingAs($ownerPass)
            ->post(route('applications.final-submit', $passApplication))
            ->assertRedirect(route('applications.show', $passApplication))
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $passApplication->fresh()->status);

        [$ownerFail, $failApplication] = $this->createReadyDraftByCategory('preduzetnica');
        $failApplication->update([
            'preduzetnik_address' => 'Ulica 1, Podgorica',
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'doo_address' => 'Njegoševa 1, 85330 Kotor',
            'company_seat' => 'Njegoševa 1, 85330 Kotor',
        ]);
        $failApplication->businessPlan->update(['applicant_address' => 'Njegoševa 1, 85330 Kotor']);

        $this->actingAs($ownerFail)
            ->post(route('applications.final-submit', $failApplication))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $failApplication->fresh()->status);
    }

    public function test_registered_company_submit_uses_company_seat_for_kotor_gate(): void
    {
        [$ownerPass, $passApplication] = $this->createReadyDraftByCategory('doo_registered');
        $passApplication->update([
            'company_seat' => 'Njegoševa 1, 85330 Kotor',
            'doo_address' => 'Ulica 1, Podgorica',
            'preduzetnik_address' => 'Ulica 1, Podgorica',
            'physical_person_address' => 'Ulica 1, Podgorica',
        ]);
        $passApplication->businessPlan->update(['applicant_address' => 'Ulica 1, Podgorica']);

        $this->actingAs($ownerPass)
            ->post(route('applications.final-submit', $passApplication))
            ->assertRedirect(route('applications.show', $passApplication))
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $passApplication->fresh()->status);

        [$ownerFail, $failApplication] = $this->createReadyDraftByCategory('doo_registered');
        $failApplication->update([
            'company_seat' => 'Podgorica',
            'doo_address' => 'Njegoševa 1, 85330 Kotor',
            'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);
        $failApplication->businessPlan->update(['applicant_address' => 'Njegoševa 1, 85330 Kotor']);

        $this->actingAs($ownerFail)
            ->post(route('applications.final-submit', $failApplication))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $failApplication->fresh()->status);
    }

    public function test_unregistered_company_submit_uses_doo_address_for_kotor_gate(): void
    {
        [$ownerPass, $passApplication] = $this->createReadyDraftByCategory('doo_unregistered');
        $passApplication->update([
            'doo_address' => 'Njegoševa 1, 85330 Kotor',
            'company_seat' => 'Podgorica',
            'preduzetnik_address' => 'Ulica 1, Podgorica',
            'physical_person_address' => 'Ulica 1, Podgorica',
        ]);
        $passApplication->businessPlan->update(['applicant_address' => 'Ulica 1, Podgorica']);

        $this->actingAs($ownerPass)
            ->post(route('applications.final-submit', $passApplication))
            ->assertRedirect(route('applications.show', $passApplication))
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $passApplication->fresh()->status);

        [$ownerFail, $failApplication] = $this->createReadyDraftByCategory('doo_unregistered');
        $failApplication->update([
            'doo_address' => 'Ulica 1, Podgorica',
            'company_seat' => 'Njegoševa 1, 85330 Kotor',
            'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);
        $failApplication->businessPlan->update(['applicant_address' => 'Njegoševa 1, 85330 Kotor']);

        $this->actingAs($ownerFail)
            ->post(route('applications.final-submit', $failApplication))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $failApplication->fresh()->status);
    }

    public function test_owner_sees_readonly_obrazac_for_submitted_application(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $this->actingAs($owner)->post(route('applications.final-submit', $application));
        $application->refresh();
        $this->assertSame('submitted', $application->status);

        $url = route('applications.create', $application->competition).'?application_id='.$application->id;

        $this->actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->assertSee('Pregled prijave', false)
            ->assertDontSee('id="saveAsDraftBtn"', false)
            ->assertDontSee('id="submitBtn"', false);
    }

    public function test_owner_sees_readonly_obrazac_for_draft_after_deadline(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true, deadlinePassed: true);
        $this->assertTrue($application->isApplicantContentWriteLocked());

        $url = route('applications.create', $application->competition).'?application_id='.$application->id;

        $this->actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->assertSee('Pregled prijave', false)
            ->assertDontSee('id="saveAsDraftBtn"', false)
            ->assertDontSee('id="submitBtn"', false);
    }

    public function test_owner_can_edit_obrazac_for_active_draft_before_deadline(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $this->assertFalse($application->isApplicantContentWriteLocked());

        $url = route('applications.create', $application->competition).'?application_id='.$application->id;

        $this->actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertViewHas('readOnly', false)
            ->assertSee('id="saveAsDraftBtn"', false);
    }

    public function test_incomplete_business_plan_content_does_not_block_submit(): void
    {
        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $application->businessPlan->update([
            'summary' => null,
            'promotion' => null,
            'business_analysis' => null,
        ]);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_submitted_application_rejects_direct_write_requests(): void
    {
        Storage::fake('local');

        [$owner, $application] = $this->createReadyDraft(financesNotice: true);
        $this->actingAs($owner)->post(route('applications.final-submit', $application));
        $application->refresh();
        $this->assertSame('submitted', $application->status);

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Licna karta.pdf',
            'file_path' => 'applications/test.pdf',
            'document_type' => 'licna_karta',
            'is_required' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('applications.store', $application->competition), [
                'application_id' => $application->id,
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Izmijenjen naziv',
                'business_area' => 'Usluge',
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => '0101990123456',
                'physical_person_phone' => '067000000',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
                'accuracy_declaration' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame('Kompletan plan', $application->fresh()->business_plan_name);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'business_idea_name' => 'Hacked ideja',
                'applicant_name' => $owner->name,
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame('Minimalna ideja', $application->fresh()->businessPlan->business_idea_name);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'ostalo',
                'files' => [UploadedFile::fake()->create('ostalo.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($owner)
            ->delete(route('applications.document.destroy', [$application, $document]))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('application_documents', ['id' => $document->id]);

        $this->actingAs($owner)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'submitted']);
    }

    public function test_draft_after_deadline_stays_draft_visible_and_write_locked(): void
    {
        Storage::fake('local');

        [$owner, $application] = $this->createReadyDraft(financesNotice: true, deadlinePassed: true);
        $this->assertSame('draft', $application->status);
        $this->assertTrue($application->isApplicantContentWriteLocked());

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk();

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->rejection_reason);

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Licna karta.pdf',
            'file_path' => 'applications/test.pdf',
            'document_type' => 'licna_karta',
            'is_required' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('applications.store', $application->competition), [
                'application_id' => $application->id,
                'save_as_draft' => '1',
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Izmijenjen naziv',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'business_idea_name' => 'Hacked ideja',
                'applicant_name' => $owner->name,
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'ostalo',
                'files' => [UploadedFile::fake()->create('ostalo.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($owner)
            ->delete(route('applications.document.destroy', [$application, $document]))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($owner)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $application->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'draft']);
        $this->assertDatabaseHas('application_documents', ['id' => $document->id]);
    }

    public function test_close_competition_does_not_reject_draft_applications(): void
    {
        [$competition, $president, $member, $draft] = $this->createCloseableCompetitionWithDraft();

        $this->assertSame('draft', $draft->status);
        $this->assertTrue($competition->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($president->user)
            ->from(route('admin.competitions.ranking', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $draft->refresh();
        $this->assertSame('draft', $draft->status);
        $this->assertNull($draft->rejection_reason);
        $this->assertSame('completed', $competition->fresh()->status);
    }

    public function test_close_competition_is_forbidden_for_non_chairman_actors(): void
    {
        [$competition, $president, $member, $draft] = $this->createCloseableCompetitionWithDraft();

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.competitions.close', $competition))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('admin.competitions.close', $competition))
            ->assertForbidden();

        $this->actingAs($member->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertForbidden();

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);

        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $competition->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertNull($draft->fresh()->rejection_reason);
    }

    /**
     * @return array{0: Competition, 1: CommissionMember, 2: CommissionMember, 3: Application}
     */
    private function createCloseableCompetitionWithDraft(): array
    {
        $commission = $this->createCommissionWithFiveMembers();
        $competition = Competition::create([
            'title' => 'Close konkurs '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);
        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        $submittedOwner = $this->makeKorisnik('submitted-'.uniqid().'@example.com', [
            'jmb' => $this->uniqueTestJmb(),
        ]);
        $submitted = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $submittedOwner->id,
            'business_plan_name' => 'Podneseni plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(10),
            'is_registered' => false,
            'physical_person_name' => $submittedOwner->name,
            'physical_person_jmbg' => $submittedOwner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $submittedOwner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
            'final_score' => 50,
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 100,
        ]);
        BusinessPlan::create([
            'application_id' => $submitted->id,
            'business_idea_name' => 'Podnesena ideja',
            'applicant_name' => $submittedOwner->name,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $submittedOwner->email,
            'finances_notice_confirmed' => true,
        ]);

        ApplicationEliminatoryCheck::create([
            'application_id' => $submitted->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now()->subDay(),
            'confirmed_by_commission_member_id' => $commission->activeMembers()->where('position', 'predsjednik')->value('id'),
            'confirmed_by_user_id' => $commission->activeMembers()->where('position', 'predsjednik')->value('user_id'),
            'confirmed_by_name' => 'Predsjednik',
        ]);

        $seat = 1;
        foreach ($commission->activeMembers()->orderBy('id')->get() as $member) {
            EvaluationScore::create([
                'application_id' => $submitted->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $seat++,
                'documents_complete' => true,
                'criterion_1' => 5,
                'criterion_2' => 5,
                'criterion_3' => 5,
                'criterion_4' => 5,
                'criterion_5' => 5,
                'criterion_6' => 5,
                'criterion_7' => 5,
                'criterion_8' => 5,
                'criterion_9' => 5,
                'criterion_10' => 5,
                'final_score' => 50,
            ]);
        }

        $draftOwner = $this->makeKorisnik('draft-'.uniqid().'@example.com', [
            'jmb' => $this->uniqueTestJmb(),
        ]);
        $draft = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $draftOwner->id,
            'business_plan_name' => 'Draft plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'draft',
            'is_registered' => false,
            'physical_person_name' => $draftOwner->name,
            'physical_person_jmbg' => $draftOwner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $draftOwner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
        ]);

        $president = $commission->activeMembers->firstWhere('position', 'predsjednik');
        $member = $commission->activeMembers->firstWhere('position', 'clan');
        $this->assertNotNull($president);
        $this->assertNotNull($member);

        return [$competition->fresh(), $president, $member, $draft];
    }

    private int $jmbSequence = 0;

    /**
     * @return array{0: User, 1: Application}
     */
    private function createReadyDraft(bool $financesNotice, bool $deadlinePassed = false): array
    {
        return $this->createReadyDraftByCategory('fizicko_lice', $financesNotice, $deadlinePassed);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createReadyDraftByCategory(
        string $category,
        bool $financesNotice = true,
        bool $deadlinePassed = false
    ): array {
        $jmb = $this->uniqueTestJmb();

        $owner = $this->makeKorisnik('owner-'.uniqid().'@example.com', [
            'jmb' => $jmb,
            'user_type' => 'Fizičko lice',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
        ]);

        $competition = Competition::create([
            'title' => 'Test konkurs '.uniqid(),
            'description' => 'Opis',
            'start_date' => $deadlinePassed
                ? now()->subDays(30)->toDateString()
                : now()->subDays(2)->toDateString(),
            'end_date' => $deadlinePassed
                ? now()->subDays(5)->toDateString()
                : now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);

        $attributes = [
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Kompletan plan',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'draft',
            'accuracy_declaration' => true,
        ];

        if ($category === 'fizicko_lice') {
            $attributes = array_merge($attributes, [
                'applicant_type' => 'fizicko_lice',
                'is_registered' => false,
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => $jmb,
                'physical_person_phone' => '067000000',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            ]);
        } elseif ($category === 'preduzetnica') {
            $attributes = array_merge($attributes, [
                'applicant_type' => 'preduzetnica',
                'is_registered' => true,
                'registration_form' => 'Preduzetnik',
                'crps_number' => 'CRPS-1',
                'pib' => '02000009',
                'applicant_jmbg' => $jmb,
                'preduzetnik_name' => $owner->name,
                'preduzetnik_phone' => '067000000',
                'preduzetnik_email' => $owner->email,
                'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
            ]);
        } elseif ($category === 'doo_registered') {
            $attributes = array_merge($attributes, [
                'applicant_type' => 'doo',
                'is_registered' => true,
                'registration_form' => 'DOO',
                'crps_number' => 'CRPS-2',
                'pib' => '02000009',
                'applicant_jmbg' => $jmb,
                'doo_name' => $owner->name,
                'doo_phone' => '067000000',
                'doo_email' => $owner->email,
                'doo_address' => 'Ulica 1, Podgorica',
                'founder_name' => $owner->name,
                'director_name' => $owner->name,
                'company_seat' => 'Njegoševa 1, 85330 Kotor',
            ]);
        } elseif ($category === 'doo_unregistered') {
            $attributes = array_merge($attributes, [
                'applicant_type' => 'doo',
                'is_registered' => false,
                'applicant_jmbg' => $jmb,
                'doo_name' => $owner->name,
                'doo_phone' => '067000000',
                'doo_email' => $owner->email,
                'doo_address' => 'Njegoševa 1, 85330 Kotor',
            ]);
        } else {
            $this->fail('Nepoznata test kategorija: '.$category);
        }

        $application = Application::create($attributes);

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Minimalna ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $jmb,
            'applicant_address' => 'Ulica 1, Podgorica',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'finances_notice_confirmed' => $financesNotice,
        ]);

        $application->refresh();
        $application->load(['businessPlan', 'competition']);
        $this->assertTrue($application->isObrazacComplete());

        return [$owner, $application];
    }

    private function uniqueTestJmb(): string
    {
        $this->jmbSequence++;

        return '0101990'.str_pad((string) $this->jmbSequence, 6, '0', STR_PAD_LEFT);
    }

    private function makeKorisnik(string $email, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => $email,
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createCommissionWithFiveMembers(): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $types[$i],
                'status' => 'active',
                'canonical_seat_no' => $i + 1,
            ]);
        }

        return $commission->fresh(['activeMembers.user']);
    }
}
