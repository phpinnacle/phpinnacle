<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class ConditionBlockExtension extends Node
{
    public static $name = 'conditionBlock';

    public static $priority = 110;

    /** @return array<string, array<string, mixed>> */
    public function addAttributes(): array
    {
        return [
            'condition' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('data-condition') ?: null,
                'renderHTML' => $this->renderConditionAttribute(...),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'div[data-condition]',
                'priority' => 100,
            ],
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
            'div',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes),
            0,
        ];
    }

    /** @return array{data-condition: string}|null */
    private function renderConditionAttribute(mixed $attributes): ?array
    {
        $condition = is_array($attributes)
            ? $attributes['condition'] ?? null
            : $attributes->condition ?? null;

        return (
            is_string($condition) && $condition !== ''
                ? ['data-condition' => $condition]
                : null
        );
    }
}
