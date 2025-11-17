<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * NotificationController
 * 
 * Kontroler za upravljanje obavještenjima korisnika.
 * Omogućava:
 * - Prikaz obavještenja korisniku
 * - Slanje obavještenja (email, SMS, in-app)
 * - Označavanje obavještenja kao pročitanih
 * 
 * Obavještenja se šalju za:
 * - Status promjene prijave
 * - Nove konkurse
 * - Rokove za izvještavanje
 * - Sistemske poruke
 */
class NotificationController extends Controller
{
    /**
     * Prikaz obavještenja korisniku
     * 
     * Prikazuje listu svih obavještenja za trenutno prijavljenog korisnika:
     * - Nepročitana obavještenja (bold)
     * - Pročitana obavještenja
     * - Datum i vrijeme slanja
     * - Tip obavještenja (info, success, warning, error)
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // TODO: Implementirati prikaz obavještenja
        // $notifications = auth()->user()->notifications;
        // Sortirati po datumu (najnovija prva)
        // return view('notifications.index', compact('notifications'));
    }

    /**
     * Slanje obavještenja korisnicima
     * 
     * Omogućava adminu da pošalje obavještenje:
     * - Jednom korisniku
     * - Grupi korisnika (po ulozi ili statusu prijave)
     * - Svim korisnicima
     * 
     * Obavještenje se može poslati preko:
     * - Email-a
     * - SMS-a (ako je konfigurisan gateway)
     * - In-app notifikacije (Laravel Notifications)
     * 
     * @param \Illuminate\Http\Request $request - HTTP zahtjev sa podacima obavještenja
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(Request $request)
    {
        // TODO: Implementirati slanje obavještenja
        // 1. Validirati podatke (title, message, recipient_type)
        // 2. Odrediti kome se šalje (user_id, role, ili svi)
        // 3. Kreirati Notification zapise u bazi
        // 4. Poslati email ili SMS ako je odabrano
        // 5. Preusmjeriti sa porukom uspjeha
    }
}