<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class SectionExtension extends Node
{
    public static $name = 'section';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function parseHTML(): array
    {
        return [
            ['tag' => 'section'],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        return [
            'section',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
