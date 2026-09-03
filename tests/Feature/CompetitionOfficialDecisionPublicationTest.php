<?php

namespace Tests\Feature;

use App\Events\OfficialContentReadyForPublicPublication;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class CompetitionOfficialDecisionPublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_signed_copy_delivers_the_exact_referenced_source_object(): void
    {
        $competition = $this->createCompletedCompetition();

        $first = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/first.bin',
        ]);
        $second = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/second.bin',
        ]);

        Storage::disk('local')->put($first->storage_path, 'FIRST-COPY-BYTES');
        Storage::disk('local')->put($second->storage_path, 'SECOND-COPY-BYTES');

        $notice = Notice::factory()->create([
            'title' => 'Objava drugog primjerka',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $second->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $this->assertNull($response->headers->get('Location'));
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame(
            'SECOND-COPY-BYTES',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
        $this->assertStringNotContainsString('FIRST-COPY-BYTES', $this->servedFileContents($response));
        $response->assertDontSee('ODLUKU', false);
    }

    public function test_signed_copy_is_delivered_to_guest_without_authentication(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/guest.bin',
        ]);
        Storage::disk('local')->put($copy->storage_path, 'GUEST-SIGNED-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $this->assertGuest();
        $this->assertSame('GUEST-SIGNED-COPY', $this->servedFileContents($response));
    }

    public function test_signed_copy_does_not_serve_when_source_object_id_is_missing(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/orphan.bin',
        ]);
        Storage::disk('local')->put($copy->storage_path, 'SHOULD-NOT-SERVE-WITHOUT-SOURCE-OBJECT');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => null,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'SHOULD-NOT-SERVE-WITHOUT-SOURCE-OBJECT');
    }

    public function test_signed_copy_does_not_serve_nonexistent_source_object(): void
    {
        $competition = $this->createCompletedCompetition();
        $existing = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/existing.bin',
        ]);
        $dangling = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/dangling.bin',
        ]);
        Storage::disk('local')->put($existing->storage_path, 'SHOULD-NOT-SERVE-LATEST-COPY');
        Storage::disk('local')->put($dangling->storage_path, 'SHOULD-NOT-SERVE-DELETED-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $dangling->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $missingId = $dangling->id;

        Schema::disableForeignKeyConstraints();
        try {
            $dangling->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->assertNull(CompetitionOfficialDecisionCopy::query()->find($missingId));

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'SHOULD-NOT-SERVE-LATEST-COPY');
        $this->assertStringNotContainsString('SHOULD-NOT-SERVE-DELETED-COPY', $response->getContent());
    }

    public function test_signed_copy_does_not_serve_copy_from_another_competition(): void
    {
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');

        $copyB = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competitionB->id,
            'storage_path' => 'competitions/decisions/competition-b.bin',
        ]);
        Storage::disk('local')->put($copyB->storage_path, 'COMPETITION-B-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competitionA->id,
            'source_object_id' => $copyB->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'COMPETITION-B-COPY');
    }

    public function test_signed_copy_does_not_serve_when_file_is_missing_from_disk(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/missing.bin',
        ]);

        $this->assertFalse(Storage::disk('local')->exists($copy->storage_path));

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response);
    }

    public function test_signed_copy_does_not_serve_when_notice_is_not_publicly_available(): void
    {
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/decisions/revoked.bin',
        ]);
        Storage::disk('local')->put($copy->storage_path, 'REVOKED-SIGNED-COPY');

        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => false,
            'publicly_available' => false,
        ]);

        $response = $this->get(route('notices.public-content', $notice));

        $this->assertSignedCopyDeliveryFailed($response, 'REVOKED-SIGNED-COPY');
    }

    public function test_konkurs_admin_can_publish_uploaded_signed_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $notice = Notice::query()->sole();
        $this->assertSame('competition_decision', $notice->source_type);
        $this->assertSame($competition->id, $notice->source_id);
        $this->assertSame($copy->id, $notice->source_object_id);
        $this->assertSame('competition_decision_signed_copy', $notice->content_delivery);
        $this->assertTrue($notice->visible_in_active_panel);
        $this->assertTrue($notice->publicly_available);
        $this->assertNull($notice->superseded_notice_id);
        $this->assertSame($copy->business_title, $notice->title);
        $this->assertNotSame('Odluka o dodjeli sredstava', $notice->title);
        $this->assertSame(now()->toDateString(), optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertSame(now()->toDateString(), optional($notice->public_display_date)?->toDateString());
        $this->assertNotNull($notice->published_at);
        $this->assertTrue($notice->published_at->isSameDay(now()));
    }

    public function test_first_publish_dispatches_event_without_public_revoke(): void
    {
        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        Event::assertDispatched(OfficialContentReadyForPublicPublication::class, function ($event) use ($competition, $copy) {
            return $event->public_revoke === false
                && $event->supersedes_notice_id === null
                && $event->source_object_id === $copy->id
                && $event->source_id === $competition->id
                && $event->source_type === 'competition_decision'
                && $event->content_delivery === 'competition_decision_signed_copy'
                && $event->title === $copy->business_title
                && $event->public_display_date === now()->toDateString();
        });
    }

    public function test_admin_cannot_publish_signed_copy(): void
    {
        $this->assertRoleCannotPublish('admin');
    }

    public function test_superadmin_cannot_publish_signed_copy(): void
    {
        $this->assertRoleCannotPublish('superadmin');
    }

    public function test_commission_member_cannot_publish_signed_copy(): void
    {
        $this->assertRoleCannotPublish('komisija');
    }

    public function test_ordinary_user_cannot_publish_signed_copy(): void
    {
        $this->assertRoleCannotPublish('korisnik');
    }

    public function test_duplicate_publish_of_the_same_copy_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $this->assertSame(1, Notice::query()->count());

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame($copy->id, Notice::query()->sole()->source_object_id);
    }

    public function test_second_copy_cannot_be_published_as_ordinary_first_publication(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();
        $this->assertSame($copyA->id, $noticeA->source_object_id);

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyB]),
            $this->firstPublishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);

        $this->assertSame(1, Notice::query()->count());
        $this->assertFalse($copyB->fresh()->hasBeenPublished());

        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
        $this->assertNull($noticeA->superseded_notice_id);
        $this->assertSame($copyA->id, $noticeA->source_object_id);

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Objavljena', false);
        $page->assertDontSee('>Objavi Odluku</button>', false);
        $page->assertSee('Zamijeni Odluku', false);
        $page->assertSee('Objavi zamjenu', false);
    }

    public function test_copy_from_another_competition_cannot_be_published(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');
        $copyB = $this->createCopyWithFile($competitionB);

        $noticesBefore = Notice::query()->count();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competitionA, $copyB]),
            $this->firstPublishPayload(),
        );

        $response->assertNotFound();
        $this->assertSame($noticesBefore, Notice::query()->count());
    }

    public function test_publish_is_rejected_when_file_is_missing(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/'.$competition->id.'/official-decisions/missing.pdf',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        );

        $response->assertNotFound();
        $this->assertSame(0, Notice::query()->count());
        $copy->refresh();
        $this->assertSame('competitions/'.$competition->id.'/official-decisions/missing.pdf', $copy->storage_path);
    }

    public function test_source_copy_is_unchanged_after_publish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $path = $copy->storage_path;
        $uploadedBy = $copy->uploaded_by;
        $bytes = Storage::disk('local')->get($path);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $copy->refresh();
        $this->assertSame($path, $copy->storage_path);
        $this->assertSame($uploadedBy, $copy->uploaded_by);
        $this->assertSame($competition->id, $copy->competition_id);
        $this->assertSame($bytes, Storage::disk('local')->get($path));
        $this->assertSame(now()->toDateString(), optional($copy->business_published_on)?->toDateString());
    }

    public function test_upload_without_publish_does_not_create_a_notice(): void
    {
        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storeOfficialDecisionPayload(
                UploadedFile::fake()->create('odluka.pdf', 120, 'application/pdf')
            ),
        )->assertRedirect();

        $this->assertSame(1, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame(0, Notice::query()->count());
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
    }

    public function test_publish_is_rejected_when_competition_is_still_published(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('published');
        $copy = $this->createCopyWithFile($competition);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        );

        $response->assertForbidden();
        $this->assertSame(0, Notice::query()->count());
    }

    public function test_closed_competition_allows_publish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompetition('closed');
        $copy = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $this->assertSame(1, Notice::query()->count());
    }

    public function test_upload_then_publish_delivers_exact_file_to_guest(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $payload = "%PDF-1.4\nE2E-SIGNED-COPY-BYTES";

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storeOfficialDecisionPayload(
                UploadedFile::fake()->createWithContent('odluka.pdf', $payload)
            ),
        )->assertRedirect();

        $copy = CompetitionOfficialDecisionCopy::query()->sole();
        $this->assertSame(0, Notice::query()->count());

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $notice = Notice::query()->sole();
        $copy->refresh();
        $this->assertSame($copy->id, $notice->source_object_id);
        $this->assertNull($notice->superseded_notice_id);
        $this->assertTrue($notice->publicly_available);
        $this->assertSame($copy->business_title, $notice->title);
        $this->assertSame(
            optional($copy->business_published_on)?->toDateString(),
            optional($notice->public_display_date)?->toDateString()
        );
        $this->assertNotNull($notice->published_at);
        $this->assertTrue($notice->published_at->isSameDay(now()));

        auth()->logout();

        $response = $this->get(route('notices.public-content', $notice));

        $response->assertOk();
        $this->assertGuest();
        $this->assertSame(
            Storage::disk('local')->get($copy->storage_path),
            $this->servedFileContents($response)
        );
    }

    public function test_unpublished_copy_shows_publish_button_and_published_copy_does_not(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $before = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $before->assertOk();
        $before->assertSee('Objavi Odluku', false);
        $before->assertSee('Nije objavljena', false);
        $before->assertSee('Datum objave', false);
        $before->assertSee('name="business_published_on"', false);
        $before->assertDontSee('Objavljena', false);
        $before->assertDontSee('Koriguj', false);
        $before->assertDontSee('Povuci', false);
        $before->assertDontSee('Ispravi podatke objave', false);
        $before->assertDontSee('Ponovo objavi', false);
        $before->assertSee('Odustani i obriši Odluku', false);
        $before->assertDontSee('Trajno obriši', false);
        $before->assertDontSee('Upravljaj objavom', false);
        $before->assertDontSee('>Učitaj Odluku</button>', false);
        $before->assertDontSee('Evidentiran', false);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $after = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $after->assertOk();
        $after->assertSee('Objavljena', false);
        $after->assertSee('Otvori PDF', false);
        $after->assertSee('Upravljaj objavom', false);
        $after->assertDontSee('>Objavi Odluku</button>', false);
        $after->assertDontSee('Koriguj', false);
        $after->assertSee('Ispravi podatke objave', false);
        $after->assertSee('Povuci objavu', false);
        $after->assertDontSee('Ponovo objavi', false);
        $after->assertSee('Trajno obriši', false);
        $after->assertSee('Zamijeni Odluku', false);
        $after->assertSee('Učitaj zamjensku Odluku', false);
        $after->assertDontSee('Evidentiran', false);
    }

    public function test_konkurs_admin_can_correct_wrongly_published_signed_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'OLD-COPY-BYTES');
        $copyB = $this->createCopyWithFile($competition, 'NEW-COPY-BYTES');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();
        $statusBefore = $competition->status;
        $closedAtBefore = optional($competition->closed_at)?->toDateTimeString();
        $titleBefore = $competition->title;
        $yearBefore = $competition->year;
        $budgetBefore = $competition->budget;

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $this->assertSame(2, Notice::query()->count());

        $noticeA->refresh();
        $noticeB = Notice::query()->whereKeyNot($noticeA->id)->sole();

        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertSame($copyA->id, $noticeA->source_object_id);

        $this->assertTrue($noticeB->visible_in_active_panel);
        $this->assertTrue($noticeB->publicly_available);
        $this->assertSame($noticeA->id, $noticeB->superseded_notice_id);
        $this->assertSame($copyB->id, $noticeB->source_object_id);
        $this->assertSame('competition_decision_signed_copy', $noticeB->content_delivery);
        $this->assertSame($copyB->business_title, $noticeB->title);
        $this->assertNotSame('Odluka o dodjeli sredstava', $noticeB->title);
        $this->assertSame(now()->toDateString(), optional($copyB->fresh()->business_published_on)?->toDateString());
        $this->assertSame(now()->toDateString(), optional($noticeB->public_display_date)?->toDateString());
        $this->assertSame(now()->toDateString(), optional($copyA->fresh()->business_published_on)?->toDateString());

        $this->assertNotNull(CompetitionOfficialDecisionCopy::query()->find($copyA->id));
        $this->assertNotNull(CompetitionOfficialDecisionCopy::query()->find($copyB->id));
        Storage::disk('local')->assertExists($copyA->storage_path);
        Storage::disk('local')->assertExists($copyB->storage_path);

        $competition->refresh();
        $this->assertSame($statusBefore, $competition->status);
        $this->assertSame($closedAtBefore, optional($competition->closed_at)?->toDateTimeString());
        $this->assertSame($titleBefore, $competition->title);
        $this->assertSame($yearBefore, $competition->year);
        $this->assertSame($budgetBefore, $competition->budget);
    }

    public function test_second_correction_revokes_previous_active_notice_and_keeps_full_trace(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->where('source_object_id', $copyA->id)->sole();
        $noticeB = Notice::query()->where('source_object_id', $copyB->id)->sole();
        $statusBefore = $competition->status;
        $closedAtBefore = optional($competition->closed_at)?->toDateTimeString();
        $budgetBefore = $competition->budget;

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storeOfficialDecisionPayload(
                UploadedFile::fake()->createWithContent('korekcija-c.pdf', 'COPY-C-BYTES')
            ),
        )->assertRedirect();

        $copyC = CompetitionOfficialDecisionCopy::query()->whereKeyNot([$copyA->id, $copyB->id])->sole();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyC]),
            $this->correctionPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $this->assertSame(3, Notice::query()->count());
        $this->assertSame(3, CompetitionOfficialDecisionCopy::query()->count());

        $noticeA->refresh();
        $noticeB->refresh();
        $noticeC = Notice::query()->where('source_object_id', $copyC->id)->sole();

        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertSame($copyA->id, $noticeA->source_object_id);
        $this->assertNull($noticeA->superseded_notice_id);

        $this->assertFalse($noticeB->visible_in_active_panel);
        $this->assertFalse($noticeB->publicly_available);
        $this->assertSame($noticeA->id, $noticeB->superseded_notice_id);
        $this->assertSame($copyB->id, $noticeB->source_object_id);

        $this->assertTrue($noticeC->visible_in_active_panel);
        $this->assertTrue($noticeC->publicly_available);
        $this->assertSame($noticeB->id, $noticeC->superseded_notice_id);
        $this->assertSame($copyC->id, $noticeC->source_object_id);

        Storage::disk('local')->assertExists($copyA->storage_path);
        Storage::disk('local')->assertExists($copyB->storage_path);
        Storage::disk('local')->assertExists($copyC->storage_path);

        $competition->refresh();
        $this->assertSame($statusBefore, $competition->status);
        $this->assertSame($closedAtBefore, optional($competition->closed_at)?->toDateTimeString());
        $this->assertSame($budgetBefore, $competition->budget);

        auth()->logout();

        $oldA = $this->get(route('notices.public-content', $noticeA));
        $oldA->assertNotFound();
        $this->assertStringNotContainsString('COPY-A-BYTES', $oldA->getContent());

        $oldB = $this->get(route('notices.public-content', $noticeB));
        $oldB->assertNotFound();
        $this->assertStringNotContainsString('COPY-B-BYTES', $oldB->getContent());

        $newC = $this->get(route('notices.public-content', $noticeC));
        $newC->assertOk();
        $this->assertGuest();
        $this->assertSame('COPY-C-BYTES', $this->servedFileContents($newC));
        $this->assertStringNotContainsString('COPY-A-BYTES', $this->servedFileContents($newC));
        $this->assertStringNotContainsString('COPY-B-BYTES', $this->servedFileContents($newC));

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Objavljena', false);
        $page->assertDontSee('>Objavi Odluku</button>', false);
        $page->assertDontSee('Koriguj objavu', false);
        $page->assertDontSee('Objavi zamjenu', false);
    }

    public function test_correction_revokes_old_public_url_and_serves_new_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'OLD-COPY-BYTES');
        $copyB = $this->createCopyWithFile($competition, 'NEW-COPY-BYTES');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        )->assertRedirect();

        $noticeB = Notice::query()->whereKeyNot($noticeA->id)->sole();

        auth()->logout();

        $oldResponse = $this->get(route('notices.public-content', $noticeA));
        $oldResponse->assertNotFound();
        $this->assertStringNotContainsString('OLD-COPY-BYTES', $oldResponse->getContent());
        $this->assertStringNotContainsString('NEW-COPY-BYTES', $oldResponse->getContent());

        $newResponse = $this->get(route('notices.public-content', $noticeB));
        $newResponse->assertOk();
        $this->assertGuest();
        $this->assertSame('NEW-COPY-BYTES', $this->servedFileContents($newResponse));
        $this->assertStringNotContainsString('OLD-COPY-BYTES', $this->servedFileContents($newResponse));
    }

    public function test_correction_dispatches_event_with_public_revoke_and_predecessor(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $oldNotice = Notice::query()->sole();

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        )->assertRedirect();

        Event::assertDispatched(OfficialContentReadyForPublicPublication::class, function ($event) use ($oldNotice, $copyB, $competition) {
            return $event->public_revoke === true
                && $event->supersedes_notice_id === $oldNotice->id
                && $event->source_object_id === $copyB->id
                && $event->source_id === $competition->id
                && $event->content_delivery === 'competition_decision_signed_copy'
                && $event->title === $copyB->business_title
                && $event->public_display_date === now()->toDateString();
        });
    }

    public function test_admin_cannot_correct_signed_copy(): void
    {
        $this->assertRoleCannotCorrect('admin');
    }

    public function test_superadmin_cannot_correct_signed_copy(): void
    {
        $this->assertRoleCannotCorrect('superadmin');
    }

    public function test_commission_member_cannot_correct_signed_copy(): void
    {
        $this->assertRoleCannotCorrect('komisija');
    }

    public function test_ordinary_user_cannot_correct_signed_copy(): void
    {
        $this->assertRoleCannotCorrect('korisnik');
    }

    public function test_correction_is_rejected_without_an_active_publication(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.correct', [$competition, $copy]),
            $this->correctionPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, Notice::query()->count());
    }

    public function test_correction_copy_from_another_competition_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');
        $copyA = $this->createCopyWithFile($competitionA);
        $copyB = $this->createCopyWithFile($competitionB);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competitionA, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competitionA, $copyB]),
            $this->correctionPayload(),
        );

        $response->assertNotFound();
        $this->assertSame(1, Notice::query()->count());
        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
    }

    public function test_correction_is_rejected_when_new_copy_was_already_published(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copyB->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => false,
            'publicly_available' => false,
        ]);

        $noticeA = Notice::query()->where('source_object_id', $copyA->id)->sole();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(2, Notice::query()->count());
        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
    }

    public function test_correction_is_rejected_when_new_copy_is_the_active_source(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyA]),
            $this->correctionPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(1, Notice::query()->count());
        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
        $this->assertNull($noticeA->superseded_notice_id);
    }

    public function test_correction_is_rejected_when_new_file_is_missing(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/'.$competition->id.'/official-decisions/missing-correct.pdf',
        ]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        );

        $response->assertNotFound();
        $this->assertSame(1, Notice::query()->count());
        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
    }

    public function test_correction_is_rejected_when_multiple_active_signed_copy_notices_exist(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = $this->createCopyWithFile($competition);
        $copyC = $this->createCopyWithFile($competition);

        Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copyA->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);
        Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copyB->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);

        $noticesBefore = Notice::query()->get()->map(fn (Notice $notice) => [
            'id' => $notice->id,
            'publicly_available' => $notice->publicly_available,
            'visible_in_active_panel' => $notice->visible_in_active_panel,
            'superseded_notice_id' => $notice->superseded_notice_id,
        ])->all();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyC]),
            $this->correctionPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(2, Notice::query()->count());
        $this->assertFalse($copyC->fresh()->hasBeenPublished());

        foreach ($noticesBefore as $snapshot) {
            $notice = Notice::query()->findOrFail($snapshot['id']);
            $this->assertTrue($notice->publicly_available);
            $this->assertTrue($notice->visible_in_active_panel);
            $this->assertNull($notice->superseded_notice_id);
        }
    }

    public function test_unpublished_second_copy_shows_correct_not_ordinary_publish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Objavljena', false);
        $page->assertSee('Zamijeni Odluku', false);
        $page->assertSee('Objavi zamjenu', false);
        $page->assertSee('official-decision-correct-form', false);
        $page->assertSee('Datum objave', false);
        $page->assertDontSee('>Objavi Odluku</button>', false);
        $page->assertSee('Povuci objavu', false);
        $page->assertDontSee('Ponovo objavi', false);
        $page->assertDontSee('>Izbriši</', false);
        $page->assertSee('Trajno obriši', false);
    }

    public function test_pending_copy_cannot_be_used_for_correction(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Naziv A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Naziv B');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $copyB->forceFill(['permanent_delete_pending_at' => now()])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        )->assertNotFound();

        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
        $this->assertSame($copyB->storage_path, $copyB->fresh()->storage_path);
        Storage::disk('local')->assertExists($copyB->storage_path);
    }

    public function test_permanently_deleted_copy_cannot_be_used_for_correction(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Naziv A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'COPY-B-BYTES');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $copyB->forceFill([
            'permanently_deleted_at' => now(),
            'permanently_deleted_by' => $admin->id,
        ])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        )->assertNotFound();

        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
        Storage::disk('local')->assertExists($copyB->storage_path);
    }

    public function test_first_publish_without_business_date_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('business_published_on');
        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($copy->fresh()->business_published_on);
        $this->assertFalse($copy->fresh()->hasBeenPublished());
    }

    public function test_first_publish_accepts_today_as_business_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $today = now()->toDateString();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload($today),
        )->assertRedirect();

        $this->assertSame($today, optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertSame($today, optional(Notice::query()->sole()->public_display_date)?->toDateString());
    }

    public function test_first_publish_accepts_past_business_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $past = now()->subDays(10)->toDateString();

        Notice::factory()->withoutDescription()->create([
            'title' => 'Drugi Notice bez KN datuma FT8',
            'source_type' => 'other_source',
            'source_id' => 4242,
            'content_delivery' => 'unsupported',
            'visible_in_active_panel' => true,
            'public_display_date' => null,
            'published_at' => now()->subDays(20),
        ]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload($past),
        )->assertRedirect();

        $notice = Notice::query()->where('source_object_id', $copy->id)->sole();
        $copy->refresh();

        $this->assertSame($past, optional($copy->business_published_on)?->toDateString());
        $this->assertSame($past, optional($notice->public_display_date)?->toDateString());
        $this->assertNotNull($notice->published_at);
        $this->assertTrue($notice->published_at->isSameDay(now()));
        $this->assertNotSame($past, $notice->published_at->toDateString());
        $this->assertSame($copy->id, $notice->source_object_id);
        $this->assertSame($copy->business_title, $notice->title);

        auth()->logout();
        $this->assertHomePanelShowsBusinessDate($copy->business_title, $past);
        $panelItem = $this->homePanelItemHtml($copy->business_title);
        $this->assertStringNotContainsString($notice->published_at->format('d.m.Y'), $panelItem);
        $direct = $this->get(route('notices.public-content', $notice));
        $direct->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $direct->baseResponse);
        $this->assertHomePanelOmitsBusinessDate('Drugi Notice bez KN datuma FT8');
        $otherItem = $this->homePanelItemHtml('Drugi Notice bez KN datuma FT8');
        $this->assertStringNotContainsString(\Illuminate\Support\Carbon::parse($past)->format('d.m.Y'), $otherItem);
    }

    public function test_first_publish_rejects_future_business_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $future = now()->addDay()->toDateString();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload($future),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('business_published_on');
        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($copy->fresh()->business_published_on);
    }

    public function test_direct_post_of_future_business_date_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            ['business_published_on' => now()->addDays(3)->toDateString()],
        );

        $response->assertSessionHasErrors('business_published_on');
        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($copy->fresh()->business_published_on);
        $this->assertFalse($copy->fresh()->hasBeenPublished());
    }

    public function test_first_publish_without_business_title_on_copy_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', null);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('business_title');
        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($copy->fresh()->business_published_on);
    }

    public function test_permanently_deleted_or_pending_copy_cannot_be_published(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $deleted = $this->createCopyWithFile($competition);
        $deleted->forceFill([
            'permanently_deleted_at' => now(),
            'permanently_deleted_by' => $admin->id,
        ])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $deleted]),
            $this->firstPublishPayload(),
        )->assertNotFound();

        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($deleted->fresh()->business_published_on);

        $pendingCompetition = $this->createCompletedCompetition('Konkurs pending');
        $pending = $this->createCopyWithFile($pendingCompetition);
        $pending->forceFill([
            'permanent_delete_pending_at' => now(),
        ])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$pendingCompetition, $pending]),
            $this->firstPublishPayload(),
        )->assertNotFound();

        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($pending->fresh()->business_published_on);
    }

    public function test_notice_publication_failure_does_not_write_business_published_on(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        Notice::creating(function () {
            throw new \RuntimeException('Forced publication failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                route('admin.competitions.official-decision.publish', [$competition, $copy]),
                $this->firstPublishPayload(),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced publication failure', $exception->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $this->assertSame(0, Notice::query()->count());
        $this->assertNull($copy->fresh()->business_published_on);
        $this->assertFalse($copy->fresh()->hasBeenPublished());
    }

    public function test_first_publish_revokes_leftover_legacy_html(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-A-BYTES', 'Naziv signed A');
        $html = $this->legacyHtmlNotice($competition, [
            'title' => 'Legacy HTML Odluka',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'public_display_date' => null,
            'published_at' => now()->subDays(9),
            'short_description' => null,
        ]);

        auth()->logout();
        $this->assertHomePanelOmitsBusinessDate('Legacy HTML Odluka');
        $leftoverItem = $this->homePanelItemHtml('Legacy HTML Odluka');
        $this->assertStringNotContainsString($html->published_at->format('d.m.Y'), $leftoverItem);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $html->refresh();
        $signed = Notice::query()->where('source_object_id', $copy->id)->sole();

        $this->assertFalse($html->visible_in_active_panel);
        $this->assertFalse($html->publicly_available);
        $this->assertTrue($signed->visible_in_active_panel);
        $this->assertTrue($signed->publicly_available);
        $this->assertSame(1, $this->currentPublicDecisionNoticeCount($competition));

        auth()->logout();
        $this->get(route('notices.public-content', $html))->assertNotFound();
        $this->get(route('notices.public-content', $signed))->assertOk();
        $this->assertSame('SIGNED-A-BYTES', $this->servedFileContents(
            $this->get(route('notices.public-content', $signed))
        ));
        $this->assertHomePanelShowsBusinessDate('Naziv signed A', optional($signed->public_display_date)?->toDateString());
        $this->get(route('home'))->assertDontSee('Legacy HTML Odluka', false);
    }

    public function test_correction_revokes_leftover_html_and_leaves_one_current_publication(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Naziv A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Naziv B');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(now()->subDays(4)->toDateString()),
        )->assertRedirect();

        $noticeA = Notice::query()->where('source_object_id', $copyA->id)->sole();
        $html = $this->legacyHtmlNotice($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(now()->subDay()->toDateString()),
        )->assertRedirect();

        $html->refresh();
        $noticeA->refresh();
        $noticeB = Notice::query()->where('source_object_id', $copyB->id)->sole();

        $this->assertFalse($html->visible_in_active_panel);
        $this->assertFalse($html->publicly_available);
        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertTrue($noticeB->visible_in_active_panel);
        $this->assertTrue($noticeB->publicly_available);
        $this->assertSame($noticeA->id, $noticeB->superseded_notice_id);
        $this->assertSame($copyB->id, $noticeB->source_object_id);
        $this->assertSame(1, $this->currentPublicDecisionNoticeCount($competition));

        auth()->logout();
        $this->get(route('notices.public-content', $html))->assertNotFound();
        $this->get(route('notices.public-content', $noticeA))->assertNotFound();
        $this->get(route('notices.public-content', $noticeB))->assertOk();
        $this->assertSame('COPY-B-BYTES', $this->servedFileContents(
            $this->get(route('notices.public-content', $noticeB))
        ));
    }

    public function test_correction_uses_copy_business_title_and_requires_business_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Naziv A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Kanonski naziv B');
        $dateA = now()->subDays(6)->toDateString();
        $dateB = now()->subDays(2)->toDateString();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload($dateA),
        )->assertRedirect();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload($dateB),
        )->assertRedirect();

        $copyA->refresh();
        $copyB->refresh();
        $noticeB = Notice::query()->where('source_object_id', $copyB->id)->sole();

        $this->assertSame('Naziv A', $copyA->business_title);
        $this->assertSame($dateA, optional($copyA->business_published_on)?->toDateString());
        $this->assertSame('Kanonski naziv B', $copyB->business_title);
        $this->assertSame($dateB, optional($copyB->business_published_on)?->toDateString());
        $this->assertSame('Kanonski naziv B', $noticeB->title);
        $this->assertNotSame('Odluka o dodjeli sredstava', $noticeB->title);
        $this->assertSame($dateB, optional($noticeB->public_display_date)?->toDateString());
        $this->assertNotNull($noticeB->published_at);
        $this->assertTrue($noticeB->published_at->isSameDay(now()));
        $this->assertNotSame($dateB, $noticeB->published_at->toDateString());

        auth()->logout();
        $this->assertHomePanelShowsBusinessDate('Kanonski naziv B', $dateB);
        $this->get(route('home'))->assertDontSee('Naziv A', false);
        $this->assertInstanceOf(
            BinaryFileResponse::class,
            $this->get(route('notices.public-content', $noticeB))->baseResponse
        );
    }

    public function test_correction_future_business_date_is_rejected(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Naziv A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Naziv B');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(now()->addDay()->toDateString()),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('business_published_on');
        $this->assertNull($copyB->fresh()->business_published_on);
        $this->assertSame(1, Notice::query()->count());
        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertSame($copyA->id, Notice::query()->sole()->source_object_id);
    }

    public function test_correction_channel_failure_rolls_back_html_predecessor_and_copy_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Naziv A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Naziv B');
        $originalPath = $copyB->storage_path;
        $originalBytes = Storage::disk('local')->get($originalPath);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(now()->subDays(3)->toDateString()),
        )->assertRedirect();

        $noticeA = Notice::query()->where('source_object_id', $copyA->id)->sole();
        $html = $this->legacyHtmlNotice($competition);

        Notice::creating(function () {
            throw new \RuntimeException('Forced correction channel failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                route('admin.competitions.official-decision.correct', [$competition, $copyB]),
                $this->correctionPayload(now()->subDay()->toDateString()),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced correction channel failure', $exception->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $html->refresh();
        $noticeA->refresh();
        $copyB->refresh();

        $this->assertTrue($html->visible_in_active_panel);
        $this->assertTrue($html->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
        $this->assertTrue($noticeA->publicly_available);
        $this->assertNull($copyB->business_published_on);
        $this->assertSame($originalPath, $copyB->storage_path);
        $this->assertSame($originalBytes, Storage::disk('local')->get($originalPath));
        $this->assertSame(0, Notice::query()->where('source_object_id', $copyB->id)->count());
        $this->assertSame(2, Notice::query()->count());
    }

    public function test_leftover_html_query_revokes_inconsistent_flag_combinations(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-A-BYTES', 'Naziv A');
        $htmlVisible = $this->legacyHtmlNotice($competition, [
            'title' => 'HTML visible',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);
        $htmlPublicOnly = $this->legacyHtmlNotice($competition, [
            'title' => 'HTML public only',
            'visible_in_active_panel' => false,
            'publicly_available' => true,
        ]);
        $htmlPanelOnly = $this->legacyHtmlNotice($competition, [
            'title' => 'HTML panel only',
            'visible_in_active_panel' => true,
            'publicly_available' => false,
        ]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $htmlVisible->refresh();
        $htmlPublicOnly->refresh();
        $htmlPanelOnly->refresh();

        foreach ([$htmlVisible, $htmlPublicOnly, $htmlPanelOnly] as $html) {
            $this->assertFalse($html->visible_in_active_panel);
            $this->assertFalse($html->publicly_available);
        }

        $this->assertSame(1, $this->currentPublicDecisionNoticeCount($competition));
        $this->assertTrue(Notice::query()->where('source_object_id', $copy->id)->sole()->publicly_available);
    }

    public function test_leftover_html_cleanup_does_not_touch_other_competitions_or_deliveries(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition('Konkurs target');
        $otherCompetition = $this->createCompletedCompetition('Konkurs other');
        $copy = $this->createCopyWithFile($competition, 'SIGNED-A-BYTES', 'Naziv A');
        $otherHtml = $this->legacyHtmlNotice($otherCompetition, ['title' => 'Other HTML']);
        $otherDelivery = Notice::factory()->create([
            'title' => 'Other delivery',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'other_delivery',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'source_object_id' => null,
        ]);
        $otherSource = Notice::factory()->create([
            'title' => 'Other source',
            'source_type' => 'tender',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'source_object_id' => null,
        ]);
        $this->legacyHtmlNotice($competition, ['title' => 'Target HTML']);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $otherHtml->refresh();
        $otherDelivery->refresh();
        $otherSource->refresh();

        $this->assertTrue($otherHtml->visible_in_active_panel);
        $this->assertTrue($otherHtml->publicly_available);
        $this->assertTrue($otherDelivery->visible_in_active_panel);
        $this->assertTrue($otherDelivery->publicly_available);
        $this->assertTrue($otherSource->visible_in_active_panel);
        $this->assertTrue($otherSource->publicly_available);
        $this->assertFalse(
            CompetitionOfficialDecisionCopy::leftoverDecisionHtmlNoticesQuery($competition->id)->exists()
        );
    }

    public function test_admin_current_view_hides_history_and_shows_one_publish_form_for_newest_never_published_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $older = $this->createCopyWithFile($competition, 'OLDER-BYTES', 'Stariji neobjavljeni naziv');
        $newer = $this->createCopyWithFile($competition, 'NEWER-BYTES', 'Noviji neobjavljeni naziv');

        $this->assertGreaterThan($older->id, $newer->id);
        $this->assertSame(2, CompetitionOfficialDecisionCopy::query()->where('competition_id', $competition->id)->count());

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Noviji neobjavljeni naziv', false);
        $page->assertSee('Nije objavljena', false);
        $page->assertSee('>Objavi Odluku</button>', false);
        $page->assertSee(route('admin.competitions.official-decision.publish', [$competition, $newer]), false);
        $page->assertDontSee(route('admin.competitions.official-decision.publish', [$competition, $older]), false);
        $page->assertDontSee('Stariji neobjavljeni naziv', false);
        $page->assertDontSee('Evidentiran', false);
        $page->assertDontSee('>Učitaj Odluku</button>', false);
        $page->assertDontSee('Upravljaj objavom', false);
        $page->assertSee('Odustani i obriši Odluku', false);
        $this->assertSame(2, CompetitionOfficialDecisionCopy::query()->where('competition_id', $competition->id)->count());
        $this->assertNull($older->fresh()->permanently_deleted_at);
        $this->assertNull($newer->fresh()->permanently_deleted_at);
    }

    public function test_replacement_upload_does_not_change_current_public_publication(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'PUBLIC-BYTES', 'Javna Odluka');
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(now()->subDays(2)->toDateString()),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storeOfficialDecisionPayload(
                UploadedFile::fake()->createWithContent('zamjena.pdf', 'REPLACEMENT-BYTES'),
                'Zamjenska Odluka'
            ),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $copyB = CompetitionOfficialDecisionCopy::query()
            ->where('competition_id', $competition->id)
            ->whereKeyNot($copyA->id)
            ->sole();

        $this->assertFalse($copyB->hasBeenPublished());
        $this->assertTrue($noticeA->fresh()->publicly_available);
        $this->assertSame(1, Notice::query()->where('publicly_available', true)->count());

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertSee('Javna Odluka', false);
        $page->assertSee('Objavljena', false);
        $page->assertSee('Otvori PDF', false);
        $page->assertSee(route('notices.public-content', $noticeA), false);
        $page->assertSee('Zamijeni Odluku', false);
        $page->assertSee('Zamjenska Odluka', false);
        $page->assertSee('Objavi zamjenu', false);
        $page->assertSee(route('admin.competitions.official-decision.correct', [$competition, $copyB]), false);
        $page->assertDontSee('>Objavi Odluku</button>', false);
        $page->assertDontSee('Evidentiran', false);

        $this->get(route('notices.public-content', $noticeA))->assertOk();
    }

    public function test_published_current_view_does_not_list_tombstoned_or_historical_copy_rows(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $tombstone = $this->createCopyWithFile($competition, 'TOMBSTONE-BYTES', 'Istorijski tombstone naziv');
        $tombstone->forceFill([
            'permanently_deleted_at' => now(),
            'permanently_deleted_by' => $admin->id,
            'storage_path' => null,
        ])->save();
        $live = $this->createCopyWithFile($competition, 'LIVE-BYTES', 'Trenutna Odluka');
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $live]),
            $this->firstPublishPayload(now()->subDays(5)->toDateString()),
        )->assertRedirect();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertSee('Trenutna Odluka', false);
        $page->assertSee('Objavljena', false);
        $page->assertSee(now()->subDays(5)->format('d.m.Y'), false);
        $page->assertDontSee('Istorijski tombstone naziv', false);
        $page->assertDontSee('Trajno obrisan', false);
        $page->assertDontSee('Evidentiran', false);
        $page->assertDontSee('postavio', false);
    }

    public function test_withdrawn_current_view_hides_never_published_replacement_candidate(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'WITHDRAWN-A-BYTES', 'Povučena javna Odluka');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(now()->subDays(2)->toDateString()),
        )->assertRedirect();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            $this->storeOfficialDecisionPayload(
                UploadedFile::fake()->createWithContent('zamjena.pdf', 'LEFTOVER-B-BYTES'),
                'Sakrivena zamjenska Odluka'
            ),
        )->assertRedirect();

        $copyB = CompetitionOfficialDecisionCopy::query()
            ->where('competition_id', $competition->id)
            ->whereKeyNot($copyA->id)
            ->sole();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copyA]),
        )->assertRedirect();

        $copyA->refresh();
        $copyB->refresh();
        $this->assertTrue($copyA->hasBeenPublished());
        $this->assertFalse($copyA->isCurrentlyPublished());
        $this->assertNull($copyA->permanently_deleted_at);
        $this->assertNull($copyA->permanent_delete_pending_at);
        $this->assertFalse($copyB->hasBeenPublished());
        $this->assertNull($copyB->permanently_deleted_at);
        $this->assertNull($copyB->permanent_delete_pending_at);

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Povučena javna Odluka', false);
        $page->assertSee('Objava povučena', false);
        $page->assertSee('Ponovo objavi', false);
        $page->assertSee(route('admin.competitions.official-decision.republish', [$competition, $copyA]), false);
        $page->assertDontSee('Sakrivena zamjenska Odluka', false);
        $page->assertDontSee('>Objavi Odluku</button>', false);
        $page->assertDontSee('Objavi zamjenu', false);
        $page->assertDontSee(route('admin.competitions.official-decision.publish', [$competition, $copyB]), false);
        $page->assertDontSee(route('admin.competitions.official-decision.correct', [$competition, $copyB]), false);
        $page->assertDontSee('Evidentiran', false);
        $page->assertDontSee('>Učitaj Odluku</button>', false);
        $page->assertDontSee('Upravljaj objavom', false);

        Event::fake([OfficialContentReadyForPublicPublication::class]);
        $blocked = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyB]),
            $this->firstPublishPayload(),
        );
        $blocked->assertRedirect(route('admin.competitions.show', $competition));
        $blocked->assertSessionHasErrors('error');
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
        $this->assertSame(2, CompetitionOfficialDecisionCopy::query()->where('competition_id', $competition->id)->count());
    }

    private function currentPublicDecisionNoticeCount(Competition $competition): int
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $competition->id)
            ->where(function ($query) {
                $query->where('visible_in_active_panel', true)
                    ->orWhere('publicly_available', true);
            })
            ->count();
    }

    private function legacyHtmlNotice(Competition $competition, array $overrides = []): Notice
    {
        return Notice::factory()->create(array_merge([
            'title' => 'Legacy HTML Odluka',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => null,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ], $overrides));
    }

    private function assertSignedCopyDeliveryFailed($response, ?string $forbiddenBytes = null): void
    {
        $response->assertNotFound();
        $this->assertNull($response->headers->get('Location'));
        $this->assertNotInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $response->assertDontSee('ODLUKU', false);

        if ($forbiddenBytes !== null) {
            $this->assertStringNotContainsString($forbiddenBytes, $response->getContent());
        }
    }

    private function servedFileContents($response): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        return file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    private function createCompletedCompetition(string $title = 'Konkurs za potpisani primjerak'): Competition
    {
        return $this->createCompetition('completed', $title);
    }

    private function createCompetition(string $status, string $title = 'Konkurs za potpisani primjerak'): Competition
    {
        return Competition::create([
            'title' => $title,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => $status,
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'closed_at' => in_array($status, ['closed', 'completed'], true) ? now()->subDay() : null,
        ]);
    }

    private function createCopyWithFile(
        Competition $competition,
        string $contents = 'SIGNED-COPY-BYTES',
        ?string $businessTitle = 'Odluka test primjerka',
    ): CompetitionOfficialDecisionCopy {
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/'.$competition->id.'/official-decisions/'.uniqid('copy_', true).'.pdf',
            'business_title' => $businessTitle,
        ]);
        Storage::disk('local')->put($copy->storage_path, $contents);

        return $copy;
    }

    private function firstPublishPayload(?string $date = null): array
    {
        return [
            'business_published_on' => $date ?? now()->toDateString(),
        ];
    }

    private function correctionPayload(?string $date = null): array
    {
        return [
            'business_published_on' => $date ?? now()->toDateString(),
        ];
    }

    private function storeOfficialDecisionPayload(UploadedFile $file, string $title = 'Odluka test primjerka'): array
    {
        return [
            'official_decision_copy' => $file,
            'business_title' => $title,
        ];
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function assertRoleCannotPublish(string $roleName): void
    {
        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $user = $this->userWithRole($roleName);
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $noticesBefore = Notice::query()->count();

        $response = $this->actingAs($user)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(),
        );

        $response->assertForbidden();
        $this->assertSame($noticesBefore, Notice::query()->count());
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
    }

    private function assertRoleCannotCorrect(string $roleName): void
    {
        $publisher = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition);
        $copyB = $this->createCopyWithFile($competition);

        $this->actingAs($publisher)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
            $this->firstPublishPayload(),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $user = $this->userWithRole($roleName);
        $response = $this->actingAs($user)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
            $this->correctionPayload(),
        );

        $response->assertForbidden();
        $this->assertSame(1, Notice::query()->count());
        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
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
