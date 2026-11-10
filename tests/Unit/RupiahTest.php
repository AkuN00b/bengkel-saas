<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RupiahTest extends TestCase
{
    public function test_money_formatting_preserves_integer_precision(): void
    {
        $this->assertSame('9.223.372.036.854.775.807', Rupiah::format(PHP_INT_MAX));
        $this->assertSame('0', Rupiah::format(0));
        $this->assertSame(150000, Rupiah::subtotal(3, 50000));
    }

    public function test_subtotal_overflow_is_rejected_before_multiplication(): void
    {
        $this->expectException(ValidationException::class);
        Rupiah::subtotal(2, PHP_INT_MAX);
    }
}
