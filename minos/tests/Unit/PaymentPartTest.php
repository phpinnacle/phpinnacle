<?php

use Carbon\CarbonImmutable;
use PHPinnacle\Minos\Models\PaymentPart;
use PHPinnacle\Money\Money;

it('creates a part from a valid array with a Money instance', function (): void {
    $part = PaymentPart::create([
        'amount' => new Money(500, 'BYN'),
        'date' => '2026-01-15',
    ]);

    expect($part->amount->amount)->toBe(500)
        ->and($part->amount->currency)->toBe('BYN')
        ->and($part->date->toDateString())->toBe('2026-01-15');
});

it('creates a part from a valid array with a decomposed money shape', function (): void {
    $part = PaymentPart::create([
        'amount' => ['amount' => 300, 'currency' => 'BYN'],
        'date' => '2026-02-01',
    ]);

    expect($part->amount->amount)->toBe(300)
        ->and($part->amount->currency)->toBe('BYN')
        ->and($part->date->toDateString())->toBe('2026-02-01');
});

it('rejects an array missing the amount key', function (): void {
    PaymentPart::create(['date' => '2026-01-15']);
})->throws(InvalidArgumentException::class);

it('rejects an array missing the date key', function (): void {
    PaymentPart::create(['amount' => new Money(500, 'BYN')]);
})->throws(InvalidArgumentException::class);

it('resolves an existing instance without rewrapping it', function (): void {
    $part = new PaymentPart(new Money(500, 'BYN'), CarbonImmutable::parse('2026-01-15'));

    expect(PaymentPart::resolve($part))->toBe($part);
});

it('resolves a raw array by delegating to create', function (): void {
    $part = PaymentPart::resolve([
        'amount' => new Money(300, 'BYN'),
        'date' => '2026-02-01',
    ]);

    expect($part->amount->amount)->toBe(300)
        ->and($part->date->toDateString())->toBe('2026-02-01');
});

it('returns a new instance with the amount increased, leaving the original untouched', function (): void {
    $date = CarbonImmutable::parse('2026-01-15');
    $original = new PaymentPart(new Money(500, 'BYN'), $date);

    $updated = $original->add(new Money(200, 'BYN'));

    expect($updated)->not->toBe($original)
        ->and($updated->amount->amount)->toBe(700)
        ->and($updated->date)->toBe($date)
        ->and($original->amount->amount)->toBe(500);
});
