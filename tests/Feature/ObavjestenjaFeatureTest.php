<?php

namespace Tests\Feature;

use App\Events\OfficialContentPublicAvailabilityRevoked;
use App\Events\OfficialContentPublicMetadataUpdated;
use App\Events\OfficialContentReadyForPublicPublication;
use App\Listeners\PublishOfficialContentNotice;
use App\Listeners\RevokeOfficialContentPublicAvailability;
use App\Listeners\UpdateOfficialContentPublicMetadata;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use App\Services\Notices\NoticePublicationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        $response->assertDontSee(
            'Javni pregled zvaničnog sadržaja nastalog u drugim funkcionalnostima Digital Kotor.',
            false
        );
        $response->assertDontSee('Pogledaj zvanični sadržaj', false);
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
        $guest->assertDontSee(
            'Javni pregled zvaničnog sadržaja nastalog u drugim funkcionalnostima Digital Kotor.',
            false
        );
        $auth->assertDontSee(
            'Javni pregled zvaničnog sadržaja nastalog u drugim funkcionalnostima Digital Kotor.',
            false
        );
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

    public function test_signed_official_decision_panel_links_open_in_a_new_tab(): void
    {
        Storage::fake('local');
        $competition = $this->createCompletedCompetition('Konkurs za novi tab Odluke');
        $copy = $this->createSignedCopy($competition, 'SIGNED-COPY-NEW-TAB');

        $notice = Notice::factory()->withoutDescription()->create([
            'title' => 'Zvanična Odluka novi tab',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
        ]);

        $item = $this->homePanelItemHtml('Zvanična Odluka novi tab');
        $publicUrl = route('notices.public-content', $notice);

        $this->assertStringNotContainsString('Pogledaj zvanični sadržaj', $item);
        $this->assertSame(1, substr_count($item, 'href="'.$publicUrl.'"'));
        $this->assertSame(1, substr_count($item, 'target="_blank"'));
        $this->assertSame(1, substr_count($item, 'rel="noopener noreferrer"'));
        $this->assertMatchesRegularExpression(
            '/<h3[^>]*>\s*<a href="'.preg_quote($publicUrl, '/').'"\s+target="_blank"\s+rel="noopener noreferrer"[^>]*>\s*Zvanična Odluka novi tab\s*<\/a>/u',
            $item
        );
    }

    public function test_ordinary_and_legacy_html_notice_panel_links_stay_in_the_same_tab(): void
    {
        $ordinary = Notice::factory()->withoutDescription()->create([
            'title' => 'Ordinary Notice isti tab',
            'source_type' => 'other_source',
            'content_delivery' => 'unsupported',
            'visible_in_active_panel' => true,
        ]);
        $legacyHtml = Notice::factory()->withoutDescription()->create([
            'title' => 'Legacy HTML Odluka isti tab',
            'source_type' => 'competition_decision',
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
        ]);

        $ordinaryItem = $this->homePanelItemHtml('Ordinary Notice isti tab');
        $legacyItem = $this->homePanelItemHtml('Legacy HTML Odluka isti tab');

        $ordinaryUrl = route('notices.public-content', $ordinary);
        $legacyUrl = route('notices.public-content', $legacyHtml);

        $this->assertStringNotContainsString('Pogledaj zvanični sadržaj', $ordinaryItem);
        $this->assertStringNotContainsString('Pogledaj zvanični sadržaj', $legacyItem);
        $this->assertSame(1, substr_count($ordinaryItem, 'href="'.$ordinaryUrl.'"'));
        $this->assertSame(1, substr_count($legacyItem, 'href="'.$legacyUrl.'"'));
        $this->assertMatchesRegularExpression(
            '/<h3[^>]*>\s*<a href="'.preg_quote($ordinaryUrl, '/').'"[^>]*>\s*Ordinary Notice isti tab\s*<\/a>/u',
            $ordinaryItem
        );
        $this->assertMatchesRegularExpression(
            '/<h3[^>]*>\s*<a href="'.preg_quote($legacyUrl, '/').'"[^>]*>\s*Legacy HTML Odluka isti tab\s*<\/a>/u',
            $legacyItem
        );
        $this->assertStringNotContainsString('target="_blank"', $ordinaryItem);
        $this->assertStringNotContainsString('rel="noopener noreferrer"', $ordinaryItem);
        $this->assertStringNotContainsString('target="_blank"', $legacyItem);
        $this->assertStringNotContainsString('rel="noopener noreferrer"', $legacyItem);
    }

    public function test_panel_shows_public_display_date_as_datum_objave_in_d_m_y_format(): void
    {
        Storage::fake('local');
        $competition = $this->createCompletedCompetition('Konkurs za FT8 datum');
        $copy = $this->createSignedCopy($competition, 'FT8-PRIVACY-BYTES');

        $notice = Notice::factory()->withoutDescription()->create([
            'title' => 'Naslov sa poslovnim datumom FT8',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'public_display_date' => '2026-01-15',
            'published_at' => '2026-08-20 11:22:33',
        ]);

        $item = $this->homePanelItemHtml('Naslov sa poslovnim datumom FT8');

        $this->assertStringContainsString('Datum objave', $item);
        $this->assertStringContainsString('datetime="2026-01-15"', $item);
        $this->assertStringContainsString('>15.01.2026</time>', $item);
        $this->assertStringNotContainsString('>2026-01-15</time>', $item);
        $this->assertStringNotContainsString('20.08.2026', $item);
        $this->assertStringNotContainsString('11:22:33', $item);
        $this->assertStringNotContainsString($copy->storage_path, $item);
        $this->assertStringNotContainsString('storage_path', $item);
        $this->assertStringNotContainsString('uploaded_by', $item);
        $this->assertStringNotContainsString('actor_user_id', $item);
        $this->assertStringNotContainsString('source_object_id', $item);
        $this->assertStringContainsString(route('notices.public-content', $notice, false), $item);
    }

    public function test_panel_omits_date_row_when_public_display_date_is_null(): void
    {
        Notice::factory()->withoutDescription()->create([
            'title' => 'Naslov bez poslovnog datuma FT8',
            'visible_in_active_panel' => true,
            'public_display_date' => null,
            'published_at' => '2026-03-17 09:45:00',
        ]);

        $this->assertHomePanelOmitsBusinessDate('Naslov bez poslovnog datuma FT8');
        $item = $this->homePanelItemHtml('Naslov bez poslovnog datuma FT8');
        $this->assertStringNotContainsString('17.03.2026', $item);
        $this->assertStringNotContainsString('09:45:00', $item);
    }

    public function test_ordinary_notice_without_public_display_date_keeps_pre_phase_8_panel_shape(): void
    {
        Notice::factory()->withoutDescription()->create([
            'title' => 'Ordinary Notice FT8 izolacija',
            'source_type' => 'other_source',
            'content_delivery' => 'unsupported',
            'visible_in_active_panel' => true,
            'public_display_date' => null,
            'published_at' => '2026-04-11 16:05:00',
        ]);

        Notice::factory()->withoutDescription()->create([
            'title' => 'KN signed-copy sa datumom FT8',
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'public_display_date' => '2026-01-15',
            'published_at' => '2026-08-20 11:22:33',
        ]);

        $ordinary = $this->homePanelItemHtml('Ordinary Notice FT8 izolacija');
        $this->assertStringNotContainsString('Datum objave', $ordinary);
        $this->assertStringNotContainsString('<time', $ordinary);
        $this->assertStringNotContainsString('11.04.2026', $ordinary);
        $this->assertStringNotContainsString('16:05:00', $ordinary);

        $this->assertHomePanelShowsBusinessDate('KN signed-copy sa datumom FT8', '2026-01-15');
    }

    public function test_leftover_html_notice_without_public_display_date_has_no_date_row(): void
    {
        Notice::factory()->withoutDescription()->create([
            'title' => 'Legacy HTML Odluka FT8',
            'source_type' => 'competition_decision',
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'public_display_date' => null,
            'published_at' => '2026-05-09 08:00:00',
        ]);

        $this->assertHomePanelOmitsBusinessDate('Legacy HTML Odluka FT8');
        $item = $this->homePanelItemHtml('Legacy HTML Odluka FT8');
        $this->assertStringNotContainsString('09.05.2026', $item);
    }

    public function test_signed_copy_notice_with_null_public_display_date_omits_date_row(): void
    {
        Notice::factory()->withoutDescription()->create([
            'title' => 'Signed-copy bez datuma FT8',
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'public_display_date' => null,
            'published_at' => '2026-07-21 13:10:00',
        ]);

        $this->assertHomePanelOmitsBusinessDate('Signed-copy bez datuma FT8');
        $item = $this->homePanelItemHtml('Signed-copy bez datuma FT8');
        $this->assertStringNotContainsString('21.07.2026', $item);
        $this->assertStringNotContainsString('13:10:00', $item);
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
        $this->assertNull($event->public_display_date);

        $payload = $event->toPublicationPayload();
        $this->assertFalse($payload['public_revoke']);
        $this->assertNull($payload['source_object_id']);
        $this->assertArrayHasKey('supersedes_notice_id', $payload);
        $this->assertArrayHasKey('public_display_date', $payload);
        $this->assertNull($payload['public_display_date']);

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

    public function test_publicly_unavailable_notice_does_not_serve_content(): void
    {
        $competition = $this->createCompletedCompetition('Konkurs za nedostupnu objavu');

        $notice = Notice::factory()->create([
            'title' => 'Povučeno obavještenje',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => false,
            'publicly_available' => false,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertNotFound();
        $this->assertNull($response->headers->get('Location'));
        $response->assertDontSee('ODLUKU', false);
        $response->assertDontSee('Povučeno obavještenje', false);
    }

    public function test_hidden_but_publicly_available_legacy_html_notice_url_still_serves(): void
    {
        $competition = $this->createCompletedCompetition('Konkurs za skrivenu HTML objavu');

        $notice = Notice::factory()->hiddenFromPanel()->create([
            'title' => 'Skriveno ali dostupno HTML',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'publicly_available' => true,
        ]);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertDontSee('Skriveno ali dostupno HTML', false);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $response->assertSee('ODLUKU', false);
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_corrected_old_notice_does_not_serve_content(): void
    {
        $competition = $this->createCompletedCompetition('Konkurs za korekciju');

        $old = Notice::factory()->create([
            'title' => 'Pogrešna objava za javni URL',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $new = $this->publicationService->publish([
            'title' => 'Ispravljena objava',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'supersedes_notice_id' => $old->id,
            'public_revoke' => true,
        ]);

        $old->refresh();

        $this->assertFalse($old->visible_in_active_panel);
        $this->assertFalse($old->publicly_available);
        $this->assertTrue($new->visible_in_active_panel);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertDontSee('Pogrešna objava za javni URL', false);
        $home->assertSee('Ispravljena objava', false);

        $response = $this->get(route('notices.public-content', $old));

        $response->assertNotFound();
        $this->assertNull($response->headers->get('Location'));
        $response->assertDontSee('ODLUKU', false);
        $response->assertDontSee('Pogrešna objava za javni URL', false);
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

    public function test_ordinary_supersession_hides_panel_but_keeps_direct_public_url(): void
    {
        $competition = $this->createCompletedCompetition('Konkurs ordinary supersession');
        $old = Notice::factory()->create([
            'title' => 'Ordinary staro',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $new = $this->publicationService->publish([
            'title' => 'Ordinary novo',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'supersedes_notice_id' => $old->id,
        ]);

        $old->refresh();

        $this->assertFalse($old->visible_in_active_panel);
        $this->assertTrue($old->publicly_available);
        $this->assertTrue($new->visible_in_active_panel);
        $this->assertTrue($new->publicly_available);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertDontSee('Ordinary staro', false);
        $home->assertSee('Ordinary novo', false);

        $direct = $this->get(route('notices.public-content', $old));
        $direct->assertOk();
        $this->assertNull($direct->headers->get('Location'));
    }

    public function test_update_public_metadata_changes_title_and_display_date_on_the_same_notice(): void
    {
        Storage::fake('local');
        $competition = $this->createCompletedCompetition('Konkurs za metadata');
        $copy = $this->createSignedCopy($competition, 'METADATA-COPY-BYTES');
        $publishedAt = now()->subDays(4);
        $notice = Notice::factory()->create([
            'title' => 'Stari javni naziv',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'published_at' => $publishedAt,
            'public_display_date' => now()->subDays(10)->toDateString(),
        ]);
        $publishedAtPersisted = $notice->published_at->toDateTimeString();
        $noticesBefore = Notice::query()->count();
        $displayDate = now()->subDays(2)->toDateString();

        $updated = $this->publicationService->updatePublicMetadata($notice, [
            'title' => 'Novi javni naziv',
            'public_display_date' => $displayDate,
        ]);

        $this->assertSame($notice->id, $updated->id);
        $this->assertSame($noticesBefore, Notice::query()->count());
        $this->assertSame('Novi javni naziv', $updated->title);
        $this->assertSame($displayDate, optional($updated->public_display_date)?->toDateString());
        $this->assertSame($publishedAtPersisted, $updated->published_at->toDateTimeString());
        $this->assertSame('competition_decision', $updated->source_type);
        $this->assertSame($competition->id, $updated->source_id);
        $this->assertSame($copy->id, $updated->source_object_id);
        $this->assertSame('competition_decision_signed_copy', $updated->content_delivery);
        $this->assertTrue($updated->publicly_available);
        $this->assertTrue($updated->visible_in_active_panel);

        $response = $this->get(route('notices.public-content', $updated));
        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame(
            'METADATA-COPY-BYTES',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_update_public_metadata_does_not_republish_a_revoked_notice(): void
    {
        $notice = Notice::factory()->create([
            'title' => 'Povučeni naziv',
            'visible_in_active_panel' => false,
            'publicly_available' => false,
            'public_display_date' => now()->subDay()->toDateString(),
        ]);

        $updated = $this->publicationService->updatePublicMetadata($notice, [
            'title' => 'Ispravljeni povučeni naziv',
            'public_display_date' => now()->subDays(3)->toDateString(),
        ]);

        $this->assertSame($notice->id, $updated->id);
        $this->assertSame('Ispravljeni povučeni naziv', $updated->title);
        $this->assertFalse($updated->visible_in_active_panel);
        $this->assertFalse($updated->publicly_available);
        $this->assertSame(1, Notice::query()->count());
    }

    public function test_revoke_public_availability_hides_panel_and_direct_access_without_new_notice(): void
    {
        Storage::fake('local');
        $competition = $this->createCompletedCompetition('Konkurs za revoke');
        $copy = $this->createSignedCopy($competition, 'REVOKE-COPY-BYTES');
        $publishedAt = now()->subDays(5);
        $displayDate = now()->subDays(6)->toDateString();
        $notice = Notice::factory()->create([
            'title' => 'Aktivna objava za revoke',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'published_at' => $publishedAt,
            'public_display_date' => $displayDate,
            'superseded_notice_id' => null,
        ]);
        $publishedAtPersisted = $notice->published_at->toDateTimeString();
        $noticesBefore = Notice::query()->count();

        $this->get(route('home'))->assertSee('Aktivna objava za revoke', false);
        $this->get(route('notices.public-content', $notice))->assertOk();

        $revoked = $this->publicationService->revokePublicAvailability($notice);

        $this->assertSame($notice->id, $revoked->id);
        $this->assertSame($noticesBefore, Notice::query()->count());
        $this->assertFalse($revoked->visible_in_active_panel);
        $this->assertFalse($revoked->publicly_available);
        $this->assertSame('Aktivna objava za revoke', $revoked->title);
        $this->assertSame($displayDate, optional($revoked->public_display_date)?->toDateString());
        $this->assertSame($publishedAtPersisted, $revoked->published_at->toDateTimeString());
        $this->assertSame('competition_decision', $revoked->source_type);
        $this->assertSame($competition->id, $revoked->source_id);
        $this->assertSame($copy->id, $revoked->source_object_id);
        $this->assertSame('competition_decision_signed_copy', $revoked->content_delivery);
        $this->assertNull($revoked->superseded_notice_id);
        Storage::disk('local')->assertExists($copy->storage_path);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertDontSee('Aktivna objava za revoke', false);

        $direct = $this->get(route('notices.public-content', $revoked));
        $direct->assertNotFound();
        $this->assertNull($direct->headers->get('Location'));
        $this->assertStringNotContainsString('REVOKE-COPY-BYTES', $direct->getContent());
    }

    public function test_revoke_public_availability_is_idempotent(): void
    {
        $publishedAt = now()->subDays(2);
        $notice = Notice::factory()->create([
            'title' => 'Već povučeno',
            'visible_in_active_panel' => false,
            'publicly_available' => false,
            'published_at' => $publishedAt,
            'public_display_date' => now()->subDays(8)->toDateString(),
        ]);
        $publishedAtPersisted = $notice->published_at->toDateTimeString();
        $noticesBefore = Notice::query()->count();

        $first = $this->publicationService->revokePublicAvailability($notice);
        $second = $this->publicationService->revokePublicAvailability($first);

        $this->assertSame($notice->id, $second->id);
        $this->assertSame($noticesBefore, Notice::query()->count());
        $this->assertFalse($second->visible_in_active_panel);
        $this->assertFalse($second->publicly_available);
        $this->assertSame('Već povučeno', $second->title);
        $this->assertSame($publishedAtPersisted, $second->published_at->toDateTimeString());
    }

    public function test_metadata_event_listener_updates_public_metadata_synchronously(): void
    {
        $notice = Notice::factory()->create([
            'title' => 'Kanal stari naziv',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'published_at' => now()->subDays(3),
            'public_display_date' => now()->subDays(9)->toDateString(),
        ]);
        $publishedAt = $notice->published_at->toDateTimeString();
        $displayDate = now()->subDays(2)->toDateString();

        $this->assertFalse(is_subclass_of(UpdateOfficialContentPublicMetadata::class, ShouldQueue::class));
        $this->assertFalse(is_subclass_of(OfficialContentPublicMetadataUpdated::class, ShouldDispatchAfterCommit::class));
        $this->assertFalse(is_subclass_of(OfficialContentPublicMetadataUpdated::class, ShouldQueue::class));

        $listeners = app('events')->getRawListeners()[OfficialContentPublicMetadataUpdated::class] ?? [];
        $this->assertCount(1, $listeners);

        event(new OfficialContentPublicMetadataUpdated($notice->id, 'Kanal novi naziv', $displayDate));

        $notice->refresh();
        $this->assertSame('Kanal novi naziv', $notice->title);
        $this->assertSame($displayDate, optional($notice->public_display_date)?->toDateString());
        $this->assertSame($publishedAt, $notice->published_at->toDateTimeString());
        $this->assertTrue($notice->publicly_available);
        $this->assertTrue($notice->visible_in_active_panel);
        $this->assertSame(1, Notice::query()->count());
    }

    public function test_revoke_event_listener_revokes_public_availability_synchronously(): void
    {
        $notice = Notice::factory()->create([
            'title' => 'Kanal za revoke event',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $this->assertFalse(is_subclass_of(RevokeOfficialContentPublicAvailability::class, ShouldQueue::class));
        $this->assertFalse(is_subclass_of(OfficialContentPublicAvailabilityRevoked::class, ShouldDispatchAfterCommit::class));
        $this->assertFalse(is_subclass_of(OfficialContentPublicAvailabilityRevoked::class, ShouldQueue::class));

        $listeners = app('events')->getRawListeners()[OfficialContentPublicAvailabilityRevoked::class] ?? [];
        $this->assertCount(1, $listeners);

        event(new OfficialContentPublicAvailabilityRevoked($notice->id));

        $notice->refresh();
        $this->assertFalse($notice->visible_in_active_panel);
        $this->assertFalse($notice->publicly_available);
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame('Kanal za revoke event', $notice->title);
    }

    public function test_metadata_and_revoke_events_do_not_carry_kn_authorization_or_overload_publish(): void
    {
        $metadataEvent = file_get_contents((new ReflectionClass(OfficialContentPublicMetadataUpdated::class))->getFileName());
        $revokeEvent = file_get_contents((new ReflectionClass(OfficialContentPublicAvailabilityRevoked::class))->getFileName());
        $metadataListener = file_get_contents((new ReflectionClass(UpdateOfficialContentPublicMetadata::class))->getFileName());
        $revokeListener = file_get_contents((new ReflectionClass(RevokeOfficialContentPublicAvailability::class))->getFileName());
        $publishListener = file_get_contents((new ReflectionClass(PublishOfficialContentNotice::class))->getFileName());
        $publishEvent = file_get_contents((new ReflectionClass(OfficialContentReadyForPublicPublication::class))->getFileName());

        foreach ([$metadataEvent, $revokeEvent, $metadataListener, $revokeListener] as $source) {
            $this->assertStringNotContainsString('konkurs_admin', $source);
            $this->assertStringNotContainsString('assertKonkursAdmin', $source);
            $this->assertStringNotContainsString('permanently_deleted', $source);
            $this->assertStringNotContainsString('closed', $source);
        }

        $this->assertStringNotContainsString('updatePublicMetadata', $publishListener);
        $this->assertStringNotContainsString('revokePublicAvailability', $publishListener);
        $this->assertStringNotContainsString('OfficialContentPublicMetadataUpdated', $publishEvent);
        $this->assertStringNotContainsString('OfficialContentPublicAvailabilityRevoked', $publishEvent);
        $this->assertStringContainsString('updatePublicMetadata', $metadataListener);
        $this->assertStringContainsString('revokePublicAvailability', $revokeListener);
    }

    private function createSignedCopy(Competition $competition, string $contents): CompetitionOfficialDecisionCopy
    {
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/'.$competition->id.'/official-decisions/'.uniqid('copy_', true).'.pdf',
        ]);
        Storage::disk('local')->put($copy->storage_path, $contents);

        return $copy;
    }

    private function createCompletedCompetition(string $title): Competition
    {
        return Competition::create([
            'title' => $title,
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
    }

    private function homePanelItemHtml(string $title): string
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString($title, $html);

        $this->assertTrue(
            (bool) preg_match('/<section class="obavjestenja-panel"[\s\S]*?<\/section>/u', $html, $panel),
            'Obavještenja panel was not found on home.'
        );

        $quoted = preg_quote($title, '/');
        $this->assertTrue(
            (bool) preg_match(
                '/<li\b[^>]*>((?:(?!<\/li>).)*'.$quoted.'(?:(?!<\/li>).)*)<\/li>/us',
                $panel[0],
                $matches
            ),
            'Home panel item for ['.$title.'] was not found.'
        );

        return $matches[0];
    }

    private function assertHomePanelShowsBusinessDate(string $title, string $isoDate): void
    {
        $item = $this->homePanelItemHtml($title);
        $visible = \Illuminate\Support\Carbon::parse($isoDate)->format('d.m.Y');

        $this->assertStringContainsString('Datum objave', $item);
        $this->assertStringContainsString('datetime="'.$isoDate.'"', $item);
        $this->assertStringContainsString('>'.$visible.'</time>', $item);
    }

    private function assertHomePanelOmitsBusinessDate(string $title): void
    {
        $item = $this->homePanelItemHtml($title);

        $this->assertStringNotContainsString('Datum objave', $item);
        $this->assertStringNotContainsString('<time', $item);
    }
}
