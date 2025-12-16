<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\CompetitionsController;
use App\Http\Controllers\TendersController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;

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

    // Modul za online plaćanja opštinskih prihoda
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index'); // Prikaz forme i istorije uplata
    Route::post('/payments/pay', [PaymentsController::class, 'pay'])->name('payments.pay'); // Slanje zahtjeva za uplatu

    // Modul za konkurse (žensko/omladinsko preduzetništvo)
    Route::get('/competitions', [CompetitionsController::class, 'index'])->name('competitions.index'); // Lista konkursa
    Route::get('/competitions/{id}', [CompetitionsController::class, 'show'])->name('competitions.show'); // Detalji konkursa
    Route::post('/competitions/apply', [CompetitionsController::class, 'apply'])->name('competitions.apply'); // Prijava na konkurs

    // Modul za tendersku dokumentaciju
    Route::get('/tenders', [TendersController::class, 'index'])->name('tenders.index'); // Lista tendera
    Route::get('/tenders/{id}', [TendersController::class, 'show'])->name('tenders.show'); // Detalji tendera
    Route::post('/tenders/purchase', [TendersController::class, 'purchase'])->name('tenders.purchase'); // Otkup tenderske dokumentacije

    // --- DOPUNA: RUTE ZA PORTAL ŽENSKOG PREDUZETNIŠTVA ---

    // Prijava na konkurs (ApplicationController)
    Route::get('/competitions/{competition}/apply', [ApplicationController::class, 'create'])->name('applications.create'); // Prikaz forme za prijavu
    Route::post('/competitions/{competition}/apply', [ApplicationController::class, 'store'])->name('applications.store'); // Snimi prijavu
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show'); // Prikaz detalja prijave
    Route::post('/applications/{application}/upload', [ApplicationController::class, 'uploadDocument'])->name('applications.upload'); // Upload dokumenata
    Route::get('/applications/{application}/status', [ApplicationController::class, 'status'])->name('applications.status'); // Prikaz statusa prijave

    // Evaluacija prijava (EvaluationController, dostupno komisiji/evaluatorima)
    Route::middleware('role:evaluator')->group(function () {
        Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index'); // Prikaz svih prijava za bodovanje
        Route::post('/evaluations/{application}/score', [EvaluationController::class, 'score'])->name('evaluations.score'); // Unos bodova
        Route::post('/evaluations/{application}/comment', [EvaluationController::class, 'comment'])->name('evaluations.comment'); // Unos komentara
    });

    // Ugovori (ContractController, dostupno adminu)
    Route::middleware('role:admin')->group(function () {
        Route::get('/contracts/{application}/generate', [ContractController::class, 'generate'])->name('contracts.generate'); // Generisanje ugovora
        Route::get('/contracts/{contract}/show', [ContractController::class, 'show'])->name('contracts.show'); // Prikaz/download ugovora
    });

    // Izvještaji o realizaciji (ReportController)
    Route::get('/applications/{application}/report', [ReportController::class, 'create'])->name('reports.create'); // Prikaz forme za izvještaj
    Route::post('/applications/{application}/report', [ReportController::class, 'store'])->name('reports.store'); // Snimi izvještaj
    Route::post('/reports/{report}/upload', [ReportController::class, 'upload'])->name('reports.upload'); // Upload dokaza realizacije
    Route::middleware('role:admin')->post('/reports/{report}/evaluate', [ReportController::class, 'evaluate'])->name('reports.evaluate'); // Ocjena izvještaja

    // Obavještenja (NotificationController)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index'); // Prikaz obavještenja korisniku
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send'); // Slanje obavještenja

    // Admin rute (dostupne superadmin i admin ulogama)
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
            
            // Upravljanje korisnicima
            Route::get('/users', [AdminController::class, 'users'])->name('users.index');
            Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
            Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
            Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
            Route::post('/users/{user}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
            Route::post('/users/{user}/deactivate', [AdminController::class, 'deactivateUser'])->name('users.deactivate');
        });
    });
});

// Ako želiš javno dostupne rute za prikaz konkursa/tendera (bez prijave), možeš ih dodati ovdje:
// Route::get('/competitions', [CompetitionsController::class, 'publicIndex'])->name('competitions.public'); // Javni prikaz konkursa
// Route::get('/tenders', [TendersController::class, 'publicIndex'])->name('tenders.public'); // Javni prikaz tendera

// Sve rute su detaljno iskomentarisane radi lakšeg održavanja i daljeg razvoja.
// Svaka ruta ima objašnjenje i jasno je kojoj funkcionalnosti pripada.
// Ako budeš dodavao još modula, nastavi sa ovim principom grupisanja i komentarisanja!