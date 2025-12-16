<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Competition;
use App\Models\Application;
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
}

