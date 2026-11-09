<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

class TestDatabaseGuardTest extends TestCase
{
    private function application(array $overrides = [], string $environment = 'testing'): Application
    {
        $app = new Application;
        $app->instance('env', $environment);
        $config = new Repository;
        $config->set(array_replace([
            'database.default' => 'pgsql',
            'database.connections.pgsql.driver' => 'pgsql',
            'database.connections.pgsql.database' => 'bengkel_saas_testing',
            'database.connections.pgsql.url' => '',
        ], $overrides));
        $app->instance('config', $config);

        return $app;
    }

    public function test_the_dedicated_postgresql_testing_configuration_is_accepted(): void
    {
        TestDatabaseGuard::ensureSafe($this->application());

        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeConfigurations')]
    public function test_unsafe_or_overridden_configuration_is_rejected(array $overrides, string $environment = 'testing'): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tests require APP_ENV=testing');

        TestDatabaseGuard::ensureSafe($this->application($overrides, $environment));
    }

    public static function unsafeConfigurations(): array
    {
        return [
            'application database inherited from shell' => [['database.connections.pgsql.database' => 'bengkel_saas']],
            'database URL overrides the name' => [['database.connections.pgsql.url' => 'pgsql://localhost/bengkel_saas']],
            'non PostgreSQL connection' => [['database.default' => 'sqlite']],
            'driver overridden in cached configuration' => [['database.connections.pgsql.driver' => 'mysql']],
            'production environment' => [[], 'production'],
            'local environment' => [[], 'local'],
            'empty database name' => [['database.connections.pgsql.database' => '']],
        ];
    }
}
