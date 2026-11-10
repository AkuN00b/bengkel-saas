<?php

namespace Tests\Unit;

use App\Support\PlateNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlateNumberTest extends TestCase
{
    #[DataProvider('plates')]
    public function test_plate_normalization_is_deterministic_and_idempotent(string $input, string $display, string $normalized): void
    {
        $this->assertSame($display, PlateNumber::display($input));
        $this->assertSame($normalized, PlateNumber::normalize($input));
        $this->assertSame($normalized, PlateNumber::normalize($normalized));
        $this->assertSame($display, PlateNumber::display($display));
    }

    public static function plates(): array
    {
        return [
            ['B 1234 ABC', 'B 1234 ABC', 'B1234ABC'],
            [' b   1234 abc ', 'B 1234 ABC', 'B1234ABC'],
            ['b1234abc', 'B1234ABC', 'B1234ABC'],
            ['ab 001 cd', 'AB 001 CD', 'AB001CD'],
            ['RI 1', 'RI 1', 'RI1'],
        ];
    }

    public function test_normalization_does_not_silently_remove_invalid_characters(): void
    {
        $this->assertSame('B-1234', PlateNumber::normalize('b-1234'));
        $this->assertSame("B\n1234", PlateNumber::normalize("b\n1234"));
    }
}
