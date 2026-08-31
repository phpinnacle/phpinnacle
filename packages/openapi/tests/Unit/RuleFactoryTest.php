<?php

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
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
