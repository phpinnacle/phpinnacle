<?php

namespace PHPinnacle\Money\Formatters;

class RussianDictionary
{
    /**
     * @var array<string, array{array{string, string, string}, array{string, string, string}}>
     */
    public static array $currencyNames = [
        'BYN' => [
            ['белорусский рубль', 'белорусских рубля', 'белорусских рублей'],
            ['копейка',           'копейки',           'копеек'],
        ],
        'RUB' => [
            ['российский рубль', 'российских рубля', 'российских рублей'],
            ['копейка',          'копейки',          'копеек'],
        ],
        'USD' => [
            ['доллар', 'доллара', 'долларов'],
            ['цент',   'цента',   'центов'],
        ],
        'EUR' => [
            ['евро',     'евро',      'евро'],
            ['евроцент', 'евроцента', 'евроцентов'],
        ],
    ];

    /**
     * Inflects a noun according to its number.
     *
     * @param array{string, string, string} $forms
     */
    public static function pluralize(int $number, array $forms): string
    {
        $number = abs($number) % 100;
        $n1 = $number % 10;

        if ($number > 10 && $number < 20) {
            return $forms[2]; // множественное число
        }

        if ($n1 > 1 && $n1 < 5) {
            return $forms[1]; // родительный падеж единственного числа
        }

        if ($n1 === 1) {
            return $forms[0]; // единственное число
        }

        return $forms[2]; // множественное число
    }
}
