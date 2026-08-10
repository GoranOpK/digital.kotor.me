<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UI regresija: KK navigacija — crvena dugmad, plava Odjava, eksplicitna 2 reda za kk_admin.
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

        $this->assertStringNotContainsString('>Kanonski događaji<', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-events.index')).'"', $html);

        $this->assertStringContainsString('background:#0d6efd', $html);
        $this->assertMatchesRegularExpression('/background:#0d6efd[^>]*>\s*Odjava\s*</', $html);
        $this->assertStringNotContainsString('border-bottom: 2px solid', $html);
    }

    public function test_kk_admin_desktop_has_two_explicit_nav_rows_with_stable_hrefs(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-kk-nav-layout="two-row"', $html);
        $this->assertStringContainsString('data-kk-nav-row="1"', $html);
        $this->assertStringContainsString('data-kk-nav-row="2"', $html);

        $layoutStart = strpos($html, 'data-kk-nav-layout="two-row"');
        $hamburgerStart = strpos($html, '<!-- Hamburger -->');
        $this->assertNotFalse($layoutStart);
        $this->assertNotFalse($hamburgerStart);
        $this->assertLessThan($hamburgerStart, $layoutStart);

        $desktopNav = substr($html, $layoutStart, $hamburgerStart - $layoutStart);
        $row1Start = strpos($desktopNav, 'data-kk-nav-row="1"');
        $row2Start = strpos($desktopNav, 'data-kk-nav-row="2"');
        $this->assertNotFalse($row1Start);
        $this->assertNotFalse($row2Start);
        $this->assertLessThan($row2Start, $row1Start);

        $row1Html = substr($desktopNav, $row1Start, $row2Start - $row1Start);
        $row2Html = substr($desktopNav, $row2Start);

        foreach (['Kalendar kulture', 'Pretraga i pregled', 'Arhiva događaja', 'Urednički rad', 'Događaji', 'Lokacije'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $row1Html);
            $this->assertStringNotContainsString('>'.$label.'<', $row2Html);
        }

        $this->assertStringNotContainsString('>Kanonski događaji<', $row1Html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-events.index')).'"', $row1Html);

        foreach (['Kategorije', 'Oznake', 'Mediji', 'Organizatori', 'Zahtjevi Org', 'Zahtjevi Mod'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $row2Html);
        }
        $this->assertMatchesRegularExpression('/background:#0d6efd[^>]*>\s*Odjava\s*</', $row2Html);

        $this->assertStringContainsString('kk-admin-nav-desktop', $html);
        $this->assertStringContainsString('flex-direction: column', $html);
        $this->assertStringContainsString('justify-content: center', $html);
        $this->assertStringNotContainsString('sm:flex-col', $html);

        $this->assertStringContainsString('href="'.e(route('cultural-calendar.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.events')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.archive')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-editorial-dashboard.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-event-entries.index')).'"', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-events.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-locations.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-categories.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-tags.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-media.index')).'"', $html);
        $this->assertStringContainsString('action="'.e(route('logout')).'"', $html);
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
        $this->assertStringNotContainsString('data-kk-nav-layout="two-row"', $html);
    }
}
