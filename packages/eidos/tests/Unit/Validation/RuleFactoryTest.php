<?php

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use PHPinnacle\Eidos\JsonSchema\Schema;
use PHPinnacle\Eidos\Validation\RuleFactory;
use Tests\TestCase;

uses(TestCase::class);

it('validates nullable email, bounded quantities and boolean fields from native schemas', function (
    array $payload,
    bool $passes,
) {
    $schema = JsonSchema::object([
        'email' => JsonSchema::string()->format('email')->nullable(),
        'quantity' => JsonSchema::integer()->min(1)->max(10),
        'active' => JsonSchema::boolean(),
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
    $rules = new RuleFactory()->make(JsonSchema::object(['email' => JsonSchema::string()->format('email')]), [
        'email' => [
            'bail',
            function (string $attribute, mixed $value, Closure $fail) {
                $fail('Rejected by the application.');
            },
        ],
    ]);

    expect(Validator::make(['email' => 'invalid'], $rules)->errors()->get('email'))
        ->toBe(['Rejected by the application.']);
});

it('validates required fields only within present optional objects and checks array items', function () {
    $schema = JsonSchema::object([
        'customer' => JsonSchema::object(['email' => JsonSchema::string()->format('email')->required()])->nullable(),
        'items' => JsonSchema::array()
            ->min(1)
            ->max(2)
            ->items(JsonSchema::object([
                'quantity' => JsonSchema::integer()->min(1)->required(),
            ])),
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
        ? Schema::oneOf([JsonSchema::integer()->min(1), JsonSchema::number()->max(10)])
        : JsonSchema::anyOf([JsonSchema::integer()->min(1), JsonSchema::number()->max(10)]);
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
        JsonSchema::array()->items(JsonSchema::anyOf([
            JsonSchema::integer(),
            JsonSchema::string()->format('email'),
        ])),
        [],
        'choices',
    );

    expect(Validator::make(['choices' => [1, 'user@example.com']], $rules)->passes())->toBeTrue();
    expect(Validator::make(['choices' => [1, 'invalid']], $rules)->fails())->toBeTrue();
    expect(Validator::make(['choice' => 'user@example.com'], new RuleFactory()->make($schema))->passes())->toBeTrue();
});

it('validates nullable multi-type unions and scalar enums', function () {
    $schema = JsonSchema::object([
        'value' => JsonSchema::union(['integer', 'boolean'])->nullable(),
        'status' => JsonSchema::string()->enum(['draft', 'published']),
    ]);
    $rules = new RuleFactory()->make($schema);

    expect(Validator::make(['value' => null, 'status' => 'draft'], $rules)->passes())->toBeTrue();
    expect(Validator::make(['value' => 42], $rules)->passes())->toBeTrue();
    expect(Validator::make(['value' => false], $rules)->passes())->toBeTrue();
    expect(Validator::make(['value' => []], $rules)->fails())->toBeTrue();
    expect(Validator::make(['status' => 'missing'], $rules)->fails())->toBeTrue();
});
