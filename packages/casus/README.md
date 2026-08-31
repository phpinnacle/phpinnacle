# Casus for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/casus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/casus)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/casus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/casus)

Casus is an application exception recorder and Filament exception browser. It stores reportable failures with request context and gives authorized panel users a searchable interface for inspecting stack traces and request data.

## Features

- Automatic integration with Laravel's exception reporting pipeline.
- Captures exception class, message, code, file, line, trace and occurrence count.
- Captures HTTP method, URL, IP address, request headers and request body when available.
- Groups repeated failures and updates their latest occurrence.
- Filament resource with syntax-highlighted exception details.
- Configurable retention using Laravel's prunable model support.
- Optional separate database connection and tenant association.
- Policy-backed view and delete permissions.

## Requirements

- PHP 8.4 or later
- Laravel 13
- Filament 5

## Installation

```bash
composer require phpinnacle/casus
php artisan vendor:publish --tag="phpinnacle-casus-migrations"
php artisan migrate
```

Publish the configuration when you need to customize navigation, retention, connection or tenancy:

```bash
php artisan vendor:publish --tag="phpinnacle-casus-config"
```

## Registering the plugin

```php
use Filament\Panel;
use PHPinnacle\Casus\CasusPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(CasusPlugin::make());
}
```

Casus registers the Exceptions resource in that panel. Access is controlled by `ExceptionPolicy`; your authenticated user must support the permissions expected by the application.

## Configuration

```php
return [
    'navigation' => [
        'exception' => ['icon' => 'phosphor-bug', 'sort' => 1000],
    ],
    'prune' => 30,
    'connection' => null,
    'tenancy' => null,
];
```

- `prune` is the number of days retained by the model's prunable query.
- `connection` selects a database connection for exception records; `null` uses Laravel's default.
- `tenancy` may define a tenant model and default tenant identifier.

Schedule Laravel's model pruning command if automatic retention is required:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();
```

Never expose the resource to untrusted users: request bodies, headers and traces can contain sensitive operational data.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).
