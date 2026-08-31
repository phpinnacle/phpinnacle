# Babylon for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/babylon.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/babylon)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/babylon.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/babylon)

Babylon adds locale selection to a Filament panel. It keeps the selected locale in the session, applies it through panel middleware, and provides a ready-to-use language selector for the Filament profile form.

## Features

- Panel-scoped locale middleware.
- Configurable list of available locales.
- Locale selector for the user profile schema.
- Locale names supplied by `phpinnacle/common`.
- No database tables or published assets.

## Requirements

- PHP 8.4 or later
- Laravel 13
- Filament 5
- `phpinnacle/common`

## Installation

```bash
composer require phpinnacle/babylon
```

## Registering the plugin

Register `BabylonPlugin` in the panel and pass the locales that users may select:

```php
use Filament\Panel;
use PHPinnacle\Babylon\BabylonPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(
        BabylonPlugin::make()->locales('en', 'pl', 'ru'),
    );
}
```

The plugin registers `SetLocale` as persistent panel middleware. The selected value is read from the session key `locale`; when it is absent, Laravel's configured application locale remains in effect.

## Adding the profile selector

Use the packaged section when composing a custom profile form:

```php
use PHPinnacle\Babylon\BabylonPlugin;

return [
    BabylonPlugin::profile(),
];
```

The selector only contains locales passed to `locales()`. Locale codes should be valid entries supported by `PHPinnacle\Intl\Locales`.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).
