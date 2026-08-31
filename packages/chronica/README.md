# Chronica for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/chronica.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/chronica)

Chronica adds an auditable history timeline to Eloquent records in Filament. It builds on `spatie/laravel-activitylog` and provides a packaged activity model, a record concern, timeline descriptors and a Filament action.

## Features

- Polymorphic activity history for Eloquent models.
- `HasHistory` concern for opt-in models.
- `HistoryAction` for Filament record actions.
- Timeline attributes and relations for readable change descriptions.
- Optional custom connection and tenant column.
- Configurable history icon.

## Installation

```bash
composer require phpinnacle/chronica
php artisan vendor:publish --tag="phpinnacle-chronica-migrations"
php artisan migrate
```

Optionally publish configuration:

```bash
php artisan vendor:publish --tag="phpinnacle-chronica-config"
```

## Registering and using history

Chronica is discovered by Laravel automatically, so panel registration is not required. Existing applications may keep their plugin registration for compatibility:

```php
use PHPinnacle\Chronica\ChronicaPlugin;

$panel->plugin(ChronicaPlugin::make());
```

Add the concern to a model that should expose activity history:

```php
use PHPinnacle\Chronica\Concerns\HasHistory;

class Order extends Model
{
    use HasHistory;
}
```

Add the action to a resource page or table using `HistoryAction::make()`. It opens Chronica's native Filament timeline in a slide-over. Chronica stores events in `activity_log`; use `icon`, `connection` and `tenancy` in `phpinnacle-chronica.php` to adapt presentation and persistence.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
