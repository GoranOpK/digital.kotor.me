<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Kontroler za upravljanje ugovorima.
 * 
 * Ovaj kontroler omogućava generisanje ugovora na osnovu prihvaćenih prijava
 * i prikaz/preuzimanje postojećih ugovora.
 */
class ContractController extends Controller
{
    /**
     * Generisanje novog ugovora za odobrenu prijavu.
     * 
     * Kreira ugovor na osnovu podataka iz prijave (application) koja je prošla
     * proces odobravanja i evaluacije.
     * 
     * @param  int  $application_id  - ID prijave za koju se generiše ugovor
     * @return mixed
     */
    public function generate($application_id)
    {
        // Generisanje ugovora
    }

    /**
     * Prikaz ili preuzimanje postojećeg ugovora.
     * 
     * Omogućava korisniku da pregleda ili preuzme PDF verziju
     * potpisanog ili nepotpisanog ugovora.
     * 
     * @param  int  $contract_id  - ID ugovora koji se prikazuje/preuzima
     * @return mixed
     */
    public function show($contract_id)
    {
        // Prikaz/download ugovora
    }
}