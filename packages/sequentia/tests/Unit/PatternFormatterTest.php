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

it('reports regular expression execution failures', function () {
    $limit = ini_get('pcre.backtrack_limit');

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Backtrack limit exhausted');

    try {
        // @mago-expect lint:no-ini-set
        ini_set('pcre.backtrack_limit', '0');

        PatternFormatter::format('[code]', ['code' => 'abc']);
    } finally {
        // @mago-expect lint:no-ini-set
        ini_set('pcre.backtrack_limit', $limit);
    }
});
