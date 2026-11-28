<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\Contract;
use App\Models\MmspTestAccessGrant;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\UserType;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MmspTestPreviewHubListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CompetitionProgramCatalog::clearTestOverrides();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_ordinary_user_without_a_grant_opens_only_test_drafts_and_can_submit_p1_and_p2(): void
    {
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()[CompetitionProfileResolver::TYPE_CANONICAL]['status']
        );
        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable(CompetitionProfileResolver::TYPE_CANONICAL));

        $owner = $this->user('korisnik');
        $second = $this->user('korisnik');
        $owner->forceFill(['user_type' => UserType::PHYSICAL_PERSON])->save();
        $second->forceFill(['user_type' => UserType::LIMITED_LIABILITY_COMPANY])->save();
        $preview = $this->preview('TEST 5 – Podrška preduzetnicima i MMSP 2026');
        $closedLater = $this->preview('Zatvorena testna faza', [
            'start_date' => now()->addDays(10)->toDateString(),
            'canonical_test_applications_closed_at' => now(),
        ]);
        $future = $this->preview('MMSP test još nije počeo', [
            'start_date' => now()->addDays(12)->toDateString(),
        ]);
        $plainDraft = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'MMSP nacrt bez testnog pregleda',
            'draft'
        );
        $published = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP poziv',
            'published'
        );
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published');

        $hub = $this->actingAs($owner)->get(route('competitions.index'))->assertOk();
        $html = $hub->getContent();
        $hub->assertSee('Aktivan modul', false)
            ->assertSee('Konkursi za podršku preduzetnicima, mikro, malim i srednjim preduzećima — prijave, dokumentacija, komisija i evaluacija.', false)
            ->assertDontSee('U razvoju', false)
            ->assertDontSee($preview->title, false)
            ->assertDontSee($published->title, false);
        $this->assertStringContainsString(
            'href="'.route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]).'"',
            $html
        );
        $this->assertSame([2, 3], $this->linkedStatValues($html, CompetitionProfileResolver::TYPE_CANONICAL));
        $this->assertSame([1, 1], $this->linkedStatValues($html, 'zensko'));

        $list = $this->actingAs($owner)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk()
            ->assertSee('Nazad na konkurse', false)
            ->assertSee($preview->title, false)
            ->assertSee($closedLater->title, false)
            ->assertDontSee($future->title, false)
            ->assertDontSee($plainDraft->title, false)
            ->assertSee($published->title, false)
            ->assertDontSee($women->title, false)
            ->assertSee('Preuzmi uputstvo (PDF)', false)
            ->assertSee(route('competitions.mmsp-guide.pdf'), false)
            ->assertDontSee(route('competitions.guide.pdf'), false)
            ->assertDontSee(route('competitions.youth-guide.pdf'), false);
        $this->assertStringNotContainsString('page-header is-hub', $list->getContent());

        $this->actingAs($owner)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->assertSee($women->title, false)
            ->assertDontSee($preview->title, false)
            ->assertDontSee($published->title, false)
            ->assertSee('Preuzmi uputstvo (PDF)', false);

        $this->actingAs($owner)
            ->get(route('competitions.show', $preview))
            ->assertOk()
            ->assertSee('Prijavi se na konkurs', false)
            ->assertSee('Prijava nije zvanični zahtjev za subvenciju', false);
        $this->actingAs($owner)
            ->get(route('competitions.show', $published))
            ->assertOk()
            ->assertDontSee('Prijava nije zvanični zahtjev za subvenciju', false);
        $this->actingAs($owner)->get(route('competitions.show', $plainDraft))->assertNotFound();
        $this->actingAs($owner)->get(route('competitions.show', $future))->assertNotFound();

        $p1a = $this->actingAs($owner)->post(route('applications.canonical.start', $preview), [
            'legal_form' => CanonicalLegalForm::PREDUZETNIK,
        ]);
        $first = Application::query()->where('user_id', $owner->id)->sole();
        $p1a->assertRedirect(route('applications.canonical-p1.edit', $first));
        $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $first->canonicalApplicantForm->legal_form);
        $this->actingAs($owner)
            ->get(route('applications.canonical-p1.edit', $first))
            ->assertOk()
            ->assertSee('Obrazac P1a', false)
            ->assertSee('Prijava nije zvanični zahtjev za subvenciju', false);
        $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $first))
            ->assertOk()
            ->assertSee('Prijava nije zvanični zahtjev za subvenciju', false);

        $p1b = $this->actingAs($second)->post(route('applications.canonical.start', $preview), [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
        ]);
        $company = Application::query()->where('user_id', $second->id)->sole();
        $p1b->assertRedirect(route('applications.canonical-p1.edit', $company));
        $this->assertSame(CanonicalLegalForm::DRUSTVO, $company->canonicalApplicantForm->legal_form);
        $this->actingAs($second)
            ->get(route('applications.canonical-p1.edit', $company))
            ->assertOk()
            ->assertSee('Obrazac P1b', false);

        $preview->refresh();
        $this->assertSame('draft', $preview->status);
        $this->assertTrue((bool) $preview->is_test_preview);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
        $this->assertSame(0, Contract::query()->count());
        $this->assertSame(0, MmspTestAccessGrant::query()->count());
    }

    public function test_revoked_grant_still_opens_a_test_draft_while_other_roles_stay_restricted(): void
    {
        $actor = $this->user('admin');
        $revoked = $this->user('korisnik');
        $this->grant($actor, $revoked);
        MmspTestAccessGrant::query()->create([
            'user_id' => $revoked->id,
            'actor_user_id' => $actor->id,
            'action' => MmspTestAccessGrant::ACTION_REVOKED,
            'reason' => MmspTestAccessGrant::REASON_MANUAL,
        ]);
        $preview = $this->preview('TEST 5 opozvan');
        $published = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP ostaje sakriven',
            'published'
        );

        $html = $this->actingAs($revoked)->get(route('competitions.index'))->assertOk()->getContent();
        $this->assertStringContainsString(
            route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]),
            $html
        );
        $this->actingAs($revoked)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk()
            ->assertSee($preview->title, false)
            ->assertSee($published->title, false);
        $this->actingAs($revoked)->get(route('competitions.show', $preview))->assertOk();
        $this->actingAs($revoked)->get(route('competitions.show', $published))->assertOk();

        $this->actingAs($this->user('komisija'))
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($this->user('kk_admin'))
            ->get(route('competitions.index'))
            ->assertRedirect(route('cultural-calendar.index'));
    }

    public function test_granted_user_opens_only_test_drafts_and_can_start_p1a_and_p1b(): void
    {
        $actor = $this->user('admin');
        $owner = $this->user('korisnik');
        $second = $this->user('korisnik');
        $owner->forceFill(['user_type' => UserType::PHYSICAL_PERSON])->save();
        $second->forceFill(['user_type' => UserType::LIMITED_LIABILITY_COMPANY])->save();
        $this->grant($actor, $owner);
        $this->grant($actor, $second);

        $preview = $this->preview('TEST 5 – Podrška preduzetnicima i MMSP 2026');
        $closedLater = $this->preview('Zatvorena testna faza', [
            'start_date' => now()->addDays(10)->toDateString(),
            'canonical_test_applications_closed_at' => now(),
        ]);
        $future = $this->preview('MMSP test još nije počeo', [
            'start_date' => now()->addDays(12)->toDateString(),
        ]);
        $plainDraft = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'MMSP nacrt bez testnog pregleda',
            'draft'
        );
        $published = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP poziv',
            'published'
        );
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published');

        $hub = $this->actingAs($owner)->get(route('competitions.index'))->assertOk();
        $html = $hub->getContent();
        $this->assertStringContainsString(
            'href="'.route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]).'"',
            $html
        );
        $this->assertStringContainsString('Aktivan modul', $html);
        $this->assertStringNotContainsString('U razvoju', $html);
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()[CompetitionProfileResolver::TYPE_CANONICAL]['status']
        );
        $this->assertSame([2, 3], $this->linkedStatValues($html, CompetitionProfileResolver::TYPE_CANONICAL));
        $this->assertSame([1, 1], $this->linkedStatValues($html, 'zensko'));

        $list = $this->actingAs($owner)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk()
            ->assertSee('Nazad na konkurse', false)
            ->assertSee($preview->title, false)
            ->assertSee($closedLater->title, false)
            ->assertDontSee($future->title, false)
            ->assertDontSee($plainDraft->title, false)
            ->assertSee($published->title, false)
            ->assertDontSee($women->title, false)
            ->assertSee('Preuzmi uputstvo (PDF)', false)
            ->assertSee(route('competitions.mmsp-guide.pdf'), false)
            ->assertDontSee(route('competitions.guide.pdf'), false)
            ->assertDontSee(route('competitions.youth-guide.pdf'), false);
        $this->assertStringNotContainsString('page-header is-hub', $list->getContent());

        $this->actingAs($owner)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->assertSee($women->title, false)
            ->assertDontSee($preview->title, false)
            ->assertDontSee($published->title, false);

        $this->actingAs($owner)
            ->get(route('competitions.show', $preview))
            ->assertOk()
            ->assertSee('Prijavi se na konkurs', false);

        $p1a = $this->actingAs($owner)->post(route('applications.canonical.start', $preview), [
            'legal_form' => CanonicalLegalForm::PREDUZETNIK,
        ]);
        $first = Application::query()->where('user_id', $owner->id)->sole();
        $p1a->assertRedirect(route('applications.canonical-p1.edit', $first));
        $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $first->canonicalApplicantForm->legal_form);
        $this->get(route('applications.canonical-p1.edit', $first))->assertOk()->assertSee('Obrazac P1a', false);

        $p1b = $this->actingAs($second)->post(route('applications.canonical.start', $preview), [
            'legal_form' => CanonicalLegalForm::DRUSTVO,
        ]);
        $company = Application::query()->where('user_id', $second->id)->sole();
        $p1b->assertRedirect(route('applications.canonical-p1.edit', $company));
        $this->assertSame(CanonicalLegalForm::DRUSTVO, $company->canonicalApplicantForm->legal_form);
        $this->get(route('applications.canonical-p1.edit', $company))->assertOk()->assertSee('Obrazac P1b', false);

        $preview->refresh();
        $this->assertSame('draft', $preview->status);
        $this->assertTrue((bool) $preview->is_test_preview);
    }

    public function test_admin_roles_can_open_the_test_list_without_a_personal_grant(): void
    {
        $preview = $this->preview('TEST 5 za administraciju');
        $published = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP ostaje van liste',
            'published'
        );

        foreach (['admin', 'superadmin', 'konkurs_admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
                ->assertOk()
                ->assertSee($preview->title, false)
                ->assertSee($published->title, false)
                ->assertDontSee('page-header is-hub', false);
        }
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function grant(User $actor, User $subject): void
    {
        MmspTestAccessGrant::query()->create([
            'user_id' => $subject->id,
            'actor_user_id' => $actor->id,
            'action' => MmspTestAccessGrant::ACTION_GRANTED,
            'reason' => MmspTestAccessGrant::REASON_MANUAL,
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
            array_merge(['is_test_preview' => true], $overrides)
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $type, string $title, string $status, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'Opis testne liste',
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

    /**
     * @return array{0: int, 1: int}
     */
    private function developmentStatValues(string $html): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " is-development ")]');
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length);
        $card = $dom->saveHTML($nodes->item(0));
        $this->assertIsString($card);

        return $this->statValues($card);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function linkedStatValues(string $html, string $type): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query(
            '//a[contains(concat(" ", normalize-space(@class), " "), " program-card ") and contains(@href, "type='.$type.'")]'
        );
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length);
        $card = $dom->saveHTML($nodes->item(0));
        $this->assertIsString($card);

        return $this->statValues($card);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function statValues(string $cardHtml): array
    {
        preg_match_all('/class="program-stat-value">([^<]*)</', $cardHtml, $matches);
        $this->assertCount(2, $matches[1], $cardHtml);

        return [(int) trim($matches[1][0]), (int) trim($matches[1][1])];
    }
}
