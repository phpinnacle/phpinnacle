<?php

use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

it('distinguishes the empty object definition from boolean schemas and null values', function () {
    expect(Schema::empty()->toJson())->toBe('{}');
    expect(Schema::empty()->isEmpty())->toBeTrue();
    expect(Schema::fromArray(true)->isEmpty())->toBeFalse();
    expect(Schema::fromArray(false)->isEmpty())->toBeFalse();
    expect(Schema::null()->isEmpty())->toBeFalse();
    expect(Schema::fromArray(['allOf' => []])->isEmpty())->toBeFalse();
});

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
    $name = SchemaBuilder::make()->type('string')->default(null)->build();
    $titled = SchemaBuilder::make($name)->title('Name')->build();
    $schema = SchemaBuilder::make(Schema::object([
        'name' => $titled,
    ]))
        ->required(['name', 'undeclared'])
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

it('protects mutable annotations while sharing children through reads and rebuilds', function () {
    $input = ['type' => 'string', 'default' => (object) ['nested' => (object) ['value' => 'original']]];
    $child = Schema::fromArray($input);
    $parent = Schema::object(['child' => $child]);
    $builder = $parent->builder();
    $snapshot = $builder->title('First')->build();
    $rebuilt = $builder->title('Second')->build();
    $composition = Schema::anyOf([$parent]);
    $array = SchemaBuilder::make()->items($parent)->build();
    $input['default']->nested->value = 'input changed';
    $snapshot->properties()['child']->default()->nested->value = 'child changed';
    $composition->branches('anyOf')[0]->properties()['child']->annotation('default')->nested->value = 'branch changed';
    $array->items()->properties()['child']->jsonSerialize()->default->nested->value = 'item changed';
    $exported = $rebuilt->toArray();
    $exported['properties']['child']['default']->nested->value = 'export changed';
    $visited = [];
    $parent->walk(function (Schema $fragment) use (&$visited) {
        $visited[] = $fragment;

        if ($fragment->hasKeyword('default')) {
            $fragment->keywords()['default']->nested->value = 'walk changed';
        }
    });

    expect($child->default()->nested->value)->toBe('original');
    expect($parent->properties()['child']->default()->nested->value)->toBe('original');
    expect($snapshot->properties()['child']->default()->nested->value)->toBe('original');
    expect($rebuilt->properties()['child']->default()->nested->value)->toBe('original');
    expect($composition->branches('anyOf')[0]->properties()['child']->default()->nested->value)->toBe('original');
    expect($array->items()->properties()['child']->default()->nested->value)->toBe('original');
    expect($visited[1]->default()->nested->value)->toBe('original');
    expect($snapshot->title())->toBe('First');
    expect($parent->title())->toBeNull();
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

it('round trips non-schema object keywords through arrays builders and JSON', function (
    string $keyword,
    string $method,
    array $value,
    string $json,
) {
    $schema = SchemaBuilder::empty()->{$method}($value)->build();

    expect($schema->toJson())->toBe($json);
    expect(Schema::fromArray([$keyword => $value])->toJson())->toBe($json);
    expect(Schema::fromJson($json)->{$method}())->toBe($value);
})->with([
    'empty dependencies' => ['dependentRequired', 'dependentRequired', [], '{"dependentRequired":{}}'],
    'named dependency' => [
        'dependentRequired',
        'dependentRequired',
        ['card' => ['number']],
        '{"dependentRequired":{"card":["number"]}}',
    ],
    'numeric dependency name' => [
        'dependentRequired',
        'dependentRequired',
        ['0' => ['name']],
        '{"dependentRequired":{"0":["name"]}}',
    ],
    'empty vocabulary' => ['$vocabulary', 'vocabulary', [], '{"$vocabulary":{}}'],
    'optional vocabulary' => [
        '$vocabulary',
        'vocabulary',
        ['urn:example:vocabulary' => false],
        '{"$vocabulary":{"urn:example:vocabulary":false}}',
    ],
]);

it('retains recursive references in the original document', function () {
    $schema = Schema::fromArray(['$defs' => ['node' => ['properties' => ['next' => ['$ref' => '#/$defs/node']]]]]);
    expect($schema->toArray()['$defs']['node']['properties']['next'])->toBe(['$ref' => '#/$defs/node']);
    expect($schema->resolveReferences(...))->toThrow(InvalidArgumentException::class, 'recursive');
});

it('allows 20000 expanded schema fragments and rejects the next fragment', function () {
    $definition = ['prefixItems' => array_fill(0, count: 19_999, value: true)];
    $schema = Schema::fromArray($definition);

    expect($schema->resolveReferences()->toArray())->toBe($definition);
    expect(Schema::fromArray(['prefixItems' => array_fill(0, count: 20_000, value: true)])->resolveReferences(...))
        ->toThrow(InvalidArgumentException::class, 'The JSON Schema expands beyond 20000 fragments.');
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
            'maxLength' => 10,
            'allOf' => [['type' => 'string', 'maxLength' => 5]],
        ])
        ->and($schema->toArray())
        ->toBe($definition);
});

it('rejects unresolved or external references when expansion is requested', function (string $reference) {
    $schema = Schema::fromArray(['$ref' => $reference]);
    expect($schema->resolveReferences(...))->toThrow(InvalidArgumentException::class);
    expect($schema->keyword('$ref'))->toBe($reference);
})->with(['#/missing', 'https://example.org/schema', '#anchor', '%23%2Fvalue', '#/invalid~2']);

it('keeps unevaluated constraints adjacent to expanded references and existing applicators', function (
    string $keyword,
    array $referenced,
    array $existing,
) {
    $schema = Schema::fromArray([
        '$defs' => ['base' => $referenced],
        '$ref' => '#/$defs/base',
        $keyword => false,
        'allOf' => [$existing],
    ]);
    $view = $schema->resolveReferences();

    expect($view->keyword($keyword))->toBeFalse();
    expect(array_map(fn (Schema $branch) => $branch->toArray(), $view->branches('allOf')))
        ->toBe([$existing, $referenced]);
    expect($schema->ref())->toBe('#/$defs/base');
})->with([
    'object properties' => [
        'unevaluatedProperties',
        ['properties' => ['name' => ['type' => 'string']]],
        ['properties' => ['age' => ['type' => 'integer']]],
    ],
    'array items' => [
        'unevaluatedItems',
        ['prefixItems' => [['type' => 'string']]],
        ['contains' => ['type' => 'integer']],
    ],
]);

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

it('rejects referenced descendants that use an embedded resource base', function (string $reference) {
    $schema = Schema::fromArray([
        '$ref' => $reference,
        '$defs' => [
            'name' => ['type' => 'string'],
            'alias' => ['$ref' => '#/$defs/resource/$defs/value'],
            'resource' => [
                '$id' => 'nested.json',
                '$defs' => [
                    'name' => ['type' => 'integer'],
                    'value' => ['$ref' => '#/$defs/name'],
                ],
            ],
        ],
    ]);

    expect($schema->resolveReferences(...))->toThrow(InvalidArgumentException::class, 'embedded');
})->with(['direct pointer' => ['#/$defs/resource/$defs/value'], 'alias pointer' => ['#/$defs/alias']]);

it('resolves reference-free descendants without interpreting opaque ids as resource boundaries', function () {
    $resource = Schema::fromArray([
        '$ref' => '#/$defs/resource/$defs/name',
        '$defs' => ['resource' => ['$id' => 'nested.json', '$defs' => ['name' => ['type' => 'integer']]]],
    ]);
    $metadata = Schema::fromArray([
        '$id' => 'https://example.test/root',
        '$ref' => '#/x-container/value',
        '$defs' => ['name' => ['type' => 'string']],
        'x-container' => (object) ['$id' => 'opaque', 'value' => (object) ['$ref' => '#/$defs/name']],
    ]);

    expect($resource->resolveReferences()->types())->toBe(['integer']);
    expect($metadata->resolveReferences()->types())->toBe(['string']);
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

it('retains definitions used by dynamic references after expanding a root reference', function () {
    $schema = Schema::fromArray([
        '$defs' => ['value' => ['type' => 'string'], 'alias' => ['$dynamicRef' => '#/$defs/value']],
        '$ref' => '#/$defs/alias',
    ]);
    $view = $schema->resolveReferences();

    expect($view->defs()['value']->type())->toBe('string');
    expect($view->branches('allOf')[0]->dynamicRef())->toBe('#/$defs/value');
    expect($schema->ref())->toBe('#/$defs/alias');
});

it('retains existing allOf pointer targets when expanding reference siblings', function () {
    $schema = Schema::fromArray([
        '$defs' => ['alias' => ['$dynamicRef' => '#/allOf/0']],
        '$ref' => '#/$defs/alias',
        'allOf' => [['type' => 'string']],
    ])->resolveReferences();

    expect($schema->branches('allOf')[0]->type())->toBe('string');
    expect($schema->branches('allOf')[1]->dynamicRef())->toBe('#/allOf/0');
});

it('wraps dynamic reference constraints and relocates their local pointer targets', function (bool $nullable) {
    $schema = Schema::fromArray([
        'type' => 'string',
        '$defs' => ['value' => ['type' => 'string']],
        '$dynamicRef' => '#%2F$defs/value',
    ]);
    $keyword = $nullable ? 'anyOf' : 'allOf';
    $result = $schema->builder()->nullable($nullable)->build();
    $branch = $result->branches($keyword)[0];

    expect($branch->type())->toBe('string');
    expect($branch->dynamicRef())->toBe('#/' . $keyword . '/0%2F$defs/value');
    expect($branch->defs()['value']->type())->toBe('string');
    expect($result->branches($keyword)[1]->toArray())
        ->toBe($nullable ? ['type' => 'null'] : ['not' => ['type' => 'null']]);
    expect($schema->dynamicRef())->toBe('#%2F$defs/value');
})->with([true, false]);

it('relocates nested dynamic pointers without changing anchors or embedded resource references', function () {
    $schema = Schema::fromArray([
        '$dynamicAnchor' => 'node',
        'properties' => [
            'root' => ['$dynamicRef' => '#'],
            'anchor' => ['$dynamicRef' => '#node'],
            'external' => ['$dynamicRef' => 'external.json#/$defs/value'],
            'resource' => [
                '$id' => 'nested.json',
                '$defs' => ['value' => ['type' => 'string']],
                '$dynamicRef' => '#/$defs/value',
            ],
        ],
    ]);
    $properties = $schema->builder()->nullable()->build()->branches('anyOf')[0]->properties();

    expect($properties['root']->dynamicRef())->toBe('#/anyOf/0');
    expect($properties['anchor']->dynamicRef())->toBe('#node');
    expect($properties['external']->dynamicRef())->toBe('external.json#/$defs/value');
    expect($properties['resource']->dynamicRef())->toBe('#/$defs/value');
    expect(
        $schema
            ->builder()
            ->id('https://example.test/schema')
            ->nullable()
            ->build()
            ->branches('anyOf')[0]->properties()['root']->dynamicRef(),
    )
        ->toBe('#');
});

it('preserves additionalItems as an opaque annotation during parsing serialization and expansion', function (string $json) {
    $schema = Schema::fromJson($json);
    $visited = [];
    $schema->walk(function (Schema $fragment) use (&$visited) {
        $visited[] = $fragment;
    });

    expect($schema->toJson())->toBe($json);
    expect($schema->resolveReferences()->toJson())->toBe($json);
    expect($visited)->toHaveCount(1);
})->with(['{"additionalItems":"annotation"}', '{"additionalItems":{"$ref":"external.json"}}']);

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
            'type' => 'string',
            '$defs' => ['value' => ['enum' => ['active']]],
            'allOf' => [['enum' => ['active']]],
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
    expect($view)->toBe(['x-origin' => 'sample', 'allOf' => [$definition['$defs']['contact']]]);
});

it('validates JSON input at the parsing boundary', function (string $json) {
    expect(fn () => Schema::fromJson($json))->toThrow(InvalidArgumentException::class);
})->with([
    '[]',
    '1',
    '"string"',
    'null',
    '{"type":"unsupported"}',
    '{"type":[]}',
    '{"allOf":42}',
    '{"properties":[]}',
    '{"dependentRequired":[]}',
    '{"$vocabulary":[]}',
]);
