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

class MmspCompetitionListPublicationDateTest extends TestCase
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

    public function test_user_list_shows_the_test_start_date_when_the_call_was_not_published(): void
    {
        $user = $this->user('korisnik');
        $preview = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'TEST 5 – Podrška preduzetnicima i MMSP 2026',
            'draft',
            [
                'is_test_preview' => true,
                'published_at' => null,
                'start_date' => '2026-11-06',
            ]
        );
        $dated = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Testni MMSP sa sačuvanim datumom objave',
            'draft',
            [
                'is_test_preview' => true,
                'published_at' => '2026-11-07 10:00:00',
                'start_date' => '2026-11-06',
            ]
        );

        $page = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk();

        $previewCard = $this->cardText($page->getContent(), $preview->title);
        $this->assertStringContainsString('Objavljen: 06.11.2026', $previewCard);
        $this->assertStringNotContainsString('Objavljen: N/A', $previewCard);
        $this->assertStringContainsString('Otvoren', $previewCard);

        $datedCard = $this->cardText($page->getContent(), $dated->title);
        $this->assertStringContainsString('Objavljen: 07.11.2026', $datedCard);
        $this->assertStringNotContainsString('Objavljen: 06.11.2026', $datedCard);

        $this->assertSame('draft', $preview->fresh()->status);
        $this->assertTrue((bool) $preview->fresh()->is_test_preview);
        $this->assertNull($preview->fresh()->published_at);
        $this->assertSame('2026-11-06', $preview->fresh()->start_date->toDateString());
        $this->assertSame('2026-11-07 10:00:00', $dated->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-06', $dated->fresh()->start_date->toDateString());
    }

    public function test_user_list_keeps_the_stored_publication_date_for_official_calls(): void
    {
        $user = $this->user('korisnik');
        $official = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP poziv',
            'published',
            [
                'is_test_preview' => false,
                'published_at' => '2026-11-01 09:15:00',
                'start_date' => '2026-11-06',
            ]
        );
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published', [
            'is_test_preview' => true,
            'published_at' => '2026-11-02 14:30:00',
            'start_date' => '2026-11-06',
        ]);
        $youth = $this->competition('omladinsko', 'Javni omladinski poziv', 'published', [
            'published_at' => '2026-11-03 08:00:00',
            'start_date' => '2026-11-06',
        ]);

        $officialCard = $this->cardText(
            $this->actingAs($user)
                ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
                ->assertOk()
                ->getContent(),
            $official->title
        );
        $this->assertStringContainsString('Objavljen: 01.11.2026', $officialCard);
        $this->assertStringNotContainsString('Objavljen: 06.11.2026', $officialCard);
        $this->assertStringContainsString('Otvoren', $officialCard);

        $womenCard = $this->cardText(
            $this->actingAs($user)
                ->get(route('competitions.index', ['type' => 'zensko']))
                ->assertOk()
                ->getContent(),
            $women->title
        );
        $this->assertStringContainsString('Objavljen: 02.11.2026', $womenCard);
        $this->assertStringNotContainsString('Objavljen: 06.11.2026', $womenCard);

        $youthCard = $this->cardText(
            $this->actingAs($user)
                ->get(route('competitions.index', ['type' => 'omladinsko']))
                ->assertOk()
                ->getContent(),
            $youth->title
        );
        $this->assertStringContainsString('Objavljen: 03.11.2026', $youthCard);
        $this->assertStringNotContainsString('Objavljen: 06.11.2026', $youthCard);

        $this->assertSame('published', $official->fresh()->status);
        $this->assertSame('2026-11-01 09:15:00', $official->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-06', $official->fresh()->start_date->toDateString());
        $this->assertTrue((bool) $women->fresh()->is_test_preview);
        $this->assertSame('2026-11-02 14:30:00', $women->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-03 08:00:00', $youth->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('published', $youth->fresh()->status);
    }

    private function user(string $role): User
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
            'description' => 'Opis konkursa',
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

    private function cardText(string $html, string $title): string
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " competition-card ")]');
        $this->assertNotFalse($nodes);

        foreach ($nodes as $node) {
            $text = trim((string) preg_replace('/\s+/u', ' ', $node->textContent));
            if (! str_contains($text, $title)) {
                continue;
            }

            return $text;
        }

        $this->fail('Competition card not found: '.$title);
    }
}
