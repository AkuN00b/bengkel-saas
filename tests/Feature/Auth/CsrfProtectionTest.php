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

    public function test_catalog_inventory_and_work_order_line_mutations_require_csrf(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        foreach (['/services', '/spare-parts', '/spare-parts/1/movements', '/spare-parts/1/usages/1/returns',
            '/work-orders/1/services', '/work-orders/1/services/1/cancel', '/work-orders/1/parts', '/work-orders/1/parts/1/cancel', '/work-orders/1/parts/1/use', '/work-orders/1/parts/1/returns'] as $url) {
            $this->post($url)->assertStatus(419);
        }
        $this->put('/services/1')->assertStatus(419);
        $this->put('/spare-parts/1')->assertStatus(419);
        $this->patch('/services/1/activation')->assertStatus(419);
        $this->patch('/spare-parts/1/activation')->assertStatus(419);
        $this->put('/work-orders/1/services/1')->assertStatus(419);
        $this->put('/work-orders/1/parts/1')->assertStatus(419);
    }

    public function test_all_billing_mutations_require_csrf(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        foreach (['/invoices', '/invoices/1/refresh', '/invoices/1/issue', '/invoices/1/void', '/invoices/1/pay', '/invoices/1/payments/1/reverse'] as $url) {
            $this->post($url)->assertStatus(419);
        }
        $this->put('/invoices/1')->assertStatus(419);
        $this->put('/invoices/1/items/1/price')->assertStatus(419);
    }
}
