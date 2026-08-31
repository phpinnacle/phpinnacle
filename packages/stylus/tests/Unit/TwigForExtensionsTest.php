<?php

use PHPinnacle\Stylus\TipTap\TwigForBodyExtension;
use PHPinnacle\Stylus\TipTap\TwigForElseExtension;
use PHPinnacle\Stylus\TipTap\TwigForExtension;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;

it('round trips Twig loop branches through HTML', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new TwigForBodyExtension,
            new TwigForElseExtension,
            new TwigForExtension,
        ],
        'content' => <<<'HTML'
            <section data-twig-for data-twig-item="order" data-twig-key="orderKey" data-twig-iterable="orders" data-twig-transforms='[{"name":"slice","arguments":["0","10"]}]'>
                <div data-twig-for-branch="body"><p>Order</p></div>
                <div data-twig-for-branch="else"><p>No orders</p></div>
            </section>
            HTML,
    ]);

    expect($editor->getDocument())
        ->toMatchArray([
            'type' => 'doc',
            'content' => [[
                'type' => 'twigFor',
                'attrs' => [
                    'item' => 'order',
                    'key' => 'orderKey',
                    'iterable' => 'orders',
                    'transforms' => [[
                        'name' => 'slice',
                        'arguments' => ['0', '10'],
                    ]],
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
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-for-branch="body"')
        ->toContain('data-twig-for-branch="else"')
        ->toContain('data-twig-transforms=');
});
