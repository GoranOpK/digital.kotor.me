<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * ApplicationController
 * 
 * Kontroler za upravljanje prijavama na konkurse.
 * Omogućava korisnicima da:
 * - Popune i podnesu prijavu
 * - Prate status prijave
 * - Upload-uju dodatne dokumente
 * - Pregledaju detalje svoje prijave
 */
class ApplicationController extends Controller
{
    /**
     * Prikaz forme za prijavu na konkurs
     * 
     * Prikazuje detaljnu formu gdje korisnik unosi:
     * - Tip prijave (žensko/omladinsko preduzetništvo)
     * - Biznis plan
     * - Lične podatke
     * 
     * @param int $competition_id - ID konkursa na koji se prijavljuje
     * @return \Illuminate\View\View
     */
    public function create($competition_id)
    {
        // TODO: Implementirati prikaz forme
        // $competition = Competition::findOrFail($competition_id);
        // Provjeriti da je konkurs aktivan
        // return view('applications.create', compact('competition'));
    }

    /**
     * Snimanje prijave u bazu
     * 
     * Prima podatke iz forme i kreira novu prijavu.
     * Postavlja inicijalni status na 'pending' (na čekanju).
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa podacima prijave
     * @param int $competition_id - ID konkursa
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $competition_id)
    {
        // TODO: Implementirati snimanje prijave
        // 1. Validirati podatke (type, business_plan obavezni)
        // 2. Kreirati Application zapis
        // 3. Povezati sa korisnikom (user_id = auth()->id())
        // 4. Postaviti status 'pending'
        // 5. Poslati potvrdu na email
        // 6. Preusmjeriti na stranicu prijave
    }

    /**
     * Prikaz detalja prijave
     * 
     * Prikazuje sve informacije o prijavi:
     * - Status (pending, approved, rejected)
     * - Biznis plan
     * - Uploadovane dokumente
     * - Ocjene evaluatora (ako postoje)
     * - Komentare
     * 
     * @param int $id - ID prijave
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // TODO: Implementirati prikaz prijave
        // $application = Application::findOrFail($id);
        // Provjeriti pristup (vlasnik ili admin)
        // return view('applications.show', compact('application'));
    }

    /**
     * Upload dodatnih dokumenata uz prijavu
     * 
     * Korisnik može upload-ovati:
     * - CV
     * - Sertifikate i diplome
     * - Dozvole za rad
     * - Biznis plan u PDF formatu
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa uploadovanim dokumentom
     * @param int $application_id - ID prijave
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument(Request $request, $application_id)
    {
        // TODO: Implementirati upload dokumenta
        // 1. Validirati fajl (tip: pdf, jpg, png; max: 5MB)
        // 2. Sačuvati fajl u storage/app/documents
        // 3. Kreirati ApplicationDocument zapis
        // 4. Vratiti JSON sa porukom uspjeha
    }

    /**
     * Prikaz statusa prijave
     * 
     * Prikazuje trenutni status prijave i historiju promjena.
     * Korisnik vidi:
     * - Trenutni status
     * - Datum podnošenja
     * - Da li je prijava u fazi evaluacije
     * - Rezultat evaluacije (ako je završena)
     * 
     * @param int $id - ID prijave
     * @return \Illuminate\View\View
     */
    public function status($id)
    {
        // TODO: Implementirati prikaz statusa
        // $application = Application::findOrFail($id);
        // Provjeriti pristup
        // $statusHistory = ... // Dohvatiti historiju promjena
        // return view('applications.status', compact('application', 'statusHistory'));
    }
}