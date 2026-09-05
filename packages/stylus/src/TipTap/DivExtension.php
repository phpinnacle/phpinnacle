<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class DivExtension extends Node
{
    /**
     * @var string
     */
    public static $name = 'div';

    /**
     * @return array{HTMLAttributes: array<string, mixed>}
     */
    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    /**
     * @return list<array{tag: string}>
     */
    public function parseHTML(): array
    {
        return [
            ['tag' => 'div'],
        ];
    }

    /**
     * @param object $node
     * @param array<string, mixed> $HTMLAttributes
     * @return array{string, array<string, mixed>, int}
     */
    public function renderHTML($node, $HTMLAttributes = []): array
    {
        return [
            'div',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
