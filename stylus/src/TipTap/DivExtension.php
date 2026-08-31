<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class DivExtension extends Node
{
    public static $name = 'div';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function parseHTML(): array
    {
        return [
            ['tag' => 'div'],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        return [
            'div',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
