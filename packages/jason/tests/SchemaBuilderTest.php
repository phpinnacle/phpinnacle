<?php

use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

it('reads standard scalar keywords without inferring or normalizing their values', function () {
    $definition = [
        'type' => ['integer', 'null'],
        'title' => '',
        'description' => 'Quantity',
        'default' => null,
        'const' => false,
        'enum' => [null, false, 0],
        'required' => ['external'],
        'minimum' => 0,
        'maximum' => 10.5,
        'exclusiveMinimum' => -1,
        'exclusiveMaximum' => 11,
        'multipleOf' => 0.5,
        'minLength' => 0,
        'maxLength' => 8,
        'minItems' => 0,
        'maxItems' => 4,
        'minContains' => 0,
        'maxContains' => 3,
        'minProperties' => 0,
        'maxProperties' => 2,
        'uniqueItems' => false,
        'readOnly' => false,
        'writeOnly' => true,
        'deprecated' => false,
        'format' => 'custom-format',
        'pattern' => '^value$',
        'examples' => [null, false, 0],
        'dependentRequired' => ['card' => ['number']],
        'contentEncoding' => 'base64',
        'contentMediaType' => 'application/json',
        '$id' => 'urn:example:quantity',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        '$ref' => '#/$defs/quantity',
        '$anchor' => 'quantity',
        '$dynamicAnchor' => 'node',
        '$dynamicRef' => '#node',
        '$vocabulary' => ['urn:example:vocabulary' => false],
        '$comment' => 'Preserve this comment.',
    ];
    $schema = Schema::fromArray($definition);

    foreach ($definition as $keyword => $value) {
        $method = ltrim($keyword, '$');
        expect($schema->{$method}())->toBe($value);
    }
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

it('reads absent standard fields from object and boolean schemas', function (array|bool $definition) {
    $schema = Schema::fromArray($definition);

    expect($schema->type())
        ->toBeNull()
        ->and($schema->required())
        ->toBe([])
        ->and($schema->title())
        ->toBeNull()
        ->and($schema->enum())
        ->toBeNull()
        ->and($schema->minimum())
        ->toBeNull()
        ->and($schema->readOnly())
        ->toBeNull()
        ->and($schema->properties())
        ->toBe([])
        ->and($schema->items())
        ->toBeNull()
        ->and($schema->prefixItems())
        ->toBe([]);
})->with(['object' => [[]], 'true' => [true], 'false' => [false]]);

it('builds typed nested schemas and exposes their original structural positions', function () {
    $string = SchemaBuilder::make()->type('string')->format('email')->minLength(3)->build();
    $rejecting = Schema::fromArray(false);
    $builder = SchemaBuilder::make()
        ->type('object')
        ->title('Customer')
        ->description('Customer details')
        ->properties(['email' => $string])
        ->patternProperties(['^extra' => $string])
        ->dependentSchemas(['email' => Schema::object()])
        ->defs(['email' => $string])
        ->required(['email'])
        ->additionalProperties($rejecting)
        ->unevaluatedProperties($rejecting)
        ->propertyNames($string)
        ->anyOf([$string, $rejecting])
        ->oneOf([$string])
        ->allOf([Schema::object()])
        ->prefixItems([$string, $rejecting])
        ->items($string)
        ->contains($string)
        ->unevaluatedItems($rejecting)
        ->not($rejecting)
        ->if($string)
        ->then($rejecting)
        ->else($string)
        ->contentSchema($string);
    $schema = $builder->build();

    expect($schema->type())
        ->toBe('object')
        ->and($schema->title())
        ->toBe('Customer')
        ->and($schema->required())
        ->toBe(['email'])
        ->and($schema->properties()['email']->format())
        ->toBe('email')
        ->and($schema->patternProperties()['^extra']->minLength())
        ->toBe(3)
        ->and($schema->dependentSchemas()['email']->type())
        ->toBe('object')
        ->and($schema->defs()['email']->toArray())
        ->toBe($string->toArray())
        ->and($schema->branches('anyOf')[1]->deniesAny())
        ->toBeTrue()
        ->and($schema->branches('oneOf')[0]->type())
        ->toBe('string')
        ->and($schema->branches('allOf')[0]->type())
        ->toBe('object')
        ->and($schema->prefixItems()[1]->deniesAny())
        ->toBeTrue();

    foreach (['additionalProperties', 'unevaluatedProperties', 'unevaluatedItems', 'not', 'then'] as $method) {
        expect($schema->{$method}()->deniesAny())->toBeTrue();
    }

    foreach (['items', 'contains', 'propertyNames', 'if', 'else', 'contentSchema'] as $method) {
        expect($schema->{$method}()->toArray())->toBe($string->toArray());
    }
});

it('starts builders from the current schema definition without expanding references', function (array|bool $definition) {
    expect(Schema::fromArray($definition)->builder()->build()->toArray())->toBe($definition);
})->with([
    'object' => [[
        '$defs' => ['name' => ['type' => 'string']],
        'properties' => ['name' => ['$ref' => '#/$defs/name']],
        'x-metadata' => ['enabled' => false],
    ]],
    'true' => [true],
    'false' => [false],
]);

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

it('replaces an additional-property schema with a prohibition', function () {
    $schema = SchemaBuilder::make()
        ->type('object')
        ->additionalProperties(Schema::string())
        ->withoutAdditionalProperties()
        ->build();

    expect($schema->toArray())->toBe(['type' => 'object', 'additionalProperties' => false]);
});

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
    'enum constraint' => [
        ['type' => ['string', 'null'], 'enum' => ['active', null]],
        [
            'allOf' => [
                ['type' => ['string', 'null'], 'enum' => ['active', null]],
                ['not' => ['type' => 'null']],
            ],
        ],
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
