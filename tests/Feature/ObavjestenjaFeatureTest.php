<?php

namespace Tests\Feature;

use App\Events\OfficialContentReadyForPublicPublication;
use App\Listeners\PublishOfficialContentNotice;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use App\Services\Notices\NoticePublicationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

class ObavjestenjaFeatureTest extends TestCase
{
    use RefreshDatabase;

    private NoticePublicationService $publicationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicationService = app(NoticePublicationService::class);
    }

    public function test_guest_sees_obavjestenja_panel_on_home(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Obavještenja', false);
        $response->assertSee('id="obavjestenja-heading"', false);
    }

    public function test_authenticated_user_sees_same_active_notices_as_guest(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::where('name', 'korisnik')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'activation_status' => 'active',
        ]);

        $notice = Notice::factory()->create([
            'title' => 'Zajedničko obavještenje FT004',
            'short_description' => 'Isti sadržaj za guest i auth',
            'visible_in_active_panel' => true,
        ]);

        $guest = $this->get(route('home'));
        $auth = $this->actingAs($user)->get(route('home'));

        $guest->assertOk()->assertSee('Zajedničko obavještenje FT004', false);
        $auth->assertOk()->assertSee('Zajedničko obavještenje FT004', false);
        $guest->assertSee(route('notices.public-content', $notice, false), false);
        $auth->assertSee(route('notices.public-content', $notice, false), false);
    }

    public function test_panel_remains_rendered_when_no_active_notices_exist(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="obavjestenja-heading"', false);
        $response->assertSee('Trenutno nema aktivnih Obavještenja.', false);
    }

    public function test_only_notices_visible_in_active_panel_appear_on_landing(): void
    {
        Notice::factory()->create([
            'title' => 'Vidljivo obavještenje',
            'visible_in_active_panel' => true,
        ]);
        Notice::factory()->hiddenFromPanel()->create([
            'title' => 'Sakriveno obavještenje',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Vidljivo obavještenje', false);
        $response->assertDontSee('Sakriveno obavještenje', false);
    }

    public function test_notice_title_is_displayed(): void
    {
        Notice::factory()->create([
            'title' => 'Naslov odluke o dodjeli',
            'visible_in_active_panel' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Naslov odluke o dodjeli', false);
    }

    public function test_optional_short_description_is_displayed_when_present(): void
    {
        Notice::factory()->create([
            'title' => 'Sa opisom',
            'short_description' => 'Kratki javni opis',
            'visible_in_active_panel' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kratki javni opis', false);
    }

    public function test_short_description_is_omitted_when_null(): void
    {
        Notice::factory()->withoutDescription()->create([
            'title' => 'Bez opisa FT004',
            'visible_in_active_panel' => true,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('Bez opisa FT004', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/Bez opisa FT004[\s\S]{0,200}<p[^>]*>\s*<\/p>/u',
            $html
        );
    }

    public function test_notice_link_points_to_public_content_route(): void
    {
        $notice = Notice::factory()->create([
            'title' => 'Link ka sadržaju',
            'visible_in_active_panel' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('notices.public-content', $notice, false), false);
    }

    public function test_guest_can_access_supported_public_content_without_authentication(): void
    {
        $competition = Competition::create([
            'title' => 'Konkurs za javnu odluku',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'completed',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'closed_at' => now()->subDay(),
        ]);

        $notice = Notice::factory()->create([
            'title' => 'Odluka o raspodjeli',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $response->assertSee('ODLUKU', false);
        $this->assertGuest();
    }

    public function test_public_route_does_not_redirect_to_admin_login_or_admin_route(): void
    {
        $competition = Competition::create([
            'title' => 'Konkurs',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'completed',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'closed_at' => now()->subDay(),
        ]);

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $this->assertNull($response->headers->get('Location'));
        $response->assertDontSee('Nazad na rang listu', false);
        $response->assertDontSee('Zatvori konkurs', false);
    }

    public function test_publishing_creates_a_visible_notice(): void
    {
        $notice = $this->publicationService->publish([
            'title' => 'Nova objava',
            'short_description' => 'Opis',
            'source_type' => 'competition_decision',
            'source_id' => 42,
            'content_delivery' => 'competition_decision_html',
        ]);

        $this->assertTrue($notice->visible_in_active_panel);
        $this->assertTrue($notice->publicly_available);
        $this->assertNull($notice->superseded_notice_id);
        $this->assertNull($notice->source_object_id);
        $this->assertDatabaseHas('notices', [
            'id' => $notice->id,
            'title' => 'Nova objava',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);
    }

    public function test_publishing_with_supersedes_notice_id_hides_old_notice(): void
    {
        $old = Notice::factory()->create([
            'title' => 'Staro',
            'visible_in_active_panel' => true,
        ]);

        $new = $this->publicationService->publish([
            'title' => 'Novo',
            'source_type' => 'competition_decision',
            'source_id' => 7,
            'content_delivery' => 'competition_decision_html',
            'supersedes_notice_id' => $old->id,
        ]);

        $old->refresh();

        $this->assertFalse($old->visible_in_active_panel);
        $this->assertTrue($old->publicly_available);
        $this->assertTrue($new->visible_in_active_panel);
        $this->assertTrue($new->publicly_available);
        $this->assertSame($old->id, $new->superseded_notice_id);
    }

    public function test_supersession_does_not_delete_the_old_notice(): void
    {
        $old = Notice::factory()->create(['visible_in_active_panel' => true]);

        $this->publicationService->publish([
            'title' => 'Zamjena',
            'source_type' => 'competition_decision',
            'source_id' => 9,
            'content_delivery' => 'competition_decision_html',
            'supersedes_notice_id' => $old->id,
        ]);

        $this->assertDatabaseHas('notices', ['id' => $old->id]);
        $this->assertNotNull(Notice::find($old->id));
    }

    public function test_supersession_and_creation_are_transactional(): void
    {
        $old = Notice::factory()->create([
            'title' => 'Prije transakcije',
            'visible_in_active_panel' => true,
        ]);

        $listener = function (Notice $notice) {
            if ($notice->title === 'Force rollback') {
                throw new \RuntimeException('Forced failure during create');
            }
        };

        Notice::creating($listener);

        try {
            $this->publicationService->publish([
                'title' => 'Force rollback',
                'source_type' => 'competition_decision',
                'source_id' => 11,
                'content_delivery' => 'competition_decision_html',
                'supersedes_notice_id' => $old->id,
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Forced failure during create', $e->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $old->refresh();
        $this->assertTrue($old->visible_in_active_panel);
        $this->assertTrue($old->publicly_available);
        $this->assertDatabaseMissing('notices', ['title' => 'Force rollback']);
    }

    public function test_invalid_publication_payload_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->publicationService->publish([
            'title' => '',
            'source_type' => 'competition_decision',
            'source_id' => 1,
            'content_delivery' => 'competition_decision_html',
        ]);
    }

    public function test_unknown_content_delivery_key_fails_safely(): void
    {
        $notice = Notice::factory()->create([
            'content_delivery' => 'unknown_delivery_key',
            'source_type' => 'competition_decision',
            'source_id' => 1,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertNotFound();
        $response->assertSee('Sadržaj nije dostupan', false);
        $response->assertDontSee('Stack trace', false);
        $response->assertDontSee('unknown_delivery_key', false);
    }

    public function test_no_read_tracking_data_is_created(): void
    {
        $this->assertFalse(Schema::hasColumn('notices', 'is_read'));
        $this->assertFalse(Schema::hasColumn('notices', 'read_at'));
        $this->assertFalse(Schema::hasColumn('notices', 'acknowledged_at'));

        $before = DB::table('notifications')->count();

        $this->publicationService->publish([
            'title' => 'Bez read-tracking',
            'source_type' => 'competition_decision',
            'source_id' => 3,
            'content_delivery' => 'competition_decision_html',
        ]);

        $this->assertSame($before, DB::table('notifications')->count());
    }

    public function test_notice_publication_service_contains_no_competition_status_decision_logic(): void
    {
        $source = file_get_contents((new ReflectionClass(NoticePublicationService::class))->getFileName());

        $this->assertStringNotContainsString('Competition', $source);
        $this->assertStringNotContainsString('approved_amount', $source);
        $this->assertStringNotContainsString('is_open', $source);
        $this->assertStringNotContainsString('status ===', $source);
        $this->assertStringNotContainsString('->status', $source);
    }

    public function test_no_queue_job_or_scheduler_is_required_for_publication(): void
    {
        $this->assertFalse(
            is_subclass_of(PublishOfficialContentNotice::class, ShouldQueue::class)
        );

        $listener = app(PublishOfficialContentNotice::class);
        $listener->handle(new OfficialContentReadyForPublicPublication(
            'Sync publish',
            'Opis',
            'competition_decision',
            15,
            'competition_decision_html',
            null,
        ));

        $this->assertDatabaseHas('notices', [
            'title' => 'Sync publish',
            'visible_in_active_panel' => true,
        ]);

        event(new OfficialContentReadyForPublicPublication(
            'Via event dispatcher',
            null,
            'competition_decision',
            16,
            'competition_decision_html',
            null,
        ));

        $this->assertSame(1, Notice::query()->where('title', 'Via event dispatcher')->count());
        $this->assertDatabaseHas('notices', [
            'title' => 'Via event dispatcher',
            'visible_in_active_panel' => true,
        ]);
    }

    public function test_event_listener_is_registered_exactly_once(): void
    {
        $listeners = app('events')->getRawListeners()[OfficialContentReadyForPublicPublication::class] ?? [];

        $this->assertCount(
            1,
            $listeners,
            'PublishOfficialContentNotice must be registered once (discovery XOR explicit listen).'
        );
    }

    public function test_inactive_notices_do_not_appear_on_the_landing_page(): void
    {
        Notice::factory()->hiddenFromPanel()->create([
            'title' => 'Neaktivno na panelu',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Neaktivno na panelu', false);
    }

    public function test_event_defaults_remain_backward_compatible_without_new_arguments(): void
    {
        $event = new OfficialContentReadyForPublicPublication(
            'Legacy event',
            null,
            'competition_decision',
            21,
            'competition_decision_html',
            null,
        );

        $this->assertFalse($event->public_revoke);
        $this->assertNull($event->source_object_id);

        $payload = $event->toPublicationPayload();
        $this->assertFalse($payload['public_revoke']);
        $this->assertNull($payload['source_object_id']);
        $this->assertArrayHasKey('supersedes_notice_id', $payload);

        $listener = app(PublishOfficialContentNotice::class);
        $listener->handle($event);

        $this->assertDatabaseHas('notices', [
            'title' => 'Legacy event',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'source_object_id' => null,
            'superseded_notice_id' => null,
        ]);
    }

    public function test_source_object_id_is_persisted_from_publication_payload(): void
    {
        $competition = Competition::create([
            'title' => 'Konkurs za source object',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'completed',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'closed_at' => now()->subDay(),
        ]);
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/payload.bin',
        ]);

        $event = new OfficialContentReadyForPublicPublication(
            'Objava sa primjerkom',
            null,
            'competition_decision',
            $competition->id,
            'competition_decision_signed_copy',
            null,
            false,
            $copy->id,
        );

        $notice = $this->publicationService->publish($event->toPublicationPayload());

        $this->assertSame($copy->id, $notice->source_object_id);
        $this->assertSame($competition->id, $notice->source_id);
    }

    public function test_public_revoke_hides_predecessor_and_revokes_public_availability(): void
    {
        $old = Notice::factory()->create([
            'title' => 'Pogrešna objava',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $new = $this->publicationService->publish([
            'title' => 'Ispravna objava',
            'source_type' => 'competition_decision',
            'source_id' => 8,
            'content_delivery' => 'competition_decision_html',
            'supersedes_notice_id' => $old->id,
            'public_revoke' => true,
        ]);

        $old->refresh();

        $this->assertFalse($old->visible_in_active_panel);
        $this->assertFalse($old->publicly_available);
        $this->assertTrue($new->visible_in_active_panel);
        $this->assertTrue($new->publicly_available);
        $this->assertSame($old->id, $new->superseded_notice_id);
    }

    public function test_public_revoke_without_predecessor_is_rejected(): void
    {
        $before = Notice::query()->count();

        try {
            $this->publicationService->publish([
                'title' => 'Revoke bez prethodnika',
                'source_type' => 'competition_decision',
                'source_id' => 12,
                'content_delivery' => 'competition_decision_html',
                'public_revoke' => true,
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('supersedes_notice_id', $exception->errors());
        }

        $this->assertSame($before, Notice::query()->count());
        $this->assertDatabaseMissing('notices', ['title' => 'Revoke bez prethodnika']);
    }

    public function test_public_revoke_rolls_back_when_create_fails(): void
    {
        $old = Notice::factory()->create([
            'title' => 'Prije revoke rollback',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $listener = function (Notice $notice) {
            if ($notice->title === 'Force revoke rollback') {
                throw new \RuntimeException('Forced failure during revoke create');
            }
        };

        Notice::creating($listener);

        try {
            $this->publicationService->publish([
                'title' => 'Force revoke rollback',
                'source_type' => 'competition_decision',
                'source_id' => 13,
                'content_delivery' => 'competition_decision_html',
                'supersedes_notice_id' => $old->id,
                'public_revoke' => true,
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Forced failure during revoke create', $e->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $old->refresh();
        $this->assertTrue($old->visible_in_active_panel);
        $this->assertTrue($old->publicly_available);
        $this->assertDatabaseMissing('notices', ['title' => 'Force revoke rollback']);
    }
}
