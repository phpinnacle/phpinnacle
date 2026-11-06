<?php

use PHPinnacle\Jason\JsonPath;
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

it('selects property and item schemas while distinguishing null values from impossible paths', function (
    array $path,
    array|bool|null $expected,
) {
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'null' => ['type' => 'null'],
            'free' => true,
            'empty' => [],
            'blocked' => false,
            '0' => ['type' => 'boolean'],
            'rows' => [
                'type' => 'array',
                'prefixItems' => [['type' => 'string'], ['type' => 'integer']],
                'items' => ['type' => 'null'],
                'maxItems' => 4,
            ],
            'closed' => ['type' => 'object', 'additionalProperties' => false],
        ],
        'additionalProperties' => ['type' => 'number'],
    ]);

    expect($schema->atPath($path)?->toArray())->toBe($expected);
    expect($schema->atPath(new JsonPath($path))?->toArray())->toBe($expected);
    expect($schema->atPath([]))->toBe($schema);
})->with([
    'JSON null value' => [['null'], ['type' => 'null']],
    'literal boolean schema' => [['free'], true],
    'empty definition' => [['empty'], []],
    'rejecting declared schema' => [['blocked'], false],
    'literal numeric property' => [['0'], ['type' => 'boolean']],
    'first positional item' => [['rows', 0], ['type' => 'string']],
    'second positional item' => [['rows', 1], ['type' => 'integer']],
    'tail item' => [['rows', 3], ['type' => 'null']],
    'index beyond maximum' => [['rows', 4], null],
    'array property mismatch' => [['rows', '0'], null],
    'object index mismatch' => [[0], null],
    'prohibited additional field' => [['closed', 'missing'], null],
    'typed additional field' => [['extra'], ['type' => 'number']],
    'scalar child' => [['null', 'value'], null],
    'rejecting child' => [['blocked', 'value'], null],
]);

it('flattens wildcard matches while preserving arrays selected as values', function () {
    $schema = Schema::fromArray([
        'type' => 'array',
        'items' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'values' => ['type' => 'array', 'items' => ['type' => 'integer']],
                ],
            ],
        ],
    ]);

    expect($schema->atPath([null, null, 'values'])->toArray())->toBe([
        'type' => 'array',
        'items' => ['type' => 'array', 'items' => ['type' => 'integer']],
    ]);
    expect($schema->atPath([null, null, 'values', null])->toArray())->toBe([
        'type' => 'array',
        'items' => ['type' => 'integer'],
    ]);
    expect($schema->atPath([0, 0, 'values'])->toArray())->toBe([
        'type' => 'array',
        'items' => ['type' => 'integer'],
    ]);
});

it('combines positional constraints at each index before projecting wildcard items', function () {
    $schema = Schema::fromArray([
        'type' => 'array',
        'prefixItems' => [['type' => 'string', 'title' => 'First'], ['type' => 'integer']],
        'items' => false,
        'allOf' => [['prefixItems' => [['minLength' => 2]], 'items' => ['maximum' => 10]]],
    ]);
    $items = $schema->atPath([null])->items();

    expect($schema->atPath([0])->effectiveMinLength())->toBe(2);
    expect($schema->atPath([1])->types())->toBe(['integer']);
    expect($schema->atPath([2]))->toBeNull();
    expect($items->branches('anyOf')[0]->annotation('title'))->toBe('First');
    expect($items->branches('anyOf')[0]->effectiveMinLength())->toBe(2);
    expect($items->branches('anyOf')[1]->branches('allOf')[1]->maximum())->toBe(10);
    expect($schema->effectiveItems()->equals($items))->toBeTrue();
});

it('projects composed paths and local references with their sibling constraints', function (string $keyword) {
    $schema = Schema::fromArray([
        '$defs' => ['value' => ['type' => 'string', 'title' => 'Value']],
        'type' => 'object',
        'properties' => ['value' => ['maxLength' => 20]],
        'allOf' => [['properties' => ['value' => ['minLength' => 2]]]],
        $keyword => [
            ['properties' => ['value' => ['$ref' => '#/$defs/value']]],
            ['properties' => ['value' => ['type' => 'integer']]],
        ],
    ]);
    $value = $schema->atPath(['value']);

    expect($value->types())->toBe(['string', 'integer']);
    expect($value->effectiveMinLength())->toBe(2);
    expect($value->effectiveMaxLength())->toBe(20);
    expect($value->annotation('title'))->toBe('Value');
    expect($schema->properties()['value']->toArray())->toBe(['maxLength' => 20]);
})->with(['anyOf', 'oneOf']);

it('projects object wildcards without dropping open additional fields', function () {
    $closed = Schema::fromArray([
        'type' => 'object',
        'properties' => ['first' => ['type' => 'string'], 'second' => ['type' => 'null']],
        'additionalProperties' => false,
    ]);
    $open = $closed->builder()->without('additionalProperties')->build();

    expect($closed->atPath([null])->items()->types())->toBe(['string', 'null']);
    expect($open->atPath([null])->items()->types())->toBe([]);
    expect(Schema::fromArray(false)->atPath(['value']))->toBeNull();
    expect(Schema::fromArray(true)->atPath(['value'])->isEmpty())->toBeTrue();
    expect(Schema::array()->builder()->items(Schema::fromArray(false))->build()->atPath([null]))->toBeNull();
});

it('resolves boolean reference targets without conflating them with null or empty schemas', function (array|bool $target) {
    $schema = Schema::fromArray([
        '$defs' => ['value' => $target],
        'properties' => ['value' => ['$ref' => '#/$defs/value']],
    ]);

    expect($schema->atPath(['value'])->toArray())->toBe($target);
})->with(['true' => [true], 'false' => [false], 'empty' => [[]], 'null value' => [['type' => 'null']]]);

it('preserves reference expansion errors when projecting a path', function () {
    $schema = Schema::fromArray(['properties' => ['value' => ['$ref' => 'external.json']]]);

    expect(fn () => $schema->atPath(['value']))->toThrow(InvalidArgumentException::class, 'Only local');
    expect($schema->properties()['value']->ref())->toBe('external.json');
});

it('finds declared data paths through properties and array items', function (string $path, bool $exists) {
    $schema = Schema::fromArray([
        'properties' => [
            'literal.key' => ['properties' => ['*' => ['type' => 'string']]],
            '0' => false,
            'rows' => ['items' => ['properties' => ['value' => ['type' => 'integer']]]],
        ],
        '$defs' => ['unused' => ['properties' => ['hidden' => []]]],
        'then' => ['properties' => ['conditional' => []]],
        'additionalProperties' => true,
    ]);

    expect($schema->hasDeclaredPath(JsonPath::parse($path)))->toBe($exists);
})->with([
    'root' => ['$', true],
    'literal names' => ["$['literal.key']['*']", true],
    'quoted numeric member' => ["$['0']", true],
    'whole array' => ["$['rows']", true],
    'wildcard item' => ["$['rows'][*]['value']", true],
    'indexed item' => ["$['rows'][3]['value']", true],
    'quoted index' => ["$['rows']['3']['value']", false],
    'missing field' => ["$['rows'][*]['missing']", false],
    'unused definition' => ["$['hidden']", false],
    'conditional inference' => ["$['conditional']", false],
    'implicit additional member' => ["$['other']", false],
]);

it('requires one complete declared path within a composition branch', function (string $keyword) {
    $schema = Schema::fromArray([
        $keyword => [
            ['properties' => ['record' => ['properties' => ['left' => ['properties' => ['first' => []]]]]]],
            ['properties' => ['record' => ['properties' => ['right' => ['properties' => ['second' => []]]]]]],
        ],
    ]);

    expect($schema->hasDeclaredPath(['record', 'left', 'first']))
        ->toBeTrue()
        ->and($schema->hasDeclaredPath(['record', 'right', 'second']))
        ->toBeTrue()
        ->and($schema->hasDeclaredPath(['record', 'left', 'second']))
        ->toBeFalse();
})->with(['anyOf', 'oneOf', 'allOf']);

it('uses an explicitly resolved view for paths through local references', function () {
    $definition = [
        '$defs' => ['record' => ['properties' => ['name' => ['type' => 'string']]]],
        'properties' => ['record' => ['$ref' => '#/$defs/record']],
    ];
    $schema = Schema::fromArray($definition);

    expect($schema->hasDeclaredPath(['record', 'name']))
        ->toBeFalse()
        ->and($schema->resolveReferences()->hasDeclaredPath(['record', 'name']))
        ->toBeTrue()
        ->and($schema->toArray())
        ->toBe($definition);
});

it('walks every supported schema position while leaving annotation and value objects opaque', function () {
    $schema = Schema::fromArray([
        'properties' => ['literal.key' => ['items' => false], '0' => false],
        'patternProperties' => ['^prefix' => true],
        '$defs' => ['unused' => ['properties' => ['inner' => []]]],
        'definitions' => ['legacy' => true],
        'dependentSchemas' => ['key' => []],
        'anyOf' => [true],
        'oneOf' => [false],
        'allOf' => [[]],
        'prefixItems' => [[], true],
        'items' => [],
        'additionalProperties' => false,
        'additionalItems' => true,
        'unevaluatedProperties' => [],
        'unevaluatedItems' => false,
        'contains' => [],
        'not' => [],
        'if' => [],
        'then' => [],
        'else' => [],
        'propertyNames' => [],
        'contentSchema' => [],
        'default' => ['properties' => ['ignored' => []]],
        'enum' => [['items' => []]],
        'x-metadata' => ['items' => []],
    ]);
    $visited = [];
    $schema->walk(function (Schema $fragment, ?string $keyword, string|int|null $key) use (&$visited) {
        $visited[] = [
            $keyword,
            $key,
            $fragment->allowsAny() || $fragment->deniesAny() ? $fragment->toArray() : 'object',
        ];
    });

    $expected = [
        [null,                    null,          'object'],
        ['properties',            'literal.key', 'object'],
        ['items',                 null,          false],
        ['properties',            '0',           false],
        ['patternProperties',     '^prefix',     true],
        ['$defs',                 'unused',      'object'],
        ['properties',            'inner',       'object'],
        ['definitions',           'legacy',      true],
        ['dependentSchemas',      'key',         'object'],
        ['anyOf',                 0,             true],
        ['oneOf',                 0,             false],
        ['allOf',                 0,             'object'],
        ['prefixItems',           0,             'object'],
        ['prefixItems',           1,             true],
        ['items',                 null,          'object'],
        ['additionalProperties',  null,          false],
        ['additionalItems',       null,          true],
        ['unevaluatedProperties', null,          'object'],
        ['unevaluatedItems',      null,          false],
        ['contains',              null,          'object'],
        ['not',                   null,          'object'],
        ['if',                    null,          'object'],
        ['then',                  null,          'object'],
        ['else',                  null,          'object'],
        ['propertyNames',         null,          'object'],
        ['contentSchema',         null,          'object'],
    ];

    expect($visited[0])->toBe([null, null, 'object']);
    expect($visited)->toHaveCount(count($expected))->toContain(...$expected);
});

it('visits each complete subtree before moving to a sibling schema', function () {
    $schema = SchemaBuilder::make(Schema::object([
        'left' => SchemaBuilder::make(Schema::object([
            'child' => SchemaBuilder::make()->type('string')->title('left child')->build(),
        ]))
            ->title('left')
            ->build(),
        'right' => SchemaBuilder::make(Schema::object([
            'child' => SchemaBuilder::make()->type('string')->title('right child')->build(),
        ]))
            ->title('right')
            ->build(),
    ]))
        ->title('root')
        ->build();
    $titles = [];
    $schema->walk(function (Schema $fragment) use (&$titles) {
        $titles[] = $fragment->title();
    });

    expect($titles)->toBeIn([
        ['root', 'left',  'left child',  'right', 'right child'],
        ['root', 'right', 'right child', 'left',  'left child'],
    ]);
});

it('walks recursive documents structurally without expanding references', function () {
    $schema = Schema::fromArray([
        '$defs' => ['recursive' => ['properties' => ['next' => ['$ref' => '#/$defs/recursive']]]],
    ]);
    $references = [];
    $schema->walk(function (Schema $fragment) use (&$references) {
        $references[] = $fragment->ref();
    });

    expect($references)->toBe([null, null, '#/$defs/recursive']);
});
