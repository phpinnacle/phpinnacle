<?php

namespace PHPinnacle\Intl;

use Symfony\Component\Intl\Languages as Intl;

class Languages
{
    public const string OTHER = 'zzz';

    private const string TRANS = 'phpinnacle-intl::forms.language.other';

    /**
     * @return array<string, string>
     */
    public static function list(?string $display = null): array
    {
        $all = Intl::getAlpha3Names($display);
        $all[self::OTHER] = __(self::TRANS, locale: $display);

        return $all;
    }

    public static function name(string $code, ?string $display = null): string
    {
        $code = strtolower(trim($code));

        return $code !== self::OTHER ? Intl::getAlpha3Name($code, $display) : __(self::TRANS, locale: $display);
    }

    public static function exists(string $code): bool
    {
        $code = strtolower(trim($code));

        return $code === self::OTHER || Intl::alpha3CodeExists($code);
    }
}
