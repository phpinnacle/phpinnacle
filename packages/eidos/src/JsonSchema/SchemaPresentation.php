<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use PHPinnacle\Jason\Schema;

use function Filament\Support\generate_icon_html;

final class SchemaPresentation
{
    /**
     * @param array<string, mixed>|bool $definition
     * @return array{definition: array<string, mixed>|bool, propertyTitles: array<string, string>, icons: array<string, string>}
     */
    public static function render(array|bool $definition, bool $withIcons = true): array
    {
        $titles = [];
        $icons = [];

        Schema::fromArray($definition)
            ->resolveReferences()
            ->walk(function (Schema $schema, ?string $keyword, string|int|null $key) use (
                &$titles,
                &$icons,
                $withIcons,
            ) {
                if ($keyword === 'properties') {
                    $propertyName = (string) $key;
                    $titles[$propertyName] = Str::apa(Str::headline($propertyName));
                }

                $attributes = $schema->keywords();
                if (array_key_exists('x-icon', $attributes)) {
                    self::validateIcon($attributes['x-icon']);
                }
                if (array_key_exists('x-color', $attributes)) {
                    self::validateColor($attributes['x-color']);
                }
                $iconName = $attributes['x-icon'] ?? null;

                if (!$withIcons || $iconName === null || array_key_exists($iconName, $icons)) {
                    return;
                }

                /** @var Htmlable $icon */
                $icon = generate_icon_html(
                    $iconName,
                    attributes: new ComponentAttributeBag(['aria-hidden' => 'true']),
                    size: IconSize::Small,
                );
                $icons[$iconName] = $icon->toHtml();
            });

        return ['definition' => $definition, 'propertyTitles' => $titles, 'icons' => $icons];
    }

    private static function validateIcon(mixed $icon): void
    {
        if (!is_string($icon) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $icon)) {
            throw new InvalidArgumentException('The JSON Schema [x-icon] must be a Blade icon name.');
        }
    }

    private static function validateColor(mixed $color): void
    {
        $number = '[+-]?(?:\\d+(?:\\.\\d+)?|\\.\\d+)%?';
        $commaRgb = $number . '\\s*,\\s*' . $number . '\\s*,\\s*' . $number . '(?:\\s*,\\s*' . $number . ')?';
        $spaceRgb = $number . '\\s+' . $number . '\\s+' . $number . '(?:\\s*/\\s*' . $number . ')?';
        $pattern =
            '~^(?:[a-z][a-z0-9_-]*|\\#(?:[a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})|rgba?\\(\\s*(?:'
            . $commaRgb
            . '|'
            . $spaceRgb
            . ')\\s*\\))$~iD';

        if (!is_string($color) || !preg_match($pattern, $color)) {
            throw new InvalidArgumentException(
                'The JSON Schema [x-color] must be a Filament color name, HEX or RGB/RGBA color.',
            );
        }
    }
}
