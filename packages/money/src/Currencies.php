<?php

namespace PHPinnacle\Money;

use Symfony\Component\Intl\Currencies as Intl;

class Currencies
{
    public const string DEFAULT = 'USD';

    public static function exists(string $code): bool
    {
        return Intl::exists(strtoupper(trim($code)));
    }

    public static function list(?string $display = null): array
    {
        return array_reduce(
            Intl::getCurrencyCodes(),
            function ($carry, $code) use ($display) {
                $carry[$code] = Intl::getName($code, $display);

                return $carry;
            },
            [],
        );
    }

    public static function name(string $code, ?string $display = null): string
    {
        return Intl::getName(strtoupper(trim($code)), $display);
    }

    public static function subunit(string $code): int
    {
        return Intl::getFractionDigits(strtoupper(trim($code)));
    }

    public static function symbol(string $code, ?string $display = null): string
    {
        return Intl::getSymbol(strtoupper(trim($code)), $display);
    }
}
