<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MmspAdminProgramCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_mmsp_test_preview_shows_program_cards_and_the_real_deadline(): void
    {
        $admin = $this->user('konkurs_admin');
        $preview = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'TEST 5 – Podrška preduzetnicima i MMSP 2026',
            'draft',
            [
                'is_test_preview' => true,
                'published_at' => null,
                'start_date' => '2026-11-06',
                'end_date' => '2026-11-26',
                'deadline_days' => 20,
                'description' => 'Opis testnog MMSP konkursa.',
                'budget' => 25000,
            ]
        );
        $commission = Commission::create([
            'name' => 'Testna komisija MMSP 2026',
            'year' => 2026,
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'profile_type' => CompetitionProfileResolver::TYPE_CANONICAL,
        ]);
        $preview->update(['commission_id' => $commission->id]);

        $html = $this->actingAs($admin)
            ->get(route('admin.competitions.index', [
                'type' => CompetitionProfileResolver::TYPE_CANONICAL,
                'tab' => 'active',
            ]))
            ->assertOk()
            ->getContent();

        $card = $this->featureCardText($html, 'Osnovne informacije');
        $this->assertStringContainsString('TEST 5 – Podrška preduzetnicima i MMSP 2026', $card);
        $this->assertStringContainsString('Otvoren za prijave', $card);
        $this->assertStringContainsString('25.000,00 €', $card);
        $this->assertStringContainsString('Datum objavljivanja: 06.11.2026', $card);
        $this->assertStringContainsString('Datum početka: 06.11.2026', $card);
        $this->assertStringContainsString('Rok za prijave: 26.11.2026 23:59', $card);
        $this->assertStringContainsString('Preostalo za prijave:', $card);
        $this->assertStringContainsString('Testna komisija MMSP 2026 (2026)', $card);
        $this->assertStringContainsString('Opis testnog MMSP konkursa.', $this->featureCardText($html, 'Opis konkursa'));
        $this->assertStringNotContainsString('Trenutno nema objavljenog aktivnog konkursa', $html);

        $row = $this->rowText($html, $preview->title);
        $this->assertStringContainsString('Rok za prijave:', $row);
        $this->assertStringContainsString('Do: 26.11.2026 23:59', $row);
        $this->assertStringNotContainsString('Predviđeno: 20 dana', $row);
        $this->assertStringContainsString('Testni konkurs', $row);

        $preview->refresh();
        $this->assertSame('draft', $preview->status);
        $this->assertTrue((bool) $preview->is_test_preview);
        $this->assertNull($preview->published_at);
        $this->assertSame('2026-11-06', $preview->start_date->toDateString());
        $this->assertSame(20, $preview->deadline_days);
        $this->assertSame('26.11.2026 23:59', $preview->deadline->format('d.m.Y H:i'));
    }

    public function test_official_mmsp_keeps_its_publication_date_and_other_programs_stay_unchanged(): void
    {
        $admin = $this->user('konkurs_admin');
        $official = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'Zvanični MMSP konkurs',
            'published',
            [
                'is_test_preview' => false,
                'published_at' => '2026-11-10 09:15:00',
                'start_date' => '2026-11-08',
                'deadline_days' => 20,
            ]
        );
        $preview = $this->competition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'TEST 5 ostaje u tabeli',
            'draft',
            [
                'is_test_preview' => true,
                'published_at' => null,
                'start_date' => '2026-11-06',
                'deadline_days' => 20,
            ]
        );
        $youth = $this->competition('omladinsko', 'Objavljeni omladinski konkurs', 'published', [
            'published_at' => '2026-11-01 08:00:00',
            'start_date' => '2026-11-06',
            'deadline_days' => 20,
        ]);
        $youthDraft = $this->competition('omladinsko', 'Omladinski nacrt', 'draft', [
            'start_date' => '2026-11-06',
            'deadline_days' => 20,
        ]);
        $womenDraft = $this->competition('zensko', 'Ženski nacrt', 'draft', [
            'is_test_preview' => true,
            'start_date' => '2026-11-06',
            'deadline_days' => 20,
        ]);

        $mmsp = $this->actingAs($admin)
            ->get(route('admin.competitions.index', [
                'type' => CompetitionProfileResolver::TYPE_CANONICAL,
                'tab' => 'active',
            ]))
            ->assertOk()
            ->getContent();
        $officialCard = $this->featureCardText($mmsp, 'Osnovne informacije');
        $this->assertStringContainsString($official->title, $officialCard);
        $this->assertStringContainsString('Datum objave: 10.11.2026 09:15', $officialCard);
        $this->assertStringNotContainsString('Datum objavljivanja:', $officialCard);
        $this->assertStringContainsString('Objavljen', $this->rowText($mmsp, $official->title));
        $previewRow = $this->rowText($mmsp, $preview->title);
        $this->assertStringContainsString('Do: 26.11.2026 23:59', $previewRow);
        $this->assertStringContainsString('Testni konkurs', $previewRow);
        $this->assertStringNotContainsString('Predviđeno: 20 dana', $previewRow);

        $youthPage = $this->actingAs($admin)
            ->get(route('admin.competitions.index', ['type' => 'omladinsko', 'tab' => 'active']))
            ->assertOk()
            ->getContent();
        $youthCard = $this->featureCardText($youthPage, 'Osnovne informacije');
        $this->assertStringContainsString($youth->title, $youthCard);
        $this->assertStringContainsString('Datum objave: 01.11.2026 08:00', $youthCard);
        $this->assertStringNotContainsString('Datum objavljivanja:', $youthCard);
        $this->assertStringContainsString('Predviđeno: 20 dana', $this->rowText($youthPage, $youthDraft->title));
        $this->assertSame('Nacrt', $this->statusLabel($youthPage, $youthDraft->title));

        $womenPage = $this->actingAs($admin)
            ->get(route('admin.competitions.index', ['type' => 'zensko', 'tab' => 'active']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Trenutno nema objavljenog aktivnog konkursa', $womenPage);
        $this->assertStringContainsString('Predviđeno: 20 dana', $this->rowText($womenPage, $womenDraft->title));
        $this->assertSame('Nacrt', $this->statusLabel($womenPage, $womenDraft->title));

        $this->assertSame('published', $official->fresh()->status);
        $this->assertSame('2026-11-10 09:15:00', $official->fresh()->published_at->format('Y-m-d H:i:s'));
        $this->assertFalse((bool) $official->fresh()->is_test_preview);
        $this->assertSame('draft', $preview->fresh()->status);
        $this->assertSame('draft', $youthDraft->fresh()->status);
        $this->assertSame('draft', $womenDraft->fresh()->status);
        $this->assertTrue((bool) $womenDraft->fresh()->is_test_preview);
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

    private function featureCardText(string $html, string $heading): string
    {
        $xpath = $this->xpath($html);
        $nodes = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " program-feature-card ")][h2[normalize-space()='.$this->xpathLiteral($heading).']]'
        );
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, $heading);

        return trim((string) preg_replace('/\s+/u', ' ', $nodes->item(0)->textContent ?? ''));
    }

    private function rowText(string $html, string $title): string
    {
        $xpath = $this->xpath($html);
        $nodes = $xpath->query('//tr[td[1][normalize-space()='.$this->xpathLiteral($title).']]');
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, $title);

        return trim((string) preg_replace('/\s+/u', ' ', $nodes->item(0)->textContent ?? ''));
    }

    private function statusLabel(string $html, string $title): string
    {
        $xpath = $this->xpath($html);
        $nodes = $xpath->query(
            '//tr[td[1][normalize-space()='.$this->xpathLiteral($title).']]//span[contains(@class, "status-badge")]'
        );
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, $title);

        return trim($nodes->item(0)->textContent ?? '');
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
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
