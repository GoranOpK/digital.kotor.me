<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Competition;
use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Role;
use App\Models\Tender;
use App\Models\Contract;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AdminController extends Controller
{
    /**
     * Prikaz admin dashboard-a
     */
    public function dashboard()
    {
        $user = auth()->user();
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        
        // Statistike za admin dashboard
        $stats = [
            'total_competitions' => Competition::count(),
            'total_applications' => Application::count(),
            'total_commissions' => Commission::count(),
            'active_commissions' => Commission::where('status', 'active')->count(),
        ];
        
        // Dodatne statistike samo za superadmin i admin
        if (!$isCompetitionAdmin) {
            $stats['total_users'] = User::count();
            $stats['active_users'] = User::where('activation_status', 'active')->count();
            $stats['total_tenders'] = Tender::count();
            $stats['total_contracts'] = Contract::count();
            $stats['total_reports'] = Report::count();
        }

        // Najnoviji korisnici (samo za superadmin i admin)
        $recent_users = null;
        if (!$isCompetitionAdmin) {
            $recent_users = User::latest()->take(10)->get();
        }

        // Najnovije prijave na konkurse
        $recent_applications = Application::with('user', 'competition')
            ->latest()
            ->take(10)
            ->get();

        // Aktivni konkursi sa trajanjem
        $active_competitions = Competition::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_applications', 'isCompetitionAdmin', 'active_competitions'));
    }

    /**
     * Lista svih korisnika
     */
    public function users()
    {
        // Proveri da li je konkurs_admin - oni nemaju pristup upravljanju korisnicima
        $user = auth()->user();
        if ($user->role && $user->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju korisnicima.');
        }
        
        $users = User::with('role')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Prikaz određenog korisnika
     */
    public function showUser(User $user)
    {
        // Proveri da li je konkurs_admin
        $currentUser = auth()->user();
        if ($currentUser->role && $currentUser->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju korisnicima.');
        }
        
        $user->load('role');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Forma za izmjenu korisnika
     */
    public function editUser(User $user)
    {
        // Proveri da li je konkurs_admin
        $currentUser = auth()->user();
        if ($currentUser->role && $currentUser->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju korisnicima.');
        }
        
        $user->load('role');
        $roles = \App\Models\Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Ažuriranje korisnika
     */
    public function updateUser(Request $request, User $user)
    {
        // Proveri da li je konkurs_admin
        $currentUser = auth()->user();
        if ($currentUser->role && $currentUser->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju korisnicima.');
        }
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'role_id' => 'required|exists:roles,id',
            'activation_status' => 'required|in:active,deactivated',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->name = $validated['first_name'] . ' ' . $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role_id = $validated['role_id'];
        $user->activation_status = $validated['activation_status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Korisnik je uspješno ažuriran.');
    }

    /**
     * Deaktivacija korisnika
     */
    public function deactivateUser(User $user)
    {
        // Proveri da li je konkurs_admin
        $currentUser = auth()->user();
        if ($currentUser->role && $currentUser->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju korisnicima.');
        }
        $user->activation_status = 'deactivated';
        $user->save();

        return redirect()->back()->with('success', 'Korisnik je deaktiviran.');
    }

    /**
     * Aktivacija korisnika
     */
    public function activateUser(User $user)
    {
        // Proveri da li je konkurs_admin
        $currentUser = auth()->user();
        if ($currentUser->role && $currentUser->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju korisnicima.');
        }
        $user->activation_status = 'active';
        $user->save();

        return redirect()->back()->with('success', 'Korisnik je aktiviran.');
    }

    /**
     * Lista svih konkursa
     */
    public function competitions(Request $request)
    {
        $tab = $request->get('tab', 'active'); // 'active' ili 'archive'
        
        if ($tab === 'archive') {
            // Arhiva - završeni konkursi (closed ili completed)
            $competitions = Competition::withCount('applications')
                ->whereIn('status', ['closed', 'completed'])
                ->orderBy('closed_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            // Aktivni konkursi (draft, published)
            $competitions = Competition::withCount('applications')
                ->whereIn('status', ['draft', 'published'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }
        
        return view('admin.competitions.index', compact('competitions', 'tab'));
    }

    /**
     * Forma za kreiranje novog konkursa
     */
    public function createCompetition()
    {
        return view('admin.competitions.create');
    }

    /**
     * Snimanje novog konkursa
     */
    public function storeCompetition(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:zensko,omladinsko,ostalo',
            'competition_number' => 'nullable|integer',
            'year' => 'required|integer|min:2020|max:2100',
            'budget' => 'required|numeric|min:0',
            'max_support_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
        ], [
            'title.required' => 'Naziv konkursa je obavezan.',
            'type.required' => 'Tip konkursa je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'budget.required' => 'Budžet je obavezan.',
            'max_support_percentage.required' => 'Maksimalna podrška je obavezna.',
        ]);

        $data = $validated;
        $data['deadline_days'] = 20;
        $data['status'] = 'draft';

        if (!empty($validated['start_date'])) {
            $start = \Carbon\Carbon::parse($validated['start_date']);
            $data['start_date'] = $start->toDateString();
            $data['end_date'] = $start->copy()->addDays(20)->toDateString();
        }

        $competition = Competition::create($data);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', 'Konkurs je uspješno kreiran.');
    }

    /**
     * Prikaz određenog konkursa
     */
    public function showCompetition(Competition $competition)
    {
        $competition->loadCount('applications');
        $applications = $competition->applications()
            ->with('user')
            ->latest()
            ->paginate(20);
        
        return view('admin.competitions.show', compact('competition', 'applications'));
    }

    /**
     * Forma za izmjenu konkursa
     */
    public function editCompetition(Competition $competition)
    {
        return view('admin.competitions.edit', compact('competition'));
    }

    /**
     * Ažuriranje konkursa
     */
    public function updateCompetition(Request $request, Competition $competition)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:zensko,omladinsko,ostalo',
            'competition_number' => 'nullable|integer',
            'year' => 'required|integer|min:2020|max:2100',
            'budget' => 'required|numeric|min:0',
            'max_support_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'status' => 'required|in:draft,published,closed,completed',
        ], [
            'title.required' => 'Naziv konkursa je obavezan.',
            'type.required' => 'Tip konkursa je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'budget.required' => 'Budžet je obavezan.',
            'max_support_percentage.required' => 'Maksimalna podrška je obavezna.',
        ]);

        $data = $validated;
        $data['deadline_days'] = 20;
        
        // Ako vraćamo konkurs u status 'published' ili 'draft', poništi datum zatvaranja
        if (in_array($validated['status'], ['published', 'draft'])) {
            $data['closed_at'] = null;
        }

        if (!empty($validated['start_date'])) {
            $start = \Carbon\Carbon::parse($validated['start_date']);
            $data['start_date'] = $start->toDateString();
            $data['end_date'] = $start->copy()->addDays(20)->toDateString();
        }

        $competition->update($data);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', 'Konkurs je uspješno ažuriran.');
    }

    /**
     * Objavljivanje konkursa
     */
    public function publishCompetition(Competition $competition)
    {
        if ($competition->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Samo nacrti konkursa mogu biti objavljeni.']);
        }

        $now = now();
        $updateData = [
            'status' => 'published',
            'published_at' => $now,
            'deadline_days' => 20,
        ];

        // Ako nije postavljen start_date, postavi ga na danas
        if (!$competition->start_date) {
            $updateData['start_date'] = $now->toDateString();
            $updateData['end_date'] = $now->copy()->addDays(20)->toDateString();
        } else {
            // Ako je start_date postavljen (bio to današnji ili budući), 
            // end_date mora biti 20 dana od tog start_date
            $updateData['end_date'] = $competition->start_date->copy()->addDays(20)->toDateString();
        }

        $competition->update($updateData);

        return redirect()->back()->with('success', 'Konkurs je uspješno objavljen.');
    }

    /**
     * Zatvaranje konkursa
     */
    public function closeCompetition(Competition $competition)
    {
        $competition->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Konkurs je uspješno zatvoren.');
    }

    /**
     * Brisanje konkursa
     */
    public function destroyCompetition(Competition $competition)
    {
        // Proveri da li ima prijava
        if ($competition->applications()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Ne možete obrisati konkurs koji već ima prijave.']);
        }

        $competition->delete();

        return redirect()->route('admin.competitions.index')->with('success', 'Konkurs je uspješno obrisan.');
    }

    /**
     * Lista svih prijava
     */
    public function applications(Request $request)
    {
        // Proveri da li je konkurs_admin - oni nemaju pristup upravljanju aplikacijama
        $user = auth()->user();
        if ($user->role && $user->role->name === 'konkurs_admin') {
            abort(403, 'Nemate pristup upravljanju aplikacijama.');
        }
        
        $query = Application::with(['user', 'competition']);

        // Filtriranje po statusu
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filtriranje po konkursu
        if ($request->has('competition_id') && $request->competition_id !== '') {
            $query->where('competition_id', $request->competition_id);
        }

        // Pretraga po nazivu biznis plana
        if ($request->has('search') && $request->search !== '') {
            $query->where('business_plan_name', 'like', '%' . $request->search . '%');
        }

        $applications = $query->latest()->paginate(20);
        $competitions = Competition::where('status', 'published')->orWhere('status', 'closed')->get();

        return view('admin.applications.index', compact('applications', 'competitions'));
    }

    /**
     * Prikaz određene prijave (admin)
     */
    public function showApplication(Application $application)
    {
        $application->load(['user', 'competition', 'businessPlan', 'documents', 'evaluationScores.commissionMember']);
        
        return view('admin.applications.show', compact('application'));
    }

    /**
     * Lista svih komisija
     */
    public function commissions()
    {
        $commissions = Commission::withCount('members')
            ->withCount('activeMembers')
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.commissions.index', compact('commissions'));
    }

    /**
     * Forma za kreiranje nove komisije
     */
    public function createCommission()
    {
        return view('admin.commissions.create');
    }

    /**
     * Snimanje nove komisije
     */
    public function storeCommission(Request $request)
    {
        // Automatski izračunaj datum završetka (2 godine od početka)
        $startDate = $request->input('start_date');
        $endDate = null;
        if ($startDate) {
            $start = \Carbon\Carbon::parse($startDate);
            $endDate = $start->copy()->addYears(2)->format('Y-m-d');
        }
        
        // Proveri da li se email-ovi ponavljaju između članova i da li već postoje u bazi
        $emails = [];
        $membersData = $request->input('members', []);
        
        // Filtriraj samo popunjene članove (koji imaju email)
        $filledMembers = [];
        foreach ($membersData as $index => $member) {
            if (!empty($member['email'])) {
                $filledMembers[$index] = $member;
                $email = strtolower($member['email']);
                
                // Proveri da li se email ponavlja između članova
                if (in_array($email, $emails)) {
                    return back()->withErrors(['members.' . $index . '.email' => 'E-mail se ne može ponavljati između članova komisije.'])->withInput();
                }
                
                // Proveri da li email već postoji u bazi
                if (User::where('email', $email)->exists()) {
                    return back()->withErrors(['members.' . $index . '.email' => 'E-mail već postoji u sistemu.'])->withInput();
                }
                
                $emails[] = $email;
            }
        }
        
        // Proveri da li je dodato najmanje 1 član
        if (count($filledMembers) < 1) {
            return back()->withErrors(['members' => 'Morate dodati najmanje jednog člana komisije.'])->withInput();
        }
        
        // Proveri da li je dodato više od 5 članova
        if (count($filledMembers) > 5) {
            return back()->withErrors(['members' => 'Komisija može imati najviše 5 članova.'])->withInput();
        }

        // Validacija - svi članovi koji se dodaju moraju imati sva polja popunjena
        $rules = [
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'members' => 'required|array|min:1|max:5',
        ];
        
        // Validacija za sve članove (0-4) - ako je bilo koje polje popunjeno, sva polja moraju biti popunjena
        for ($i = 0; $i <= 4; $i++) {
            $rules["members.{$i}.name"] = 'nullable|required_with:members.' . $i . '.email|string|max:255';
            $rules["members.{$i}.email"] = 'nullable|required_with:members.' . $i . '.name|email|max:255';
            $rules["members.{$i}.password"] = 'nullable|required_with:members.' . $i . '.email|string|min:8';
            $rules["members.{$i}.position"] = 'nullable|required_with:members.' . $i . '.email|in:predsjednik,clan';
            $rules["members.{$i}.member_type"] = 'nullable|required_with:members.' . $i . '.email|in:opstina,udruzenje,zene_mreza';
            $rules["members.{$i}.organization"] = 'nullable|string|max:255';
        }
        
        $messages = [
            'name.required' => 'Naziv komisije je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'start_date.required' => 'Datum početka je obavezan.',
            'end_date.required' => 'Datum završetka je obavezan.',
            'end_date.after' => 'Datum završetka mora biti posle datuma početka.',
            'members.required' => 'Morate dodati najmanje jednog člana komisije.',
            'members.min' => 'Morate dodati najmanje jednog člana komisije.',
            'members.max' => 'Komisija može imati najviše 5 članova.',
        ];
        
        // Dodaj poruke za sve članove
        for ($i = 0; $i <= 4; $i++) {
            $messages["members.{$i}.name.required_with"] = "Ime i prezime člana je obavezno ako dodajete člana.";
            $messages["members.{$i}.email.required_with"] = "E-mail člana je obavezan ako dodajete člana.";
            $messages["members.{$i}.email.email"] = "E-mail člana mora biti validan.";
            $messages["members.{$i}.password.required_with"] = "Password člana je obavezan ako dodajete člana.";
            $messages["members.{$i}.password.min"] = "Password člana mora imati minimum 8 karaktera.";
            $messages["members.{$i}.position.required_with"] = "Pozicija člana je obavezna ako dodajete člana.";
            $messages["members.{$i}.member_type.required_with"] = "Tip člana je obavezan ako dodajete člana.";
        }
        
        // Dodaj poruke za organizaciju
        for ($i = 0; $i <= 4; $i++) {
            $messages["members.{$i}.organization.required_if"] = 'Organizacija je obavezna za člana iz udruženja.';
        }
        
        $validated = $request->validate($rules, $messages);
        
        // Custom validacija: organizacija je obavezna za člana iz udruženja ako je član popunjen
        if (!empty($validated['members'][3]['email']) && 
            isset($validated['members'][3]['member_type']) && 
            $validated['members'][3]['member_type'] === 'udruzenje' && 
            empty($validated['members'][3]['organization'])) {
            return back()->withErrors(['members.3.organization' => 'Organizacija je obavezna za člana iz udruženja.'])->withInput();
        }

        $commission = Commission::create([
            'name' => $validated['name'],
            'year' => $validated['year'],
            'start_date' => $validated['start_date'],
            'end_date' => $endDate ?? $validated['end_date'],
            'status' => 'active',
        ]);

        // Pronađi rolu za komisiju
        $komisijaRole = Role::where('name', 'komisija')->first();
        if (!$komisijaRole) {
            return back()->withErrors(['error' => 'Rola "komisija" ne postoji u sistemu.'])->withInput();
        }

        // Kreiraj samo popunjene članove komisije sa User nalozima
        $createdMembers = 0;
        foreach ($validated['members'] as $memberData) {
            // Preskoči prazne članove (koji nisu popunjeni)
            if (empty($memberData['name']) || empty($memberData['email'])) {
                continue;
            }
            
            // Kreiraj User nalog za člana komisije
            $user = User::create([
                'name' => $memberData['name'],
                'email' => strtolower($memberData['email']),
                'password' => Hash::make($memberData['password']),
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'user_type' => 'Fizičko lice',
                'residential_status' => 'resident',
            ]);
            
            // Pošalji email za verifikaciju
            event(new Registered($user));

            // Kreiraj člana komisije i poveži sa User nalogom
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $memberData['name'],
                'position' => $memberData['position'],
                'member_type' => $memberData['member_type'],
                'organization' => $memberData['organization'] ?? null,
                'status' => 'active',
            ]);
            
            $createdMembers++;
        }

        $message = $createdMembers == 1 
            ? 'Komisija sa 1 članom je uspješno kreirana. Možete dodati ostale članove kasnije.' 
            : "Komisija sa {$createdMembers} članova je uspješno kreirana. Možete dodati ostale članove kasnije.";

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', $message);
    }

    /**
     * Prikaz određene komisije
     */
    public function showCommission(Commission $commission)
    {
        $commission->load(['members.user']);
        $users = User::whereHas('role', function($query) {
            $query->where('name', 'komisija');
        })->get();
        
        return view('admin.commissions.show', compact('commission', 'users'));
    }

    /**
     * Forma za izmjenu komisije
     */
    public function editCommission(Commission $commission)
    {
        return view('admin.commissions.edit', compact('commission'));
    }

    /**
     * Ažuriranje komisije
     */
    public function updateCommission(Request $request, Commission $commission)
    {
        // Automatski izračunaj datum završetka (2 godine od početka)
        $startDate = $request->input('start_date');
        $endDate = null;
        if ($startDate) {
            $start = \Carbon\Carbon::parse($startDate);
            $endDate = $start->copy()->addYears(2)->format('Y-m-d');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['end_date'] = $endDate ?? $validated['end_date'];
        
        $commission->update($validated);

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', 'Komisija je uspješno ažurirana.');
    }

    /**
     * Brisanje komisije
     */
    public function destroyCommission(Commission $commission)
    {
        // Brisanje komisije će automatski obrisati sve članove komisije zbog cascade delete
        // Takođe, treba proveriti da li postoje ocjene povezane sa ovom komisijom
        $commissionName = $commission->name;
        
        $commission->delete();

        return redirect()->route('admin.commissions.index')
            ->with('success', "Komisija '{$commissionName}' je uspješno obrisana.");
    }

    /**
     * Dodavanje člana komisije
     */
    public function addCommissionMember(Request $request, Commission $commission)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required_without:user_id|email|max:255|unique:users,email',
            'password' => 'required_without:user_id|string|min:8',
            'position' => 'required|string|max:255',
            'member_type' => 'required|in:opstina,udruzenje,zene_mreza',
            'organization' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Ime i prezime je obavezno.',
            'email.required_without' => 'E-mail je obavezan ako član ne postoji u sistemu.',
            'email.email' => 'E-mail mora biti validan.',
            'email.unique' => 'E-mail već postoji u sistemu.',
            'password.required_without' => 'Password je obavezan ako član ne postoji u sistemu.',
            'password.min' => 'Password mora imati minimum 8 karaktera.',
            'position.required' => 'Pozicija je obavezna.',
            'member_type.required' => 'Tip člana je obavezan.',
        ]);

        // Proveri da li već postoji 5 članova
        if ($commission->members()->count() >= 5) {
            return back()->withErrors(['error' => 'Komisija može imati najviše 5 članova.'])->withInput();
        }

        // Ako je izabran postojeći korisnik, koristi njegov ID
        $userId = $validated['user_id'];
        
        // Ako nije izabran postojeći korisnik, kreiraj novog
        if (!$userId) {
            // Pronađi rolu za komisiju
            $komisijaRole = Role::where('name', 'komisija')->first();
            if (!$komisijaRole) {
                return back()->withErrors(['error' => 'Rola "komisija" ne postoji u sistemu.'])->withInput();
            }

            // Kreiraj User nalog za člana komisije
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'user_type' => 'Fizičko lice',
                'residential_status' => 'resident',
            ]);
            
            // Pošalji email za verifikaciju
            event(new Registered($user));
            
            $userId = $user->id;
        }

        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $userId,
            'name' => $validated['name'],
            'position' => $validated['position'],
            'member_type' => $validated['member_type'],
            'organization' => $validated['organization'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Član komisije je uspješno dodat.');
    }

    /**
     * Forma za potpisivanje izjava
     */
    public function signDeclarations(CommissionMember $member)
    {
        return view('admin.commissions.sign-declarations', compact('member'));
    }

    /**
     * Snimanje potpisanih izjava
     */
    public function storeDeclarations(Request $request, CommissionMember $member)
    {
        $validated = $request->validate([
            'confidentiality_declaration' => 'required|string',
            'conflict_of_interest_declaration' => 'required|string',
        ], [
            'confidentiality_declaration.required' => 'Izjava o tajnosti je obavezna.',
            'conflict_of_interest_declaration.required' => 'Izjava o sukobu interesa je obavezna.',
        ]);

        $member->update([
            'confidentiality_declaration' => $validated['confidentiality_declaration'],
            'conflict_of_interest_declaration' => $validated['conflict_of_interest_declaration'],
            'declarations_signed_at' => now(),
        ]);

        return redirect()->route('admin.commissions.show', $member->commission)
            ->with('success', 'Izjave su uspješno potpisane.');
    }

    /**
     * Ažuriranje statusa člana komisije
     */
    public function updateMemberStatus(Request $request, CommissionMember $member)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,resigned,dismissed',
        ]);

        $member->update(['status' => $validated['status']]);

        return back()->with('success', 'Status člana je uspješno ažuriran.');
    }

    /**
     * Brisanje člana komisije
     */
    public function deleteMember(CommissionMember $member)
    {
        $commission = $member->commission;
        $member->delete();

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', 'Član komisije je uspješno obrisan.');
    }

    /**
     * Rang lista prijava za konkurs
     */
    public function rankingList(Competition $competition)
    {
        // Učitaj sve prijave koje su ocjenjene ili podnesene
        $applications = Application::where('competition_id', $competition->id)
            ->whereIn('status', ['evaluated', 'submitted'])
            ->with(['user', 'businessPlan'])
            ->get()
            ->map(function ($application) {
                // Izračunaj konačnu ocjenu ako nije izračunata
                if (!$application->final_score) {
                    $application->final_score = $application->calculateFinalScore();
                    $application->save();
                }
                return $application;
            })
            ->filter(function ($application) {
                // Filtriraj samo one koje zadovoljavaju minimum (30 bodova)
                return $application->meetsMinimumScore();
            })
            ->sortByDesc('final_score')
            ->values();

        // Dodaj poziciju na rang listi
        $position = 1;
        foreach ($applications as $application) {
            $application->ranking_position = $position;
            $application->save();
            $position++;
        }

        // Izračunaj ukupan budžet i preostali budžet
        $totalBudget = $competition->budget ?? 0;
        $usedBudget = $applications->sum('approved_amount');
        $remainingBudget = $totalBudget - $usedBudget;

        return view('admin.competitions.ranking', compact('competition', 'applications', 'totalBudget', 'usedBudget', 'remainingBudget'));
    }

    /**
     * Odabir dobitnika
     */
    public function selectWinners(Request $request, Competition $competition)
    {
        // Prikupi odabrane dobitnike iz forme
        $winnersData = [];
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'winners[') === 0 && strpos($key, '][selected]') !== false) {
                preg_match('/winners\[(\d+)\]/', $key, $matches);
                if (!empty($matches[1]) && $value == '1') {
                    $appId = $matches[1];
                    $approvedAmount = $request->input("winners[{$appId}][approved_amount]");
                    if ($approvedAmount && $approvedAmount > 0) {
                        $winnersData[] = [
                            'application_id' => $appId,
                            'approved_amount' => (float) $approvedAmount,
                        ];
                    }
                }
            }
        }

        if (empty($winnersData)) {
            return back()->withErrors(['error' => 'Morate odabrati najmanje jednog dobitnika sa odobrenim iznosom.'])->withInput();
        }

        $totalBudget = $competition->budget ?? 0;
        $maxSupportPerPlan = ($totalBudget * (($competition->max_support_percentage ?? 30) / 100));
        $usedBudget = Application::where('competition_id', $competition->id)
            ->where('status', 'approved')
            ->sum('approved_amount');

        foreach ($winnersData as $winner) {
            $application = Application::find($winner['application_id']);
            
            if (!$application || $application->competition_id !== $competition->id) {
                continue;
            }

            // Proveri da li je iznos validan
            $approvedAmount = min(
                $winner['approved_amount'],
                $maxSupportPerPlan,
                $totalBudget - $usedBudget
            );

            if ($approvedAmount > 0) {
                $application->update([
                    'approved_amount' => $approvedAmount,
                    'status' => 'approved',
                ]);

                $usedBudget += $approvedAmount;
            }
        }

        // Oznaci sve ostale kao odbijene
        Application::where('competition_id', $competition->id)
            ->where('status', 'evaluated')
            ->whereNull('approved_amount')
            ->update(['status' => 'rejected']);

        return redirect()->route('admin.competitions.ranking', $competition)
            ->with('success', 'Dobitnici su uspješno odabrani.');
    }

    /**
     * Generisanje Odluke
     */
    public function generateDecision(Competition $competition)
    {
        $winners = Application::where('competition_id', $competition->id)
            ->where('status', 'approved')
            ->with(['user', 'businessPlan'])
            ->orderBy('ranking_position')
            ->get();

        // Generiši PDF ili pripremi podatke za prikaz
        return view('admin.competitions.decision', compact('competition', 'winners'));
    }
}

