<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class DescriptionDetailsExtension extends Node
{
    /**
     * @var string
     */
    public static $name = 'descriptionDetails';

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
            ['tag' => 'dd'],
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
            'dd',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
