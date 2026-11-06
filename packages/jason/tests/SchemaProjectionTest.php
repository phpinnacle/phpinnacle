<?php

use PHPinnacle\Jason\Schema;

it('compares definitions strictly by ordered structure without requiring object identity', function () {
    $schema = Schema::fromJson('{"default":{"nested":{"value":1}},"title":"Value"}');

    expect($schema->equals(Schema::fromJson($schema->toJson()), strict: true))->toBeTrue();
    expect($schema->equals(Schema::fromJson('{"title":"Value","default":{"nested":{"value":1}}}'), true))->toBeFalse();
    expect($schema->equals(
        Schema::fromJson('{"default":{"nested":{"value":1.0}},"title":"Value"}'),
        true,
    ))->toBeFalse();
    expect(Schema::empty()->equals(Schema::fromArray(['default' => null]), true))->toBeFalse();
    expect(Schema::fromArray(['default' => (object) ['a' => 1, 'b' => 2]])->equals(
        Schema::fromArray(['default' => (object) ['b' => 2, 'a' => 1]]),
        true,
    ))->toBeFalse();
});

it('projects alternative fields with sibling constraints and additional property rules', function (string $keyword) {
    $schema = Schema::fromArray([
        'properties' => ['root' => ['type' => 'boolean'], 'shared' => ['maxLength' => 20]],
        'allOf' => [['properties' => ['shared' => ['minLength' => 2]]]],
        $keyword => [
            [
                'properties' => ['shared' => ['type' => 'string', 'title' => 'First'], 'left' => ['type' => 'integer']],
                'additionalProperties' => false,
            ],
            [
                'properties' => ['shared' => ['type' => 'string', 'title' => 'Second'], 'right' => ['type' => 'null']],
                'additionalProperties' => ['type' => 'number'],
            ],
        ],
    ]);
    $properties = $schema->effectiveProperties();

    expect(array_keys($properties))->toBe(['root', 'shared', 'left', 'right']);
    expect($properties['shared']->effectiveMinLength())->toBe(2);
    expect($properties['shared']->effectiveMaxLength())->toBe(20);
    expect($properties['shared']->annotation('title'))->toBe('First');
    expect($properties['left']->types())->toBe(['integer', 'number']);
    expect($properties['right']->singleType())->toBe('null');
    expect($properties['root']->types())->toBe([]);
})->with(['anyOf', 'oneOf']);

it('retains unrestricted alternatives and conjunctive additional property restrictions', function (string $keyword) {
    $schema = Schema::fromArray([
        $keyword => [
            ['properties' => ['value' => ['type' => 'string', 'title' => 'Value']]],
            [],
            false,
        ],
    ]);
    $value = $schema->effectiveProperties()['value'];

    expect($value->types())->toBe([]);
    expect($value->annotation('title'))->toBe('Value');
    expect(
        Schema::allOf([
            $schema,
            Schema::fromArray(['additionalProperties' => false]),
        ])->effectiveProperties()['value']->deniesAny(),
    )->toBeTrue();
})->with(['anyOf', 'oneOf']);

it('retains annotations alongside rejecting field constraints', function () {
    $schema = Schema::allOf([
        Schema::object(['value' => Schema::fromArray(false)]),
        Schema::object(['value' => Schema::fromArray(['title' => 'Value'])]),
    ]);
    $value = $schema->effectiveProperties()['value'];

    expect($value->annotation('title'))->toBe('Value');
    expect($value->branches('allOf')[0]->deniesAny())->toBeTrue();
});

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

it('compares numeric schema map keys as object members while retaining strict order', function () {
    $left = Schema::fromJson('{"properties":{"0":{},"1":{"type":"string"}},"dependentRequired":{"0":[],"1":[]}}');
    $right = Schema::fromJson('{"properties":{"1":{"type":"string"},"0":{}},"dependentRequired":{"1":[],"0":[]}}');

    expect($left->equals($right))->toBeTrue();
    expect($right->equals($left))->toBeTrue();
    expect($left->equals($right, strict: true))->toBeFalse();
    expect(Schema::fromJson('{"default":{}}')->equals(Schema::fromJson('{"default":[]}')))->toBeFalse();
});

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
    'unrestricted alternative' => [['anyOf' => [['type' => 'string', 'maxLength' => 10], true]], null],
    'false anyOf alternative' => [['anyOf' => [['type' => 'string', 'maxLength' => 10], false]], 10],
    'false oneOf alternative' => [['oneOf' => [['type' => 'string', 'maxLength' => 10], false]], 10],
    'impossible type alternative' => [
        [
            'anyOf' => [
                ['type' => 'string', 'maxLength' => 10],
                ['allOf' => [['type' => 'string'], ['type' => 'integer']]],
            ],
        ],
        10,
    ],
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

it('keeps type projections consistent with unrestricted and conjunctive alternatives', function (
    array $definition,
    array $expected,
) {
    expect(Schema::fromArray($definition)->types())->toBe($expected);
})->with([
    'unrestricted alternative' => [['anyOf' => [['type' => 'string'], true]], []],
    'sibling intersection' => [
        ['type' => 'string', 'anyOf' => [['type' => 'string'], ['type' => 'integer']]],
        ['string'],
    ],
    'independent alternative groups' => [
        [
            'anyOf' => [['type' => 'string'], ['type' => 'integer']],
            'oneOf' => [['type' => 'integer'], ['type' => 'null']],
        ],
        ['integer'],
    ],
    'rejected branch' => [['anyOf' => [false, ['type' => 'array']]], ['array']],
    'impossible intersection stays impossible' => [
        ['allOf' => [['type' => 'string'], ['type' => 'integer'], ['type' => 'boolean']]],
        [],
    ],
]);

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

it('intersects numeric and array bounds and retains conjunctive uniqueness', function () {
    $schema = Schema::fromArray([
        'minimum' => 0.5,
        'maximum' => 20.5,
        'minItems' => 1,
        'maxItems' => 20,
        'uniqueItems' => false,
        'allOf' => [['minimum' => 2.5, 'maximum' => 10.5, 'minItems' => 2, 'maxItems' => 10, 'uniqueItems' => true]],
    ]);

    expect($schema->effectiveMinimum())->toBe(2.5);
    expect($schema->effectiveMaximum())->toBe(10.5);
    expect($schema->effectiveMinItems())->toBe(2);
    expect($schema->effectiveMaxItems())->toBe(10);
    expect($schema->effectiveUniqueItems())->toBeTrue();
    expect(Schema::empty()->effectiveMinimum())->toBeNull();
    expect(Schema::empty()->effectiveMaxItems())->toBeNull();
    expect(Schema::empty()->effectiveUniqueItems())->toBeFalse();
});
