<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MmspListCountdownLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_mmsp_list_uses_ijekavian_countdown_wording_without_changing_other_programs(): void
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
                'deadline_days' => 20,
                'description' => 'Opis sa riječju vreme ostaje neizmijenjen.',
            ]
        );
        $women = $this->competition('zensko', 'Javni zenski poziv', 'published', [
            'start_date' => '2026-11-06',
            'deadline_days' => 20,
            'published_at' => '2026-11-01 08:00:00',
        ]);
        $youth = $this->competition('omladinsko', 'Javni omladinski poziv', 'published', [
            'start_date' => '2026-11-06',
            'deadline_days' => 20,
            'published_at' => '2026-11-02 08:00:00',
        ]);

        $mmsp = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk();
        $mmspHtml = $mmsp->getContent();
        $this->assertSame('Preostalo vrijeme za prijavu', $this->countdownLabel($mmspHtml));
        $this->assertTrue($this->countdownUsesMmspRules($mmspHtml));
        $mmsp->assertSee('js/mmsp-list-countdown.js', false);
        $mmsp->assertSee('Opis sa riječju vreme ostaje neizmijenjen.', false);
        $mmsp->assertSee('deadline-info', false);

        $womenHtml = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->getContent();
        $this->assertSame('Preostalo vreme za prijavu', $this->countdownLabel($womenHtml));
        $this->assertFalse($this->countdownUsesMmspRules($womenHtml));
        $this->assertStringNotContainsString('mmsp-list-countdown.js', $womenHtml);

        $youthHtml = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->getContent();
        $this->assertSame('Preostalo vreme za prijavu', $this->countdownLabel($youthHtml));
        $this->assertFalse($this->countdownUsesMmspRules($youthHtml));
        $this->assertStringNotContainsString('mmsp-list-countdown.js', $youthHtml);

        $this->assertSame('draft', $preview->fresh()->status);
        $this->assertNull($preview->fresh()->published_at);
        $this->assertSame('2026-11-06', $preview->fresh()->start_date->toDateString());
        $this->assertSame(20, $preview->fresh()->deadline_days);
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
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-26',
            'published_at' => $status === 'published' ? '2026-11-06 09:00:00' : null,
            'is_test_preview' => false,
        ], $overrides));
    }

    private function countdownLabel(string $html): string
    {
        $box = $this->countdownBox($html);
        $label = trim((string) preg_replace('/\s+/u', ' ', $box->textContent ?? ''));
        $this->assertSame(1, preg_match('/^(Preostalo vr(?:ij)?eme za prijavu):/u', $label, $matches), $label);

        return $matches[1];
    }

    private function countdownUsesMmspRules(string $html): bool
    {
        return $this->countdownBox($html)->hasAttribute('data-mmsp-countdown');
    }

    private function countdownBox(string $html): \DOMElement
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $nodes = (new \DOMXPath($dom))->query('//div[contains(concat(" ", normalize-space(@class), " "), " deadline-info ")]');
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length);
        $box = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $box);

        return $box;
    }
}
