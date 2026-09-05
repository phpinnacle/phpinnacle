<?php

namespace PHPinnacle\Stylus\TipTap;

use InvalidArgumentException;
use JsonException;
use Tiptap\Core\Extension;

class TwigTableConditionExtension extends Extension
{
    public static $name = 'twigTableCondition';

    /** @return array<int, array<string, mixed>> */
    public function addGlobalAttributes(): array
    {
        return [[
            'types' => ['tableRow'],
            'attributes' => [
                'twigCondition' => $this->stringAttribute('data-twig-row-condition'),
                'twigConditionId' => $this->stringAttribute('data-twig-row-condition-id'),
                'twigConditionAst' => [
                    'default' => null,
                    'parseHTML' => fn ($domNode) => $this->parseConditionAst(
                        $domNode->getAttribute('data-twig-row-condition-ast'),
                    ),
                    'renderHTML' => function ($attributes) {
                        $conditionAst = is_array($attributes)
                            ? $attributes['twigConditionAst'] ?? null
                            : $attributes->twigConditionAst ?? null;

                        return (
                            $conditionAst === null
                                ? []
                                : ['data-twig-row-condition-ast' => json_encode($conditionAst, JSON_THROW_ON_ERROR)]
                        );
                    },
                ],
            ],
        ]];
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
            throw new InvalidArgumentException(
                'Twig table row condition AST must be valid JSON.',
                previous: $exception,
            );
        }

        if (!is_array($conditionAst) || array_is_list($conditionAst)) {
            throw new InvalidArgumentException('Twig table row condition AST must be an object.');
        }

        return $conditionAst;
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
                $name = $htmlName === 'data-twig-row-condition'
                    ? 'twigCondition'
                    : 'twigConditionId';
                $value = is_array($attributes)
                    ? $attributes[$name] ?? null
                    : $attributes->{$name} ?? null;

                return filled($value) ? [$htmlName => $value] : [];
            },
        ];
    }
}
