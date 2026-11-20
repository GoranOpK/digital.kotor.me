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

class MmspTestPreviewPublicationDateTest extends TestCase
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

    public function test_canonical_test_preview_shows_the_start_date_when_it_was_not_published(): void
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

        $response = $this->actingAs($user)
            ->get(route('competitions.show', $preview))
            ->assertOk()
            ->assertSee('Osnovne informacije', false)
            ->assertSee('Datum objavljivanja', false)
            ->assertSee('Datum početka', false)
            ->assertSee('Rok za prijave', false)
            ->assertSee('Prijava nije zvanični zahtjev za subvenciju', false);

        $html = $response->getContent();
        $this->assertSame(1, $this->labelCount($html, 'Datum objavljivanja'));
        $this->assertSame('06.11.2026', $this->labelValue($html, 'Datum objavljivanja'));
        $this->assertSame('06.11.2026', $this->labelValue($html, 'Datum početka'));
        $this->assertSame('draft', $preview->fresh()->status);
        $this->assertTrue((bool) $preview->fresh()->is_test_preview);
        $this->assertNull($preview->fresh()->published_at);
        $this->assertSame('2026-11-06', $preview->fresh()->start_date->toDateString());

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
        $datedPage = $this->actingAs($user)->get(route('competitions.show', $dated))->assertOk();
        $this->assertSame(
            $dated->published_at->format('d.m.Y H:i'),
            $this->labelValue($datedPage->getContent(), 'Datum objavljivanja')
        );
        $this->assertSame('2026-11-07 10:00:00', $dated->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-06', $dated->fresh()->start_date->toDateString());
    }

    public function test_published_competitions_keep_the_actual_publication_date(): void
    {
        $user = $this->user('korisnik');
        CompetitionProgramCatalog::overrideStatusForTests(
            CompetitionProfileResolver::TYPE_CANONICAL,
            CompetitionProgramCatalog::STATUS_ACTIVE
        );

        $official = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP poziv',
            'published',
            ['is_test_preview' => false, 'published_at' => '2026-11-06 09:15:00']
        );
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published', [
            'is_test_preview' => true,
            'published_at' => '2026-03-02 14:30:00',
        ]);
        $youth = $this->competition('omladinsko', 'Javni omladinski poziv', 'published', [
            'published_at' => '2026-05-12 08:00:00',
        ]);

        $officialPage = $this->actingAs($user)
            ->get(route('competitions.show', $official))
            ->assertOk();
        $this->assertSame(1, $this->labelCount($officialPage->getContent(), 'Datum objavljivanja'));
        $this->assertSame(
            '06.11.2026 09:15',
            $this->labelValue($officialPage->getContent(), 'Datum objavljivanja')
        );

        $womenPage = $this->actingAs($user)->get(route('competitions.show', $women))->assertOk();
        $this->assertSame(1, $this->labelCount($womenPage->getContent(), 'Datum objavljivanja'));
        $this->assertSame(
            '02.03.2026 14:30',
            $this->labelValue($womenPage->getContent(), 'Datum objavljivanja')
        );
        $this->assertNotSame(
            $women->start_date->format('d.m.Y'),
            $this->labelValue($womenPage->getContent(), 'Datum objavljivanja')
        );

        $youthPage = $this->actingAs($user)->get(route('competitions.show', $youth))->assertOk();
        $this->assertSame(1, $this->labelCount($youthPage->getContent(), 'Datum objavljivanja'));
        $this->assertSame(
            '12.05.2026 08:00',
            $this->labelValue($youthPage->getContent(), 'Datum objavljivanja')
        );

        $this->assertSame('published', $official->fresh()->status);
        $this->assertFalse((bool) $official->fresh()->is_test_preview);
        $this->assertSame('2026-11-06 09:15:00', $official->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('published', $women->fresh()->status);
        $this->assertTrue((bool) $women->fresh()->is_test_preview);
        $this->assertSame('2026-03-02 14:30:00', $women->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('published', $youth->fresh()->status);
        $this->assertSame('2026-05-12 08:00:00', $youth->fresh()->published_at->format('Y-m-d H:i:s'));
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

    private function labelCount(string $html, string $label): int
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query(
            '//span[contains(concat(" ", normalize-space(@class), " "), " info-label ")]'
        );
        $this->assertNotFalse($nodes);

        $count = 0;
        foreach ($nodes as $node) {
            if (trim($node->textContent) === $label) {
                $count++;
            }
        }

        return $count;
    }

    private function labelValue(string $html, string $label): string
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query(
            '//span[contains(concat(" ", normalize-space(@class), " "), " info-label ")]'
        );
        $this->assertNotFalse($nodes);

        foreach ($nodes as $node) {
            if (trim($node->textContent) !== $label) {
                continue;
            }

            $value = $xpath->query(
                'following-sibling::span[contains(concat(" ", normalize-space(@class), " "), " info-value ")]',
                $node
            );
            $this->assertNotFalse($value);
            $this->assertGreaterThan(0, $value->length);

            return trim($value->item(0)->textContent);
        }

        $this->fail('Label not found: '.$label);
    }
}
