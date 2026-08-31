<?php

use PHPinnacle\Stylus\TipTap\TwigApplyExtension;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;

it('round trips Twig apply filters through HTML', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new TwigApplyExtension,
        ],
        'content' => <<<'HTML'
            <section data-twig-apply data-twig-apply-filters='[{"name":"upper","arguments":[]}]'>
                <p>Important</p>
            </section>
            HTML,
    ]);

    expect($editor->getDocument())
        ->toMatchArray([
            'type' => 'doc',
            'content' => [[
                'type' => 'twigApply',
                'attrs' => [
                    'filters' => [[
                        'name' => 'upper',
                        'arguments' => [],
                    ]],
                ],
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Important']],
                ]],
            ]],
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-apply-filters=');
});
