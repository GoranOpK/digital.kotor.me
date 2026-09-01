<?php

namespace Tests\Feature;

use App\Events\OfficialContentReadyForPublicPublication;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompetitionOfficialDecisionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_konkurs_admin_can_upload_signed_copy_for_completed_competition(): void
    {
        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');
        $noticesBefore = Notice::query()->count();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('odluka.pdf')),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $copy = CompetitionOfficialDecisionCopy::query()->sole();
        $this->assertSame($competition->id, $copy->competition_id);
        $this->assertSame($admin->id, $copy->uploaded_by);
        $this->assertSame('Odluka test primjerka', $copy->business_title);
        $this->assertNotSame('odluka.pdf', $copy->business_title);
        $this->assertNotSame('', (string) $copy->storage_path);
        Storage::disk('local')->assertExists($copy->storage_path);
        $this->assertSame($noticesBefore, Notice::query()->count());
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
    }

    public function test_second_upload_creates_a_new_immutable_copy_without_overwrite(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('prva.pdf'), 'Prva odluka'),
        )->assertRedirect();

        $first = CompetitionOfficialDecisionCopy::query()->sole();
        $firstPath = $first->storage_path;
        $firstBytes = Storage::disk('local')->get($firstPath);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('druga.pdf'), 'Druga odluka'),
        )->assertRedirect();

        $this->assertSame(2, CompetitionOfficialDecisionCopy::query()->count());

        $first->refresh();
        $second = CompetitionOfficialDecisionCopy::query()->where('id', '!=', $first->id)->sole();

        $this->assertSame($firstPath, $first->storage_path);
        $this->assertSame($firstBytes, Storage::disk('local')->get($firstPath));
        Storage::disk('local')->assertExists($firstPath);
        Storage::disk('local')->assertExists($second->storage_path);
        $this->assertNotSame($first->storage_path, $second->storage_path);
        $this->assertSame($competition->id, $second->competition_id);
    }

    public function test_admin_cannot_upload_signed_copy(): void
    {
        $this->assertRoleCannotUpload('admin');
    }

    public function test_superadmin_cannot_upload_signed_copy(): void
    {
        $this->assertRoleCannotUpload('superadmin');
    }

    public function test_commission_member_cannot_upload_signed_copy(): void
    {
        $this->assertRoleCannotUpload('komisija');
    }

    public function test_ordinary_user_cannot_upload_signed_copy(): void
    {
        $this->assertRoleCannotUpload('korisnik');
    }

    public function test_upload_is_rejected_when_competition_is_not_closed(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('published');

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('odluka.pdf')),
        );

        $response->assertForbidden();
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_closed_competition_allows_upload(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('closed');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('odluka.pdf')),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $this->assertSame(1, CompetitionOfficialDecisionCopy::query()->count());
    }

    public function test_executable_upload_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload(UploadedFile::fake()->create('malware.exe', 20, 'application/x-msdownload')),
        );

        $response->assertSessionHasErrors('official_decision_copy');
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_larger_than_two_megabytes_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload(UploadedFile::fake()->create('odluka.pdf', 2049, 'application/pdf')),
        );

        $response->assertSessionHasErrors('official_decision_copy');
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_db_failure_after_storage_write_does_not_leave_orphan_file(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        CompetitionOfficialDecisionCopy::creating(function () {
            throw new \RuntimeException('Forced DB failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                route('admin.competitions.official-decision.store', $competition),
                $this->storePayload($this->pdfUpload('odluka.pdf')),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced DB failure', $exception->getMessage());
        } finally {
            CompetitionOfficialDecisionCopy::flushEventListeners();
        }

        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_completed_show_page_exposes_upload_form_without_publish_actions(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));

        $response->assertOk();
        $response->assertSee('Zvanična Odluka', false);
        $response->assertSee('Naziv dokumenta', false);
        $response->assertSee('name="business_title"', false);
        $response->assertSee('name="official_decision_copy"', false);
        $response->assertSee('Postavi primjerak', false);
        $response->assertDontSee('Koriguj', false);
        $response->assertDontSee('Objavi Odluku', false);
        $response->assertDontSee('Povuci', false);
        $response->assertDontSee('Ispravi podatke objave', false);
        $response->assertDontSee('Ponovo objavi', false);
        $response->assertDontSee('Trajno obriši', false);
    }

    public function test_upload_without_business_title_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.store', $competition),
            ['official_decision_copy' => $this->pdfUpload('odluka.pdf')],
        );

        $response->assertSessionHasErrors('business_title');
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_whitespace_only_business_title_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('odluka.pdf'), '   '),
        );

        $response->assertSessionHasErrors('business_title');
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_business_title_longer_than_255_characters_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('odluka.pdf'), str_repeat('a', 256)),
        );

        $response->assertSessionHasErrors('business_title');
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_uploaded_copy_stores_entered_business_title_not_original_filename(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('completed');
        $title = 'Odluka o dodjeli za 2026. godinu';

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('izvorni-fajl.pdf'), $title),
        )->assertRedirect();

        $copy = CompetitionOfficialDecisionCopy::query()->sole();
        $this->assertSame($title, $copy->business_title);
        $this->assertNotSame('izvorni-fajl.pdf', $copy->business_title);
        Storage::disk('local')->assertExists($copy->storage_path);
    }

    private function storePayload(UploadedFile $file, string $title = 'Odluka test primjerka'): array
    {
        return [
            'official_decision_copy' => $file,
            'business_title' => $title,
        ];
    }

    private function assertRoleCannotUpload(string $roleName): void
    {
        $user = $this->userWithRole($roleName);
        $competition = $this->createCompetition('completed');

        $response = $this->actingAs($user)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storePayload($this->pdfUpload('odluka.pdf')),
        );

        $response->assertForbidden();
        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    private function pdfUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 120, 'application/pdf');
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function createCompetition(string $status): Competition
    {
        return Competition::create([
            'title' => 'Konkurs za upload Odluke',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => $status,
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'closed_at' => in_array($status, ['closed', 'completed'], true) ? now()->subDay() : null,
        ]);
    }
}
