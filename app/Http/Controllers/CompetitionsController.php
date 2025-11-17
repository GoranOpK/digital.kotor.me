<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * CompetitionsController
 * 
 * Kontroler za upravljanje konkursima za žensko i omladinsko preduzetništvo.
 * Omogućava:
 * - Pregled liste konkursa
 * - Prikaz detalja pojedinog konkursa
 * - Podnošenje prijave na konkurs
 */
class CompetitionsController extends Controller
{
    /**
     * Prikaz liste svih konkursa
     * 
     * Prikazuje listu aktivnih i zatvorenih konkursa.
     * Korisnik može vidjeti naziv, tip, rok i status konkursa.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // TODO: Dohvatiti konkurse iz baze
        // $competitions = Competition::all();
        // return view('competitions.index', compact('competitions'));
        return view('competitions.index');
    }

    /**
     * Prikaz detalja pojedinog konkursa
     * 
     * Prikazuje sve informacije o konkursu:
     * - Naslov i opis
     * - Uslove učešća
     * - Kriterijume evaluacije
     * - Prioritetne oblasti
     * - Deadline za prijavu
     * 
     * @param int $id - ID konkursa
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // TODO: Dohvatiti konkurs iz baze
        // $competition = Competition::findOrFail($id);
        // return view('competitions.show', compact('competition'));
        return view('competitions.show');
    }

    /**
     * Podnošenje prijave na konkurs
     * 
     * Prima podatke iz forme za prijavu:
     * - Biznis plan
     * - Tip aplikacije (žensko/omladinsko preduzetništvo)
     * - Upload dokumenta (CV, certifikati)
     * 
     * Validira podatke, kreira Application zapis u bazi
     * i šalje obavještenje korisniku.
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa podacima prijave
     * @return \Illuminate\Http\RedirectResponse
     */
    public function apply(Request $request)
    {
        // TODO: Implementirati podnošenje prijave
        // 1. Validirati podatke (competition_id, business_plan, type)
        // 2. Kreirati Application zapis
        // 3. Upload-ovati dokumente ako ih ima
        // 4. Poslati obavještenje korisniku
        // 5. Preusmjeriti na stranicu sa potvrdom
    }
}