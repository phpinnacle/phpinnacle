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
```

The picker displays six columns and five rows by default. Use `columns()` and `rows()` to adjust the grid for the available form width.

It discovers registered Blade icon sets, selects regular Phosphor icons, excludes alternate weights, and caches the resulting options. The first grid page is loaded when the picker opens, and more icons are loaded as the user scrolls. Searching starts again from the first matching page, so forms never embed the complete icon catalog.

## License

The MIT License (MIT). See [License File](LICENSE.md).
