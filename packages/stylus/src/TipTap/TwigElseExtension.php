<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class TwigElseExtension extends Node
{
    public static $name = 'twigElse';

    /** @return array<int, array<string, mixed>> */
    public function parseHTML(): array
    {
        return [['tag' => 'div[data-twig-branch="else"]']];
    }

    /**
     * @param  object  $node
     * @param  array<string, mixed>  $htmlAttributes
     * @return array<mixed>
     */
    public function renderHTML($node, $htmlAttributes = []): array
    {
        return ['div', HTML::mergeAttributes($htmlAttributes, ['data-twig-branch' => 'else']), 0];
    }
}
