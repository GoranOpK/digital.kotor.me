<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * EvaluationController
 * 
 * Kontroler za evaluaciju (bodovanje) prijava na konkurse.
 * Dostupan samo korisnicima sa ulogom 'evaluator' ili 'admin'.
 * 
 * Omogućava:
 * - Pregled prijava koje treba ocijeniti
 * - Davanje ocjena po definisanim kriterijumima
 * - Ostavljanje komentara uz ocjenu
 */
class EvaluationController extends Controller
{
    /**
     * Prikaz liste prijava za bodovanje
     * 
     * Prikazuje sve prijave koje evaluator treba ocijeniti.
     * Za svaku prijavu se prikazuje:
     * - Osnovni podaci o aplikantu
     * - Tip prijave
     * - Da li je evaluator već dao ocjenu
     * - Link ka formi za bodovanje
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // TODO: Implementirati prikaz prijava za bodovanje
        // $applications = Application::where('status', 'pending')->get();
        // Za svaką prijavu provjeriti da li trenutni evaluator vec dao ocjenu
        // return view('evaluations.index', compact('applications'));
    }

    /**
     * Unos bodova za prijavu
     * 
     * Evaluator daje ocjenu prijavi po svakom kriterijumu evaluacije.
     * Na primjer:
     * - Inovativnost (max 20 bodova)
     * - Izvodljivost (max 20 bodova)
     * - Održivost (max 15 bodova)
     * - Itd.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa ocjenama
     * @param int $application_id - ID prijave koja se ocjenjuje
     * @return \Illuminate\Http\RedirectResponse
     */
    public function score(Request $request, $application_id)
    {
        // TODO: Implementirati bodovanje
        // 1. Validirati podatke (criteria_id, score)
        // 2. Provjeriti da ocjena ne prelazi max_score za kriterijum
        // 3. Kreirati ApplicationScore zapise
        // 4. Povezati sa evaluatorom (evaluator_id = auth()->id())
        // 5. Izračunati ukupan score ako su svi kriterijumi ocijenjeni
        // 6. Preusmjeriti sa porukom uspjeha
    }

    /**
     * Unos komentara uz ocjenu
     * 
     * Evaluator može ostaviti detaljne komentare uz ocjenu:
     * - Objašnjenje ocjene
     * - Prednosti prijave
     * - Slabosti koje treba poboljšati
     * - Preporuke
     * 
     * Komentar mogu vidjeti samo admin i drugi evaluatori,
     * ne i sam aplikant.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa komentarom
     * @param int $application_id - ID prijave
     * @return \Illuminate\Http\RedirectResponse
     */
    public function comment(Request $request, $application_id)
    {
        // TODO: Implementirati unos komentara
        // 1. Validirati komentar (min 10 karaktera)
        // 2. Ažurirati ApplicationScore zapis sa komentarom
        // 3. Ili kreirati zaseban EvaluationComment model
        // 4. Preusmjeriti sa porukom uspjeha
    }
}