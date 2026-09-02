<?php

namespace App\Http\Controllers\Admin;

use App\Events\OfficialContentPublicAvailabilityRevoked;
use App\Events\OfficialContentPublicMetadataUpdated;
use App\Events\OfficialContentReadyForPublicPublication;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\CompetitionOfficialDecisionLifecycleEvent;
use App\Models\Notice;
use App\Services\Competitions\CompetitionOfficialDecisionCopyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompetitionOfficialDecisionController extends Controller
{
    public function store(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopyService $service,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

        $request->merge([
            'business_title' => trim((string) $request->input('business_title', '')),
        ]);

        $validated = $request->validate([
            'official_decision_copy' => ['required', 'file', 'mimes:pdf', 'max:2048'],
            'business_title' => ['required', 'string', 'max:255'],
        ], [
            'official_decision_copy.required' => 'Potpisani primjerak zvanične Odluke je obavezan.',
            'official_decision_copy.mimes' => 'Potpisani primjerak mora biti PDF fajl.',
            'official_decision_copy.max' => 'Potpisani primjerak ne može biti veći od 2MB.',
            'business_title.required' => 'Naziv dokumenta je obavezan.',
            'business_title.string' => 'Naziv dokumenta mora biti tekst.',
            'business_title.max' => 'Naziv dokumenta ne može biti duži od 255 karaktera.',
        ]);

        $service->store(
            $competition,
            $validated['official_decision_copy'],
            $request->user(),
            $validated['business_title'],
        );

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Potpisani primjerak zvanične Odluke je postavljen.');
    }

    public function publish(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

        if ((int) $copy->competition_id !== (int) $competition->id) {
            abort(404);
        }

        if ($copy->permanently_deleted_at !== null || $copy->permanent_delete_pending_at !== null) {
            abort(404);
        }

        $storagePath = $copy->storage_path;

        if (! is_string($storagePath) || $storagePath === '' || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        if ($copy->hasBeenPublished()) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak je već objavljen.']);
        }

        if (CompetitionOfficialDecisionCopy::competitionHasNonTombstonedSignedCopyPublication($competition->id)) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Zvanična Odluka je već objavljena za ovaj Konkurs.']);
        }

        $businessTitle = trim((string) $copy->business_title);

        if ($businessTitle === '') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['business_title' => 'Naziv dokumenta je obavezan.']);
        }

        $validated = $request->validate([
            'business_published_on' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'business_published_on.required' => 'Datum objave je obavezan.',
            'business_published_on.date' => 'Datum objave nije ispravan.',
            'business_published_on.before_or_equal' => 'Datum objave ne može biti u budućnosti.',
        ]);

        $businessPublishedOn = $validated['business_published_on'];

        DB::transaction(function () use ($copy, $competition, $businessTitle, $businessPublishedOn) {
            $copy->business_published_on = $businessPublishedOn;
            $copy->save();

            CompetitionOfficialDecisionCopy::revokeLeftoverDecisionHtmlPublications($competition->id);

            event(new OfficialContentReadyForPublicPublication(
                $businessTitle,
                $competition->title,
                'competition_decision',
                $competition->id,
                'competition_decision_signed_copy',
                null,
                false,
                $copy->id,
                $businessPublishedOn,
            ));
        });

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Zvanična Odluka je objavljena.');
    }

    public function correct(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

        if ((int) $copy->competition_id !== (int) $competition->id) {
            abort(404);
        }

        $this->assertCopyIsLiveOnCompetition($competition, $copy);

        $storagePath = $copy->storage_path;

        if (! is_string($storagePath) || $storagePath === '' || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        $activeNotices = CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id);

        if ($activeNotices->count() === 0) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nema aktivne objave zvanične Odluke za korekciju.']);
        }

        if ($activeNotices->count() > 1) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće korigovati objavu jer postoji više aktivnih objava.']);
        }

        $oldNotice = $activeNotices->first();

        if ((int) $oldNotice->source_object_id === (int) $copy->id) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Korekcija mora koristiti drugi primjerak.']);
        }

        if ($copy->hasBeenPublished()) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak je već objavljen.']);
        }

        $businessTitle = trim((string) $copy->business_title);

        if ($businessTitle === '') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['business_title' => 'Naziv dokumenta je obavezan.']);
        }

        $validated = $request->validate([
            'business_published_on' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'business_published_on.required' => 'Datum objave je obavezan.',
            'business_published_on.date' => 'Datum objave nije ispravan.',
            'business_published_on.before_or_equal' => 'Datum objave ne može biti u budućnosti.',
        ]);

        $businessPublishedOn = $validated['business_published_on'];

        DB::transaction(function () use (
            $copy,
            $competition,
            $businessTitle,
            $businessPublishedOn,
            $oldNotice,
        ) {
            $copy->business_published_on = $businessPublishedOn;
            $copy->save();

            CompetitionOfficialDecisionCopy::revokeLeftoverDecisionHtmlPublications($competition->id);

            event(new OfficialContentReadyForPublicPublication(
                $businessTitle,
                $competition->title,
                'competition_decision',
                $competition->id,
                'competition_decision_signed_copy',
                $oldNotice->id,
                true,
                $copy->id,
                $businessPublishedOn,
            ));
        });

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Objava zvanične Odluke je korigovana.');
    }

    public function updateMetadata(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);
        $this->assertCopyIsLiveOnCompetition($competition, $copy);

        $notice = $this->currentPublicSignedCopyNoticeOrRedirect($competition, $copy);

        if ($notice instanceof RedirectResponse) {
            return $notice;
        }

        $request->merge([
            'business_title' => trim((string) $request->input('business_title', '')),
        ]);

        $validated = $request->validate([
            'business_title' => ['required', 'string', 'max:255'],
            'business_published_on' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'business_title.required' => 'Naziv dokumenta je obavezan.',
            'business_title.string' => 'Naziv dokumenta mora biti tekst.',
            'business_title.max' => 'Naziv dokumenta ne može biti duži od 255 karaktera.',
            'business_published_on.required' => 'Datum objave je obavezan.',
            'business_published_on.date' => 'Datum objave nije ispravan.',
            'business_published_on.before_or_equal' => 'Datum objave ne može biti u budućnosti.',
        ]);

        $businessTitle = $validated['business_title'];
        $businessPublishedOn = $validated['business_published_on'];
        $oldTitle = $copy->business_title;
        $oldPublishedOn = optional($copy->business_published_on)?->toDateString();
        $actorUserId = $request->user()->id;

        DB::transaction(function () use (
            $copy,
            $competition,
            $notice,
            $businessTitle,
            $businessPublishedOn,
            $oldTitle,
            $oldPublishedOn,
            $actorUserId,
        ) {
            $copy->business_title = $businessTitle;
            $copy->business_published_on = $businessPublishedOn;
            $copy->save();

            CompetitionOfficialDecisionLifecycleEvent::create([
                'competition_official_decision_copy_id' => $copy->id,
                'competition_id' => $competition->id,
                'action' => CompetitionOfficialDecisionLifecycleEvent::ACTION_METADATA_CORRECTED,
                'actor_user_id' => $actorUserId,
                'payload' => [
                    'business_title' => [
                        'from' => $oldTitle,
                        'to' => $businessTitle,
                    ],
                    'business_published_on' => [
                        'from' => $oldPublishedOn,
                        'to' => $businessPublishedOn,
                    ],
                    'notice_id' => $notice->id,
                ],
            ]);

            event(new OfficialContentPublicMetadataUpdated(
                $notice->id,
                $businessTitle,
                $businessPublishedOn,
            ));
        });

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Podaci objave zvanične Odluke su ispravljeni.');
    }

    public function unpublish(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);
        $this->assertCopyIsLiveOnCompetition($competition, $copy);

        $notice = $this->currentPublicSignedCopyNoticeOrRedirect($competition, $copy);

        if ($notice instanceof RedirectResponse) {
            return $notice;
        }

        $actorUserId = $request->user()->id;

        DB::transaction(function () use ($copy, $competition, $notice, $actorUserId) {
            CompetitionOfficialDecisionLifecycleEvent::create([
                'competition_official_decision_copy_id' => $copy->id,
                'competition_id' => $competition->id,
                'action' => CompetitionOfficialDecisionLifecycleEvent::ACTION_UNPUBLISHED,
                'actor_user_id' => $actorUserId,
                'payload' => [
                    'public_availability_revoked' => true,
                    'notice_id' => $notice->id,
                ],
            ]);

            event(new OfficialContentPublicAvailabilityRevoked($notice->id));
        });

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Objava zvanične Odluke je povučena.');
    }

    public function republish(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);
        $this->assertCopyIsLiveOnCompetition($competition, $copy);

        $storagePath = $copy->storage_path;

        if (! is_string($storagePath) || $storagePath === '' || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        if (! $copy->hasBeenPublished()) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak nije ranije objavljen.']);
        }

        if ($copy->isCurrentlyPublished()) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak je već objavljen.']);
        }

        $activeNotices = CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id);

        if ($activeNotices->count() > 1) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće ponovo objaviti jer postoji više aktivnih objava.']);
        }

        if ($activeNotices->count() === 1) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Zvanična Odluka je već objavljena za ovaj Konkurs.']);
        }

        $previousNotice = $copy->previousRevokedSignedCopyNoticesQuery()->first();

        if ($previousNotice === null) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nema povučene objave ovog primjerka za ponovno objavljivanje.']);
        }

        $request->merge([
            'business_title' => trim((string) $request->input('business_title', '')),
        ]);

        $validated = $request->validate([
            'business_title' => ['required', 'string', 'max:255'],
            'business_published_on' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'business_title.required' => 'Naziv dokumenta je obavezan.',
            'business_title.string' => 'Naziv dokumenta mora biti tekst.',
            'business_title.max' => 'Naziv dokumenta ne može biti duži od 255 karaktera.',
            'business_published_on.required' => 'Datum objave je obavezan.',
            'business_published_on.date' => 'Datum objave nije ispravan.',
            'business_published_on.before_or_equal' => 'Datum objave ne može biti u budućnosti.',
        ]);

        $businessTitle = $validated['business_title'];
        $businessPublishedOn = $validated['business_published_on'];
        $oldTitle = $copy->business_title;
        $oldPublishedOn = optional($copy->business_published_on)?->toDateString();
        $actorUserId = $request->user()->id;
        $previousNoticeId = $previousNotice->id;

        DB::transaction(function () use (
            $copy,
            $competition,
            $businessTitle,
            $businessPublishedOn,
            $oldTitle,
            $oldPublishedOn,
            $actorUserId,
            $previousNoticeId,
        ) {
            $copy->business_title = $businessTitle;
            $copy->business_published_on = $businessPublishedOn;
            $copy->save();

            CompetitionOfficialDecisionCopy::revokeLeftoverDecisionHtmlPublications($competition->id);

            CompetitionOfficialDecisionLifecycleEvent::create([
                'competition_official_decision_copy_id' => $copy->id,
                'competition_id' => $competition->id,
                'action' => CompetitionOfficialDecisionLifecycleEvent::ACTION_REPUBLISHED,
                'actor_user_id' => $actorUserId,
                'payload' => [
                    'previous_notice_id' => $previousNoticeId,
                    'business_title' => [
                        'from' => $oldTitle,
                        'to' => $businessTitle,
                    ],
                    'business_published_on' => [
                        'from' => $oldPublishedOn,
                        'to' => $businessPublishedOn,
                    ],
                ],
            ]);

            event(new OfficialContentReadyForPublicPublication(
                $businessTitle,
                $competition->title,
                'competition_decision',
                $competition->id,
                'competition_decision_signed_copy',
                null,
                false,
                $copy->id,
                $businessPublishedOn,
            ));
        });

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Zvanična Odluka je ponovo objavljena.');
    }

    public function permanentlyDelete(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

        if ((int) $copy->competition_id !== (int) $competition->id) {
            abort(404);
        }

        $actorUserId = $request->user()->id;

        $t1 = $this->startOfficialDecisionPermanentDelete($competition, $copy, $actorUserId);

        if ($t1['outcome'] === 'already_completed') {
            abort(404);
        }

        if ($t1['outcome'] === 'never_published') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak nije ranije objavljen.']);
        }

        if ($t1['outcome'] === 'multiple_current') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće trajno obrisati jer postoji više aktivnih objava.']);
        }

        if ($t1['outcome'] === 'current_belongs_to_another_copy') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće trajno obrisati jer postoji aktivna objava drugog primjerka.']);
        }

        if ($t1['outcome'] === 'pending_public_active') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće nastaviti trajno brisanje jer postoji aktivna objava.']);
        }

        $storagePath = $t1['storage_path'] ?? null;

        if (! $this->deleteOfficialDecisionStoredPdf($storagePath)) {
            Log::warning('Official decision permanent delete could not confirm PDF absence.', [
                'competition_id' => $competition->id,
                'copy_id' => $copy->id,
            ]);

            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Trajno brisanje nije moglo da se završi. Pokušajte ponovo.']);
        }

        $t2 = $this->completeOfficialDecisionPermanentDelete($competition, $copy, $actorUserId);

        if ($t2['outcome'] === 'already_completed') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->with('success', 'Zvanična Odluka je trajno obrisana.');
        }

        if ($t2['outcome'] !== 'completed') {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Trajno brisanje nije moglo da se završi. Pokušajte ponovo.']);
        }

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Zvanična Odluka je trajno obrisana.');
    }

    /**
     * @return array{outcome: string, storage_path?: ?string}
     */
    private function startOfficialDecisionPermanentDelete(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
        int $actorUserId,
    ): array {
        return DB::transaction(function () use ($competition, $copy, $actorUserId) {
            /** @var CompetitionOfficialDecisionCopy $locked */
            $locked = CompetitionOfficialDecisionCopy::query()
                ->whereKey($copy->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->competition_id !== (int) $competition->id) {
                abort(404);
            }

            if ($locked->permanently_deleted_at !== null) {
                return ['outcome' => 'already_completed'];
            }

            $activeNotices = CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id);
            $activeCount = $activeNotices->count();

            if ($locked->permanent_delete_pending_at !== null) {
                if ($activeCount > 0) {
                    return ['outcome' => 'pending_public_active'];
                }

                return [
                    'outcome' => 'pending_retry',
                    'storage_path' => $locked->storage_path,
                ];
            }

            if (! $locked->hasBeenPublished()) {
                return ['outcome' => 'never_published'];
            }

            if ($activeCount > 1) {
                return ['outcome' => 'multiple_current'];
            }

            $noticeId = null;

            if ($activeCount === 1) {
                $notice = $activeNotices->first();

                if ((int) $notice->source_object_id !== (int) $locked->id) {
                    return ['outcome' => 'current_belongs_to_another_copy'];
                }

                $noticeId = $notice->id;
                event(new OfficialContentPublicAvailabilityRevoked($notice->id));
            } else {
                $noticeId = $locked->previousRevokedSignedCopyNoticesQuery()->first()?->id;
            }

            CompetitionOfficialDecisionCopy::revokeLeftoverDecisionHtmlPublications($competition->id);

            $locked->permanent_delete_pending_at = now();
            $locked->save();

            CompetitionOfficialDecisionLifecycleEvent::create([
                'competition_official_decision_copy_id' => $locked->id,
                'competition_id' => $competition->id,
                'action' => CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_STARTED,
                'actor_user_id' => $actorUserId,
                'payload' => $this->permanentDeleteAuditPayload($locked, $noticeId),
            ]);

            return [
                'outcome' => 'started',
                'storage_path' => $locked->storage_path,
            ];
        });
    }

    /**
     * @return array{outcome: string}
     */
    private function completeOfficialDecisionPermanentDelete(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
        int $actorUserId,
    ): array {
        return DB::transaction(function () use ($competition, $copy, $actorUserId) {
            /** @var CompetitionOfficialDecisionCopy $locked */
            $locked = CompetitionOfficialDecisionCopy::query()
                ->whereKey($copy->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->competition_id !== (int) $competition->id) {
                abort(404);
            }

            if ($locked->permanently_deleted_at !== null) {
                return ['outcome' => 'already_completed'];
            }

            if ($locked->permanent_delete_pending_at === null) {
                return ['outcome' => 'not_pending'];
            }

            $activeCount = CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id)->count();

            if ($activeCount > 0) {
                return ['outcome' => 'pending_public_active'];
            }

            if (! $this->officialDecisionPdfIsPhysicallyAbsent($locked->storage_path)) {
                return ['outcome' => 'file_present'];
            }

            $noticeId = $locked->previousRevokedSignedCopyNoticesQuery()->first()?->id;

            $locked->permanently_deleted_at = now();
            $locked->permanently_deleted_by = $actorUserId;
            $locked->storage_path = null;
            $locked->permanent_delete_pending_at = null;
            $locked->save();

            CompetitionOfficialDecisionLifecycleEvent::create([
                'competition_official_decision_copy_id' => $locked->id,
                'competition_id' => $competition->id,
                'action' => CompetitionOfficialDecisionLifecycleEvent::ACTION_PERMANENT_DELETE_COMPLETED,
                'actor_user_id' => $actorUserId,
                'payload' => $this->permanentDeleteAuditPayload($locked, $noticeId),
            ]);

            return ['outcome' => 'completed'];
        });
    }

    /**
     * @return array{business_title: mixed, business_published_on: ?string, notice_id: mixed}
     */
    private function permanentDeleteAuditPayload(
        CompetitionOfficialDecisionCopy $copy,
        mixed $noticeId,
    ): array {
        return [
            'business_title' => $copy->business_title,
            'business_published_on' => optional($copy->business_published_on)?->toDateString(),
            'notice_id' => $noticeId,
        ];
    }

    protected function officialDecisionPdfIsPhysicallyAbsent(?string $storagePath): bool
    {
        if (! is_string($storagePath) || $storagePath === '') {
            return true;
        }

        return Storage::disk('local')->exists($storagePath) === false;
    }

    protected function deleteOfficialDecisionStoredPdf(?string $storagePath): bool
    {
        if ($this->officialDecisionPdfIsPhysicallyAbsent($storagePath)) {
            return true;
        }

        Storage::disk('local')->delete($storagePath);

        return $this->officialDecisionPdfIsPhysicallyAbsent($storagePath);
    }

    private function assertCopyIsLiveOnCompetition(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): void {
        if ((int) $copy->competition_id !== (int) $competition->id) {
            abort(404);
        }

        if ($copy->permanently_deleted_at !== null || $copy->permanent_delete_pending_at !== null) {
            abort(404);
        }
    }

    private function currentPublicSignedCopyNoticeOrRedirect(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): Notice|RedirectResponse {
        $notices = $copy->currentPublicSignedCopyNotices();

        if ($notices->count() === 0) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nema trenutno javne objave zvanične Odluke za ovaj primjerak.']);
        }

        if ($notices->count() > 1) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće nastaviti jer postoji više aktivnih objava ovog primjerka.']);
        }

        return $notices->first();
    }

    private function assertKonkursAdmin(): void
    {
        $roleName = auth()->user()?->role?->name;

        if ($roleName !== 'konkurs_admin') {
            abort(403);
        }
    }

    private function assertCompetitionAllowsOfficialDecisionAction(Competition $competition): void
    {
        if (! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403);
        }
    }
}
