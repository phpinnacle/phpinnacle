<?php

use Filament\Support\Colors\ColorManager;
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

it('resolves semantic colors from the current Filament registry', function () {
    FilamentColor::swap(new ColorManager);
    FilamentColor::register([
        'brand' => Color::Blue,
        'primary' => Color::Blue,
    ]);

    expect(Color::resolve('brand'))
        ->toBe(Color::hex(Color::Blue))
        ->and(Color::resolve('missing'))
        ->toBe(Color::hex(Color::Blue));

    FilamentColor::swap(new ColorManager);
    FilamentColor::register([
        'brand' => Color::Green,
        'primary' => Color::Red,
    ]);

    expect(Color::resolve('brand'))
        ->toBe(Color::hex(Color::Green))
        ->and(Color::resolve('primary'))
        ->toBe(Color::hex(Color::Red))
        ->and(Color::resolve('missing'))
        ->toBe(Color::hex(Color::Red));
});
