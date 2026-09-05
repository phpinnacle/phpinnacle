<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Extension;

class TwigTableLoopExtension extends Extension
{
    public static $name = 'twigTableLoop';

    /** @return array<int, array<string, mixed>> */
    public function addGlobalAttributes(): array
    {
        return [
            [
                'types' => ['tableRow'],
                'attributes' => $this->loopAttributes(),
            ],
            [
                'types' => ['tableCell', 'tableHeader'],
                'attributes' => [
                    ...$this->loopAttributes(),
                    'twigLoopId' => $this->stringAttribute('data-twig-loop-id'),
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function loopAttributes(): array
    {
        return [
            'twigLoopItem' => $this->stringAttribute('data-twig-loop-item'),
            'twigLoopKey' => $this->stringAttribute('data-twig-loop-key'),
            'twigLoopIterable' => $this->stringAttribute('data-twig-loop-iterable'),
            'twigLoopTransforms' => [
                'default' => [],
                'parseHTML' => fn ($domNode) => $this->parseTransforms(
                    $domNode->getAttribute('data-twig-loop-transforms'),
                ),
                'renderHTML' => fn ($attributes) => [
                    'data-twig-loop-transforms' => json_encode(
                        is_array($attributes)
                            ? $attributes['twigLoopTransforms'] ?? []
                            : $attributes->twigLoopTransforms ?? [],
                        JSON_THROW_ON_ERROR,
                    ),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function stringAttribute(string $htmlName): array
    {
        return [
            'default' => null,
            'parseHTML' => static function ($domNode) use ($htmlName) {
                $value = $domNode->getAttribute($htmlName);

                return $value === '' ? null : $value;
            },
            'renderHTML' => function ($attributes) use ($htmlName) {
                $name = match ($htmlName) {
                    'data-twig-loop-item' => 'twigLoopItem',
                    'data-twig-loop-key' => 'twigLoopKey',
                    'data-twig-loop-iterable' => 'twigLoopIterable',
                    default => 'twigLoopId',
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
