# Tags for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/tags.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/tags)

Tags adds reusable, polymorphic tagging to Eloquent records and provides the Filament components needed to edit, display, filter and bulk-assign tags.

## Features

- Central `Tag` model with polymorphic record assignments.
- Filament `TagsInput` form field.
- `TagsColumn` for table display.
- `TagsFilter` for narrowing table records.
- `TagsBulkAction` for assigning tags to multiple records.
- Policy-backed tag management, custom connection and optional tenancy.

## Installation

```bash
composer require phpinnacle/tags
php artisan vendor:publish --tag="phpinnacle-tags-migrations"
php artisan migrate
```

Register `TagsPlugin::make()` in the target Filament panel. Publish `phpinnacle-tags-config` when using a non-default database connection or tenant model.

## Filament usage

```php
use PHPinnacle\Tags\Actions\TagsBulkAction;
use PHPinnacle\Tags\Filters\TagsFilter;
use PHPinnacle\Tags\Forms\TagsInput;
use PHPinnacle\Tags\Tables\TagsColumn;

TagsInput::make('tags');

TagsColumn::make('tags');

TagsFilter::make('tags');

TagsBulkAction::make();
```

The tagged model must expose the relationship expected by these components. Follow the relation naming and morph contract used by `Tag` and the package migration when integrating an existing model. Authorization for tag records is handled by `TagPolicy`.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
