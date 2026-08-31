<?php

namespace PHPinnacle\Money\Formatters;

use NumberToWords\Language\English\EnglishDictionary;
use NumberToWords\Language\English\EnglishExponentGetter;
use NumberToWords\Language\English\EnglishTripletTransformer;
use NumberToWords\NumberTransformer\NumberTransformer;
use NumberToWords\NumberTransformer\NumberTransformerBuilder;
use NumberToWords\Service\NumberToTripletsConverter;
use PHPinnacle\Money\Contracts\Formatter;
use PHPinnacle\Money\Money;

class EnglishFormatter implements Formatter
{
    private EnglishDictionary $dictionary;

    private EnglishExponentGetter $getter;

    private NumberTransformer $transformer;

    public function __construct()
    {
        $this->dictionary = new EnglishDictionary;
        $this->getter = new EnglishExponentGetter;
        $this->transformer = new NumberTransformerBuilder()
            ->withDictionary($this->dictionary)
            ->withWordsSeparatedBy(' ')
            ->transformNumbersBySplittingIntoTriplets(
                new NumberToTripletsConverter,
                new EnglishTripletTransformer($this->dictionary),
            )
            ->useRegularExponents($this->getter)
            ->build();
    }

    public function inflect(Money $money): array
    {
        [$paper, $coins] = $money->explode();

        $words['%A'] = $this->transformer->toWords($paper->amount);
        $words['%a'] = $this->transformer->toWords($coins->amount);
        $words['%C'] = EnglishDictionary::$currencyNames[$paper->currency][0][0];
        $words['%c'] = EnglishDictionary::$currencyNames[$coins->currency][1][0];
        $words['%D'] = $money->decimal();
        $words['%d'] = $coins->decimal();
        $words['%N'] = number_format($paper->amount, 0, ',', ' ');
        $words['%n'] = number_format($coins->amount, 0, ',', ' ');

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
