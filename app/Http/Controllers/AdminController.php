<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Competition;
use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Tender;
use App\Models\Contract;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Prikaz admin dashboard-a
     */
    public function dashboard()
    {
        // Statistike za admin dashboard
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('activation_status', 'active')->count(),
            'total_competitions' => Competition::count(),
            'total_applications' => Application::count(),
            'total_commissions' => Commission::count(),
            'active_commissions' => Commission::where('status', 'active')->count(),
            'total_tenders' => Tender::count(),
            'total_contracts' => Contract::count(),
            'total_reports' => Report::count(),
        ];

        // Najnoviji korisnici
        $recent_users = User::latest()->take(10)->get();

        // Najnovije prijave na konkurse
        $recent_applications = Application::with('user', 'competition')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_applications'));
    }

    /**
     * Lista svih korisnika
     */
    public function users()
    {
        $users = User::with('role')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Prikaz određenog korisnika
     */
    public function showUser(User $user)
    {
        $user->load('role');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Forma za izmenu korisnika
     */
    public function editUser(User $user)
    {
        $user->load('role');
        $roles = \App\Models\Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Ažuriranje korisnika
     */
    public function updateUser(Request $request, User $user)
    {
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
            ->with('success', 'Korisnik je uspešno ažuriran.');
    }

    /**
     * Deaktivacija korisnika
     */
    public function deactivateUser(User $user)
    {
        $user->activation_status = 'deactivated';
        $user->save();

        return redirect()->back()->with('success', 'Korisnik je deaktiviran.');
    }

    /**
     * Aktivacija korisnika
     */
    public function activateUser(User $user)
    {
        $user->activation_status = 'active';
        $user->save();

        return redirect()->back()->with('success', 'Korisnik je aktiviran.');
    }

    /**
     * Lista svih konkursa
     */
    public function competitions()
    {
        $competitions = Competition::withCount('applications')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.competitions.index', compact('competitions'));
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
            'deadline_days' => 'required|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'title.required' => 'Naziv konkursa je obavezan.',
            'type.required' => 'Tip konkursa je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'budget.required' => 'Budžet je obavezan.',
            'max_support_percentage.required' => 'Maksimalna podrška je obavezna.',
            'deadline_days.required' => 'Rok za prijave je obavezan.',
        ]);

        $competition = Competition::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'competition_number' => $validated['competition_number'] ?? null,
            'year' => $validated['year'],
            'budget' => $validated['budget'],
            'max_support_percentage' => $validated['max_support_percentage'],
            'deadline_days' => $validated['deadline_days'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'draft',
        ]);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', 'Konkurs je uspešno kreiran.');
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
     * Forma za izmenu konkursa
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
            'deadline_days' => 'required|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,published,closed,completed',
        ]);

        $competition->update($validated);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', 'Konkurs je uspešno ažuriran.');
    }

    /**
     * Objavljivanje konkursa
     */
    public function publishCompetition(Competition $competition)
    {
        if ($competition->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Samo nacrti konkursa mogu biti objavljeni.']);
        }

        $competition->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Konkurs je uspešno objavljen.');
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

        return redirect()->back()->with('success', 'Konkurs je uspešno zatvoren.');
    }

    /**
     * Lista svih prijava
     */
    public function applications(Request $request)
    {
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ], [
            'name.required' => 'Naziv komisije je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'start_date.required' => 'Datum početka je obavezan.',
            'end_date.required' => 'Datum završetka je obavezan.',
            'end_date.after' => 'Datum završetka mora biti posle datuma početka.',
        ]);

        $commission = Commission::create([
            'name' => $validated['name'],
            'year' => $validated['year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'active',
        ]);

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', 'Komisija je uspešno kreirana.');
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
     * Forma za izmenu komisije
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive',
        ]);

        $commission->update($validated);

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', 'Komisija je uspešno ažurirana.');
    }

    /**
     * Dodavanje člana komisije
     */
    public function addCommissionMember(Request $request, Commission $commission)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'member_type' => 'required|in:opstina,udruzenje,zene_mreza',
            'organization' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Ime i prezime je obavezno.',
            'position.required' => 'Pozicija je obavezna.',
            'member_type.required' => 'Tip člana je obavezan.',
        ]);

        // Proveri da li već postoji 5 članova
        if ($commission->members()->count() >= 5) {
            return back()->withErrors(['error' => 'Komisija može imati najviše 5 članova.'])->withInput();
        }

        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $validated['user_id'] ?? null,
            'name' => $validated['name'],
            'position' => $validated['position'],
            'member_type' => $validated['member_type'],
            'organization' => $validated['organization'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Član komisije je uspešno dodat.');
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
            ->with('success', 'Izjave su uspešno potpisane.');
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

        return back()->with('success', 'Status člana je uspešno ažuriran.');
    }

    /**
     * Brisanje člana komisije
     */
    public function deleteMember(CommissionMember $member)
    {
        $commission = $member->commission;
        $member->delete();

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', 'Član komisije je uspešno obrisan.');
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
            ->with('success', 'Dobitnici su uspešno odabrani.');
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

