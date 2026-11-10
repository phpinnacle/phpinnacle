<?php

use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

it('defaults boolean flags to true and allows explicitly disabling them', function (string $method) {
    $builder = SchemaBuilder::empty()->{$method}();
    $enabled = $builder->build();
    $disabled = $builder->{$method}(false)->build();

    expect($enabled->{$method}())->toBeTrue();
    expect($disabled->{$method}())->toBeFalse();
})->with(['readOnly', 'writeOnly', 'deprecated', 'uniqueItems']);

it('provides fluent shortcuts for string formats', function (string $method, string $format) {
    $schema = SchemaBuilder::string()->{$method}()->title('Value')->build();

    expect($schema->toArray())->toBe(['type' => 'string', 'format' => $format, 'title' => 'Value']);
})->with([
    'uuid' => ['uuid', 'uuid'],
    'date' => ['date', 'date'],
    'dateTime' => ['dateTime', 'date-time'],
    'time' => ['time', 'time'],
]);

it('composes object and union factories with typed children and further configuration', function () {
    $schema = SchemaBuilder::object([
        'name' => SchemaBuilder::string()->min(2)->max(20)->build(),
        'values' => SchemaBuilder::array()
            ->items(SchemaBuilder::union(['integer', 'null'])->default(null)->build())
            ->build(),
    ])
        ->required(['name'])
        ->withoutAdditionalProperties()
        ->build();

    expect($schema->toArray())->toBe([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 20],
            'values' => ['type' => 'array', 'items' => ['type' => ['integer', 'null'], 'default' => null]],
        ],
        'required' => ['name'],
        'additionalProperties' => false,
    ]);
});

it('removes keywords without assigning null or changing earlier snapshots', function () {
    $source = Schema::fromArray(['type' => 'string', 'enum' => ['a'], 'default' => null]);
    $builder = $source->builder()->without('enum', 'missing');
    $first = $builder->build();
    $second = $builder->without('default')->build();

    expect($first->toArray())->toBe(['type' => 'string', 'default' => null]);
    expect($second->toArray())->toBe(['type' => 'string']);
    expect($source->enum())->toBe(['a']);
    expect(Schema::fromArray(true)->builder()->without('missing')->build()->allowsAny())->toBeTrue();
});

it('identifies literal boolean schemas separately from primitive types and empty objects', function (
    array|bool $definition,
    bool $allows,
    bool $denies,
) {
    $schema = Schema::fromArray($definition);

    expect($schema->allowsAny())->toBe($allows)->and($schema->deniesAny())->toBe($denies);
})->with([
    'true schema' => [true, true, false],
    'false schema' => [false, false, true],
    'boolean type' => [['type' => 'boolean'], false, false],
    'empty object' => [[], false, false],
]);

it('converts typed children at map list and single-schema builder boundaries', function () {
    $child = Schema::fromArray(['type' => 'string', 'default' => null]);
    $schema = SchemaBuilder::empty()
        ->properties(['value' => $child])
        ->prefixItems([$child, Schema::fromArray(false)])
        ->items($child)
        ->build();

    expect($schema->toArray())->toBe([
        'properties' => ['value' => ['type' => 'string', 'default' => null]],
        'prefixItems' => [['type' => 'string', 'default' => null], false],
        'items' => ['type' => 'string', 'default' => null],
    ]);
});

it('snapshots builder inputs and outputs without changing the source schema', function () {
    $source = Schema::string();
    $default = (object) ['nested' => (object) ['value' => 'original']];
    $builder = $source->builder()->title('First')->default($default)->enum([$default])->examples([$default]);
    $default->nested->value = 'input changed';
    $first = $builder->build();
    $second = $builder->title('Second')->build();
    $first->default()->nested->value = 'output changed';
    $first->enum()[0]->nested->value = 'enum output changed';
    $first->examples()[0]->nested->value = 'example output changed';

    expect($source->title())
        ->toBeNull()
        ->and($first->title())
        ->toBe('First')
        ->and($second->title())
        ->toBe('Second')
        ->and($first->default()->nested->value)
        ->toBe('original')
        ->and($first->enum()[0]->nested->value)
        ->toBe('original')
        ->and($first->examples()[0]->nested->value)
        ->toBe('original');
});

it('copies raw schema fragments at generic builder input boundaries', function () {
    $input = ['default' => (object) ['value' => 'original']];
    $builder = SchemaBuilder::make()->with('properties', ['field' => $input])->with('items', $input);
    $schema = $builder->build();
    $input['default']->value = 'changed';
    $builder->without('properties', 'items');

    expect($schema->properties()['field']->default()->value)->toBe('original');
    expect($schema->items()->default()->value)->toBe('original');
});

it('preserves boolean schema keywords errors at the builder boundary', function (bool $definition) {
    expect(fn () => SchemaBuilder::make(Schema::fromArray($definition))->title('Unsupported'))
        ->toThrow(InvalidArgumentException::class, 'Boolean schemas cannot have keywords.');
})->with([true, false]);

it('writes type-specific lower and upper bounds', function (
    string $type,
    int|float $minimum,
    int|float $maximum,
    array $expected,
) {
    $schema = SchemaBuilder::make()->type($type)->min($minimum)->max($maximum)->build();

    expect($schema->toArray())->toBe($expected);
})->with([
    'string' => ['string', 0, 8, ['type' => 'string', 'minLength' => 0, 'maxLength' => 8]],
    'array' => ['array', 0, 4, ['type' => 'array', 'minItems' => 0, 'maxItems' => 4]],
    'integer' => ['integer', -1, 5, ['type' => 'integer', 'minimum' => -1, 'maximum' => 5]],
    'number' => ['number', -0.5, 1.5, ['type' => 'number', 'minimum' => -0.5, 'maximum' => 1.5]],
]);

it('excludes null from primitive and constrained schemas', function (array $definition, array|bool $expected) {
    $schema = Schema::fromArray($definition);
    $result = $schema->builder()->nullable(false)->build();

    expect($result->toArray())->toBe($expected);
    expect($schema->toArray())->toBe($definition);
})->with([
    'single remaining type' => [
        ['type' => ['string', 'null']],
        ['type' => 'string'],
    ],
    'multiple remaining types' => [
        ['type' => ['string', 'integer', 'null']],
        ['type' => ['string', 'integer']],
    ],
    'null only' => [
        ['type' => 'null'],
        false,
    ],
    'untyped enum' => [
        ['enum' => [0, false, null, '']],
        ['enum' => [0, false, '']],
    ],
    'null enum only' => [
        ['enum' => [null]],
        false,
    ],
    'enum constraint' => [
        ['type' => ['string', 'null'], 'enum' => ['active', null]],
        ['type' => 'string', 'enum' => ['active']],
    ],
]);

it('adds null directly to simple schemas without losing annotations', function (
    array $definition,
    array $expected,
) {
    $schema = Schema::fromArray($definition);

    $nullable = $schema->builder()->nullable()->build();

    expect($nullable->toArray())->toBe($expected);
    expect($nullable->builder()->nullable()->build()->toArray())->toBe($expected);
    expect($schema->toArray())->toBe($definition);
})->with([
    'primitive type' => [
        ['type' => 'integer'],
        ['type' => ['integer', 'null']],
    ],
    'string enum' => [
        ['type' => 'string', 'enum' => ['PL', 'DE'], 'title' => 'Country', 'default' => null],
        ['type' => ['string', 'null'], 'enum' => ['PL', 'DE', null], 'title' => 'Country', 'default' => null],
    ],
    'integer enum' => [
        ['type' => 'integer', 'enum' => [0, 1]],
        ['type' => ['integer', 'null'], 'enum' => [0, 1, null]],
    ],
    'untyped enum' => [
        ['enum' => [0, false, '']],
        ['enum' => [0, false, '', null]],
    ],
    'existing null' => [
        ['type' => ['string', 'null'], 'enum' => ['PL', null, 'DE']],
        ['type' => ['string', 'null'], 'enum' => ['PL', null, 'DE']],
    ],
]);

it('allows null outside existing composition constraints', function (string $keyword) {
    $schema = Schema::fromArray([$keyword => [['type' => 'string'], ['type' => 'integer']]]);

    expect($schema->builder()->nullable()->build()->toArray())->toBe([
        'anyOf' => [
            [$keyword => [['type' => 'string'], ['type' => 'integer']]],
            ['type' => 'null'],
        ],
    ]);
})->with(['anyOf', 'oneOf', 'allOf']);

it('allows null outside a constant constraint', function () {
    $schema = Schema::fromArray(['type' => 'string', 'const' => 'PL']);

    expect($schema->builder()->nullable()->build()->toArray())->toBe([
        'anyOf' => [
            ['type' => 'string', 'const' => 'PL'],
            ['type' => 'null'],
        ],
    ]);
});
