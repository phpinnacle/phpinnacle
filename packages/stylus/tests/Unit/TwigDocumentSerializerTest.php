<?php

use PHPinnacle\Stylus\Twig\TwigDocumentSerializer;

it('serializes rich text tables and Twig structures', function () {
    $document = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'heading',
                'attrs' => ['level' => 1],
                'content' => [
                    ['type' => 'text', 'text' => 'Products'],
                ],
            ],
            [
                'type' => 'table',
                'content' => [
                    [
                        'type' => 'tableRow',
                        'content' => [
                            [
                                'type' => 'tableHeader',
                                'content' => [[
                                    'type' => 'paragraph',
                                    'content' => [['type' => 'text', 'text' => 'Name']],
                                ]],
                            ],
                            [
                                'type' => 'tableHeader',
                                'content' => [[
                                    'type' => 'paragraph',
                                    'content' => [['type' => 'text', 'text' => 'Price']],
                                ]],
                            ],
                        ],
                    ],
                    [
                        'type' => 'tableRow',
                        'content' => [
                            [
                                'type' => 'tableCell',
                                'content' => [[
                                    'type' => 'paragraph',
                                    'content' => [[
                                        'type' => 'twigVariable',
                                        'attrs' => [
                                            'expression' => 'product.name',
                                            'filters' => [['name' => 'title', 'arguments' => []]],
                                        ],
                                    ]],
                                ]],
                            ],
                            [
                                'type' => 'tableCell',
                                'content' => [[
                                    'type' => 'paragraph',
                                    'content' => [[
                                        'type' => 'twigVariable',
                                        'attrs' => [
                                            'expression' => 'product.price',
                                            'filters' => [['name' => 'number_format', 'arguments' => ['2']]],
                                        ],
                                    ]],
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'twigIf',
                'attrs' => ['condition' => 'products is not empty'],
                'content' => [
                    [
                        'type' => 'twigThen',
                        'content' => [[
                            'type' => 'twigFor',
                            'attrs' => [
                                'key' => 'slug',
                                'item' => 'product',
                                'iterable' => 'products',
                            ],
                            'content' => [[
                                'type' => 'twigForBody',
                                'content' => [[
                                    'type' => 'paragraph',
                                    'content' => [['type' => 'text', 'text' => 'Available']],
                                ]],
                            ]],
                        ]],
                    ],
                    [
                        'type' => 'twigElse',
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [['type' => 'text', 'text' => 'No products']],
                        ]],
                    ],
                ],
            ],
        ],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize($document))
        ->toBe(implode("\n", [
            '<h1>Products</h1>',
            '<table>',
            '<tr>',
            '<th><p>Name</p></th>',
            '<th><p>Price</p></th>',
            '</tr>',
            '<tr>',
            '<td><p>{{ product.name|title }}</p></td>',
            '<td><p>{{ product.price|number_format(2) }}</p></td>',
            '</tr>',
            '</table>',
            '{% if products is not empty %}',
            '{% for slug, product in products %}',
            '<p>Available</p>',
            '{% endfor %}',
            '{% else %}',
            '<p>No products</p>',
            '{% endif %}',
        ]));
});

it('escapes text and link attributes', function () {
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => '<View>',
                'marks' => [
                    ['type' => 'bold'],
                    ['type' => 'link', 'attrs' => ['href' => '/products?view="all"&active=1']],
                ],
            ]],
        ]],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize($document))
        ->toBe('<p><a href="/products?view=&quot;all&quot;&amp;active=1"><strong>&lt;View&gt;</strong></a></p>');
});

it('rejects unsupported Twig comment nodes', function () {
    $serializer = app(TwigDocumentSerializer::class);

    expect(fn () => $serializer->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'twigComment',
            'attrs' => ['text' => 'Review this branch before launch.'],
        ]],
    ]))
        ->toThrow(RuntimeException::class, "Unsupported Twig editor node: 'twigComment'.");
});

it('serializes block loop body and empty-state branches', function () {
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'twigFor',
            'attrs' => [
                'item' => 'order',
                'key' => null,
                'iterable' => 'orders',
                'transforms' => [
                    ['name' => 'sort', 'arguments' => []],
                    ['name' => 'slice', 'arguments' => ['0', '10']],
                ],
            ],
            'content' => [
                [
                    'type' => 'twigForBody',
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Order']],
                    ]],
                ],
                [
                    'type' => 'twigForElse',
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'No orders']],
                    ]],
                ],
            ],
        ]],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize($document))
        ->toBe(implode("\n", [
            '{% for order in orders|sort|slice(0, 10) %}',
            '<p>Order</p>',
            '{% else %}',
            '<p>No orders</p>',
            '{% endfor %}',
        ]));
});

it('rejects block loops without an explicit body branch', function () {
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'twigFor',
            'attrs' => [
                'item' => 'order',
                'key' => null,
                'iterable' => 'orders',
            ],
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Order']],
            ]],
        ]],
    ];

    expect(fn () => app(TwigDocumentSerializer::class)->serialize($document))
        ->toThrow(
            InvalidArgumentException::class,
            'twigFor must contain twigForBody followed by an optional twigForElse.',
        );
});

it('serializes block filter regions with Twig apply', function () {
    expect(app(TwigDocumentSerializer::class)->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'twigApply',
            'attrs' => [
                'filters' => [
                    ['name' => 'upper', 'arguments' => []],
                    ['name' => 'escape', 'arguments' => ["'html'"]],
                ],
            ],
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Important']],
            ]],
        ]],
    ]))
        ->toBe(implode("\n", [
            "{% apply upper|escape('html') %}",
            '<p>Important</p>',
            '{% endapply %}',
        ]));
});

it('serializes a contiguous inline condition as one Twig region', function () {
    $inlineCondition = [
        'type' => 'twigInlineIf',
        'attrs' => [
            'condition' => 'user.active',
            'conditionAst' => null,
            'conditionId' => 'active-user',
            'branch' => 'then',
        ],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Hello '],
                [
                    'type' => 'text',
                    'text' => 'active ',
                    'marks' => [
                        $inlineCondition,
                        ['type' => 'bold'],
                    ],
                ],
                [
                    'type' => 'twigVariable',
                    'attrs' => [
                        'expression' => 'user.name',
                        'filters' => [],
                    ],
                    'marks' => [$inlineCondition],
                ],
                ['type' => 'text', 'text' => '.'],
            ],
        ]],
    ]))
        ->toBe('<p>Hello {% if user.active %}<strong>active </strong>{{ user.name }}{% endif %}.</p>');
});

it('serializes paired inline condition branches as one Twig region', function () {
    $condition = [
        'condition' => 'user.active',
        'conditionAst' => null,
        'conditionId' => 'active-user',
    ];

    expect(app(TwigDocumentSerializer::class)->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Hello '],
                [
                    'type' => 'text',
                    'text' => 'member',
                    'marks' => [[
                        'type' => 'twigInlineIf',
                        'attrs' => [...$condition, 'branch' => 'then'],
                    ]],
                ],
                [
                    'type' => 'text',
                    'text' => "\u{200B}",
                    'marks' => [[
                        'type' => 'twigInlineIf',
                        'attrs' => [...$condition, 'branch' => 'else'],
                    ]],
                ],
                [
                    'type' => 'text',
                    'text' => 'guest',
                    'marks' => [[
                        'type' => 'twigInlineIf',
                        'attrs' => [...$condition, 'branch' => 'else'],
                    ]],
                ],
                ['type' => 'text', 'text' => '.'],
            ],
        ]],
    ]))
        ->toBe('<p>Hello {% if user.active %}member{% else %}guest{% endif %}.</p>');
});

it('serializes an inline condition nested inside another inline condition', function () {
    $outerCondition = [
        'type' => 'twigInlineIf',
        'attrs' => [
            'condition' => 'user.visible',
            'conditionAst' => null,
            'conditionId' => 'visible-user',
            'branch' => 'then',
        ],
    ];
    $innerCondition = [
        'type' => 'twigInlineIf',
        'attrs' => [
            'condition' => 'user.active',
            'conditionAst' => null,
            'conditionId' => 'active-user',
            'branch' => 'then',
        ],
    ];
    $innerElseCondition = [
        ...$innerCondition,
        'attrs' => [
            ...$innerCondition['attrs'],
            'branch' => 'else',
        ],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Hello ',
                    'marks' => [$outerCondition],
                ],
                [
                    'type' => 'text',
                    'text' => 'member',
                    'marks' => [$outerCondition, $innerCondition],
                ],
                [
                    'type' => 'text',
                    'text' => 'guest',
                    'marks' => [$outerCondition, $innerElseCondition],
                ],
                [
                    'type' => 'text',
                    'text' => '!',
                    'marks' => [$outerCondition],
                ],
            ],
        ]],
    ]))
        ->toBe('<p>{% if user.visible %}Hello {% if user.active %}member{% else %}guest{% endif %}!{% endif %}</p>');
});

it('rejects an inline else branch without a preceding if branch', function () {
    expect(fn () => app(TwigDocumentSerializer::class)->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'guest',
                'marks' => [[
                    'type' => 'twigInlineIf',
                    'attrs' => [
                        'condition' => 'user.active',
                        'conditionId' => 'active-user',
                        'branch' => 'else',
                    ],
                ]],
            ]],
        ]],
    ]))
        ->toThrow(InvalidArgumentException::class, 'must start with an if branch');
});

it('rejects an incomplete inline condition format', function (array $attributes, string $message) {
    expect(fn () => app(TwigDocumentSerializer::class)->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'member',
                'marks' => [[
                    'type' => 'twigInlineIf',
                    'attrs' => $attributes,
                ]],
            ]],
        ]],
    ]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing condition ID' => [
        ['condition' => 'user.active', 'branch' => 'then'],
        'Twig inline condition ID must be a string',
    ],
    'missing branch' => [
        ['condition' => 'user.active', 'conditionId' => 'active-user'],
        'Twig inline condition branch must be then or else',
    ],
]);

it('serializes nested table row and cell loops', function () {
    $loopedCellAttributes = [
        'twigLoopId' => 'column-loop',
        'twigLoopItem' => 'column',
        'twigLoopKey' => null,
        'twigLoopIterable' => 'order.columns',
    ];
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [[
                'type' => 'tableRow',
                'attrs' => [
                    'twigLoopItem' => 'order',
                    'twigLoopKey' => 'orderKey',
                    'twigLoopIterable' => 'orders',
                ],
                'content' => [
                    [
                        'type' => 'tableCell',
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [[
                                'type' => 'twigVariable',
                                'attrs' => [
                                    'expression' => 'order.number',
                                    'filters' => [],
                                ],
                            ]],
                        ]],
                    ],
                    [
                        'type' => 'tableCell',
                        'attrs' => $loopedCellAttributes,
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [[
                                'type' => 'twigVariable',
                                'attrs' => [
                                    'expression' => 'column.label',
                                    'filters' => [],
                                ],
                            ]],
                        ]],
                    ],
                    [
                        'type' => 'tableCell',
                        'attrs' => $loopedCellAttributes,
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [[
                                'type' => 'twigVariable',
                                'attrs' => [
                                    'expression' => 'column.value',
                                    'filters' => [],
                                ],
                            ]],
                        ]],
                    ],
                ],
            ]],
        ]],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize($document))
        ->toBe(implode("\n", [
            '<table>',
            '{% for orderKey, order in orders %}',
            '<tr>',
            '<td><p>{{ order.number }}</p></td>',
            '{% for column in order.columns %}',
            '<td><p>{{ column.label }}</p></td>',
            '<td><p>{{ column.value }}</p></td>',
            '{% endfor %}',
            '</tr>',
            '{% endfor %}',
            '</table>',
        ]));
});

it('rejects malformed table cell loop groups', function (array $cells, string $message) {
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [[
                'type' => 'tableRow',
                'content' => $cells,
            ]],
        ]],
    ];

    expect(fn () => app(TwigDocumentSerializer::class)->serialize($document))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'non-contiguous group' => [
        [
            twigLoopTestCell('group'),
            twigLoopTestCell(loop: false),
            twigLoopTestCell('group'),
        ],
        'must be contiguous',
    ],
    'mismatched attributes' => [
        [
            twigLoopTestCell('group', item: 'column'),
            twigLoopTestCell('group', item: 'value'),
        ],
        'must use identical loop attributes',
    ],
    'missing group ID' => [
        [twigLoopTestCell(id: null, item: 'column')],
        'loop ID must be a string',
    ],
]);

it('wraps contiguous table rows in one Twig condition', function () {
    $conditionAttributes = [
        'twigCondition' => 'order.active',
        'twigConditionId' => 'active-orders',
    ];
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [
                twigConditionTestRow($conditionAttributes, 'First'),
                twigConditionTestRow($conditionAttributes, 'Second'),
                twigConditionTestRow([], 'Always'),
            ],
        ]],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize($document))
        ->toBe(implode("\n", [
            '<table>',
            '{% if order.active %}',
            '<tr>',
            '<td><p>First</p></td>',
            '</tr>',
            '<tr>',
            '<td><p>Second</p></td>',
            '</tr>',
            '{% endif %}',
            '<tr>',
            '<td><p>Always</p></td>',
            '</tr>',
            '</table>',
        ]));
});

it('places a single row condition inside its row loop', function () {
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [twigConditionTestRow([
                'twigLoopItem' => 'order',
                'twigLoopKey' => null,
                'twigLoopIterable' => 'orders',
                'twigLoopTransforms' => [],
                'twigCondition' => 'order.active',
                'twigConditionId' => 'active-order',
            ], 'Order')],
        ]],
    ];

    expect(app(TwigDocumentSerializer::class)->serialize($document))
        ->toBe(implode("\n", [
            '<table>',
            '{% for order in orders %}',
            '{% if order.active %}',
            '<tr>',
            '<td><p>Order</p></td>',
            '</tr>',
            '{% endif %}',
            '{% endfor %}',
            '</table>',
        ]));
});

it('rejects merged cells in a table containing loops', function () {
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [[
                'type' => 'tableRow',
                'attrs' => [
                    'twigLoopItem' => 'order',
                    'twigLoopKey' => null,
                    'twigLoopIterable' => 'orders',
                ],
                'content' => [[
                    'type' => 'tableCell',
                    'attrs' => ['colspan' => 2],
                    'content' => [['type' => 'paragraph']],
                ]],
            ]],
        ]],
    ];

    expect(fn () => app(TwigDocumentSerializer::class)->serialize($document))
        ->toThrow(InvalidArgumentException::class, 'cannot be used in tables with merged cells');
});

it('rejects invalid nodes and Twig identifiers', function () {
    $serializer = app(TwigDocumentSerializer::class);

    expect(fn () => $serializer->serialize([
        'type' => 'doc',
        'content' => [['type' => 'unsupported']],
    ]))
        ->toThrow(RuntimeException::class, 'Unsupported Twig editor node');

    expect(fn () => $serializer->serialize([
        'type' => 'doc',
        'content' => [[
            'type' => 'twigFor',
            'attrs' => ['item' => 'not valid', 'iterable' => 'products'],
            'content' => [[
                'type' => 'twigForBody',
                'content' => [['type' => 'paragraph']],
            ]],
        ]],
    ]))
        ->toThrow(InvalidArgumentException::class, 'Twig loop item must be a valid Twig identifier');
});

function twigLoopTestCell(?string $id = null, string $item = 'column', bool $loop = true): array
{
    return [
        'type' => 'tableCell',
        'attrs' => $loop
            ? array_filter(
                [
                    'twigLoopId' => $id,
                    'twigLoopItem' => $item,
                    'twigLoopKey' => null,
                    'twigLoopIterable' => 'columns',
                ],
                static fn (mixed $value) => $value !== null,
            )
            : [],
        'content' => [['type' => 'paragraph']],
    ];
}

/** @param array<string, mixed> $attributes */
function twigConditionTestRow(array $attributes, string $content): array
{
    return [
        'type' => 'tableRow',
        'attrs' => $attributes,
        'content' => [[
            'type' => 'tableCell',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $content]],
            ]],
        ]],
    ];
}
