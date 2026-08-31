<?php

use PHPinnacle\Stylus\TipTap\TwigTableLoopExtension;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

it('round trips Twig table loop attributes through HTML', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new Table,
            new TableRow,
            new TableHeader,
            new TableCell,
            new TwigTableLoopExtension,
        ],
        'content' => <<<'HTML'
            <table><tbody><tr data-twig-loop-item="order" data-twig-loop-key="orderKey" data-twig-loop-iterable="orders" data-twig-loop-transforms='[{"name":"reverse","arguments":[]}]'><td data-twig-loop-id="columns" data-twig-loop-item="column" data-twig-loop-iterable="order.columns"><p>Value</p></td></tr></tbody></table>
            HTML,
    ]);

    $document = $editor->getDocument();
    $row = $document['content'][0]['content'][0];
    $cell = $row['content'][0];

    expect($document['type'])
        ->toBe('doc')
        ->and($row['type'])
        ->toBe('tableRow')
        ->and($row['attrs'])
        ->toMatchArray([
            'twigLoopItem' => 'order',
            'twigLoopKey' => 'orderKey',
            'twigLoopIterable' => 'orders',
            'twigLoopTransforms' => [[
                'name' => 'reverse',
                'arguments' => [],
            ]],
        ])
        ->and($cell['type'])
        ->toBe('tableCell')
        ->and($cell['attrs'])
        ->toMatchArray([
            'twigLoopId' => 'columns',
            'twigLoopItem' => 'column',
            'twigLoopIterable' => 'order.columns',
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-loop-item="order"')
        ->toContain('data-twig-loop-key="orderKey"')
        ->toContain('data-twig-loop-iterable="orders"')
        ->toContain('data-twig-loop-transforms=')
        ->toContain('data-twig-loop-id="columns"')
        ->toContain('data-twig-loop-iterable="order.columns"');
});
