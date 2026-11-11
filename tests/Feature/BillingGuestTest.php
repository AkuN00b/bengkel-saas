<?php

namespace Tests\Feature;

use Tests\TestCase;

class BillingGuestTest extends TestCase
{
    public function test_guests_cannot_read_print_or_mutate_invoices(): void
    {
        foreach (['/invoices', '/invoices/create', '/invoices/1', '/invoices/1/print'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['/invoices', '/invoices/1/issue', '/invoices/1/refresh', '/invoices/1/void', '/invoices/1/pay', '/invoices/1/payments/1/reverse'] as $url) {
            $this->post($url)->assertRedirect(route('login'));
        }
        $this->put('/invoices/1')->assertRedirect(route('login'));
        $this->put('/invoices/1/items/1/price')->assertRedirect(route('login'));
    }
}
