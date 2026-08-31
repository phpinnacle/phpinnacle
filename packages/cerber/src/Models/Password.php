<?php

namespace PHPinnacle\Cerber\Models;

class Password
{
    private static string $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function alpha(): void
    {
        self::alphabet('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    public static function alphabet(string $value): void
    {
        self::$alphabet = $value;
    }

    public static function generate(int $length = 16): string
    {
        return substr(str_shuffle(self::$alphabet), 0, $length);
    }

    public static function numeric(): void
    {
        self::alphabet('0123456789');
    }
}
