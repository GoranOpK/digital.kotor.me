<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * PaymentsController
 * 
 * Kontroler za online plaćanja opštinskih prihoda.
 * Omogućava građanima da plate različite opštinske takse i pristojbe:
 * - Komunalne takse
 * - Boravišna pristojba
 * - Druge administrativne pristojbe
 */
class PaymentsController extends Controller
{
    /**
     * Prikaz forme za plaćanje i istorija uplata
     * 
     * Prikazuje:
     * - Formu za odabir tipa plaćanja i unos iznosa
     * - Istoriju prethodnih uplata korisnika
     * - Dostupne načine plaćanja (kartica, ebanking)
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // TODO: Dohvatiti istoriju plaćanja korisnika
        // $payments = Payment::where('user_id', auth()->id())->get();
        // return view('payments.index', compact('payments'));
        return view('payments.index');
    }

    /**
     * Procesuiranje plaćanja
     * 
     * Prima podatke iz forme i procesira plaćanje kroz payment gateway.
     * Koraci:
     * 1. Validacija podataka (iznos, tip plaćanja, metod)
     * 2. Kreiranje transakcije u bazi
     * 3. Preusmjeravanje na payment gateway (npr. Stripe, PayPal, lokalna banka)
     * 4. Callback nakon uspješnog plaćanja
     * 5. Generisanje potvrde/računa
     * 6. Slanje email potvrde
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa podacima o plaćanju
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pay(Request $request)
    {
        // TODO: Implementirati logiku plaćanja
        // 1. Validirati podatke (amount, type, payment_method)
        // 2. Kreirati Payment zapis (status: 'pending')
        // 3. Integracija sa payment gateway-om
        // 4. Preusmjeriti na gateway ili prikazati potvrdu
    }
}