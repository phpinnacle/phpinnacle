# Recens for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/recens.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/recens)

Recens records pages visited by each authenticated Filament user and renders a compact recent-items menu. Applications decide which pages are recorded and may customize the stored title, icon, group and URL.

## Features

- Per-user recent page history.
- Panel render hook for displaying recent entries.
- Configurable icon, color and number of entries.
- Page-specific recorder callbacks and scoped recording.
- Automatic pruning with configurable retention.
- Optional database connection and tenancy.

## Installation

```bash
composer require phpinnacle/recens
php artisan vendor:publish --tag="phpinnacle-recens-migrations"
php artisan migrate
```

Register and configure the plugin:

```php
use PHPinnacle\Recens\RecensPlugin;

$panel->plugin(
    RecensPlugin::make()
        ->limit(8)
        ->icon('phosphor-clock-counter-clockwise')
        ->color('gray'),
);
```

Use `scopes()` to limit recording to the intended page groups. The plugin loads registrations into `Recorder`, which records matching Filament pages after navigation. `Recent::list()` returns the current user's prepared navigation entries.

Publish `phpinnacle-recens-config` to set the user model, connection, tenancy and pruning behavior. When pruning is enabled, schedule Laravel's `model:prune` command.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
