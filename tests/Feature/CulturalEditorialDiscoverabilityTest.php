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

class CulturalEditorialDiscoverabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $kkAdmin;

    private User $ordinary;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->ordinary = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $request = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->ordinary->id,
            'proposed_moderator_user_id' => $this->moderator->id,
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
            'proposed_naziv' => 'Discoverability Org',
            'proposed_moderator_is_submitter' => false,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->kkAdmin->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::query()->create([
            'naziv' => 'Discoverability Org',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);

        CulturalModeratorAuthorization::query()->create([
            'user_id' => $this->moderator->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
            'activated_at' => now(),
        ]);
    }

    public function test_ordinary_user_on_public_events_does_not_see_editorial_bridge(): void
    {
        $this->actingAs($this->ordinary)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertDontSee('Upravljanje događajima')
            ->assertDontSee('data-kk-bridge="events-editorial"', false);
    }

    public function test_moderator_on_public_events_does_not_see_kk_admin_editorial_bridge(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertDontSee('Upravljanje događajima')
            ->assertDontSee('data-kk-bridge="events-editorial"', false);
    }

    public function test_kk_admin_on_public_events_sees_editorial_bridge_to_events_index(): void
    {
        $response = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertSee('Upravljanje događajima')
            ->assertSee('data-kk-bridge="events-editorial"', false)
            ->assertSee('href="'.route('cultural-event-entries.index').'"', false);

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->assertSee('+ Novi događaj');

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-event-entries.create'))
            ->assertOk()
            ->assertSee('Novi događaj', false);

        $this->assertStringContainsString('background:#b91c1c', $response->getContent());
    }

    public function test_ordinary_user_cannot_access_editorial_events_index(): void
    {
        $this->actingAs($this->ordinary)
            ->get(route('cultural-event-entries.index'))
            ->assertForbidden();
    }

    public function test_ordinary_user_on_public_manifestations_does_not_see_editorial_bridge(): void
    {
        $this->actingAs($this->ordinary)
            ->get(route('cultural-calendar.manifestations'))
            ->assertOk()
            ->assertDontSee('Upravljanje manifestacijama')
            ->assertDontSee('data-kk-bridge="mf-editorial"', false);
    }

    public function test_moderator_on_public_manifestations_does_not_see_kk_admin_editorial_bridge(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.manifestations'))
            ->assertOk()
            ->assertDontSee('Upravljanje manifestacijama')
            ->assertDontSee('data-kk-bridge="mf-editorial"', false);
    }

    public function test_kk_admin_on_public_manifestations_sees_editorial_bridge_and_create_flow(): void
    {
        $response = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.manifestations'))
            ->assertOk()
            ->assertSee('Upravljanje manifestacijama')
            ->assertSee('data-kk-bridge="mf-editorial"', false)
            ->assertSee('href="'.route('cultural-manifestations.index').'"', false);

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-manifestations.index'))
            ->assertOk()
            ->assertSee('+ Nova manifestacija');

        $this->actingAs($this->kkAdmin)
            ->get(route('cultural-manifestations.create'))
            ->assertOk()
            ->assertSee('Nova Manifestacija');

        $this->assertStringContainsString('background:#b91c1c', $response->getContent());
    }

    public function test_ordinary_user_cannot_access_editorial_manifestations_index(): void
    {
        $this->actingAs($this->ordinary)
            ->get(route('cultural-manifestations.index'))
            ->assertForbidden();
    }

    public function test_kk_admin_public_nav_uses_public_labels_only(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-kk-nav="events-public"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="mf-public"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="bridge-editorial"'));
        $this->assertSame(0, substr_count($html, 'data-kk-nav="bridge-public"'));
        $this->assertSame(0, substr_count($html, 'data-kk-nav="events-editorial"'));
        $this->assertSame(0, substr_count($html, 'data-kk-nav="mf-editorial"'));
        $this->assertMatchesRegularExpression(
            '/data-kk-nav="events-public"[^>]*>\s*Događaji\s*</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-kk-nav="mf-public"[^>]*>\s*Manifestacije\s*</u',
            $html
        );
        $this->assertStringNotContainsString('>Upravljanje događajima<', $html);
        $this->assertStringNotContainsString('>Upravljanje manifestacijama<', $html);
        $this->assertStringContainsString('>Urednički portal<', $html);
    }

    public function test_kk_admin_editorial_nav_uses_editorial_labels_only(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(0, substr_count($html, 'data-kk-nav="events-public"'));
        $this->assertSame(0, substr_count($html, 'data-kk-nav="mf-public"'));
        $this->assertSame(0, substr_count($html, 'data-kk-nav="bridge-editorial"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="bridge-public"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="events-editorial"'));
        $this->assertSame(2, substr_count($html, 'data-kk-nav="mf-editorial"'));
        $this->assertMatchesRegularExpression(
            '/data-kk-nav="events-editorial"[^>]*>\s*Upravljanje događajima\s*</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-kk-nav="mf-editorial"[^>]*>\s*Upravljanje manifestacijama\s*</u',
            $html
        );
        $this->assertStringNotContainsString('data-kk-nav="events-public"', $html);
        $this->assertStringNotContainsString('data-kk-nav="mf-public"', $html);
        $this->assertStringContainsString('>Kalendar kulture<', $html);
        $this->assertStringNotContainsString('>Javni portal<', $html);
    }

    public function test_kk_admin_mobile_public_nav_is_public_only_with_editorial_bridge(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $mobileStart = strpos($html, '<!-- Responsive Navigation Menu -->');
        $mobileEnd = strpos($html, '<!-- Responsive Settings Options -->');
        $this->assertNotFalse($mobileStart);
        $this->assertNotFalse($mobileEnd);
        $mobileNav = substr($html, $mobileStart, $mobileEnd - $mobileStart);

        foreach (['Kalendar kulture', 'Događaji', 'Arhiva događaja', 'Manifestacije', 'Urednički portal'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $mobileNav);
        }
        foreach ([
            'Upravljanje događajima',
            'Upravljanje manifestacijama',
            'Kontrolna tabla',
            'Lokacije',
            'Kategorije',
            'Oznake',
            'Mediji',
            'Organizatori',
            'Zahtjevi',
            'Javni portal',
        ] as $label) {
            $this->assertStringNotContainsString('>'.$label.'<', $mobileNav);
        }
    }

    public function test_kk_admin_mobile_editorial_nav_is_editorial_only_with_public_bridge(): void
    {
        $html = $this->actingAs($this->kkAdmin)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->getContent();

        $mobileStart = strpos($html, '<!-- Responsive Navigation Menu -->');
        $mobileEnd = strpos($html, '<!-- Responsive Settings Options -->');
        $this->assertNotFalse($mobileStart);
        $this->assertNotFalse($mobileEnd);
        $mobileNav = substr($html, $mobileStart, $mobileEnd - $mobileStart);

        foreach ([
            'Kalendar kulture',
            'Kontrolna tabla',
            'Upravljanje događajima',
            'Upravljanje manifestacijama',
            'Lokacije',
            'Kategorije',
            'Oznake',
            'Mediji',
            'Organizatori',
            'Zahtjevi',
        ] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $mobileNav);
        }
        foreach (['Događaji', 'Arhiva događaja', 'Manifestacije', 'Urednički portal', 'Urednički rad', 'Zahtjevi Org', 'Zahtjevi Mod', 'Javni portal'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'<', $mobileNav);
        }

        $kalendarPos = strpos($mobileNav, '>Kalendar kulture<');
        $kontrolnaPos = strpos($mobileNav, '>Kontrolna tabla<');
        $dogadjajiPos = strpos($mobileNav, '>Upravljanje događajima<');
        $this->assertNotFalse($kalendarPos);
        $this->assertNotFalse($kontrolnaPos);
        $this->assertNotFalse($dogadjajiPos);
        $this->assertLessThan($kontrolnaPos, $kalendarPos);
        $this->assertLessThan($dogadjajiPos, $kontrolnaPos);
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(e(route('cultural-calendar.index')), '/').'"[^>]*>Kalendar kulture</u',
            $mobileNav
        );
    }

    public function test_ordinary_user_nav_keeps_public_labels_without_editorial_management(): void
    {
        $html = $this->actingAs($this->ordinary)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Događaji<', $html);
        $this->assertStringContainsString('>Manifestacije<', $html);
        $this->assertStringNotContainsString('>Upravljanje događajima<', $html);
        $this->assertStringNotContainsString('>Upravljanje manifestacijama<', $html);
        $this->assertStringNotContainsString('data-kk-nav="events-editorial"', $html);
        $this->assertStringNotContainsString('data-kk-nav="mf-editorial"', $html);
    }

    public function test_moderator_nav_keeps_public_labels_without_editorial_management(): void
    {
        $html = $this->actingAs($this->moderator)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Događaji<', $html);
        $this->assertStringContainsString('>Manifestacije<', $html);
        $this->assertStringNotContainsString('>Upravljanje događajima<', $html);
        $this->assertStringNotContainsString('>Upravljanje manifestacijama<', $html);
    }
}
