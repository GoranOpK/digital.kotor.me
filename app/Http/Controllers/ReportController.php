<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Kontroler za upravljanje izvještajima realizacije projekata.
 * 
 * Ovaj kontroler omogućava korisnicima da kreiraju izvještaje o napretku,
 * uploaduju dokaze realizacije i omogućava administratorima da ocjenjuju
 * kvalitet izvršenih aktivnosti.
 */
class ReportController extends Controller
{
    /**
     * Prikaz forme za kreiranje izvještaja realizacije.
     * 
     * Prikazuje formu gdje korisnik može unijeti informacije o napretku
     * realizacije projekta za određenu prijavu.
     * 
     * @param  int  $application_id  - ID prijave za koju se kreira izvještaj
     * @return mixed
     */
    public function create($application_id)
    {
        // Prikaz forme za izvještaj
    }

    /**
     * Čuvanje novog izvještaja realizacije u bazu.
     * 
     * Procesira podatke iz forme i kreira novi izvještaj vezan
     * za određenu prijavu.
     * 
     * @param  \Illuminate\Http\Request  $request  - HTTP zahtjev sa podacima izvještaja
     * @param  int  $application_id  - ID prijave za koju se snima izvještaj
     * @return mixed
     */
    public function store(Request $request, $application_id)
    {
        // Snimi izvještaj
    }

    /**
     * Upload dokaza realizacije projekta.
     * 
     * Omogućava korisnicima da upload-uju dokumente (fotografije, fakture, itd.)
     * kao dokaz realizacije aktivnosti navedenih u izvještaju.
     * 
     * @param  \Illuminate\Http\Request  $request  - HTTP zahtjev sa fajlovima dokaza
     * @param  int  $report_id  - ID izvještaja za koji se uploaduju dokazi
     * @return mixed
     */
    public function upload(Request $request, $report_id)
    {
        // Upload dokaza realizacije
    }

    /**
     * Ocjena realizacije projekta od strane evaluatora.
     * 
     * Omogućava administratoru ili evaluatoru da ocijeni kvalitet
     * realizacije projekta na osnovu dostavljenog izvještaja i dokaza.
     * 
     * @param  \Illuminate\Http\Request  $request  - HTTP zahtjev sa ocjenom i komentarima
     * @param  int  $report_id  - ID izvještaja koji se ocjenjuje
     * @return mixed
     */
    public function evaluate(Request $request, $report_id)
    {
        // Ocjena realizacije
    }
}