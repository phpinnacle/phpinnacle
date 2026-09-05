<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class TwigIfExtension extends Node
{
    public static $name = 'twigIf';

    /** @return array<string, mixed> */
    public function addOptions(): array
    {
        return ['HTMLAttributes' => []];
    }

    /** @return array<int, array<string, mixed>> */
    public function parseHTML(): array
    {
        return [['tag' => 'section[data-twig-if]']];
    }

    /** @return array<string, array<string, mixed>> */
    public function addAttributes(): array
    {
        return [
            'condition' => [
                'default' => '',
                'parseHTML' => fn ($domNode) => $domNode->getAttribute('data-twig-condition') ?? '',
                'renderHTML' => fn ($attributes) => [
                    'data-twig-condition' => is_array($attributes)
                        ? $attributes['condition'] ?? ''
                        : $attributes->condition ?? '',
                ],
            ],
            'conditionAst' => [
                'default' => null,
                'parseHTML' => fn ($domNode) => $this->parseConditionAst(
                    $domNode->getAttribute('data-twig-condition-ast'),
                ),
                'renderHTML' => function ($attributes) {
                    $conditionAst = is_array($attributes)
                        ? $attributes['conditionAst'] ?? null
                        : $attributes->conditionAst ?? null;

                    return (
                        $conditionAst === null
                            ? []
                            : ['data-twig-condition-ast' => json_encode($conditionAst, JSON_THROW_ON_ERROR)]
                    );
                },
            ],
        ];
    }

    /**
     * @param  object  $node
     * @param  array<string, mixed>  $htmlAttributes
     * @return array<mixed>
     */
    public function renderHTML($node, $htmlAttributes = []): array
    {
        return [
            'section',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $htmlAttributes, ['data-twig-if' => '']),
            0,
        ];
    }

    /** @return array<string, mixed>|null */
    private function parseConditionAst(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $conditionAst = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Twig condition AST must be valid JSON.', previous: $exception);
        }

        if (!is_array($conditionAst) || array_is_list($conditionAst)) {
            throw new InvalidArgumentException('Twig condition AST must be an object.');
        }

        return $conditionAst;
    }
}
