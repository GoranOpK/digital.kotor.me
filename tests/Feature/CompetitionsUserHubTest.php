<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionsUserHubTest extends TestCase
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

    public function test_guest_is_redirected_from_the_competitions_hub(): void
    {
        $this->get(route('competitions.index'))->assertRedirect(route('login'));
        $this->get(route('competitions.index', ['type' => 'zensko']))->assertRedirect(route('login'));
    }

    public function test_verified_user_sees_three_program_cards_without_internal_data(): void
    {
        $user = $this->userWithRole('korisnik');
        $draft = $this->competition('zensko', 'Skriveni zenski nacrt', 'draft');

        $response = $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk();
        $html = $response->getContent();
        $text = $this->visiblePageText($html);

        $response->assertSee('Podrška ženskom preduzetništvu', false);
        $response->assertSee('Podrška preduzetništvu mladih', false);
        $response->assertSee('Podrška mikro, malim i srednjim preduzećima', false);
        $this->assertSame(2, substr_count($text, 'Aktivan modul'));
        $this->assertSame(1, substr_count($text, 'U razvoju'));
        $this->assertStringContainsString(
            'href="'.route('competitions.index', ['type' => 'zensko']).'"',
            $html
        );
        $this->assertStringContainsString(
            'href="'.route('competitions.index', ['type' => 'omladinsko']).'"',
            $html
        );
        $this->assertStringNotContainsString(route('admin.competitions.index', absolute: false), $html);
        $this->assertStringNotContainsString(route('admin.commissions.index', absolute: false), $html);
        $this->assertStringNotContainsString($draft->title, $html);
        $this->assertStringNotContainsString(route('competitions.guide.pdf', absolute: false), $html);
        $this->assertStringContainsString('class="page-header is-hub"', $html);
        $this->assertStringContainsString('Konkursi za podršku preduzetništvu', $text);
        $this->assertStringNotContainsString(
            'Pregled objavljenih konkursa za podršku preduzetništvu iz budžeta Opštine Kotor',
            $text
        );
        $this->assertSame(3, $this->programCardCount($html));
        $this->assertSame(3, substr_count($html, 'program-stat-label">Aktivni'));
        $this->assertSame(3, substr_count($html, 'program-stat-label">Ukupno'));
        $this->assertStringNotContainsString('program-stat-label">Prijave', $html);
        $this->assertStringContainsString('grid-template-columns: repeat(2, 1fr)', $html);

        $this->assertSame([0, 0], $this->programStatValues($this->linkedProgramCardHtml($html, 'zensko')));
        $this->assertSame([0, 0], $this->programStatValues($this->linkedProgramCardHtml($html, 'omladinsko')));

        $development = $this->developmentCardHtml($html);
        $this->assertStringContainsString('is-development', $development);
        $this->assertStringContainsString('U razvoju', $this->visiblePageText($development));
        $this->assertStringNotContainsString('href', $development);
        $this->assertStringNotContainsString('admin', $development);
        $this->assertStringNotContainsString('Prijavi', $development);
        $this->assertSame([0, 0], $this->programStatValues($development));

        $this->assertSame(2, $this->programCardLinkCount($html));
    }

    public function test_unknown_and_mmsp_type_parameters_stay_on_the_hub(): void
    {
        $user = $this->userWithRole('korisnik');
        $publishedMmsp = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Objavljeni MMSP poziv',
            'published'
        );

        foreach (['ostalo', 'mmsp', CompetitionProfileResolver::TYPE_CANONICAL, 'zensko-extra'] as $type) {
            $this->actingAs($user)
                ->get(route('competitions.index', ['type' => $type]))
                ->assertOk()
                ->assertSee('Aktivan modul')
                ->assertSee('U razvoju')
                ->assertDontSee($publishedMmsp->title)
                ->assertDontSee('Nazad na konkurse');
        }
    }

    public function test_public_filters_keep_existing_visibility_and_back_link(): void
    {
        $user = $this->userWithRole('korisnik');
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published');
        $youth = $this->competition('omladinsko', 'Javni omladinski poziv', 'published');
        $draft = $this->competition('zensko', 'Nacrt zenskog poziva', 'draft');
        $upcoming = $this->competition('omladinsko', 'Buduci omladinski poziv', 'published', [
            'start_date' => now()->addDays(5)->toDateString(),
        ]);
        $canonical = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Objavljeni MMSP ostaje sakriven',
            'published'
        );

        $womenList = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->assertSee($women->title)
            ->assertSee('Nazad na konkurse')
            ->assertSee('Uputstvo za podnosioce (PDF)')
            ->assertDontSee($youth->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($canonical->title);
        $womenHtml = $womenList->getContent();
        $this->assertStringContainsString(
            'href="'.route('competitions.index').'"',
            $womenHtml
        );
        $this->assertStringNotContainsString('program-stat', $womenHtml);
        $this->assertStringNotContainsString('page-header is-hub', $womenHtml);

        $youthHtml = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->assertSee($youth->title)
            ->assertSee('Nazad na konkurse')
            ->assertDontSee('Uputstvo za podnosioce (PDF)')
            ->assertDontSee($women->title)
            ->assertDontSee($upcoming->title)
            ->assertDontSee($canonical->title)
            ->getContent();
        $this->assertStringNotContainsString('program-stat', $youthHtml);
        $this->assertStringNotContainsString('page-header is-hub', $youthHtml);

        $this->actingAs($user)
            ->get(route('competitions.show', $women))
            ->assertOk()
            ->assertSee($women->title);
        $this->actingAs($user)
            ->get(route('competitions.show', $canonical))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('competitions.show', $upcoming))
            ->assertNotFound();
    }

    public function test_empty_filtered_list_names_the_category_and_hides_the_guide(): void
    {
        $user = $this->userWithRole('korisnik');

        $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->assertSee('Nazad na konkurse')
            ->assertSee('Trenutno nema objavljenih konkursa')
            ->assertSee('Podrška ženskom preduzetništvu')
            ->assertDontSee('Uputstvo za podnosioce (PDF)');
    }

    public function test_canonical_test_preview_stays_off_the_hub_and_direct_url_is_hidden_without_a_grant(): void
    {
        $user = $this->userWithRole('korisnik');
        $preview = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'MMSP testni pregled ostaje',
            'draft',
            ['is_test_preview' => true]
        );

        $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertDontSee($preview->title);

        $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->assertDontSee($preview->title);

        $this->actingAs($user)
            ->get(route('competitions.show', $preview))
            ->assertNotFound();
    }

    public function test_restricted_roles_keep_their_competition_access_rules(): void
    {
        $kkAdmin = $this->userWithRole('kk_admin');
        $this->actingAs($kkAdmin)
            ->get(route('competitions.index'))
            ->assertRedirect(route('cultural-calendar.index'));

        $commission = $this->userWithRole('komisija');
        $this->actingAs($commission)
            ->get(route('competitions.index'))
            ->assertRedirect(route('dashboard'));

        $competitionAdmin = $this->userWithRole('konkurs_admin');
        $html = $this->actingAs($competitionAdmin)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertSee('U razvoju')
            ->getContent();
        $this->assertStringNotContainsString(route('admin.competitions.index', absolute: false), $html);
    }

    public function test_user_stats_count_only_the_public_set_and_admin_counts_stay_by_status(): void
    {
        $user = $this->userWithRole('korisnik');
        $admin = $this->userWithRole('konkurs_admin');

        $this->competition('zensko', 'Otvoren zenski poziv', 'published');
        $this->competition('zensko', 'Istekao zenski poziv', 'published', [
            'start_date' => now()->subDays(40)->toDateString(),
            'published_at' => now()->subDays(40),
        ]);
        $this->competition('zensko', 'Nacrt zenskog poziva', 'draft');
        $this->competition('zensko', 'Buduci zenski poziv', 'published', [
            'start_date' => now()->addDays(5)->toDateString(),
        ]);
        $this->competition('zensko', 'Zatvoren zenski poziv', 'closed');
        $this->competition('zensko', 'Zavrsen zenski poziv', 'completed');
        $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'MMSP testni pregled',
            'draft',
            ['is_test_preview' => true]
        );

        $userHtml = $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(3, $this->programCardCount($userHtml));
        $this->assertSame([1, 2], $this->programStatValues($this->linkedProgramCardHtml($userHtml, 'zensko')));
        $this->assertSame([0, 0], $this->programStatValues($this->linkedProgramCardHtml($userHtml, 'omladinsko')));
        $development = $this->developmentCardHtml($userHtml);
        $this->assertSame([0, 0], $this->programStatValues($development));
        $this->assertStringNotContainsString('href', $development);
        $this->assertStringNotContainsString('MMSP testni pregled', $userHtml);
        $this->assertStringNotContainsString('program-stat-label">Prijave', $userHtml);

        $adminHtml = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Rok za prijavu', false)
            ->getContent();

        $this->assertSame(3, $this->programCardCount($adminHtml));
        $this->assertStringContainsString('grid-template-columns: repeat(2, 1fr)', $adminHtml);
        $this->assertStringNotContainsString('repeat(3, 1fr)', $adminHtml);
        $this->assertStringNotContainsString('program-stat-label">Prijave', $adminHtml);
        $this->assertSame([4, 6], $this->programStatValues($this->linkedProgramCardHtml($adminHtml, 'zensko')));
        $this->assertSame([0, 0], $this->programStatValues($this->linkedProgramCardHtml($adminHtml, 'omladinsko')));
        $this->assertSame(
            [1, 1],
            $this->programStatValues($this->linkedProgramCardHtml(
                $adminHtml,
                CompetitionProfileResolver::TYPE_CANONICAL
            ))
        );
        foreach (['zensko', 'omladinsko', CompetitionProfileResolver::TYPE_CANONICAL] as $type) {
            $this->assertStringContainsString(
                'href="'.e(route('admin.competitions.index', ['type' => $type, 'tab' => 'active'])).'"',
                $adminHtml
            );
        }
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $type, string $title, string $status, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'Opis javnog huba',
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

    private function visiblePageText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function developmentCardHtml(string $html): string
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

        return $card;
    }

    private function programCardLinkCount(string $html): int
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " program-card ")]');
        $this->assertNotFalse($nodes);

        return $nodes->length;
    }

    private function programCardCount(string $html): int
    {
        $xpath = $this->htmlXpath($html);
        $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " program-card ")]');
        $this->assertNotFalse($nodes);

        return $nodes->length;
    }

    private function linkedProgramCardHtml(string $html, string $type): string
    {
        $xpath = $this->htmlXpath($html);
        $nodes = $xpath->query(
            '//a[contains(concat(" ", normalize-space(@class), " "), " program-card ") and contains(@href, "type='.$type.'")]'
        );
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length);
        $card = $xpath->document->saveHTML($nodes->item(0));
        $this->assertIsString($card);

        return $card;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function programStatValues(string $cardHtml): array
    {
        preg_match_all('/class="program-stat-value">([^<]*)</', $cardHtml, $matches);
        $this->assertCount(2, $matches[1], $cardHtml);
        $this->assertStringContainsString('Aktivni', $cardHtml);
        $this->assertStringContainsString('Ukupno', $cardHtml);
        $this->assertStringNotContainsString('Prijave', $cardHtml);

        return [(int) trim($matches[1][0]), (int) trim($matches[1][1])];
    }

    private function htmlXpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($dom);
    }
}
