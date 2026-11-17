<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\PmIsolatedDatabase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bootstrap the test application and stop before any database operation
     * if the configured database is not an isolated MySQL test database.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (! $app->environment('testing')) {
            throw new RuntimeException('Testovi se mogu pokrenuti samo sa APP_ENV=testing.');
        }

        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'mysql') {
            throw new RuntimeException('Testovi moraju koristiti posebnu MySQL test bazu.');
        }

        $pmIsolated = defined('PM_ISOLATED_DATABASE')
            && in_array(PM_ISOLATED_DATABASE, ['opstinakotor_digital_pm_regression', 'opstinakotor_digital_pm_concurrency'], true);
        if ($pmIsolated) {
            $prefix = "database.connections.{$connection}.";
            if ($database !== PM_ISOLATED_DATABASE
                || $app['config']->get($prefix.'host') !== '127.0.0.1'
                || (string) $app['config']->get($prefix.'port') !== '3306'
                || $app['config']->get($prefix.'username') !== PM_ISOLATED_USERNAME
                || $app['config']->get($prefix.'url') || $app['config']->get($prefix.'unix_socket')) {
                throw new RuntimeException('Isolated PM database configuration mismatch.');
            }
            PmIsolatedDatabase::verifyPdo($app['db']->connection()->getPdo(), PM_ISOLATED_DATABASE, PM_ISOLATED_USERNAME);
        }
        if (! $pmIsolated && ! preg_match('/test(?:ing)?/i', $database)) {
            throw new RuntimeException(
                'Naziv testne MySQL baze mora sadržati "test" ili "testing".'
            );
        }

        return $app;
    }

    /** Exact database expected by the verified isolated harness, or the existing test setup. */
    protected function expectedTestDatabase(): string
    {
        return defined('PM_ISOLATED_DATABASE') ? PM_ISOLATED_DATABASE : 'opstinakotor_digital_testing';
    }

    protected function enableEpIdentityFlows(): void
    {
        config(['identity.ep_identity_flows' => true]);
    }
}
