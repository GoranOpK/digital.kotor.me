<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MmspAdminTestPreviewLabelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_list_labels_only_mmsp_test_drafts_as_test_competitions(): void
    {
        $admin = $this->user('konkurs_admin');
        $visitor = $this->user('korisnik');
        $preview = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'TEST 5 – Podrška preduzetnicima i MMSP 2026', 'draft', [
            'is_test_preview' => true,
        ]);
        $plain = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'MMSP obični nacrt', 'draft');
        $official = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'Zvanični MMSP konkurs', 'published');
        $women = $this->competition('zensko', 'Ženski nacrt', 'draft', ['is_test_preview' => true]);
        $youth = $this->competition('omladinsko', 'Omladinski nacrt', 'draft');

        $html = $this->actingAs($admin)->get(route('admin.competitions.index'))->assertOk()->getContent();
        $this->assertSame('Testni konkurs', $this->statusLabel($html, $preview->title));
        $this->assertSame('Nacrt', $this->statusLabel($html, $plain->title));
        $this->assertSame('Objavljen', $this->statusLabel($html, $official->title));
        $this->assertSame('Nacrt', $this->statusLabel($html, $women->title));
        $this->assertSame('Nacrt', $this->statusLabel($html, $youth->title));

        $preview->refresh();
        $this->assertSame('draft', $preview->status);
        $this->assertTrue((bool) $preview->is_test_preview);

        $publicList = $this->actingAs($visitor)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk();
        $publicList->assertSee($preview->title, false);
        $publicList->assertSee('Otvoren', false);
        $publicList->assertDontSee('Testni konkurs', false);

        $this->actingAs($visitor)
            ->get(route('competitions.show', $preview))
            ->assertOk()
            ->assertSee('Otvoren', false)
            ->assertSee('Ovo je testni MMSP konkurs. Prijava nije zvanični zahtjev za subvenciju i ne stvara pravo na dodjelu sredstava.', false)
            ->assertDontSee('>Testni konkurs<', false);
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
            'description' => 'Opis',
            'type' => $type,
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

    private function statusLabel(string $html, string $title): string
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query(
            '//tr[td[1][normalize-space()='.$this->xpathLiteral($title).']]//span[contains(@class, "status-badge")]'
        );
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, $title);

        return trim($nodes->item(0)->textContent ?? '');
    }

    private function xpathLiteral(string $value): string
    {
        if (! str_contains($value, "'")) {
            return "'".$value."'";
        }

        $parts = explode("'", $value);

        return 'concat(\''.implode("', \"'\", '", $parts).'\')';
    }
}
