<?php

namespace Tests\Support;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/** Shared, credential-free isolation policy for PHPUnit and the standalone PM writer test. */
final class PmIsolatedDatabase
{
    private const TARGETS = [
        'regression' => ['opstinakotor_digital_pm_regression', 'pm_regression_test'],
        'concurrency' => ['opstinakotor_digital_pm_concurrency', 'pm_concurrency_test'],
    ];

    /** Credentials arrive through the invoking process; this helper never loads DPAPI files. */
    public static function bootstrap(): void
    {
        $mode = getenv('PM_ISOLATED_MODE');
        if ($mode === 'concurrency') {
            set_exception_handler(static function (Throwable $error): void {
                fwrite(STDERR, 'Isolated test execution failed: '.get_class($error)."; details suppressed.\n");
                exit(1);
            });
        }
        if (! isset(self::TARGETS[$mode]) || file_exists(getcwd().'/bootstrap/cache/config.php')) {
            throw new RuntimeException('Unsafe isolation mode or cached configuration.');
        }
        [$database, $username] = self::TARGETS[$mode];
        $password = getenv('PM_ISOLATED_PASSWORD');
        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Dedicated isolated credential is required; no fallback is allowed.');
        }

        // Optional local application settings, never a source of database credentials.
        $settings = is_file(getcwd().'/.env.testing')
            ? \Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env.testing')) : [];
        foreach ($settings as $key => $value) {
            if (str_starts_with($key, 'DB_') || str_starts_with($key, 'IDENTITY_CENSUS_')) {
                unset($settings[$key]);
            }
        }
        $settings = array_merge($settings, [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306', 'DB_DATABASE' => $database, 'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password, 'DB_URL' => '', 'DB_SOCKET' => '',
        ]);
        foreach ($settings as $key => $value) {
            self::setEnvironment($key, $value);
        }
        $xml = simplexml_load_file(getcwd().'/phpunit.xml');
        foreach ($xml->php->env as $setting) {
            $key = (string) $setting['name'];
            if (str_starts_with($key, 'DB_')) {
                throw new RuntimeException('phpunit.xml may not override isolated database settings.');
            }
            self::setEnvironment($key, (string) $setting['value']);
        }
        if (getenv('APP_ENV') !== 'testing') {
            throw new RuntimeException('APP_ENV must be testing.');
        }

        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname='.$database, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            self::verifyPdo($pdo, $database, $username);
            $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
            if (count($tables) !== 104) {
                throw new RuntimeException('Unexpected isolated schema.');
            }
            $rows = 0;
            foreach ($tables as $table) {
                if (! preg_match('/^[a-zA-Z0-9_]+$/D', $table)) {
                    throw new RuntimeException('Unexpected table name.');
                }
                $rows += (int) $pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
            }
            $writer = ($_SERVER['argv'][1] ?? '') === '--writer';
            if (getenv('PM_REQUIRE_EMPTY') === '1' && ! $writer && $rows !== 0) {
                throw new RuntimeException('Isolated schema must be empty.');
            }
        } catch (Throwable $error) {
            $reason = $error instanceof PDOException ? 'PDO code '.$error->getCode() : $error->getMessage();
            fwrite(STDERR, 'Isolated database preflight failed: '.$reason."; no fallback attempted.\n");
            exit(1);
        }
        if (! $writer) {
            fwrite(STDOUT, 'Verified '.$database.'; '.$username.'@127.0.0.1; localhost:3306; 104 tables; '
                .$rows." rows; DML only, no roles/DDL/cross-database grants.\n");
        }
        define('PM_ISOLATED_DATABASE', $database);
        define('PM_ISOLATED_USERNAME', $username);
        RefreshDatabaseState::$migrated = true;
        \Illuminate\Console\Application::starting(static function ($artisan): void {
            $artisan->getLaravel()['events']->listen(CommandStarting::class, static function ($event): void {
                $command = (string) $event->command;
                if (str_starts_with($command, 'migrate') || str_starts_with($command, 'db:')) {
                    throw new RuntimeException('Schema/database commands are forbidden.');
                }
            });
        });
    }

    public static function verifyPdo(PDO $pdo, string $database, string $username): void
    {
        if (! in_array([$database, $username], self::TARGETS, true)) {
            throw new RuntimeException('Unexpected database/account pair.');
        }
        $identity = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@port AS port, '
            .'@@hostname AS hostname, CURRENT_ROLE() AS active_roles')->fetch(PDO::FETCH_ASSOC);
        $localHostname = getenv('COMPUTERNAME') ?: gethostname();
        if ($identity['db'] !== $database || $identity['account'] !== $username.'@127.0.0.1'
            || (int) $identity['port'] !== 3306 || strcasecmp($identity['hostname'], (string) $localHostname) !== 0
            || $identity['active_roles'] !== 'NONE') {
            throw new RuntimeException('Effective database identity mismatch.');
        }
        $privileges = [];
        foreach ($pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
            $prefix = explode(' TO ', $grant, 2)[0];
            if ($prefix === 'GRANT USAGE ON *.*') {
                continue;
            }
            if (! preg_match('/^GRANT ([A-Z, ]+) ON `'.preg_quote($database, '/').'`\.\*$/D', $prefix, $match)
                || str_contains($grant, 'WITH GRANT OPTION')) {
                throw new RuntimeException('Unexpected grant scope.');
            }
            foreach (explode(', ', $match[1]) as $privilege) {
                $privileges[$privilege] = true;
            }
        }
        $actual = array_keys($privileges);
        sort($actual);
        if ($actual !== ['DELETE', 'INSERT', 'SELECT', 'UPDATE']) {
            throw new RuntimeException('Unexpected isolated database privileges.');
        }
    }

    private static function setEnvironment(string $key, string $value): void
    {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
