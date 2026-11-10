<?php

/**
 * Generate printable PDF pack for official forms/reports.
 * Output: storage/app/print-preview/pack/*.html (+ DomPDF/static PDFs)
 * Then Edge headless prints HTML → PDF (invoked by the shell wrapper).
 */

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BusinessPlanController;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Contract;
use App\Models\Report;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Payments\PaymentConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ViewErrorBag;

foreach ([
    'JMB_ENCRYPTION_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'JMB_ENCRYPTION_KEY_ID' => 'v1',
    'JMB_LOOKUP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'JMB_LOOKUP_KEY_ID' => 'v1',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = 'http://127.0.0.1:8765';
config(['app.url' => $base]);
URL::forceRootUrl($base);

$packDir = storage_path('app/print-preview/pack');
$htmlDir = $packDir.'/html';
$pdfDir = $packDir.'/pdf';
foreach ([$packDir, $htmlDir, $pdfDir] as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

$manifest = [];

function saveHtml(string $htmlDir, string $slug, string $html, array &$manifest, string $title): void
{
    $path = $htmlDir.'/'.$slug.'.html';
    file_put_contents($path, $html);
    $manifest[] = ['slug' => $slug, 'title' => $title, 'html' => $path, 'bytes' => strlen($html)];
    echo "HTML {$slug} bytes=".strlen($html).PHP_EOL;
}

// Cleanup previous preview users/competitions
$stale = Competition::where('title', 'like', 'PDF Pack %')->pluck('id');
if ($stale->isNotEmpty()) {
    $appIds = Application::whereIn('competition_id', $stale)->pluck('id');
    BusinessPlan::whereIn('application_id', $appIds)->delete();
    Report::whereIn('application_id', $appIds)->delete();
    Contract::whereIn('application_id', $appIds)->delete();
    Application::whereIn('id', $appIds)->delete();
    UpNumber::whereIn('competition_id', $stale)->delete();
    Competition::whereIn('id', $stale)->delete();
}
User::where('email', 'like', 'pdf.pack.%@example.test')->delete();

$roleId = (int) Role::where('name', 'korisnik')->value('id');
$user = User::create([
    'name' => 'Marko Printović',
    'email' => 'pdf.pack.'.uniqid().'@example.test',
    'password' => Hash::make('password'),
    'role_id' => $roleId,
    'activation_status' => 'active',
    'email_verified_at' => now(),
    'address' => 'Njegoševa 12',
    'city' => 'Kotor',
    'phone' => '067111222',
]);
Auth::login($user);
view()->share('errors', new ViewErrorBag);

$createdCompetitionIds = [];
$createdApplicationIds = [];

$makeCompetition = function (string $type, string $label) use ($user, &$createdCompetitionIds): Competition {
    $c = Competition::create([
        'title' => 'PDF Pack '.$label.' '.uniqid(),
        'description' => 'Privremeni konkurs za PDF paket obrazaca.',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(25)->toDateString(),
        'type' => $type,
        'status' => 'published',
        'year' => (int) now()->year,
        'deadline_days' => 30,
        'published_at' => now()->subDays(5),
    ]);
    UpNumber::create(['competition_id' => $c->id, 'number' => 'UP-PDF-'.strtoupper(substr($type, 0, 2))]);
    $createdCompetitionIds[] = $c->id;

    return $c;
};

try {
    // —— Omladinsko M1a / M1b / M2 ——
    $om = $makeCompetition('omladinsko', 'Omladinsko');
    $m1a = Application::create([
        'competition_id' => $om->id,
        'user_id' => $user->id,
        'status' => 'draft',
        'business_plan_name' => 'Digitalna radionica za mlade',
        'applicant_type' => 'fizicko_lice',
        'business_stage' => 'započinjanje',
        'business_area' => 'Usluge',
        'is_registered' => false,
        'accuracy_declaration' => true,
        'physical_person_name' => 'Marko Printović',
        'physical_person_jmbg' => '1505990123456',
        'physical_person_phone' => '067111222',
        'physical_person_email' => $user->email,
        'physical_person_address' => 'Njegoševa 12, 85330 Kotor',
        'redni_broj' => 1,
    ]);
    $m1b = Application::create([
        'competition_id' => $om->id,
        'user_id' => $user->id,
        'status' => 'draft',
        'business_plan_name' => 'Kotor Coastal Craft DOO',
        'applicant_type' => 'privredno_drustvo',
        'business_stage' => 'razvoj',
        'business_area' => 'Proizvodnja',
        'is_registered' => true,
        'accuracy_declaration' => true,
        'applicant_jmbg' => '1505990123456',
        'doo_name' => 'Kotor Coastal Craft DOO',
        'doo_phone' => '032325865',
        'doo_email' => $user->email,
        'doo_address' => 'Stari grad 10, 85330 Kotor',
        'redni_broj' => 2,
    ]);
    $m2App = Application::create([
        'competition_id' => $om->id,
        'user_id' => $user->id,
        'status' => 'draft',
        'business_plan_name' => 'Eko turizam Kotor',
        'applicant_type' => 'preduzetnik',
        'business_stage' => 'započinjanje',
        'business_area' => 'Turizam',
        'is_registered' => false,
        'accuracy_declaration' => true,
        'applicant_jmbg' => '1505990123456',
        'preduzetnik_name' => 'Marko Printović',
        'preduzetnik_phone' => '067111222',
        'preduzetnik_email' => $user->email,
        'preduzetnik_address' => 'Njegoševa 12, 85330 Kotor',
        'redni_broj' => 3,
        'approved_amount' => 2000,
    ]);
    $createdApplicationIds = array_merge($createdApplicationIds, [$m1a->id, $m1b->id, $m2App->id]);
    BusinessPlan::create([
        'application_id' => $m2App->id,
        'business_idea_name' => 'Eko turizam Kotor',
        'applicant_name' => 'Marko Printović',
        'applicant_address' => 'Njegoševa 12, 85330 Kotor',
        'applicant_phone' => '067111222',
        'applicant_email' => $user->email,
        'has_registered_business' => false,
        'summary' => 'Organizacija lokalnih eko-tura u zalivu Boke Kotorske.',
        'required_amount' => 3700,
        'requested_amount' => 2000,
        'funding_sources_table' => [['type' => 'Oprema', 'price' => 1500], ['type' => 'Marketing', 'price' => 500]],
        'revenue_projection' => [['product' => 'Ture', 'year1' => 3000, 'year2' => 4000, 'year3' => 5000]],
    ]);

    foreach ([
        ['m1a-omladinsko', $m1a, 'Obrazac M1a (omladinsko)'],
        ['m1b-omladinsko', $m1b, 'Obrazac M1b (omladinsko)'],
    ] as [$slug, $application, $title]) {
        $request = Request::create($base.'/competitions/'.$om->id.'/apply?application_id='.$application->id, 'GET', [
            'application_id' => $application->id,
        ]);
        app()->instance('request', $request);
        $html = app(ApplicationController::class)->create($om, $request)->render();
        saveHtml($htmlDir, $slug, $html, $manifest, $title);
    }

    $request = Request::create($base.'/applications/'.$m2App->id.'/business-plan', 'GET');
    app()->instance('request', $request);
    $html = app(BusinessPlanController::class)->create($m2App)->render();
    saveHtml($htmlDir, 'm2-biznis-plan', $html, $manifest, 'Obrazac 2 / M2 — Forma za biznis plan');

    // —— Žensko 1a / 1b / Obrazac 2 ——
    $zp = $makeCompetition('zensko', 'Zensko');
    $z1a = Application::create([
        'competition_id' => $zp->id,
        'user_id' => $user->id,
        'status' => 'draft',
        'business_plan_name' => 'Atelje Vez Kotor',
        'applicant_type' => 'preduzetnica',
        'business_stage' => 'započinjanje',
        'business_area' => 'Usluge',
        'is_registered' => false,
        'accuracy_declaration' => true,
        'applicant_jmbg' => '1505990123456',
        'preduzetnik_name' => 'Ana Printović',
        'preduzetnik_phone' => '067222333',
        'preduzetnik_email' => $user->email,
        'preduzetnik_address' => 'Njegoševa 12, 85330 Kotor',
        'redni_broj' => 1,
    ]);
    $z1b = Application::create([
        'competition_id' => $zp->id,
        'user_id' => $user->id,
        'status' => 'draft',
        'business_plan_name' => 'Boka Design DOO',
        'applicant_type' => 'doo',
        'business_stage' => 'razvoj',
        'business_area' => 'Proizvodnja',
        'is_registered' => true,
        'accuracy_declaration' => true,
        'applicant_jmbg' => '1505990123456',
        'doo_name' => 'Boka Design DOO',
        'doo_phone' => '032111222',
        'doo_email' => $user->email,
        'doo_address' => 'Stari grad 5, 85330 Kotor',
        'redni_broj' => 2,
    ]);
    $createdApplicationIds = array_merge($createdApplicationIds, [$z1a->id, $z1b->id]);
    foreach ([
        ['obrazac-1a-zensko', $z1a, 'Obrazac 1a (žensko)'],
        ['obrazac-1b-zensko', $z1b, 'Obrazac 1b (žensko)'],
    ] as [$slug, $application, $title]) {
        $request = Request::create($base.'/competitions/'.$zp->id.'/apply?application_id='.$application->id, 'GET', [
            'application_id' => $application->id,
        ]);
        app()->instance('request', $request);
        $html = app(ApplicationController::class)->create($zp, $request)->render();
        saveHtml($htmlDir, $slug, $html, $manifest, $title);
    }

    BusinessPlan::create([
        'application_id' => $z1a->id,
        'business_idea_name' => 'Atelje Vez Kotor',
        'applicant_name' => 'Ana Printović',
        'has_registered_business' => false,
        'summary' => 'Ručni vez i lokalni suveniri.',
        'requested_amount' => 1500,
    ]);
    $request = Request::create($base.'/applications/'.$z1a->id.'/business-plan', 'GET');
    app()->instance('request', $request);
    $html = app(BusinessPlanController::class)->create($z1a)->render();
    saveHtml($htmlDir, 'obrazac-2-zensko', $html, $manifest, 'Obrazac 2 — Biznis plan (žensko)');

    // —— Obrazac 4 / 4a reports (view make; skip controller gates) ——
    $approved = Application::create([
        'competition_id' => $zp->id,
        'user_id' => $user->id,
        'status' => 'approved',
        'business_plan_name' => 'Atelje Vez Kotor',
        'applicant_type' => 'preduzetnica',
        'business_stage' => 'započinjanje',
        'business_area' => 'Usluge',
        'is_registered' => true,
        'accuracy_declaration' => true,
        'applicant_jmbg' => '1505990123456',
        'preduzetnik_name' => 'Ana Printović',
        'preduzetnik_phone' => '067222333',
        'preduzetnik_email' => $user->email,
        'preduzetnik_address' => 'Njegoševa 12, 85330 Kotor',
        'redni_broj' => 10,
        'approved_amount' => 1500,
    ]);
    $createdApplicationIds[] = $approved->id;
    $report = new Report([
        'entrepreneur_name' => 'Ana Printović',
        'legal_status' => 'Preduzetnica',
        'business_plan_name' => 'Atelje Vez Kotor',
        'approved_amount' => 1500,
        'contract_number' => 'UG-2026-01',
        'report_period_start' => now()->subMonths(3)->toDateString(),
        'report_period_end' => now()->toDateString(),
    ]);
    $html = view('reports.create', ['application' => $approved, 'report' => $report])->render();
    saveHtml($htmlDir, 'obrazac-4-realizacija', $html, $manifest, 'Obrazac 4 — Izvještaj o realizaciji');

    $finReport = new Report([
        'entrepreneur_name' => 'Ana Printović',
        'legal_status' => 'Preduzetnica',
        'business_plan_name' => 'Atelje Vez Kotor',
        'approved_amount' => 1500,
        'total_amount' => 1480,
        'report_date' => now()->toDateString(),
    ]);
    $html = view('reports.create-financial', ['application' => $approved, 'report' => $finReport])->render();
    saveHtml($htmlDir, 'obrazac-4a-finansijski', $html, $manifest, 'Obrazac 4a — Finansijski izvještaj');

    // —— Rang lista (žensko) ——
    $rankingCompetition = $makeCompetition('zensko', 'RangLista');
    $rankingCompetition->update([
        'status' => 'closed',
        'budget' => 20000,
        'title' => 'PDF Pack Rang lista žensko preduzetništvo '.uniqid(),
        'end_date' => now()->subDays(10)->toDateString(),
    ]);
    $rankingCompetition->load('upNumber');

    $rankRows = [
        ['name' => 'Ana Printović', 'plan' => 'Atelje Vez Kotor', 'type' => 'preduzetnica', 'stage' => 'započinjanje', 'score' => 48.5, 'pos' => 1, 'req' => 1500, 'apr' => 1500, 'status' => 'approved'],
        ['name' => 'Jelena Boka', 'plan' => 'Boka Design Studio', 'type' => 'doo', 'stage' => 'razvoj', 'score' => 42.0, 'pos' => 2, 'req' => 2500, 'apr' => 2000, 'status' => 'approved'],
        ['name' => 'Marija Kotor', 'plan' => 'Suvenirnica Perast', 'type' => 'preduzetnica', 'stage' => 'započinjanje', 'score' => 35.5, 'pos' => 3, 'req' => 1800, 'apr' => null, 'status' => 'evaluated'],
    ];
    $belowRows = [
        ['name' => 'Ivana Skala', 'plan' => 'Craft & Coffee', 'type' => 'fizicko_lice', 'stage' => 'započinjanje', 'score' => 22.0, 'pos' => null, 'req' => 1200, 'apr' => null, 'status' => 'evaluated'],
    ];

    $rankedApps = collect();
    $belowApps = collect();
    foreach (array_merge(
        array_map(fn ($r) => [$r, false], $rankRows),
        array_map(fn ($r) => [$r, true], $belowRows),
    ) as [$row, $isBelow]) {
        $applicant = User::create([
            'name' => $row['name'],
            'email' => 'pdf.pack.rank.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $app = Application::create([
            'competition_id' => $rankingCompetition->id,
            'user_id' => $applicant->id,
            'status' => $row['status'],
            'business_plan_name' => $row['plan'],
            'applicant_type' => $row['type'],
            'business_stage' => $row['stage'],
            'business_area' => 'Usluge',
            'is_registered' => $row['type'] !== 'fizicko_lice',
            'accuracy_declaration' => true,
            'applicant_jmbg' => '1505990123456',
            'preduzetnik_name' => $row['name'],
            'redni_broj' => ($row['pos'] ?? 90),
            'final_score' => $row['score'],
            'ranking_position' => $row['pos'],
            'requested_amount' => $row['req'],
            'approved_amount' => $row['apr'],
        ]);
        $app->setRelation('user', $applicant);
        $createdApplicationIds[] = $app->id;
        if ($isBelow) {
            $belowApps->push($app);
        } else {
            $rankedApps->push($app);
        }
    }

    $totalBudget = (float) ($rankingCompetition->budget ?? 0);
    $usedBudget = (float) $rankedApps->sum('approved_amount');
    $html = view('admin.competitions.ranking', [
        'competition' => $rankingCompetition,
        'applications' => $rankedApps,
        'belowLineApplications' => $belowApps,
        'totalBudget' => $totalBudget,
        'usedBudget' => $usedBudget,
        'remainingBudget' => $totalBudget - $usedBudget,
        'isSuperAdmin' => true,
        'isCompetitionAdmin' => false,
        'isChairman' => true,
        'isCommissionMember' => true,
        'commissionMembers' => collect([
            (object) ['position' => 'predsjednik', 'name' => 'Petar Predsjednik'],
            (object) ['position' => 'clan', 'name' => 'Milena Član'],
            (object) ['position' => 'clan', 'name' => 'Ivan Član'],
        ]),
        'decisionEnteredBy' => 'Petar Predsjednik',
    ])->render();
    saveHtml($htmlDir, 'rang-lista-zensko', $html, $manifest, 'Rang lista — RANG LISTA BIZNIS PLANOVA (žensko)');

    // —— Canonical P1 / P2 if possible ——
    try {
        $canonicalType = \App\Services\Competitions\Profile\CompetitionProfileResolver::TYPE_CANONICAL;
        $canonical = $makeCompetition($canonicalType, 'MMSP');
        $canApp = Application::create([
            'competition_id' => $canonical->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'business_plan_name' => 'MMSP Ulaganje Demo',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'razvoj',
            'business_area' => 'Usluge',
            'is_registered' => true,
            'accuracy_declaration' => true,
            'applicant_jmbg' => '1505990123456',
            'preduzetnik_name' => 'Marko Printović',
            'preduzetnik_phone' => '067111222',
            'preduzetnik_email' => $user->email,
            'preduzetnik_address' => 'Njegoševa 12, 85330 Kotor',
            'redni_broj' => 1,
        ]);
        $createdApplicationIds[] = $canApp->id;
        $request = Request::create($base.'/applications/'.$canApp->id.'/obrazac-p1', 'GET');
        app()->instance('request', $request);
        $resp = app(\App\Http\Controllers\CanonicalApplicantController::class)->editP1($canApp);
        if (method_exists($resp, 'render')) {
            saveHtml($htmlDir, 'obrazac-p1-mmsp', $resp->render(), $manifest, 'Obrazac P1 — MMSP prijava');
        } else {
            echo 'SKIP obrazac-p1: '.get_class($resp).PHP_EOL;
        }
        $request = Request::create($base.'/applications/'.$canApp->id.'/plan-ulaganja', 'GET');
        app()->instance('request', $request);
        $resp = app(\App\Http\Controllers\CanonicalInvestmentPlanController::class)->edit($canApp);
        if (method_exists($resp, 'render')) {
            saveHtml($htmlDir, 'obrazac-p2-mmsp', $resp->render(), $manifest, 'Obrazac P2 — Plan ulaganja');
        } else {
            echo 'SKIP obrazac-p2: '.get_class($resp).PHP_EOL;
        }
    } catch (Throwable $e) {
        echo 'SKIP canonical: '.$e->getMessage().PHP_EOL;
    }

    // —— Payment confirmation (HTML printable; DomPDF package not installed in this env) ——
    $confirmation = new PaymentConfirmation(
        title: 'Potvrda o uspješnoj uplati',
        statusLabel: 'Uspješno',
        succeededAtLabel: now()->format('d.m.Y H:i'),
        payerLabel: 'Marko Printović',
        userTypeLabel: 'Fizičko lice',
        paymentTypeName: 'Komunalna taksa (demo)',
        accountNumber: '510-000000-00',
        accountName: 'Opština Kotor',
        amount: '25.00',
        currency: 'EUR',
        merchantTransactionId: 'DK-DEMO-'.uniqid(),
        gatewayReference: 'GW-DEMO-001',
        disclaimer: PaymentConfirmation::disclaimer(),
        issuer: 'Opština Kotor — Digital Kotor e-Plaćanje',
        pdfFilename: 'potvrda-uplate-demo.pdf',
    );
    $html = view('payments.confirmation-pdf', ['confirmation' => $confirmation])->render();
    saveHtml($htmlDir, 'potvrda-uplate', $html, $manifest, 'Potvrda o uspješnoj uplati');

} finally {
    // cleanup seeded rows
    $appIds = collect($createdApplicationIds);
    if ($appIds->isNotEmpty()) {
        BusinessPlan::whereIn('application_id', $appIds)->delete();
        Report::whereIn('application_id', $appIds)->delete();
        Contract::whereIn('application_id', $appIds)->delete();
        Application::whereIn('id', $appIds)->delete();
    }
    if ($createdCompetitionIds) {
        UpNumber::whereIn('competition_id', $createdCompetitionIds)->delete();
        Competition::whereIn('id', $createdCompetitionIds)->delete();
    }
    User::where('email', 'like', 'pdf.pack.%@example.test')->delete();
    echo "seed cleanup done\n";
}

// Copy static PDFs
$static = [
    'uputstvo-zensko-preduzetnistvo.pdf' => public_path('pdf/uputstvo-zensko-preduzetnistvo.pdf'),
    'moderator-uputstvo.pdf' => public_path('pdf/Moderator uputstvo.pdf'),
];
foreach ($static as $name => $src) {
    if (is_file($src)) {
        copy($src, $pdfDir.'/'.$name);
        $manifest[] = ['slug' => pathinfo($name, PATHINFO_FILENAME), 'title' => $name, 'pdf' => $pdfDir.'/'.$name, 'bytes' => filesize($src)];
        echo "COPIED {$name}\n";
    }
}

file_put_contents($packDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo 'manifest entries='.count($manifest).PHP_EOL;
