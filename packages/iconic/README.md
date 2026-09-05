# PHPinnacle Iconic

`phpinnacle/iconic` provides a searchable Filament icon picker backed by the Phosphor icon set.

## Installation

```bash
composer require phpinnacle/iconic
```

## Usage

```php
use PHPinnacle\Iconic\IconPicker;

IconPicker::make('icon');

IconPicker::make('icon')
    ->columns(8)
    ->rows(4);

IconPicker::make('icon')
    ->iconSets(['phosphor-icons', 'heroicons'])
    ->weights(['regular', 'bold'])
    ->allowIcons(['phosphor-heart', 'phosphor-heart-bold', 'heroicon-o-heart'])
    ->excludeIcons(['phosphor-heart-bold']);
```

The picker displays six columns and five rows by default. Use `columns()` and `rows()` to adjust the grid for the available form width.

It discovers the `phosphor-icons` Blade icon set and its regular weight by default. Use `iconSets()` to select registered Blade icon sets and `weights()` to include any of the `regular`, `bold`, `duotone`, `fill`, `light`, or `thin` Phosphor weights. `allowIcons()` limits the picker to an application allow-list, while `excludeIcons()` removes individual icons. Each method also accepts a closure returning the list.

Icon discovery reuses the Blade Icons manifest, while labels are rendered only for the requested page. The first grid page is loaded when the picker opens, and more icons are loaded as the user scrolls. Searching starts again from the first matching page, so forms never embed the complete icon catalog. Favorites and the five most recently selected icons are stored in the browser and placed first when available.

## License

The MIT License (MIT). See [License File](LICENSE.md).
