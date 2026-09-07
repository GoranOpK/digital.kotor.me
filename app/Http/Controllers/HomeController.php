<?php

namespace App\Http\Controllers;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CountryCatalog;
use App\Identity\PhoneCallingCodeCatalog;
use App\Identity\Runtime\CanonicalHttpIdentityService;
use App\Identity\Runtime\IdentityMutationDeniedException;
use App\Http\Requests\RegisterSubjectRequest;
use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Notice;
use App\Models\User;
use App\Support\UserType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    public function index()
    {
        $activeNotices = Notice::query()
            ->where('visible_in_active_panel', true)
            ->with(['sourceObject.competition'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('landing', [
            'landingCategories' => $this->getLandingCategories(),
            'activeNotices' => $activeNotices,
        ]);
    }

    /**
     * Kategorije na naslovnoj — administrator konkursa i član komisije vide samo Konkursi.
     */
    protected function getLandingCategories(): array
    {
        $defaultCategories = [
            ['label' => 'Plaćanja', 'url' => route('payments.index')],
            ['label' => 'Konkursi', 'url' => route('competitions.index')],
            ['label' => 'Tenderi', 'url' => route('tenders.index')],
            ['label' => 'Kalendar kulture', 'url' => route('cultural-calendar.index')],
        ];

        $user = auth()->user();
        if (! $user || ! $user->role) {
            return $defaultCategories;
        }

        return match ($user->role->name) {
            'konkurs_admin' => [
                ['label' => 'Konkursi', 'url' => route('admin.dashboard')],
            ],
            'komisija' => [
                ['label' => 'Konkursi', 'url' => $this->getCommissionMemberCompetitionUrl($user)],
            ],
            default => $defaultCategories,
        };
    }

    /**
     * Link na konkurs dodijeljen komisiji člana (ili panel ako nema konkursa).
     */
    protected function getCommissionMemberCompetitionUrl(User $user): string
    {
        $membership = CommissionMember::activeMembershipForUser($user->id);
        if (! $membership) {
            return route('dashboard');
        }

        $competitions = Competition::where('commission_id', $membership->commission_id)
            ->whereIn('status', ['published', 'closed', 'completed'])
            ->orderByDesc('published_at')
            ->get();

        if ($competitions->isEmpty()) {
            return route('dashboard');
        }

        $primary = $competitions->first(fn (Competition $competition) => $competition->is_open)
            ?? $competitions->first(fn (Competition $competition) => $competition->is_upcoming)
            ?? $competitions->first(fn (Competition $competition) => $competition->status === 'published')
            ?? $competitions->first();

        return route('admin.competitions.show', $primary);
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

            $default = route('home');
            $user = Auth::user();
            if ($user && $user->role && $user->role->name === 'kk_admin') {
                $default = route('cultural-calendar.index');
            } elseif ($user && $user->role && $user->role->name === 'konkurs_admin') {
                $request->session()->forget('url.intended');

                return redirect()->route('admin.dashboard');
            }

            return $this->redirectAfterLogin($request, $default);
        }

        return back()->withErrors([
            'email' => 'Pogrešan email ili lozinka, ili nalog nije aktivan.',
        ])->onlyInput('email');
    }

    /**
     * Preserve intended only for safe same-app URLs (no open redirect).
     */
    private function redirectAfterLogin(Request $request, string $default): \Illuminate\Http\RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return redirect()->to($default);
        }

        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return redirect()->to($intended);
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && (str_starts_with($intended, $appUrl.'/') || $intended === $appUrl)) {
            return redirect()->to($intended);
        }

        return redirect()->to($default);
    }

    public function registerForm()
    {
        $countryOptions = [];
        foreach (CountryCatalog::codes() as $code) {
            $countryOptions[$code] = CountryCatalog::label($code) ?? $code;
        }

        return view('auth.register', [
            'businessTypeOptions' => UserType::registrationLegalEntityOptions(),
            'countryOptions' => $countryOptions,
            'phonePickerEntries' => PhoneCallingCodeCatalog::pickerEntries(),
            'crpsRequiredForms' => [
                UserType::ENTREPRENEUR,
                UserType::GENERAL_PARTNERSHIP,
                UserType::LIMITED_PARTNERSHIP,
                UserType::JOINT_STOCK_COMPANY,
                UserType::LIMITED_LIABILITY_COMPANY,
            ],
        ]);
    }

    public function register(RegisterSubjectRequest $request)
    {
        $validated = $request->validated();
        $storedUserType = $request->storedUserType();
        $jmb = $request->resolvedJmb();
        $phone = $request->composedPhone();

        if (isset($validated['first_name'])) {
            $validated['first_name'] = mb_convert_case(mb_strtolower($validated['first_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        if (isset($validated['last_name'])) {
            $validated['last_name'] = mb_convert_case(mb_strtolower($validated['last_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        if (isset($validated['authorized_first_name'])) {
            $validated['authorized_first_name'] = mb_convert_case(mb_strtolower($validated['authorized_first_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        if (isset($validated['authorized_last_name'])) {
            $validated['authorized_last_name'] = mb_convert_case(mb_strtolower($validated['authorized_last_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        if (isset($validated['representative_first_name'])) {
            $validated['representative_first_name'] = mb_convert_case(mb_strtolower($validated['representative_first_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        if (isset($validated['representative_last_name'])) {
            $validated['representative_last_name'] = mb_convert_case(mb_strtolower($validated['representative_last_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        $userData = [
            'name' => $request->accountDisplayName(),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role_id' => 3,
            'activation_status' => 'active',
            'phone' => $phone,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'residential_status' => UserType::requiresResidentialStatus($storedUserType)
                ? ($validated['residential_status'] ?? null)
                : null,
        ];

        if (! UserType::isNgoFoundation($storedUserType)) {
            $userData['user_type'] = UserType::isForeignBranch($storedUserType)
                ? UserType::LEGACY_FOREIGN_BRANCH
                : $storedUserType;
        }

        try {
            $user = app(CanonicalHttpIdentityService::class)->registerFromValidated(
                $userData,
                $validated,
                $jmb,
                $storedUserType,
            );
        } catch (IdentityMutationDeniedException $e) {
            abort(403, $e->getMessage());
        } catch (CanonicalIdentityWriteException $e) {
            $message = $e->getMessage();
            $field = 'user_type';
            if (str_contains($message, 'JMB')) {
                $field = 'jmb';
            } elseif (str_contains($message, 'PIB')) {
                $field = 'pib';
            } elseif (str_contains($message, 'pasoš') || str_contains($message, 'paso')) {
                $field = 'passport_number';
            }

            return back()->withErrors([
                $field => $message === 'Canonical registration failed.' || $message === 'Canonical identity create failed.'
                    ? 'Registracija identiteta nije uspjela.'
                    : $message,
            ])->withInput();
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice')->with('status', 'registration-success');
    }

    public function dashboard()
    {
        $user = auth()->user();
        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
        $isSuperAdmin = $user->role && $user->role->name === 'superadmin';
        $isKomisija = $user->role && $user->role->name === 'komisija';
        $isKkAdmin = $user->role && $user->role->name === 'kk_admin';

        // KK administrator koristi isključivo modul Kalendar kulture
        if ($isKkAdmin) {
            return redirect()->route('cultural-calendar.index');
        }

        // Administrator konkursa koristi konkursni administratorski panel
        if ($isCompetitionAdmin) {
            return redirect()->route('admin.dashboard');
        }

        // Ako je konkurs admin, pripremi podatke za admin dashboard

        // Ako je član komisije, pripremi podatke za dashboard komisije
        if ($isKomisija) {
            // Pronađi člana komisije
            $commissionMember = \App\Models\CommissionMember::activeMembershipForUser($user->id);

            if ($commissionMember) {
                // Učitaj komisiju sa njenim konkursima
                $commission = $commissionMember->commission;
                $commission->load('competitions');

                $competitionIds = $commission->competitions->pluck('id');

                // Sekcija "Prijave za ocjenjivanje" vidljiva je komisiji tek nakon isteka roka za prijavljivanje
                $competitionsWithDeadlinePassed = $commission->competitions->filter(function ($c) {
                    return in_array($c->status, ['closed', 'completed']) || $c->isApplicationDeadlinePassed();
                });
                $showEvaluationSection = $competitionsWithDeadlinePassed->isNotEmpty();
                $competitionIdsForEvaluation = $competitionsWithDeadlinePassed->pluck('id');

                // Pronađi prijave za konkurse gdje je rok istekao – samo prijave koje član JOŠ NIJE ocjenio
                $evaluatedApplicationIds = \App\Models\EvaluationScore::where('commission_member_id', $commissionMember->id)
                    ->whereNotNull('criterion_1')
                    ->pluck('application_id')
                    ->toArray();

                $applicationsQuery = Application::whereIn('competition_id', $competitionIdsForEvaluation)
                    ->whereIn('status', ['submitted', 'evaluated'])
                    ->whereEliminatoryScoringNotBlocked()
                    ->with(['competition', 'user', 'businessPlan', 'evaluationScores', 'evaluationScores.commissionMember', 'eliminatoryCheck', 'prigovor']);

                if (! empty($evaluatedApplicationIds)) {
                    $applicationsQuery->whereNotIn('id', $evaluatedApplicationIds);
                }

                $applications = $applicationsQuery->latest()->get();
                $applications->each(function ($app) use ($commissionMember) {
                    if ($app->isEliminatedFromScoring()) {
                        $app->is_evaluated_by_member = true;

                        return;
                    }
                    $app->is_evaluated_by_member = \App\Models\EvaluationScore::where('application_id', $app->id)
                        ->where('commission_member_id', $commissionMember->id)
                        ->whereNotNull('criterion_1')
                        ->exists();
                });

                // Moje prijave - samo prijave koje je korisnik lično podneo
                $myApplications = Application::where('user_id', $user->id)
                    ->whereIn('competition_id', $competitionIds)
                    ->with('competition', 'businessPlan')
                    ->latest()
                    ->get();

                // Učitaj konkursi za prikaz preostalog vremena
                $competitions = \App\Models\Competition::whereIn('id', $competitionIds->toArray())
                    ->whereIn('status', ['published', 'closed', 'completed'])
                    ->get();

                return view('dashboard', compact('applications', 'commissionMember', 'commission', 'isKomisija', 'myApplications', 'competitions', 'showEvaluationSection'));
            }

            return view('dashboard', compact('isKomisija', 'isSuperAdmin'));
        }

        // Za običnog korisnika
        $applications = Application::where('user_id', $user->id)
            ->with('competition', 'businessPlan')
            ->latest()
            ->get();

        // Podaci za skladište dokumenata
        $maxStorageMB = 20;
        $usedStorageMB = round($user->used_storage_bytes / (1024 * 1024), 2);
        $storagePercentage = min(round(($usedStorageMB / $maxStorageMB) * 100), 100);

        return view('dashboard', compact('applications', 'usedStorageMB', 'maxStorageMB', 'storagePercentage', 'isSuperAdmin'));
    }
}
