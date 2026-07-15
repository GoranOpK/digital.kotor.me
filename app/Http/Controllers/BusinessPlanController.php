<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Rules\KotorMunicipalityAddress;
use App\Support\PhoneNumber;
use App\Support\Pib;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BusinessPlanController extends Controller
{
    /**
     * Pouzdano logovanje za debug čuvanja biznis plana.
     * Piše u laravel.log, channel business_plan i direktno u fajl (fallback).
     */
    protected function bpLog(string $message, array $context = [], string $level = 'info'): void
    {
        try {
            Log::channel('business_plan')->{$level}($message, $context);
        } catch (\Throwable $e) {
            // ignore channel errors
        }

        try {
            Log::{$level}($message, $context);
        } catch (\Throwable $e) {
            // ignore facade errors
        }

        try {
            $line = '[' . now()->toDateTimeString() . "] {$level}: {$message} "
                . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . PHP_EOL;
            @file_put_contents(storage_path('logs/business-plan.log'), $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore filesystem errors
        }
    }

    /**
     * PIB iz Obrasca 1a/1b (prijava) ili korisničkog profila.
     */
    protected function resolvePibFromApplication(Application $application, $user): ?string
    {
        if (filled($application->pib)) {
            return $application->pib;
        }

        if ($user && filled($user->pib)) {
            return $user->pib;
        }

        return null;
    }

    /**
     * Prikaz forme za popunjavanje biznis plana (Obrazac 2)
     */
    public function create(Application $application): View
    {
        $this->bpLog('BP_CREATE: entered create()', [
            'application_id' => $application->id,
            'auth_user_id' => Auth::id(),
            'url' => request()->fullUrl(),
        ]);

        // Prikaz biznis plana:
        // - vlasnik prijave uvijek može da vidi/uređuje
        // - član komisije može da vidi samo biznis plan za konkurse dodijeljene njegovoj komisiji (read-only)
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $roleName = $user->role ? $user->role->name : null;

        $isCommissionMemberForThisCompetition = false;
        $isCompetitionAdminArchiveAccess = false;
        if ($roleName === 'komisija') {
            $competition = $application->competition;
            if ($competition && $competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::activeForCommission(
                    $user->id,
                    $competition->commission_id
                );

                if ($commissionMember) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
        }

        if ($roleName === 'konkurs_admin') {
            $competition = $application->competition;
            if ($competition && in_array($competition->status, ['closed', 'completed'], true)) {
                $isCompetitionAdminArchiveAccess = true;
            }
        }

        if (!$isOwner && !$isCommissionMemberForThisCompetition && !$isCompetitionAdminArchiveAccess) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        if ($isCommissionMemberForThisCompetition) {
            if ($application->status === 'draft') {
                abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
            }

            $competition = $application->competition;
            if ($competition && !in_array($competition->status, ['closed', 'completed']) && !$competition->isApplicationDeadlinePassed()) {
                abort(403, 'Prijave su komisiji vidljive tek nakon isteka roka za prijavljivanje na konkurs.');
            }
        }

        // Proveri da li je Obrazac 1a/1b kompletno popunjen
        if (!$application->isObrazacComplete()) {
            $url = route('applications.create', $application->competition_id) . '?application_id=' . $application->id;

            return redirect()->to($url)
                ->withErrors(['error' => 'Molimo popunite kompletan Obrazac 1a ili 1b (sva obavezna polja i potvrdite sve obavezne izjave) pre nego što nastavite na biznis plan.'])
                ->withInput();
        }

        // Proveri da li već postoji biznis plan
        $businessPlan = $application->businessPlan;

        $this->bpLog('BP_CREATE: open form', [
            'application_id' => $application->id,
            'user_id' => $user->id,
            'is_owner' => $isOwner,
            'read_only_role' => $roleName,
            'has_business_plan' => (bool) $businessPlan,
            'business_plan_id' => $businessPlan?->id,
            'bp_updated_at' => optional($businessPlan?->updated_at)?->toDateTimeString(),
            'db_scalars' => $businessPlan ? [
                'business_idea_name' => $businessPlan->business_idea_name,
                'applicant_name' => $businessPlan->applicant_name,
                'applicant_jmbg' => $businessPlan->applicant_jmbg,
                'applicant_address' => $businessPlan->applicant_address,
                'applicant_phone' => $businessPlan->applicant_phone,
                'applicant_email' => $businessPlan->applicant_email,
                'summary_len' => is_string($businessPlan->summary) ? strlen($businessPlan->summary) : null,
                'required_amount' => $businessPlan->required_amount,
                'requested_amount' => $businessPlan->requested_amount,
                'promotion_len' => is_string($businessPlan->promotion) ? strlen($businessPlan->promotion) : null,
                'business_analysis_len' => is_string($businessPlan->business_analysis) ? strlen($businessPlan->business_analysis) : null,
                'work_experience_len' => is_string($businessPlan->work_experience) ? strlen($businessPlan->work_experience) : null,
            ] : null,
            'db_table_counts' => $businessPlan ? [
                'products_services_table' => is_array($businessPlan->products_services_table) ? count($businessPlan->products_services_table) : null,
                'target_customers' => is_array($businessPlan->target_customers) ? count($businessPlan->target_customers) : null,
                'sales_locations' => is_array($businessPlan->sales_locations) ? count($businessPlan->sales_locations) : null,
                'pricing_table' => is_array($businessPlan->pricing_table) ? count($businessPlan->pricing_table) : null,
                'revenue_share_table' => is_array($businessPlan->revenue_share_table) ? count($businessPlan->revenue_share_table) : null,
                'employment_structure' => is_array($businessPlan->employment_structure) ? count($businessPlan->employment_structure) : null,
                'business_history' => is_array($businessPlan->business_history) ? count($businessPlan->business_history) : null,
                'suppliers_table' => is_array($businessPlan->suppliers_table) ? count($businessPlan->suppliers_table) : null,
                'funding_sources_table' => is_array($businessPlan->funding_sources_table) ? count($businessPlan->funding_sources_table) : null,
                'revenue_projection' => is_array($businessPlan->revenue_projection) ? count($businessPlan->revenue_projection) : null,
                'expense_projection' => is_array($businessPlan->expense_projection) ? count($businessPlan->expense_projection) : null,
                'job_schedule' => is_array($businessPlan->job_schedule) ? count($businessPlan->job_schedule) : null,
                'risk_matrix' => is_array($businessPlan->risk_matrix) ? count($businessPlan->risk_matrix) : null,
            ] : null,
            'is_complete' => $businessPlan?->isComplete(),
        ]);
        
        if ($businessPlan) {
            // Razdvoji expense_projection na investment_expenses i operating_expenses
            $investmentExpenses = [];
            $operatingExpenses = [];
            if ($businessPlan->expense_projection && is_array($businessPlan->expense_projection)) {
                foreach ($businessPlan->expense_projection as $expense) {
                    if (isset($expense['category']) && $expense['category'] === 'investment') {
                        $expenseCopy = $expense;
                        unset($expenseCopy['category']);
                        $investmentExpenses[] = $expenseCopy;
                    } elseif (isset($expense['category']) && $expense['category'] === 'operating') {
                        $expenseCopy = $expense;
                        unset($expenseCopy['category']);
                        $operatingExpenses[] = $expenseCopy;
                    }
                }
            }
            
            // Postavi privremene svojstva za prikaz u view-u
            if (empty($investmentExpenses)) {
                $investmentExpenses = [['type' => '', 'year1' => '', 'year2' => '', 'year3' => '']];
            }
            if (empty($operatingExpenses)) {
                $operatingExpenses = [['type' => '', 'year1' => '', 'year2' => '', 'year3' => '']];
            }
            
            $businessPlan->setAttribute('investment_expenses', $investmentExpenses);
            $businessPlan->setAttribute('operating_expenses', $operatingExpenses);

            $this->bpLog('BP_CREATE: expense split for view', [
                'application_id' => $application->id,
                'business_plan_id' => $businessPlan->id,
                'investment_count' => count($investmentExpenses),
                'operating_count' => count($operatingExpenses),
            ]);
        } else {
            $this->bpLog('BP_CREATE: no business plan found', [
                'application_id' => $application->id,
            ]);
        }

        // Pripremi podatke za automatsko popunjavanje iz prijave
        $application->loadMissing('user');
        $applicantUser = $application->user ?? Auth::user();
        $defaultData = [];
        
        // Podaci o podnosiocu - uzmi iz prijave ili korisničkog profila podnosioca
        if ($application->applicant_type === 'fizicko_lice') {
            // Za fizičko lice, podaci su u prijavi
            $defaultData['applicant_name'] = $application->physical_person_name ?? $applicantUser->name ?? '';
            $defaultData['applicant_jmbg'] = $application->physical_person_jmbg ?? $applicantUser->jmb ?? '';
            $defaultData['applicant_phone'] = PhoneNumber::normalize($application->physical_person_phone ?? $applicantUser->phone ?? '');
            $defaultData['applicant_email'] = $application->physical_person_email ?? $applicantUser->email ?? '';
            $defaultData['applicant_address'] = $applicantUser->formattedAddress();
        } elseif ($application->applicant_type === 'preduzetnica') {
            // Za preduzetnicu, podaci su u korisničkom profilu
            $defaultData['applicant_name'] = $applicantUser->name ?? '';
            $defaultData['applicant_jmbg'] = $application->resolvedApplicantJmbg() ?? '';
            $defaultData['applicant_phone'] = PhoneNumber::normalize($applicantUser->phone ?? '');
            $defaultData['applicant_email'] = $applicantUser->email ?? '';
            $defaultData['applicant_address'] = $applicantUser->formattedAddress();
        } elseif ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') {
            // Za DOO/Ostalo, podaci su u korisničkom profilu
            $defaultData['applicant_name'] = $applicantUser->name ?? '';
            $defaultData['applicant_jmbg'] = $application->resolvedApplicantJmbg() ?? '';
            $defaultData['applicant_phone'] = PhoneNumber::normalize($applicantUser->phone ?? '');
            $defaultData['applicant_email'] = $applicantUser->email ?? '';
            $defaultData['applicant_address'] = $applicantUser->formattedAddress();
        }

        // Podaci o registrovanom biznisu - uzmi iz prijave (Obrazac 1a/1b)
        $defaultData['has_registered_business'] = $application->is_registered ?? false;
        $defaultData['registration_form'] = $application->registration_form ?? '';
        $resolvedPib = $this->resolvePibFromApplication($application, $applicantUser);
        $defaultData['pib'] = $resolvedPib ?? '';
        $defaultData['vat_number'] = $application->vat_number ?? '';
        $defaultData['bank_account'] = $application->bank_account ?? '';
        $defaultData['company_website'] = $application->website ?? '';

        // IV. FINANSIJE - povuci podatke iz Obrasca 1a/1b
        $defaultData['required_amount'] = $application->total_budget_needed ?? null;
        $defaultData['requested_amount'] = $application->requested_amount ?? null;

        // Ako već postoji biznis plan, koristi njegove podatke, inače koristi default podatke
        if ($businessPlan) {
            // Ako biznis plan već ima podatke, koristi ih
            $defaultData = array_merge($defaultData, [
                'applicant_name' => $businessPlan->applicant_name ?? $defaultData['applicant_name'],
                'applicant_jmbg' => filled($businessPlan->applicant_jmbg) ? $businessPlan->applicant_jmbg : $defaultData['applicant_jmbg'],
                'applicant_phone' => $businessPlan->applicant_phone ?? $defaultData['applicant_phone'],
                'applicant_email' => $businessPlan->applicant_email ?? $defaultData['applicant_email'],
                'applicant_address' => $businessPlan->applicant_address ?? $defaultData['applicant_address'],
                'has_registered_business' => $businessPlan->has_registered_business ?? $defaultData['has_registered_business'],
                'registration_form' => $businessPlan->registration_form ?? $defaultData['registration_form'],
                'pib' => filled($businessPlan->pib) ? $businessPlan->pib : $defaultData['pib'],
                'vat_number' => $businessPlan->vat_number ?? $defaultData['vat_number'],
                'bank_account' => $businessPlan->bank_account ?? $defaultData['bank_account'],
                'company_website' => $businessPlan->company_website ?? $defaultData['company_website'],
                // IV. FINANSIJE - koristi postojeće podatke ako postoje, inače koristi podatke iz prijave
                'required_amount' => $businessPlan->required_amount ?? $defaultData['required_amount'],
                'requested_amount' => $businessPlan->requested_amount ?? $defaultData['requested_amount'],
            ]);
        }

        // Član komisije i administrator konkursa (arhiva) mogu samo da pregledaju (read-only), bez izmjena
        $readOnly = !$isOwner;
        
        // Ako je član komisije, osiguraj da je readOnly = true
        if ($roleName === 'komisija' && !$readOnly) {
            abort(403, 'Članovi komisije mogu samo pregledati biznis planove u read-only modu.');
        }

        return view('business-plans.create', compact('application', 'businessPlan', 'defaultData', 'readOnly', 'resolvedPib'));
    }

    /**
     * Snimi biznis plan
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->bpLog('BP_STORE: start', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'content_length' => $request->header('Content-Length'),
            'save_as_draft_raw' => $request->input('save_as_draft'),
            'request_keys' => array_keys($request->except(['_token'])),
        ]);

        // Blokiraj članove komisije od čuvanja izmjena
        $user = Auth::user();
        $roleName = $user->role ? $user->role->name : null;
        if ($roleName === 'komisija') {
            $this->bpLog('BP_STORE: blocked commission member', [
                'application_id' => $application->id,
                'user_id' => $user->id,
            ]);
            abort(403, 'Članovi komisije mogu samo pregledati biznis planove, ne mogu ih mijenjati.');
        }
        
        // Proveri da li prijava pripada korisniku
        if ($application->user_id !== Auth::id()) {
            $this->bpLog('BP_STORE: ownership mismatch', [
                'application_id' => $application->id,
                'application_user_id' => $application->user_id,
                'auth_user_id' => Auth::id(),
            ]);
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li se čuva kao nacrt
        $isDraft = $request->has('save_as_draft') && $request->save_as_draft === '1';

        $this->bpLog('BP_STORE: before validate', [
            'application_id' => $application->id,
            'is_draft' => $isDraft,
            'scalar_preview' => [
                'business_idea_name' => $request->input('business_idea_name'),
                'applicant_name' => $request->input('applicant_name'),
                'applicant_jmbg' => $request->input('applicant_jmbg'),
                'applicant_phone' => $request->input('applicant_phone'),
                'applicant_email' => $request->input('applicant_email'),
                'summary_len' => is_string($request->input('summary')) ? strlen($request->input('summary')) : null,
                'required_amount' => $request->input('required_amount'),
                'requested_amount' => $request->input('requested_amount'),
                'finances_notice_confirmed' => $request->input('finances_notice_confirmed'),
            ],
            'table_counts' => [
                'products_services_table' => is_array($request->input('products_services_table')) ? count($request->input('products_services_table')) : null,
                'target_customers' => is_array($request->input('target_customers')) ? count($request->input('target_customers')) : null,
                'sales_locations' => is_array($request->input('sales_locations')) ? count($request->input('sales_locations')) : null,
                'pricing_table' => is_array($request->input('pricing_table')) ? count($request->input('pricing_table')) : null,
                'revenue_share_table' => is_array($request->input('revenue_share_table')) ? count($request->input('revenue_share_table')) : null,
                'employment_structure' => is_array($request->input('employment_structure')) ? count($request->input('employment_structure')) : null,
                'business_history' => is_array($request->input('business_history')) ? count($request->input('business_history')) : null,
                'suppliers_table' => is_array($request->input('suppliers_table')) ? count($request->input('suppliers_table')) : null,
                'funding_sources_table' => is_array($request->input('funding_sources_table')) ? count($request->input('funding_sources_table')) : null,
                'revenue_projection' => is_array($request->input('revenue_projection')) ? count($request->input('revenue_projection')) : null,
                'investment_expenses' => is_array($request->input('investment_expenses')) ? count($request->input('investment_expenses')) : null,
                'operating_expenses' => is_array($request->input('operating_expenses')) ? count($request->input('operating_expenses')) : null,
                'job_schedule' => is_array($request->input('job_schedule')) ? count($request->input('job_schedule')) : null,
                'risk_matrix' => is_array($request->input('risk_matrix')) ? count($request->input('risk_matrix')) : null,
            ],
        ]);

        // Validacija
        try {
            $validated = $request->validate([
            // I. OSNOVNI PODACI
            'business_idea_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'applicant_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'applicant_jmbg' => $isDraft ? 'nullable|string|max:13' : 'required|string|max:13',
            'applicant_address' => $isDraft ? ['nullable', 'string'] : ['required', 'string', new KotorMunicipalityAddress()],
            'applicant_phone' => $isDraft ? 'nullable|string|max:50' : 'required|string|max:50',
            'applicant_email' => $isDraft ? 'nullable|email|max:255' : 'required|email|max:255',
            'has_registered_business' => 'nullable|boolean',
            'registration_form' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'pib' => $isDraft ? 'nullable|string|max:50' : ['nullable', 'string', 'regex:'.Pib::REGEX],
            'vat_number' => 'nullable|string|max:50',
            'company_address' => ['nullable', 'string', new KotorMunicipalityAddress()],
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'summary' => $isDraft ? 'nullable|string' : 'required|string',
            
            // II. MARKETING
            'products_services_table' => 'nullable|array',
            'realization_type' => 'nullable|string|max:255',
            'target_customers' => 'nullable|array',
            'sales_locations' => 'nullable|array',
            'has_business_space' => 'nullable|string|max:50',
            'pricing_table' => 'nullable|array',
            'annual_sales_volume' => 'nullable|numeric|min:0',
            'revenue_share_table' => 'nullable|array',
            'promotion' => 'nullable|string',
            'employment_structure' => 'nullable|array',
            'has_seasonal_workers' => 'nullable|boolean',
            'competition_analysis' => 'nullable|string',
            'competition_analysis_part1' => 'nullable|string',
            'competition_analysis_part2' => 'nullable|string',
            
            // III. POSLOVANJE
            'business_analysis' => 'nullable|string',
            'business_history' => 'nullable|array',
            'required_resources' => 'nullable|string',
            'suppliers_table' => 'nullable|array',
            'annual_purchases_volume' => 'nullable|numeric|min:0',
            
            // IV. FINANSIJE
            'required_amount' => 'nullable|numeric|min:0',
            'requested_amount' => 'nullable|numeric|min:0',
            'funding_sources_table' => 'nullable|array',
            'funding_alternative' => 'nullable|string|max:50',
            'revenue_projection' => 'nullable|array',
            'expense_projection' => 'nullable|array',
            
            // V. LJUDI
            'work_experience' => 'nullable|string',
            'personal_strengths_weaknesses' => 'nullable|string',
            'biggest_support' => 'nullable|string|max:255',
            'job_schedule' => 'nullable|array',
            
            // VI. RIZICI
            'risk_matrix' => 'nullable|array',

            'finances_notice_confirmed' => $isDraft ? 'nullable' : 'accepted',
        ], [
            'business_idea_name.required' => 'Naziv biznis ideje je obavezan.',
            'applicant_name.required' => 'Ime i prezime podnosioca je obavezno.',
            'applicant_jmbg.required' => 'JMBG je obavezan.',
            'applicant_address.required' => 'Adresa je obavezna.',
            'applicant_phone.required' => 'Kontakt telefon je obavezan.',
            'applicant_email.required' => 'E-mail je obavezan.',
            'summary.required' => 'Rezime je obavezno.',
            'pib.regex' => Pib::VALIDATION_MESSAGE,
            'finances_notice_confirmed.accepted' => 'Potrebno je potvrditi da ste pročitali napomenu u dijelu IV. Finansije.',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->bpLog('BP_STORE: validation failed', [
                'application_id' => $application->id,
                'is_draft' => $isDraft,
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        $this->bpLog('BP_STORE: validation ok', [
            'application_id' => $application->id,
            'validated_keys' => array_keys($validated),
            'validated_scalars' => [
                'business_idea_name' => $validated['business_idea_name'] ?? null,
                'applicant_name' => $validated['applicant_name'] ?? null,
                'summary_len' => isset($validated['summary']) && is_string($validated['summary']) ? strlen($validated['summary']) : null,
                'required_amount' => $validated['required_amount'] ?? null,
                'requested_amount' => $validated['requested_amount'] ?? null,
            ],
        ]);

        // Očisti i pripremi podatke iz tabela - osiguraj da se čuvaju svi redovi
        // Koristi $request->all() umjesto $validated za tabela polja jer $validated možda ne sadrži sve podatke
        $tableFields = [
            'products_services_table',
            'target_customers',
            'sales_locations',
            'pricing_table',
            'revenue_share_table',
            'employment_structure',
            'business_history',
            'suppliers_table',
            'funding_sources_table',
            'revenue_projection',
            'expense_projection',
            'job_schedule',
            'risk_matrix',
        ];

        $cleanedData = $validated;
        unset($cleanedData['finances_notice_confirmed']);

        // Spoji dva textarea polja analize konkurencije u jedno DB polje
        $competitionPart1 = trim((string) $request->input('competition_analysis_part1', ''));
        $competitionPart2 = trim((string) $request->input('competition_analysis_part2', ''));
        unset($cleanedData['competition_analysis_part1'], $cleanedData['competition_analysis_part2']);
        if ($competitionPart1 !== '' || $competitionPart2 !== '') {
            $cleanedData['competition_analysis'] = trim($competitionPart1 . ($competitionPart1 !== '' && $competitionPart2 !== '' ? "\n\n---\n\n" : '') . $competitionPart2);
        } elseif ($request->has('competition_analysis_part1') || $request->has('competition_analysis_part2')) {
            // Eksplicitno prazna oba dijela – sačuvaj prazan tekst umjesto da ostavimo staro
            $cleanedData['competition_analysis'] = null;
        }

        if (array_key_exists('applicant_phone', $cleanedData)) {
            $cleanedData['applicant_phone'] = PhoneNumber::normalize($cleanedData['applicant_phone']);
        }
        if (array_key_exists('company_phone', $cleanedData)) {
            $cleanedData['company_phone'] = PhoneNumber::normalize($cleanedData['company_phone']);
        }

        if (empty($cleanedData['pib'])) {
            $applicationPib = $this->resolvePibFromApplication($application, $user);
            if ($applicationPib) {
                $cleanedData['pib'] = $applicationPib;
            }
        }

        $rowHasValue = static function ($row): bool {
            if (!is_array($row)) {
                return false;
            }
            foreach ($row as $value) {
                if (is_array($value)) {
                    continue;
                }
                if ($value !== null && $value !== '' && trim((string) $value) !== '') {
                    return true;
                }
            }
            return false;
        };

        foreach ($tableFields as $field) {
            // Koristi podatke direktno iz request-a, ne iz validated
            if ($request->has($field) && is_array($request->input($field))) {
                $tableData = $request->input($field);

                $this->bpLog('BP_STORE: raw table sample', [
                    'application_id' => $application->id,
                    'field' => $field,
                    'raw_count' => count($tableData),
                    'first_row' => $tableData[0] ?? ($tableData[array_key_first($tableData)] ?? null),
                ]);

                // Filtriraj prazne redove - zadrži samo redove gdje je barem jedno polje popunjeno
                $cleanedData[$field] = array_values(array_filter($tableData, $rowHasValue));

                // VAŽNO: ako su svi redovi prazni, NE briši postojeće podatke iz baze
                // (prazni placeholder redovi bi inače pregazili ranije sačuvane tabele)
                if (empty($cleanedData[$field])) {
                    unset($cleanedData[$field]);
                    $this->bpLog('BP_STORE: table empty in request – preserving existing DB value', [
                        'application_id' => $application->id,
                        'field' => $field,
                    ]);
                }
            } elseif (isset($cleanedData[$field]) && is_array($cleanedData[$field])) {
                $cleanedData[$field] = array_values(array_filter($cleanedData[$field], $rowHasValue));
                if (empty($cleanedData[$field])) {
                    unset($cleanedData[$field]);
                }
            } else {
                unset($cleanedData[$field]);
            }
        }
        
        // Posebna logika za expense_projection - kombinuj investment_expenses i operating_expenses
        if ($request->has('investment_expenses') || $request->has('operating_expenses')) {
            $expenseProjection = [];
            
            if ($request->has('investment_expenses') && is_array($request->input('investment_expenses'))) {
                $investmentExpenses = array_values(array_filter($request->input('investment_expenses'), $rowHasValue));
                foreach ($investmentExpenses as $expense) {
                    $expense['category'] = 'investment';
                    $expenseProjection[] = $expense;
                }
            }
            
            if ($request->has('operating_expenses') && is_array($request->input('operating_expenses'))) {
                $operatingExpenses = array_values(array_filter($request->input('operating_expenses'), $rowHasValue));
                foreach ($operatingExpenses as $expense) {
                    $expense['category'] = 'operating';
                    $expenseProjection[] = $expense;
                }
            }
            
            if (!empty($expenseProjection)) {
                $cleanedData['expense_projection'] = $expenseProjection;
                $this->bpLog('BP_STORE: expense_projection combined', [
                    'application_id' => $application->id,
                    'count' => count($expenseProjection),
                    'data' => $expenseProjection,
                ]);
            } else {
                // Nemoj pregaziti postojeći expense_projection praznim placeholderima
                unset($cleanedData['expense_projection']);
                $this->bpLog('BP_STORE: expense_projection empty – preserving existing', [
                    'application_id' => $application->id,
                ]);
            }
        }

        $payloadForSave = array_merge(
            $cleanedData,
            [
                'has_registered_business' => $request->has('has_registered_business') ? (bool)$request->has_registered_business : null,
                'has_seasonal_workers' => $request->has('has_seasonal_workers') ? (bool)$request->has_seasonal_workers : null,
                'finances_notice_confirmed' => $request->boolean('finances_notice_confirmed'),
            ]
        );

        $existingBefore = BusinessPlan::where('application_id', $application->id)->first();

        $this->bpLog('BP_STORE: before updateOrCreate', [
            'application_id' => $application->id,
            'existing_bp_id' => $existingBefore?->id,
            'payload_keys' => array_keys($payloadForSave),
            'payload_scalars' => [
                'business_idea_name' => $payloadForSave['business_idea_name'] ?? null,
                'applicant_name' => $payloadForSave['applicant_name'] ?? null,
                'applicant_jmbg' => $payloadForSave['applicant_jmbg'] ?? null,
                'applicant_address' => $payloadForSave['applicant_address'] ?? null,
                'applicant_phone' => $payloadForSave['applicant_phone'] ?? null,
                'applicant_email' => $payloadForSave['applicant_email'] ?? null,
                'summary_len' => isset($payloadForSave['summary']) && is_string($payloadForSave['summary']) ? strlen($payloadForSave['summary']) : null,
                'required_amount' => $payloadForSave['required_amount'] ?? null,
                'requested_amount' => $payloadForSave['requested_amount'] ?? null,
                'finances_notice_confirmed' => $payloadForSave['finances_notice_confirmed'] ?? null,
            ],
            'payload_table_counts' => collect($tableFields)->mapWithKeys(function ($field) use ($payloadForSave) {
                $value = $payloadForSave[$field] ?? null;
                return [$field => is_array($value) ? count($value) : ($value === null ? 'null' : gettype($value))];
            })->all(),
            'fillable_diff' => array_values(array_diff(array_keys($payloadForSave), (new BusinessPlan())->getFillable())),
        ]);

        // Kreiraj ili ažuriraj biznis plan
        $businessPlan = BusinessPlan::updateOrCreate(
            ['application_id' => $application->id],
            $payloadForSave
        );

        $businessPlan->refresh();

        $this->bpLog('BP_STORE: after updateOrCreate', [
            'application_id' => $application->id,
            'business_plan_id' => $businessPlan->id,
            'was_recently_created' => $businessPlan->wasRecentlyCreated,
            'db_scalars' => [
                'business_idea_name' => $businessPlan->business_idea_name,
                'applicant_name' => $businessPlan->applicant_name,
                'applicant_jmbg' => $businessPlan->applicant_jmbg,
                'applicant_address' => $businessPlan->applicant_address,
                'applicant_phone' => $businessPlan->applicant_phone,
                'applicant_email' => $businessPlan->applicant_email,
                'summary_len' => is_string($businessPlan->summary) ? strlen($businessPlan->summary) : null,
                'required_amount' => $businessPlan->required_amount,
                'requested_amount' => $businessPlan->requested_amount,
                'finances_notice_confirmed' => $businessPlan->finances_notice_confirmed,
            ],
            'db_table_counts' => [
                'products_services_table' => is_array($businessPlan->products_services_table) ? count($businessPlan->products_services_table) : null,
                'target_customers' => is_array($businessPlan->target_customers) ? count($businessPlan->target_customers) : null,
                'sales_locations' => is_array($businessPlan->sales_locations) ? count($businessPlan->sales_locations) : null,
                'pricing_table' => is_array($businessPlan->pricing_table) ? count($businessPlan->pricing_table) : null,
                'revenue_share_table' => is_array($businessPlan->revenue_share_table) ? count($businessPlan->revenue_share_table) : null,
                'employment_structure' => is_array($businessPlan->employment_structure) ? count($businessPlan->employment_structure) : null,
                'business_history' => is_array($businessPlan->business_history) ? count($businessPlan->business_history) : null,
                'suppliers_table' => is_array($businessPlan->suppliers_table) ? count($businessPlan->suppliers_table) : null,
                'funding_sources_table' => is_array($businessPlan->funding_sources_table) ? count($businessPlan->funding_sources_table) : null,
                'revenue_projection' => is_array($businessPlan->revenue_projection) ? count($businessPlan->revenue_projection) : null,
                'expense_projection' => is_array($businessPlan->expense_projection) ? count($businessPlan->expense_projection) : null,
                'job_schedule' => is_array($businessPlan->job_schedule) ? count($businessPlan->job_schedule) : null,
                'risk_matrix' => is_array($businessPlan->risk_matrix) ? count($businessPlan->risk_matrix) : null,
            ],
            'dirty_after_refresh' => $businessPlan->getDirty(),
        ]);

        $resolvedRequired = $businessPlan->resolvedRequiredAmount();
        $resolvedRequested = $businessPlan->resolvedRequestedAmount();

        $businessPlanUpdates = [];
        if ($resolvedRequired !== null && ((float) ($businessPlan->required_amount ?? 0)) <= 0) {
            $businessPlanUpdates['required_amount'] = $resolvedRequired;
        }
        if ($resolvedRequested !== null && ((float) ($businessPlan->requested_amount ?? 0)) <= 0) {
            $businessPlanUpdates['requested_amount'] = $resolvedRequested;
        }
        if (!empty($businessPlanUpdates)) {
            $this->bpLog('BP_STORE: syncing resolved amounts on BP', [
                'application_id' => $application->id,
                'business_plan_id' => $businessPlan->id,
                'updates' => $businessPlanUpdates,
            ]);
            $businessPlan->update($businessPlanUpdates);
            $businessPlan->refresh();
        }

        // Uskladi ključne iznose iz Biznis plana sa prijavom (za sve ostale prikaze u sistemu)
        $applicationUpdate = [];
        if ($resolvedRequired !== null) {
            $applicationUpdate['total_budget_needed'] = $resolvedRequired;
        }
        if ($resolvedRequested !== null) {
            $applicationUpdate['requested_amount'] = $resolvedRequested;
        }
        if (!empty($applicationUpdate)) {
            $this->bpLog('BP_STORE: syncing amounts to application', [
                'application_id' => $application->id,
                'updates' => $applicationUpdate,
            ]);
            $application->update($applicationUpdate);
        }

        $this->bpLog('BP_STORE: done', [
            'application_id' => $application->id,
            'business_plan_id' => $businessPlan->id,
            'is_draft' => $isDraft,
            'is_complete' => $businessPlan->isComplete(),
            'redirect' => $isDraft ? 'dashboard' : 'applications.show',
        ]);

        // Ako je sačuvano kao nacrt, vrati korisnika na Moj Panel
        if ($isDraft) {
            return redirect()->route('dashboard')
                ->with('success', 'Biznis plan je sačuvan kao nacrt. Možete ga nastaviti popunjavati iz sekcije \"Moje prijave\".');
        }

        // U suprotnom, vrati na prikaz prijave
        return redirect()->route('applications.show', $application)
            ->with('success', 'Biznis plan je uspješno sačuvan. Sada možete pregledati prijavu i priložiti dokumente.');
    }
}



