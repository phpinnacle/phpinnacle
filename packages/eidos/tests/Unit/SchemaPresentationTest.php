<?php

use BladeUI\Icons\Exceptions\SvgNotFound;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;
use Tests\TestCase;

uses(TestCase::class);

it('renders metadata from referenced definitions without replacing the original schema', function () {
    $definition = [
        'type' => 'object',
        '$defs' => [
            'contact/address~' => [
                'type' => 'object',
                'properties' => ['postal_code' => ['type' => 'string', 'x-icon' => 'heroicon-o-map-pin']],
            ],
            'alias' => ['$ref' => '#/$defs/contact~1address~0'],
        ],
        'properties' => ['home_address' => ['$ref' => '#/$defs/alias']],
    ];
    $presentation = SchemaPresentation::render($definition);

    expect($presentation['definition'])
        ->toBe($definition)
        ->and($presentation['propertyTitles'])
        ->toBe(['home_address' => 'Home Address', 'postal_code' => 'Postal Code'])
        ->and($presentation['icons']['heroicon-o-map-pin'])
        ->toContain('<svg');
});

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

    $presentation = SchemaPresentation::render($definition);

    expect($presentation['propertyTitles'])->toBe([
        'unit_price' => 'Unit Price',
        'point_of_contact' => 'Point of Contact',
        'billingAddress' => 'Billing Address',
        'postal-code' => 'Postal Code',
        'line_items' => 'Line Items',
        'productName' => 'Product Name',
        'delivery_method' => 'Delivery Method',
    ]);
    expect($presentation['definition'])->toBe($definition);
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
