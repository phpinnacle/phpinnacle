<?php

use PHPinnacle\Jason\JsonPath;

it('shares canonical segments as an immutable path object', function () {
    $segments = ['rows', null, '0', 2, '*'];
    $path = new JsonPath($segments);
    $segments[0] = 'changed';

    expect($path->segments)->toBe(['rows', null, '0', 2, '*']);
    expect((string) $path)->toBe("$['rows'][*]['0'][2]['*']");
    expect(new JsonPath((string) $path)->segments)->toBe($path->segments);
});

it('round trips canonical JSONPath names indexes and wildcards', function () {
    $segments = ['literal.key', 'slash/key', '*', "quote'\"\\", "line\n\t", 'товар', '0', 0, null];
    $expected = <<<'PATH'
        $['literal.key']['slash/key']['*']['quote\'"\\']['line\n\t']['товар']['0'][0][*]
        PATH;

    $path = JsonPath::fromSegments($segments);

    expect($path)
        ->toBe($expected)
        ->and(JsonPath::parse($expected))
        ->toBe($segments)
        ->and(JsonPath::parse('$'))
        ->toBe([]);
});

it('rejects malformed and noncanonical JSONPath strings', function (string $path) {
    expect(fn () => JsonPath::parse($path))->toThrow(InvalidArgumentException::class);
})->with([
    '',
    'input.name',
    '$.name',
    '$["name"]',
    '$[-1]',
    '$[01]',
    '$[999999999999999999999999]',
    "$['bad\\q']",
    "$['unclosed'",
    "$['\\u0061']",
]);
