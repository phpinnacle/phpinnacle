<?php

use PHPinnacle\Common\Models\Range;

it('matches inclusive and strict numeric ranges', function () {
    $range = Range::make(10, 20);

    expect($range->match(10))
        ->toBeTrue()
        ->and($range->match(20))
        ->toBeTrue()
        ->and($range->match(10, strict: true))
        ->toBeFalse()
        ->and($range->match(15, strict: true))
        ->toBeTrue()
        ->and($range->match(21))
        ->toBeFalse();
});

it('matches arrays and open ranges', function () {
    expect(Range::make(2, 4)->match(['one', 'two', 'three']))
        ->toBeTrue()
        ->and(Range::make(null, 2)->match(1))
        ->toBeTrue()
        ->and(Range::make(2, null)->match(3))
        ->toBeTrue();
});
