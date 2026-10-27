<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    #[DataProvider('authenticationEndpoints')]
    public function test_authentication_posts_require_csrf_when_not_same_origin(string $uri): void
    {
        // Laravel skips CSRF during testing; enable the real middleware path here.
        $this->app->detectEnvironment(fn () => 'local');
        $this->post($uri)->assertStatus(419);
    }

    public static function authenticationEndpoints(): array
    {
        return [['/register'], ['/login'], ['/logout'], ['/forgot-password'], ['/reset-password']];
    }
}
