<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BusinessPlanController;
use App\Http\Controllers\CompetitionsController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CulturalCalendarController;
use App\Http\Controllers\CulturalCalendarNewsletterController;
use App\Http\Controllers\CulturalEditorialDashboardController;
use App\Http\Controllers\CulturalEventChangeProposalController;
use App\Http\Controllers\CulturalEventEntryController;
use App\Http\Controllers\CulturalEventEntryOccurrenceController;
use App\Http\Controllers\CulturalLocationController;
use App\Http\Controllers\CulturalCategoryController;
use App\Http\Controllers\CulturalManifestationController;
use App\Http\Controllers\CulturalMediaController;
use App\Http\Controllers\CulturalModeratorDashboardController;
use App\Http\Controllers\CulturalModeratorEventEntryController;
use App\Http\Controllers\CulturalModeratorManifestationController;
use App\Http\Controllers\CulturalModeratorEventEntryOccurrenceController;
use App\Http\Controllers\CulturalModeratorEventChangeProposalController;
use App\Http\Controllers\CulturalModeratorOrganizerContextController;
use App\Http\Controllers\CulturalModeratorRequestController;
use App\Http\Controllers\CulturalModeratorWorkspaceController;
use App\Http\Controllers\CulturalOrganizerController;
use App\Http\Controllers\CulturalOrganizerCreationRequestController;
use App\Http\Controllers\CulturalTagController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\PublicNoticeContentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TendersController;
use Illuminate\Support\Facades\Route;

// Učitaj auth rute (za email verifikaciju i sl.)
require __DIR__.'/auth.php';

// Početna stranica (landing/home)
Route::get('/', [HomeController::class, 'index'])->name('home'); // Prikaz početne strane

// Javni sadržaj Obavještenja (FT-004) — bez auth / verified / role middleware
Route::get('/obavjestenja/{notice}/sadrzaj', [PublicNoticeContentController::class, 'show'])
    ->name('notices.public-content');

// Rute za autentikaciju (login/register) - koristi Breeze, Fortify ili custom rješenje
Route::get('/login', [HomeController::class, 'loginForm'])->name('login'); // Forma za login
Route::post('/login', [HomeController::class, 'login']); // Slanje login podataka
Route::get('/register', [HomeController::class, 'registerForm'])->name('register'); // Forma za registraciju
Route::post('/register', [HomeController::class, 'register']); // Slanje podataka za registraciju

// Grupe ruta dostupne samo prijavljenim korisnicima
Route::middleware(['auth', 'verified', 'module_access_restrict'])->group(function () {
    // Dashboard - profil korisnika nakon prijave
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard'); // Prikaz korisničkog panela

    // Profil korisnika
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Biblioteka dokumenata
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/documents/process-for-mega', [DocumentController::class, 'processDocumentsForMega'])->name('documents.process-for-mega');
    Route::get('/documents/temp-download-mega', [DocumentController::class, 'tempDownloadMegaPdf'])->name('documents.temp-download-mega');
    Route::post('/documents/store-mega', [DocumentController::class, 'storeMegaMetadata'])->name('documents.store-mega');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::patch('/documents/{document}/category', [DocumentController::class, 'updateCategory'])->name('documents.update-category');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/status', [DocumentController::class, 'status'])->name('documents.status'); // API za proveru statusa

    // MEGA API endpoints
    Route::post('/api/mega/session', [DocumentController::class, 'getMegaSession'])->name('mega.session');

    // Modul za online plaćanja opštinskih prihoda
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index'); // Prikaz forme i istorije uplata
    Route::post('/payments/pay', [PaymentsController::class, 'pay'])->name('payments.pay'); // Slanje zahtjeva za uplatu

    // Modul za konkurse (žensko/omladinsko preduzetništvo)
    Route::get('/competitions', [CompetitionsController::class, 'index'])->name('competitions.index'); // Lista konkursa
    Route::get('/competitions/guide/pdf', function () {
        $filename = 'uputstvo-zensko-preduzetnistvo.pdf';
        $candidatePaths = [
            public_path('pdf/'.$filename),
            storage_path('app/public/pdf/'.$filename),
            storage_path('app/public/documents/'.$filename),
        ];

        foreach ($candidatePaths as $path) {
            if (is_file($path)) {
                return response()->file($path, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                ]);
            }
        }

        abort(404, 'PDF uputstvo nije pronađeno.');
    })->name('competitions.guide.pdf');
    // Arhiva konkursa (mora biti pre rute sa parametrom)
    Route::middleware('role:admin,konkurs_admin,komisija')->group(function () {
        Route::get('/competitions/archive', [AdminController::class, 'competitionsArchive'])->name('competitions.archive');
    });
    Route::get('/competitions/{competition}', [CompetitionsController::class, 'show'])->name('competitions.show'); // Detalji konkursa

    // Modul za tendersku dokumentaciju
    Route::get('/tenders', [TendersController::class, 'index'])->name('tenders.index'); // Lista tendera
    Route::get('/tenders/{id}', [TendersController::class, 'show'])->name('tenders.show'); // Detalji tendera
    Route::post('/tenders/purchase', [TendersController::class, 'purchase'])->name('tenders.purchase'); // Otkup tenderske dokumentacije

    // Modul za kalendar kulturnih događaja (pregled za sve prijavljene korisnike)
    Route::get('/kalendar-kulture', [CulturalCalendarController::class, 'index'])->name('cultural-calendar.index');
    Route::get('/kalendar-kulture/pregled-dogadjaja', [CulturalCalendarController::class, 'events'])->name('cultural-calendar.events');
    Route::get('/kalendar-kulture/arhiva-dogadjaja', [CulturalCalendarController::class, 'archive'])->name('cultural-calendar.archive');
    Route::get('/kalendar-kulture/manifestacije', [CulturalCalendarController::class, 'manifestations'])->name('cultural-calendar.manifestations');
    Route::get('/kalendar-kulture/manifestacije/{manifestacija}', [CulturalCalendarController::class, 'manifestationShow'])->name('cultural-calendar.manifestation');
    Route::get('/kalendar-kulture/dogadjaj/{event}', [CulturalCalendarController::class, 'show'])->name('cultural-calendar.show');
    Route::get('/kalendar-kulture/dan/{date}', [CulturalCalendarController::class, 'day'])->name('cultural-calendar.day');
    Route::post('/kalendar-kulture/newsletter', [CulturalCalendarNewsletterController::class, 'store'])->name('cultural-calendar.newsletter.store');

    // TS-001 — zahtjev za kreiranje Organizatora (registrovani korisnik; ne kreira entitet)
    Route::get('/kalendar-kulture/zahtjev-organizator', [CulturalOrganizerCreationRequestController::class, 'create'])
        ->name('cultural-organizer-creation-requests.create');
    Route::post('/kalendar-kulture/zahtjev-organizator', [CulturalOrganizerCreationRequestController::class, 'store'])
        ->name('cultural-organizer-creation-requests.store');

    // TS-001 — portal pristup (kk_admin ili aktivni Moderator aktivnog Org) — PO-ORG-04
    Route::middleware('cultural.portal')->group(function () {
        Route::get('/kalendar-kulture/moderatorski-rad', [CulturalModeratorWorkspaceController::class, 'index'])
            ->name('cultural-moderator-workspace.index');
        Route::get('/kalendar-kulture/organizatori/{organizatori}/zahtjev-moderator', [CulturalModeratorRequestController::class, 'create'])
            ->name('cultural-moderator-requests.create');
        Route::post('/kalendar-kulture/organizatori/{organizatori}/zahtjev-moderator', [CulturalModeratorRequestController::class, 'store'])
            ->name('cultural-moderator-requests.store');
    });

    // TS-010.1 — Moderator Draft tok + aktivni Organizator kontekst
    // TS-010.6 — Moderator Dashboard (DM-01–DM-03)
    Route::middleware(['cultural.portal', 'cultural.moderator'])->group(function () {
        Route::post('/kalendar-kulture/moderatorski-rad/kontekst', [CulturalModeratorOrganizerContextController::class, 'update'])
            ->name('cultural-moderator-context.update');

        Route::get('/kalendar-kulture/moderatorska-radna-tabla', [CulturalModeratorDashboardController::class, 'index'])
            ->name('cultural-moderator-dashboard.index');

        Route::get('/kalendar-kulture/moderatorski-dogadjaji', [CulturalModeratorEventEntryController::class, 'index'])
            ->name('cultural-moderator-events.index');
        Route::get('/kalendar-kulture/moderatorski-dogadjaji/create', [CulturalModeratorEventEntryController::class, 'create'])
            ->name('cultural-moderator-events.create');
        Route::post('/kalendar-kulture/moderatorski-dogadjaji', [CulturalModeratorEventEntryController::class, 'store'])
            ->name('cultural-moderator-events.store');
        Route::get('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/edit', [CulturalModeratorEventEntryController::class, 'edit'])
            ->name('cultural-moderator-events.edit');
        Route::put('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}', [CulturalModeratorEventEntryController::class, 'update'])
            ->name('cultural-moderator-events.update');
        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/submit', [CulturalModeratorEventEntryController::class, 'submit'])
            ->name('cultural-moderator-events.submit');
        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/cancel', [CulturalModeratorEventEntryController::class, 'cancel'])
            ->name('cultural-moderator-events.cancel');

        // 6B-02 — Moderator Manifestacije
        Route::get('/kalendar-kulture/moderatorske-manifestacije', [CulturalModeratorManifestationController::class, 'index'])
            ->name('cultural-moderator-manifestations.index');
        Route::get('/kalendar-kulture/moderatorske-manifestacije/create', [CulturalModeratorManifestationController::class, 'create'])
            ->name('cultural-moderator-manifestations.create');
        Route::post('/kalendar-kulture/moderatorske-manifestacije', [CulturalModeratorManifestationController::class, 'store'])
            ->name('cultural-moderator-manifestations.store');
        Route::get('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}/edit', [CulturalModeratorManifestationController::class, 'edit'])
            ->name('cultural-moderator-manifestations.edit');
        Route::put('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}', [CulturalModeratorManifestationController::class, 'update'])
            ->name('cultural-moderator-manifestations.update');
        Route::post('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}/submit', [CulturalModeratorManifestationController::class, 'submit'])
            ->name('cultural-moderator-manifestations.submit');
        Route::post('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}/cancel', [CulturalModeratorManifestationController::class, 'cancel'])
            ->name('cultural-moderator-manifestations.cancel');
        Route::post('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}/dogadjaji/link', [CulturalModeratorManifestationController::class, 'linkEvent'])
            ->name('cultural-moderator-manifestations.events.link');
        Route::post('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}/dogadjaji/unlink', [CulturalModeratorManifestationController::class, 'unlinkEvent'])
            ->name('cultural-moderator-manifestations.events.unlink');
        Route::post('/kalendar-kulture/moderatorske-manifestacije/{moderator_manifestacija}/dogadjaji/move', [CulturalModeratorManifestationController::class, 'moveEvent'])
            ->name('cultural-moderator-manifestations.events.move');

        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja', [CulturalModeratorEventEntryOccurrenceController::class, 'store'])
            ->name('cultural-moderator-events.occurrences.store');
        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja/generisi', [CulturalModeratorEventEntryOccurrenceController::class, 'generate'])
            ->name('cultural-moderator-events.occurrences.generate');
        Route::put('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja/{odrzavanje}', [CulturalModeratorEventEntryOccurrenceController::class, 'update'])
            ->name('cultural-moderator-events.occurrences.update');
        Route::delete('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja/{odrzavanje}', [CulturalModeratorEventEntryOccurrenceController::class, 'destroy'])
            ->name('cultural-moderator-events.occurrences.destroy');

        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja/{odrzavanje}/postpone', [CulturalModeratorEventEntryOccurrenceController::class, 'postpone'])
            ->name('cultural-moderator-events.occurrences.postpone');
        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja/{odrzavanje}/cancel', [CulturalModeratorEventEntryOccurrenceController::class, 'cancel'])
            ->name('cultural-moderator-events.occurrences.cancel');
        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/odrzavanja/{odrzavanje}/resume', [CulturalModeratorEventEntryOccurrenceController::class, 'resume'])
            ->name('cultural-moderator-events.occurrences.resume');

        // TS-010.3a — Prijedlog izmjene (Moderator)
        Route::post('/kalendar-kulture/moderatorski-dogadjaji/{moderator_dogadjaj}/prijedlog-izmjene', [CulturalModeratorEventChangeProposalController::class, 'store'])
            ->name('cultural-moderator-proposals.store');
        Route::get('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/edit', [CulturalModeratorEventChangeProposalController::class, 'edit'])
            ->name('cultural-moderator-proposals.edit');
        Route::put('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}', [CulturalModeratorEventChangeProposalController::class, 'update'])
            ->name('cultural-moderator-proposals.update');
        Route::post('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/submit', [CulturalModeratorEventChangeProposalController::class, 'submit'])
            ->name('cultural-moderator-proposals.submit');
        Route::post('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/withdraw', [CulturalModeratorEventChangeProposalController::class, 'withdraw'])
            ->name('cultural-moderator-proposals.withdraw');

        // TS-010.3b — Occurrence ops na prijedlogu (Moderator)
        Route::post('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/odrzavanja', [CulturalModeratorEventChangeProposalController::class, 'storeOccurrence'])
            ->name('cultural-moderator-proposals.occurrences.store');
        Route::put('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/odrzavanja-kanonski/{odrzavanje}', [CulturalModeratorEventChangeProposalController::class, 'updateCanonicalOccurrence'])
            ->name('cultural-moderator-proposals.occurrences.update-canonical');
        Route::put('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/odrzavanja/{operacija}', [CulturalModeratorEventChangeProposalController::class, 'updateOccurrenceOp'])
            ->name('cultural-moderator-proposals.occurrences.update');
        Route::delete('/kalendar-kulture/moderatorski-prijedlozi/{prijedlog}/odrzavanja/{operacija}', [CulturalModeratorEventChangeProposalController::class, 'destroyOccurrenceOp'])
            ->name('cultural-moderator-proposals.occurrences.destroy');
    });

    // Administracija događaja i kataloga lokacija (samo KK administrator / Urednik)
    Route::middleware('role:kk_admin')->group(function () {
        // TS-010.2 / TS-010.3a — Urednik Dashboard / Inbox (DU-01–DU-05)
        Route::get('/kalendar-kulture/urednicki-rad', [CulturalEditorialDashboardController::class, 'index'])
            ->name('cultural-editorial-dashboard.index');

        // TS-010.3a — Prijedlozi izmjene (Urednik)
        Route::get('/kalendar-kulture/prijedlozi-izmjene', [CulturalEventChangeProposalController::class, 'index'])
            ->name('cultural-event-change-proposals.index');
        Route::get('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}', [CulturalEventChangeProposalController::class, 'show'])
            ->name('cultural-event-change-proposals.show');
        Route::get('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/edit', [CulturalEventChangeProposalController::class, 'edit'])
            ->name('cultural-event-change-proposals.edit');
        Route::put('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}', [CulturalEventChangeProposalController::class, 'update'])
            ->name('cultural-event-change-proposals.update');
        Route::post('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/start-review', [CulturalEventChangeProposalController::class, 'startReview'])
            ->name('cultural-event-change-proposals.start-review');
        Route::post('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/approve', [CulturalEventChangeProposalController::class, 'approve'])
            ->name('cultural-event-change-proposals.approve');
        Route::post('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/return', [CulturalEventChangeProposalController::class, 'returnToDraft'])
            ->name('cultural-event-change-proposals.return');

        // TS-010.3b — Occurrence ops na prijedlogu (Urednik)
        Route::post('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/odrzavanja', [CulturalEventChangeProposalController::class, 'storeOccurrence'])
            ->name('cultural-event-change-proposals.occurrences.store');
        Route::put('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/odrzavanja-kanonski/{odrzavanje}', [CulturalEventChangeProposalController::class, 'updateCanonicalOccurrence'])
            ->name('cultural-event-change-proposals.occurrences.update-canonical');
        Route::put('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/odrzavanja/{operacija}', [CulturalEventChangeProposalController::class, 'updateOccurrenceOp'])
            ->name('cultural-event-change-proposals.occurrences.update');
        Route::delete('/kalendar-kulture/prijedlozi-izmjene/{prijedlog}/odrzavanja/{operacija}', [CulturalEventChangeProposalController::class, 'destroyOccurrenceOp'])
            ->name('cultural-event-change-proposals.occurrences.destroy');

        // Sprint 3A.2 — kanonski Draft UI (CulturalEventEntry); nije TS-010 / nije legacy CRUD
        Route::get('/kalendar-kulture/kanonski-dogadjaji', [CulturalEventEntryController::class, 'index'])
            ->name('cultural-event-entries.index');
        Route::get('/kalendar-kulture/kanonski-dogadjaji/create', [CulturalEventEntryController::class, 'create'])
            ->name('cultural-event-entries.create');
        Route::post('/kalendar-kulture/kanonski-dogadjaji', [CulturalEventEntryController::class, 'store'])
            ->name('cultural-event-entries.store');
        Route::get('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/edit', [CulturalEventEntryController::class, 'edit'])
            ->name('cultural-event-entries.edit');
        Route::put('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}', [CulturalEventEntryController::class, 'update'])
            ->name('cultural-event-entries.update');
        Route::delete('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}', [CulturalEventEntryController::class, 'destroy'])
            ->name('cultural-event-entries.destroy');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/submit', [CulturalEventEntryController::class, 'submit'])
            ->name('cultural-event-entries.submit');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/approve', [CulturalEventEntryController::class, 'approve'])
            ->name('cultural-event-entries.approve');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/publish', [CulturalEventEntryController::class, 'publish'])
            ->name('cultural-event-entries.publish');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/return', [CulturalEventEntryController::class, 'returnToDraft'])
            ->name('cultural-event-entries.return');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/cancel', [CulturalEventEntryController::class, 'cancel'])
            ->name('cultural-event-entries.cancel');
        Route::put('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/cancellation-reason', [CulturalEventEntryController::class, 'updateCancellationReason'])
            ->name('cultural-event-entries.cancellation-reason');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/featured', [CulturalEventEntryController::class, 'updateFeatured'])
            ->name('cultural-event-entries.featured');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/link-organizer', [CulturalEventEntryController::class, 'linkOrganizer'])
            ->name('cultural-event-entries.link-organizer');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja', [CulturalEventEntryOccurrenceController::class, 'store'])
            ->name('cultural-event-entries.occurrences.store');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja/generisi', [CulturalEventEntryOccurrenceController::class, 'generate'])
            ->name('cultural-event-entries.occurrences.generate');
        Route::put('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja/{odrzavanje}', [CulturalEventEntryOccurrenceController::class, 'update'])
            ->name('cultural-event-entries.occurrences.update');
        Route::delete('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja/{odrzavanje}', [CulturalEventEntryOccurrenceController::class, 'destroy'])
            ->name('cultural-event-entries.occurrences.destroy');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja/{odrzavanje}/postpone', [CulturalEventEntryOccurrenceController::class, 'postpone'])
            ->name('cultural-event-entries.occurrences.postpone');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja/{odrzavanje}/cancel', [CulturalEventEntryOccurrenceController::class, 'cancel'])
            ->name('cultural-event-entries.occurrences.cancel');
        Route::post('/kalendar-kulture/kanonski-dogadjaji/{kanonski_dogadjaj}/odrzavanja/{odrzavanje}/resume', [CulturalEventEntryOccurrenceController::class, 'resume'])
            ->name('cultural-event-entries.occurrences.resume');

        // 6B-02 — Urednik Manifestacije
        Route::get('/kalendar-kulture/kanonske-manifestacije', [CulturalManifestationController::class, 'index'])
            ->name('cultural-manifestations.index');
        Route::get('/kalendar-kulture/kanonske-manifestacije/create', [CulturalManifestationController::class, 'create'])
            ->name('cultural-manifestations.create');
        Route::post('/kalendar-kulture/kanonske-manifestacije', [CulturalManifestationController::class, 'store'])
            ->name('cultural-manifestations.store');
        Route::get('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/edit', [CulturalManifestationController::class, 'edit'])
            ->name('cultural-manifestations.edit');
        Route::put('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}', [CulturalManifestationController::class, 'update'])
            ->name('cultural-manifestations.update');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/submit', [CulturalManifestationController::class, 'submit'])
            ->name('cultural-manifestations.submit');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/return', [CulturalManifestationController::class, 'returnToRevision'])
            ->name('cultural-manifestations.return');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/publish', [CulturalManifestationController::class, 'publish'])
            ->name('cultural-manifestations.publish');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/cancel', [CulturalManifestationController::class, 'cancel'])
            ->name('cultural-manifestations.cancel');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/dogadjaji/link', [CulturalManifestationController::class, 'linkEvent'])
            ->name('cultural-manifestations.events.link');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/dogadjaji/unlink', [CulturalManifestationController::class, 'unlinkEvent'])
            ->name('cultural-manifestations.events.unlink');
        Route::post('/kalendar-kulture/kanonske-manifestacije/{kanonska_manifestacija}/dogadjaji/move', [CulturalManifestationController::class, 'moveEvent'])
            ->name('cultural-manifestations.events.move');

        Route::resource('/kalendar-kulture/lokacije', CulturalLocationController::class)
            ->except(['show', 'destroy'])
            ->parameters(['lokacije' => 'lokacije'])
            ->names('cultural-locations');
        Route::post('/kalendar-kulture/lokacije/{lokacije}/deactivate', [CulturalLocationController::class, 'deactivate'])
            ->name('cultural-locations.deactivate');
        Route::post('/kalendar-kulture/lokacije/{lokacije}/activate', [CulturalLocationController::class, 'activate'])
            ->name('cultural-locations.activate');

        Route::resource('/kalendar-kulture/kategorije', CulturalCategoryController::class)
            ->except(['show', 'destroy'])
            ->parameters(['kategorije' => 'kategorije'])
            ->names('cultural-categories');
        Route::post('/kalendar-kulture/kategorije/{kategorije}/deactivate', [CulturalCategoryController::class, 'deactivate'])
            ->name('cultural-categories.deactivate');
        Route::post('/kalendar-kulture/kategorije/{kategorije}/activate', [CulturalCategoryController::class, 'activate'])
            ->name('cultural-categories.activate');

        Route::resource('/kalendar-kulture/oznake', CulturalTagController::class)
            ->except(['show', 'destroy'])
            ->parameters(['oznake' => 'oznake'])
            ->names('cultural-tags');
        Route::post('/kalendar-kulture/oznake/{oznake}/deactivate', [CulturalTagController::class, 'deactivate'])
            ->name('cultural-tags.deactivate');
        Route::post('/kalendar-kulture/oznake/{oznake}/activate', [CulturalTagController::class, 'activate'])
            ->name('cultural-tags.activate');

        Route::resource('/kalendar-kulture/mediji', CulturalMediaController::class)
            ->except(['show'])
            ->parameters(['mediji' => 'mediji'])
            ->names('cultural-media');
        Route::post('/kalendar-kulture/mediji/{mediji}/deactivate', [CulturalMediaController::class, 'deactivate'])
            ->name('cultural-media.deactivate');
        Route::post('/kalendar-kulture/mediji/{mediji}/activate', [CulturalMediaController::class, 'activate'])
            ->name('cultural-media.activate');

        // TS-001 — Organizatori i uredničke odluke
        Route::get('/kalendar-kulture/organizatori', [CulturalOrganizerController::class, 'index'])
            ->name('cultural-organizers.index');
        Route::get('/kalendar-kulture/organizatori/{organizatori}/edit', [CulturalOrganizerController::class, 'edit'])
            ->name('cultural-organizers.edit');
        Route::put('/kalendar-kulture/organizatori/{organizatori}', [CulturalOrganizerController::class, 'update'])
            ->name('cultural-organizers.update');
        Route::post('/kalendar-kulture/organizatori/{organizatori}/deactivate', [CulturalOrganizerController::class, 'deactivate'])
            ->name('cultural-organizers.deactivate');

        Route::get('/kalendar-kulture/zahtjevi-organizator', [CulturalOrganizerCreationRequestController::class, 'index'])
            ->name('cultural-organizer-creation-requests.index');
        Route::get('/kalendar-kulture/zahtjevi-organizator/{zahtjev}', [CulturalOrganizerCreationRequestController::class, 'show'])
            ->name('cultural-organizer-creation-requests.show');
        Route::post('/kalendar-kulture/zahtjevi-organizator/{zahtjev}/approve', [CulturalOrganizerCreationRequestController::class, 'approve'])
            ->name('cultural-organizer-creation-requests.approve');
        Route::post('/kalendar-kulture/zahtjevi-organizator/{zahtjev}/reject', [CulturalOrganizerCreationRequestController::class, 'reject'])
            ->name('cultural-organizer-creation-requests.reject');

        Route::get('/kalendar-kulture/zahtjevi-moderator', [CulturalModeratorRequestController::class, 'index'])
            ->name('cultural-moderator-requests.index');
        Route::get('/kalendar-kulture/zahtjevi-moderator/{zahtjev}', [CulturalModeratorRequestController::class, 'show'])
            ->name('cultural-moderator-requests.show');
        Route::post('/kalendar-kulture/zahtjevi-moderator/{zahtjev}/approve', [CulturalModeratorRequestController::class, 'approve'])
            ->name('cultural-moderator-requests.approve');
        Route::post('/kalendar-kulture/zahtjevi-moderator/{zahtjev}/reject', [CulturalModeratorRequestController::class, 'reject'])
            ->name('cultural-moderator-requests.reject');
    });

    // --- DOPUNA: RUTE ZA PORTAL ŽENSKOG PREDUZETNIŠTVA ---

    // Prijava na konkurs (ApplicationController)
    Route::get('/competitions/{competition}/apply', [ApplicationController::class, 'create'])->name('applications.create'); // Prikaz forme za prijavu
    Route::post('/competitions/{competition}/apply', [ApplicationController::class, 'store'])->name('applications.store'); // Snimi prijavu
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show'); // Prikaz detalja prijave
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy'); // Brisanje prijave
    Route::post('/applications/{application}/submit', [ApplicationController::class, 'submit'])->name('applications.final-submit'); // Konačno podnošenje prijave
    Route::post('/applications/{application}/upload', [ApplicationController::class, 'uploadDocument'])->name('applications.upload'); // Upload dokumenata
    Route::get('/applications/{application}/documents/{document}/view', [ApplicationController::class, 'viewDocument'])->name('applications.document.view'); // Pregled dokumenta
    Route::get('/applications/{application}/documents/{document}/download', [ApplicationController::class, 'downloadDocument'])->name('applications.document.download'); // Download dokumenta
    Route::delete('/applications/{application}/documents/{document}', [ApplicationController::class, 'destroyDocument'])->name('applications.document.destroy'); // Brisanje dokumenta
    Route::get('/applications/{application}/status', [ApplicationController::class, 'status'])->name('applications.status'); // Prikaz statusa prijave

    // Biznis plan (BusinessPlanController)
    Route::get('/applications/{application}/business-plan', [BusinessPlanController::class, 'create'])->name('applications.business-plan.create'); // Prikaz forme za biznis plan
    Route::post('/applications/{application}/business-plan', [BusinessPlanController::class, 'store'])->name('applications.business-plan.store'); // Snimi biznis plan

    // Evaluacija prijava (EvaluationController, dostupno komisiji/evaluatorima)
    Route::middleware('role:komisija')->group(function () {
        Route::prefix('evaluation')->name('evaluation.')->group(function () {
            Route::get('/', [EvaluationController::class, 'index'])->name('index');
            Route::post('/applications/{application}', [EvaluationController::class, 'store'])->name('store');
            Route::get('/applications/{application}/show', [EvaluationController::class, 'show'])->name('show');
            // Rute za predsjednika komisije
            Route::post('/applications/{application}/decision', [EvaluationController::class, 'storeDecision'])->name('store-decision');
            Route::post('/applications/{application}/sign', [EvaluationController::class, 'signDecision'])->name('sign-decision');
        });
    });

    // Ruta za pregled ocjenjivanja - dostupna i podnosiocu prijave kada je prijava odbijena
    Route::get('/evaluation/applications/{application}', [EvaluationController::class, 'create'])->name('evaluation.create');

    // Stara ruta za evaluatore (ako postoji)
    Route::middleware('role:evaluator')->group(function () {
        Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index'); // Prikaz svih prijava za bodovanje
        Route::post('/evaluations/{application}/score', [EvaluationController::class, 'score'])->name('evaluations.score'); // Unos bodova
        Route::post('/evaluations/{application}/comment', [EvaluationController::class, 'comment'])->name('evaluations.comment'); // Unos komentara
    });

    // Ugovori (ContractController)
    Route::get('/contracts/{application}/generate', [ContractController::class, 'generate'])->name('contracts.generate'); // Generisanje ugovora
    Route::post('/contracts/{application}', [ContractController::class, 'store'])->name('contracts.store'); // Kreiranje ugovora
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show'); // Prikaz ugovora
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download'); // Download ugovora
    Route::post('/contracts/{contract}/upload', [ContractController::class, 'upload'])->name('contracts.upload'); // Upload potpisanog ugovora
    Route::middleware('role:admin')->post('/contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve'); // Potvrda ugovora (admin)

    // Izvještaji o realizaciji (ReportController)
    Route::get('/applications/{application}/report', [ReportController::class, 'create'])->name('reports.create'); // Prikaz forme za izvještaj o realizaciji
    Route::post('/applications/{application}/report', [ReportController::class, 'store'])->name('reports.store'); // Snimi izvještaj o realizaciji
    Route::get('/applications/{application}/report/financial', [ReportController::class, 'createFinancial'])->name('reports.create-financial'); // Prikaz forme za finansijski izvještaj
    Route::post('/applications/{application}/report/financial', [ReportController::class, 'storeFinancial'])->name('reports.store-financial'); // Snimi finansijski izvještaj
    Route::post('/reports/{report}/upload', [ReportController::class, 'upload'])->name('reports.upload'); // Upload dokaza realizacije
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download'); // Download izvještaja
    Route::middleware('role:admin')->post('/reports/{report}/evaluate', [ReportController::class, 'evaluate'])->name('reports.evaluate'); // Ocjena izvještaja

    // Obavještenja (NotificationController)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index'); // Prikaz obavještenja korisniku
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send'); // Slanje obavještenja

    // Admin rute (dostupne superadmin, admin i konkurs_admin ulogama)
    Route::middleware('role:admin,konkurs_admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        });
    });

    // Admin rute za pregled prijave (dostupne admin, konkurs_admin i komisija ulogama)
    Route::middleware('role:admin,konkurs_admin,komisija')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            // Dozvoli pregled pojedinačne prijave i administratoru konkursa i članovima komisije
            Route::get('/applications/{application}', [AdminController::class, 'showApplication'])->name('applications.show');
        });
    });

    // Admin rute (dostupne samo superadmin i admin ulogama)
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            // Upravljanje korisnicima (samo admin i superadmin)
            Route::get('/users', [AdminController::class, 'users'])->name('users.index');
            Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
            Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
            Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
            Route::post('/users/{user}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
            Route::post('/users/{user}/deactivate', [AdminController::class, 'deactivateUser'])->name('users.deactivate');

            // Pregled prijava (samo admin i superadmin)
            Route::get('/applications', [AdminController::class, 'applications'])->name('applications.index');
        });
    });

    // Rute za upravljanje konkursima (samo za admin i konkurs_admin - kreiranje, editovanje, brisanje)
    // OVE RUTE MORAJU BITI PRVO zbog redosleda match-ovanja (specifičnije rute pre opštijih)
    Route::middleware('role:admin,konkurs_admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/competitions/create', [AdminController::class, 'createCompetition'])->name('competitions.create');
            Route::post('/competitions', [AdminController::class, 'storeCompetition'])->name('competitions.store');
            Route::get('/competitions/{competition}/edit', [AdminController::class, 'editCompetition'])->name('competitions.edit');
            Route::put('/competitions/{competition}', [AdminController::class, 'updateCompetition'])->name('competitions.update');
            Route::post('/competitions/{competition}/publish', [AdminController::class, 'publishCompetition'])->name('competitions.publish');
            Route::delete('/competitions/{competition}', [AdminController::class, 'destroyCompetition'])->name('competitions.destroy');
        });
    });

    // Rute za upravljanje konkursima (dostupne superadmin, admin, konkurs_admin i komisija ulogama)
    Route::middleware('role:admin,konkurs_admin,komisija')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            // Upravljanje konkursima
            Route::get('/competitions', [AdminController::class, 'competitions'])->name('competitions.index');
            Route::get('/competitions/{competition}', [AdminController::class, 'showCompetition'])->name('competitions.show');

            // Zatvaranje konkursa (dostupno i predsjedniku komisije)
            Route::post('/competitions/{competition}/close', [AdminController::class, 'closeCompetition'])->name('competitions.close');

            // Rang lista
            Route::get('/competitions/{competition}/ranking', [AdminController::class, 'rankingList'])->name('competitions.ranking');
            Route::post('/competitions/{competition}/winners', [AdminController::class, 'selectWinners'])->name('competitions.select-winners');
            Route::get('/competitions/{competition}/decision', [AdminController::class, 'generateDecision'])->name('competitions.decision');
        });
    });

    // Rute za upravljanje komisijom za konkurse (dostupno i konkurs_admin roli)
    Route::middleware('role:admin,konkurs_admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions.index');
            Route::get('/commissions/create', [AdminController::class, 'createCommission'])->name('commissions.create');
            Route::post('/commissions', [AdminController::class, 'storeCommission'])->name('commissions.store');
            Route::get('/commissions/{commission}', [AdminController::class, 'showCommission'])->name('commissions.show');
            Route::get('/commissions/{commission}/edit', [AdminController::class, 'editCommission'])->name('commissions.edit');
            Route::put('/commissions/{commission}', [AdminController::class, 'updateCommission'])->name('commissions.update');
            Route::delete('/commissions/{commission}', [AdminController::class, 'destroyCommission'])->name('commissions.destroy');
            Route::post('/commissions/{commission}/members', [AdminController::class, 'addCommissionMember'])->name('commissions.members.add');
            Route::get('/commissions/members/{member}/sign', [AdminController::class, 'signDeclarations'])->name('commissions.members.sign');
            Route::post('/commissions/members/{member}/sign', [AdminController::class, 'storeDeclarations'])->name('commissions.members.store-declarations');
            Route::post('/commissions/members/{member}/status', [AdminController::class, 'updateMemberStatus'])->name('commissions.members.update-status');
            Route::delete('/commissions/members/{member}', [AdminController::class, 'deleteMember'])->name('commissions.members.delete');
        });
    });
});

// Ako želiš javno dostupne rute za prikaz konkursa/tendera (bez prijave), možeš ih dodati ovdje:
// Route::get('/competitions', [CompetitionsController::class, 'publicIndex'])->name('competitions.public'); // Javni prikaz konkursa
// Route::get('/tenders', [TendersController::class, 'publicIndex'])->name('tenders.public'); // Javni prikaz tendera

// Sve rute su detaljno iskomentarisane radi lakšeg održavanja i daljeg razvoja.
// Svaka ruta ima objašnjenje i jasno je kojoj funkcionalnosti pripada.
// Ako budeš dodavao još modula, nastavi sa ovim principom grupisanja i komentarisanja!
