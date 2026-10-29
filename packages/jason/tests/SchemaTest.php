<?php

use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

it('preserves complete definitions without normalizing constraints or annotations', function () {
    $definition = [
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        '$defs' => ['name' => ['type' => 'string', 'default' => null]],
        'properties' => ['name' => ['$ref' => '#/$defs/name'], 'disabled' => false],
        'required' => ['other'],
        'oneOf' => [['required' => ['name']], ['required' => ['other']]],
        'unknown-keyword' => ['properties' => ['metadata' => 'unchanged']],
        'x-null' => null,
        'x-false' => false,
        'x-zero' => 0,
    ];
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
});

it('keeps built schemas immutable and required names on the containing object', function () {
    $name = SchemaBuilder::make()->type('string')->min(1)->default(null)->build();
    $titled = SchemaBuilder::make($name)->title('Name')->build();
    $schema = SchemaBuilder::make(Schema::object([
        'name' => $titled,
        'count' => SchemaBuilder::make()->type('integer')->min(0)->build(),
    ]))
        ->required(['name', 'undeclared'])
        ->withoutAdditionalProperties()
        ->build();
    expect($name->hasKeyword('title'))
        ->toBeFalse()
        ->and($schema->keyword('required'))
        ->toBe(['name', 'undeclared'])
        ->and($schema->properties()['name']->hasKeyword('default'))
        ->toBeTrue()
        ->and($schema->properties()['name']->keyword('default'))
        ->toBeNull();
});

it('takes immutable snapshots of nested JSON objects at input and output boundaries', function () {
    $default = (object) ['nested' => (object) ['value' => 'original']];
    $schema = SchemaBuilder::make(Schema::object())->default($default)->build();
    $default->nested->value = 'input changed';
    $output = $schema->toArray();
    $output['default']->nested->value = 'output changed';
    $schema->keyword('default')->nested->value = 'keyword output changed';
    $schema->keywords()['default']->nested->value = 'keywords output changed';
    $schema->jsonSerialize()->default->nested->value = 'serialized output changed';
    expect($schema->keyword('default')->nested->value)->toBe('original');
});

it('reads absent keywords from boolean schemas', function (bool $definition) {
    $schema = Schema::fromArray($definition);

    expect($schema->keyword('type'))->toBeNull()->and($schema->hasKeyword('type'))->toBeFalse();
})->with([true, false]);

it('serializes schema objects and arrays without losing their JSON identity', function (string $json) {
    expect(Schema::fromJson($json)->toJson())->toBe($json);
})->with([
    '{}',
    'true',
    'false',
    '{"properties":{},"$defs":{},"required":[],"default":null,"x-empty":[],"x-object":{}}',
    '{"type":"array","items":false,"prefixItems":[{},true,false],"enum":[{},[],null,0,false]}',
    '{"properties":{"0":{},"1":{"type":"string"}},"additionalProperties":true}',
]);

it('retains recursive references in the original document', function () {
    $schema = Schema::fromArray(['$defs' => ['node' => ['properties' => ['next' => ['$ref' => '#/$defs/node']]]]]);
    expect($schema->toArray()['$defs']['node']['properties']['next'])->toBe(['$ref' => '#/$defs/node']);
    expect($schema->resolveReferences(...))->toThrow(InvalidArgumentException::class, 'recursive');
});

it('resolves percent-encoded JSON Pointer fragments', function () {
    $schema = Schema::fromArray([
        '$defs' => ['a b' => ['type' => 'string']],
        'properties' => ['value' => ['$ref' => '#%2F%24defs%2Fa%20b']],
    ]);
    expect($schema->resolveReferences()->properties()['value']->toArray())->toBe(['type' => 'string']);
});

it('expands local pointers in a separate view and preserves reference siblings as constraints', function () {
    $definition = [
        '$defs' => ['a/b~c' => ['type' => 'string', 'maxLength' => 5]],
        'properties' => ['value' => ['$ref' => '#/$defs/a~1b~0c', 'maxLength' => 10]],
    ];
    $schema = Schema::fromArray($definition);
    expect($schema->resolveReferences()->properties()['value']->toArray())
        ->toBe([
            'allOf' => [['type' => 'string', 'maxLength' => 5], ['maxLength' => 10]],
        ])
        ->and($schema->toArray())
        ->toBe($definition);
});

it('rejects unresolved or external references when expansion is requested', function (string $reference) {
    $schema = Schema::fromArray(['$ref' => $reference]);
    expect($schema->resolveReferences(...))->toThrow(InvalidArgumentException::class);
    expect($schema->keyword('$ref'))->toBe($reference);
})->with(['#/missing', 'https://example.org/schema', '#anchor', '%23%2Fvalue', '#/invalid~2']);

it('does not resolve embedded resource references against the wrong document root', function () {
    $definition = [
        '$defs' => ['value' => ['type' => 'string']],
        'properties' => [
            'nested' => [
                '$id' => 'nested.json',
                '$defs' => ['value' => ['type' => 'integer']],
                'properties' => ['value' => ['$ref' => '#/$defs/value']],
            ],
        ],
    ];
    $schema = Schema::fromArray($definition);
    expect($schema->resolveReferences(...))
        ->toThrow(InvalidArgumentException::class, 'embedded')
        ->and($schema->toArray())
        ->toBe($definition);
});

it('does not reinterpret referenced JSON arrays as schema objects', function () {
    $schema = Schema::fromArray(['default' => [], 'properties' => ['value' => ['$ref' => '#/default']]]);
    expect($schema->resolveReferences(...))->toThrow(InvalidArgumentException::class, 'target');
});

it('can resolve a schema object stored outside a schema keyword', function () {
    $schema = Schema::fromArray([
        'x-schema' => (object) ['type' => 'string'],
        'properties' => ['value' => ['$ref' => '#/x-schema']],
    ]);
    expect($schema->resolveReferences()->properties()['value']->toArray())->toBe(['type' => 'string']);
});

it('keeps nullability outside enum and composition constraints', function () {
    $schema = SchemaBuilder::make()->type('string')->enum(['active'])->build();
    expect(SchemaBuilder::make($schema)->nullable()->build()->toArray())
        ->toBe(['anyOf' => [$schema->toArray(), ['type' => 'null']]])
        ->and($schema->toArray())
        ->toBe(['type' => 'string', 'enum' => ['active']])
        ->and(SchemaBuilder::make()->type('integer')->nullable()->build()->toArray())
        ->toBe(['type' => ['integer', 'null']]);
});

it('composes nested schema builders without special serializer subclasses', function (string $keyword) {
    $branch = Schema::fromArray(['type' => 'string', 'readOnly' => true, 'x-metadata' => ['enabled' => true]]);
    $composition = SchemaBuilder::make(Schema::{$keyword}([
        $branch,
        SchemaBuilder::make()->type('string')->min(3)->build(),
    ]))
        ->title('Value')
        ->build();
    $schema = SchemaBuilder::make(Schema::object([
        'value' => $composition,
        'items' => SchemaBuilder::make()->type('array')->items($composition)->build(),
    ]))
        ->required(['value', 'external'])
        ->build();
    expect($schema->properties()['value']->toArray())
        ->toBe($composition->toArray())
        ->and($schema->properties()['items']->items()->toArray())
        ->toBe($composition->toArray())
        ->and($composition->branches($keyword)[0]->toArray())
        ->toBe($branch->toArray())
        ->and(Schema::fromArray($schema->toArray())->toArray())
        ->toBe($schema->toArray());
})->with(['allOf', 'anyOf', 'oneOf']);

it('keeps nullability outside conditional and reference constraints', function (array $definition, array $resolved) {
    expect(SchemaBuilder::make(Schema::fromArray($definition))->nullable()->build()->toArray())
        ->toBe(['anyOf' => [$resolved, ['type' => 'null']]]);
})->with([
    'conditional' => [
        ['type' => 'string', 'if' => ['type' => 'null'], 'then' => false],
        ['type' => 'string', 'if' => ['type' => 'null'], 'then' => false],
    ],
    'local reference' => [
        ['type' => 'string', '$defs' => ['value' => ['enum' => ['active']]], '$ref' => '#/$defs/value'],
        [
            'allOf' => [
                ['enum' => ['active']],
                ['type' => 'string', '$defs' => ['value' => ['enum' => ['active']]]],
            ],
        ],
    ],
]);

it('retains nested references and annotations without inferring undeclared types', function () {
    $definition = [
        '$defs' => ['contact' => ['type' => ['string', 'null'], 'x-tag' => 'example', 'default' => null]],
        'properties' => [
            'contact' => ['$ref' => '#/$defs/contact', 'x-origin' => 'sample'],
            'value' => ['oneOf' => [['required' => ['card']], ['required' => ['token']]], 'readOnly' => false],
        ],
        'allOf' => [['minimum' => 1]],
        'anyOf' => [['type' => 'integer'], ['type' => 'string']],
        'x-metadata' => ['properties' => ['metadata' => 'opaque']],
    ];
    expect(Schema::fromArray($definition)->toArray())->toBe($definition);
    $view = Schema::fromArray($definition)->resolveReferences()->properties()['contact']->toArray();
    expect($view)->toBe(['allOf' => [$definition['$defs']['contact'], ['x-origin' => 'sample']]]);
});

it('validates JSON input at the parsing boundary', function (string $json) {
    expect(fn () => Schema::fromJson($json))->toThrow(InvalidArgumentException::class);
})->with(['[]', '1', '"string"', 'null', '{"type":"unsupported"}', '{"type":[]}', '{"allOf":42}', '{"properties":[]}']);
