<?php

use Illuminate\JsonSchema\JsonSchema;
use PHPinnacle\Eidos\JsonSchema\Schema;
use PHPinnacle\Eidos\JsonSchema\Types\AllOfType;
use PHPinnacle\Eidos\JsonSchema\Types\OneOfType;

it('preserves explicit null defaults through nested schema round trips', function () {
    $definition = [
        'type' => 'object',
        'properties' => ['label' => ['type' => ['string', 'null'], 'default' => null]],
    ];

    expect(Schema::fromArray($definition)->toArray())->toEqual($definition);
});

it('preserves arbitrary x-attribute values without interpreting their contents', function () {
    $definition = [
        'type' => 'string',
        'x-label' => '',
        'x-count' => 0,
        'x-ratio' => 1.5,
        'x-enabled' => false,
        'x-null' => null,
        'x-empty' => [],
        'x-options' => ['compact', 42, true, null],
        'x-display' => ['label' => 'Contact email', 'properties' => ['x-icon' => '<svg>']],
    ];

    $schema = Schema::fromArray($definition);

    expect($schema->toArray())->toBe($definition);
    expect(Schema::fromArray($schema->toArray())->toArray())->toBe($definition);
});

it('preserves read only annotations on nested schemas and composition siblings', function (bool $readOnly) {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'amount' => [
                'type' => 'integer',
                'readOnly' => $readOnly,
                'allOf' => [['minimum' => 0]],
            ],
        ],
    ])->toArray();

    expect($schema['properties']['amount']['readOnly'])
        ->toBe($readOnly)
        ->and(Schema::fromArray($schema)->toArray())
        ->toBe($schema);
})->with([true, false]);

it('retains extra attributes on nested nodes, reference siblings and union variants', function () {
    $definition = Schema::fromArray([
        'type' => 'object',
        'x-display' => ['expanded' => true],
        '$defs' => [
            'address' => ['type' => 'string', 'x-label' => 'Address', 'x-options' => ['primary']],
        ],
        'properties' => [
            'email' => ['anyOf' => [['$ref' => '#/$defs/address'], ['type' => 'null']]],
            'secondary' => ['$ref' => '#/$defs/address', 'x-label' => null, 'x-enabled' => false],
            'contacts' => [
                'type' => 'array',
                'x-display' => ['expanded' => false],
                'items' => [
                    'type' => 'object',
                    'x-group' => 'contact',
                    'properties' => [
                        'email' => ['$ref' => '#/$defs/address'],
                    ],
                ],
            ],
            'identifier' => [
                'anyOf' => [
                    ['type' => 'string', 'x-label' => 'External ID'],
                    ['type' => 'integer', 'x-label' => 'Internal ID'],
                ],
                'x-display' => ['compact' => true],
            ],
        ],
    ])->toArray();

    expect($definition['x-display'])->toBe(['expanded' => true]);
    expect($definition['properties']['email'])->toBe([
        'type' => ['string', 'null'],
        'x-label' => 'Address',
        'x-options' => ['primary'],
    ]);
    expect($definition['properties']['secondary'])->toBe([
        'type' => 'string',
        'x-label' => null,
        'x-options' => ['primary'],
        'x-enabled' => false,
    ]);
    expect($definition['properties']['contacts']['x-display'])->toBe(['expanded' => false]);
    expect($definition['properties']['contacts']['items']['x-group'])->toBe('contact');
    expect($definition['properties']['contacts']['items']['properties']['email'])->toBe([
        'type' => 'string',
        'x-label' => 'Address',
        'x-options' => ['primary'],
    ]);
    expect($definition['properties']['identifier']['x-display'])->toBe(['compact' => true]);
    expect($definition['properties']['identifier']['anyOf'])->toBe([
        ['type' => 'string', 'x-label' => 'External ID'],
        ['type' => 'integer', 'x-label' => 'Internal ID'],
    ]);
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
});

it('preserves x-icon annotations while Laravel resolves references and nullable unions', function () {
    $schema = Schema::fromArray([
        'type' => 'object',
        'x-icon' => 'heroicon-o-user',
        '$defs' => [
            'email/address' => ['type' => 'string', 'format' => 'email', 'x-icon' => 'heroicon-o-envelope'],
        ],
        'properties' => [
            'email' => ['anyOf' => [['$ref' => '#/$defs/email~1address'], ['type' => 'null']]],
            'secondary' => ['$ref' => '#/$defs/email~1address', 'x-icon' => 'heroicon-o-at-symbol'],
            'contacts' => [
                'type' => 'array',
                'items' => ['type' => 'object', 'properties' => ['email' => ['$ref' => '#/$defs/email~1address']]],
            ],
            'identifier' => [
                'anyOf' => [
                    ['type' => 'string', 'x-icon' => 'heroicon-o-document-text'],
                    ['type' => 'integer', 'x-icon' => 'heroicon-o-hashtag'],
                    ['type' => 'null'],
                ],
            ],
        ],
        'required' => ['email'],
        'additionalProperties' => false,
    ]);
    $definition = $schema->toArray();

    expect($definition['x-icon'])->toBe('heroicon-o-user');
    expect($definition['properties']['email'])->toMatchArray([
        'type' => ['string', 'null'],
        'format' => 'email',
        'x-icon' => 'heroicon-o-envelope',
    ]);
    expect($definition['properties']['secondary']['x-icon'])->toBe('heroicon-o-at-symbol');
    expect($definition['properties']['contacts']['items']['properties']['email']['x-icon'])
        ->toBe('heroicon-o-envelope');
    expect($definition['properties']['identifier']['anyOf'][0]['x-icon'])->toBe('heroicon-o-document-text');
    expect($definition['properties']['identifier']['anyOf'][1]['x-icon'])->toBe('heroicon-o-hashtag');
    expect($definition['properties']['identifier']['anyOf'][2])->toBe(['type' => 'null']);
    expect($definition['required'])->toBe(['email']);
    expect($definition['additionalProperties'])->toBeFalse();
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
});

it('retains the native Type modifiers and serialization for unannotated schemas', function () {
    $definition = ['type' => 'string', 'format' => 'uuid'];
    $schema = Schema::fromArray($definition)
        ->nullable()
        ->title('Identifier')
        ->description('A record identifier')
        ->enum(['sample']);
    $native = JsonSchema::fromArray($definition)
        ->nullable()
        ->title('Identifier')
        ->description('A record identifier')
        ->enum(['sample']);

    expect($schema->toArray())->toBe($native->toArray());
    expect($schema->nullable(false)->toArray())->toBe($native->nullable(false)->toArray());
});

it('preserves nested oneOf and allOf compositions with sibling properties and local references', function () {
    $schema = Schema::fromArray([
        'type' => 'object',
        '$defs' => ['token' => ['type' => 'string', 'format' => 'uuid', 'x-display' => 'Saved card']],
        'properties' => [
            'payment' => [
                'type' => 'object',
                'title' => 'Payment',
                'properties' => ['card' => ['type' => 'object'], 'token' => ['$ref' => '#/$defs/token']],
                'oneOf' => [['required' => ['card']], ['required' => ['token']]],
            ],
            'quantity' => ['allOf' => [['type' => 'integer', 'minimum' => 1], ['type' => 'integer', 'maximum' => 10]]],
        ],
        'required' => ['payment', 'external'],
    ]);
    $definition = $schema->toArray();

    expect($definition['properties']['payment']['oneOf'])->toBe([
        ['type' => 'object', 'required' => ['card']],
        ['type' => 'object', 'required' => ['token']],
    ]);
    expect($definition['properties']['payment']['properties']['token']['x-display'])->toBe('Saved card');
    expect($definition['properties']['quantity']['allOf'])->toBe([
        ['minimum' => 1, 'type' => 'integer'],
        ['maximum' => 10, 'type' => 'integer'],
    ]);
    expect($definition['required'])->toBe(['payment', 'external']);
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);

    $payment = Schema::fromArray($definition['properties']['payment'])->title('Checkout');
    expect($payment->toArray()['title'])->toBe('Checkout');
});

it('composes annotated schemas directly and makes the complete composition nullable', function (string $keyword) {
    $branch = Schema::fromArray(['type' => 'string', 'x-display' => ['compact' => true]]);
    $schema = Schema::{$keyword}([$branch, JsonSchema::string()->min(3)]);
    $definition = $schema->toArray();

    expect($definition[$keyword][0]['x-display'])->toBe(['compact' => true]);
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
    expect($schema->nullable()->toArray())->toBe(['anyOf' => [$definition, ['type' => 'null']]]);
    expect(Schema::fromArray($schema->toArray())->toArray())->toBe($schema->toArray());
    expect($schema->nullable(false)->toArray())->toBe($definition);
})->with(['oneOf', 'allOf']);

it('serializes composition types with their own keywords and makes the complete composition nullable', function (
    string $typeClass,
    string $keyword,
) {
    $type = new $typeClass([
        Schema::fromArray(['type' => 'string', 'readOnly' => true]),
        JsonSchema::string()->min(3),
    ])
        ->title('Value')
        ->description('A composed value')
        ->enum(['value']);
    $definition = $type->toArray();

    expect($definition[$keyword])
        ->toBe([['type' => 'string', 'readOnly' => true], ['minLength' => 3, 'type' => 'string']])
        ->and($definition['title'])
        ->toBe('Value')
        ->and($definition['description'])
        ->toBe('A composed value')
        ->and($definition['enum'])
        ->toBe(['value'])
        ->and($definition)
        ->not
        ->toHaveKey('anyOf')
        ->and($type->nullable()->toArray())
        ->toBe(['anyOf' => [$definition, ['type' => 'null']]])
        ->and($type->nullable(false)->toArray())
        ->toBe($definition);
})->with([
    'oneOf' => [OneOfType::class, 'oneOf'],
    'allOf' => [AllOfType::class, 'allOf'],
]);

it('preserves composition types nested in native object and array builders through the adapter', function () {
    $schema = new Schema(JsonSchema::object([
        'quantity' => new AllOfType([JsonSchema::integer()->min(1), JsonSchema::integer()->max(10)])->required(),
        'identifiers' => JsonSchema::array()->items(new OneOfType([JsonSchema::string(), JsonSchema::integer()])),
    ]));
    $definition = $schema->toArray();

    expect($definition['required'])
        ->toBe(['quantity'])
        ->and($definition['properties']['quantity']['allOf'])
        ->toBe([['minimum' => 1, 'type' => 'integer'], ['maximum' => 10, 'type' => 'integer']])
        ->and($definition['properties']['identifiers']['items']['oneOf'])
        ->toBe([['type' => 'string'], ['type' => 'integer']])
        ->and(Schema::fromArray($definition)->toArray())
        ->toBe($definition);

    expect(new OneOfType([$schema])->toArray()['oneOf'][0])
        ->toBe($definition)
        ->and(
            new OneOfType([JsonSchema::object([
                'quantity' => new AllOfType([JsonSchema::integer()->min(1)]),
            ])])->toArray()['oneOf'][0]['properties']['quantity']['allOf'],
        )
        ->toBe([['minimum' => 1, 'type' => 'integer']]);
});

it('preserves different compositions declared as siblings on the same schema', function () {
    $definition = Schema::fromArray([
        'type' => 'integer',
        'oneOf' => [['type' => 'integer', 'minimum' => 0]],
        'allOf' => [['type' => 'integer', 'maximum' => 10]],
        'readOnly' => true,
    ])->toArray();

    expect($definition['oneOf'])
        ->toBe([['minimum' => 0, 'type' => 'integer']])
        ->and($definition['allOf'])
        ->toBe([['maximum' => 10, 'type' => 'integer']])
        ->and($definition['readOnly'])
        ->toBeTrue()
        ->and(Schema::fromArray($definition)->toArray())
        ->toBe($definition);
});

it('retains annotations on referenced null branches within compositions', function () {
    $schema = Schema::fromArray([
        '$defs' => ['empty' => ['type' => 'null', 'x-display' => 'Empty value']],
        'oneOf' => [['type' => 'string'], ['$ref' => '#/$defs/empty']],
    ]);
    $definition = $schema->toArray();

    expect($definition['oneOf'][1])->toBe(['type' => ['null'], 'x-display' => 'Empty value']);
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
});

it('rejects invalid x-icon values at the schema parsing boundary', function (mixed $icon) {
    expect(fn () => Schema::fromArray(['type' => 'string', 'x-icon' => $icon]))
        ->toThrow(InvalidArgumentException::class, 'x-icon');
})->with([
    'empty' => '',
    'non-string' => 42,
    'array' => [['heroicon-o-user']],
    'HTML' => '<svg onload="alert(1)">',
    'path' => '../user',
]);

it('preserves supported field colors through references, nullable types and schema round trips', function () {
    $schema = Schema::fromArray([
        'type' => 'object',
        '$defs' => ['contact' => ['type' => ['string', 'null'], 'x-color' => 'success']],
        'properties' => [
            'email' => ['$ref' => '#/$defs/contact'],
            'secondary' => ['$ref' => '#/$defs/contact', 'x-color' => '#3b82f680'],
            'value' => [
                'anyOf' => [['type' => 'integer', 'x-color' => 'rgba(30, 144, 255, 0.5)'], ['type' => 'string']],
            ],
        ],
    ]);
    $definition = $schema->toArray();

    expect($definition['properties']['email']['x-color'])->toBe('success');
    expect($definition['properties']['secondary']['x-color'])->toBe('#3b82f680');
    expect($definition['properties']['value']['anyOf'][0]['x-color'])->toBe('rgba(30, 144, 255, 0.5)');
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
});

it('accepts palette names and numeric HEX and RGB colors without changing their values', function (string $color) {
    expect(Schema::fromArray(['type' => 'string', 'x-color' => $color])->toArray()['x-color'])->toBe($color);
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

it('rejects invalid x-color values at the schema parsing boundary', function (mixed $color) {
    expect(fn () => Schema::fromArray(['type' => 'string', 'x-color' => $color]))
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
