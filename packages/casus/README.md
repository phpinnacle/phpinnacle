# Casus for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/casus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/casus)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/casus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/casus)

Casus is an application exception recorder and Filament exception browser. It stores reportable failures with request context and gives authorized panel users a searchable interface for inspecting stack traces and request data.

## Features

- Automatic integration with Laravel's exception reporting pipeline.
- Captures exception class, message, code, file, line, trace and occurrence count.
- Captures HTTP method, URL, IP address, request headers and request body when available.
- Redacts configurable sensitive request values before persistence.
- Groups repeated failures by fingerprint and tracks their occurrence count.
- Supports open, acknowledged and resolved triage states with an internal note.
- Optionally sends mail notifications for new failures and a repeat threshold.
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

Casus registers the Exceptions resource in that panel. Access is controlled by `ExceptionPolicy`; `view`, `update`, and `delete` govern browsing, triage, and deletion.

## Configuration

```php
return [
    'navigation' => [
        'exception' => ['icon' => 'phosphor-bug', 'sort' => 1000],
    ],
    'prune' => 30,
    'connection' => null,
    'redaction' => [
        'replacement' => '[hidden]',
        'keys' => [
            'authorization',
            'cookie',
            'csrf_token',
            'credential',
            'password',
            'remember',
            'secret',
            'session',
            'token',
        ],
    ],
    'notifications' => [
        'mail' => null,
        'repeat_threshold' => null,
    ],
    'tenancy' => null,
];
```

- `prune` is the number of days retained by the model's prunable query.
- `connection` selects a database connection for exception records; `null` uses Laravel's default.
- `redaction.keys` contains case-insensitive key fragments removed from headers, cookies, query parameters, and supported JSON or form bodies before storage.
- `notifications.mail` enables Laravel mail notifications at the given address. New fingerprints are always sent; `repeat_threshold` sends one additional notification when the occurrence count reaches that value.
- `tenancy` may define a tenant model and default tenant identifier.

Schedule Laravel's model pruning command if automatic retention is required:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();
```

Never expose the resource to untrusted users: exception context, opaque request bodies, and traces can still contain sensitive operational data.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).
