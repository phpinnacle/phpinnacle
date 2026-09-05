<?php

namespace PHPinnacle\Stylus\TipTap;

use Tiptap\Core\Mark;
use Tiptap\Utils\HTML;

class ConditionInlineExtension extends Mark
{
    /**
     * @var string
     */
    public static $name = 'conditionInline';

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
            ['tag' => 'span[data-condition]'],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function addAttributes(): array
    {
        return [
            'condition' => [
                'default' => null,
                'parseHTML' => static function ($DOMNode) {
                    $condition = $DOMNode->getAttribute('data-condition');

                    return $condition === '' ? null : $condition;
                },
                'renderHTML' => $this->renderConditionAttribute(...),
            ],
        ];
    }

    /**
     * @param  object  $mark
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<mixed>
     */
    public function renderHTML($mark, $HTMLAttributes = []): array
    {
        return [
            'span',
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
