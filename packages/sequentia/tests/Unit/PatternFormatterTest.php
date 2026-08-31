<?php

use Carbon\CarbonImmutable;
use PHPinnacle\Sequentia\PatternFormatter;
use PHPinnacle\Sequentia\Sequence;

it('formats placeholders and modifiers', function () {
    expect(PatternFormatter::format(
        '[prefix]-[CODE]-[code:2]-[code:1-2]-[missing]',
        ['prefix' => 'order', 'code' => 'abcde'],
    ))->toBe('order-ABCDE-ab-bc-[missing]');
});

it('creates immutable tenant and date variants', function () {
    $original = Sequence::create('ORD-[SY]', 'orders');
    $configured = $original
        ->forTenant(42)
        ->on(CarbonImmutable::parse('2026-07-17'));

    expect($configured)
        ->not
        ->toBe($original)
        ->and($configured->tenant)
        ->toBe('42')
        ->and($configured->date->format('Y-m-d'))
        ->toBe('2026-07-17')
        ->and($original->tenant)
        ->toBeNull();
});
