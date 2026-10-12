# PHPinnacle Common

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)

Common provides shared Laravel and Filament utilities that have not yet moved into focused PHPinnacle packages.

## Features

- ItemPicker form fields with inline or custom dropdown layouts, grouped trees, multiple selection, badges and images.
- Immutable-style `Range` utilities.
- Range, active and combined table filters.
- Standard active, creator, created, updated and default table columns.
- Reusable creator, slug, page badge and refresh concerns.

## Requirements and installation

- PHP 8.4
- Laravel 13, Livewire 4 and Filament 5

```bash
composer require phpinnacle/common
```

## Filament components

```php
use PHPinnacle\Common\Tables\CreatedColumn;

CreatedColumn::make();
```

### Item picker

```php
use PHPinnacle\Common\Enums\ItemPickerMode;
use PHPinnacle\Common\Forms\Item;
use PHPinnacle\Common\Forms\ItemBadge;
use PHPinnacle\Common\Forms\ItemGroup;
use PHPinnacle\Common\Forms\ItemPicker;

ItemPicker::make('assignees')
    ->mode(ItemPickerMode::Select)
    ->multiple()
    ->compact()
    ->dense()
    ->circular()
    ->branches(false)
    ->items([
        ItemGroup::make('team')->children([
            Item::make('anna')
                ->label('Anna')
                ->description('Administrator')
                ->image('/avatars/anna.jpg')
                ->badges([ItemBadge::make('Available')->color('success')]),
        ]),
    ]);
```

`compact()` puts item and group descriptions on the label line. `dense()` independently reduces spacing and visual sizes. `circular()` rounds images and icon backgrounds. Images take precedence over icons. These options, `multiple()` and `branches()` accept boolean conditions or closures. Multiple dropdowns show complete selected chips and summarize overflow as `+N`.

Item and group factories derive labels with `Str::title()`. Single selection stores a string or `null`; multiple selection stores a list of strings. The field validates availability, disabled items and branch selection. Its Blade views, styles, translations and lazy Alpine component are registered by Common without requiring a panel plugin or frontend build.

Publish package assets after installation or changes:

```bash
php artisan filament:assets
```

## Testing

```bash
composer test
node --test tests/item-picker.test.js
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
