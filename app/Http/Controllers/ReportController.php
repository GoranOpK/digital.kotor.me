<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * ReportController
 * 
 * Kontroler za upravljanje izvještajima o realizaciji projekata.
 * Nakon što korisnik dobije sredstva, mora podnijeti izvještaje o napretku.
 * Omogućava:
 * - Kreiranje izvještaja
 * - Upload dokaza realizacije (fotografije, računi, fakture)
 * - Admin evaluaciju i ocjenu izvještaja
 */
class ReportController extends Controller
{
    /**
     * Prikaz forme za kreiranje izvještaja
     * 
     * Prikazuje formu gdje korisnik unosi:
     * - Opis realizovanih aktivnosti
     * - Postignute rezultate
     * - Eventualne probleme i izazove
     * 
     * @param int $application_id - ID prijave za koju se podnosi izvještaj
     * @return \Illuminate\View\View
     */
    public function create($application_id)
    {
        // TODO: Implementirati prikaz forme
        // $application = Application::findOrFail($application_id);
        // Provjeriti da korisnik ima pravo (vlasnik prijave)
        // return view('reports.create', compact('application'));
    }

    /**
     * Snimanje izvještaja u bazu
     * 
     * Prima podatke iz forme i kreira zapis u bazi.
     * Postavlja status na 'submitted' i šalje obavještenje adminu.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa podacima izvještaja
     * @param int $application_id - ID prijave
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $application_id)
    {
        // TODO: Implementirati snimanje izvještaja
        // 1. Validirati podatke (description obavezan)
        // 2. Kreirati Report zapis
        // 3. Postaviti status na 'submitted'
        // 4. Poslati obavještenje adminu
        // 5. Preusmjeriti sa porukom uspjeha
    }

    /**
     * Upload dokaza realizacije
     * 
     * Omogućava upload fotografija, računa, faktura i drugih dokumenata
     * koji dokazuju realizaciju projekta.
     * 
     * Prihvata: PDF, JPG, PNG, do 10MB po fajlu
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa uploadovanim fajlom
     * @param int $report_id - ID izvještaja
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request, $report_id)
    {
        // TODO: Implementirati upload dokumenata
        // 1. Validirati fajl (tip, veličina)
        // 2. Sačuvati fajl na storage
        // 3. Ažurirati Report sa document_file putanjom
        // 4. Vratiti JSON sa porukom uspjeha
    }

    /**
     * Evaluacija i ocjena izvještaja (samo za admin)
     * 
     * Admin pregledava izvještaj i dokaze, te odlučuje:
     * - Approved - izvještaj prihvaćen
     * - Rejected - izvještaj odbijen, potrebne dodatne informacije
     * 
     * Može ostaviti komentar sa obrazloženjem.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa ocjenom i komentarom
     * @param int $report_id - ID izvještaja
     * @return \Illuminate\Http\RedirectResponse
     */
    public function evaluate(Request $request, $report_id)
    {
        // TODO: Implementirati evaluaciju
        // 1. Dohvatiti izvještaj (Report::findOrFail($report_id))
        // 2. Validirati status i komentar
        // 3. Ažurirati status izvještaja
        // 4. Poslati obavještenje korisniku
        // 5. Preusmjeriti sa porukom
    }
}