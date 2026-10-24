<?php

use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;

it('rejects invalid x-icon values at the presentation boundary', function (mixed $icon) {
    expect(fn () => SchemaPresentation::render(['type' => 'string', 'x-icon' => $icon], withIcons: false))
        ->toThrow(InvalidArgumentException::class, 'x-icon');
})->with([
    'empty' => '',
    'non-string' => 42,
    'array' => [['heroicon-o-user']],
    'HTML' => '<svg onload="alert(1)">',
    'path' => '../user',
]);

it('accepts palette names and numeric HEX and RGB colors without changing their values', function (string $color) {
    $definition = ['type' => 'string', 'x-color' => $color];
    expect(SchemaPresentation::render($definition, withIcons: false)['definition'])->toBe($definition);
})->with([
    'primary',
    'brand-accent',
    '#abc',
    '#abcd',
    '#AABBCC',
    '#AABBCCDD',
    'rgb(0, 128, 255)',
    'rgba(0, 128, 255, 0)',
    'rgba(10%, 20%, 30%, 50%)',
    'rgba(0 128 255 / .5)',
]);

it('rejects invalid x-color values at the presentation boundary', function (mixed $color) {
    expect(fn () => SchemaPresentation::render(['type' => 'string', 'x-color' => $color], withIcons: false))
        ->toThrow(InvalidArgumentException::class, 'x-color');
})->with([
    'empty' => '',
    'non-string' => 42,
    'null' => null,
    'array' => [['success']],
    'invalid HEX' => '#12xz34',
    'wrong HEX length' => '#12345',
    'incomplete RGBA' => 'rgba(1, 2)',
    'invalid channel' => 'rgba(1, two, 3, 1)',
    'CSS declaration' => 'red; background: url(https://example.com)',
]);
