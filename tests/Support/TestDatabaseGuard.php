<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

final class TestDatabaseGuard
{
    public static function ensureSafe(Application $app): void
    {
        $config = $app->make('config');

        if (! $app->environment('testing')
            || $config->get('database.default') !== 'pgsql'
            || $config->get('database.connections.pgsql.driver') !== 'pgsql'
            || $config->get('database.connections.pgsql.database') !== 'bengkel_saas_testing'
            || ! empty($config->get('database.connections.pgsql.url'))) {
            throw new RuntimeException(
                'Tests require APP_ENV=testing, DB_CONNECTION=pgsql, DB_DATABASE=bengkel_saas_testing and an empty DB_URL. Clear cached configuration and verify a dedicated disposable testing database before retrying.'
            );
        }
    }
}
