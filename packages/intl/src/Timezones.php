<?php

namespace PHPinnacle\Intl;

use Symfony\Component\Intl\Timezones as Intl;

class Timezones
{
    public const string DEFAULT = 'UTC';

    public static function exists(string $code): bool
    {
        return Intl::exists($code);
    }

    public static function list(?string $display = null): array
    {
        return Intl::getNames($display);
    }

    public static function name(string $timezone, ?string $display = null): string
    {
        return Intl::getName($timezone, $display);
    }
}
