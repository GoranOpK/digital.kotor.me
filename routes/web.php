<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\CompetitionsController;
use App\Http\Controllers\TendersController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BusinessPlanController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocumentController;

// Učitaj auth rute (za email verifikaciju i sl.)
require __DIR__.'/auth.php';

// Početna stranica (landing/home)
Route::get('/', [HomeController::class, 'index'])->name('home'); // Prikaz početne strane

// Rute za autentikaciju (login/register) - koristi Breeze, Fortify ili custom rješenje
Route::get('/login', [HomeController::class, 'loginForm'])->name('login'); // Forma za login
Route::post('/login', [HomeController::class, 'login']); // Slanje login podataka
Route::get('/register', [HomeController::class, 'registerForm'])->name('register'); // Forma za registraciju
Route::post('/register', [HomeController::class, 'register']); // Slanje podataka za registraciju

// Grupe ruta dostupne samo prijavljenim korisnicima
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - profil korisnika nakon prijave
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard'); // Prikaz korisničkog panela

    // Profil korisnika
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Biblioteka dokumenata
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Modul za online plaćanja opštinskih prihoda
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index'); // Prikaz forme i istorije uplata
    Route::post('/payments/pay', [PaymentsController::class, 'pay'])->name('payments.pay'); // Slanje zahtjeva za uplatu

    // Modul za konkurse (žensko/omladinsko preduzetništvo)
    Route::get('/competitions', [CompetitionsController::class, 'index'])->name('competitions.index'); // Lista konkursa
    Route::get('/competitions/{competition}', [CompetitionsController::class, 'show'])->name('competitions.show'); // Detalji konkursa

    // Modul za tendersku dokumentaciju
    Route::get('/tenders', [TendersController::class, 'index'])->name('tenders.index'); // Lista tendera
    Route::get('/tenders/{id}', [TendersController::class, 'show'])->name('tenders.show'); // Detalji tendera
    Route::post('/tenders/purchase', [TendersController::class, 'purchase'])->name('tenders.purchase'); // Otkup tenderske dokumentacije

    // --- DOPUNA: RUTE ZA PORTAL ŽENSKOG PREDUZETNIŠTVA ---

    // Prijava na konkurs (ApplicationController)
    Route::get('/competitions/{competition}/apply', [ApplicationController::class, 'create'])->name('applications.create'); // Prikaz forme za prijavu
    Route::post('/competitions/{competition}/apply', [ApplicationController::class, 'store'])->name('applications.store'); // Snimi prijavu
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show'); // Prikaz detalja prijave
    Route::post('/applications/{application}/submit', [ApplicationController::class, 'submit'])->name('applications.final-submit'); // Konačno podnošenje prijave
    Route::post('/applications/{application}/upload', [ApplicationController::class, 'uploadDocument'])->name('applications.upload'); // Upload dokumenata
    Route::get('/applications/{application}/documents/{document}/download', [ApplicationController::class, 'downloadDocument'])->name('applications.document.download'); // Download dokumenta
    Route::get('/applications/{application}/status', [ApplicationController::class, 'status'])->name('applications.status'); // Prikaz statusa prijave

    // Biznis plan (BusinessPlanController)
    Route::get('/applications/{application}/business-plan', [BusinessPlanController::class, 'create'])->name('applications.business-plan.create'); // Prikaz forme za biznis plan
    Route::post('/applications/{application}/business-plan', [BusinessPlanController::class, 'store'])->name('applications.business-plan.store'); // Snimi biznis plan

    // Evaluacija prijava (EvaluationController, dostupno komisiji/evaluatorima)
    Route::middleware('role:komisija')->group(function () {
        Route::prefix('evaluation')->name('evaluation.')->group(function () {
            Route::get('/', [EvaluationController::class, 'index'])->name('index');
            Route::get('/applications/{application}', [EvaluationController::class, 'create'])->name('create');
            Route::post('/applications/{application}', [EvaluationController::class, 'store'])->name('store');
            Route::get('/applications/{application}/show', [EvaluationController::class, 'show'])->name('show');
        });
    });
    
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
            // Dozvoli pregled pojedinačne prijave i administratoru konkursa
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

    // Rute za upravljanje konkursima (dostupne superadmin, admin i konkurs_admin ulogama)
    Route::middleware('role:admin,konkurs_admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            // Upravljanje konkursima
            Route::get('/competitions', [AdminController::class, 'competitions'])->name('competitions.index');
            Route::get('/competitions/create', [AdminController::class, 'createCompetition'])->name('competitions.create');
            Route::post('/competitions', [AdminController::class, 'storeCompetition'])->name('competitions.store');
            Route::get('/competitions/{competition}', [AdminController::class, 'showCompetition'])->name('competitions.show');
            Route::get('/competitions/{competition}/edit', [AdminController::class, 'editCompetition'])->name('competitions.edit');
            Route::put('/competitions/{competition}', [AdminController::class, 'updateCompetition'])->name('competitions.update');
            Route::post('/competitions/{competition}/publish', [AdminController::class, 'publishCompetition'])->name('competitions.publish');
            Route::post('/competitions/{competition}/close', [AdminController::class, 'closeCompetition'])->name('competitions.close');
            Route::delete('/competitions/{competition}', [AdminController::class, 'destroyCompetition'])->name('competitions.destroy');
            
            // Rang lista
            Route::get('/competitions/{competition}/ranking', [AdminController::class, 'rankingList'])->name('competitions.ranking');
            Route::post('/competitions/{competition}/winners', [AdminController::class, 'selectWinners'])->name('competitions.select-winners');
            Route::get('/competitions/{competition}/decision', [AdminController::class, 'generateDecision'])->name('competitions.decision');
            
            // Upravljanje komisijom za konkurse (dodavanje evaluatora) - dostupno i konkurs_admin roli
            Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions.index');
            Route::get('/commissions/create', [AdminController::class, 'createCommission'])->name('commissions.create');
            Route::post('/commissions', [AdminController::class, 'storeCommission'])->name('commissions.store');
            Route::get('/commissions/{commission}', [AdminController::class, 'showCommission'])->name('commissions.show');
            Route::get('/commissions/{commission}/edit', [AdminController::class, 'editCommission'])->name('commissions.edit');
            Route::put('/commissions/{commission}', [AdminController::class, 'updateCommission'])->name('commissions.update');
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