<?php

use PHPinnacle\Jason\JsonQuery;

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
    'logical precedence' => ['$.items[?(@.price < 15 or @.price > 25 and @.active)].name', ['Alice', 'Ada']],
    'logical symbols' => ['$.items[?(@.active && @.price >= 20)].name', ['Ada']],
    'negation' => ['$.items[?(not @.active)].name', ['Bob']],
    'regex value' => ['$.items[?(@.name =~ /^a/i)].name', ['Alice', 'Ada']],
    'regex key' => ['$[?(/^meta\./)]', ['v1']],
    'membership' => ['$.items[?(@.name in ["Alice", $.items[2].name])].name', ['Alice', 'Ada']],
    'numeric equality' => ['$.items[?(@.price == 20.0)].name', ['Bob']],
    'numeric membership' => ['$.items[?(@.price in [10.0, 30])].name', ['Alice', 'Ada']],
    'fractional literal without a leading zero' => ['$.items[?(@.price > .5)].name', ['Alice', 'Bob', 'Ada']],
    'negative fractional literal' => ['$.items[?(@.price > -.5)].name', ['Alice', 'Bob', 'Ada']],
    'string length' => ['$.items[?(@.name.length > 3)].name', ['Alice']],
    'array length' => ['$.items.length', [3]],
    'null value' => ['$.items[0].tag', [null]],
    'missing values do not compare to null' => ['$.items[?(@.tag != null)].name', []],
    'missing values do not compare to numbers' => ['$.items[?(@.tag < 5)].name', ['Alice']],
    'missing' => ['$.absent.name', []],
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
    '$[?(system("id"))]',
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
        ->toBe(['metadata' => ['version' => 'v1']])
        ->and(new JsonQuery('$.target')->set(['target' => null], 1))
        ->toBe(['target' => 1])
        ->and(new JsonQuery('$.target.child')->set(['target' => null], 1))
        ->toBe(['target' => null])
        ->and(new JsonQuery('$.items[*].name')->set([], 'value'))
        ->toBe(['items' => []])
        ->and(new JsonQuery('$.items.length')->set(['items' => []], 1))
        ->toBe(['items' => ['length' => 1]])
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
        ->toBe(['b'])
        ->and(new JsonQuery('$..secret')->remove(['secret' => ['secret' => 1], 'other' => ['secret' => 1]]))
        ->toBe(['other' => []])
        ->and(new JsonQuery('$[2]')->remove([2 => 'remove', 5 => 'keep']))
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
