# Signum for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/signum.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/signum)

Signum adds persistent, record-scoped alerts to Filament. Alerts carry a severity, title and description, may belong to an application record, and can be displayed or dismissed through packaged Filament components and actions.

## Features

- Polymorphic alerts attached to Eloquent records.
- Levels: note, info, success, warning and danger.
- Filament `Alerts` schema component.
- `AlertAction` for creating alerts and `DismissAction` for dismissal.
- `AlertResource` and policy-backed management.
- Configurable user model, connection and tenancy.

## Installation

```bash
composer require phpinnacle/signum
php artisan vendor:publish --tag="phpinnacle-signum-migrations"
php artisan migrate
```

Register `SignumPlugin::make()` in the target Filament panel. Publish `phpinnacle-signum-config` when the default `App\\Models\\User`, database connection or tenancy settings do not fit the application.

## Creating and displaying alerts

```php
use PHPinnacle\Signum\Enums\Level;
use PHPinnacle\Signum\Models\Alert;

Alert::create($order, [
    'level' => Level::Warning,
    'title' => 'Manual review required',
    'description' => 'The delivery address could not be verified.',
]);
```

Add `Alerts::make()` to a Filament schema to render alerts for its current record. Use `AlertAction::make()` where users may create one and `DismissAction::make()` where dismissal is allowed. `AlertPolicy` remains the authority for those operations.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
