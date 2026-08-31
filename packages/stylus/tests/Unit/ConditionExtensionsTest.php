<?php

use PHPinnacle\Stylus\TipTap\ConditionBlockExtension;
use PHPinnacle\Stylus\TipTap\ConditionInlineExtension;
use PHPinnacle\Stylus\TipTap\DivExtension;
use PHPinnacle\Stylus\TipTap\TwigElseExtension;
use PHPinnacle\Stylus\TipTap\TwigIfExtension;
use PHPinnacle\Stylus\TipTap\TwigInlineIfExtension;
use PHPinnacle\Stylus\TipTap\TwigThenExtension;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;

it('round trips block and inline conditions through HTML', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new DivExtension,
            new ConditionBlockExtension,
            new ConditionInlineExtension,
        ],
        'content' => '<div data-condition=\'user.plan == "pro"\'><p>Premium <span data-condition="feature.beta">beta</span></p></div>',
    ]);

    expect($editor->getDocument())
        ->toMatchArray([
            'type' => 'doc',
            'content' => [[
                'type' => 'conditionBlock',
                'attrs' => [
                    'condition' => 'user.plan == "pro"',
                ],
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Premium ',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'beta',
                            'marks' => [[
                                'type' => 'conditionInline',
                                'attrs' => [
                                    'condition' => 'feature.beta',
                                ],
                            ]],
                        ],
                    ],
                ]],
            ]],
        ])
        ->and($editor->getHTML())
        ->toBe(
            '<div data-condition="user.plan == &quot;pro&quot;"><p>Premium <span data-condition="feature.beta">beta</span></p></div>',
        );
});

it('does not treat an ordinary div as a condition block', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new DivExtension,
            new ConditionBlockExtension,
            new ConditionInlineExtension,
        ],
        'content' => '<div><p>Always visible</p></div>',
    ]);

    expect($editor->getDocument()['content'][0]['type'])->toBe('div');
});

it('round trips a structured Twig condition AST through HTML', function () {
    $conditionAst = [
        'type' => 'group',
        'operator' => 'and',
        'children' => [[
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'user.active'],
        ]],
    ];
    $encodedConditionAst = htmlspecialchars(json_encode($conditionAst, JSON_THROW_ON_ERROR), ENT_QUOTES);
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new TwigThenExtension,
            new TwigElseExtension,
            new TwigIfExtension,
        ],
        'content' => <<<HTML
            <section data-twig-if data-twig-condition="(user.active)" data-twig-condition-ast="{$encodedConditionAst}">
                <div data-twig-branch="then"><p>Active</p></div>
            </section>
            HTML,
    ]);

    expect($editor->getDocument()['content'][0]['attrs'])
        ->toMatchArray([
            'condition' => '(user.active)',
            'conditionAst' => $conditionAst,
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-condition-ast=');
});

it('round trips a structured inline Twig condition through HTML', function () {
    $conditionAst = [
        'type' => 'group',
        'operator' => 'and',
        'children' => [[
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'user.active'],
        ]],
    ];
    $encodedConditionAst = htmlspecialchars(json_encode($conditionAst, JSON_THROW_ON_ERROR), ENT_QUOTES);
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new TwigInlineIfExtension,
        ],
        'content' => <<<HTML
            <p>Hello <span data-twig-inline-if data-twig-inline-condition-id="active-user" data-twig-inline-branch="then" data-twig-condition="(user.active)" data-twig-condition-ast="{$encodedConditionAst}">member</span>.</p>
            HTML,
    ]);

    expect($editor->getDocument()['content'][0]['content'][1]['marks'][0])
        ->toMatchArray([
            'type' => 'twigInlineIf',
            'attrs' => [
                'condition' => '(user.active)',
                'conditionAst' => $conditionAst,
                'conditionId' => 'active-user',
                'branch' => 'then',
            ],
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-inline-if="true"')
        ->toContain('data-twig-inline-condition-id="active-user"')
        ->toContain('data-twig-inline-branch="then"')
        ->toContain('data-twig-condition-ast=');
});

it('round trips paired inline Twig condition branches through HTML', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new TwigInlineIfExtension,
        ],
        'content' => <<<'HTML'
            <p><span data-twig-inline-if data-twig-inline-condition-id="greeting" data-twig-inline-branch="then" data-twig-condition="user.active">member</span><span data-twig-inline-if data-twig-inline-condition-id="greeting" data-twig-inline-branch="else" data-twig-condition="user.active">guest</span></p>
            HTML,
    ]);

    expect($editor->getDocument()['content'][0]['content'])
        ->toMatchArray([
            [
                'type' => 'text',
                'text' => 'member',
                'marks' => [[
                    'type' => 'twigInlineIf',
                    'attrs' => [
                        'condition' => 'user.active',
                        'conditionId' => 'greeting',
                        'branch' => 'then',
                    ],
                ]],
            ],
            [
                'type' => 'text',
                'text' => 'guest',
                'marks' => [[
                    'type' => 'twigInlineIf',
                    'attrs' => [
                        'condition' => 'user.active',
                        'conditionId' => 'greeting',
                        'branch' => 'else',
                    ],
                ]],
            ],
        ])
        ->and($editor->getHTML())
        ->toContain('data-twig-inline-condition-id="greeting"')
        ->toContain('data-twig-inline-branch="then"')
        ->toContain('data-twig-inline-branch="else"');
});

it('round trips nested inline Twig conditions through HTML', function () {
    $editor = new Editor([
        'extensions' => [
            new StarterKit,
            new TwigInlineIfExtension,
        ],
        'content' => <<<'HTML'
            <p><span data-twig-inline-if data-twig-inline-condition-id="visible-user" data-twig-inline-branch="then" data-twig-condition="user.visible">Hello <span data-twig-inline-if data-twig-inline-condition-id="active-user" data-twig-inline-branch="then" data-twig-condition="user.active">member</span></span></p>
            HTML,
    ]);

    $marks = $editor->getDocument()['content'][0]['content'][1]['marks'];

    expect($marks)
        ->toHaveCount(2)
        ->and($marks[0]['attrs']['conditionId'])
        ->toBe('visible-user')
        ->and($marks[1]['attrs']['conditionId'])
        ->toBe('active-user')
        ->and($editor->getHTML())
        ->toContain('data-twig-inline-condition-id="visible-user"')
        ->toContain('data-twig-inline-condition-id="active-user"');
});
