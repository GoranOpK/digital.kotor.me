<?php

namespace Tests\Feature;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MOD-UX-01 — Moderator UX / navigation corrective regression.
 */
class CulturalModeratorUxNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $ordinary;

    private User $moderator;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->ordinary = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->orgA = $this->makeOrganizer('UX Org A');
        $this->orgB = $this->makeOrganizer('UX Org B');
        $this->grant($this->moderator, $this->orgA);
    }

    public function test_ordinary_user_does_not_see_moderator_nav_block(): void
    {
        $html = $this->actingAs($this->ordinary)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-kk-nav-moderator-block="1"', $html);
        $this->assertStringNotContainsString('data-kk-nav="kontrolna-tabla-moderator"', $html);
        $this->assertStringNotContainsString('data-kk-nav="moderiranje"', $html);
        $this->assertStringNotContainsString('>Radna tabla<', $html);
        $this->assertStringNotContainsString('>Mod rad<', $html);
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-moderator-dashboard.index')).'"',
            $html
        );
    }

    public function test_active_moderator_sees_kontrolna_tabla_and_moderiranje_links(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-kk-nav-moderator-block="1"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="kontrolna-tabla-moderator"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="moderiranje"'));
        $this->assertStringContainsString('>Kontrolna tabla<', $html);
        $this->assertStringContainsString('>Moderiranje<', $html);
        $this->assertStringContainsString('Organizator: UX Org A', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-dashboard.index')).'"',
            $html
        );
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-workspace.index')).'"',
            $html
        );
        // Content CTAs live on Moderiranje page, not as nav dropdown branches.
        $this->assertStringNotContainsString('data-kk-nav="mod-events"', $html);
        $this->assertStringNotContainsString('data-kk-nav="mod-manifestations"', $html);
        $this->assertStringNotContainsString('>Radna tabla<', $html);
        $this->assertStringNotContainsString('>Mod rad<', $html);
        $this->assertStringNotContainsString('>Workspace<', $html);

        // Public KK remains available.
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.index')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.events')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.archive')).'"', $html);
        $this->assertStringContainsString('href="'.e(route('cultural-calendar.manifestations')).'"', $html);
    }

    public function test_desktop_and_mobile_moderiranje_are_plain_links_with_identical_sizing(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $layoutStart = strpos($html, 'data-kk-nav-moderator-block="1"');
        $hamburgerStart = strpos($html, '<!-- Hamburger -->');
        $this->assertNotFalse($layoutStart);
        $this->assertNotFalse($hamburgerStart);
        $desktopNav = substr($html, $layoutStart, $hamburgerStart - $layoutStart);

        $this->assertMatchesRegularExpression(
            '/<a[^>]*data-kk-nav="kontrolna-tabla-moderator"[^>]*>Kontrolna tabla<\/a>/u',
            $desktopNav
        );
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="'.preg_quote(e(route('cultural-moderator-workspace.index')), '/').'"[^>]*data-kk-nav="moderiranje"[^>]*>Moderiranje<\/a>/u',
            $desktopNav
        );
        $this->assertStringNotContainsString('<details', $desktopNav);
        $this->assertStringNotContainsString('<summary', $desktopNav);
        $this->assertStringNotContainsString('data-kk-moderation-toggle', $desktopNav);
        $this->assertStringNotContainsString('x-data=', $desktopNav);
        $this->assertStringNotContainsString('x-show=', $desktopNav);
        $this->assertStringNotContainsString('@click', $desktopNav);

        preg_match(
            '/data-kk-nav="kontrolna-tabla-moderator"[^>]*style="([^"]*)"/u',
            $desktopNav,
            $kontrolnaStyle
        );
        preg_match(
            '/data-kk-nav="moderiranje"[^>]*style="([^"]*)"/u',
            $desktopNav,
            $moderiranjeStyle
        );
        $this->assertNotEmpty($kontrolnaStyle[1] ?? null);
        $this->assertNotEmpty($moderiranjeStyle[1] ?? null);
        $this->assertSame(
            $kontrolnaStyle[1],
            $moderiranjeStyle[1],
            'Kontrolna tabla and Moderiranje must share an identical style attribute on the public calendar (both inactive).'
        );
        $this->assertStringContainsString('display:inline-flex', $kontrolnaStyle[1]);
        $this->assertStringContainsString('align-items:center', $kontrolnaStyle[1]);
        $this->assertStringContainsString('justify-content:center', $kontrolnaStyle[1]);
        $this->assertStringContainsString('padding:8px 14px', $kontrolnaStyle[1]);
        $this->assertStringContainsString('min-height:38px', $kontrolnaStyle[1]);
        $this->assertStringContainsString('height:38px', $kontrolnaStyle[1]);
        $this->assertStringContainsString('width:128px', $kontrolnaStyle[1]);
        $this->assertStringContainsString('min-width:128px', $kontrolnaStyle[1]);
        $this->assertStringContainsString('line-height:1.25', $kontrolnaStyle[1]);
        $this->assertStringContainsString('box-sizing:border-box', $kontrolnaStyle[1]);
        $this->assertStringContainsString('font-size:14px;font-weight:600', $kontrolnaStyle[1]);
        $this->assertStringContainsString('white-space:nowrap', $kontrolnaStyle[1]);
        $this->assertSame(
            1,
            preg_match('/(?:^|;)width:128px(?:;|$)/u', $kontrolnaStyle[1]),
            'Shared explicit width constraint required for equal Moderiranje/Kontrolna tabla width.'
        );
        $this->assertSame(
            1,
            preg_match('/(?:^|;)width:128px(?:;|$)/u', $moderiranjeStyle[1]),
            'Moderiranje must use the same explicit width as Kontrolna tabla.'
        );
        $this->assertSame(
            1,
            preg_match('/(?:^|;)min-width:128px(?:;|$)/u', $kontrolnaStyle[1])
        );
        $this->assertSame(
            1,
            preg_match('/(?:^|;)min-width:128px(?:;|$)/u', $moderiranjeStyle[1])
        );

        $menuStart = strpos($html, '<!-- Responsive Navigation Menu -->');
        $this->assertNotFalse($menuStart);
        $mobileNav = substr($html, $menuStart);
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="'.preg_quote(e(route('cultural-moderator-workspace.index')), '/').'"[^>]*data-kk-nav="moderiranje"[^>]*>Moderiranje<\/a>/u',
            $mobileNav
        );
        $this->assertStringNotContainsString('data-kk-nav="mod-events"', $mobileNav);
        $this->assertStringNotContainsString('data-kk-nav="mod-manifestations"', $mobileNav);
        $this->assertStringNotContainsString('<details', $mobileNav);
    }

    public function test_mobile_hamburger_uses_inline_vanilla_hooks_without_alpine(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-kk-mobile-nav-root', $html);
        $this->assertStringContainsString('data-kk-mobile-nav-toggle', $html);
        $this->assertStringContainsString('data-kk-mobile-nav-menu', $html);
        $this->assertStringContainsString('id="kk-mobile-nav-menu"', $html);
        $this->assertStringContainsString('data-kk-mobile-nav-icon="closed"', $html);
        $this->assertStringContainsString('data-kk-mobile-nav-icon="open"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-controls="kk-mobile-nav-menu"', $html);
        $this->assertStringContainsString('aria-label="Navigacija"', $html);

        $navStart = strpos($html, 'data-kk-mobile-nav-root');
        $navEnd = strpos($html, '</nav>', $navStart);
        $this->assertNotFalse($navStart);
        $this->assertNotFalse($navEnd);
        $navHtml = substr($html, $navStart, $navEnd - $navStart);

        $this->assertStringNotContainsString('x-data=', $navHtml);
        $this->assertStringNotContainsString('x-show=', $navHtml);
        $this->assertStringNotContainsString('@click', $navHtml);
        $this->assertStringNotContainsString(':class=', $navHtml);
        $this->assertStringNotContainsString('x-cloak', $navHtml);

        // Inline script ships with the layout (no Vite rebuild required).
        $this->assertStringContainsString("querySelector('[data-kk-mobile-nav-toggle]')", $html);

        // Desktop sizing regression guard.
        preg_match(
            '/data-kk-nav="kontrolna-tabla-moderator"[^>]*style="([^"]*)"/u',
            $html,
            $kontrolnaStyle
        );
        preg_match(
            '/data-kk-nav="moderiranje"[^>]*style="([^"]*)"/u',
            $html,
            $moderiranjeStyle
        );
        $this->assertSame($kontrolnaStyle[1] ?? null, $moderiranjeStyle[1] ?? null);
        $this->assertStringContainsString('width:128px', $kontrolnaStyle[1] ?? '');
        $this->assertStringContainsString('height:38px', $kontrolnaStyle[1] ?? '');
    }

    public function test_multi_org_moderator_sees_promijeni_organizatora_action(): void
    {
        $this->grant($this->moderator, $this->orgB);
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Promijeni organizatora<', $html);
        $this->assertStringContainsString('data-kk-nav="promijeni-organizatora"', $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-moderator-workspace.index')).'"',
            $html
        );
    }

    public function test_dashboard_and_moderiranje_page_use_new_user_facing_labels(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('Kontrolna tabla', false)
            ->assertSee('Organizator: UX Org A', false)
            ->assertSee('DM-01', false)
            ->assertDontSee('Radna tabla', false)
            ->assertDontSee('Workspace', false);

        // Page-level redundant Moderiranje|Događaji|Manifestacije block removed (global nav KEEP).
        $dashboardHtml = $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString(
            'hover:bg-gray-50">Moderiranje</a>',
            $dashboardHtml
        );
        $this->assertStringNotContainsString(
            'hover:bg-gray-50">Događaji</a>',
            $dashboardHtml
        );
        $this->assertStringNotContainsString(
            'hover:bg-gray-50">Manifestacije</a>',
            $dashboardHtml
        );

        $page = $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk();

        $page->assertSee('Moderiranje', false)
            ->assertSee('Organizator: UX Org A', false)
            ->assertSee('Događaji organizatora', false)
            ->assertSee('Manifestacije organizatora', false)
            ->assertSee(route('cultural-moderator-events.index'), false)
            ->assertSee(route('cultural-moderator-manifestations.index'), false)
            ->assertDontSee('Moderatorski workspace', false)
            ->assertDontSee('>Workspace<', false);

        $html = $page->getContent();
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*Moderiranje\s*<\/h1>/u', $html);
    }

    public function test_select_context_label_remains_izbor_organizatora_without_context(): void
    {
        $this->grant($this->moderator, $this->orgB);
        CulturalOrganizerContext::clear();

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-workspace.index'))
            ->assertOk()
            ->assertSee('Izbor organizatora', false)
            ->assertDontSee('Događaji organizatora', false);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.index'))
            ->assertOk()
            ->assertSee('Izbor organizatora', false);
    }

    public function test_context_switch_uses_session_and_lands_on_kontrolna_tabla(): void
    {
        $this->grant($this->moderator, $this->orgB);
        CulturalOrganizerContext::clear();

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgB->id])
            ->assertRedirect(route('cultural-moderator-dashboard.index'));

        $this->assertSame($this->orgB->id, CulturalOrganizerContext::get($this->moderator)?->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('Organizator: UX Org B', false);
    }

    public function test_cross_organizer_isolation_remains_forbidden(): void
    {
        $other = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->grant($other, $this->orgB);
        CulturalOrganizerContext::set($other, $this->orgB->id);
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgB->id])
            ->assertForbidden();
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_naziv' => $naziv,
            'proposed_moderator_is_submitter' => false,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
                'activation_status' => 'active',
            ])->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::query()->create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    private function grant(User $user, CulturalOrganizer $organizer): void
    {
        CulturalModeratorAuthorization::query()->create([
            'user_id' => $user->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
            'activated_at' => now(),
        ]);
    }
}
