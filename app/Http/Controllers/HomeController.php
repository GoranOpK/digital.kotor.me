<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Competition;
use App\Models\Application;
use App\Models\Commission;
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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Dozvoli samo aktivne naloge
        $credentials['activation_status'] = 'active';

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Pogrešan email ili lozinka, ili nalog nije aktivan.',
        ])->onlyInput('email');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    /**
     * Validacija JMB (Jedinstvenog matičnog broja)
     */
    private function validateJMB($jmb)
    {
        if (!preg_match('/^[0-9]{13}$/', $jmb)) {
            return false;
        }

        // Izdvajanje delova JMB formata: DDMMGGGRRBBBK
        // Pozicije: 0-1 (DD), 2-3 (MM), 4-6 (GGG), 7-8 (RR), 9-11 (BBB), 12 (K)
        $DD = (int)substr($jmb, 0, 2);      // Dan: pozicije 0-1
        $MM = (int)substr($jmb, 2, 2);      // Mesec: pozicije 2-3
        $GGG = (int)substr($jmb, 4, 3);     // Godina (3 cifre): pozicije 4-6
        $RR = (int)substr($jmb, 7, 2);      // Region: pozicije 7-8
        $BBB = (int)substr($jmb, 9, 3);     // Redni broj: pozicije 9-11
        $K = (int)substr($jmb, 12, 1);      // Kontrolna cifra: pozicija 12

        // Validacija dana (1-31)
        if ($DD < 1 || $DD > 31) {
            return false;
        }

        // Validacija meseca (1-12)
        if ($MM < 1 || $MM > 12) {
            return false;
        }

        // Validacija godine u JMB-u
        // Format: 
        // - 900 <= GGG <= 999 → godina = 1900 + (GGG - 900) = 1000 + GGG (period 1900-1999)
        // - 000 <= GGG <= [trenutna godina - 2000] → godina = 2000 + GGG (period 2000-trenutna godina)
        $currentYear = (int)date('Y');
        $currentYearLastTwo = $currentYear - 2000; // npr. 2025 -> 25
        
        if ($GGG >= 900 && $GGG <= 999) {
            // Period 1900-1999
            $yearFull = 1000 + $GGG;
            // Provera: godina mora biti između 1900 i 1999
            if ($yearFull < 1900 || $yearFull > 1999) {
                return false;
            }
        } elseif ($GGG >= 0 && $GGG <= $currentYearLastTwo) {
            // Period 2000-trenutna godina
            $yearFull = 2000 + $GGG;
            // Provera: godina ne može biti veća od trenutne
            if ($yearFull > $currentYear) {
                return false;
            }
        } else {
            // GGG je van validnog opsega
            return false;
        }

        // Validacija regiona (00-99)
        if ($RR < 0 || $RR > 99) {
            return false;
        }

        // Validacija BBB (000-999)
        if ($BBB < 0 || $BBB > 999) {
            return false;
        }

        // Validacija kontrolne cifre
        $weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$jmb[$i] * $weights[$i];
        }
        $m = $sum % 11;
        
        if ($m === 0) {
            $calculatedK = 0;
        } elseif ($m === 1) {
            return false; // JMB je neispravan
        } else {
            $calculatedK = 11 - $m;
        }

        return $calculatedK === $K;
    }

    public function register(Request $request)
    {
        // Osnovne validacije
        $rules = [
            'user_type' => ['required', 'in:Fizičko lice,Registrovan privredni subjekt'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'email_confirmation' => ['required', 'same:email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone_full' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
        ];

        $messages = [
            'user_type.required' => 'Vrsta korisnika je obavezna.',
            'user_type.in' => 'Odaberite validnu vrstu korisnika.',
            'first_name.required' => 'Ime je obavezno.',
            'last_name.required' => 'Prezime je obavezno.',
            'email.required' => 'E-mail adresa je obavezna.',
            'email.email' => 'Unesite validnu e-mail adresu.',
            'email.unique' => 'E-mail adresa je već registrovana.',
            'email_confirmation.required' => 'Potvrda e-mail adrese je obavezna.',
            'email_confirmation.same' => 'E-mail adrese se ne poklapaju.',
            'password.required' => 'Lozinka je obavezna.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'phone_full.required' => 'Broj telefona je obavezan.',
            'address.required' => 'Adresa je obavezna.',
            'address.max' => 'Adresa ne može biti duža od 500 karaktera.',
        ];

        // Validacija u zavisnosti od tipa korisnika
        if ($request->user_type === 'Registrovan privredni subjekt') {
            $rules['business_type'] = ['required', 'in:Preduzetnik,Ortačko društvo,Komanditno društvo,Društvo sa ograničenom odgovornošću,Akcionarsko društvo,Dio stranog društva (predstavništvo ili poslovna jedinica),Udruženje (nvo, fondacije, sportske organizacije),Ustanova (državne i privatne),Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)'];
            $messages['business_type.required'] = 'Odaberite tip privrednog subjekta.';

            // Ako nije Preduzetnik, PIB je obavezan
            if ($request->business_type && $request->business_type !== 'Preduzetnik') {
                $rules['pib'] = ['required', 'string', 'regex:/^[0-9]{9}$/', 'unique:users,pib'];
                $messages['pib.required'] = 'PIB je obavezan.';
                $messages['pib.regex'] = 'PIB mora imati tačno 9 cifara.';
                $messages['pib.unique'] = 'PIB je već registrovan.';
            }

            // Ako je Preduzetnik, proveri residential_status
            if ($request->business_type === 'Preduzetnik') {
                $rules['residential_status'] = ['required', 'in:resident,non-resident'];
                $messages['residential_status.required'] = 'Status prebivališta je obavezan.';
            }
        }

        // Validacija za Fizičko lice i Preduzetnik - residential_status
        if ($request->user_type === 'Fizičko lice') {
            $rules['residential_status'] = ['required', 'in:resident,non-resident'];
            $messages['residential_status.required'] = 'Status prebivališta je obavezan.';
        }

        // Validacija JMB/PIB/Passport u zavisnosti od residential_status
        if ($request->residential_status === 'resident') {
            if ($request->user_type === 'Fizičko lice' || 
                ($request->user_type === 'Registrovan privredni subjekt' && $request->business_type === 'Preduzetnik')) {
                $rules['jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/'];
                $messages['jmb.required'] = 'JMB je obavezan za rezidente.';
                $messages['jmb.regex'] = 'JMB mora imati tačno 13 cifara.';
            }
        } elseif ($request->residential_status === 'non-resident') {
            $rules['non_resident_id_type'] = ['required', 'in:jmb,passport'];
            $messages['non_resident_id_type.required'] = 'Odaberite vrstu identifikacije.';

            if ($request->non_resident_id_type === 'jmb') {
                $rules['jmb_non_resident'] = ['required', 'string', 'regex:/^[0-9]{13}$/'];
                $messages['jmb_non_resident.required'] = 'JMB je obavezan.';
                $messages['jmb_non_resident.regex'] = 'JMB mora imati tačno 13 cifara.';
            } elseif ($request->non_resident_id_type === 'passport') {
                $rules['passport_number'] = ['required', 'string', 'regex:/^[A-Z0-9]+$/', 'min:3', 'unique:users,passport_number'];
                $messages['passport_number.required'] = 'Broj pasoša je obavezan.';
                $messages['passport_number.regex'] = 'Broj pasoša može sadržati samo velika slova i brojeve.';
                $messages['passport_number.unique'] = 'Broj pasoša je već registrovan.';
            }
        }

        $validated = $request->validate($rules, $messages);

        // Dodatna validacija JMB-a (kontrolna cifra)
        $jmbToValidate = null;
        if (isset($validated['jmb'])) {
            $jmbToValidate = $validated['jmb'];
        } elseif (isset($validated['jmb_non_resident'])) {
            $jmbToValidate = $validated['jmb_non_resident'];
        }

        if ($jmbToValidate && !$this->validateJMB($jmbToValidate)) {
            return back()->withErrors(['jmb' => 'JMB je neispravan (kontrolna cifra ne odgovara ili format nije validan).'])->withInput();
        }

        // Provera da li korisnik sa istim JMB/PIB/passport već postoji (ali je deaktiviran)
        if ($jmbToValidate) {
            $existingUser = User::where('jmb', $jmbToValidate)
                ->where('activation_status', 'deactivated')
                ->first();
            if ($existingUser) {
                return back()->withErrors(['jmb' => 'Nalog sa ovim JMB-om već postoji, ali je deaktiviran. Molimo aktivirajte postojeći nalog.'])->withInput();
            }
            
            // Provera jedinstvenosti aktivnog JMB-a
            if (User::where('jmb', $jmbToValidate)->where('activation_status', 'active')->exists()) {
                return back()->withErrors(['jmb' => 'JMB je već registrovan.'])->withInput();
            }
        }

        if (isset($validated['pib'])) {
            $existingUser = User::where('pib', $validated['pib'])
                ->where('activation_status', 'deactivated')
                ->first();
            if ($existingUser) {
                return back()->withErrors(['pib' => 'Nalog sa ovim PIB-om već postoji, ali je deaktiviran. Molimo aktivirajte postojeći nalog.'])->withInput();
            }
        }

        if (isset($validated['passport_number'])) {
            $existingUser = User::where('passport_number', $validated['passport_number'])
                ->where('activation_status', 'deactivated')
                ->first();
            if ($existingUser) {
                return back()->withErrors(['passport_number' => 'Nalog sa ovim brojem pasoša već postoji, ali je deaktiviran. Molimo aktivirajte postojeći nalog.'])->withInput();
            }
        }

        // Capitalize prvo slovo imena i prezimena
        $validated['first_name'] = ucfirst(mb_strtolower($validated['first_name'], 'UTF-8'));
        $validated['last_name'] = ucfirst(mb_strtolower($validated['last_name'], 'UTF-8'));

        // Priprema podataka za kreiranje korisnika
        // Podrazumevana rola je 3 (korisnik) - dodeljena će se kasnije ako treba
        $userData = [
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'user_type' => $request->business_type ?? $validated['user_type'],
            'residential_status' => $validated['residential_status'] ?? 'resident',
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone_full'],
            'address' => $validated['address'],
            'password' => Hash::make($validated['password']),
            'role_id' => 3, // Podrazumevana rola: korisnik
            'activation_status' => 'active',
        ];

        // Dodavanje JMB/PIB/Passport
        if ($jmbToValidate) {
            $userData['jmb'] = $jmbToValidate;
        }
        if (isset($validated['pib'])) {
            $userData['pib'] = $validated['pib'];
        }
        if (isset($validated['passport_number'])) {
            $userData['passport_number'] = strtoupper($validated['passport_number']);
        }

        // Kreiranje korisnika
        $user = User::create($userData);

        // Slanje email verifikacije
        event(new Registered($user));

        // Automatska prijava nakon registracije
        Auth::login($user);

        return redirect()->route('verification.notice')->with('status', 'registration-success');
    }

    public function dashboard()
    {
        $user = auth()->user();
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        
        // Ako je konkurs admin, pripremi podatke za admin dashboard
        if ($isCompetitionAdmin) {
            $stats = [
                'total_competitions' => Competition::count(),
                'total_applications' => Application::count(),
                'total_commissions' => Commission::count(),
                'active_commissions' => Commission::where('status', 'active')->count(),
            ];
            
            $recent_applications = Application::with('user', 'competition')
                ->latest()
                ->take(10)
                ->get();
            
            return view('dashboard', compact('stats', 'recent_applications'));
        }
        
        return view('dashboard');
    }
}