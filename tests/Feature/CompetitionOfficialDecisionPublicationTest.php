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
        $this->assertSame('Odluka o dodjeli sredstava', $notice->title);
    }

    public function test_first_publish_dispatches_event_without_public_revoke(): void
    {
        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
        )->assertRedirect();

        Event::assertDispatched(OfficialContentReadyForPublicPublication::class, function ($event) use ($competition, $copy) {
            return $event->public_revoke === false
                && $event->supersedes_notice_id === null
                && $event->source_object_id === $copy->id
                && $event->source_id === $competition->id
                && $event->source_type === 'competition_decision'
                && $event->content_delivery === 'competition_decision_signed_copy';
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
        )->assertRedirect();

        $this->assertSame(1, Notice::query()->count());

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
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
        )->assertRedirect();

        $noticeA = Notice::query()->sole();
        $this->assertSame($copyA->id, $noticeA->source_object_id);

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyB]),
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
        $page->assertSee('Objavljeno', false);
        $page->assertDontSee('>Objavi</button>', false);
        $page->assertSee('Koriguj objavu', false);
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
        )->assertRedirect();

        $copy->refresh();
        $this->assertSame($path, $copy->storage_path);
        $this->assertSame($uploadedBy, $copy->uploaded_by);
        $this->assertSame($competition->id, $copy->competition_id);
        $this->assertSame($bytes, Storage::disk('local')->get($path));
    }

    public function test_upload_without_publish_does_not_create_a_notice(): void
    {
        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            ['official_decision_copy' => UploadedFile::fake()->create('odluka.pdf', 120, 'application/pdf')],
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
            ['official_decision_copy' => UploadedFile::fake()->createWithContent('odluka.pdf', $payload)],
        )->assertRedirect();

        $copy = CompetitionOfficialDecisionCopy::query()->sole();
        $this->assertSame(0, Notice::query()->count());

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
        )->assertRedirect();

        $notice = Notice::query()->sole();
        $this->assertSame($copy->id, $notice->source_object_id);
        $this->assertNull($notice->superseded_notice_id);
        $this->assertTrue($notice->publicly_available);

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
        $before->assertSee('Objavi', false);
        $before->assertDontSee('Objavljeno', false);
        $before->assertDontSee('Koriguj', false);
        $before->assertDontSee('Povuci', false);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
        )->assertRedirect();

        $after = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $after->assertOk();
        $after->assertSee('Objavljeno', false);
        $after->assertDontSee('>Objavi</button>', false);
        $after->assertDontSee('Koriguj', false);
        $after->assertDontSee('Povuci', false);
    }

    public function test_konkurs_admin_can_correct_wrongly_published_signed_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'OLD-COPY-BYTES');
        $copyB = $this->createCopyWithFile($competition, 'NEW-COPY-BYTES');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();
        $statusBefore = $competition->status;
        $closedAtBefore = optional($competition->closed_at)?->toDateTimeString();
        $titleBefore = $competition->title;
        $yearBefore = $competition->year;
        $budgetBefore = $competition->budget;

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
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
        )->assertRedirect();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
        )->assertRedirect();

        $noticeA = Notice::query()->where('source_object_id', $copyA->id)->sole();
        $noticeB = Notice::query()->where('source_object_id', $copyB->id)->sole();
        $statusBefore = $competition->status;
        $closedAtBefore = optional($competition->closed_at)?->toDateTimeString();
        $budgetBefore = $competition->budget;

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            ['official_decision_copy' => UploadedFile::fake()->createWithContent('korekcija-c.pdf', 'COPY-C-BYTES')],
        )->assertRedirect();

        $copyC = CompetitionOfficialDecisionCopy::query()->whereKeyNot([$copyA->id, $copyB->id])->sole();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyC]),
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
        $page->assertSee('Objavljeno', false);
        $page->assertDontSee('>Objavi</button>', false);
        $page->assertDontSee('Koriguj objavu', false);
    }

    public function test_correction_revokes_old_public_url_and_serves_new_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'OLD-COPY-BYTES');
        $copyB = $this->createCopyWithFile($competition, 'NEW-COPY-BYTES');

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyA]),
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
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
        )->assertRedirect();

        $oldNotice = Notice::query()->sole();

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
        )->assertRedirect();

        Event::assertDispatched(OfficialContentReadyForPublicPublication::class, function ($event) use ($oldNotice, $copyB, $competition) {
            return $event->public_revoke === true
                && $event->supersedes_notice_id === $oldNotice->id
                && $event->source_object_id === $copyB->id
                && $event->source_id === $competition->id
                && $event->content_delivery === 'competition_decision_signed_copy';
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
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competitionA, $copyB]),
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
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyA]),
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
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
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
        )->assertRedirect();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Objavljeno', false);
        $page->assertSee('Koriguj objavu', false);
        $page->assertDontSee('>Objavi</button>', false);
        $page->assertDontSee('Povuci', false);
        $page->assertDontSee('Zamijeni', false);
        $page->assertDontSee('Izbriši', false);
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

    private function createCopyWithFile(Competition $competition, string $contents = 'SIGNED-COPY-BYTES'): CompetitionOfficialDecisionCopy
    {
        $copy = CompetitionOfficialDecisionCopy::create([
            'competition_id' => $competition->id,
            'storage_path' => 'competitions/'.$competition->id.'/official-decisions/'.uniqid('copy_', true).'.pdf',
        ]);
        Storage::disk('local')->put($copy->storage_path, $contents);

        return $copy;
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
        )->assertRedirect();

        $noticeA = Notice::query()->sole();

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $user = $this->userWithRole($roleName);
        $response = $this->actingAs($user)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copyB]),
        );

        $response->assertForbidden();
        $this->assertSame(1, Notice::query()->count());
        $noticeA->refresh();
        $this->assertTrue($noticeA->publicly_available);
        $this->assertTrue($noticeA->visible_in_active_panel);
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
    }
}
