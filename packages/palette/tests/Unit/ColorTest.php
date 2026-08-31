<?php

use Filament\Support\Facades\FilamentColor;
use PHPinnacle\Palette\Color;
use Tests\TestCase;

uses(TestCase::class);

it('converts and generates colors', function () {
    expect(Color::hex(Color::Blue))
        ->toMatch('/^#[0-9a-f]{6}$/i')
        ->and(Color::shades('#2563eb'))
        ->toHaveKeys([50, 500, 950])
        ->and(Color::random())
        ->toMatch('/^#[0-9a-f]{6}$/i');
});

it('resolves aliased palette shades', function () {
    expect(Color::hex([
        400 => Color::Blue[400],
        500 => 400,
    ]))
        ->toBe(Color::hex(Color::Blue, 400));
});

it('falls back to the primary semantic color', function () {
    FilamentColor::register(Color::DEFAULT);

    expect(Color::resolve('primary'))
        ->toBe(Color::hex(Color::Blue))
        ->and(Color::resolve('missing'))
        ->toBe(Color::hex(Color::Blue));
});
