# Dendros for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/dendros.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/dendros)

Dendros supplies Eloquent and Filament building blocks for hierarchical records. It combines database path helpers, ancestor and descendant relations, a tree-aware model concern, tree selection and tree table pages.

## Features

- `AsTree` concern for hierarchical Eloquent models.
- Materialized path value object and database helpers.
- `Ancestors`, `Descendants` and deep has-many relations.
- Filament `NodeTree` form component.
- `TreeTable`, `RecordsTree` and `RelationsTree` UI foundations.
- Integration with Filament Select Tree and Filament Tree Table.

## Installation

```bash
composer require phpinnacle/dendros
php artisan vendor:publish --tag="phpinnacle-dendros-migrations"
php artisan migrate
```

The published migration creates the database helpers required by the path implementation. Review it for compatibility with the application's database engine before running it.

## Model usage

```php
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Dendros\AsTree;

class Category extends Model
{
    use AsTree;
}
```

Use `NodeTree` when a Filament form must select a node, and extend `RecordsTree` or `RelationsTree` for dedicated hierarchy pages. `TreeTable` provides the corresponding hierarchical table configuration.

The model and migration must follow the column contract expected by `AsTree`; inspect the concern and sibling models before adapting an existing table.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
