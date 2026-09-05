<?php

namespace PHPinnacle\Money\Formatters;

use NumberToWords\Language\Polish\PolishDictionary;
use NumberToWords\Language\Polish\PolishExponentInflector;
use NumberToWords\Language\Polish\PolishNounGenderInflector;
use NumberToWords\Language\Polish\PolishTripletTransformer;
use NumberToWords\NumberTransformer\NumberTransformer;
use NumberToWords\NumberTransformer\NumberTransformerBuilder;
use NumberToWords\Service\NumberToTripletsConverter;
use PHPinnacle\Money\Contracts\Formatter;
use PHPinnacle\Money\Money;

class PolishFormatter implements Formatter
{
    private PolishDictionary $dictionary;

    private PolishNounGenderInflector $inflector;

    private NumberTransformer $transformer;

    public function __construct()
    {
        $this->dictionary = new PolishDictionary;
        $this->inflector = new PolishNounGenderInflector;
        $this->transformer = new NumberTransformerBuilder()
            ->withDictionary($this->dictionary)
            ->withWordsSeparatedBy(' ')
            ->transformNumbersBySplittingIntoTriplets(
                new NumberToTripletsConverter,
                new PolishTripletTransformer($this->dictionary),
            )
            ->inflectExponentByNumbers(new PolishExponentInflector($this->inflector))
            ->build();
    }

    public function inflect(Money $money): array
    {
        [$paper, $coins] = $money->explode();

        $currency = PolishDictionary::$currencyNames[$money->currency];

        $words['%A'] = $this->transformer->toWords($paper->amount);
        $words['%a'] = $this->transformer->toWords($coins->amount);
        $words['%C'] = $this->inflector->inflectNounByNumber($paper->amount, ...$currency[0]);
        $words['%c'] = $this->inflector->inflectNounByNumber($coins->amount, ...$currency[1]);
        $words['%D'] = $money->decimal();
        $words['%d'] = $coins->decimal();
        $words['%N'] = number_format($paper->amount, decimals: 0, decimal_separator: ',', thousands_separator: ' ');
        $words['%n'] = number_format($coins->amount, decimals: 0, decimal_separator: ',', thousands_separator: ' ');

        $words['%S'] = implode(' ', [
            $words['%A'],
            $words['%C'],
            $words['%a'],
            $words['%c'],
        ]);

        $words['%T'] = $coins->isZero()
            ? implode(' ', [
                $words['%A'],
                $words['%C'],
            ])
            : $words['%S'];

        return $words;
    }
}
