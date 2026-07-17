<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

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

        if (! preg_match('/test(?:ing)?/i', $database)) {
            throw new RuntimeException(
                'Naziv testne MySQL baze mora sadržati "test" ili "testing".'
            );
        }

        return $app;
    }
}
