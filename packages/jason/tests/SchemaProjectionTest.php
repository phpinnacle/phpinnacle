<?php

use PHPinnacle\Jason\Schema;

it('compares schema definitions using JSON value equality', function (array $left, array $right, bool $equal) {
    $source = Schema::fromArray($left);
    $other = Schema::fromArray($right);

    expect($source->equals($other))->toBe($equal)->and($other->equals($source))->toBe($equal);
})->with([
    'nested object key order' => [
        ['type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 36]],
        ['items' => ['maxLength' => 36, 'type' => 'string'], 'type' => 'array'],
        true,
    ],
    'changed constraint' => [['type' => 'string', 'maxLength' => 36], ['type' => 'string', 'maxLength' => 35], false],
    'JSON scalar types' => [['enum' => [1]], ['enum' => ['1']], false],
    'array element order' => [['default' => [1, 2]], ['default' => [2, 1]], false],
    'JSON number equality' => [['enum' => [1]], ['enum' => [1.0]], true],
]);

it('resolves maximum string lengths across composed output schemas', function (array $definition, ?int $maximum) {
    expect(Schema::fromArray($definition)->effectiveMaxLength())->toBe($maximum);
})->with([
    'unbounded' => [['type' => 'string'], null],
    'bounded' => [['type' => 'string', 'maxLength' => 10], 10],
    'intersection' => [['allOf' => [['type' => 'string', 'maxLength' => 20], ['maxLength' => 10]]], 10],
    'nullable alternatives' => [['anyOf' => [['type' => 'string', 'maxLength' => 10], ['type' => 'null']]], 10],
    'bounded alternatives' => [
        ['oneOf' => [['type' => 'string', 'maxLength' => 10], ['type' => 'string', 'maxLength' => 20]]],
        20,
    ],
    'unbounded alternative' => [['anyOf' => [['type' => 'string', 'maxLength' => 10], ['type' => 'string']]], null],
]);

it('preserves number and null alternatives in numeric intersections', function () {
    expect(Schema::fromArray(['allOf' => [['type' => 'number'], ['type' => 'number']]])->types())
        ->toBe(['number'])
        ->and(
            Schema::fromArray([
                'allOf' => [['type' => ['number', 'null']], ['type' => ['integer', 'null']]],
            ])->types(),
        )
        ->toEqualCanonicalizing(['integer', 'null']);
});

it('projects referenced compositions without changing structural reads or the original definition', function () {
    $definition = [
        '$defs' => ['code' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 20, 'enum' => ['a', 'bb']]],
        'allOf' => [
            ['properties' => ['code' => ['$ref' => '#/$defs/code']], 'required' => ['code']],
            ['properties' => ['code' => ['maxLength' => 10, 'enum' => ['bb']]]],
        ],
    ];
    $schema = Schema::fromArray($definition);
    $view = $schema->resolveReferences();
    $code = $view->effectiveProperties()['code'];

    expect($schema->toArray())->toBe($definition);
    expect($view->properties())->toBe([]);
    expect($view->required())->toBe([]);
    expect($view->effectiveRequired())->toBe(['code']);
    expect($code->type())->toBeNull();
    expect($code->types())->toBe(['string']);
    expect($code->maxLength())->toBeNull();
    expect($code->effectiveMinLength())->toBe(2);
    expect($code->effectiveMaxLength())->toBe(10);
    expect($code->enumValues())->toBe(['bb']);
    expect($code->allowsEnumValue('a'))->toBeFalse();
    expect($code->allowsEnumValue('bb'))->toBeTrue();
});

it('combines item restrictions while preserving the declared array definition', function () {
    $schema = Schema::allOf([
        Schema::fromArray(['type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 20]]),
        Schema::fromArray(['items' => ['minLength' => 2, 'maxLength' => 10]]),
    ]);

    expect($schema->items())->toBeNull();
    expect($schema->arrayItems()->singleType())->toBe('string');
    expect($schema->arrayItems()->effectiveMinLength())->toBe(2);
    expect($schema->arrayItems()->effectiveMaxLength())->toBe(10);
});
