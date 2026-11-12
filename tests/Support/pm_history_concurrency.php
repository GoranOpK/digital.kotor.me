<?php

/**
 * Standalone two-process PM lock regression. Run ONLY against the existing isolated MySQL test schema.
 * Creates one new synthetic user/competition/application/plan and preserves them; deletes no data.
 * No migrations. The regular PHPUnit suites remain transactional.
 */
require_once dirname(__DIR__, 2).'/vendor/autoload.php';
\Tests\Support\PmIsolatedDatabase::bootstrap();
if (PM_ISOLATED_DATABASE !== 'opstinakotor_digital_pm_concurrency') throw new RuntimeException('Concurrency requires its dedicated database.');
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=$app['db']->connection();
if (!$app->environment('testing') || $db->getDatabaseName()!==PM_ISOLATED_DATABASE
    || config('database.connections.mysql.host')!=='127.0.0.1'
    || config('database.connections.mysql.username')!==PM_ISOLATED_USERNAME) throw new RuntimeException('Unexpected effective database.');
\Tests\Support\PmIsolatedDatabase::verifyPdo($db->getPdo(),PM_ISOLATED_DATABASE,PM_ISOLATED_USERNAME);

if (($argv[1] ?? '') === '--writer') {
    $stale = ($argv[3] ?? '') === '--doo'
        ? App\Models\BusinessPlan::where('application_id', (int) $argv[2])->firstOrFail()
        : App\Models\Application::findOrFail((int) $argv[2]);
    if (($argv[3] ?? '') === '--doo') $stale->company_name = 'Concurrent late company';
    else $stale->physical_person_name = 'Concurrent late writer';
    fwrite(STDOUT, "READY\n"); fflush(STDOUT);
    if (trim((string) fgets(STDIN)) !== 'GO') exit(2);
    fwrite(STDOUT, "ATTEMPT\n"); fflush(STDOUT);
    try {
        $stale->save();
        fwrite(STDOUT, "SAVED\n"); exit(3);
    } catch (Illuminate\Validation\ValidationException $exception) {
        if (! array_key_exists('identity', $exception->errors())) throw $exception;
        fwrite(STDOUT, "REJECTED\n"); exit(0);
    }
}

if (! App\Models\Role::where('name', 'korisnik')->exists()) {
    if (App\Models\Role::whereKey(3)->exists()) throw new RuntimeException('Cannot provision the missing test role without changing an existing row.');
    App\Models\Role::unguarded(fn () => App\Models\Role::create(['id' => 3, 'name' => 'korisnik', 'display_name' => 'Korisnik']));
}
$isDoo = ($argv[1] ?? '') === '--doo';
$fixture = new class {
    use Tests\Support\MakesPmHistoryFixtures, Tests\Support\MakesIdentitySnapshots;
    public function companySnapshot($user) { return $this->plSnapshot($user, ['idDocumentType' => 'passport', 'jmb' => null, 'passportNumber' => 'TEST'.bin2hex(random_bytes(6)), 'passportIssuingCountryCode' => 'ME'], ['city' => 'Kotor', 'pib' => (string) random_int(10000000, 99999999)]); }
};
[$user, $application, $plan] = $fixture->makePmHistoryFixture($isDoo ? 'privredno_drustvo' : 'fizicko_lice', $isDoo);
if ($isDoo) {
    config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
    (new App\Identity\CanonicalIdentityWriter)->createForUser($user, $fixture->companySnapshot($user));
}
$originalCompanyName = $plan->company_name;
$originalName = $application->physical_person_name;
$process = proc_open([PHP_BINARY, __FILE__, '--writer', (string) $application->id, $isDoo ? '--doo' : '--person'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, getcwd(), null, ['bypass_shell' => true]);
if (! is_resource($process)) throw new RuntimeException('Could not start concurrent writer.');
stream_set_timeout($pipes[1], 15);
try {
    if (trim((string) fgets($pipes[1])) !== 'READY') throw new RuntimeException('Writer did not load the draft.');
    $db->beginTransaction();
    Illuminate\Support\Facades\Auth::login($user);
    $request = Illuminate\Http\Request::create('/applications/'.$application->id.'/submit', 'POST', [
        'confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1',
    ]);
    $request->setUserResolver(fn () => $user);
    $app->make(App\Http\Controllers\ApplicationController::class)->submit($request, $application);
    if ($application->fresh()->status !== 'submitted') throw new RuntimeException('FINAL did not succeed.');
    fwrite($pipes[0], "GO\n"); fflush($pipes[0]);
    if (trim((string) fgets($pipes[1])) !== 'ATTEMPT') throw new RuntimeException('Writer did not attempt saving.');
    usleep(250000);
    if (! proc_get_status($process)['running']) throw new RuntimeException('Writer passed the FINAL lock prematurely.');
    $db->commit();
    if (trim((string) fgets($pipes[1])) !== 'REJECTED') throw new RuntimeException('Stale writer was not rejected after FINAL committed.');
    if ($application->fresh()->physical_person_name !== $originalName || $application->fresh()->status !== 'submitted'
        || $plan->fresh()->company_name !== $originalCompanyName) {
        throw new RuntimeException('Concurrent request changed the final identity.');
    }
    fwrite(STDOUT, "PASS: two processes; stale writer waits for FINAL, then is rejected.\n");
    fwrite(STDOUT, "Preserved newly created synthetic fixture rows; no existing data deleted; no migrations.\n");
} finally {
    if ($db->transactionLevel() > 0) $db->rollBack();
    fclose($pipes[0]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0 || $stderr !== '') throw new RuntimeException('Concurrent writer failed: '.$stderr);
}
