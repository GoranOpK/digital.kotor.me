<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UI regresija: KK navigacija — jednaka crvena dugmad; Odjava plava.
 */
class CulturalCalendarNavigationButtonsTest extends TestCase
{
    use RefreshDatabase;

    private User $kkAdmin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_kk_admin_public_and_admin_nav_items_use_red_buttons(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        foreach ([
            'Kalendar kulture',
            'Pretraga i pregled',
            'Arhiva događaja',
            'Urednički rad',
            'Događaji',
            'Kanonski događaji',
            'Lokacije',
            'Kategorije',
            'Oznake',
            'Mediji',
        ] as $label) {
            $this->assertMatchesRegularExpression(
                '/background:#(?:7a0f17|5f0c12)[^>]*>'.preg_quote($label, '/').'</',
                $html,
                "Expected red button style for: {$label}"
            );
        }

        $this->assertStringContainsString('background:#0d6efd', $html);
        $this->assertMatchesRegularExpression('/background:#0d6efd[^>]*>\s*Odjava\s*</', $html);
        $this->assertStringNotContainsString("border-bottom: 2px solid", $html);
    }

    public function test_regular_user_calendar_nav_uses_red_buttons_and_blue_logout(): void
    {
        $html = $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        foreach (['Kalendar kulture', 'Pretraga i pregled', 'Arhiva događaja'] as $label) {
            $this->assertMatchesRegularExpression(
                '/background:#(?:7a0f17|5f0c12)[^>]*>'.preg_quote($label, '/').'</',
                $html,
                "Expected red button style for: {$label}"
            );
        }

        $this->assertMatchesRegularExpression('/background:#0d6efd[^>]*>\s*Odjava\s*</', $html);
        $this->assertStringNotContainsString('>Urednički rad<', $html);
    }
}
