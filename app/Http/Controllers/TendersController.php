<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * TendersController
 * 
 * Kontroler za upravljanje tenderima i tenderskom dokumentacijom.
 * Omogućava:
 * - Pregled liste tendera
 * - Prikaz detalja tendera
 * - Otkup/preuzimanje tenderske dokumentacije
 */
class TendersController extends Controller
{
    /**
     * Prikaz liste tendera
     * 
     * Prikazuje sve aktivne i prošle tendere.
     * Korisnici mogu vidjeti naziv, rok, status i cijenu dokumentacije.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // TODO: Dohvatiti tendere iz baze
        // $tenders = Tender::all();
        // return view('tenders.index', compact('tenders'));
        return view('tenders.index');
    }

    /**
     * Prikaz detalja pojedinog tendera
     * 
     * Prikazuje sve informacije o tenderu:
     * - Naziv i opis posla
     * - Tehnička specifikacija
     * - Rok za podnošenje ponuda
     * - Uslove učešća
     * - Način preuzimanja dokumentacije
     * 
     * @param int $id - ID tendera
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // TODO: Dohvatiti tender iz baze
        // $tender = Tender::findOrFail($id);
        // return view('tenders.show', compact('tender'));
        return view('tenders.show');
    }

    /**
     * Otkup/preuzimanje tenderske dokumentacije
     * 
     * Omogućava korisnicima da plate i preuzmu tendersku dokumentaciju.
     * Proces:
     * 1. Korisnik odabire tender i plaća naknadu
     * 2. Nakon plaćanja, dobija pristup dokumentaciji
     * 3. Može preuzeti PDF ili ZIP sa svim fajlovima
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa ID tendera
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function purchase(Request $request)
    {
        // TODO: Implementirati otkup dokumentacije
        // 1. Validirati tender_id i payment_method
        // 2. Procesuirati plaćanje
        // 3. Kreirati Purchase zapis u bazi
        // 4. Omogućiti download dokumentacije
        // 5. Poslati potvrdu na email
    }
}