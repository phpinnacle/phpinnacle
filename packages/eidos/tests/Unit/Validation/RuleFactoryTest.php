<?php

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PHPinnacle\Eidos\JsonSchema\Schema;
use PHPinnacle\Eidos\Validation\RuleFactory;
use Tests\TestCase;

uses(TestCase::class);

it('creates Laravel rules from native JSON Schema types', function () {
    $schema = JsonSchema::object([
        'email' => JsonSchema::string()->format('email')->nullable(),
        'quantity' => JsonSchema::integer()->min(1)->max(10),
        'active' => JsonSchema::boolean(),
    ]);

    expect(new RuleFactory()->make($schema, [], 'payload'))->toBe([
        'payload' => ['array'],
        'payload.email' => ['nullable', 'email'],
        'payload.quantity' => ['integer', 'min:1', 'max:10'],
        'payload.active' => ['boolean'],
    ]);
});

it('preserves application rules and their ordering across nested properties and allOf', function () {
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
    $unique = Rule::unique('users', 'email');
    $callback = function (string $attribute, mixed $value, Closure $fail) {
        $fail('Rejected by the application.');
    };
    $rules = new RuleFactory()->make(
        $schema,
        [
            'payload' => ['required'],
            'payload.customer.email' => [$unique, $callback, 'email'],
            'payload.quantity' => ['required', 'integer'],
            'unrelated' => ['boolean'],
        ],
        'payload',
    );

    expect($rules['payload.customer.email'])->toBe([$unique, $callback, 'email']);
    expect($rules['payload.quantity'])->toBe(['required', 'integer', 'min:1', 'max:10']);
    expect($rules['payload.token'])->toBe(['string']);
    expect($rules['unrelated'])->toBe(['boolean']);
    expect(Validator::make([], $rules)->fails())->toBeTrue();

    $rules['payload.customer.email'] = array_slice($rules['payload.customer.email'], 1);
    expect(
        Validator::make(['payload' => ['customer' => ['email' => 'user@example.com'], 'quantity' => 5]], $rules)
            ->errors()
            ->first('payload.customer.email'),
    )
        ->toBe('Rejected by the application.');
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

it('appends x-validation rules to inferred rules', function () {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'card' => [
                'type' => ['object', 'null'],
                'x-validation' => ['required_without:payload.token', 'prohibits:payload.token'],
            ],
        ],
    ]);

    expect(new RuleFactory()->make($schema, [], 'payload')['payload.card'])->toBe([
        'nullable',
        'array',
        'required_without:payload.token',
        'prohibits:payload.token',
    ]);
});

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
