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
        return [['/register'], ['/login'], ['/logout'], ['/forgot-password'], ['/reset-password'], ['/workshops/active'], ['/customers'], ['/vehicles'], ['/mechanics'], ['/mechanics/1/resend'], ['/work-orders'], ['/work-orders/1/transition']];
    }

    public function test_work_order_edits_assignments_and_mechanic_activation_require_csrf(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->put('/work-orders/1', [])->assertStatus(419);
        $this->put('/work-orders/1/mechanic', [])->assertStatus(419);
        $this->patch('/mechanics/1/activation', [])->assertStatus(419);
    }
}
