<?php

namespace PHPinnacle\Palette;

use Spatie\Color\Factory;
use Spatie\Color\Hex;

enum ColorFormat: string
{
    case Hex = 'hex';
    case Rgba = 'rgba';
    case Hsl = 'hsl';
    case Semantic = 'semantic';

    public function hydrate(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

        return match ($this) {
            self::Rgba, self::Hsl => (string) Factory::fromString($state)->toHex(),
            self::Hex, self::Semantic => $state,
        };
    }

    public function dehydrate(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

        return match ($this) {
            self::Rgba => (string) Hex::fromString($state)->toRgba(),
            self::Hsl => (string) Hex::fromString($state)->toHsl(),
            self::Hex, self::Semantic => $state,
        };
    }

    public function supports(ColorSource $source): bool
    {
        return $this !== self::Semantic || $source === ColorSource::Filament;
    }

    /**
     * @param  array<string>  $themeColors
     */
    public function pickerPattern(array $themeColors): string
    {
        if ($this === self::Rgba) {
            return '/^#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?$/';
        }

        if ($this !== self::Semantic) {
            return '/^#[0-9a-fA-F]{6}$/';
        }

        $colors = array_map(
            static fn (string $color) => preg_quote($color, '/'),
            $themeColors,
        );

        return '/^(?:' . implode('|', $colors) . ')$/';
    }
}
