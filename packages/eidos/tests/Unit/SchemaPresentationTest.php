<?php

use BladeUI\Icons\Exceptions\SvgNotFound;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;
use Tests\TestCase;

uses(TestCase::class);

it('prepares readable property titles across nested objects, arrays and union branches without changing the schema', function () {
    $definition = [
        'properties' => [
            'unit_price' => ['type' => 'number'],
            'point_of_contact' => ['type' => 'string', 'title' => 'Contact person'],
            'billingAddress' => ['properties' => ['postal-code' => ['type' => 'string']]],
            'line_items' => ['items' => ['properties' => ['productName' => ['type' => 'string']]]],
        ],
        'anyOf' => [['properties' => ['delivery_method' => ['type' => 'string']]]],
    ];

    expect(SchemaPresentation::render($definition)['propertyTitles'])->toBe([
        'unit_price' => 'Unit Price',
        'point_of_contact' => 'Point of Contact',
        'billingAddress' => 'Billing Address',
        'postal-code' => 'Postal Code',
        'line_items' => 'Line Items',
        'productName' => 'Product Name',
        'delivery_method' => 'Delivery Method',
    ]);
    expect($definition['properties']['point_of_contact']['title'])->toBe('Contact person');
    expect($definition['properties']['unit_price'])->not->toHaveKey('title');
});

it('prepares titles and keeps the complete definition when icon rendering is disabled', function () {
    $definition = [
        'type' => 'object',
        'properties' => [
            'unit_price' => ['type' => 'number', 'x-icon' => 'heroicon-o-currency-dollar'],
        ],
    ];
    $presentation = SchemaPresentation::render($definition, withIcons: false);

    expect($presentation)->toBe([
        'definition' => $definition,
        'propertyTitles' => ['unit_price' => 'Unit Price'],
        'icons' => [],
    ]);
});

it('renders only referenced registered icons as decorative SVG markup', function () {
    $presentation = SchemaPresentation::render([
        'type' => 'object',
        'x-icon' => 'heroicon-o-user',
        'properties' => [
            'customer' => ['type' => 'object', 'x-icon' => 'heroicon-o-user'],
            'contacts' => ['type' => 'array', 'items' => ['type' => 'string', 'x-icon' => 'heroicon-o-envelope']],
            'value' => ['anyOf' => [['type' => 'integer'], ['type' => 'string', 'x-icon' => 'heroicon-o-envelope']]],
        ],
    ]);

    $icons = $presentation['icons'];

    expect(array_keys($icons))->toBe(['heroicon-o-user', 'heroicon-o-envelope']);
    expect($icons['heroicon-o-user'])->toContain('<svg', 'aria-hidden="true"');
    expect($icons['heroicon-o-envelope'])->toContain('<svg', 'aria-hidden="true"');
    expect(SchemaPresentation::render(['type' => 'string'])['icons'])->toBe([]);
    expect(fn () => SchemaPresentation::render(['type' => 'string', 'x-icon' => 'heroicon-o-nonexistent']))
        ->toThrow(SvgNotFound::class);
});

it('does not interpret schema-like data inside arbitrary annotations', function () {
    $definition = [
        'type' => 'string',
        'x-display' => ['properties' => ['unit_price' => ['x-icon' => '<svg>']]],
    ];

    expect(SchemaPresentation::render($definition))->toBe([
        'definition' => $definition,
        'propertyTitles' => [],
        'icons' => [],
    ]);
});
