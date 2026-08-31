<?php

use PHPinnacle\Stylus\TipTap\TwigTableConditionExtension;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

it('round trips Twig table row condition attributes through HTML', function () {
    $conditionAst = htmlspecialchars(json_encode([
        'type' => 'group',
        'operator' => 'and',
        'negated' => false,
        'children' => [],
    ], JSON_THROW_ON_ERROR), ENT_QUOTES);
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new Table,
            new TableRow,
            new TableHeader,
            new TableCell,
            new TwigTableConditionExtension,
        ],
        'content' => <<<HTML
            <table><tbody><tr data-twig-row-condition="order.active" data-twig-row-condition-id="active-orders" data-twig-row-condition-ast="{$conditionAst}"><td><p>Value</p></td></tr></tbody></table>
            HTML,
    ]);

    $row = $editor->getDocument()['content'][0]['content'][0];

    expect($row['attrs'])
        ->toMatchArray([
            'twigCondition' => 'order.active',
            'twigConditionId' => 'active-orders',
            'twigConditionAst' => [
                'type' => 'group',
                'operator' => 'and',
                'negated' => false,
                'children' => [],
            ],
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-row-condition="order.active"')
        ->toContain('data-twig-row-condition-id="active-orders"')
        ->toContain('data-twig-row-condition-ast=');
});
