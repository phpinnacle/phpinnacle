<?php

namespace PHPinnacle\Palette;

use Filament\Support\Colors\Color as ColorHelper;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Arr;
use Spatie\Color\Rgb;

use function Filament\Support\get_color_css_variables;

class Color
{
    public const array Amber = ColorHelper::Amber;

    public const array Blue = ColorHelper::Blue;

    public const array Green = ColorHelper::Green;

    public const array Red = ColorHelper::Red;

    public const array Zinc = ColorHelper::Zinc;

    public const array SHADES = [
        50,
        100,
        200,
        300,
        400,
        500,
        600,
        700,
        800,
        900,
        950,
    ];

    public const array TAILWIND = [
        'slate' => ColorHelper::Slate,
        'gray' => ColorHelper::Gray,
        'zinc' => ColorHelper::Zinc,
        'neutral' => ColorHelper::Neutral,
        'stone' => ColorHelper::Stone,
        'red' => ColorHelper::Red,
        'orange' => ColorHelper::Orange,
        'amber' => ColorHelper::Amber,
        'yellow' => ColorHelper::Yellow,
        'lime' => ColorHelper::Lime,
        'green' => ColorHelper::Green,
        'emerald' => ColorHelper::Emerald,
        'teal' => ColorHelper::Teal,
        'cyan' => ColorHelper::Cyan,
        'sky' => ColorHelper::Sky,
        'blue' => ColorHelper::Blue,
        'indigo' => ColorHelper::Indigo,
        'violet' => ColorHelper::Violet,
        'purple' => ColorHelper::Purple,
        'fuchsia' => ColorHelper::Fuchsia,
        'pink' => ColorHelper::Pink,
        'rose' => ColorHelper::Rose,
    ];

    public const array DEFAULT = [
        'primary' => Color::Blue,
        'warning' => Color::Amber,
        'success' => Color::Green,
        'danger' => Color::Red,
        'gray' => Color::Zinc,
        'info' => Color::Blue,
    ];

    public const array SCHEME = [
        'primary',
        'warning',
        'success',
        'danger',
        'gray',
        'info',
    ];

    private static array $colors = [];

    public static function hex(array $color, int $shade = 500): string
    {
        $value = $color[$shade];

        while (is_int($value)) {
            $value = $color[$value];
        }

        return (string) Rgb::fromString(ColorHelper::convertToRgb($value))->toHex();
    }

    public static function random(): string
    {
        return '#' . str_pad(dechex(random_int(0, 0xFFFFFF)), length: 6, pad_string: '0', pad_type: STR_PAD_LEFT);
    }

    public static function resolve(string $name): string
    {
        if (self::$colors === []) {
            self::$colors = FilamentColor::getColors();
        }

        return self::hex(self::$colors[$name] ?? self::$colors['primary']);
    }

    public static function shades(string $color): array
    {
        return ColorHelper::generateV3Palette($color);
    }

    public static function styles(string|array $color, array $shades = [400, 600]): string
    {
        return Arr::toCssStyles([
            get_color_css_variables($color, $shades),
        ]);
    }
}
