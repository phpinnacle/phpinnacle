<?php

namespace PHPinnacle\Intl;

use Illuminate\Support\Facades\Lang;
use Locale;
use Symfony\Component\Intl\Locales as Intl;

class Locales
{
    public const string OTHER = 'zz';

    private const string TRANS = 'phpinnacle-intl::forms.locale.other';

    public static function default(): string
    {
        return Locale::getDefault();
    }

    /**
     * @return array<string, string>
     */
    public static function list(?string $display = null): array
    {
        $all = Intl::getNames($display);
        $all[self::OTHER] = Lang::string(self::TRANS, locale: $display);

        return $all;
    }

    public static function name(string $code, ?string $display = null): string
    {
        $code = trim($code);

        return $code !== self::OTHER ? Intl::getName($code, $display) : __(self::TRANS, locale: $display);
    }

    public static function exists(string $code): bool
    {
        $code = trim($code);

        return $code === self::OTHER || Intl::exists($code);
    }
}
