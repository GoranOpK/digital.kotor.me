<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CanonicalApplicantForm;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CallPublicationService;
use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\UserType;
use App\Support\CompetitionProgramCatalog;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MmspModuleActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_registered_user_sees_official_and_test_calls_together_and_plain_drafts_stay_hidden(): void
    {
        $user = $this->user('korisnik');
        $preview = $this->competition('TEST 5', 'draft', ['is_test_preview' => true]);
        $official = $this->competition('Zvanični MMSP decembar', 'published', ['is_test_preview' => false]);
        $plain = $this->competition('MMSP obični nacrt', 'draft');
        $women = $this->competition('Ženski poziv', 'published', ['type' => 'zensko', 'is_test_preview' => false]);

        $this->actingAs($user)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk()
            ->assertSee($preview->title, false)
            ->assertSee($official->title, false)
            ->assertDontSee($plain->title, false)
            ->assertDontSee($women->title, false);

        $this->actingAs($user)
            ->get(route('competitions.show', $preview))
            ->assertOk()
            ->assertSee('Prijava nije zvanični zahtjev za subvenciju', false);
        $this->actingAs($user)
            ->get(route('competitions.show', $official))
            ->assertOk()
            ->assertSee('Prijavi se na konkurs', false)
            ->assertDontSee('Prijava nije zvanični zahtjev za subvenciju', false);
        $this->actingAs($user)->get(route('competitions.show', $plain))->assertNotFound();

        $started = $this->actingAs($user)->post(route('applications.canonical.start', $official), [
            'legal_form' => CanonicalLegalForm::PREDUZETNIK,
        ]);
        $application = Application::query()->where('competition_id', $official->id)->sole();
        $started->assertRedirect(route('applications.canonical-p1.edit', $application));
        $this->actingAs($user)
            ->get(route('applications.canonical-p1.edit', $application))
            ->assertOk()
            ->assertDontSee('Prijava nije zvanični zahtjev za subvenciju', false);

        $this->assertSame('draft', $preview->fresh()->status);
        $this->assertTrue((bool) $preview->fresh()->is_test_preview);
        $this->assertSame('published', $official->fresh()->status);
    }

    public function test_december_publish_allows_several_official_calls_and_refuses_a_test_preview(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-10 09:00:00', 'Europe/Belgrade'));
        $admin = $this->user('konkurs_admin');
        $first = $this->competition('Prvi zvanični', 'draft', ['year' => 2026, 'start_date' => '2026-12-10']);
        $second = $this->competition('Drugi zvanični', 'draft', ['year' => 2026, 'start_date' => '2026-12-10']);
        $preview = $this->competition('TEST 5', 'draft', [
            'is_test_preview' => true,
            'year' => 2026,
            'start_date' => '2026-12-10',
        ]);

        $this->actingAs($admin)->post(route('admin.competitions.publish', $first))->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.competitions.publish', $second))->assertSessionHas('success');
        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $preview))
            ->post(route('admin.competitions.publish', $preview))
            ->assertSessionHasErrors('error');

        $first->refresh();
        $second->refresh();
        $preview->refresh();
        $this->assertSame('published', $first->status);
        $this->assertSame('published', $second->status);
        $this->assertSame(20, (int) $first->deadline_days);
        $this->assertSame(20, (int) $second->deadline_days);
        $this->assertSame('2026-12-30', $first->end_date->toDateString());
        $this->assertSame('draft', $preview->status);
        $this->assertTrue((bool) $preview->is_test_preview);

        $this->assertSame(2, CompetitionPublicationClaim::query()->count());
        $this->assertSame('call_'.$first->id, CompetitionPublicationClaim::query()->where('competition_id', $first->id)->value('publication_slot'));
        $this->assertSame('call_'.$second->id, CompetitionPublicationClaim::query()->where('competition_id', $second->id)->value('publication_slot'));

        $repeat = app(CallPublicationService::class)->publish($first, $admin);
        $this->assertTrue($repeat->succeeded);
        $this->assertTrue($repeat->idempotent);
        $this->assertSame(2, CompetitionPublicationClaim::query()->count());
    }

    public function test_admin_and_public_panels_mark_mmsp_active_without_opening_admin_routes_to_users(): void
    {
        $admin = $this->user('konkurs_admin');
        $user = $this->user('korisnik');

        $adminPage = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $adminPage->assertSee('Aktivan modul', false);
        $adminPage->assertSee('Podrška mikro, malim i srednjim preduzećima', false);
        $adminPage->assertSee('Konkursi za podršku preduzetnicima, mikro, malim i srednjim preduzećima — prijave, dokumentacija, komisija i evaluacija.', false);
        $adminPage->assertSee('Kompletan modul — prijave, dokumentacija, komisija i evaluacija.', false);
        $adminPage->assertSee('Konkurs za podršku preduzetništvu mladih — prijave, dokumentacija, komisija i evaluacija.', false);
        $adminPage->assertDontSee('Zvanični MMSP modul. Testni nacrti su posebno označeni, a prijava na njih nije zvanični zahtjev za subvenciju.', false);
        $adminPage->assertDontSee('Profil je u implementaciji i još nije pušten korisnicima.', false);
        $adminPage->assertDontSee('U razvoju', false);
        $adminPage->assertSee(
            e(route('admin.competitions.index', [
                'type' => CompetitionProfileResolver::TYPE_CANONICAL,
                'tab' => 'active',
            ])),
            false
        );

        $publicPage = $this->actingAs($user)->get(route('competitions.index'))->assertOk();
        $publicPage->assertSee('Aktivan modul', false);
        $publicPage->assertDontSee('U razvoju', false);
        $this->assertStringNotContainsString(route('admin.competitions.index', absolute: false), $publicPage->getContent());

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()[CompetitionProfileResolver::TYPE_CANONICAL]['status']
        );
    }

    public function test_women_and_youth_publication_rules_stay_unchanged(): void
    {
        $women = CompetitionProfileResolver::resolve('zensko');
        $youth = CompetitionProfileResolver::resolve('omladinsko');

        $this->assertSame(CompetitionProfilePolicy::PUBLICATION_UNRESTRICTED, $women->publicationMode());
        $this->assertSame(CompetitionProfilePolicy::PUBLICATION_INACTIVE, $youth->publicationMode());
        $this->assertSame(2, $youth->publicationQuarter());
        $this->assertSame(['first_call', 'second_call'], $youth->publicationSlots());
        $this->assertTrue($youth->allowsSecondCall());
        $this->assertSame(
            'Konkurs za podršku preduzetništvu mladih — prijave, dokumentacija, komisija i evaluacija.',
            CompetitionProgramCatalog::definitions()['omladinsko']['description']
        );
        $this->assertSame(
            'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.',
            CompetitionProgramCatalog::definitions()['zensko']['description']
        );
    }

    public function test_confirmed_removal_deletes_only_the_mmsp_test_preview_and_its_applications(): void
    {
        $admin = $this->user('konkurs_admin');
        $owner = $this->user('korisnik');
        $commission = Commission::create([
            'name' => 'Zajednička komisija',
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $admin->id,
            'name' => 'Član komisije',
            'position' => 'predsjednik',
            'member_type' => 'opstina',
        ]);
        $preview = $this->competition('TEST 5', 'draft', [
            'is_test_preview' => true,
            'commission_id' => $commission->id,
        ]);
        $official = $this->competition('Zvanični MMSP', 'published', [
            'commission_id' => $commission->id,
        ]);
        CompetitionPublicationClaim::query()->create([
            'competition_id' => $official->id,
            'calendar_year' => 2026,
            'competition_type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'publication_slot' => 'call_'.$official->id,
            'claimed_at' => now(),
        ]);
        $women = $this->competition('Ženski poziv ostaje', 'published', ['type' => 'zensko']);
        $application = Application::create([
            'competition_id' => $preview->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'draft',
        ]);
        CanonicalApplicantForm::query()->create([
            'application_id' => $application->id,
            'legal_form' => CanonicalLegalForm::PREDUZETNIK,
        ]);
        Storage::disk('local')->put('mmsp-test/'.$application->id.'.txt', 'dokaz');
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Dokaz',
            'file_path' => 'mmsp-test/'.$application->id.'.txt',
            'type' => 'pdf',
            'document_type' => 'licna_karta',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $preview))
            ->post(route('admin.competitions.mmsp-test-preview.remove', $preview), [
                'confirmation' => 'POGREŠNO',
            ])
            ->assertSessionHasErrors('confirmation');
        $this->assertNotNull(Competition::query()->find($preview->id));

        $this->actingAs($owner)
            ->post(route('admin.competitions.mmsp-test-preview.remove', $preview), [
                'confirmation' => 'UKLONI TEST '.$preview->id,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.competitions.mmsp-test-preview.remove', $official), [
                'confirmation' => 'UKLONI TEST '.$official->id,
            ])
            ->assertSessionHasErrors('confirmation');
        $this->actingAs($admin)
            ->post(route('admin.competitions.mmsp-test-preview.remove', $women), [
                'confirmation' => 'UKLONI TEST '.$women->id,
            ])
            ->assertSessionHasErrors('confirmation');

        $this->actingAs($admin)
            ->post(route('admin.competitions.mmsp-test-preview.remove', $preview), [
                'confirmation' => 'UKLONI TEST '.$preview->id,
            ])
            ->assertRedirect(route('admin.competitions.index'));

        $this->assertNull(Competition::query()->find($preview->id));
        $this->assertNull(Application::query()->find($application->id));
        $this->assertSame(0, CanonicalApplicantForm::query()->count());
        $this->assertNotNull(Competition::query()->find($official->id));
        $this->assertNotNull(Competition::query()->find($women->id));
        $this->assertSame(1, CompetitionPublicationClaim::query()->count());
        $this->assertNotNull(Commission::query()->find($commission->id));
        $this->assertSame(1, CommissionMember::query()->where('commission_id', $commission->id)->count());
        Storage::disk('local')->assertMissing('mmsp-test/'.$application->id.'.txt');
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => $role === 'korisnik' ? UserType::PHYSICAL_PERSON : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $title, string $status, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'Opis',
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => $status,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(19)->toDateString(),
            'published_at' => $status === 'published' ? now()->subDay() : null,
            'is_test_preview' => false,
        ], $overrides));
    }
}
