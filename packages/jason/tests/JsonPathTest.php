<?php

use PHPinnacle\Jason\JsonPath;

it('round trips canonical JSONPath names indexes and wildcards', function () {
    $segments = ['literal.key', 'slash/key', '*', "quote'\"\\", "line\n\t", 'товар', '0', 0, null];

    $path = JsonPath::fromSegments($segments);

    expect($path)
        ->toStartWith("$['literal.key']['slash/key']['*']")
        ->toEndWith("['0'][0][*]")
        ->and(JsonPath::parse($path))
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
