<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class TwigVariableExtension extends Node
{
    public static $name = 'twigVariable';

    /** @return array<string, array<string, mixed>> */
    public function addAttributes(): array
    {
        return [
            'expression' => [
                'default' => '',
                'parseHTML' => fn ($domNode) => $domNode->getAttribute('data-twig-expression') ?? '',
                'renderHTML' => fn ($attributes) => [
                    'data-twig-expression' => $this->attribute($attributes, 'expression', ''),
                ],
            ],
            'filters' => [
                'default' => [],
                'parseHTML' => fn ($domNode) => $this->parseFilters($domNode->getAttribute('data-twig-filters')),
                'renderHTML' => fn ($attributes) => [
                    'data-twig-filters' => json_encode(
                        $this->attribute($attributes, 'filters', []),
                        JSON_THROW_ON_ERROR,
                    ),
                ],
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
        return [['tag' => 'span[data-twig-variable]']];
    }

    /**
     * @param  object  $node
     * @param  array<string, mixed>  $htmlAttributes
     * @return array<mixed>
     */
    public function renderHTML($node, $htmlAttributes = []): array
    {
        $expression = $node->attrs->expression ?? '';

        return [
            'span',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $htmlAttributes, [
                'data-twig-variable' => '',
                'contenteditable' => 'false',
            ]),
            "{{ {$expression} }}",
        ];
    }

    private function attribute(mixed $attributes, string $name, mixed $default): mixed
    {
        return is_array($attributes)
            ? $attributes[$name] ?? $default
            : $attributes->{$name} ?? $default;
    }

    /** @return list<array{name: string, arguments: list<string>}> */
    private function parseFilters(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $filters = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Twig variable filters must be valid JSON.', previous: $exception);
        }

        if (!is_array($filters) || !array_is_list($filters)) {
            throw new InvalidArgumentException('Twig variable filters must be a list.');
        }

        return $filters;
    }
}
