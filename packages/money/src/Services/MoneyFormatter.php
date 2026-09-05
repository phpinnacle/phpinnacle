<?php

namespace PHPinnacle\Money\Services;

use Locale;
use PHPinnacle\Money\Contracts\Formatter;
use PHPinnacle\Money\Formatters\EnglishFormatter;
use PHPinnacle\Money\Money;

class MoneyFormatter
{
    /**
     * @var array<string, Formatter>
     */
    private static array $formatters = [];

    public static function learn(string $lang, Formatter $formatter): void
    {
        self::$formatters[$lang] = $formatter;
    }

    public static function format(Money $money, ?string $format = null, ?string $locale = null): string
    {
        $locale ??= Locale::getDefault();
        $language = Locale::getPrimaryLanguage($locale);
        $dictionary = self::$formatters[$language] ?? new EnglishFormatter;
        $replacements = $dictionary->inflect($money);

        return str_replace(array_keys($replacements), $replacements, $format ?? '%D %C');
    }
}
