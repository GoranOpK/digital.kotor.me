<?php

namespace Tests\Feature;

use App\Events\OfficialContentPublicAvailabilityRevoked;
use App\Events\OfficialContentPublicMetadataUpdated;
use App\Events\OfficialContentReadyForPublicPublication;
use App\Http\Controllers\Admin\CompetitionOfficialDecisionController;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\CompetitionOfficialDecisionLifecycleEvent;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class CompetitionOfficialDecisionLifecycleActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_konkurs_admin_can_correct_publication_metadata_on_the_same_notice_and_pdf(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'METADATA-PDF-BYTES', 'Stari poslovni naziv');
        $originalPath = $copy->storage_path;
        $originalBytes = Storage::disk('local')->get($originalPath);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload(now()->subDays(8)->toDateString()),
        )->assertRedirect();

        $notice = Notice::query()->sole();
        $publishedAt = $notice->published_at->toDateTimeString();
        $sourceType = $notice->source_type;
        $sourceId = $notice->source_id;
        $sourceObjectId = $notice->source_object_id;
        $contentDelivery = $notice->content_delivery;
        $noticesBefore = Notice::query()->count();
        $newTitle = 'Ispravljeni poslovni naziv';
        $newDate = now()->subDays(3)->toDateString();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Ispravi podatke objave', false);
        $page->assertSee('Sačuvaj izmjene', false);
        $page->assertSee('value="Stari poslovni naziv"', false);
        $page->assertSee('value="'.now()->subDays(8)->toDateString().'"', false);
        $page->assertSee('Upravljaj objavom', false);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_title' => $newTitle,
                'business_published_on' => $newDate,
            ],
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $copy->refresh();
        $notice->refresh();

        $this->assertSame($copy->id, $copy->getKey());
        $this->assertSame($newTitle, $copy->business_title);
        $this->assertSame($newDate, optional($copy->business_published_on)?->toDateString());
        $this->assertSame($originalPath, $copy->storage_path);
        $this->assertSame($originalBytes, Storage::disk('local')->get($originalPath));

        $this->assertSame($notice->id, Notice::query()->sole()->id);
        $this->assertSame($noticesBefore, Notice::query()->count());
        $this->assertSame($newTitle, $notice->title);
        $this->assertSame($newDate, optional($notice->public_display_date)?->toDateString());
        $this->assertSame($publishedAt, $notice->published_at->toDateTimeString());
        $this->assertSame($sourceType, $notice->source_type);
        $this->assertSame($sourceId, $notice->source_id);
        $this->assertSame($sourceObjectId, $notice->source_object_id);
        $this->assertSame($contentDelivery, $notice->content_delivery);
        $this->assertTrue($notice->publicly_available);
        $this->assertTrue($notice->visible_in_active_panel);

        $audit = CompetitionOfficialDecisionLifecycleEvent::query()->sole();
        $this->assertSame(CompetitionOfficialDecisionLifecycleEvent::ACTION_METADATA_CORRECTED, $audit->action);
        $this->assertSame($copy->id, $audit->competition_official_decision_copy_id);
        $this->assertSame($competition->id, $audit->competition_id);
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertNotNull($audit->created_at);
        $this->assertSame('Stari poslovni naziv', $audit->payload['business_title']['from']);
        $this->assertSame($newTitle, $audit->payload['business_title']['to']);
        $this->assertSame(now()->subDays(8)->toDateString(), $audit->payload['business_published_on']['from']);
        $this->assertSame($newDate, $audit->payload['business_published_on']['to']);
        $this->assertSame($notice->id, $audit->payload['notice_id']);
        $this->assertArrayNotHasKey('pdf', $audit->payload);
        $this->assertStringNotContainsString('METADATA-PDF-BYTES', json_encode($audit->payload));

        try {
            $audit->update(['action' => 'tampered']);
            $this->fail('Lifecycle audit update must be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('KN official decision lifecycle events are append-only.', $exception->getMessage());
        }

        $this->assertSame(
            CompetitionOfficialDecisionLifecycleEvent::ACTION_METADATA_CORRECTED,
            $audit->fresh()->action
        );

        $this->get(route('home'))->assertSee($newTitle, false);
        $this->get(route('home'))->assertDontSee('Stari poslovni naziv', false);
        $this->assertHomePanelShowsBusinessDate($newTitle, $newDate);
        $item = $this->homePanelItemHtml($newTitle);
        $this->assertStringNotContainsString(now()->subDays(8)->format('d.m.Y'), $item);
        $direct = $this->get(route('notices.public-content', $notice));
        $direct->assertOk();
        $this->assertSame('METADATA-PDF-BYTES', $this->servedFileContents($direct));
    }

    public function test_metadata_correction_accepts_today_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy, now()->subDays(4)->toDateString());

        $today = now()->toDateString();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_title' => 'Naziv sa današnjim datumom',
                'business_published_on' => $today,
            ],
        )->assertRedirect(route('admin.competitions.show', $competition));

        $this->assertSame($today, optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertSame($today, optional(Notice::query()->sole()->public_display_date)?->toDateString());
    }

    public function test_admin_cannot_correct_publication_metadata(): void
    {
        $this->assertRoleCannotCorrectMetadata('admin');
    }

    public function test_superadmin_cannot_correct_publication_metadata(): void
    {
        $this->assertRoleCannotCorrectMetadata('superadmin');
    }

    public function test_open_competition_cannot_correct_publication_metadata(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);

        $competition->forceFill(['status' => 'published', 'closed_at' => null])->save();
        $notice = Notice::query()->sole();
        $titleBefore = $notice->title;

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $this->metadataPayload(),
        )->assertForbidden();

        $this->assertSame($titleBefore, $notice->fresh()->title);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_unrelated_copy_cannot_correct_publication_metadata(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');
        $copyA = $this->createCopyWithFile($competitionA);
        $this->publishCopy($admin, $competitionA, $copyA);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competitionB, $copyA]),
            $this->metadataPayload(),
        )->assertNotFound();

        $this->assertSame($copyA->business_title, $copyA->fresh()->business_title);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_permanently_deleted_copy_cannot_correct_publication_metadata(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $copy->forceFill([
            'permanently_deleted_at' => now(),
            'permanently_deleted_by' => $admin->id,
        ])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $this->metadataPayload(),
        )->assertNotFound();

        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertTrue(Notice::query()->sole()->publicly_available);
    }

    public function test_pending_copy_cannot_correct_publication_metadata(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $copy->forceFill(['permanent_delete_pending_at' => now()])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $this->metadataPayload(),
        )->assertNotFound();

        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_metadata_correction_keeps_existing_title_when_title_is_omitted(): void
    {
        $this->assertMetadataPartialUpdate(
            ['business_published_on' => now()->subDay()->toDateString()],
            'Stari poslovni naziv',
            now()->subDay()->toDateString(),
            titleChanged: false,
            dateChanged: true,
        );
    }

    public function test_metadata_correction_keeps_existing_title_when_title_is_blank(): void
    {
        $this->assertMetadataPartialUpdate(
            [
                'business_title' => '   ',
                'business_published_on' => now()->subDay()->toDateString(),
            ],
            'Stari poslovni naziv',
            now()->subDay()->toDateString(),
            titleChanged: false,
            dateChanged: true,
        );
    }

    public function test_metadata_correction_rejects_title_longer_than_255(): void
    {
        $this->assertMetadataValidationRejected([
            'business_title' => str_repeat('a', 256),
            'business_published_on' => now()->toDateString(),
        ], 'business_title');
    }

    public function test_metadata_correction_keeps_existing_date_when_date_is_omitted(): void
    {
        $this->assertMetadataPartialUpdate(
            ['business_title' => 'Validan naziv'],
            'Validan naziv',
            now()->subDays(4)->toDateString(),
            titleChanged: true,
            dateChanged: false,
        );
    }

    public function test_metadata_correction_keeps_existing_date_when_date_is_blank(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $originalDate = now()->subDays(4)->toDateString();
        $this->publishCopy($admin, $competition, $copy, $originalDate);
        $notice = Notice::query()->sole();
        $publishedAt = $notice->published_at->toDateTimeString();

        Event::fake([OfficialContentPublicMetadataUpdated::class]);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_published_on' => '',
            ],
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $this->assertSame('Stari poslovni naziv', $copy->fresh()->business_title);
        $this->assertSame($originalDate, optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertNotNull($copy->fresh()->business_published_on);
        $this->assertSame('Stari poslovni naziv', $notice->fresh()->title);
        $this->assertSame($originalDate, optional($notice->fresh()->public_display_date)?->toDateString());
        $this->assertSame($publishedAt, $notice->fresh()->published_at->toDateTimeString());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        Event::assertNotDispatched(OfficialContentPublicMetadataUpdated::class);
    }

    public function test_metadata_correction_is_noop_when_values_are_unchanged(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $originalDate = now()->subDays(4)->toDateString();
        $this->publishCopy($admin, $competition, $copy, $originalDate);
        $notice = Notice::query()->sole();
        $publishedAt = $notice->published_at->toDateTimeString();

        Event::fake([OfficialContentPublicMetadataUpdated::class]);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_title' => 'Stari poslovni naziv',
                'business_published_on' => $originalDate,
            ],
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $this->assertSame('Stari poslovni naziv', $copy->fresh()->business_title);
        $this->assertSame($originalDate, optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertSame('Stari poslovni naziv', $notice->fresh()->title);
        $this->assertSame($originalDate, optional($notice->fresh()->public_display_date)?->toDateString());
        $this->assertSame($publishedAt, $notice->fresh()->published_at->toDateTimeString());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        Event::assertNotDispatched(OfficialContentPublicMetadataUpdated::class);
    }

    public function test_metadata_correction_title_only_does_not_invent_a_date_change_in_audit(): void
    {
        $this->assertMetadataPartialUpdate(
            ['business_title' => 'Samo novi naziv'],
            'Samo novi naziv',
            now()->subDays(4)->toDateString(),
            titleChanged: true,
            dateChanged: false,
        );
    }

    public function test_metadata_correction_date_only_does_not_invent_a_title_change_in_audit(): void
    {
        $this->assertMetadataPartialUpdate(
            ['business_published_on' => now()->subDay()->toDateString()],
            'Stari poslovni naziv',
            now()->subDay()->toDateString(),
            titleChanged: false,
            dateChanged: true,
        );
    }

    public function test_metadata_correction_rejects_empty_effective_title_when_existing_title_is_empty(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(4)->toDateString());
        $copy->forceFill(['business_title' => ''])->save();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_title' => '   ',
                'business_published_on' => now()->subDay()->toDateString(),
            ],
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('business_title');
        $this->assertSame('', $copy->fresh()->business_title);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_metadata_correction_rejects_future_date(): void
    {
        $this->assertMetadataValidationRejected([
            'business_title' => 'Validan naziv',
            'business_published_on' => now()->addDay()->toDateString(),
        ], 'business_published_on');
    }

    public function test_metadata_correction_rejects_direct_post_future_date(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $this->publishCopy($admin, $competition, $copy);
        $notice = Notice::query()->sole();
        $titleBefore = $notice->title;
        $dateBefore = optional($notice->public_display_date)?->toDateString();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_title' => 'Direktni POST u budućnost',
                'business_published_on' => now()->addDays(2)->toDateString(),
            ],
        );

        $response->assertSessionHasErrors('business_published_on');
        $this->assertSame('Stari poslovni naziv', $copy->fresh()->business_title);
        $this->assertSame($titleBefore, $notice->fresh()->title);
        $this->assertSame($dateBefore, optional($notice->fresh()->public_display_date)?->toDateString());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_channel_failure_rolls_back_kn_metadata_and_audit_without_partial_notice_change(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(6)->toDateString());
        $notice = Notice::query()->sole();
        $titleBefore = $notice->title;
        $dateBefore = optional($notice->public_display_date)?->toDateString();
        $publishedAtBefore = $notice->published_at->toDateTimeString();

        Notice::updating(function () {
            throw new RuntimeException('Forced metadata channel failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
                $this->metadataPayload('Novi naziv koji ne smije ostati', now()->subDay()->toDateString()),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced metadata channel failure', $exception->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $copy->refresh();
        $notice->refresh();
        $this->assertSame('Stari poslovni naziv', $copy->business_title);
        $this->assertSame(now()->subDays(6)->toDateString(), optional($copy->business_published_on)?->toDateString());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame($titleBefore, $notice->title);
        $this->assertSame($dateBefore, optional($notice->public_display_date)?->toDateString());
        $this->assertSame($publishedAtBefore, $notice->published_at->toDateTimeString());
        $this->assertTrue($notice->publicly_available);
        $this->assertTrue($notice->visible_in_active_panel);
    }

    public function test_konkurs_admin_can_unpublish_without_changing_copy_pdf_or_competition(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $competition->forceFill(['budget' => '2500.00'])->save();
        $copy = $this->createCopyWithFile($competition, 'UNPUBLISH-PDF-BYTES', 'Naziv za povlačenje');
        $originalPath = $copy->storage_path;
        $this->publishCopy($admin, $competition, $copy, now()->subDays(2)->toDateString());

        $notice = Notice::query()->sole();
        $copyId = $copy->id;
        $title = $copy->fresh()->business_title;
        $publishedOn = optional($copy->fresh()->business_published_on)?->toDateString();
        $statusBefore = $competition->fresh()->status;
        $closedAtBefore = optional($competition->fresh()->closed_at)?->toDateTimeString();
        $budgetBefore = $competition->fresh()->budget;
        $yearBefore = $competition->fresh()->year;
        $noticesBefore = Notice::query()->count();

        $this->assertHomePanelShowsBusinessDate($title, $publishedOn);
        $this->get(route('notices.public-content', $notice))->assertOk();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Povuci objavu', false);
        $page->assertSee('Da li želite da povučete ovu Odluku iz javne objave?', false);
        $page->assertDontSee('Ponovo objavi', false);
        $page->assertSee('Trajno obriši', false);
        $page->assertDontSee('name="delete_reason"', false);
        $page->assertDontSee('name="reason"', false);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $copy->refresh();
        $notice->refresh();
        $competition->refresh();

        $this->assertSame($copyId, $copy->id);
        $this->assertNotNull(CompetitionOfficialDecisionCopy::query()->find($copyId));
        $this->assertSame($originalPath, $copy->storage_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertSame('UNPUBLISH-PDF-BYTES', Storage::disk('local')->get($originalPath));
        $this->assertSame($title, $copy->business_title);
        $this->assertSame($publishedOn, optional($copy->business_published_on)?->toDateString());
        $this->assertSame($noticesBefore, Notice::query()->count());
        $this->assertSame($notice->id, Notice::query()->sole()->id);
        $this->assertFalse($notice->visible_in_active_panel);
        $this->assertFalse($notice->publicly_available);
        $this->assertSame($statusBefore, $competition->status);
        $this->assertSame($closedAtBefore, optional($competition->closed_at)?->toDateTimeString());
        $this->assertSame($budgetBefore, $competition->budget);
        $this->assertSame($yearBefore, $competition->year);

        $audit = CompetitionOfficialDecisionLifecycleEvent::query()->sole();
        $this->assertSame(CompetitionOfficialDecisionLifecycleEvent::ACTION_UNPUBLISHED, $audit->action);
        $this->assertSame($copy->id, $audit->competition_official_decision_copy_id);
        $this->assertSame($competition->id, $audit->competition_id);
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertNotNull($audit->created_at);
        $this->assertTrue($audit->payload['public_availability_revoked']);
        $this->assertSame($notice->id, $audit->payload['notice_id']);

        $this->get(route('home'))->assertDontSee($title, false);
        $direct = $this->get(route('notices.public-content', $notice));
        $direct->assertNotFound();
        $this->assertStringNotContainsString('UNPUBLISH-PDF-BYTES', $direct->getContent());

        $after = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $after->assertOk();
        $after->assertSee('Objava povučena', false);
        $after->assertSee('Ponovo objavi', false);
        $after->assertSee($title, false);
        $after->assertDontSee('>Objavi Odluku</button>', false);
        $after->assertDontSee('Upravljaj objavom', false);
        $after->assertDontSee('Zamijeni Odluku', false);
        $after->assertDontSee('>Učitaj Odluku</button>', false);
    }

    public function test_admin_cannot_unpublish_signed_copy(): void
    {
        $this->assertRoleCannotUnpublish('admin');
    }

    public function test_superadmin_cannot_unpublish_signed_copy(): void
    {
        $this->assertRoleCannotUnpublish('superadmin');
    }

    public function test_open_competition_cannot_unpublish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $competition->forceFill(['status' => 'published', 'closed_at' => null])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        )->assertForbidden();

        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_unrelated_copy_cannot_unpublish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');
        $copyA = $this->createCopyWithFile($competitionA);
        $this->publishCopy($admin, $competitionA, $copyA);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competitionB, $copyA]),
        )->assertNotFound();

        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_deleted_or_pending_copy_cannot_unpublish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $deletedCompetition = $this->createCompletedCompetition('Konkurs deleted');
        $deleted = $this->createCopyWithFile($deletedCompetition);
        $this->publishCopy($admin, $deletedCompetition, $deleted);
        $deleted->forceFill([
            'permanently_deleted_at' => now(),
            'permanently_deleted_by' => $admin->id,
        ])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$deletedCompetition, $deleted]),
        )->assertNotFound();

        $pendingCompetition = $this->createCompletedCompetition('Konkurs pending');
        $pending = $this->createCopyWithFile($pendingCompetition);
        $this->publishCopy($admin, $pendingCompetition, $pending);
        $pending->forceFill(['permanent_delete_pending_at' => now()])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$pendingCompetition, $pending]),
        )->assertNotFound();

        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertTrue(Notice::query()->where('source_object_id', $deleted->id)->sole()->publicly_available);
        $this->assertTrue(Notice::query()->where('source_object_id', $pending->id)->sole()->publicly_available);
    }

    public function test_unpublished_copy_cannot_be_unpublished(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, Notice::query()->count());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame($copy->storage_path, $copy->fresh()->storage_path);
        $this->assertSame('completed', $competition->fresh()->status);
    }

    public function test_second_unpublish_does_not_write_another_success_audit(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        )->assertRedirect();

        $this->assertSame(1, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame(1, Notice::query()->count());
        $notice = Notice::query()->sole();
        $this->assertFalse($notice->publicly_available);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(1, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame(1, Notice::query()->count());
        $this->assertFalse($notice->fresh()->publicly_available);
        $this->assertSame($copy->storage_path, $copy->fresh()->storage_path);
        $this->assertSame('completed', $competition->fresh()->status);
    }

    public function test_unpublish_channel_failure_rolls_back_kn_success_audit(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $notice = Notice::query()->sole();

        Notice::updating(function () {
            throw new RuntimeException('Forced revoke channel failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced revoke channel failure', $exception->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $notice->refresh();
        $this->assertTrue($notice->publicly_available);
        $this->assertTrue($notice->visible_in_active_panel);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame($copy->business_title, $copy->fresh()->business_title);
    }

    public function test_konkurs_admin_can_republish_the_same_unpublished_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'REPUBLISH-PDF-BYTES', 'Naziv prije ponovne objave');
        $originalPath = $copy->storage_path;
        $originalBytes = Storage::disk('local')->get($originalPath);
        $firstDate = now()->subDays(6)->toDateString();

        $this->travelTo(now()->subMinute());
        $this->publishCopy($admin, $competition, $copy, $firstDate);
        $noticeA = Notice::query()->sole();
        $publishedAtA = $noticeA->published_at->toDateTimeString();
        $this->unpublishCopy($admin, $competition, $copy);

        $newTitle = 'Naziv nakon ponovne objave';
        $newDate = now()->subDays(2)->toDateString();
        $copyId = $copy->id;
        $statusBefore = $competition->fresh()->status;
        $budgetBefore = $competition->fresh()->budget;

        $this->travelTo(now()->addMinute());

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Ponovo objavi', false);
        $page->assertSee('Objava povučena', false);
        $page->assertSee('value="Naziv prije ponovne objave"', false);
        $page->assertDontSee('>Objavi Odluku</button>', false);
        $page->assertDontSee('Ispravi podatke objave', false);
        $page->assertDontSee('Povuci objavu', false);
        $page->assertDontSee('>Učitaj Odluku</button>', false);

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload($newTitle, $newDate),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $copy->refresh();
        $noticeA->refresh();
        $noticeB = Notice::query()->whereKeyNot($noticeA->id)->sole();

        $this->assertSame($copyId, $copy->id);
        $this->assertSame($originalPath, $copy->storage_path);
        $this->assertSame($originalBytes, Storage::disk('local')->get($originalPath));
        $this->assertSame($newTitle, $copy->business_title);
        $this->assertSame($newDate, optional($copy->business_published_on)?->toDateString());
        $this->assertNull($copy->permanently_deleted_at);
        $this->assertNull($copy->permanent_delete_pending_at);

        $this->assertSame(2, Notice::query()->count());
        $this->assertNotSame($noticeA->id, $noticeB->id);
        $this->assertSame($copy->id, $noticeB->source_object_id);
        $this->assertSame($noticeA->source_type, $noticeB->source_type);
        $this->assertSame($noticeA->source_id, $noticeB->source_id);
        $this->assertSame($noticeA->content_delivery, $noticeB->content_delivery);
        $this->assertNull($noticeB->superseded_notice_id);
        $this->assertSame($newTitle, $noticeB->title);
        $this->assertSame($newDate, optional($noticeB->public_display_date)?->toDateString());
        $this->assertNotSame($publishedAtA, $noticeB->published_at->toDateTimeString());
        $this->assertTrue($noticeB->visible_in_active_panel);
        $this->assertTrue($noticeB->publicly_available);

        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertSame($publishedAtA, $noticeA->published_at->toDateTimeString());
        $this->assertSame($copy->id, $noticeA->source_object_id);

        $this->assertSame($statusBefore, $competition->fresh()->status);
        $this->assertSame($budgetBefore, $competition->fresh()->budget);

        $audit = CompetitionOfficialDecisionLifecycleEvent::query()
            ->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)
            ->sole();
        $this->assertSame($copy->id, $audit->competition_official_decision_copy_id);
        $this->assertSame($competition->id, $audit->competition_id);
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertNotNull($audit->created_at);
        $this->assertSame($noticeA->id, $audit->payload['previous_notice_id']);
        $this->assertSame('Naziv prije ponovne objave', $audit->payload['business_title']['from']);
        $this->assertSame($newTitle, $audit->payload['business_title']['to']);
        $this->assertSame($firstDate, $audit->payload['business_published_on']['from']);
        $this->assertSame($newDate, $audit->payload['business_published_on']['to']);
        $this->assertArrayNotHasKey('notice_id', $audit->payload);
        $this->assertArrayNotHasKey('pdf', $audit->payload);

        $this->get(route('home'))->assertSee($newTitle, false);
        $this->get(route('home'))->assertDontSee('Naziv prije ponovne objave', false);
        $this->assertHomePanelShowsBusinessDate($newTitle, $newDate);
        $republishItem = $this->homePanelItemHtml($newTitle);
        $this->assertStringNotContainsString($noticeB->published_at->format('d.m.Y H:i'), $republishItem);
        $businessVisible = \Illuminate\Support\Carbon::parse($newDate)->format('d.m.Y');
        $this->assertStringContainsString($businessVisible, $republishItem);
        if ($noticeB->published_at->format('d.m.Y') !== $businessVisible) {
            $this->assertStringNotContainsString($noticeB->published_at->format('d.m.Y'), $republishItem);
        }

        $newPublic = $this->get(route('notices.public-content', $noticeB));
        $newPublic->assertOk();
        $this->assertSame('REPUBLISH-PDF-BYTES', $this->servedFileContents($newPublic));

        $oldPublic = $this->get(route('notices.public-content', $noticeA));
        $oldPublic->assertNotFound();
        $this->assertStringNotContainsString('REPUBLISH-PDF-BYTES', $oldPublic->getContent());
    }

    public function test_second_republish_cycle_uses_previous_notice_b_not_a(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'CYCLE-PDF-BYTES', 'Naziv ciklusa A');
        $originalPath = $copy->storage_path;
        $originalBytes = Storage::disk('local')->get($originalPath);
        $copyId = $copy->id;
        $dateA = now()->subDays(6)->toDateString();
        $dateB = now()->subDays(3)->toDateString();
        $dateC = now()->subDay()->toDateString();

        $this->publishCopy($admin, $competition, $copy, $dateA);
        $noticeA = Notice::query()->sole();
        $this->unpublishCopy($admin, $competition, $copy);

        $noticeA->refresh();
        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload('Naziv ciklusa B', $dateB),
        )->assertRedirect();

        $noticeB = Notice::query()->whereKeyNot($noticeA->id)->sole();
        $this->unpublishCopy($admin, $competition, $copy);

        $noticeB->refresh();
        $this->assertFalse($noticeB->visible_in_active_panel);
        $this->assertFalse($noticeB->publicly_available);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload('Naziv ciklusa C', $dateC),
        )->assertRedirect();

        $copy->refresh();
        $noticeA->refresh();
        $noticeB->refresh();
        $noticeC = Notice::query()
            ->whereKeyNot($noticeA->id)
            ->whereKeyNot($noticeB->id)
            ->sole();

        $this->assertSame($copyId, $copy->id);
        $this->assertSame(1, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame($originalPath, $copy->storage_path);
        $this->assertSame($originalBytes, Storage::disk('local')->get($originalPath));
        $this->assertSame('Naziv ciklusa C', $copy->business_title);
        $this->assertSame($dateC, optional($copy->business_published_on)?->toDateString());

        $this->assertSame(3, Notice::query()->count());
        $this->assertNotSame($noticeA->id, $noticeB->id);
        $this->assertNotSame($noticeB->id, $noticeC->id);
        $this->assertNotSame($noticeA->id, $noticeC->id);

        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertFalse($noticeB->visible_in_active_panel);
        $this->assertFalse($noticeB->publicly_available);
        $this->assertTrue($noticeC->visible_in_active_panel);
        $this->assertTrue($noticeC->publicly_available);

        foreach ([$noticeA, $noticeB, $noticeC] as $notice) {
            $this->assertSame('competition_decision', $notice->source_type);
            $this->assertSame($competition->id, $notice->source_id);
            $this->assertSame('competition_decision_signed_copy', $notice->content_delivery);
            $this->assertSame($copyId, $notice->source_object_id);
        }

        $this->assertNull($noticeB->superseded_notice_id);
        $this->assertNull($noticeC->superseded_notice_id);
        $this->assertSame('Naziv ciklusa C', $noticeC->title);
        $this->assertSame($dateC, optional($noticeC->public_display_date)?->toDateString());

        $republishAudits = CompetitionOfficialDecisionLifecycleEvent::query()
            ->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)
            ->where('competition_official_decision_copy_id', $copyId)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $republishAudits);

        $auditB = $republishAudits[0];
        $this->assertSame($noticeA->id, $auditB->payload['previous_notice_id']);
        $this->assertSame('Naziv ciklusa A', $auditB->payload['business_title']['from']);
        $this->assertSame('Naziv ciklusa B', $auditB->payload['business_title']['to']);
        $this->assertSame($dateA, $auditB->payload['business_published_on']['from']);
        $this->assertSame($dateB, $auditB->payload['business_published_on']['to']);

        $auditC = $republishAudits[1];
        $this->assertSame($noticeB->id, $auditC->payload['previous_notice_id']);
        $this->assertNotSame($noticeA->id, $auditC->payload['previous_notice_id']);
        $this->assertSame('Naziv ciklusa B', $auditC->payload['business_title']['from']);
        $this->assertSame('Naziv ciklusa C', $auditC->payload['business_title']['to']);
        $this->assertSame($dateB, $auditC->payload['business_published_on']['from']);
        $this->assertSame($dateC, $auditC->payload['business_published_on']['to']);
    }

    public function test_republish_revokes_leftover_legacy_html(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'REPUBLISH-PDF-BYTES', 'Naziv A');

        $this->publishCopy($admin, $competition, $copy, now()->subDays(5)->toDateString());
        $this->unpublishCopy($admin, $competition, $copy);
        $noticeA = Notice::query()->where('source_object_id', $copy->id)->sole();
        $html = Notice::factory()->create([
            'title' => 'Legacy HTML Odluka',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => null,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);
        $originalPath = $copy->storage_path;
        $originalBytes = Storage::disk('local')->get($originalPath);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload('Naziv B', now()->subDay()->toDateString()),
        )->assertRedirect();

        $html->refresh();
        $noticeA->refresh();
        $noticeB = Notice::query()
            ->where('source_object_id', $copy->id)
            ->whereKeyNot($noticeA->id)
            ->sole();

        $this->assertFalse($html->visible_in_active_panel);
        $this->assertFalse($html->publicly_available);
        $this->assertFalse($noticeA->visible_in_active_panel);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertTrue($noticeB->visible_in_active_panel);
        $this->assertTrue($noticeB->publicly_available);
        $this->assertNull($noticeB->superseded_notice_id);
        $this->assertSame($copy->id, $noticeB->source_object_id);
        $this->assertSame($originalPath, $copy->fresh()->storage_path);
        $this->assertSame($originalBytes, Storage::disk('local')->get($originalPath));

        auth()->logout();
        $this->get(route('notices.public-content', $html))->assertNotFound();
        $this->get(route('notices.public-content', $noticeA))->assertNotFound();
        $this->get(route('notices.public-content', $noticeB))->assertOk();
        $this->assertSame('REPUBLISH-PDF-BYTES', $this->servedFileContents(
            $this->get(route('notices.public-content', $noticeB))
        ));
    }

    public function test_republish_dispatches_existing_publish_event_without_supersession(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(4)->toDateString());
        $this->unpublishCopy($admin, $competition, $copy);

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload('Novi naziv', now()->subDay()->toDateString()),
        )->assertRedirect();

        Event::assertDispatched(OfficialContentReadyForPublicPublication::class, function ($event) use ($copy) {
            return $event->title === 'Novi naziv'
                && $event->source_type === 'competition_decision'
                && $event->source_id === $copy->competition_id
                && $event->content_delivery === 'competition_decision_signed_copy'
                && $event->source_object_id === $copy->id
                && $event->public_revoke === false
                && $event->supersedes_notice_id === null
                && $event->public_display_date === now()->subDay()->toDateString();
        });
    }

    public function test_currently_published_copy_cannot_republish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Objavljeni naziv');
        $this->publishCopy($admin, $competition, $copy);

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Povuci objavu', false);
        $page->assertDontSee('Ponovo objavi', false);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(1, Notice::query()->count());
        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
    }

    public function test_never_published_copy_cannot_republish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Još neobjavljen naziv');

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('>Objavi Odluku</button>', false);
        $page->assertDontSee('Ponovo objavi', false);
        $page->assertDontSee(route('admin.competitions.official-decision.republish', [$competition, $copy]), false);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, Notice::query()->count());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame('Još neobjavljen naziv', $copy->fresh()->business_title);
        $this->assertNull($copy->fresh()->business_published_on);
    }

    public function test_republish_future_business_date_is_rejected(): void
    {
        $this->assertRepublishValidationRejected(
            ['business_title' => 'Validan naziv', 'business_published_on' => now()->addDay()->toDateString()],
            'business_published_on',
        );
    }

    public function test_republish_whitespace_only_title_is_rejected(): void
    {
        $this->assertRepublishValidationRejected(
            ['business_title' => '   ', 'business_published_on' => now()->subDay()->toDateString()],
            'business_title',
        );
    }

    public function test_deleted_or_pending_copy_cannot_republish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        $deletedCompetition = $this->createCompletedCompetition('Konkurs deleted republish');
        $deleted = $this->createCopyWithFile($deletedCompetition, 'SIGNED-COPY-BYTES', 'Deleted naziv');
        $this->publishCopy($admin, $deletedCompetition, $deleted);
        $this->unpublishCopy($admin, $deletedCompetition, $deleted);
        $deleted->forceFill([
            'permanently_deleted_at' => now(),
            'permanently_deleted_by' => $admin->id,
        ])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$deletedCompetition, $deleted]),
            $this->republishPayload(),
        )->assertNotFound();

        $pendingCompetition = $this->createCompletedCompetition('Konkurs pending republish');
        $pending = $this->createCopyWithFile($pendingCompetition, 'SIGNED-COPY-BYTES', 'Pending naziv');
        $this->publishCopy($admin, $pendingCompetition, $pending);
        $this->unpublishCopy($admin, $pendingCompetition, $pending);
        $pending->forceFill(['permanent_delete_pending_at' => now()])->save();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$pendingCompetition, $pending]),
            $this->republishPayload(),
        )->assertNotFound();

        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
        $this->assertSame(1, Notice::query()->where('source_object_id', $deleted->id)->count());
        $this->assertSame(1, Notice::query()->where('source_object_id', $pending->id)->count());
        $this->assertFalse(Notice::query()->where('source_object_id', $deleted->id)->sole()->publicly_available);
        $this->assertFalse(Notice::query()->where('source_object_id', $pending->id)->sole()->publicly_available);
        $this->assertSame('Deleted naziv', $deleted->fresh()->business_title);
        $this->assertSame('Pending naziv', $pending->fresh()->business_title);
    }

    public function test_other_current_public_copy_blocks_republish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Copy A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Copy B');
        $this->publishCopy($admin, $competition, $copyA);
        $this->unpublishCopy($admin, $competition, $copyA);
        $this->forceCurrentPublicSignedCopyNotice($competition, $copyB);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.republish', [$competition, $copyA]),
            $this->republishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(1, Notice::query()->where('source_object_id', $copyA->id)->count());
        $this->assertFalse(Notice::query()->where('source_object_id', $copyA->id)->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
        $this->assertSame('Copy A', $copyA->fresh()->business_title);
    }

    public function test_multiple_current_public_signed_copy_notices_block_republish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-BYTES', 'Copy A');
        $copyB = $this->createCopyWithFile($competition, 'COPY-B-BYTES', 'Copy B');
        $copyC = $this->createCopyWithFile($competition, 'COPY-C-BYTES', 'Copy C');
        $this->publishCopy($admin, $competition, $copyA);
        $this->unpublishCopy($admin, $competition, $copyA);
        $this->forceCurrentPublicSignedCopyNotice($competition, $copyB);
        $this->forceCurrentPublicSignedCopyNotice($competition, $copyC);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.republish', [$competition, $copyA]),
            $this->republishPayload(),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertSame(1, Notice::query()->where('source_object_id', $copyA->id)->count());
        $this->assertFalse(Notice::query()->where('source_object_id', $copyA->id)->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
    }

    public function test_missing_pdf_cannot_republish(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $this->unpublishCopy($admin, $competition, $copy);
        Storage::disk('local')->delete($copy->storage_path);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $this->republishPayload(),
        )->assertNotFound();

        $this->assertSame(1, Notice::query()->count());
        $this->assertFalse(Notice::query()->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
    }

    public function test_republish_channel_failure_rolls_back_kn_metadata_and_audit(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(5)->toDateString());
        $this->unpublishCopy($admin, $competition, $copy);
        $noticeA = Notice::query()->sole();
        $titleBefore = $copy->fresh()->business_title;
        $dateBefore = optional($copy->fresh()->business_published_on)?->toDateString();
        $auditsBefore = CompetitionOfficialDecisionLifecycleEvent::query()->count();

        Notice::creating(function () {
            throw new RuntimeException('Forced republish channel failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                route('admin.competitions.official-decision.republish', [$competition, $copy]),
                $this->republishPayload('Novi naziv', now()->subDay()->toDateString()),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced republish channel failure', $exception->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $copy->refresh();
        $noticeA->refresh();
        $this->assertSame($titleBefore, $copy->business_title);
        $this->assertSame($dateBefore, optional($copy->business_published_on)?->toDateString());
        $this->assertSame($auditsBefore, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame($noticeA->id, Notice::query()->sole()->id);
        $this->assertFalse($noticeA->publicly_available);
        $this->assertFalse($noticeA->visible_in_active_panel);
    }

    public function test_kn_controller_does_not_call_channel_primitives_directly(): void
    {
        $source = file_get_contents((new ReflectionClass(CompetitionOfficialDecisionController::class))->getFileName());

        $this->assertStringNotContainsString('updatePublicMetadata', $source);
        $this->assertStringNotContainsString('revokePublicAvailability', $source);
        $this->assertStringNotContainsString('NoticePublicationService', $source);
        $this->assertStringContainsString('OfficialContentPublicMetadataUpdated', $source);
        $this->assertStringContainsString('OfficialContentPublicAvailabilityRevoked', $source);
    }

    public function test_metadata_and_unpublish_do_not_dispatch_the_publish_event(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);

        Event::fake([OfficialContentReadyForPublicPublication::class]);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $this->metadataPayload(),
        )->assertRedirect();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        )->assertRedirect();

        Event::assertNotDispatched(OfficialContentReadyForPublicPublication::class);
        $this->assertSame('Ispravljeni naziv', Notice::query()->sole()->title);
        $this->assertFalse(Notice::query()->sole()->publicly_available);
    }

    private function assertMetadataPartialUpdate(
        array $payload,
        string $expectedTitle,
        string $expectedDate,
        bool $titleChanged,
        bool $dateChanged,
    ): void {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $originalDate = now()->subDays(4)->toDateString();
        $this->publishCopy($admin, $competition, $copy, $originalDate);
        $notice = Notice::query()->sole();
        $publishedAt = $notice->published_at->toDateTimeString();

        $response = $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $payload,
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $copy->refresh();
        $notice->refresh();
        $this->assertSame($expectedTitle, $copy->business_title);
        $this->assertSame($expectedDate, optional($copy->business_published_on)?->toDateString());
        $this->assertSame($expectedTitle, $notice->title);
        $this->assertSame($expectedDate, optional($notice->public_display_date)?->toDateString());
        $this->assertSame($publishedAt, $notice->published_at->toDateTimeString());
        $this->assertTrue($notice->publicly_available);

        $audit = CompetitionOfficialDecisionLifecycleEvent::query()->sole();
        $this->assertSame(CompetitionOfficialDecisionLifecycleEvent::ACTION_METADATA_CORRECTED, $audit->action);
        $this->assertSame('Stari poslovni naziv', $audit->payload['business_title']['from']);
        $this->assertSame($expectedTitle, $audit->payload['business_title']['to']);
        $this->assertSame($originalDate, $audit->payload['business_published_on']['from']);
        $this->assertSame($expectedDate, $audit->payload['business_published_on']['to']);
        $this->assertSame($titleChanged, $audit->payload['business_title']['from'] !== $audit->payload['business_title']['to']);
        $this->assertSame($dateChanged, $audit->payload['business_published_on']['from'] !== $audit->payload['business_published_on']['to']);
        $this->assertSame($notice->id, $audit->payload['notice_id']);
    }

    private function assertMetadataValidationRejected(array $payload, string $errorKey): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(4)->toDateString());
        $notice = Notice::query()->sole();
        $titleBefore = $notice->title;
        $dateBefore = optional($notice->public_display_date)?->toDateString();
        $copyTitleBefore = $copy->fresh()->business_title;
        $copyDateBefore = optional($copy->fresh()->business_published_on)?->toDateString();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $payload,
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors($errorKey);
        $this->assertSame($copyTitleBefore, $copy->fresh()->business_title);
        $this->assertSame($copyDateBefore, optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertSame($titleBefore, $notice->fresh()->title);
        $this->assertSame($dateBefore, optional($notice->fresh()->public_display_date)?->toDateString());
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame(1, Notice::query()->count());
    }

    private function assertRoleCannotCorrectMetadata(string $roleName): void
    {
        $publisher = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $this->publishCopy($publisher, $competition, $copy);
        $notice = Notice::query()->sole();

        $user = $this->userWithRole($roleName);
        $response = $this->actingAs($user)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            $this->metadataPayload(),
        );

        $response->assertForbidden();
        $this->assertSame('Stari poslovni naziv', $copy->fresh()->business_title);
        $this->assertSame('Stari poslovni naziv', $notice->fresh()->title);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    private function assertRoleCannotUnpublish(string $roleName): void
    {
        $publisher = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($publisher, $competition, $copy);
        $notice = Notice::query()->sole();

        $user = $this->userWithRole($roleName);
        $response = $this->actingAs($user)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        );

        $response->assertForbidden();
        $this->assertTrue($notice->fresh()->publicly_available);
        $this->assertTrue($notice->fresh()->visible_in_active_panel);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
        $this->assertSame(1, Notice::query()->count());
    }

    private function unpublishCopy(
        User $admin,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): void {
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        )->assertRedirect();
    }

    private function republishPayload(
        string $title = 'Naziv nakon ponovne objave',
        ?string $date = null,
    ): array {
        return [
            'business_title' => $title,
            'business_published_on' => $date ?? now()->subDay()->toDateString(),
        ];
    }

    private function assertRepublishValidationRejected(array $payload, string $errorKey): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-COPY-BYTES', 'Stari poslovni naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(4)->toDateString());
        $this->unpublishCopy($admin, $competition, $copy);
        $notice = Notice::query()->sole();
        $copyTitleBefore = $copy->fresh()->business_title;
        $copyDateBefore = optional($copy->fresh()->business_published_on)?->toDateString();

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            $payload,
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors($errorKey);
        $this->assertSame($copyTitleBefore, $copy->fresh()->business_title);
        $this->assertSame($copyDateBefore, optional($copy->fresh()->business_published_on)?->toDateString());
        $this->assertSame(1, Notice::query()->count());
        $this->assertFalse($notice->fresh()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->where('action', CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED)->count());
    }

    private function forceCurrentPublicSignedCopyNotice(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): Notice {
        return Notice::factory()->create([
            'title' => 'Forced current public notice',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'published_at' => now(),
        ]);
    }

    private function publishCopy(
        User $admin,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
        ?string $date = null,
    ): void {
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            $this->firstPublishPayload($date),
        )->assertRedirect();
    }

    private function metadataPayload(
        string $title = 'Ispravljeni naziv',
        ?string $date = null,
    ): array {
        return [
            'business_title' => $title,
            'business_published_on' => $date ?? now()->subDay()->toDateString(),
        ];
    }

    private function firstPublishPayload(?string $date = null): array
    {
        return [
            'business_published_on' => $date ?? now()->toDateString(),
        ];
    }

    private function servedFileContents($response): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        return file_get_contents($response->baseResponse->getFile()->getPathname());
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

    private function createCompletedCompetition(string $title = 'Konkurs za lifecycle akcije'): Competition
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

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
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
}
