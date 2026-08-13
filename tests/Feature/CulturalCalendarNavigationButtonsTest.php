<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
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
            'Događaji',
            'Arhiva događaja',
            'Manifestacije',
            'Urednički portal',
        ] as $label) {
            $this->assertMatchesRegularExpression(
                '/background:#(?:7a0f17|5f0c12)[^>]*>'.preg_quote($label, '/').'</',
                $html,
                "Expected red button style for: {$label}"
            );
        }

        $this->assertStringNotContainsString('>Kanonski događaji<', $html);
        $this->assertStringNotContainsString('/kalendar-kulture/dogadjaji', $html);
        $this->assertStringNotContainsString('>Urednički rad<', $html);
        $this->assertStringNotContainsString('>Lokacije<', $html);
        $this->assertStringNotContainsString('>Kategorije<', $html);

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

        foreach (['Kalendar kulture', 'Događaji', 'Arhiva događaja', 'Manifestacije', 'Urednički portal'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $row1Html);
            $this->assertStringNotContainsString('>'.$label.'<', $row2Html);
        }

        $this->assertStringNotContainsString('>Upravljanje događajima<', $row1Html);
        $this->assertStringNotContainsString('>Upravljanje manifestacijama<', $row1Html);
        $this->assertStringNotContainsString('>Urednički rad<', $row1Html);
        $this->assertStringNotContainsString('>Lokacije<', $row1Html);
        $this->assertStringContainsString('data-kk-nav="events-public"', $row1Html);
        $this->assertStringContainsString('data-kk-nav="mf-public"', $row1Html);
        $this->assertStringContainsString('data-kk-nav="bridge-editorial"', $row1Html);
        $this->assertStringNotContainsString('data-kk-nav="events-editorial"', $row1Html);
        $this->assertStringNotContainsString('data-kk-nav="mf-editorial"', $row1Html);

        foreach (['Kategorije', 'Oznake', 'Mediji', 'Organizatori', 'Zahtjevi Org', 'Zahtjevi Mod', 'Javni portal'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'<', $row2Html);
        }
        $this->assertMatchesRegularExpression('/background:#0d6efd[^>]*>\s*Odjava\s*</', $row2Html);

        $this->assertStringContainsString('kk-admin-nav-desktop', $html);
        $this->assertStringContainsString('flex-direction: column', $html);
        $this->assertStringContainsString('justify-content: center', $html);
        $this->assertStringNotContainsString('sm:flex-col', $html);

        $this->assertStringContainsString('href="'.e(route('cultural-calendar.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.events')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.archive')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.manifestations')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-editorial-dashboard.index')).'"', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-event-entries.index')).'"', $html);
        $this->assertStringNotContainsString('/kalendar-kulture/dogadjaji', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-locations.index')).'"', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-categories.index')).'"', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-tags.index')).'"', $html);
        $this->assertStringNotContainsString('href="'.e(route('cultural-media.index')).'"', $html);
        $this->assertStringContainsString('action="'.e(route('logout')).'"', $html);
    }

    public function test_kk_admin_editorial_nav_contains_only_editorial_items_and_public_bridge(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->getContent();

        $layoutStart = strpos($html, 'data-kk-nav-layout="two-row"');
        $hamburgerStart = strpos($html, '<!-- Hamburger -->');
        $this->assertNotFalse($layoutStart);
        $this->assertNotFalse($hamburgerStart);
        $desktopNav = substr($html, $layoutStart, $hamburgerStart - $layoutStart);

        $row1Start = strpos($desktopNav, 'data-kk-nav-row="1"');
        $row2Start = strpos($desktopNav, 'data-kk-nav-row="2"');
        $this->assertNotFalse($row1Start);
        $this->assertNotFalse($row2Start);
        $row1Html = substr($desktopNav, $row1Start, $row2Start - $row1Start);
        $row2Html = substr($desktopNav, $row2Start);

        $row1Labels = [
            'Kalendar kulture',
            'Kontrolna tabla',
            'Upravljanje događajima',
            'Upravljanje manifestacijama',
            'Lokacije',
            'Kategorije',
        ];
        foreach ($row1Labels as $label) {
            $this->assertStringContainsString('>'.$label.'<', $row1Html);
            $this->assertStringNotContainsString('>'.$label.'<', $row2Html);
        }

        $row2Labels = ['Oznake', 'Mediji', 'Organizatori', 'Zahtjevi'];
        foreach ($row2Labels as $label) {
            $this->assertStringContainsString('>'.$label.'<', $row2Html);
            $this->assertStringNotContainsString('>'.$label.'<', $row1Html);
        }
        $this->assertMatchesRegularExpression('/background:#0d6efd[^>]*>\s*Odjava\s*</', $row2Html);

        foreach (['Događaji', 'Arhiva događaja', 'Manifestacije', 'Urednički portal', 'Urednički rad', 'Zahtjevi Org', 'Zahtjevi Mod', 'Javni portal'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'<', $desktopNav);
        }

        $this->assertStringContainsString('href="'.e(route('cultural-calendar.index')).'"', $desktopNav);
        $this->assertStringContainsString('href="'.e(route('cultural-editorial-dashboard.index')).'"', $desktopNav);
        $this->assertStringContainsString('href="'.e(route('cultural-editorial-requests.index')).'"', $desktopNav);
        $this->assertStringContainsString('data-kk-nav="bridge-public"', $desktopNav);
        $this->assertStringContainsString('data-kk-nav="kontrolna-tabla"', $desktopNav);
        $this->assertStringContainsString('data-kk-nav="zahtjevi"', $desktopNav);

        // Order: Kalendar kulture first, Kontrolna tabla second; Zahtjevi after Organizatori.
        $kalendarPos = strpos($desktopNav, '>Kalendar kulture<');
        $kontrolnaPos = strpos($desktopNav, '>Kontrolna tabla<');
        $dogadjajiPos = strpos($desktopNav, '>Upravljanje događajima<');
        $orgPos = strpos($desktopNav, '>Organizatori<');
        $zahtjeviPos = strpos($desktopNav, '>Zahtjevi<');
        $this->assertNotFalse($kalendarPos);
        $this->assertNotFalse($kontrolnaPos);
        $this->assertNotFalse($dogadjajiPos);
        $this->assertLessThan($kontrolnaPos, $kalendarPos);
        $this->assertLessThan($dogadjajiPos, $kontrolnaPos);
        $this->assertNotFalse($orgPos);
        $this->assertNotFalse($zahtjeviPos);
        $this->assertLessThan($zahtjeviPos, $orgPos);

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(e(route('cultural-calendar.index')), '/').'"[^>]*>Kalendar kulture</u',
            $desktopNav
        );
    }

    public function test_kk_admin_sees_zahtjevi_only_in_editorial_context_and_never_ordinary_request_cta(): void
    {
        $publicHtml = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('>Zahtjevi Org<', $publicHtml);
        $this->assertStringNotContainsString('>Zahtjevi Mod<', $publicHtml);
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-editorial-requests.index')).'"',
            $publicHtml
        );
        $this->assertStringNotContainsString('>Zahtjev za Organizatora<', $publicHtml);
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-organizer-creation-requests.create')).'"',
            $publicHtml
        );

        $editorialHtml = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Zahtjevi<', $editorialHtml);
        $this->assertStringNotContainsString('>Zahtjevi Org<', $editorialHtml);
        $this->assertStringNotContainsString('>Zahtjevi Mod<', $editorialHtml);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-editorial-requests.index')).'"',
            $editorialHtml
        );
    }

    public function test_regular_user_calendar_nav_uses_red_buttons_and_blue_logout(): void
    {
        $html = $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        foreach (['Kalendar kulture', 'Događaji', 'Arhiva događaja', 'Zahtjev za Organizatora'] as $label) {
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

    public function test_ordinary_user_sees_organizer_request_cta_to_create_route(): void
    {
        $html = $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Zahtjev za Organizatora<', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-organizer-creation-requests.create')).'"',
            $html
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, 'href="'.e(route('cultural-organizer-creation-requests.create')).'"'),
            'Expected create CTA in both desktop and mobile nav branches'
        );

        $this->actingAs($this->regularUser)
            ->get(route('cultural-organizer-creation-requests.create'))
            ->assertOk()
            ->assertSee('Zahtjev za kreiranje Organizatora', false);
    }

    public function test_ordinary_user_with_rejected_organizer_request_still_sees_create_cta(): void
    {
        $moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->regularUser->id,
            'proposed_moderator_user_id' => $moderator->id,
            'proposed_moderator_name' => $moderator->name,
            'proposed_moderator_email' => $moderator->email,
            'proposed_naziv' => 'Smoke Org Rejected',
            'proposed_moderator_is_submitter' => false,
            'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
            'decision_note' => 'Odbijeno u produkcionom testu',
        ]);

        $html = $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Zahtjev za Organizatora<', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-organizer-creation-requests.create')).'"',
            $html
        );
        $this->assertSame(1, CulturalOrganizerCreationRequest::query()->count());
        $this->assertSame(
            CulturalOrganizerCreationRequest::STATUS_REJECTED,
            CulturalOrganizerCreationRequest::query()->first()->status
        );
    }

    public function test_active_moderator_still_sees_moderator_links_with_organizer_request_cta(): void
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->regularUser->id,
            'proposed_moderator_user_id' => $this->regularUser->id,
            'proposed_moderator_name' => $this->regularUser->name,
            'proposed_moderator_email' => $this->regularUser->email,
            'proposed_naziv' => 'Nav Mod Org',
            'proposed_moderator_is_submitter' => true,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => 'Nav Mod Org',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);

        CulturalModeratorAuthorization::query()->create([
            'user_id' => $this->regularUser->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
            'activated_at' => now(),
        ]);

        $html = $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Zahtjev za Organizatora<', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-organizer-creation-requests.create')).'"',
            $html
        );
        $this->assertStringContainsString('>Kontrolna tabla<', $html);
        $this->assertStringContainsString('data-kk-nav="moderiranje"', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-workspace.index')).'"',
            $html
        );
        $this->assertStringNotContainsString('data-kk-nav="mod-events"', $html);
        $this->assertStringNotContainsString('data-kk-nav="mod-manifestations"', $html);
        $this->assertStringContainsString('Organizator: Nav Mod Org', $html);
        $this->assertStringNotContainsString('>Radna tabla<', $html);
        $this->assertStringNotContainsString('>Mod rad<', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-dashboard.index')).'"',
            $html
        );
    }
}
