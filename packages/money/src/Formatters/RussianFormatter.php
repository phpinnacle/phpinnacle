<?php

namespace PHPinnacle\Money\Formatters;

use NumberToWords\Language\English\EnglishDictionary;
use PHPinnacle\Money\Contracts\Formatter;
use PHPinnacle\Money\Money;

class RussianFormatter implements Formatter
{
    public function inflect(Money $money): array
    {
        [$paper, $coins] = $money->explode();

        $words['%D'] = $money->decimal();
        $words['%d'] = $coins->decimal();
        $words['%C'] = $this->getCurrencyName($paper->currency, 0, $paper->amount);
        $words['%c'] = $this->getCurrencyName($coins->currency, 1, $coins->amount);
        $words['%N'] = number_format($paper->amount, 0, ',', ' ');
        $words['%n'] = number_format($coins->amount, 0, ',', ' ');

        return $words;
    }

    private function getCurrencyName(string $currency, int $type, int $amount): string
    {
        // Проверяем наличие в словаре
        if (isset(RussianDictionary::$currencyNames[$currency][$type])) {
            $forms = RussianDictionary::$currencyNames[$currency][$type];

            return RussianDictionary::pluralize($amount, $forms);
        }

        // Fallback для неизвестных валют
        return EnglishDictionary::$currencyNames[$currency][$type] ?? strtolower($currency);
    }
}
