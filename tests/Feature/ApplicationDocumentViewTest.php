<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationDocumentViewTest extends TestCase
{
    use RefreshDatabase;

    private string $storagePath = 'applications/test-doc.pdf';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Storage::fake('local');
        Storage::disk('local')->put($this->storagePath, '%PDF-1.4 test document content');
    }

    public function test_owner_can_view_document_on_draft_while_deadline_open(): void
    {
        [$owner, $application, $document] = $this->createApplicationWithDocument([
            'status' => 'draft',
            'deadline_passed' => false,
        ]);

        $response = $this->actingAs($owner)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $response->assertOk();
    }

    public function test_commission_member_cannot_view_draft_after_deadline(): void
    {
        [$owner, $application, $document, $member] = $this->createApplicationWithDocument([
            'status' => 'draft',
            'deadline_passed' => true,
            'with_commission_member' => true,
        ]);

        $response = $this->actingAs($member)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $response->assertForbidden();
    }

    public function test_commission_member_cannot_view_submitted_before_deadline(): void
    {
        [$owner, $application, $document, $member] = $this->createApplicationWithDocument([
            'status' => 'submitted',
            'deadline_passed' => false,
            'with_commission_member' => true,
        ]);

        $response = $this->actingAs($member)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $response->assertForbidden();
    }

    public function test_commission_member_can_view_submitted_after_deadline(): void
    {
        [$owner, $application, $document, $member] = $this->createApplicationWithDocument([
            'status' => 'submitted',
            'deadline_passed' => true,
            'with_commission_member' => true,
        ]);

        $response = $this->actingAs($member)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $response->assertOk();
    }

    public function test_other_commission_member_cannot_view_document(): void
    {
        [$owner, $application, $document] = $this->createApplicationWithDocument([
            'status' => 'submitted',
            'deadline_passed' => true,
            'with_commission_member' => true,
        ]);

        $otherMember = $this->createCommissionUserOnOtherCommission();

        $response = $this->actingAs($otherMember)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $response->assertForbidden();
    }

    public function test_inactive_commission_member_cannot_view_document(): void
    {
        [$owner, $application, $document, $member, $commission] = $this->createApplicationWithDocument([
            'status' => 'submitted',
            'deadline_passed' => true,
            'with_commission_member' => true,
        ]);

        CommissionMember::where('user_id', $member->id)
            ->where('commission_id', $commission->id)
            ->update(['status' => 'resigned']);

        $response = $this->actingAs($member)->get(route('applications.document.view', [
            'application' => $application,
            'document' => $document,
        ]));

        $response->assertForbidden();
    }

    public function test_mismatched_document_application_returns_not_found(): void
    {
        [$ownerA, $applicationA, $documentA] = $this->createApplicationWithDocument([
            'status' => 'submitted',
            'deadline_passed' => true,
        ]);

        [$ownerB, $applicationB] = $this->createApplicationWithDocument([
            'status' => 'submitted',
            'deadline_passed' => true,
        ]);

        $response = $this->actingAs($ownerA)->get(route('applications.document.view', [
            'application' => $applicationB,
            'document' => $documentA,
        ]));

        $response->assertNotFound();
    }

    /**
     * @param  array{status: string, deadline_passed: bool, with_commission_member?: bool}  $options
     * @return array{0: User, 1: Application, 2: ApplicationDocument, 3?: User, 4?: Commission}
     */
    private function createApplicationWithDocument(array $options): array
    {
        $applicantRole = Role::where('name', 'konkurs_admin')->firstOrFail();

        $owner = User::factory()->create([
            'role_id' => $applicantRole->id,
            'activation_status' => 'active',
        ]);

        $commission = Commission::create([
            'name' => 'Test komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $deadlinePassed = $options['deadline_passed'];
        $startDate = $deadlinePassed
            ? now()->subDays(30)->toDateString()
            : now()->subDays(2)->toDateString();
        $endDate = $deadlinePassed
            ? now()->subDays(5)->toDateString()
            : now()->addDays(18)->toDateString();

        $competition = Competition::create([
            'title' => 'Test konkurs '.uniqid(),
            'description' => 'Opis',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => $options['status'],
            'submitted_at' => $options['status'] === 'submitted' ? now() : null,
        ]);

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'test.pdf',
            'file_path' => $this->storagePath,
            'document_type' => 'licna_karta',
            'is_required' => true,
        ]);

        $member = null;
        if (! empty($options['with_commission_member'])) {
            $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
            $member = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
            ]);

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $member->id,
                'name' => $member->name,
                'position' => 'clan',
                'member_type' => 'opstina',
                'status' => 'active',
            ]);
        }

        if ($member) {
            return [$owner, $application, $document, $member, $commission];
        }

        return [$owner, $application, $document];
    }

    private function createCommissionUserOnOtherCommission(): User
    {
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $member = User::factory()->create([
            'role_id' => $komisijaRole->id,
            'activation_status' => 'active',
        ]);

        $otherCommission = Commission::create([
            'name' => 'Druga komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        CommissionMember::create([
            'commission_id' => $otherCommission->id,
            'user_id' => $member->id,
            'name' => $member->name,
            'position' => 'clan',
            'member_type' => 'opstina',
            'status' => 'active',
        ]);

        return $member;
    }
}
