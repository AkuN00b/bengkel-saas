<?php

namespace App\Support;

final class PlateNumber
{
    public const PATTERN = '/\A(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9 ]+\z/';

    public static function display(string $plate): string
    {
        return strtoupper(preg_replace('/ +/', ' ', trim($plate)));
    }

    public static function normalize(string $plate): string
    {
        return str_replace(' ', '', self::display($plate));
    }
}
