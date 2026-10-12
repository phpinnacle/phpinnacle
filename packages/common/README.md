# PHPinnacle Common

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)

Common provides shared Laravel and Filament utilities that have not yet moved into focused PHPinnacle packages.

## Features

- ItemPicker form fields with inline or custom dropdown layouts, grouped trees, server search, scroll loading, multiple selection, badges and images.
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

Enable server search with `searchable()`. Its boolean or closure controls whether the search input is available. The `items()` callback receives a nullable `$search` and the current `$state`, alongside the usual Filament utility injections. Initial loading and clearing the search pass `null`. The callback owns filtering, ordering and access restrictions; the field does not filter search results automatically.

Set `limit(50)` to enable loading additional portions when scrolling. The default is `null`, with one unbounded result. When a limit is configured, the callback also receives `$offset` (starting at `0`) and `$limit`. Apply these to the source query; the field does not truncate callback results. Each successful portion advances the offset by the limit, and `[]` ends loading. Changing the search starts again at offset `0`. Static arrays are paginated by their top-level items.

Use `resolve()` to retrieve selected items outside the current search portion:

```php
ItemPicker::make('assignees')
    ->mode(ItemPickerMode::Select)
    ->multiple()
    ->searchable()
    ->limit(50)
    ->items(fn (?string $search, int $offset, int $limit) => $directory->searchItems($search, $offset, $limit))
    ->resolve(fn (?array $state, array $items) => $directory->selectedItems($state, $items));
```

The resolver runs after `items()` and receives the current `$state` and the returned `$items`. It returns `list<Item>` in both single and multiple modes, using already loaded items or looking up missing selections. Its results display the selection and determine valid values without being appended to the search list or affecting offsets. Inline selections already loaded in the list are highlighted there. Missing selections are added as selectable cards before the results. These cards remain in the current list after deselection or loading further portions, so choosing an item does not move other cards; duplicate results are hidden. When a branch loads, its full tree row replaces the resolved card so its children remain accessible. Changing the search rebuilds the list using the current selection. This applies to each selected value in multiple mode. Return `[]` for an empty selection and omit unavailable values. Disabled items and forbidden branches remain invalid. Apply the same access restrictions to search and resolution. Validation uses the initial portion with `$search = null` and the current state. Static arrays resolve selections automatically; callbacks without a resolver use their current portion.

Callbacks returning trees must retain the parent context needed to display matches and identify branches. Search results expand their branches to reveal matching descendants.

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
