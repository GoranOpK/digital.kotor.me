<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * ContractController
 * 
 * Kontroler za upravljanje ugovorima između Opštine i dobitnika konkursa.
 * Omogućava:
 * - Generisanje ugovora nakon odobravanja prijave
 * - Pregled i preuzimanje ugovora
 * 
 * Napomena: Ove funkcije su dostupne samo adminu (middleware: role:admin)
 */
class ContractController extends Controller
{
    /**
     * Generisanje ugovora za odobrenu prijavu
     * 
     * Kreira PDF dokument sa ugovorom na osnovu podataka iz prijave.
     * Ugovor sadrži:
     * - Podatke o dobitniku
     * - Opis projekta
     * - Iznos sredstava
     * - Uslove realizacije i izvještavanja
     * 
     * @param int $application_id - ID prijave za koju se generiše ugovor
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate($application_id)
    {
        // TODO: Implementirati generisanje ugovora
        // 1. Dohvatiti prijavu (Application::findOrFail($application_id))
        // 2. Provjeriti da je prijava odobrena (status: 'approved')
        // 3. Generisati PDF ugovor (koristeći biblioteku kao dompdf ili snappy)
        // 4. Sačuvati Contract zapis u bazi
        // 5. Poslati email korisniku sa ugovorom
        // 6. Preusmjeriti na stranicu sa uspješnom porukom
    }

    /**
     * Prikaz i preuzimanje ugovora
     * 
     * Omogućava adminu i korisniku da pregledaju i preuzmu PDF ugovor.
     * Provjerava da korisnik ima pravo pristupa ugovoru.
     * 
     * @param int $contract_id - ID ugovora
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show($contract_id)
    {
        // TODO: Implementirati prikaz ugovora
        // 1. Dohvatiti ugovor (Contract::findOrFail($contract_id))
        // 2. Provjeriti pristup (admin ili vlasnik prijave)
        // 3. Vratiti PDF za pregled ili download
        // return response()->file($contract->contract_file);
    }
}