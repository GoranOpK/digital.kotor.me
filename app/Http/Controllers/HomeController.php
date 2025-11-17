<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * HomeController
 * 
 * Kontroler za upravljanje osnovnim stranicama aplikacije:
 * - Početna stranica
 * - Login i registracija forme
 * - Dashboard za prijavljene korisnike
 */
class HomeController extends Controller
{
    /**
     * Prikaz početne stranice (landing page)
     * 
     * Ova metoda prikazuje javnu početnu stranicu sajta.
     * Dostupna je svim korisnicima bez prijave.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Prikaz forme za prijavu (login)
     * 
     * Prikazuje formu gdje korisnik unosi email i lozinku.
     * 
     * @return \Illuminate\View\View
     */
    public function loginForm()
    {
        return view('auth.login');
    }

    /**
     * Obrada login zahtjeva
     * 
     * Prima podatke iz login forme (email, password),
     * validira ih i pokušava autentifikaciju korisnika.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa login podacima
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // TODO: Implementirati login logiku
        // 1. Validirati email i password
        // 2. Pokušati auth()->attempt()
        // 3. Preusmjeriti na dashboard ili vratiti grešku
    }

    /**
     * Prikaz forme za registraciju
     * 
     * Prikazuje formu gdje novi korisnik unosi svoje podatke
     * (ime, email, lozinka, potvrda lozinke).
     * 
     * @return \Illuminate\View\View
     */
    public function registerForm()
    {
        return view('auth.register');
    }

    /**
     * Obrada registracije novog korisnika
     * 
     * Prima podatke iz registracione forme, validira ih,
     * kreira novog korisnika i automatski ga prijavljuje.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa podacima za registraciju
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        // TODO: Implementirati registraciju
        // 1. Validirati podatke (ime, email, password)
        // 2. Kreirati novog korisnika (User::create())
        // 3. Dodijeliti defaultnu rolu
        // 4. Prijaviti korisnika (auth()->login())
        // 5. Preusmjeriti na dashboard
    }

    /**
     * Prikaz dashboard-a (kontrolna tabla korisnika)
     * 
     * Prikazuje osobnu stranicu korisnika nakon prijave.
     * Ovdje korisnik vidi svoje aktivnosti, obavještenja i opcije.
     * Dostupno samo prijavljenim korisnicima (middleware: auth).
     * 
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        return view('dashboard');
    }
}