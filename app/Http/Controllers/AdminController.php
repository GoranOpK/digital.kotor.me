<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Competition;
use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\Tender;
use App\Models\Contract;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;

class AdminController extends Controller
{
    /**
     * Proverava da li je korisnik predsjednik komisije i da li je konkurs dodijeljen njegovoj komisiji
     */
    protected function isCommissionChairmanForCompetition(Competition $competition): bool
    {
        $user = auth()->user();
        
        // Proveri da li je korisnik predsjednik komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('position', 'predsjednik')
            ->first();
        
        if (!$commissionMember) {
            return false;
        }
        
        // Proveri da li je konkurs dodijeljen komisiji korisnika
        return $competition->commission_id === $commissionMember->commission_id;
    }
    
    /**
     * Proverava da li je korisnik član komisije (bilo koji član) i da li je konkurs dodijeljen njegovoj komisiji
     */
    protected function isCommissionMemberForCompetition(Competition $competition): bool
    {
        $user = auth()->user();
        
        // Proveri da li je korisnik član komisije (bilo koji član)
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        $result = $commissionMember && $competition->commission_id === $commissionMember->commission_id;

        Log::channel('single')->info('[RANG LISTA DEBUG] isCommissionMemberForCompetition', [
            'user_id' => $user->id,
            'competition_id' => $competition->id,
            'competition_commission_id' => $competition->commission_id,
            'commission_member_id' => $commissionMember?->id,
            'commission_member_commission_id' => $commissionMember?->commission_id,
            'result' => $result,
        ]);

        return $result;
    }
    
    /**
     * Proverava da li je korisnik predsjednik komisije
     */
    protected function isCommissionChairman(): bool
    {
        $user = auth()->user();
        
        return CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('position', 'predsjednik')
            ->exists();
    }
    
    /**
     * Proverava da li je korisnik član komisije (bilo koji član)
     */
    protected function isCommissionMember(): bool
    {
        $user = auth()->user();
        
        return CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
    
    /**
     * Vraća ID komisije za člana komisije
     */
    protected function getCommissionIdForMember(): ?int
    {
        $user = auth()->user();
        
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        return $commissionMember ? $commissionMember->commission_id : null;
    }
    
    /**
     * Proverava da li korisnik može da pristupi upravljanju konkursima
     */
    protected function canAccessCompetitionsManagement(): bool
    {
        $user = auth()->user();
        
        // Admin i konkurs_admin mogu pristupiti
        if ($user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin'])) {
            return true;
        }
        
        // Proveri da li je član komisije (bilo koji član)
        return $this->isCommissionMember();
    }

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
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Ako je član komisije, prikaži samo konkurse dodijeljene njegovoj komisiji
        if (!$isAdmin && $this->isCommissionMember()) {
            $commissionId = $this->getCommissionIdForMember();
            if (!$commissionId) {
                abort(403, 'Nemate pristup upravljanju konkursima.');
            }
        }
        
        $tab = $request->get('tab', 'active'); // 'active', 'archive' ili 'all'
        
        if ($tab === 'archive') {
            // Arhiva - završeni konkursi (closed ili completed)
            $query = Competition::withCount('applications')
                ->whereIn('status', ['closed', 'completed']);
            
            if (!$isAdmin && isset($commissionId)) {
                $query->where('commission_id', $commissionId);
            }
            
            $competitions = $query->orderBy('closed_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } elseif ($tab === 'all') {
            // Svi konkursi (bez obzira na status)
            $query = Competition::withCount('applications');
            
            if (!$isAdmin && isset($commissionId)) {
                $query->where('commission_id', $commissionId);
            }
            
            $competitions = $query->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            // Aktivni konkursi (draft, published)
            $query = Competition::withCount('applications')
                ->whereIn('status', ['draft', 'published']);
            
            if (!$isAdmin && isset($commissionId)) {
                $query->where('commission_id', $commissionId);
            }
            
            $competitions = $query->orderBy('created_at', 'desc')
                ->paginate(20);
        }
        
        return view('admin.competitions.index', compact('competitions', 'tab', 'isAdmin'));
    }

    /**
     * Arhiva konkursa (dostupna administratoru konkursa i članovima komisije)
     */
    public function competitionsArchive(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        $isCommissionMember = $this->isCommissionMember();
        
        // Filtriraj završene konkursi (closed ili completed)
        $query = Competition::withCount('applications')
            ->whereIn('status', ['closed', 'completed']);
        
        // Ako je član komisije, prikaži samo konkurse dodijeljene njegovoj komisiji
        if (!$isAdmin && $isCommissionMember) {
            $commissionId = $this->getCommissionIdForMember();
            if ($commissionId) {
                $query->where('commission_id', $commissionId);
            } else {
                // Ako nema komisiju, ne prikazuj ništa
                $query->whereRaw('1 = 0');
            }
        }
        
        $competitions = $query->orderBy('closed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('competitions.archive', compact('competitions'));
    }

    /**
     * Forma za kreiranje novog konkursa
     */
    public function createCompetition()
    {
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Predsjednik komisije ne može kreirati konkurse
        if (!$isAdmin) {
            abort(403, 'Nemate dozvolu za kreiranje konkursa.');
        }
        
        $commissions = Commission::where('status', 'active')->orderBy('year', 'desc')->get();
        return view('admin.competitions.create', compact('commissions'));
    }

    /**
     * Snimanje novog konkursa
     */
    public function storeCompetition(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Predsjednik komisije ne može kreirati konkurse
        if (!$isAdmin) {
            abort(403, 'Nemate dozvolu za kreiranje konkursa.');
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:zensko,omladinsko,ostalo',
            'competition_number' => 'nullable|integer',
            'year' => 'required|integer|min:2020|max:2100',
            'budget' => 'required|numeric|min:0',
            'max_support_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'commission_id' => 'nullable|exists:commissions,id',
        ], [
            'title.required' => 'Naziv konkursa je obavezan.',
            'type.required' => 'Tip konkursa je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'budget.required' => 'Budžet je obavezan.',
            'max_support_percentage.required' => 'Maksimalna podrška je obavezna.',
            'commission_id.exists' => 'Izabrana komisija ne postoji.',
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
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        $isSuperAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin']);
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        
        // Ako nije admin, proveri da li je član komisije i da li je konkurs dodijeljen njegovoj komisiji
        if (!$isAdmin && !$this->isCommissionMemberForCompetition($competition)) {
            abort(403, 'Nemate pristup ovom konkursu.');
        }
        
        // Proveri da li je predsjednik komisije (za prikaz dodatnih opcija)
        $isChairman = $this->isCommissionChairmanForCompetition($competition);
        
        // Proveri da li je član komisije
        $isCommissionMember = $this->isCommissionMemberForCompetition($competition);
        
        $competition->loadCount('applications');
        $competition->load('commission');
        
        // Članovi komisije ne mogu vidjeti draft prijave
        $applicationsQuery = $competition->applications()
            ->with(['user', 'businessPlan']);
        
        if ($isCommissionMember && !$isAdmin) {
            $applicationsQuery->where('status', '!=', 'draft');
        }
        
        $applications = $applicationsQuery->latest()->paginate(20);
        
        // Proveri da li je deadline prošao (za prikaz dugmeta "Zatvori konkurs")
        $deadline = $competition->deadline;
        $isDeadlinePassed = $deadline && now()->isAfter($deadline);
        
        // Rang lista: vidljiva svima koji imaju pristup (superadmin, predsjednik, članovi komisije) kada je formirana
        $isRankingFormed = $competition->isRankingFormed();
        $showRankingLink = ($isSuperAdmin || $isChairman || $isCommissionMember) && $isRankingFormed;

        // DEBUG: Rang lista dugme
        Log::channel('single')->info('[RANG LISTA DEBUG] showCompetition', [
            'user_id' => $user->id,
            'user_role' => $user->role?->name,
            'competition_id' => $competition->id,
            'competition_status' => $competition->status,
            'competition_commission_id' => $competition->commission_id,
            'isAdmin' => $isAdmin,
            'isSuperAdmin' => $isSuperAdmin,
            'isChairman' => $isChairman,
            'isCommissionMember' => $isCommissionMember,
            'isRankingFormed' => $isRankingFormed,
            'showRankingLink' => $showRankingLink,
        ]);
        
        return view('admin.competitions.show', compact('competition', 'applications', 'isAdmin', 'isSuperAdmin', 'isCompetitionAdmin', 'isChairman', 'isCommissionMember', 'isDeadlinePassed', 'showRankingLink'));
    }

    /**
     * Forma za izmjenu konkursa
     */
    public function editCompetition(Competition $competition)
    {
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Proveri da li je konkurs završen
        if (in_array($competition->status, ['closed', 'completed'])) {
            abort(403, 'Ne možete izmeniti završeni konkurs.');
        }
        
        // Ako nije admin, proveri da li je predsjednik komisije i da li je konkurs dodijeljen njegovoj komisiji
        if (!$isAdmin && !$this->isCommissionChairmanForCompetition($competition)) {
            abort(403, 'Nemate dozvolu za izmjenu ovog konkursa.');
        }
        
        $commissions = Commission::where('status', 'active')->orderBy('year', 'desc')->get();
        return view('admin.competitions.edit', compact('competition', 'commissions', 'isAdmin'));
    }

    /**
     * Ažuriranje konkursa
     */
    public function updateCompetition(Request $request, Competition $competition)
    {
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Proveri da li je konkurs završen
        if (in_array($competition->status, ['closed', 'completed'])) {
            abort(403, 'Ne možete izmeniti završeni konkurs.');
        }
        
        // Ako nije admin, proveri da li je predsjednik komisije i da li je konkurs dodijeljen njegovoj komisiji
        if (!$isAdmin && !$this->isCommissionChairmanForCompetition($competition)) {
            abort(403, 'Nemate dozvolu za izmjenu ovog konkursa.');
        }
        
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
            'commission_id' => 'nullable|exists:commissions,id',
        ], [
            'title.required' => 'Naziv konkursa je obavezan.',
            'type.required' => 'Tip konkursa je obavezan.',
            'year.required' => 'Godina je obavezna.',
            'budget.required' => 'Budžet je obavezan.',
            'max_support_percentage.required' => 'Maksimalna podrška je obavezna.',
            'commission_id.exists' => 'Izabrana komisija ne postoji.',
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
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Predsjednik komisije ne može objavljivati konkurse
        if (!$isAdmin) {
            abort(403, 'Nemate dozvolu za objavljivanje konkursa.');
        }
        
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
        $user = auth()->user();
        $isSuperAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin']);
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        
        // Administrator konkursa ne može zatvarati konkurse
        if ($isCompetitionAdmin) {
            abort(403, 'Nemate dozvolu za zatvaranje konkursa. Samo predsjednik komisije može zatvarati konkurse.');
        }
        
        // Ako nije superadmin, proveri da li je predsjednik komisije i da li je konkurs dodijeljen njegovoj komisiji
        if (!$isSuperAdmin && !$this->isCommissionChairmanForCompetition($competition)) {
            abort(403, 'Nemate dozvolu za zatvaranje ovog konkursa.');
        }
        
        // Proveri da li je deadline prošao - konkurs se ne može zatvoriti dok je još otvoren za prijave
        $deadline = $competition->deadline;
        $isDeadlinePassed = $deadline && now()->isAfter($deadline);
        
        if (!$isDeadlinePassed) {
            return redirect()->back()
                ->withErrors(['error' => 'Ne možete zatvoriti konkurs dok je još otvoren za prijave. Rok za prijave mora prvo isteći.']);
        }

        // Konkurs se može zatvoriti tek nakon što predsjednik generiše odluku (donese zaključak za sve prijave)
        if (!$competition->hasChairmanCompletedDecisions()) {
            return redirect()->back()
                ->withErrors(['error' => 'Ne možete zatvoriti konkurs prije nego što donesete zaključak za sve prijave i generišete odluku o dodjeli sredstava.']);
        }
        
        // Proveri da li postoje prijave koje nisu ocijenjene
        $submittedApplications = $competition->applications()
            ->where('status', 'submitted')
            ->get();
        
        if ($submittedApplications->count() > 0) {
            // Proveri da li sve prijave imaju ocjene od svih članova komisije
            $commission = $competition->commission;
            if ($commission) {
                $activeMembers = $commission->activeMembers()->get();
                $activeMemberIds = $activeMembers->pluck('id');
                
                foreach ($submittedApplications as $application) {
                    // Ako je deadline prošao, provjeri da li je prijava kompletna
                    $isIncomplete = false;
                    if ($isDeadlinePassed) {
                        // Provjeri da li prijava ima biznis plan i da li je obrazac kompletan
                        // Napomena: Provjera dokumenata je uklonjena - predsjednik komisije će odbiti prijavu ako nedostaju dokumenti kroz formu za ocjenjivanje
                        $hasBusinessPlan = $application->businessPlan !== null;
                        $isObrazacComplete = $application->isObrazacComplete();
                        
                        // Ako prijava nije kompletna (nema biznis plan ili obrazac nije kompletan), tretiraj je kao nepotpunu
                        if (!$hasBusinessPlan || !$isObrazacComplete) {
                            $isIncomplete = true;
                            
                            // Automatski postavi status na 'rejected' za nepotpune prijave nakon isteka deadline-a
                            if ($application->status === 'submitted') {
                                $application->update([
                                    'status' => 'rejected',
                                    'rejection_reason' => 'Prijava je nepotpuna (nedostaje biznis plan ili obrazac nije kompletan) i rok za prijave je istekao.',
                                ]);
                            }
                        }
                    }
                    
                    // Ako prijava nije nepotpuna, provjeri da li je ocijenjena
                    if (!$isIncomplete) {
                        $evaluatedByMemberIds = $application->evaluationScores()
                            ->whereIn('commission_member_id', $activeMemberIds)
                            ->pluck('commission_member_id');
                        
                        // Ako neki član komisije nije ocijenio prijavu, preusmeri na formu za ocjenjivanje
                        $missingEvaluations = $activeMemberIds->diff($evaluatedByMemberIds);
                        if ($missingEvaluations->count() > 0) {
                            return redirect()->route('evaluation.index', ['competition_id' => $competition->id])
                                ->withErrors(['error' => 'Ne možete zatvoriti konkurs dok postoje prijave koje nisu ocijenjene od svih članova komisije. Molimo prvo ocijenite sve prijave.']);
                        }
                    }
                }
            }
        }
        
        // Ako je deadline prošao, automatski odbij sve draft prijave
        if ($isDeadlinePassed) {
            $draftApplications = $competition->applications()
                ->where('status', 'draft')
                ->get();
            
            foreach ($draftApplications as $application) {
                $application->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Prijava nije podnesena u roku i rok za prijave je istekao.',
                ]);
            }
        }
        
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
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        
        // Privremeno: Administrator konkursa može brisati sve konkurse
        // Ostali ne mogu brisati završene konkurse
        if (in_array($competition->status, ['closed', 'completed']) && !$isCompetitionAdmin && !$isAdmin) {
            abort(403, 'Ne možete obrisati završeni konkurs.');
        }
        
        // Ako nije admin i nije konkurs_admin, proveri da li je predsjednik komisije i da li je konkurs dodijeljen njegovoj komisiji
        if (!$isAdmin && !$this->isCommissionChairmanForCompetition($competition)) {
            abort(403, 'Nemate dozvolu za brisanje ovog konkursa.');
        }
        
        // Proveri da li ima prijava (samo za aktivne konkurse, ali ne blokiraj konkurs_admin)
        if (!in_array($competition->status, ['closed', 'completed']) && $competition->applications()->count() > 0 && !$isCompetitionAdmin) {
            return redirect()->back()->withErrors(['error' => 'Ne možete obrisati konkurs koji već ima prijave.']);
        }

        $competition->delete();

        return redirect()->back()->with('success', 'Konkurs je uspješno obrisan.');
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
        
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin']);
        $isKomisija = $user->role && $user->role->name === 'komisija';
        
        $query = Application::with(['user', 'competition', 'evaluationScores']);

        // Filtriranje po statusu
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Članovi komisije ne mogu vidjeti draft prijave (uvijek, čak i ako eksplicitno traže status 'draft')
        if ($isKomisija && !$isAdmin) {
            $query->where('status', '!=', 'draft');
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
        $user = auth()->user();
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
        
        // Ako nije admin, proveri da li je član komisije i da li je prijava vezana za konkurs dodijeljen njegovoj komisiji
        if (!$isAdmin) {
            $competition = $application->competition;
            if (!$competition || !$this->isCommissionMemberForCompetition($competition)) {
                abort(403, 'Nemate pristup ovoj prijavi.');
            }
            
            // Članovi komisije mogu vidjeti samo prijave koje su podnesene (status 'submitted' ili viši)
            // Ne mogu vidjeti draft prijave
            if ($application->status === 'draft') {
                abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
            }
        }
        
        $application->load(['user', 'competition', 'businessPlan', 'documents', 'evaluationScores.commissionMember']);
        
        $application->load(['competition', 'businessPlan', 'documents', 'evaluationScores.commissionMember', 'contract', 'reports']);
        
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
        // Prikaži sve dostupne konkursa (draft i published)
        $competitions = Competition::whereIn('status', ['draft', 'published'])
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.commissions.create', compact('competitions'));
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
            'competition_ids' => 'nullable|array',
            'competition_ids.*' => 'exists:competitions,id',
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

        // Dodijeli komisiju izabranim konkursima
        if (!empty($validated['competition_ids']) && is_array($validated['competition_ids'])) {
            Competition::whereIn('id', $validated['competition_ids'])
                ->update(['commission_id' => $commission->id]);
        }

        $message = $createdMembers == 1 
            ? 'Komisija sa 1 članom je uspješno kreirana. Možete dodati ostale članove kasnije.' 
            : "Komisija sa {$createdMembers} članova je uspješno kreirana. Možete dodati ostale članove kasnije.";

        if (!empty($validated['competition_ids']) && is_array($validated['competition_ids'])) {
            $count = count($validated['competition_ids']);
            $message .= $count == 1 
                ? " Dodijeljen 1 konkurs ovoj komisiji." 
                : " Dodijeljeno {$count} konkursa ovoj komisiji.";
        }

        return redirect()->route('admin.commissions.show', $commission)
            ->with('success', $message);
    }

    /**
     * Prikaz određene komisije
     */
    public function showCommission(Commission $commission)
    {
        $commission->load(['members.user', 'competitions']);
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
        // Prikaži sve dostupne konkursa (draft i published)
        $competitions = Competition::whereIn('status', ['draft', 'published'])
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.commissions.edit', compact('commission', 'competitions'));
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
            'competition_ids' => 'nullable|array',
            'competition_ids.*' => 'exists:competitions,id',
        ]);

        $validated['end_date'] = $endDate ?? $validated['end_date'];
        
        $commission->update([
            'name' => $validated['name'],
            'year' => $validated['year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
        ]);

        // Ažuriraj dodjelu konkursa
        // Prvo ukloni dodjelu sa svih konkursa koji su bili dodijeljeni ovoj komisiji
        Competition::where('commission_id', $commission->id)
            ->update(['commission_id' => null]);
        
        // Zatim dodijeli izabrane konkursa ovoj komisiji
        if (!empty($validated['competition_ids']) && is_array($validated['competition_ids'])) {
            Competition::whereIn('id', $validated['competition_ids'])
                ->update(['commission_id' => $commission->id]);
        }

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
        $user = auth()->user();
        $isSuperAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin']);
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        $isChairman = $this->isCommissionChairmanForCompetition($competition);
        $isCommissionMember = $this->isCommissionMemberForCompetition($competition);
        
        // Administrator konkursa ne može pristupiti rang listi
        if ($isCompetitionAdmin) {
            abort(403, 'Administrator konkursa nema pristup rang listi.');
        }
        
        // Ako nije superadmin ili predsjednik komisije, proveri da li je član komisije
        if (!$isSuperAdmin && !$isChairman) {
            if (!$isCommissionMember) {
                abort(403, 'Nemate pristup ovom konkursu.');
            }
            // Članovi komisije mogu vidjeti rang listu kada je formirana (svi su ocjenili sve prijave),
            // ili kada je konkurs zatvoren, ili kada je rok za prijave istekao
            $canAccessRanking = $competition->isRankingFormed()
                || $competition->status === 'closed'
                || ($competition->status === 'published' && $competition->isApplicationDeadlinePassed());
            if (!$canAccessRanking) {
                abort(403, 'Rang lista je dostupna članovima komisije kada svi članovi ocjene sve prijave, kada je konkurs zatvoren ili kada je rok za prijave istekao.');
            }
        }
        
        // Učitaj sve prijave za ovaj konkurs
        $allApplications = Application::where('competition_id', $competition->id)
            ->with(['user', 'businessPlan', 'evaluationScores'])
            ->get()
            ->map(function ($application) {
                // Izračunaj konačnu ocjenu ako nije izračunata
                if (!$application->final_score) {
                    $application->final_score = $application->calculateFinalScore();
                    $application->save();
                }
                // Dodaj informacije o ocjenjivanju
                $application->has_evaluations = $application->evaluationScores()->count() > 0;
                $application->evaluation_count = $application->evaluationScores()->count();
                return $application;
            });

        // Pronađi predsjednika komisije
        $commission = $competition->commission;
        $chairmanMember = $commission ? $commission->activeMembers()->where('position', 'predsjednik')->first() : null;
        
        // Prijave odbijene zbog nedostatka dokumenata (documents_complete = false) - NE prikazuju se u rang listi
        $isRejectedForDocuments = function ($application) use ($chairmanMember) {
            if (!$chairmanMember) {
                return false;
            }
            $chairmanScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $chairmanMember->id)
                ->first();
            return $chairmanScore && $chairmanScore->documents_complete === false;
        };
        
        // Prijave koje se prikazuju u rang listi: imaju ocjene, NISU odbijene zbog dokumenata
        $visibleApplications = $allApplications
            ->filter(function ($application) use ($isRejectedForDocuments) {
                if (!$application->has_evaluations) {
                    return false;
                }
                if ($isRejectedForDocuments($application)) {
                    return false;
                }
                return true;
            });
        
        // Iznad crte: 30+ bodova, sortirano po ocjeni
        $applications = $visibleApplications
            ->filter(fn ($app) => $app->meetsMinimumScore())
            ->sortByDesc('final_score')
            ->values();
        
        // Ispod crte: ispod 30 bodova, sortirano po ocjeni
        $belowLineApplications = $visibleApplications
            ->filter(fn ($app) => !$app->meetsMinimumScore())
            ->sortByDesc('final_score')
            ->values();

        // Dodaj poziciju na rang listi samo za prijave iznad crte (30+ bodova)
        $position = 1;
        foreach ($applications as $application) {
            DB::table('applications')
                ->where('id', $application->id)
                ->update(['ranking_position' => $position]);
            $application->ranking_position = $position;
            $position++;
        }

        // Izračunaj ukupan budžet i preostali budžet
        $totalBudget = $competition->budget ?? 0;
        $usedBudget = $applications->sum('approved_amount');
        $remainingBudget = $totalBudget - $usedBudget;

        // Članovi komisije za potpis (predsjednik prvi, zatim članovi)
        $commissionMembers = $commission ? $commission->members()
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->with('user')
            ->get() : collect();

        return view('admin.competitions.ranking', compact('competition', 'applications', 'belowLineApplications', 'totalBudget', 'usedBudget', 'remainingBudget', 'isSuperAdmin', 'isChairman', 'isCommissionMember', 'commissionMembers'));
    }

    /**
     * Odabir dobitnika
     */
    public function selectWinners(Request $request, Competition $competition)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin']);
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        $isChairman = $this->isCommissionChairmanForCompetition($competition);
        
        // Samo superadmin i predsjednik komisije mogu odabirati dobitnike
        if ($isCompetitionAdmin || (!$isSuperAdmin && !$isChairman)) {
            abort(403, 'Nemate dozvolu za odabir dobitnika. Samo predsjednik komisije može odabirati dobitnike.');
        }
        
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
            ->with('success', 'Dobitnici sredstava su uspješno odabrani.');
    }

    /**
     * Generisanje Odluke
     */
    public function generateDecision(Competition $competition)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin']);
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        $isChairman = $this->isCommissionChairmanForCompetition($competition);
        
        // Samo superadmin i predsjednik komisije mogu generisati odluku
        if ($isCompetitionAdmin || (!$isSuperAdmin && !$isChairman)) {
            abort(403, 'Nemate dozvolu za generisanje odluke. Samo predsjednik komisije može generisati odluku.');
        }
        
        // Dobitnici sredstava su oni koji imaju approved_amount postavljen (veći od 0)
        $winners = Application::where('competition_id', $competition->id)
            ->whereNotNull('approved_amount')
            ->where('approved_amount', '>', 0)
            ->with(['user', 'businessPlan'])
            ->orderBy('ranking_position')
            ->orderBy('id')
            ->get();

        // Generiši PDF ili pripremi podatke za prikaz
        return view('admin.competitions.decision', compact('competition', 'winners', 'isSuperAdmin', 'isChairman'));
    }
}

