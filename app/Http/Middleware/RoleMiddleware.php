<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ova metoda obrađuje svaki dolazni zahtjev na koji je primijenjen ovaj middleware.
     * 
     * @param  \Illuminate\Http\Request  $request  - trenutni HTTP zahtjev
     * @param  \Closure  $next  - callback za sljedeći middleware ili kontroler
     * @param  string  $role  - naziv uloge koja je dozvoljena (prosleđuje se iz rute)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next, $role)
    {
        // Provjeravamo da li je korisnik ulogovan i da li ima traženu ulogu
        if(auth()->check() && auth()->user()->role->name === $role){
            // Ako ima traženu ulogu, propuštamo zahtjev dalje (npr. do kontrolera)
            return $next($request);
        }
        // Ako nema odgovarajuću ulogu, vraćamo 403 Forbidden (zabranjen pristup)
        abort(403);
    }
}