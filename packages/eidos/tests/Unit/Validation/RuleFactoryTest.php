<?php

use Illuminate\Support\Facades\Validator;
use PHPinnacle\Eidos\Validation\RuleFactory;
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;
use Tests\TestCase;

uses(TestCase::class);

it('validates nullable email, bounded quantities and boolean fields from native schemas', function (
    array $payload,
    bool $passes,
) {
    $schema = Schema::object([
        'email' => SchemaBuilder::make()->type('string')->format('email')->nullable()->build(),
        'quantity' => SchemaBuilder::make()->type('integer')->min(1)->max(10)->build(),
        'active' => Schema::boolean(),
    ]);

    $rules = new RuleFactory()->make($schema, [], 'payload');

    expect(Validator::make(['payload' => $payload], $rules)->passes())->toBe($passes);
})->with([
    'valid' => [['email' => 'user@example.com', 'quantity' => 5, 'active' => true], true],
    'optional fields' => [[], true],
    'null email' => [['email' => null], true],
    'invalid email' => [['email' => 'invalid'], false],
    'below minimum' => [['quantity' => 0], false],
    'above maximum' => [['quantity' => 11], false],
    'fractional quantity' => [['quantity' => 1.5], false],
    'invalid boolean' => [['active' => 'enabled'], false],
]);

it('accepts only null when a null-schema value is supplied and allows absence', function (mixed $value, bool $passes) {
    $rules = new RuleFactory()->make(Schema::null(), [], 'value');

    expect(Validator::make([], $rules)->passes())
        ->toBeTrue()
        ->and(Validator::make(['value' => $value], $rules)->passes())
        ->toBe($passes);
})->with([
    'null' => [null, true],
    'empty string' => ['', false],
    'whitespace' => ['   ', false],
    'string' => ['value', false],
    'zero' => [0, false],
    'false' => [false, false],
    'empty array' => [[], false],
]);

it('validates null schemas in nested properties and array items', function () {
    $rules = new RuleFactory()->make(Schema::object([
        'nested' => Schema::object(['value' => Schema::null()]),
        'items' => SchemaBuilder::make()->type('array')->items(Schema::null())->build(),
    ]));

    expect(Validator::make([], $rules)->passes())
        ->toBeTrue()
        ->and(Validator::make(['nested' => ['value' => null], 'items' => [null]], $rules)->passes())
        ->toBeTrue()
        ->and(
            Validator::make(['nested' => ['value' => ''], 'items' => ['']], $rules)
                ->errors()
                ->keys(),
        )
        ->toEqualCanonicalizing(['nested.value', 'items.0']);
});

it('validates null schemas inside alternative branches while allowing absence', function (
    string $keyword,
    mixed $value,
    bool $passes,
) {
    $rules = new RuleFactory()->make(Schema::fromArray([$keyword => [['type' => 'null']]]), [], 'value');

    expect(Validator::make([], $rules)->passes())
        ->toBeTrue()
        ->and(Validator::make(['value' => $value], $rules)->passes())
        ->toBe($passes);
})->with([
    'anyOf null' => ['anyOf', null, true],
    'anyOf empty string' => ['anyOf', '', false],
    'oneOf null' => ['oneOf', null, true],
    'oneOf empty string' => ['oneOf', '', false],
]);

it('applies union bounds within the matching type branch', function (
    array $types,
    array $bounds,
    mixed $value,
    bool $passes,
) {
    $schema = SchemaBuilder::make(Schema::fromArray(['type' => $types, ...$bounds]))
        ->nullable()
        ->build();
    $rules = new RuleFactory()->make($schema, [], 'value');

    expect(Validator::make(['value' => $value], $rules)->passes())->toBe($passes);
})->with([
    'integer minimum' => [['integer', 'boolean'], ['minimum' => 10], 12, true],
    'integer below minimum' => [['integer', 'boolean'], ['minimum' => 10], 9, false],
    'boolean ignores numeric minimum' => [['integer', 'boolean'], ['minimum' => 10], false, true],
    'null remains nullable' => [['integer', 'boolean'], ['minimum' => 10], null, true],
    'integer maximum' => [['integer', 'boolean'], ['maximum' => 20], 20, true],
    'integer above maximum' => [['integer', 'boolean'], ['maximum' => 20], 21, false],
    'integer ignores string length' => [['integer', 'string'], ['minLength' => 3, 'minimum' => 10], 12, true],
    'string ignores numeric minimum' => [['integer', 'string'], ['minLength' => 3, 'minimum' => 10], 'abc', true],
    'string below minimum length' => [['integer', 'string'], ['minLength' => 3, 'minimum' => 10], 'ab', false],
    'string above maximum length' => [['integer', 'string'], ['maxLength' => 3], 'abcd', false],
    'array minimum items' => [['integer', 'array'], ['minItems' => 2, 'minimum' => 10], [1, 2], true],
    'array below minimum items' => [['integer', 'array'], ['minItems' => 2, 'minimum' => 10], [1], false],
    'array above maximum items' => [['integer', 'array'], ['maxItems' => 2], [1, 2, 3], false],
]);

it('keeps union enums and sibling-dependent annotation rules active', function () {
    $rules = new RuleFactory()->make(Schema::object([
        'choice' => SchemaBuilder::make(Schema::union(['integer', 'boolean']))
            ->enum([9, 12, 13])
            ->with('minimum', 10)
            ->with('x-validation', ['required_without:backup', 'not_in:12'])
            ->build(),
        'backup' => Schema::string(),
    ]));

    expect(Validator::make([], $rules)->fails())->toBeTrue();
    expect(Validator::make(['backup' => 'saved'], $rules)->passes())->toBeTrue();
    expect(Validator::make(['choice' => 13], $rules)->passes())->toBeTrue();
    expect(Validator::make(['choice' => 12], $rules)->fails())->toBeTrue();
    expect(Validator::make(['choice' => 15], $rules)->fails())->toBeTrue();
    expect(Validator::make(['choice' => 9], $rules)->fails())->toBeTrue();
});

it('combines application validation with nested schema and allOf constraints', function () {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'customer' => ['type' => 'object', 'properties' => ['email' => ['type' => 'string', 'format' => 'email']]],
            'quantity' => ['type' => 'integer', 'minimum' => 1],
        ],
        'allOf' => [
            ['type' => 'object', 'properties' => ['quantity' => ['type' => 'integer', 'maximum' => 10]]],
            ['type' => 'object', 'properties' => ['token' => ['type' => 'string']]],
        ],
    ]);
    $rules = new RuleFactory()->make(
        $schema,
        [
            'payload' => ['required'],
            'payload.customer.email' => ['ends_with:@example.com'],
            'payload.quantity' => ['required', 'integer'],
            'unrelated' => ['boolean'],
        ],
        'payload',
    );

    expect(Validator::make([], $rules)->fails())->toBeTrue();
    expect(
        Validator::make(['payload' => ['customer' => ['email' => 'user@example.com'], 'quantity' => 5]], $rules)
            ->passes(),
    )->toBeTrue();
    expect(
        Validator::make([
            'payload' => [
                'customer' => ['email' => 'user@other.com'],
                'quantity' => 11,
                'token' => [],
            ],
            'unrelated' => 'invalid',
        ], $rules)
            ->errors()
            ->keys(),
    )
        ->toEqualCanonicalizing(['payload.customer.email', 'payload.quantity', 'payload.token', 'unrelated']);
    expect(Validator::make(['payload' => ['quantity' => 0]], $rules)->fails())->toBeTrue();
    expect(Validator::make(['payload' => ['token' => 'saved-card']], $rules)->fails())->toBeTrue();
});

it('runs application validation before inferred rules when bail is requested', function () {
    $rules = new RuleFactory()->make(
        Schema::object(['email' => SchemaBuilder::make()->type('string')->format('email')->build()]),
        [
            'email' => [
                'bail',
                function (string $attribute, mixed $value, Closure $fail) {
                    $fail('Rejected by the application.');
                },
            ],
        ],
    );

    expect(Validator::make(['email' => 'invalid'], $rules)->errors()->get('email'))
        ->toBe(['Rejected by the application.']);
});

it('validates required fields only within present optional objects and checks array items', function () {
    $schema = Schema::object([
        'customer' => SchemaBuilder::make(Schema::object([
            'email' => SchemaBuilder::make()->type('string')->format('email')->build(),
        ]))
            ->required(['email'])
            ->nullable()
            ->build(),
        'items' => SchemaBuilder::make()
            ->type('array')
            ->min(1)
            ->max(2)
            ->items(
                SchemaBuilder::make(Schema::object([
                    'quantity' => SchemaBuilder::make()->type('integer')->min(1)->build(),
                ]))
                    ->required(['quantity'])
                    ->build(),
            )
            ->build(),
    ]);
    $rules = new RuleFactory()->make($schema);

    expect(Validator::make([], $rules)->passes())->toBeTrue();
    expect(Validator::make(['customer' => null], $rules)->passes())->toBeTrue();
    expect(Validator::make(['customer' => []], $rules)->fails())->toBeTrue();
    expect(Validator::make(['items' => []], $rules)->fails())->toBeTrue();
    expect(Validator::make(['items' => [['quantity' => 1], ['quantity' => 2]]], $rules)->passes())->toBeTrue();
    expect(Validator::make(['items' => [['quantity' => 0]]], $rules)->fails())->toBeTrue();
    expect(Validator::make(['items' => [[]]], $rules)->fails())->toBeTrue();
});

it('applies conditional x-validation rules alongside schema types', function (array $payment, bool $passes) {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'card' => [
                'type' => ['object', 'null'],
                'x-validation' => ['required_without:payload.token', 'prohibits:payload.token'],
            ],
            'token' => ['type' => 'string'],
        ],
    ]);

    $rules = new RuleFactory()->make($schema, [], 'payload');

    expect(Validator::make(['payload' => $payment], $rules)->passes())->toBe($passes);
})->with([
    'card' => [['card' => ['number' => 'encrypted']], true],
    'token' => [['token' => 'saved-card'], true],
    'neither' => [[], false],
    'both' => [['card' => ['number' => 'encrypted'], 'token' => 'saved-card'], false],
    'invalid card type' => [['card' => 'encrypted'], false],
]);

it('validates exactly one required-property branch while keeping the prefixed object optional', function (
    array $payment,
    bool $passes,
) {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => ['card' => ['type' => 'object'], 'token' => ['type' => 'string']],
        'oneOf' => [['required' => ['card']], ['required' => ['token']]],
    ]);
    $rules = new RuleFactory()->make($schema, [], 'payment');

    expect(Validator::make([], $rules)->passes())->toBeTrue();
    expect(Validator::make(['payment' => $payment], $rules)->passes())->toBe($passes);
})->with([
    'card' => [['card' => ['number' => 'encrypted']], true],
    'token' => [['token' => 'stored-card'], true],
    'neither' => [[], false],
    'both' => [['card' => ['number' => 'encrypted'], 'token' => 'stored-card'], false],
]);

it('validates alternative constraints and distinguishes anyOf from oneOf', function (
    string $keyword,
    mixed $value,
    bool $passes,
) {
    $schema = $keyword === 'oneOf'
        ? Schema::oneOf([
            SchemaBuilder::make()->type('integer')->min(1)->build(),
            SchemaBuilder::make()->type('number')->max(10)->build(),
        ])
        : Schema::anyOf([
            SchemaBuilder::make()->type('integer')->min(1)->build(),
            SchemaBuilder::make()->type('number')->max(10)->build(),
        ]);
    $rules = new RuleFactory()->make($schema, [], 'value');

    expect(Validator::make(['value' => $value], $rules)->passes())->toBe($passes);
})->with([
    'anyOf overlapping' => ['anyOf', 5, true],
    'oneOf overlapping' => ['oneOf', 5, false],
    'oneOf one match' => ['oneOf', 11, true],
    'anyOf no match' => ['anyOf', 'invalid', false],
]);

it('validates each wildcard alternative independently and keeps sibling-dependent rules available', function () {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'choice' => [
                'oneOf' => [
                    ['type' => 'integer'],
                    ['type' => 'string', 'format' => 'email'],
                ],
            ],
        ],
    ]);
    $rules = new RuleFactory()->make(
        SchemaBuilder::make()
            ->type('array')
            ->items(Schema::anyOf([
                Schema::integer(),
                SchemaBuilder::make()->type('string')->format('email')->build(),
            ]))
            ->build(),
        [],
        'choices',
    );

    expect(Validator::make(['choices' => [1, 'user@example.com']], $rules)->passes())->toBeTrue();
    expect(Validator::make(['choices' => [1, 'invalid']], $rules)->fails())->toBeTrue();
    expect(Validator::make(['choice' => 'user@example.com'], new RuleFactory()->make($schema))->passes())->toBeTrue();
});

it('validates nullable multi-type unions and scalar enums', function () {
    $schema = Schema::object([
        'value' => SchemaBuilder::make(Schema::union(['integer', 'boolean']))
            ->nullable()
            ->build(),
        'status' => SchemaBuilder::make()->type('string')->enum(['draft', 'published'])->build(),
    ]);
    $rules = new RuleFactory()->make($schema);

    expect(Validator::make(['value' => null, 'status' => 'draft'], $rules)->passes())->toBeTrue();
    expect(Validator::make(['value' => 42], $rules)->passes())->toBeTrue();
    expect(Validator::make(['value' => false], $rules)->passes())->toBeTrue();
    expect(Validator::make(['value' => []], $rules)->fails())->toBeTrue();
    expect(Validator::make(['status' => 'missing'], $rules)->fails())->toBeTrue();
});

it('rejects present values for false schemas while preserving optional absence', function (mixed $value) {
    $rules = new RuleFactory()->make(Schema::fromArray(['properties' => ['value' => false]]));
    expect(Validator::make([], $rules)->passes())
        ->toBeTrue()
        ->and(Validator::make(['value' => $value], $rules)->fails())
        ->toBeTrue();
})->with(['null' => [null], 'empty string' => [''], 'false' => [false], 'zero' => [0], 'empty array' => [[]]]);

it('validates boolean composition branches with their original meaning', function () {
    $schema = Schema::fromArray(['oneOf' => [false, ['type' => 'integer']]]);
    $rules = new RuleFactory()->make($schema, [], 'value');
    expect(Validator::make(['value' => 1], $rules)->passes())
        ->toBeTrue()
        ->and(Validator::make(['value' => 'text'], $rules)->fails())
        ->toBeTrue();
});
