<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Ažuriraj osnovne podatke
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name . ' ' . $request->last_name;
        $user->phone = $request->phone;
        $user->address = $request->address;

        // Ažuriraj tip korisnika
        if ($request->has('user_type')) {
            $user->user_type = $request->user_type;
        }

        // Ažuriraj status rezidentnosti
        if ($request->has('residential_status')) {
            $user->residential_status = $request->residential_status;
        }

        // Ažuriraj JMB (za fizička lica)
        if ($request->has('jmb')) {
            $user->jmb = $request->jmb;
        }

        // Ažuriraj PIB (za pravna lica)
        if ($request->has('pib')) {
            $user->pib = $request->pib;
        }

        // Ažuriraj broj pasoša (za nerezidente)
        if ($request->has('passport_number')) {
            $user->passport_number = $request->passport_number ? strtoupper($request->passport_number) : null;
        }

        // Ažuriraj email ako je promijenjen
        if ($user->email !== $request->email) {
            $user->email = strtolower($request->email);
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('dashboard')->with('success', 'Profil je uspješno ažuriran.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.edit')->with('success', 'Lozinka je uspješno promijenjena.');
    }
}
