<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class DescriptionListExtension extends Node
{
    /**
     * @var string
     */
    public static $name = 'descriptionList';

    /**
     * @return array<string, mixed>
     */
    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function parseHTML(): array
    {
        return [
            ['tag' => 'dl'],
        ];
    }

    /**
     * @param  object  $node
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<mixed>
     */
    public function renderHTML($node, $HTMLAttributes = []): array
    {
        return [
            'dl',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
