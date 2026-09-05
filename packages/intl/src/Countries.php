<?php

namespace PHPinnacle\Intl;

use Symfony\Component\Intl\Countries as Intl;

class Countries
{
    public const string OTHER = 'ZZ';

    private const string TRANS = 'phpinnacle-intl::forms.country.other';

    /**
     * @return array<string, string>
     */
    public static function list(?string $display = null): array
    {
        $all = Intl::getNames($display);
        $all[self::OTHER] = __(self::TRANS, locale: $display);

        return $all;
    }

    public static function name(string $code, ?string $display = null): string
    {
        $code = strtoupper(trim($code));

        return $code !== self::OTHER ? Intl::getName($code, $display) : __(self::TRANS, locale: $display);
    }

    public static function exists(string $code): bool
    {
        $code = strtoupper(trim($code));

        return $code === self::OTHER || Intl::exists($code);
    }
}
