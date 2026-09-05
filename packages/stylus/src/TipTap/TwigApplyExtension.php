<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class TwigApplyExtension extends Node
{
    /**
     * @var string
     */
    public static $name = 'twigApply';

    /** @return array<int, array<string, mixed>> */
    public function parseHTML(): array
    {
        return [['tag' => 'section[data-twig-apply]']];
    }

    /** @return array<string, array<string, mixed>> */
    public function addAttributes(): array
    {
        return [
            'filters' => [
                'default' => [],
                'parseHTML' => fn ($domNode) => $this->parseFilters(
                    $domNode->getAttribute('data-twig-apply-filters'),
                ),
                'renderHTML' => fn ($attributes) => [
                    'data-twig-apply-filters' => json_encode(
                        is_array($attributes)
                            ? $attributes['filters'] ?? []
                            : $attributes->filters ?? [],
                        JSON_THROW_ON_ERROR,
                    ),
                ],
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
            HTML::mergeAttributes($htmlAttributes, ['data-twig-apply' => '']),
            0,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function parseFilters(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $filters = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Twig apply filters must be valid JSON.', previous: $exception);
        }

        if (!is_array($filters) || !array_is_list($filters)) {
            throw new InvalidArgumentException('Twig apply filters must be a list.');
        }

        return $filters;
    }
}
