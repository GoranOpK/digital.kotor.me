<?php

namespace Tests\Feature;

use App\Models\CulturalActivityRecord;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityActor;
use App\Services\CulturalActivity\CulturalActivityRecordInput;
use App\Services\CulturalActivity\CulturalActivitySourceModule;
use App\Services\CulturalActivity\CulturalActivityStore;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CulturalActivityAdminUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $actor;

    private CulturalActivityStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->userWithRole('admin', 'Admin F804', 'f804-admin@example.com');
        $this->actor = $this->userWithRole('korisnik', 'Actor Name', 'secret-actor-email-f804@example.com');
        $this->store = app(CulturalActivityStore::class);
    }

    public function test_admin_can_open_the_list(): void
    {
        $this->insertUserActivity('event.create', 'TS12-EV-01:4', Carbon::parse('2026-08-14 23:47:33'));

        $response = $this->actingAs($this->admin)->get(route('admin.cultural-activity.index'));

        $response->assertOk();
        $response->assertSee('Evidencija aktivnosti', false);
        $response->assertSee('Kreiranje događaja', false);
        $response->assertSee('event.create', false);
        $response->assertSee('TS12-EV-01:4', false);
        $response->assertSee('TS-003', false);
    }

    public function test_superadmin_can_open_the_list(): void
    {
        $superadmin = $this->userWithRole('superadmin', 'Super', 'f804-super@example.com');

        $this->actingAs($superadmin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk();
    }

    public function test_unauthorized_roles_cannot_open_the_list(): void
    {
        foreach (['kk_admin', 'korisnik', 'konkurs_admin', 'komisija'] as $role) {
            $user = $this->userWithRole($role, 'Denied '.$role, 'f804-'.$role.'@example.com');
            $response = $this->actingAs($user)->get(route('admin.cultural-activity.index'));
            $this->assertNotSame(200, $response->status(), $role.' must not open Evidencija aktivnosti');
        }

        $this->actingAs($this->userWithRole('korisnik', 'Plain', 'f804-plain@example.com'))
            ->get(route('admin.cultural-activity.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.cultural-activity.index'))
            ->assertRedirect(route('login'));
    }

    public function test_list_is_read_only_without_mutation_routes_or_actions(): void
    {
        $this->insertUserActivity('event.create', 'TS12-EV-01:4');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Akcije', $html);
        $this->assertStringNotContainsString('Izmijeni', $html);
        $this->assertStringNotContainsString('Obriši', $html);
        $this->assertStringNotContainsString('Edit', $html);
        $this->assertStringNotContainsString('Delete', $html);

        $activityRoutes = collect(app('router')->getRoutes())->filter(
            fn ($route): bool => str_contains((string) $route->getName(), 'cultural-activity')
        );

        $this->assertCount(1, $activityRoutes);
        $route = $activityRoutes->first();
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('admin.cultural-activity.index', $route->getName());

        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->actingAs($this->admin)
                ->{$method}(route('admin.cultural-activity.index'))
                ->assertMethodNotAllowed();
        }
    }

    public function test_records_are_ordered_by_occurred_at_desc_then_id_desc(): void
    {
        $at = Carbon::parse('2026-08-15 00:09:00');
        $older = $this->insertUserActivity('event.create', 'order-old', $at->copy()->subHour());
        $sameA = $this->insertUserActivity('mf.event.add', 'order-same-a', $at);
        $sameB = $this->insertUserActivity('mf.event.remove', 'order-same-b', $at);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->getContent();

        $posNewer = min(strpos($html, 'order-same-a'), strpos($html, 'order-same-b'));
        $posOlder = strpos($html, 'order-old');
        $this->assertNotFalse($posNewer);
        $this->assertNotFalse($posOlder);
        $this->assertLessThan($posOlder, $posNewer);

        $laterId = $sameB->id > $sameA->id ? 'order-same-b' : 'order-same-a';
        $earlierId = $sameB->id > $sameA->id ? 'order-same-a' : 'order-same-b';
        $this->assertLessThan(strpos($html, $earlierId), strpos($html, $laterId));
        $this->assertGreaterThan($older->id, $sameA->id);
    }

    public function test_pagination_is_stable_and_does_not_duplicate_rows(): void
    {
        $base = Carbon::parse('2026-08-15 01:00:00');
        for ($i = 1; $i <= 21; $i++) {
            $this->insertUserActivity('event.create', 'evt-page-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), $base->copy()->addSeconds($i));
        }

        $page1 = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk();
        $page2 = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index', ['page' => 2]))
            ->assertOk();

        $page1->assertSee('evt-page-21', false);
        $page1->assertDontSee('evt-page-01', false);
        $page2->assertSee('evt-page-01', false);
        $page2->assertDontSee('evt-page-21', false);
    }

    public function test_system_actor_and_user_actor_rendering_is_privacy_safe(): void
    {
        $this->insertSystemActivity();
        $this->insertUserActivity('event.create', 'user-row');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sistem', $html);
        $this->assertStringContainsString('Actor Name (#'.$this->actor->id.')', $html);
        $this->assertStringNotContainsString('secret-actor-email-f804@example.com', $html);
    }

    public function test_missing_actor_and_null_target_do_not_error(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('cultural_activity_records')->insert([
            'source_module' => CulturalActivitySourceModule::TS_011,
            'event_id' => 'orphan-actor',
            'event_type' => 'nl.send.regular',
            'occurred_at' => now(),
            'actor_type' => CulturalActivityRecord::ACTOR_USER,
            'actor_user_id' => 9_999_999,
            'target_type' => 'newsletter_cycle',
            'target_id' => null,
            'organizer_context_id' => null,
            'context' => json_encode(['cycle_id' => 'abc']),
            'created_at' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->assertSee('Korisnik #9999999 — nedostupan nalog', false)
            ->assertSee('newsletter_cycle', false);
    }

    public function test_organizer_context_and_scalar_context_rendering(): void
    {
        $this->store->write(new CulturalActivityRecordInput(
            sourceModule: CulturalActivitySourceModule::TS_005,
            eventId: 'ctx-row',
            eventType: 'mf.event.add',
            occurredAt: now(),
            actor: CulturalActivityActor::user($this->actor),
            targetType: 'manifestation',
            targetId: 5,
            organizerContextId: 7,
            context: ['entry_id' => 4, 'manifestation_id' => 5],
        ));

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('#7', $html);
        $this->assertStringContainsString('entry_id=4', $html);
        $this->assertStringContainsString('manifestation_id=5', $html);
        $this->assertStringContainsString('Dodavanje događaja Manifestaciji', $html);
    }

    public function test_null_organizer_context_is_neutral_dash(): void
    {
        $this->insertUserActivity('event.create', 'no-org');

        $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->assertSee('—', false);
    }

    public function test_sensitive_and_nested_context_are_not_dumped(): void
    {
        DB::table('cultural_activity_records')->insert([
            'source_module' => CulturalActivitySourceModule::TS_001,
            'event_id' => 'privacy-row',
            'event_type' => 'org.request.submit',
            'occurred_at' => now(),
            'actor_type' => CulturalActivityRecord::ACTOR_USER,
            'actor_user_id' => $this->actor->id,
            'target_type' => 'organizer_request',
            'target_id' => 1,
            'organizer_context_id' => null,
            'context' => json_encode([
                'request_id' => 12,
                'access_token' => 'super-secret-token-value',
                'email' => 'hidden@example.com',
                'nested' => ['dump' => 'nope'],
            ]),
            'created_at' => now(),
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('request_id=12', $html);
        $this->assertStringNotContainsString('super-secret-token-value', $html);
        $this->assertStringNotContainsString('hidden@example.com', $html);
        $this->assertStringNotContainsString('nope', $html);
    }

    public function test_unknown_event_type_falls_back_to_technical_value(): void
    {
        $this->store->write(new CulturalActivityRecordInput(
            sourceModule: CulturalActivitySourceModule::TS_003,
            eventId: 'unknown-row',
            eventType: 'unknown.custom.type',
            occurredAt: now(),
            actor: CulturalActivityActor::user($this->actor),
            targetType: 'event',
            targetId: 1,
        ));

        $this->actingAs($this->admin)
            ->get(route('admin.cultural-activity.index'))
            ->assertOk()
            ->assertSee('unknown.custom.type', false);
    }

    public function test_dashboard_links_activity_list_for_platform_admin_only(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.cultural-activity.index'), false)
            ->assertSee('Evidencija aktivnosti', false);
    }

    private function userWithRole(string $role, string $name, string $email): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    private function insertUserActivity(string $eventType, string $eventId, ?Carbon $occurredAt = null): CulturalActivityRecord
    {
        $result = $this->store->write(new CulturalActivityRecordInput(
            sourceModule: CulturalActivitySourceModule::TS_003,
            eventId: $eventId,
            eventType: $eventType,
            occurredAt: $occurredAt ?? now(),
            actor: CulturalActivityActor::user($this->actor),
            targetType: 'event',
            targetId: 4,
        ));

        return $result->record;
    }

    private function insertSystemActivity(): CulturalActivityRecord
    {
        $result = $this->store->write(new CulturalActivityRecordInput(
            sourceModule: CulturalActivitySourceModule::TS_004,
            eventId: 'sys-row',
            eventType: 'occ.auto_finish',
            occurredAt: now(),
            actor: CulturalActivityActor::system(),
            targetType: 'occurrence',
            targetId: 1,
        ));

        return $result->record;
    }
}
