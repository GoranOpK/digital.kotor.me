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
        $page->assertSee('PDF se ne mijenja', false);
        $page->assertSee('value="Stari poslovni naziv"', false);

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

    public function test_metadata_correction_rejects_missing_title(): void
    {
        $this->assertMetadataValidationRejected(['business_published_on' => now()->toDateString()], 'business_title');
    }

    public function test_metadata_correction_rejects_whitespace_title(): void
    {
        $this->assertMetadataValidationRejected([
            'business_title' => '   ',
            'business_published_on' => now()->toDateString(),
        ], 'business_title');
    }

    public function test_metadata_correction_rejects_title_longer_than_255(): void
    {
        $this->assertMetadataValidationRejected([
            'business_title' => str_repeat('a', 256),
            'business_published_on' => now()->toDateString(),
        ], 'business_title');
    }

    public function test_metadata_correction_rejects_missing_date(): void
    {
        $this->assertMetadataValidationRejected(['business_title' => 'Validan naziv'], 'business_published_on');
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

        $this->get(route('home'))->assertSee($title, false);
        $this->get(route('notices.public-content', $notice))->assertOk();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Povuci objavu', false);
        $page->assertDontSee('Ponovo objavi', false);
        $page->assertDontSee('Trajno obriši', false);

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
}
