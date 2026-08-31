# PHPinnacle Palette

`phpinnacle/palette` provides color palette utilities for Laravel Filament applications.

## Installation

```bash
composer require phpinnacle/palette
```

## Usage

```php
use PHPinnacle\Palette\Color;
use PHPinnacle\Palette\ColorFormat;
use PHPinnacle\Palette\ColorSource;
use PHPinnacle\Palette\Forms\ColorPicker;

$random = Color::random();
$palette = Color::shades('#2563eb');
$hex = Color::hex(Color::Blue);
$primary = Color::resolve('primary');
$styles = Color::styles(Color::Blue);

ColorPicker::make('color')
    ->enable(ColorSource::Tailwind, ColorSource::Filament)
    ->shades(min: 100, max: 900)
    ->wide()
    ->format(ColorFormat::Rgba);
```

`Color::DEFAULT` contains the standard Filament semantic color scheme, while `Color::SCHEME` contains its supported names.

`ColorPicker` uses only the custom color source and stores HEX by default. Add or remove sources with `enable(ColorSource ...$sources)` and `disable(ColorSource ...$sources)`. `shades(int $min = 50, int $max = 950)` keeps the standard Tailwind shades within the inclusive boundaries.

The popover sizes itself to the selected Tailwind shade range. Call `wide()` to make it match the input width.

The storage format may be `ColorFormat::Hex`, `ColorFormat::Rgba`, or `ColorFormat::Hsl`. RGBA fields also expose opacity and preserve their alpha channel. `ColorFormat::Semantic` uses only the Filament source and stores its registered alias, such as `primary`, `success`, or `danger`, instead of converting it to a color value:

```php
ColorPicker::make('color')
    ->format(ColorFormat::Semantic);
```

## License

The MIT License (MIT). See [License File](LICENSE.md).
