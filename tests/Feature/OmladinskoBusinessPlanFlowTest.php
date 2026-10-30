<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentProcessor;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnOmladinskoDocumentPackage;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class OmladinskoBusinessPlanFlowTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private int $jmbSerial = 730;

    protected function setUp(): void
    {
        parent::setUp();
        CompetitionProgramCatalog::clearTestOverrides();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_omladinsko_saves_partial_draft_without_purchase_item(): void
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Djelimična ideja',
                'funding_sources_table' => [['type' => '', 'price' => '']],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Nacrt biznis plana je sačuvan. Biznis plan još nije kompletan. Nedostajuće podatke možete dopuniti prije konačnog podnošenja prijave.');

        $application->refresh();
        $this->assertNotNull($application->businessPlan);
        $this->assertSame('Djelimična ideja', $application->businessPlan->business_idea_name);
        $this->assertSame(0, KnOmladinskoDocumentPackage::filledPurchaseCount($application->businessPlan->funding_sources_table));
        $this->assertNull($application->businessPlan->summary);
    }

    public function test_omladinsko_draft_persists_and_redisplays_entered_data(): void
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Sačuvana ideja',
                'promotion' => 'Opis promocije nacrta',
                'funding_sources_table' => [['type' => '', 'price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="bpSubmitBtnText">Sačuvaj nacrt biznis plana</span>', $html);
        $this->assertStringContainsString('Sačuvana ideja', $html);
        $this->assertStringContainsString('Opis promocije nacrta', $html);
        $this->assertSame('Sačuvana ideja', $application->fresh()->businessPlan->business_idea_name);
        $this->assertSame('Opis promocije nacrta', $application->fresh()->businessPlan->promotion);
    }

    public function test_owner_business_plan_form_links_back_to_application_show(): void
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();
        $ownerUrl = route('applications.show', $application);
        $adminUrl = route('admin.applications.show', $application);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->assertViewHas('applicationReviewUrl', $ownerUrl)
            ->getContent();

        $this->assertSame(2, substr_count($html, '← Nazad na pregled prijave'));
        $this->assertStringContainsString('href="'.$ownerUrl.'"', $html);
        $this->assertStringNotContainsString('href="'.$adminUrl.'"', $html);

        $this->actingAs($owner)
            ->get($ownerUrl)
            ->assertOk();
    }

    public function test_omladinsko_rejects_invalid_email_format_on_draft(): void
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();

        $this->actingAs($owner)
            ->from(route('applications.business-plan.create', $application))
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Ideja sa lošim emailom',
                'applicant_email' => 'nije-email',
            ])
            ->assertRedirect(route('applications.business-plan.create', $application))
            ->assertSessionHasErrors('applicant_email');

        $this->assertNull($application->fresh()->businessPlan);
    }

    public function test_omladinsko_complete_draft_does_not_claim_incompleteness(): void
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), $this->completeYouthDraftPayload($owner))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success')
            ->assertSessionMissing('errors');
        $this->assertStringContainsString('sačuvan kao nacrt', (string) session('success'));
        $this->assertStringNotContainsString('još nije kompletan', (string) session('success'));

        $this->assertFalse(KnOmladinskoDocumentPackage::isContentIncomplete($application->fresh()->businessPlan));
    }

    public function test_omladinsko_submit_warns_about_missing_documents_but_does_not_block(): void
    {
        [$owner, $application] = $this->createOmladinskoReadyDraft();
        $this->assertSame(0, $application->documents()->count());
        $this->assertNotEmpty($application->getMissingRequiredDocumentTypes());

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('omladinsko_confirm_incomplete_forms');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);

        $afterForms = $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1',
        ]);
        $afterForms->assertRedirect(route('applications.show', $application));
        $afterForms->assertSessionHas('omladinsko_confirm_missing_documents');
        $this->assertSame('draft', $application->fresh()->status);

        $confirmed = $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1',
            'confirm_missing_documents' => '1',
        ]);
        $confirmed->assertRedirect(route('applications.show', $application));
        $confirmed->assertSessionHas('document_warning');
        $this->assertStringContainsString('nisu priloženi svi obavezni prilozi', session('document_warning'));
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNotSame('rejected', $application->fresh()->status);
    }

    public function test_omladinsko_documents_lock_after_submit(): void
    {
        [$owner, $application] = $this->createOmladinskoReadyDraft();
        $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1',
            'confirm_missing_documents' => '1',
        ]);
        $application->refresh();
        $this->assertSame('submitted', $application->status);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'licna_karta',
                'files' => [UploadedFile::fake()->create('lk.pdf', 20, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame(0, $application->documents()->count());
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $application->id,
            'document_type' => 'licna_karta',
        ]);
    }

    public function test_omladinsko_submit_allows_missing_point_seven_and_keeps_empty_drugo_text(): void
    {
        [$owner, $application] = $this->createOmladinskoReadyDraft([
            'realization_type' => null,
            'product_service' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Jedan ili više obrazaca vaše prijave nijesu u potpunosti popunjeni.', false);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertNotSame('rejected', $application->status);
        $this->assertNull($application->businessPlan->fresh()->realization_type);
        $this->assertNull($application->businessPlan->fresh()->product_service);

        $application->businessPlan->update([
            'realization_type' => KnOmladinskoDocumentPackage::REALIZATION_DRUGO,
            'product_service' => '',
        ]);
        $this->assertSame('', (string) $application->businessPlan->fresh()->product_service);
    }

    public function test_omladinsko_submit_allows_empty_purchase_table_without_autofill(): void
    {
        [$owner, $application] = $this->createOmladinskoReadyDraft([
            'funding_sources_table' => [['type' => '', 'price' => '']],
            'requested_amount' => 9999,
            'summary' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertSame(0, KnOmladinskoDocumentPackage::filledPurchaseCount($application->businessPlan->fresh()->funding_sources_table));
        $this->assertNull($application->businessPlan->fresh()->summary);
        $this->assertSame(9999.0, (float) $application->businessPlan->fresh()->requested_amount);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->getContent();
        $this->assertStringContainsString('UKUPNO', $html);
        $this->assertStringContainsString('>Drugo<', $html);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'business_idea_name' => 'Izmjena poslije podnošenja',
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame('Ideja', $application->businessPlan->fresh()->business_idea_name);
    }

    public function test_omladinsko_complete_plan_still_submits(): void
    {
        [$owner, $application] = $this->createOmladinskoReadyDraft([
            'summary' => 'Potpun sažetak',
        ]);
        $this->assertFalse(KnOmladinskoDocumentPackage::isContentIncomplete($application->businessPlan));

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame('Potpun sažetak', $application->fresh()->businessPlan->summary);
        $this->assertSame(1, KnOmladinskoDocumentPackage::filledPurchaseCount($application->fresh()->businessPlan->funding_sources_table));
    }

    public function test_commission_can_open_incomplete_submitted_youth_plan(): void
    {
        [$owner, $application] = $this->createOmladinskoReadyDraft([
            'summary' => null,
            'funding_sources_table' => [['type' => '', 'price' => '']],
            'promotion' => 'Dostavljeni sadržaj za Komisiju',
        ]);
        $this->actingAs($owner)->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1',
            'confirm_missing_documents' => '1',
        ]);
        $application->refresh();
        $this->assertSame('submitted', $application->status);

        $commission = Commission::create([
            'name' => 'Omladinska komisija pregled',
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members[] = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }
        $application->competition->update([
            'commission_id' => $commission->id,
            'status' => 'closed',
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $competition = $application->competition->fresh();
        $this->assertNull($competition->firstCommissionSession());
        $this->assertFalse($competition->hasConfirmedFirstSessionQuorum());
        $this->assertTrue($competition->hasCompleteValidCommission());
        $this->assertFalse($competition->isCommissionProcessingBlocked());

        $member = $members[1]->fresh('user');
        $adminUrl = route('admin.applications.show', $application);
        $ownerUrl = route('applications.show', $application);
        $html = $this->actingAs($member->user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->assertViewHas('applicationReviewUrl', $adminUrl)
            ->getContent();
        $this->assertStringContainsString('Dostavljeni sadržaj za Komisiju', $html);
        $this->assertStringContainsString('Ideja', $html);
        $this->assertSame(2, substr_count($html, '← Nazad na pregled prijave'));
        $this->assertStringContainsString('href="'.$adminUrl.'"', $html);
        $this->assertStringNotContainsString('href="'.$ownerUrl.'"', $html);
        $this->actingAs($member->user)
            ->get($adminUrl)
            ->assertOk();
        $this->assertNull($application->businessPlan->fresh()->summary);
        $this->assertSame(0, KnOmladinskoDocumentPackage::filledPurchaseCount($application->businessPlan->fresh()->funding_sources_table));

        $this->actingAs($member->user)
            ->get(route('evaluation.create', $application))
            ->assertOk();

        $incomplete = Commission::create([
            'name' => 'Omladinska nepotpuna komisija',
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $incompleteMembers = [];
        for ($seat = 1; $seat <= 2; $seat++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $incompleteMembers[] = CommissionMember::create([
                'commission_id' => $incomplete->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }
        $application->competition->update(['commission_id' => $incomplete->id]);
        $this->assertTrue($application->competition->fresh()->isCommissionProcessingBlocked());
        $this->actingAs($incompleteMembers[0]->fresh('user')->user)
            ->get(route('applications.business-plan.create', $application))
            ->assertForbidden();
        $this->actingAs($incompleteMembers[0]->fresh('user')->user)
            ->get(route('evaluation.create', $application))
            ->assertForbidden();
    }

    public function test_second_call_creates_new_application_without_copying_m1_m2_or_documents(): void
    {
        [$owner, $first] = $this->createOmladinskoReadyDraft();
        $this->enableControlledFakePdfUploadProcessor();
        $this->actingAs($owner)->post(route('applications.upload', $first), [
            'document_type' => 'licna_karta',
            'files' => [UploadedFile::fake()->create('lk.pdf', 20, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('applications.final-submit', $first), [
            'confirm_incomplete_forms' => '1',
            'confirm_missing_documents' => '1',
        ]);
        $first->refresh();
        $this->assertSame('submitted', $first->status);
        $this->assertSame(1, $first->documents()->count());
        $this->assertNotNull($first->businessPlan);

        $secondCompetition = $this->openCompetition('omladinsko', 'Drugi Poziv', 2);
        $second = Application::create([
            'competition_id' => $secondCompetition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Novi plan drugog Poziva',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(0, $second->documents()->count());
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $second->id,
            'document_type' => 'licna_karta',
        ]);
        $this->assertNull($second->businessPlan);
        $this->assertNotSame($first->business_plan_name, $second->business_plan_name);
        $this->assertSame(1, $first->fresh()->documents()->count());
        $this->assertDatabaseHas('application_documents', [
            'application_id' => $first->id,
            'document_type' => 'licna_karta',
        ]);
    }

    public function test_zensko_incomplete_draft_keeps_existing_save_message(): void
    {
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb(798)]);
        $competition = $this->openCompetition('zensko', 'Poziv žene nacrt');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Ženski plan nacrt',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('id="bpSubmitBtnText">Sačuvaj Nacrt plana</span>', $html);
        $this->assertStringNotContainsString('id="bpSubmitBtnText">Sačuvaj nacrt biznis plana</span>', $html);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Ženska djelimična ideja',
                'funding_sources_table' => [['type' => '', 'price' => '']],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');
        $this->assertStringContainsString('sačuvan kao nacrt', (string) session('success'));
        $this->assertStringNotContainsString('još nije kompletan', (string) session('success'));

        $this->assertSame('Ženska djelimična ideja', $application->fresh()->businessPlan->business_idea_name);
    }

    public function test_omladinsko_store_rejects_non_owner(): void
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();
        $intruder = $this->makeKorisnik(['jmb' => $this->validJmb(797), 'email' => 'intruder-bp@example.com']);

        $this->actingAs($intruder)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'business_idea_name' => 'Tuđa ideja',
            ])
            ->assertForbidden();
        $this->assertNull($application->fresh()->businessPlan);
        $this->assertSame($owner->id, $application->user_id);
    }

    public function test_zensko_submit_and_m2_remain_without_youth_gates(): void
    {
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb(799)]);
        $competition = $this->openCompetition('zensko', 'Poziv žene');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Ženski plan',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);
        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $owner->jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'finances_notice_confirmed' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertDontSee('Biznis plan nije kompletno popunjen. Prijavu možete podnijeti, ali će Komisija ocjenjivati isključivo dostavljeni sadržaj.', false);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('document_warning');
        $this->assertSame('submitted', $application->fresh()->status);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application->fresh()), [
                'document_type' => 'potvrda_zavod_nezaposleni',
                'files' => [UploadedFile::fake()->create('zavod.pdf', 20, 'application/pdf')],
            ])
            ->assertSessionHasErrors('error');
        $this->assertDatabaseMissing('application_documents', [
            'application_id' => $application->id,
            'document_type' => 'potvrda_zavod_nezaposleni',
        ]);
    }

    public function test_youth_m2_defaults_for_preduzetnik_and_privredno_drustvo(): void
    {
        $owner = $this->makeKorisnik([
            'user_type' => 'Preduzetnik',
            'pib' => $this->validPib(740),
            'jmb' => $this->validJmb(741),
        ]);
        $competition = $this->openCompetition('omladinsko');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan preduzetnika',
            'business_area' => 'Usluge',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'razvoj',
            'is_registered' => true,
            'registration_form' => 'Preduzetnik',
            'crps_number' => 'CRPS-1',
            'pib' => $this->validPib(740),
            'accuracy_declaration' => true,
            'applicant_jmbg' => $owner->jmb,
            'preduzetnik_name' => $owner->name,
            'preduzetnik_phone' => '067000000',
            'preduzetnik_email' => $owner->email,
            'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($owner->name, $html);
        $this->assertStringContainsString('podnosiocu', $html);
        $this->assertStringNotContainsString('podnositeljki', $html);
    }

    private function openCompetition(string $type, ?string $title = null, ?int $callNumber = null): Competition
    {
        return Competition::create([
            'title' => $title ?? ('KN tok '.$type),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
            'call_number' => $type === 'omladinsko' ? ($callNumber ?? 1) : null,
        ]);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createOmladinskoApplicationWithoutPlan(): array
    {
        $this->jmbSerial++;
        $owner = $this->makeKorisnik(['jmb' => $this->validJmb($this->jmbSerial)]);
        $competition = $this->openCompetition('omladinsko');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Omladinski plan',
            'business_area' => 'Usluge',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => $owner->jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);
        $application->refresh();
        $this->assertTrue($application->isObrazacComplete());
        $this->assertNull($application->businessPlan);

        return [$owner, $application];
    }

    /**
     * @return array<string, mixed>
     */
    private function completeYouthDraftPayload(User $owner): array
    {
        return [
            'save_as_draft' => '1',
            'has_registered_business' => '0',
            'business_idea_name' => 'Kompletna omladinska ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $owner->jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'summary' => 'Potpun sažetak nacrta',
            'realization_type' => 'stvori_novi',
            'funding_sources_table' => [
                ['type' => 'Laptop', 'price' => '1200'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $planOverrides
     * @return array{0: User, 1: Application}
     */
    private function createOmladinskoReadyDraft(array $planOverrides = []): array
    {
        [$owner, $application] = $this->createOmladinskoApplicationWithoutPlan();

        BusinessPlan::create(array_merge([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $owner->jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'realization_type' => 'stvori_novi',
            'funding_sources_table' => [
                ['type' => 'Laptop', 'price' => '1200'],
            ],
            'requested_amount' => 500,
            'finances_notice_confirmed' => true,
        ], $planOverrides));

        $application->refresh();
        $application->load(['businessPlan', 'competition', 'documents']);
        $this->assertTrue($application->isObrazacComplete());

        return [$owner, $application];
    }

    /**
     * Process::fake + DocumentProcessor stub only for tests that POST a fake PDF
     * through the real upload route. Does not skip document_type, ownership, or lock.
     */
    private function enableControlledFakePdfUploadProcessor(): void
    {
        Process::fake();
        Storage::disk('local')->put('documents/test-upload.pdf', '%PDF-1.4 test');
        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('processDocument')->andReturn([
                'success' => true,
                'file_path' => 'documents/test-upload.pdf',
                'file_size' => 100,
                'cloud_path' => null,
                'error' => null,
            ]);
        });
    }
}
