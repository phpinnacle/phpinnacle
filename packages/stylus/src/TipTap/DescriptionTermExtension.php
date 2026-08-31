<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class DescriptionTermExtension extends Node
{
    /**
     * @var string
     */
    public static $name = 'descriptionTerm';

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
            ['tag' => 'dt'],
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
            'dt',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }
}
