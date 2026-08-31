<?php

use PHPinnacle\Stylus\Condition;
use PHPinnacle\Stylus\Enums\FilterOutput;
use PHPinnacle\Stylus\Filter;
use PHPinnacle\Stylus\Twig\ConditionExpressionSerializer;
use PHPinnacle\Stylus\Variable;
use PHPinnacle\Stylus\VariableScope;

function stylusConditionSerializer(): ConditionExpressionSerializer
{
    $truthy = Condition::truthy();
    $equals = Condition::comparison('equals', '==')->types('text', 'number', 'boolean');
    $contains = Condition::comparison('contains', 'in')->matchVariableTypes(false);
    $defined = Condition::test('defined');
    $sequence = Condition::test('sequence')->types('collection');

    return new ConditionExpressionSerializer(
        new VariableScope([
            'user.active' => Variable::make('user.active', 'boolean'),
            'user.age' => Variable::make('user.age', 'number'),
            'user.email' => Variable::text('user.email'),
            'needle' => Variable::text('needle'),
            'created_at' => Variable::make('created_at', 'date'),
            'orders' => Variable::collection('orders'),
        ]),
        [
            $truthy->getKey() => $truthy,
            $equals->getKey() => $equals,
            $contains->getKey() => $contains,
            $defined->getKey() => $defined,
            $sequence->getKey() => $sequence,
        ],
        [
            'trim' => Filter::make('trim')->types('text'),
            'lower' => Filter::make('lower')->types('text'),
            'first' => Filter::make('first')->types('collection')->output(FilterOutput::CollectionItem),
        ],
    );
}

it('serializes structured boolean conditions with registered descriptors', function () {
    $expression = [
        'type' => 'group',
        'operator' => 'and',
        'negated' => false,
        'children' => [
            [
                'type' => 'truthy',
                'subject' => ['type' => 'variable', 'name' => 'user.active'],
                'negated' => false,
            ],
            [
                'type' => 'comparison',
                'left' => ['type' => 'variable', 'name' => 'user.age'],
                'operator' => 'equals',
                'right' => ['type' => 'literal', 'valueType' => 'number', 'value' => '18'],
                'negated' => true,
            ],
            [
                'type' => 'test',
                'subject' => ['type' => 'variable', 'name' => 'user.email'],
                'test' => 'defined',
                'negated' => false,
            ],
        ],
    ];

    expect(stylusConditionSerializer()->serialize($expression))
        ->toBe(
            '(user.active) and (not (user.age == 18)) and (user.email is defined)',
        );
});

it('serializes ordered filters on variable operands', function () {
    expect(stylusConditionSerializer()->serialize([
        'type' => 'comparison',
        'left' => [
            'type' => 'variable',
            'name' => 'user.email',
            'filters' => [
                ['name' => 'trim', 'arguments' => []],
                ['name' => 'lower', 'arguments' => ["'UTF-8'"]],
            ],
        ],
        'operator' => 'equals',
        'right' => ['type' => 'literal', 'valueType' => 'string', 'value' => 'ada@example.com'],
        'negated' => false,
    ]))
        ->toBe("user.email|trim|lower('UTF-8') == 'ada@example.com'");
});

it('rejects null literals', function () {
    expect(fn () => stylusConditionSerializer()->serialize([
        'type' => 'comparison',
        'left' => ['type' => 'variable', 'name' => 'user.age'],
        'operator' => 'equals',
        'right' => ['type' => 'literal', 'valueType' => 'null', 'value' => null],
        'negated' => false,
    ]))
        ->toThrow(InvalidArgumentException::class, 'Unsupported Twig condition literal type.');
});

it('serializes containment and typed variable operands', function () {
    expect(stylusConditionSerializer()->serialize([
        'type' => 'comparison',
        'left' => ['type' => 'variable', 'name' => 'needle'],
        'operator' => 'contains',
        'right' => ['type' => 'variable', 'name' => 'orders'],
        'negated' => false,
    ]))
        ->toBe('needle in orders');
});

it('serializes typed tests for supported variables', function () {
    expect(stylusConditionSerializer()->serialize([
        'type' => 'test',
        'subject' => ['type' => 'variable', 'name' => 'orders'],
        'test' => 'sequence',
        'negated' => false,
    ]))
        ->toBe('orders is sequence');
});

it('rejects unregistered or malformed persisted condition input', function (array $expression, string $message) {
    expect(fn () => stylusConditionSerializer()->serialize($expression))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unknown variable' => [
        [
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'admin.secret'],
        ],
        'variable is not available',
    ],
    'unknown operator' => [
        [
            'type' => 'comparison',
            'left' => ['type' => 'variable', 'name' => 'user.age'],
            'operator' => 'executes',
            'right' => ['type' => 'literal', 'valueType' => 'number', 'value' => 1],
        ],
        'operator is not registered',
    ],
    'unsupported truthy variable type' => [
        [
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'user.email'],
        ],
        'variable type is not supported',
    ],
    'unsupported comparison variable type' => [
        [
            'type' => 'comparison',
            'left' => ['type' => 'variable', 'name' => 'created_at'],
            'operator' => 'equals',
            'right' => ['type' => 'variable', 'name' => 'user.email'],
        ],
        'variable type is not supported',
    ],
    'unsupported test variable type' => [
        [
            'type' => 'test',
            'subject' => ['type' => 'variable', 'name' => 'user.email'],
            'test' => 'sequence',
        ],
        'variable type is not supported',
    ],
    'mismatched comparison variable types' => [
        [
            'type' => 'comparison',
            'left' => ['type' => 'variable', 'name' => 'user.age'],
            'operator' => 'equals',
            'right' => ['type' => 'variable', 'name' => 'user.email'],
        ],
        'variables must have matching types',
    ],
    'invalid number' => [
        [
            'type' => 'comparison',
            'left' => ['type' => 'variable', 'name' => 'user.age'],
            'operator' => 'equals',
            'right' => ['type' => 'literal', 'valueType' => 'number', 'value' => '1 + system()'],
        ],
        'must be an integer or decimal',
    ],
    'function operand' => [
        [
            'type' => 'truthy',
            'subject' => ['type' => 'function', 'name' => 'unknown', 'arguments' => []],
        ],
        'Unsupported Twig condition operand type',
    ],
    'unknown operand filter' => [
        [
            'type' => 'test',
            'subject' => [
                'type' => 'variable',
                'name' => 'user.email',
                'filters' => [['name' => 'missing', 'arguments' => []]],
            ],
            'test' => 'defined',
        ],
        'filter is not available',
    ],
    'type-changing operand filter' => [
        [
            'type' => 'test',
            'subject' => [
                'type' => 'variable',
                'name' => 'orders',
                'filters' => [['name' => 'first', 'arguments' => []]],
            ],
            'test' => 'sequence',
        ],
        'filter is not available',
    ],
]);
