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

The filter uses the table model's morph class to load available tags, including any registered morph alias. Use `TagsFilter::make()->model($type)` to explicitly select the type stored in `tags.type`.

The tagged model must expose the relationship expected by these components. Follow the relation naming and morph contract used by `Tag` and the package migration when integrating an existing model. Authorization for tag records is handled by `TagPolicy`.

Tag definitions and assignments use `phpinnacle-tags.connection`, including bulk transactions and reads/removals. Tagged models may live on another connection: bulk assignment checks selected IDs on their source connection and writes only to the tag database. Deleted source records are skipped, repeat assignment is idempotent, and a failed bulk operation rolls back its tag definitions and assignments together.

## Testing

```bash
composer test
```

To exercise tag writes on PostgreSQL while source records remain on another connection, use a dedicated test database:

```bash
TAGS_PGSQL_URL=postgresql://user:password@localhost/test_database vendor/bin/pest packages/tags/tests --no-coverage
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
