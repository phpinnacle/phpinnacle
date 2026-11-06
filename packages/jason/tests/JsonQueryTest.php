<?php

use PHPinnacle\Jason\JsonPath;
use PHPinnacle\Jason\JsonQuery;
use PHPinnacle\Jason\Schema;

it('uses one canonical path for schema navigation and flat PHP data queries', function () {
    $path = new JsonPath("$['rows'][*]['values'][*]");
    $schema = Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'rows' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'values' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $data = ['rows' => [['values' => [1, 2]], ['values' => [3]]]];

    expect(new JsonQuery($path)->find($data))->toBe([1, 2, 3]);
    expect(new JsonQuery($path->segments)->find($data))->toBe([1, 2, 3]);
    expect($schema->atPath($path)->toArray())->toBe(['type' => 'array', 'items' => ['type' => 'integer']]);
});

it('preserves canonical literal names and supports mutations without reparsing', function () {
    $data = ['2' => ['*' => ['length' => null, "literal.'\\" => 3]]];
    $path = new JsonPath(['2', '*', 'length']);
    $query = new JsonQuery($path);

    expect($query->find($data))->toBe([null]);
    expect($query->set($data, 2))->toBe([2 => ['*' => ['length' => 2, "literal.'\\" => 3]]]);
    expect($query->update($data, fn ($value) => $value === null ? 'updated' : $value))->toBe([
        2 => ['*' => ['length' => 'updated', "literal.'\\" => 3]],
    ]);
    expect($query->remove($data))->toBe([2 => ['*' => ["literal.'\\" => 3]]]);
    expect(new JsonQuery(['items', 0, 0])->set(['items' => []], 'created'))->toBe(['items' => [['created']]]);
    expect(new JsonQuery(['2', '*', 'missing'])->find($data))->toBe([]);
    expect(new JsonQuery(['2', '*', "literal.'\\"])->find($data))->toBe([3]);
    expect(new JsonQuery([])->find($data))->toBe([$data]);
    expect($data[2]['*']['length'])->toBeNull();
});

it('distinguishes object properties from array indexes for every path input', function (
    array $data,
    array $segments,
    array $expected,
) {
    $path = new JsonPath($segments);

    foreach ([$path, $segments, (string) $path] as $input) {
        expect(new JsonQuery($input)->find($data))->toBe($expected);
    }
})->with([
    'quoted index on a list' => [[null], ['0'], []],
    'index on a numeric map' => [[2 => null], [2], []],
    'numeric property on a map' => [[2 => null], ['2'], [null]],
    'numeric property on a mixed map' => [[0 => null, 'name' => 'value'], ['0'], [null]],
    'index on a list' => [[null], [0], [null]],
    'missing property on a map' => [[2 => null], ['3'], []],
    'empty array is a list' => [[], ['name'], []],
    'list wildcard' => [[null, 'value'], [null], [null, 'value']],
    'object wildcard' => [[2 => null, 5 => 'value'], [null], [null, 'value']],
]);

it('leaves mismatched containers untouched by mutations', function (array $data, array $segments) {
    $path = new JsonPath($segments);

    foreach ([$path, $segments, (string) $path] as $input) {
        $query = new JsonQuery($input);

        expect($query->set($data, 'changed'))->toBe($data);
        expect($query->update($data, fn ($value) => 'changed'))->toBe($data);
        expect($query->remove($data))->toBe($data);
    }
})->with([
    'quoted index on a list' => [['value'], ['0']],
    'index on a numeric map' => [[2 => 'value'], [2]],
    'empty array is a list' => [[], ['name']],
]);

it('selects JSON values with the webhook query syntax', function (string $path, array $expected) {
    $data = [
        'limit' => 15,
        'items' => [
            ['name' => 'Alice', 'price' => 10, 'active' => true, 'tag' => null],
            ['name' => 'Bob', 'price' => 20, 'active' => false],
            ['name' => 'Ada', 'price' => 30, 'active' => true],
        ],
        'meta.version' => 'v1',
        '*' => 'literal',
        'nested' => ['name' => 'child'],
    ];

    expect(new JsonQuery($path)->find($data))->toBe($expected);
})->with([
    'dot names' => ['$.items[0].name', ['Alice']],
    'leading zero index' => ['$.items[00].name', ['Alice']],
    'unquoted names' => ['$[items][0][name]', ['Alice']],
    'quoted dot' => ["$['meta.version']", ['v1']],
    'literal star' => ["$['*']", ['literal']],
    'union' => ['$.items[2,0].name', ['Ada', 'Alice']],
    'names union' => ['$.items[0][name,price]', ['Alice', 10]],
    'wildcard' => ['$.items[*].name', ['Alice', 'Bob', 'Ada']],
    'negative index' => ['$.items[-1].name', ['Ada']],
    'slice' => ['$.items[0:2].name', ['Alice', 'Bob']],
    'slice step' => ['$.items[::2].name', ['Alice', 'Ada']],
    'negative slice bounds' => ['$.items[-2:].name', ['Bob', 'Ada']],
    'reverse slice' => ['$.items[::-1].name', ['Ada', 'Bob', 'Alice']],
    'empty slice' => ['$.items[2:0].name', []],
    'recursive names' => ['$..name', ['Alice', 'Bob', 'Ada', 'child']],
    'recursive bracket' => ["$..['name']", ['Alice', 'Bob', 'Ada', 'child']],
    'boolean filter' => ['$.items[?(@.active == true)].name', ['Alice', 'Ada']],
    'root reference' => ['$.items[?(@.price > $.limit)].name', ['Bob', 'Ada']],
    'logical symbols' => ['$.items[?(@.active && @.price >= 20)].name', ['Ada']],
    'negation' => ['$.items[?(not @.active)].name', ['Bob']],
    'regex value' => ['$.items[?(@.name =~ /^a/i)].name', ['Alice', 'Ada']],
    'regex key' => ['$[?(/^meta\./)]', ['v1']],
    'membership' => ['$.items[?(@.name in ["Alice", $.items[2].name])].name', ['Alice', 'Ada']],
    'numeric equality' => ['$.items[?(@.price == 20.0)].name', ['Bob']],
    'numeric membership' => ['$.items[?(@.price in [10.0, 30])].name', ['Alice', 'Ada']],
    'string length' => ['$.items[?(@.name.length > 3)].name', ['Alice']],
    'array length' => ['$.items.length', [3]],
    'null value' => ['$.items[0].tag', [null]],
    'missing values do not compare to null' => ['$.items[?(@.tag != null)].name', []],
    'missing values do not compare to numbers' => ['$.items[?(@.tag < 5)].name', ['Alice']],
    'missing' => ['$.absent.name', []],
]);

it('evaluates and before or in query filters', function (string $path) {
    $data = [
        ['name' => 'cheap inactive', 'price' => 10, 'active' => false],
        ['name' => 'expensive active', 'price' => 30, 'active' => true],
        ['name' => 'expensive inactive', 'price' => 30, 'active' => false],
        ['name' => 'middle active', 'price' => 20, 'active' => true],
    ];

    expect(new JsonQuery($path)->find($data))->toBe(['cheap inactive', 'expensive active']);
})->with([
    'words' => ['$[?(@.price < 15 or @.price > 25 and @.active)].name'],
    'symbols' => ['$[?(@.price < 15 || @.price > 25 && @.active)].name'],
]);

it('compares fractional literals without a leading zero at their numeric boundary', function (
    string $path,
    array $expected,
) {
    expect(new JsonQuery($path)->find([-0.75, -0.25, 0.25, 0.75]))->toBe($expected);
})->with([
    'positive' => ['$[?(@ > .5)]', [0.75]],
    'negative' => ['$[?(@ > -.5)]', [-0.25, 0.25, 0.75]],
]);

it('rejects invalid query syntax before looking at the payload', function (string $path) {
    expect(fn () => new JsonQuery($path))->toThrow(InvalidArgumentException::class);
})->with([
    '',
    'items',
    '@.items',
    '$.',
    '$..',
    '$[]',
    '$[1,]',
    '$[1',
    '$[1:2:0]',
    '$.missing[?(@.x === 1)]',
    '$[?(@.x ==)]',
    '$[?(@.x =~ /[/)]',
    "$['bad\\q']",
    '$[9999999999999999999999999]',
    '$[9007199254740992]',
    '$[-9007199254740992]',
    '$[9007199254740992:]',
    '$[:9007199254740992]',
    '$[::-9007199254740992]',
    '$[?(system("id"))]',
]);

it('accepts interoperable integer boundaries in indexes and slices', function (string $path, array $expected) {
    expect(new JsonQuery($path)->find([1, 2]))->toBe($expected);
})->with([
    'positive index' => ['$[9007199254740991]', []],
    'negative index' => ['$[-9007199254740991]', []],
    'slice start' => ['$[9007199254740991:]', []],
    'slice end' => ['$[:9007199254740991]', [1, 2]],
    'positive slice step' => ['$[::9007199254740991]', [1]],
    'negative slice step' => ['$[::-9007199254740991]', [2]],
]);

it('round trips escaped property names through the canonical codec and query evaluator', function () {
    $key = "quote' slash\\ newline\n and ] , ☃";
    $path = PHPinnacle\Jason\JsonPath::fromSegments([$key]);

    expect(new JsonQuery($path)->find([$key => 'found']))->toBe(['found']);
});

it('reads numeric and digit-prefixed unquoted property names', function () {
    $data = ['123abc' => ['1' => ['2' => 'found']]];

    expect(new JsonQuery('$.123abc.1.2')->find($data))
        ->toBe(['found'])
        ->and(new JsonQuery('$[123abc][1][2]')->find($data))
        ->toBe([])
        ->and(new JsonQuery("$[123abc]['1']['2']")->find($data))
        ->toBe(['found']);
});

it('updates matched nodes without mutating the original payload', function () {
    $data = ['items' => [1, 2, 3], 'other' => 1];

    expect(new JsonQuery('$.items[0,2]')->update($data, fn ($value) => $value * 2))
        ->toBe(['items' => [2, 2, 6], 'other' => 1])
        ->and($data)
        ->toBe(['items' => [1, 2, 3], 'other' => 1]);
});

it('resolves negative indexes for mutations while keeping quoted negative names literal', function () {
    $data = ['items' => ['a', 'b'], -1 => 'literal'];
    $query = new JsonQuery('$.items[-1]');

    expect($query->update($data, fn ($value) => strtoupper($value)))
        ->toBe(['items' => ['a', 'B'], -1 => 'literal'])
        ->and($query->set($data, 'new'))
        ->toBe(['items' => ['a', 'new'], -1 => 'literal'])
        ->and(new JsonQuery("$['-1']")->find($data))
        ->toBe(['literal'])
        ->and($data)
        ->toBe(['items' => ['a', 'b'], -1 => 'literal']);
});

it('keeps duplicate and overlapping update selections attached to their original nodes', function () {
    expect(new JsonQuery('$[0,0]')->update([1], fn ($value) => $value + 1))
        ->toBe([3])
        ->and(new JsonQuery('$..*')->update(['parent' => ['child' => 1]], fn ($value) => null))
        ->toBe(['parent' => null]);
});

it('sets missing destinations and replaces existing nulls without replacing scalar parents', function () {
    expect(new JsonQuery('$.metadata.version')->set([], 'v1'))
        ->toBe([])
        ->and(new JsonQuery('$.version')->set(['name' => 'document'], 'v1'))
        ->toBe(['name' => 'document', 'version' => 'v1'])
        ->and(new JsonQuery('$.target')->set(['target' => null], 1))
        ->toBe(['target' => 1])
        ->and(new JsonQuery('$.target.child')->set(['target' => null], 1))
        ->toBe(['target' => null])
        ->and(new JsonQuery('$.items[*].name')->set([], 'value'))
        ->toBe([])
        ->and(new JsonQuery('$.items.length')->set(['items' => []], 1))
        ->toBe(['items' => []])
        ->and(new JsonQuery('$')->set(['target' => 1], null))
        ->toBeNull();
});

it('sets every matching destination using filters and recursive selections', function () {
    $data = ['items' => [['active' => true, 'name' => 'a'], ['active' => false, 'name' => 'b']]];

    expect(new JsonQuery('$.items[?(@.active)].name')->set($data, 'new'))
        ->toBe(['items' => [['active' => true, 'name' => 'new'], ['active' => false, 'name' => 'b']]])
        ->and(new JsonQuery('$..name')->set($data, 'new'))
        ->toBe(['items' => [['active' => true, 'name' => 'new'], ['active' => false, 'name' => 'new']]]);
});

it('removes selected nodes once and compacts only lists', function () {
    expect(new JsonQuery('$.items[0,2,0]')->remove(['items' => [null, null, null], 'other' => null]))
        ->toBe(['items' => [null], 'other' => null])
        ->and(new JsonQuery("$['0']")->remove(['a', 'b']))
        ->toBe(['a', 'b'])
        ->and(new JsonQuery('$..secret')->remove(['secret' => ['secret' => 1], 'other' => ['secret' => 1]]))
        ->toBe(['other' => []])
        ->and(new JsonQuery("$['2']")->remove([2 => 'remove', 5 => 'keep']))
        ->toBe([5 => 'keep'])
        ->and(new JsonQuery('$')->remove(['a' => 1]))
        ->toBe([]);
});

it('does not mutate computed lengths', function () {
    $data = ['items' => [1, 2]];
    $query = new JsonQuery('$.items.length');

    expect($query->update($data, fn ($value) => 100))->toBe($data)->and($query->remove($data))->toBe($data);
});

it('uses stored length properties before computed lengths within each selection', function () {
    $data = ['items' => [['length' => 9], ['name' => 'a']]];

    expect(new JsonQuery('$.items[*].length')->find($data))
        ->toBe([9])
        ->and(new JsonQuery('$..length')->find($data))
        ->toBe([1, 2, 9, 1, 1, 1]);
});

it('measures filter operands independently from stored length properties', function () {
    $data = [['length' => 9], 123, null, 'abc'];

    expect(new JsonQuery('$[?(@.length == 1)]')->find($data))
        ->toBe([['length' => 9]])
        ->and(new JsonQuery('$[?(@.length == false)]')->find($data))
        ->toBe([123, null]);
});
