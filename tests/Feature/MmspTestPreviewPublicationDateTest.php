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

    public function test_canonical_test_preview_hides_the_publication_date_row(): void
    {
        $user = $this->user('korisnik');
        $preview = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'TEST 5 – Podrška preduzetnicima i MMSP 2026',
            'draft',
            ['is_test_preview' => true, 'published_at' => null]
        );

        $response = $this->actingAs($user)
            ->get(route('competitions.show', $preview))
            ->assertOk()
            ->assertSee('Osnovne informacije', false)
            ->assertSee('Datum početka', false)
            ->assertSee('Rok za prijave', false)
            ->assertSee('Prijava nije zvanični zahtjev za subvenciju', false)
            ->assertDontSee('Datum objavljivanja', false);

        $this->assertSame(0, $this->labelCount($response->getContent(), 'Datum objavljivanja'));
        $this->assertSame('draft', $preview->fresh()->status);
        $this->assertTrue((bool) $preview->fresh()->is_test_preview);
        $this->assertNull($preview->fresh()->published_at);
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
        $officialPage->assertSee($official->published_at->format('d.m.Y H:i'), false);

        $womenPage = $this->actingAs($user)->get(route('competitions.show', $women))->assertOk();
        $this->assertSame(1, $this->labelCount($womenPage->getContent(), 'Datum objavljivanja'));
        $womenPage->assertSee($women->published_at->format('d.m.Y H:i'), false);

        $youthPage = $this->actingAs($user)->get(route('competitions.show', $youth))->assertOk();
        $this->assertSame(1, $this->labelCount($youthPage->getContent(), 'Datum objavljivanja'));
        $youthPage->assertSee($youth->published_at->format('d.m.Y H:i'), false);

        $this->assertSame('published', $official->fresh()->status);
        $this->assertFalse((bool) $official->fresh()->is_test_preview);
        $this->assertSame('2026-11-06 09:15:00', $official->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('published', $women->fresh()->status);
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
}
