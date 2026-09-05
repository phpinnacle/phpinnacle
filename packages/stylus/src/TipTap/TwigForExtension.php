<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class TwigForExtension extends Node
{
    public static $name = 'twigFor';

    /** @return array<string, mixed> */
    public function addOptions(): array
    {
        return ['HTMLAttributes' => []];
    }

    /** @return array<int, array<string, mixed>> */
    public function parseHTML(): array
    {
        return [['tag' => 'section[data-twig-for]']];
    }

    /** @return array<string, array<string, mixed>> */
    public function addAttributes(): array
    {
        return [
            'item' => $this->stringAttribute('data-twig-item'),
            'key' => $this->stringAttribute('data-twig-key', null),
            'iterable' => $this->stringAttribute('data-twig-iterable'),
            'transforms' => [
                'default' => [],
                'parseHTML' => fn ($domNode) => $this->parseTransforms(
                    $domNode->getAttribute('data-twig-transforms'),
                ),
                'renderHTML' => fn ($attributes) => [
                    'data-twig-transforms' => json_encode(
                        is_array($attributes)
                            ? $attributes['transforms'] ?? []
                            : $attributes->transforms ?? [],
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
            HTML::mergeAttributes($this->options['HTMLAttributes'], $htmlAttributes, ['data-twig-for' => '']),
            0,
        ];
    }

    /** @return array<string, mixed> */
    private function stringAttribute(string $htmlName, ?string $default = ''): array
    {
        return [
            'default' => $default,
            'parseHTML' => static function ($domNode) use ($default, $htmlName) {
                $value = $domNode->getAttribute($htmlName);

                return $value === null || $value === '' ? $default : $value;
            },
            'renderHTML' => function ($attributes) use ($htmlName) {
                $name = match ($htmlName) {
                    'data-twig-item' => 'item',
                    'data-twig-key' => 'key',
                    default => 'iterable',
                };
                $value = is_array($attributes)
                    ? $attributes[$name] ?? null
                    : $attributes->{$name} ?? null;

                return filled($value) ? [$htmlName => $value] : [];
            },
        ];
    }

    /** @return list<array<string, mixed>> */
    private function parseTransforms(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $transforms = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Twig collection transforms must be valid JSON.', previous: $exception);
        }

        if (!is_array($transforms) || !array_is_list($transforms)) {
            throw new InvalidArgumentException('Twig collection transforms must be a list.');
        }

        return $transforms;
    }
}
