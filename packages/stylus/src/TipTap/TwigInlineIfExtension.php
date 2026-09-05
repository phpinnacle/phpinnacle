<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Mark;
use Tiptap\Utils\HTML;

class TwigInlineIfExtension extends Mark
{
    public static $name = 'twigInlineIf';

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
            'conditionId' => [
                'default' => null,
                'parseHTML' => static function ($domNode) {
                    $conditionId = $domNode->getAttribute('data-twig-inline-condition-id');

                    return $conditionId === '' ? null : $conditionId;
                },
                'renderHTML' => function ($attributes) {
                    $conditionId = is_array($attributes)
                        ? $attributes['conditionId'] ?? null
                        : $attributes->conditionId ?? null;

                    return (
                        is_string($conditionId) && $conditionId !== ''
                            ? ['data-twig-inline-condition-id' => $conditionId]
                            : []
                    );
                },
            ],
            'branch' => [
                'default' => null,
                'parseHTML' => static function ($domNode) {
                    $branch = $domNode->getAttribute('data-twig-inline-branch');

                    return $branch === '' ? null : $branch;
                },
                'renderHTML' => function ($attributes) {
                    $branch = is_array($attributes)
                        ? $attributes['branch'] ?? null
                        : $attributes->branch ?? null;

                    return (
                        is_string($branch) && $branch !== ''
                            ? ['data-twig-inline-branch' => $branch]
                            : []
                    );
                },
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function addOptions(): array
    {
        return ['HTMLAttributes' => []];
    }

    /** @return array<int, array<string, mixed>> */
    public function parseHTML(): array
    {
        return [['tag' => 'span[data-twig-inline-if]']];
    }

    /**
     * @param  object  $mark
     * @param  array<string, mixed>  $htmlAttributes
     * @return array<mixed>
     */
    public function renderHTML($mark, $htmlAttributes = []): array
    {
        return [
            'span',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $htmlAttributes, ['data-twig-inline-if' => 'true']),
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
