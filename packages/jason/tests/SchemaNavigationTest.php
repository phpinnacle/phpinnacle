<?php

use PHPinnacle\Jason\JsonPath;
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

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
