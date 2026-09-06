<?php

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use PHPinnacle\OpenApi\Validation\RuleFactory;

it('creates Laravel rules from an OpenAPI schema', function () {
    $schema = new OA\Schema(
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
            new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 10),
            new OA\Property(property: 'active', type: 'boolean'),
        ],
        type: 'object',
    );

    $rules = new RuleFactory()->make($schema, [], 'payload');

    expect($rules)->toMatchArray([
        'payload.email' => ['nullable', 'email'],
        'payload.quantity' => ['integer', 'min:1', 'max:10'],
        'payload.active' => ['boolean'],
    ]);
});

it('creates rules for nested OpenAPI properties', function () {
    $schema = new OA\Schema(
        properties: [
            new OA\Property(
                property: 'card',
                properties: [
                    new OA\Property(property: 'number', type: 'string'),
                    new OA\Property(property: 'persist', type: 'boolean'),
                ],
                type: 'object',
            ),
        ],
        type: 'object',
    );

    $rules = new RuleFactory()->make($schema, [], 'bepaid');

    expect($rules)
        ->toHaveKeys([
            'bepaid.card',
            'bepaid.card.number',
            'bepaid.card.persist',
        ])
        ->and($rules['bepaid.card'])
        ->toContain('array')
        ->and($rules['bepaid.card.number'])
        ->toBe(['string'])
        ->and($rules['bepaid.card.persist'])
        ->toBe(['boolean']);
});

it('appends x-validation rules to inferred rules', function () {
    $schema = new OA\Schema(
        properties: [
            new OA\Property(
                property: 'card',
                type: 'object',
                nullable: true,
                x: [
                    'validation' => [
                        'required_without:payload.token',
                        'prohibits:payload.token',
                    ],
                ],
            ),
        ],
        type: 'object',
    );

    $rules = new RuleFactory()->make($schema, [], 'payload');

    expect($rules['payload.card'])->toBe([
        'nullable',
        'array',
        'required_without:payload.token',
        'prohibits:payload.token',
    ]);
});

it('validates exactly one oneOf branch within a prefixed object', function (array $payment, bool $passes) {
    $schema = new OA\Schema(
        properties: [
            new OA\Property(property: 'card', type: 'object'),
            new OA\Property(property: 'token', type: 'string'),
        ],
        type: 'object',
        oneOf: [
            new OA\Schema(required: ['card']),
            new OA\Schema(required: ['token']),
        ],
    );

    $rules = new RuleFactory()->make($schema, [], 'payment');
    $validator = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));

    expect($validator->make([], $rules)->passes())
        ->toBeTrue()
        ->and($validator->make(['payment' => $payment], $rules)->passes())
        ->toBe($passes);
})->with([
    'card' => [['card' => ['number' => 'encrypted']], true],
    'token' => [['token' => 'stored-card'], true],
    'neither' => [[], false],
    'both' => [['card' => ['number' => 'encrypted'], 'token' => 'stored-card'], false],
]);

it('requires a prefix for oneOf object validation', function () {
    $schema = new OA\Schema(
        oneOf: [new OA\Schema(required: ['card'])],
        type: 'object',
    );

    new RuleFactory()->make($schema, []);
})->throws(InvalidArgumentException::class);

it('requires each oneOf branch to declare required properties', function () {
    $schema = new OA\Schema(
        oneOf: [new OA\Schema],
        type: 'object',
    );

    new RuleFactory()->make($schema, [], 'payment');
})->throws(InvalidArgumentException::class);

it('preserves application rules on schema properties', function (string $prefix) {
    $schema = new OA\Schema(properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
    ]);
    $key = $prefix === '' ? 'email' : $prefix . '.email';
    $unique = Rule::unique('users', 'email');
    $callback = function (string $attribute, mixed $value, Closure $fail) {
        $fail('Rejected by the application.');
    };

    $rules = new RuleFactory()->make($schema, [$key => [$unique, $callback, 'email']], $prefix);
    $validator = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));

    expect($rules[$key])->toBe([$unique, $callback, 'email']);
    $rules[$key] = array_slice($rules[$key], 1);
    $data = $prefix === '' ? ['email' => 'user@example.com'] : ['payload' => ['email' => 'user@example.com']];
    expect($validator->make($data, $rules)->errors()->first($key))->toBe('Rejected by the application.');
})->with(['root' => '', 'prefixed' => 'payload']);

it('combines nested properties and allOf branches without losing application rules', function () {
    $schema = new OA\Schema(
        properties: [
            new OA\Property(
                property: 'customer',
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ],
                type: 'object',
            ),
            new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
        ],
        type: 'object',
        allOf: [
            new OA\Schema(properties: [new OA\Property(property: 'quantity', type: 'integer', maximum: 10)]),
            new OA\Schema(properties: [new OA\Property(property: 'token', type: 'string')]),
        ],
        oneOf: [new OA\Schema(required: ['customer']), new OA\Schema(required: ['token'])],
    );
    $rules = new RuleFactory()->make(
        $schema,
        [
            'payload' => ['required'],
            'payload.customer.email' => ['required'],
            'payload.quantity' => ['required', 'integer'],
            'unrelated' => ['boolean'],
        ],
        'payload',
    );
    $validator = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));

    expect($rules['payload.customer.email'])
        ->toBe(['required', 'email'])
        ->and($rules['payload.quantity'])
        ->toBe(['required', 'integer', 'min:1', 'max:10'])
        ->and($rules['payload.token'])
        ->toBe(['string'])
        ->and($rules['unrelated'])
        ->toBe(['boolean'])
        ->and($validator->make([], $rules)->fails())
        ->toBeTrue()
        ->and(
            $validator
                ->make([
                    'payload' => [
                        'customer' => ['email' => 'user@example.com'],
                        'quantity' => 5,
                    ],
                ], $rules)
                ->passes(),
        )
        ->toBeTrue()
        ->and(
            $validator
                ->make([
                    'payload' => [
                        'customer' => ['email' => 'user@example.com'],
                        'token' => 'stored',
                        'quantity' => 5,
                    ],
                ], $rules)
                ->errors()
                ->has('payload'),
        )
        ->toBeTrue();
});
