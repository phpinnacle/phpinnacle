<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class ArticleExtension extends Node
{
    public static $name = 'article';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function parseHTML(): array
    {
        return [
            ['tag' => 'article'],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        return [
            'article',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
