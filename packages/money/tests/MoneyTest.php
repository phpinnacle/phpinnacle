<?php

use PHPinnacle\Money\Money;

it('calculates percentages using minor units', function (int|float $percentage, int $expected) {
    expect(new Money(1000, 'BYN')->percent($percentage)->amount)->toBe($expected);
})->with([
    'zero' => [0, 0],
    'ten percent' => [10, 100],
    'hundred percent' => [100, 1000],
]);

it('floors fractional percentage results', function () {
    expect(new Money(999, 'BYN')->percent(10)->amount)->toBe(99);
});

it('rejects percentages outside the supported range', function (int $percentage) {
    new Money(1000, 'BYN')->percent($percentage);
})->with([-1, 101])->throws(InvalidArgumentException::class);

it('compares amounts', function () {
    $small = new Money(500, 'BYN');
    $large = new Money(1000, 'BYN');

    expect($large->gt($small))
        ->toBeTrue()
        ->and($small->gt($large))
        ->toBeFalse()
        ->and($large->gt(new Money(1000, 'BYN')))
        ->toBeFalse()
        ->and($large->gt(new Money(1000, 'BYN'), equal: true))
        ->toBeTrue()
        ->and($small->lt($large))
        ->toBeTrue()
        ->and($large->lt(new Money(1000, 'BYN')))
        ->toBeFalse()
        ->and($large->lt(new Money(1000, 'BYN'), equal: true))
        ->toBeTrue()
        ->and($large->eq(new Money(1000, 'BYN')))
        ->toBeTrue()
        ->and($large->eq($small))
        ->toBeFalse()
        ->and($small->compare($large))
        ->toBe(-1)
        ->and($large->compare(new Money(1000, 'BYN')))
        ->toBe(0)
        ->and($large->compare($small))
        ->toBe(1);
});

it('rejects operations between different non-zero currencies', function (string $operation) {
    $money = new Money(1000, 'BYN');
    $other = new Money(500, 'USD');

    $money->{$operation}($other);
})->with(['gt', 'add', 'sub'])->throws(InvalidArgumentException::class);

it('allows a zero amount in another currency', function () {
    $result = new Money(1000, 'BYN')->add(new Money(0, 'USD'));

    expect($result->amount)->toBe(1000)->and($result->currency)->toBe('BYN');
});

it('rejects unknown currencies', function () {
    new Money(1000, 'XYZ');
})->throws(InvalidArgumentException::class);
