# PHPinnacle Common

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)

Common provides shared Laravel and Filament utilities that have not yet moved into focused PHPinnacle packages.

## Features

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

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
