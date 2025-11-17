<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Kontroler za upravljanje konkursima.
 * 
 * Ovaj kontroler omogućava prikazivanje liste konkursa, detalja pojedinačnog konkursa
 * i obradu prijava na konkurs od strane korisnika.
 */
class CompetitionsController extends Controller
{
    /**
     * Prikaz liste svih aktivnih konkursa.
     * 
     * Prikazuje pregled svih dostupnih konkursa koje korisnici mogu vidjeti i prijaviti se.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Prikaz liste konkursa
        return view('competitions.index');
    }

    /**
     * Prikaz detaljnih informacija o određenom konkursu.
     * 
     * Prikazuje sve relevantne informacije o konkursu uključujući uslove,
     * kriterijume, rokove i sl.
     * 
     * @param  int  $id  - ID konkursa koji se prikazuje
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Prikaz detalja konkursa
        return view('competitions.show');
    }

    /**
     * Obrada prijave korisnika na konkurs.
     * 
     * Prima podatke iz forme prijave i kreira novu aplikaciju za konkurs.
     * 
     * @param  \Illuminate\Http\Request  $request  - HTTP zahtjev sa podacima o prijavi
     * @return mixed
     */
    public function apply(Request $request)
    {
        // Prijava na konkurs
    }
}