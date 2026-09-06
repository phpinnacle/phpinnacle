# Settings for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/settings.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/settings)

Settings provides a registry-driven Filament settings page with groups, nested navigation and persistent values. It supports application settings through `SettingsStorage` and per-user preferences through the `Preference` model.

## Features

- Multiple settings groups defined with fluent `Definition` objects.
- Filament schemas for each group and optional parent-child navigation.
- Central `DefinitionRegistry` with a deterministic default group.
- Persistent setting sections loaded into typed settings objects.
- Per-user JSON preferences.
- Configurable user model, navigation and database connection.

## Installation

```bash
composer require phpinnacle/settings
php artisan vendor:publish --tag="phpinnacle-settings-migrations"
php artisan migrate
```

## Registering definitions

```php
use Filament\Forms\Components\TextInput;
use PHPinnacle\Settings\Definition;
use PHPinnacle\Settings\SettingsPlugin;

$panel->plugin(
    SettingsPlugin::make()->definitions(
        Definition::make(GeneralSettings::class)
            ->label('General')
            ->slug('general')
            ->icon('phosphor-gear')
            ->sort(10)
            ->schema([
                TextInput::make('site_name')->required(),
            ]),
    ),
);
```

`Definition::parent()` groups a definition below another definition. A definition class may also provide its own form contract; the explicit `schema()` method is convenient for small groups.

## Storage API

`SettingsStorage` exposes `register()`, `fill()`, `load()` and `save()` for application setting sections. `Preference::get()`, `retrieve()` and `store()` provide the equivalent user-scoped API. Values are stored as JSON and keyed by group and key.

Publish `phpinnacle-settings-config` to change navigation, connection or the authenticatable user model.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
