<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules\Password;

class HomeController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // login logika
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'user_type' => ['required', 'in:Fizičko lice,Registrovan privredni subjekt'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'email_confirmation' => ['required', 'same:email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone_full' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today'],
        ], [
            'user_type.required' => 'Vrsta korisnika je obavezna.',
            'user_type.in' => 'Izaberite validnu vrstu korisnika.',
            'first_name.required' => 'Ime je obavezno.',
            'first_name.max' => 'Ime ne može biti duže od 255 karaktera.',
            'last_name.required' => 'Prezime je obavezno.',
            'last_name.max' => 'Prezime ne može biti duže od 255 karaktera.',
            'email.required' => 'E-mail adresa je obavezna.',
            'email.email' => 'Unesite validnu e-mail adresu.',
            'email.unique' => 'E-mail adresa je već registrovana.',
            'email_confirmation.required' => 'Potvrda e-mail adrese je obavezna.',
            'email_confirmation.same' => 'E-mail adrese se ne poklapaju.',
            'password.required' => 'Lozinka je obavezna.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'phone_full.required' => 'Broj telefona je obavezan.',
            'date_of_birth.required' => 'Datum rođenja je obavezan.',
            'date_of_birth.date' => 'Unesite validan datum rođenja.',
            'date_of_birth.before' => 'Datum rođenja mora biti u prošlosti.',
        ]);

        // Capitalize prvo slovo imena i prezimena
        $validated['first_name'] = ucfirst(mb_strtolower($validated['first_name'], 'UTF-8'));
        $validated['last_name'] = ucfirst(mb_strtolower($validated['last_name'], 'UTF-8'));

        // Pronađi ulogu "prijavitelj" ili kreiraj ako ne postoji
        $role = Role::firstOrCreate(
            ['name' => 'prijavitelj'],
            ['display_name' => 'Prijavitelj']
        );

        // Kreiranje korisnika
        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'user_type' => $validated['user_type'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone_full'],
            'date_of_birth' => $validated['date_of_birth'],
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id, // Default uloga: prijavitelj
        ]);

        // Slanje email verifikacije
        event(new Registered($user));

        // Automatska prijava nakon registracije
        Auth::login($user);

        return redirect()->route('verification.notice')->with('status', 'registration-success');
    }

    public function dashboard()
    {
        return view('dashboard');
    }
}