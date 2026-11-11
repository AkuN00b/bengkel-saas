<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Rupiah
{
    public static function add(int $left, int $right): int
    {
        if ($left < 0 || $right < 0 || $left > PHP_INT_MAX - $right) {
            throw ValidationException::withMessages(['invoice' => 'Total melebihi batas nilai yang dapat disimpan.']);
        }

        return $left + $right;
    }

    public static function format(int|string $amount): string
    {
        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount);
    }

    public static function subtotal(int $quantity, int $price): int
    {
        if ($quantity < 1 || $price < 0 || $price > intdiv(PHP_INT_MAX, $quantity)) {
            throw ValidationException::withMessages(['unit_price' => 'Jumlah × harga melebihi batas nilai yang dapat disimpan.']);
        }

        return $quantity * $price;
    }
}
