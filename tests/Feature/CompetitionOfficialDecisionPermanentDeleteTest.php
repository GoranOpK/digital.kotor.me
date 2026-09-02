<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CompetitionOfficialDecisionController;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\CompetitionOfficialDecisionLifecycleEvent;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class CompetitionOfficialDecisionPermanentDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_konkurs_admin_can_permanently_delete_current_published_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $competition->forceFill(['budget' => '1800.00'])->save();
        $copy = $this->createCopyWithFile($competition, 'DELETE-PDF-BYTES', 'Naziv za trajno brisanje');
        $originalPath = $copy->storage_path;
        $publishedOn = now()->subDays(4)->toDateString();
        $this->publishCopy($admin, $competition, $copy, $publishedOn);

        $notice = Notice::query()->sole();
        $copyId = $copy->id;
        $statusBefore = $competition->fresh()->status;
        $closedAtBefore = optional($competition->fresh()->closed_at)?->toDateTimeString();
        $budgetBefore = $competition->fresh()->budget;
        $yearBefore = $competition->fresh()->year;
        $noticeCountBefore = Notice::query()->count();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertOk();
        $page->assertSee('Trajno obriši', false);
        $page->assertSee('Ova radnja je nepovratna', false);
        $page->assertDontSee('name="delete_reason"', false);
        $page->assertDontSee('name="reason"', false);
        $page->assertDontSee('name="comment"', false);

        $this->actingAs($admin)->get(
            route('admin.competitions.official-decision.permanent-delete', [$competition, $copy]),
        )->assertStatus(405);

        $directBefore = $this->get(route('notices.public-content', $notice));
        $directBefore->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $directBefore->baseResponse);
        $this->assertSame('DELETE-PDF-BYTES', $this->servedFileContents($directBefore));

        $response = $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHas('success');

        $copy->refresh();
        $notice->refresh();
        $competition->refresh();

        $this->assertNotNull(CompetitionOfficialDecisionCopy::query()->find($copyId));
        $this->assertNull($copy->storage_path);
        $this->assertNotNull($copy->permanently_deleted_at);
        $this->assertSame($admin->id, $copy->permanently_deleted_by);
        $this->assertNull($copy->permanent_delete_pending_at);
        $this->assertTrue($copy->hasBeenPublished());
        $this->assertTrue(CompetitionOfficialDecisionCopy::competitionHasPublishedSignedCopy($competition->id));
        $this->assertFalse(CompetitionOfficialDecisionCopy::competitionHasNonTombstonedSignedCopyPublication($competition->id));
        $this->assertFalse(Storage::disk('local')->exists($originalPath));
        Storage::disk('local')->assertMissing($originalPath);
        $this->assertSame($noticeCountBefore, Notice::query()->count());
        $this->assertFalse($notice->publicly_available);
        $this->assertFalse($notice->visible_in_active_panel);
        $this->assertSame($statusBefore, $competition->status);
        $this->assertSame($closedAtBefore, optional($competition->closed_at)?->toDateTimeString());
        $this->assertSame($budgetBefore, $competition->budget);
        $this->assertSame($yearBefore, $competition->year);

        $started = $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED);
        $completed = $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED);
        $this->assertCount(1, $started);
        $this->assertCount(1, $completed);
        $this->assertSame($copy->id, $started[0]->competition_official_decision_copy_id);
        $this->assertSame($competition->id, $started[0]->competition_id);
        $this->assertSame($admin->id, $started[0]->actor_user_id);
        $this->assertSame('Naziv za trajno brisanje', $started[0]->payload['business_title']);
        $this->assertSame($publishedOn, $started[0]->payload['business_published_on']);
        $this->assertSame($notice->id, $started[0]->payload['notice_id']);
        $this->assertArrayNotHasKey('pdf', $started[0]->payload);
        $this->assertSame('Naziv za trajno brisanje', $completed[0]->payload['business_title']);
        $this->assertSame($publishedOn, $completed[0]->payload['business_published_on']);
        $this->assertSame($notice->id, $completed[0]->payload['notice_id']);
        $this->assertSame($admin->id, $completed[0]->actor_user_id);
        $this->assertNotNull($completed[0]->created_at);
        $this->assertStringNotContainsString('DELETE-PDF-BYTES', json_encode($started[0]->payload));
        $this->assertStringNotContainsString('DELETE-PDF-BYTES', json_encode($completed[0]->payload));

        $this->get(route('home'))->assertDontSee('Naziv za trajno brisanje', false);
        $directAfter = $this->get(route('notices.public-content', $notice));
        $directAfter->assertNotFound();
        $this->assertStringNotContainsString('DELETE-PDF-BYTES', $directAfter->getContent());

        $tombstonePage = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $tombstonePage->assertOk();
        $tombstonePage->assertSee('Trajno obrisan', false);
        $tombstonePage->assertSee('Naziv za trajno brisanje', false);
        $tombstonePage->assertDontSee('Trajno obriši', false);
        $tombstonePage->assertDontSee('Ponovi trajno brisanje', false);
        $tombstonePage->assertDontSee('>Objavi</button>', false);
        $tombstonePage->assertDontSee('Ponovo objavi', false);
        $tombstonePage->assertDontSee('Koriguj objavu', false);
        $tombstonePage->assertDontSee('Ispravi podatke objave', false);
        $tombstonePage->assertDontSee('Povuci objavu', false);
        $tombstonePage->assertDontSee($originalPath, false);
    }

    public function test_withdrawn_historical_published_copy_can_be_permanently_deleted(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'WITHDRAWN-PDF-BYTES', 'Povučeni naziv');
        $this->publishCopy($admin, $competition, $copy, now()->subDays(3)->toDateString());
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        )->assertRedirect();

        $notice = Notice::query()->sole();
        $originalPath = $copy->fresh()->storage_path;

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertSee('Trajno obriši', false);
        $page->assertSee('Ponovo objavi', false);

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $copy->refresh();
        $notice->refresh();

        $this->assertNull($copy->storage_path);
        $this->assertNotNull($copy->permanently_deleted_at);
        $this->assertFalse(Storage::disk('local')->exists($originalPath));
        $this->assertFalse($notice->publicly_available);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
        $this->assertSame($notice->id, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED)[0]->payload['notice_id']);
        $this->get(route('notices.public-content', $notice))->assertNotFound();
    }

    public function test_never_published_copy_cannot_be_permanently_deleted(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'NEVER-PUBLISHED-BYTES', 'Neobjavljeni naziv');
        $originalPath = $copy->storage_path;

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertSee('>Objavi</button>', false);
        $page->assertDontSee('Trajno obriši', false);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            $this->permanentDeleteRoute($competition, $copy),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertNull($copy->fresh()->permanently_deleted_at);
        $this->assertNull($copy->fresh()->permanent_delete_pending_at);
        $this->assertSame($originalPath, $copy->fresh()->storage_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_permanent_delete_revokes_leftover_html_and_direct_url(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'SIGNED-DELETE-BYTES', 'Naziv signed');
        $html = $this->legacyHtmlNotice($competition);
        $this->publishCopy($admin, $competition, $copy);
        $signed = Notice::query()->where('source_object_id', $copy->id)->sole();

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertRedirect();

        $html->refresh();
        $signed->refresh();

        $this->assertFalse($html->visible_in_active_panel);
        $this->assertFalse($html->publicly_available);
        $this->assertFalse($signed->publicly_available);
        $this->assertFalse(
            CompetitionOfficialDecisionCopy::leftoverDecisionHtmlNoticesQuery($competition->id)->exists()
        );
        $this->get(route('notices.public-content', $html))->assertNotFound();
        $this->get(route('notices.public-content', $signed))->assertNotFound();
    }

    public function test_t1_channel_failure_rolls_back_pending_audit_and_keeps_pdf(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'ATOMIC-PDF-BYTES', 'Naziv atomic');
        $originalPath = $copy->storage_path;
        $this->publishCopy($admin, $competition, $copy);
        $notice = Notice::query()->sole();

        Notice::updating(function () {
            throw new RuntimeException('Forced T1 revoke failure');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                $this->permanentDeleteRoute($competition, $copy),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced T1 revoke failure', $exception->getMessage());
        } finally {
            Notice::flushEventListeners();
        }

        $copy->refresh();
        $notice->refresh();

        $this->assertNull($copy->permanent_delete_pending_at);
        $this->assertNull($copy->permanently_deleted_at);
        $this->assertSame($originalPath, $copy->storage_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertTrue($notice->publicly_available);
        $this->assertTrue($notice->visible_in_active_panel);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_file_delete_failure_leaves_pending_without_completed_and_url_is_404(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'STICKY-PDF-BYTES', 'Naziv sticky');
        $originalPath = $copy->storage_path;
        $this->publishCopy($admin, $competition, $copy);
        $notice = Notice::query()->sole();

        $this->app->bind(CompetitionOfficialDecisionController::class, function () {
            return new class extends CompetitionOfficialDecisionController
            {
                protected function deleteOfficialDecisionStoredPdf(?string $storagePath): bool
                {
                    return false;
                }
            };
        });

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            $this->permanentDeleteRoute($competition, $copy),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');

        $copy->refresh();
        $notice->refresh();

        $this->assertNotNull($copy->permanent_delete_pending_at);
        $this->assertNull($copy->permanently_deleted_at);
        $this->assertSame($originalPath, $copy->storage_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertFalse($notice->publicly_available);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(0, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
        $this->get(route('notices.public-content', $notice))->assertNotFound();

        $pendingPage = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $pendingPage->assertSee('Trajno brisanje je u toku', false);
        $pendingPage->assertSee('Ponovi trajno brisanje', false);
        $pendingPage->assertDontSee('Objavljeno', false);

        $this->app->bind(CompetitionOfficialDecisionController::class, CompetitionOfficialDecisionController::class);
    }

    public function test_retry_when_pending_and_file_already_absent(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'ABSENT-PDF-BYTES', 'Naziv absent');
        $this->publishCopy($admin, $competition, $copy);
        $this->simulatePendingAfterT1($admin, $competition, $copy);
        $originalPath = $copy->fresh()->storage_path;
        Storage::disk('local')->delete($originalPath);
        $this->assertFalse(Storage::disk('local')->exists($originalPath));

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $copy->refresh();
        $this->assertNull($copy->storage_path);
        $this->assertNotNull($copy->permanently_deleted_at);
        $this->assertNull($copy->permanent_delete_pending_at);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
    }

    public function test_retry_when_pending_and_storage_path_already_null(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'NULL-PATH-BYTES', 'Naziv null path');
        $this->publishCopy($admin, $competition, $copy);
        $this->simulatePendingAfterT1($admin, $competition, $copy);
        Storage::disk('local')->delete($copy->fresh()->storage_path);
        $copy->forceFill(['storage_path' => null])->save();

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $copy->refresh();
        $this->assertNull($copy->storage_path);
        $this->assertNotNull($copy->permanently_deleted_at);
        $this->assertSame($admin->id, $copy->permanently_deleted_by);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
    }

    public function test_t2_failure_after_physical_absence_can_be_retried(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'T2-FAIL-BYTES', 'Naziv T2');
        $this->publishCopy($admin, $competition, $copy);
        $this->simulatePendingAfterT1($admin, $competition, $copy);
        $originalPath = $copy->fresh()->storage_path;
        Storage::disk('local')->delete($originalPath);

        CompetitionOfficialDecisionLifecycleEvent::creating(function ($event) {
            if ($event->action === CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED) {
                throw new RuntimeException('Forced T2 failure');
            }
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post(
                $this->permanentDeleteRoute($competition, $copy),
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Expected RuntimeException was not thrown.') {
                throw $exception;
            }
            $this->assertSame('Forced T2 failure', $exception->getMessage());
        } finally {
            CompetitionOfficialDecisionLifecycleEvent::flushEventListeners();
        }

        $copy->refresh();
        $this->assertNotNull($copy->permanent_delete_pending_at);
        $this->assertNull($copy->permanently_deleted_at);
        $this->assertSame($originalPath, $copy->storage_path);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(0, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertRedirect(route('admin.competitions.show', $competition));

        $copy->refresh();
        $this->assertNull($copy->permanent_delete_pending_at);
        $this->assertNotNull($copy->permanently_deleted_at);
        $this->assertNull($copy->storage_path);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
    }

    public function test_completed_duplicate_request_does_not_create_duplicate_audit(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copy))->assertRedirect();

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertNotFound();

        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
        $this->assertNotNull(CompetitionOfficialDecisionCopy::query()->find($copy->id));
    }

    public function test_tombstoned_copy_refuses_all_lifecycle_actions(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'TOMBSTONE-BYTES', 'Naziv tombstone');
        $this->publishCopy($admin, $competition, $copy);
        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copy))->assertRedirect();
        $copy->refresh();
        $this->assertTrue($copy->hasBeenPublished());

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            ['business_published_on' => now()->toDateString()],
        )->assertNotFound();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.republish', [$competition, $copy]),
            [
                'business_title' => 'Nova',
                'business_published_on' => now()->toDateString(),
            ],
        )->assertNotFound();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.update-metadata', [$competition, $copy]),
            [
                'business_title' => 'Nova',
                'business_published_on' => now()->toDateString(),
            ],
        )->assertNotFound();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copy]),
        )->assertNotFound();

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.correct', [$competition, $copy]),
            ['business_published_on' => now()->toDateString()],
        )->assertNotFound();

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertNotFound();

        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
        $this->assertTrue($copy->fresh()->hasBeenPublished());
    }

    public function test_new_copy_can_be_uploaded_and_published_after_completed_delete(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'COPY-A-DELETE-BYTES', 'Naziv A');
        $dateA = now()->subDays(7)->toDateString();
        $this->publishCopy($admin, $competition, $copyA, $dateA);
        $noticeA = Notice::query()->where('source_object_id', $copyA->id)->sole();

        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copyA))->assertRedirect();
        $copyA->refresh();
        $this->assertTrue($copyA->hasBeenPublished());
        $this->assertNotNull($copyA->permanently_deleted_at);

        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.store', $competition),
            [
                'official_decision_copy' => UploadedFile::fake()->create('odluka-b.pdf', 120, 'application/pdf'),
                'business_title' => 'Naziv B',
            ],
        )->assertRedirect(route('admin.competitions.show', $competition));

        $copyB = CompetitionOfficialDecisionCopy::query()
            ->where('competition_id', $competition->id)
            ->whereNull('permanently_deleted_at')
            ->orderByDesc('id')
            ->firstOrFail();

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertSee('>Objavi</button>', false);
        $page->assertSee(route('admin.competitions.official-decision.publish', [$competition, $copyB]), false);

        $dateB = now()->subDay()->toDateString();
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyB]),
            ['business_published_on' => $dateB],
        )->assertRedirect(route('admin.competitions.show', $competition));

        $noticeB = Notice::query()->where('source_object_id', $copyB->id)->sole();
        $this->assertTrue($noticeB->publicly_available);
        $this->assertSame('Naziv B', $noticeB->title);
        $this->assertSame($dateB, optional($noticeB->public_display_date)?->toDateString());
        $this->assertFalse($noticeA->fresh()->publicly_available);

        $actions = CompetitionOfficialDecisionLifecycleEvent::query()
            ->where('competition_official_decision_copy_id', $copyA->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();
        $this->assertSame([
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED,
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED,
        ], $actions);

        $this->assertTrue($copyA->fresh()->hasBeenPublished());
        $this->assertTrue($copyB->fresh()->hasBeenPublished());
        $this->assertNotNull(Notice::query()->where('source_object_id', $copyA->id)->first());
        $this->assertTrue(CompetitionOfficialDecisionCopy::competitionHasNonTombstonedSignedCopyPublication($competition->id));

        $copyC = $this->createCopyWithFile($competition, 'COPY-C-BYTES', 'Naziv C');
        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyC]),
            ['business_published_on' => now()->toDateString()],
        );
        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertFalse($copyC->fresh()->hasBeenPublished());
        $this->assertTrue($noticeB->fresh()->publicly_available);
    }

    public function test_pending_copy_blocks_first_publish_of_another_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'A-BYTES', 'Naziv A');
        $this->publishCopy($admin, $competition, $copyA);
        $this->simulatePendingAfterT1($admin, $competition, $copyA);
        $copyB = $this->createCopyWithFile($competition, 'B-BYTES', 'Naziv B');

        $page = $this->actingAs($admin)->get(route('admin.competitions.show', $competition));
        $page->assertDontSee(route('admin.competitions.official-decision.publish', [$competition, $copyB]), false);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyB]),
            ['business_published_on' => now()->toDateString()],
        );
        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
    }

    public function test_withdrawn_non_deleted_copy_blocks_first_publish_of_another_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copyA = $this->createCopyWithFile($competition, 'A-BYTES', 'Naziv A');
        $this->publishCopy($admin, $competition, $copyA);
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.unpublish', [$competition, $copyA]),
        )->assertRedirect();
        $copyB = $this->createCopyWithFile($competition, 'B-BYTES', 'Naziv B');

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            route('admin.competitions.official-decision.publish', [$competition, $copyB]),
            ['business_published_on' => now()->toDateString()],
        );
        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertFalse($copyB->fresh()->hasBeenPublished());
        $this->assertNull($copyA->fresh()->permanently_deleted_at);
        $this->assertTrue($copyA->fresh()->hasBeenPublished());
    }

    public function test_multiple_current_signed_copy_notices_refuse_without_healing(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'MULTI-BYTES', 'Naziv multi');
        $originalPath = $copy->storage_path;
        $this->publishCopy($admin, $competition, $copy);
        Notice::factory()->create([
            'title' => 'Second current',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            $this->permanentDeleteRoute($competition, $copy),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertNull($copy->fresh()->permanent_delete_pending_at);
        $this->assertNull($copy->fresh()->permanently_deleted_at);
        $this->assertSame($originalPath, $copy->fresh()->storage_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->whereIn('action', [
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED,
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED,
        ])->count());
        $this->assertSame(2, CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id)->count());
    }

    public function test_pending_retry_refuses_when_current_public_signed_copy_exists(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'PENDING-PUBLIC-BYTES', 'Naziv pending public');
        $this->publishCopy($admin, $competition, $copy);
        $this->simulatePendingAfterT1($admin, $competition, $copy);
        Notice::query()->where('source_object_id', $copy->id)->update([
            'publicly_available' => true,
            'visible_in_active_panel' => true,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.competitions.show', $competition))->post(
            $this->permanentDeleteRoute($competition, $copy),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasErrors('error');
        $this->assertNotNull($copy->fresh()->permanent_delete_pending_at);
        $this->assertNull($copy->fresh()->permanently_deleted_at);
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(0, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
        $this->assertTrue(Notice::query()->where('source_object_id', $copy->id)->sole()->publicly_available);
    }

    public function test_permanent_delete_does_not_affect_other_competition_or_ordinary_notice(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition('Konkurs target');
        $otherCompetition = $this->createCompletedCompetition('Konkurs other');
        $copy = $this->createCopyWithFile($competition, 'TARGET-BYTES', 'Naziv target');
        $otherCopy = $this->createCopyWithFile($otherCompetition, 'OTHER-BYTES', 'Naziv other');
        $this->publishCopy($admin, $competition, $copy);
        $this->publishCopy($admin, $otherCompetition, $otherCopy);
        $ordinary = Notice::factory()->create([
            'title' => 'Ordinary FT-004',
            'source_type' => 'tender',
            'source_id' => 999,
            'content_delivery' => 'html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'source_object_id' => null,
        ]);

        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copy))->assertRedirect();

        $this->assertTrue($otherCopy->fresh()->isCurrentlyPublished());
        $this->assertTrue(Notice::query()->where('source_object_id', $otherCopy->id)->sole()->publicly_available);
        $this->assertTrue($ordinary->fresh()->publicly_available);
        $this->assertTrue($ordinary->fresh()->visible_in_active_panel);
        $this->assertNull($otherCopy->fresh()->permanently_deleted_at);
        Storage::disk('local')->assertExists($otherCopy->storage_path);
    }

    public function test_admin_cannot_permanently_delete(): void
    {
        $this->assertRoleCannotPermanentlyDelete('admin');
    }

    public function test_superadmin_cannot_permanently_delete(): void
    {
        $this->assertRoleCannotPermanentlyDelete('superadmin');
    }

    public function test_wrong_competition_or_copy_cannot_permanently_delete(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competitionA = $this->createCompletedCompetition('Konkurs A');
        $competitionB = $this->createCompletedCompetition('Konkurs B');
        $copyA = $this->createCopyWithFile($competitionA);
        $this->publishCopy($admin, $competitionA, $copyA);

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competitionB, $copyA),
        )->assertNotFound();

        $this->assertNull($copyA->fresh()->permanently_deleted_at);
        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->whereIn('action', [
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED,
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED,
        ])->count());
    }

    public function test_non_closed_competition_cannot_permanently_delete(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($admin, $competition, $copy);
        $competition->forceFill(['status' => 'published', 'closed_at' => null])->save();

        $this->actingAs($admin)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertForbidden();

        $this->assertTrue(Notice::query()->sole()->publicly_available);
        $this->assertNull($copy->fresh()->permanently_deleted_at);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_permanent_delete_uses_copy_row_locks_and_does_not_hard_delete_row(): void
    {
        $source = file_get_contents((new ReflectionClass(CompetitionOfficialDecisionController::class))->getFileName());
        $this->assertSame(2, substr_count($source, 'lockForUpdate()'));
        $this->assertStringNotContainsString('$copy->delete()', $source);
        $this->assertStringNotContainsString('forceDelete()', $source);

        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $copyId = $copy->id;
        $this->publishCopy($admin, $competition, $copy);
        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copy))->assertRedirect();

        $this->assertNotNull(CompetitionOfficialDecisionCopy::query()->find($copyId));
        $this->assertSame(1, Notice::query()->count());
        $this->assertSame(2, CompetitionOfficialDecisionLifecycleEvent::query()->count());
    }

    public function test_sequential_double_submit_while_pending_completes_once(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition, 'DOUBLE-BYTES', 'Naziv double');
        $this->publishCopy($admin, $competition, $copy);
        $this->simulatePendingAfterT1($admin, $competition, $copy);

        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copy))->assertRedirect();
        $this->actingAs($admin)->post($this->permanentDeleteRoute($competition, $copy))->assertNotFound();

        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED));
        $this->assertCount(1, $this->lifecycleEvents(CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED));
        $this->assertNotNull($copy->fresh()->permanently_deleted_at);
    }

    /**
     * @return list<CompetitionOfficialDecisionLifecycleEvent>
     */
    private function lifecycleEvents(string $action): array
    {
        return CompetitionOfficialDecisionLifecycleEvent::query()
            ->where('action', $action)
            ->orderBy('id')
            ->get()
            ->all();
    }

    private function simulatePendingAfterT1(
        User $admin,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): void {
        $notice = Notice::query()
            ->where('source_object_id', $copy->id)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->firstOrFail();
        $notice->forceFill([
            'publicly_available' => false,
            'visible_in_active_panel' => false,
        ])->save();

        $copy->forceFill(['permanent_delete_pending_at' => now()])->save();

        CompetitionOfficialDecisionLifecycleEvent::create([
            'competition_official_decision_copy_id' => $copy->id,
            'competition_id' => $competition->id,
            'action' => CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED,
            'actor_user_id' => $admin->id,
            'payload' => [
                'business_title' => $copy->business_title,
                'business_published_on' => optional($copy->fresh()->business_published_on)?->toDateString(),
                'notice_id' => $notice->id,
            ],
        ]);
    }

    private function permanentDeleteRoute(Competition $competition, CompetitionOfficialDecisionCopy $copy): string
    {
        return route('admin.competitions.official-decision.permanent-delete', [$competition, $copy]);
    }

    private function publishCopy(
        User $admin,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
        ?string $date = null,
    ): void {
        $this->actingAs($admin)->post(
            route('admin.competitions.official-decision.publish', [$competition, $copy]),
            ['business_published_on' => $date ?? now()->toDateString()],
        )->assertRedirect();
    }

    private function legacyHtmlNotice(Competition $competition): Notice
    {
        return Notice::factory()->create([
            'title' => 'Legacy HTML Odluka',
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => null,
            'content_delivery' => 'competition_decision_html',
            'visible_in_active_panel' => true,
            'publicly_available' => true,
        ]);
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

    private function createCompletedCompetition(string $title = 'Konkurs za trajno brisanje'): Competition
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

    private function assertRoleCannotPermanentlyDelete(string $roleName): void
    {
        $publisher = $this->userWithRole('konkurs_admin');
        $competition = $this->createCompletedCompetition();
        $copy = $this->createCopyWithFile($competition);
        $this->publishCopy($publisher, $competition, $copy);
        $path = $copy->fresh()->storage_path;

        $user = $this->userWithRole($roleName);
        $this->actingAs($user)->post(
            $this->permanentDeleteRoute($competition, $copy),
        )->assertForbidden();

        $this->assertNull($copy->fresh()->permanently_deleted_at);
        $this->assertTrue(Notice::query()->where('source_object_id', $copy->id)->sole()->publicly_available);
        Storage::disk('local')->assertExists($path);
        $this->assertSame(0, CompetitionOfficialDecisionLifecycleEvent::query()->whereIn('action', [
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED,
            CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED,
        ])->count());
    }
}
