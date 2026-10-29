<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CanonicalApplicantForm;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Contract;
use App\Models\MmspTestAccessGrant;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class MmspTestAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_user_without_a_grant_cannot_reach_mmsp_test_resources_or_the_dashboard_row(): void
    {
        $user = $this->account('korisnik');
        $preview = $this->preview('MMSP skriveni test');
        $application = $this->application($preview, $user, 'Skrivena test prijava');
        $document = $this->applicationDocument($application);
        $contract = Contract::query()->create(['application_id' => $application->id]);
        $report = Report::query()->create([
            'application_id' => $application->id,
            'document_file' => 'mmsp-access/izvjestaj-'.$application->id.'.txt',
        ]);
        Storage::disk('local')->put($report->document_file, 'izvjestaj');

        $this->actingAs($user)->get(route('competitions.show', $preview))->assertNotFound();
        $this->actingAs($user)->post(route('applications.start', $preview))->assertNotFound();
        $this->actingAs($user)->get(route('applications.canonical.legal-form', $preview))->assertNotFound();
        $this->actingAs($user)
            ->post(route('applications.canonical.start', $preview), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])
            ->assertNotFound();
        $this->actingAs($user)->get(route('applications.show', $application))->assertNotFound();
        $this->actingAs($user)->get(route('applications.canonical-p1.edit', $application))->assertNotFound();
        $this->actingAs($user)->put(route('applications.canonical-p1.update', $application), [
            'person_name' => 'Nedozvoljena izmjena',
        ])->assertNotFound();
        $this->actingAs($user)->get(route('applications.canonical-p2.edit', $application))->assertNotFound();
        $this->actingAs($user)->get(route('applications.document.view', [$application, $document]))->assertNotFound();
        $this->actingAs($user)->get(route('contracts.show', $contract))->assertNotFound();
        $this->actingAs($user)->get(route('reports.download', $report))->assertNotFound();
        $this->actingAs($user)->post(route('applications.final-submit', $application))->assertNotFound();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('MMSP skriveni test', false);

        $this->assertSame(1, Application::query()->count());
        $this->assertSame(1, ApplicationDocument::query()->count());
        $this->assertSame(0, MmspTestAccessGrant::query()->count());
    }

    public function test_only_admin_and_superadmin_can_grant_and_a_repeat_save_does_not_duplicate(): void
    {
        $admin = $this->account('admin');
        $superadmin = $this->account('superadmin');
        $konkursAdmin = $this->account('konkurs_admin');
        $user = $this->account('korisnik');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user, ['mmsp_test_access' => '1']))
            ->assertRedirect(route('admin.users.show', $user));

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user, ['mmsp_test_access' => '1']))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertSame(1, MmspTestAccessGrant::query()->where('user_id', $user->id)->count());
        $this->assertSame(MmspTestAccessGrant::ACTION_GRANTED, $this->latestAction($user));

        $second = $this->account('korisnik', 'drugi@example.test');
        $this->actingAs($superadmin)
            ->put(route('admin.users.update', $second), $this->payload($second, ['mmsp_test_access' => '1']))
            ->assertRedirect(route('admin.users.show', $second));
        $this->assertSame(MmspTestAccessGrant::ACTION_GRANTED, $this->latestAction($second));

        $this->actingAs($konkursAdmin)
            ->put(route('admin.users.update', $user), $this->payload($user, ['mmsp_test_access' => '0']))
            ->assertRedirect(route('admin.dashboard'));
        $this->actingAs($user)
            ->put(route('admin.users.update', $user), $this->payload($user, ['mmsp_test_access' => '1']))
            ->assertForbidden();

        $this->assertSame(1, MmspTestAccessGrant::query()->where('user_id', $user->id)->count());

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Dozvoli pristup MMSP testnom režimu', false);

        $commission = $this->account('komisija');
        $this->actingAs($admin)
            ->get(route('admin.users.edit', $commission))
            ->assertOk()
            ->assertDontSee('Dozvoli pristup MMSP testnom režimu', false);
    }

    public function test_granted_user_can_use_own_test_applications_on_current_and_future_calls_but_not_someone_elses(): void
    {
        $admin = $this->account('admin');
        $owner = $this->applicant();
        $other = $this->applicant('drugi-podnosilac@example.test');
        $this->grant($admin, $owner);
        $this->grant($admin, $other);

        $preview = $this->preview('MMSP odobreni test');
        $later = $this->preview('MMSP buduci testni konkurs');
        $application = $this->application($preview, $owner, 'Odobrena test prijava');
        $foreign = $this->application($preview, $other, 'Tudja test prijava');
        $document = $this->applicationDocument($application);
        Storage::disk('local')->put($document->file_path, 'dokaz');
        $contract = Contract::query()->create(['application_id' => $application->id]);
        $foreignContract = Contract::query()->create(['application_id' => $foreign->id]);

        $this->actingAs($owner)->get(route('competitions.show', $preview))->assertOk();
        $this->actingAs($owner)->get(route('competitions.show', $later))->assertOk();
        $started = $this->actingAs($owner)
            ->post(route('applications.canonical.start', $later), ['legal_form' => CanonicalLegalForm::PREDUZETNIK]);
        $started->assertRedirect();
        $this->assertNotSame(404, $started->status());

        $this->actingAs($owner)->get(route('applications.show', $application))->assertOk();
        $this->actingAs($owner)->get(route('applications.canonical-p1.edit', $application))->assertOk();
        $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))->assertOk();
        $this->actingAs($owner)->get(route('applications.document.view', [$application, $document]))->assertOk();
        $this->actingAs($owner)->get(route('contracts.show', $contract))->assertOk();
        $this->actingAs($owner)->get(route('contracts.show', $foreignContract))->assertForbidden();

        $this->actingAs($owner)->get(route('applications.show', $foreign))->assertForbidden();
        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('MMSP odobreni test', false);

        $future = $this->preview('MMSP jos nije poceo', [
            'start_date' => now()->addDays(10)->toDateString(),
        ]);
        $this->actingAs($owner)->get(route('competitions.show', $future))->assertNotFound();
    }

    public function test_manual_revoke_hides_data_immediately_and_a_new_grant_restores_it(): void
    {
        $admin = $this->account('admin');
        $user = $this->applicant();
        $preview = $this->preview('MMSP opoziv testa');
        $application = $this->application($preview, $user, 'Prijava poslije opoziva');
        $this->applicationDocument($application);
        $this->grant($admin, $user);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user, ['mmsp_test_access' => '0']))
            ->assertRedirect();

        $this->assertSame(MmspTestAccessGrant::ACTION_REVOKED, $this->latestAction($user));
        $this->assertSame(MmspTestAccessGrant::REASON_MANUAL, $this->latest($user)->reason);
        $this->actingAs($user)->get(route('competitions.show', $preview))->assertNotFound();
        $this->actingAs($user)->get(route('applications.show', $application))->assertNotFound();
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('MMSP opoziv testa', false);
        $this->assertNotNull(Application::query()->find($application->id));
        $this->assertSame(1, ApplicationDocument::query()->where('application_id', $application->id)->count());

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user, ['mmsp_test_access' => '1']))
            ->assertRedirect();

        $this->actingAs($user)->get(route('applications.show', $application))->assertOk();
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('MMSP opoziv testa', false);
    }

    public function test_leaving_korisnik_revokes_the_grant_and_returning_does_not_restore_it(): void
    {
        $admin = $this->account('admin');
        $user = $this->account('korisnik');
        $preview = $this->preview('MMSP uloga testa');
        $this->grant($admin, $user);
        $komisijaId = Role::where('name', 'komisija')->firstOrFail()->id;
        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user, [
                'role_id' => $komisijaId,
                'mmsp_test_access' => '1',
            ]))
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('komisija', $user->role->name);
        $revoked = $this->latest($user);
        $this->assertSame(MmspTestAccessGrant::ACTION_REVOKED, $revoked->action);
        $this->assertSame(MmspTestAccessGrant::REASON_ROLE_CHANGED, $revoked->reason);
        $this->assertSame($admin->id, (int) $revoked->actor_user_id);
        $this->actingAs($user)->get(route('competitions.show', $preview))->assertRedirect(route('dashboard'));

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user->fresh(), [
                'role_id' => $korisnikId,
            ]))
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('korisnik', $user->role->name);
        $this->assertSame(MmspTestAccessGrant::ACTION_REVOKED, $this->latestAction($user));
        $this->actingAs($user)->get(route('competitions.show', $preview))->assertNotFound();

        $this->grant($admin, $user);
        $this->actingAs($user)->get(route('competitions.show', $preview))->assertOk();
    }

    public function test_failed_role_change_transaction_does_not_leave_a_partial_grant_or_role(): void
    {
        $admin = $this->account('admin');
        $user = $this->account('korisnik');
        $this->grant($admin, $user);
        $event = 'eloquent.created: '.MmspTestAccessGrant::class;
        Event::listen($event, function (): void {
            throw new RuntimeException('forced rollback');
        });

        try {
            $this->actingAs($admin)
                ->put(route('admin.users.update', $user), $this->payload($user, [
                    'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
                ]))
                ->assertStatus(500);
        } finally {
            Event::forget($event);
        }

        $user->refresh();
        $this->assertSame('korisnik', $user->role->name);
        $this->assertSame(1, MmspTestAccessGrant::query()->where('user_id', $user->id)->count());
        $this->assertSame(MmspTestAccessGrant::ACTION_GRANTED, $this->latestAction($user));
    }

    public function test_existing_admin_commission_zp_pm_hub_result_and_library_paths_stay_intact(): void
    {
        $admin = $this->account('admin');
        $konkursAdmin = $this->account('konkurs_admin');
        $commission = $this->account('komisija');
        $user = $this->account('korisnik');
        $preview = $this->preview('MMSP admin tok');
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published');
        $youth = $this->competition('omladinsko', 'Javni omladinski poziv', 'published');

        $this->actingAs($konkursAdmin)->get(route('admin.competitions.show', $preview))->assertOk();
        $this->actingAs($konkursAdmin)
            ->post(route('applications.canonical.start', $preview), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])
            ->assertRedirect(route('competitions.show', $preview));
        $this->assertSame(0, Application::query()->where('competition_id', $preview->id)->count());

        $this->attachCommissionMember($preview, $commission);
        $this->actingAs($commission)->get(route('admin.competitions.show', $preview))->assertOk();
        $this->actingAs($commission)
            ->get(route('competitions.show', $preview))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)->get(route('competitions.show', $women))->assertOk();
        $this->actingAs($user)->get(route('competitions.show', $youth))->assertOk();
        $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertSee('U razvoju', false);

        $this->get(route('competitions.canonical-result', $preview))->assertNotFound();

        $library = UserDocument::query()->create([
            'user_id' => $user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Licni dokument van konkursa',
            'file_path' => 'library/licni.pdf',
            'file_size' => 12,
            'status' => 'active',
        ]);
        $this->actingAs($user)
            ->patch(route('documents.update-category', $library), ['category' => 'Lični dokumenti'])
            ->assertRedirect(route('documents.index'));

        $grant = $this->grant($admin, $user);
        $this->expectException(LogicException::class);
        $grant->action = MmspTestAccessGrant::ACTION_REVOKED;
        $grant->save();
    }

    public function test_grant_rows_cannot_be_deleted(): void
    {
        $admin = $this->account('admin');
        $user = $this->account('korisnik');
        $grant = $this->grant($admin, $user);

        $this->expectException(LogicException::class);
        $grant->delete();
    }

    private function attachCommissionMember(Competition $competition, User $member): void
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => CompetitionProfileResolver::TYPE_CANONICAL,
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $member->id,
            'name' => $member->name,
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
            'canonical_seat_no' => 1,
        ]);
        $competition->update(['commission_id' => $commission->id]);
    }

    private function grant(User $actor, User $subject): MmspTestAccessGrant
    {
        $this->actingAs($actor)
            ->put(route('admin.users.update', $subject), $this->payload($subject, ['mmsp_test_access' => '1']))
            ->assertRedirect();

        return $this->latest($subject);
    }

    private function latest(User $user): MmspTestAccessGrant
    {
        return MmspTestAccessGrant::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function latestAction(User $user): string
    {
        return (string) $this->latest($user)->action;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name ?: 'Ime',
            'last_name' => $user->last_name ?: 'Prezime',
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'activation_status' => $user->activation_status ?: 'active',
        ], $overrides);
    }

    private function account(string $role, ?string $email = null): User
    {
        return User::factory()->create([
            'first_name' => 'Ime',
            'last_name' => 'Prezime',
            'name' => 'Ime Prezime',
            'email' => $email ?? $role.'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function applicant(?string $email = null): User
    {
        return User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Podnosilac',
            'name' => 'Test Podnosilac',
            'email' => $email ?? 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'jmb' => '0101990'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
            'user_type' => UserType::ENTREPRENEUR,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function preview(string $title, array $overrides = []): Competition
    {
        return $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            $title,
            'draft',
            array_merge(['is_test_preview' => true], $overrides),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $type, string $title, string $status, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'Opis testa pristupa',
            'type' => $type,
            'status' => $status,
            'year' => (int) now()->year,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(19)->toDateString(),
            'published_at' => $status === 'published' ? now()->subDay() : null,
        ], $overrides));
    }

    private function application(Competition $competition, User $owner, string $name): Application
    {
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'draft',
            'business_plan_name' => $name,
            'founder_name' => $owner->name,
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);
        CanonicalApplicantForm::query()->create([
            'application_id' => $application->id,
            'legal_form' => CanonicalLegalForm::PREDUZETNIK,
        ]);

        return $application;
    }

    private function applicationDocument(Application $application): ApplicationDocument
    {
        return ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Dokaz prijave',
            'file_path' => 'mmsp-access/'.$application->id.'.txt',
            'type' => 'pdf',
            'document_type' => 'licna_karta',
        ]);
    }
}
