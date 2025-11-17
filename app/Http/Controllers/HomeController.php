<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Kontroler za osnovne stranice i autentifikaciju.
 * 
 * Ovaj kontroler upravlja prikazom početne stranice, login i registracionih formi,
 * te obradom procesa prijave i registracije korisnika.
 */
class HomeController extends Controller
{
    /**
     * Prikaz početne stranice aplikacije.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Prikaz forme za prijavu (login).
     * 
     * @return \Illuminate\View\View
     */
    public function loginForm()
    {
        return view('auth.login');
    }

    /**
     * Obrada zahtjeva za prijavu korisnika.
     * 
     * @param  \Illuminate\Http\Request  $request  - HTTP zahtjev sa login podacima
     * @return mixed
     */
    public function login(Request $request)
    {
        // login logika
    }

    /**
     * Prikaz forme za registraciju novog korisnika.
     * 
     * @return \Illuminate\View\View
     */
    public function registerForm()
    {
        return view('auth.register');
    }

    /**
     * Obrada zahtjeva za registraciju novog korisnika.
     * 
     * @param  \Illuminate\Http\Request  $request  - HTTP zahtjev sa registracionim podacima
     * @return mixed
     */
    public function register(Request $request)
    {
        // register logika
    }

    /**
     * Prikaz dashboard stranice za ulogovanog korisnika.
     * 
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        return view('dashboard');
    }
}