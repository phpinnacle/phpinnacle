# Babylon for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/babylon.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/babylon)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/babylon.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/babylon)

Babylon adds locale selection to Filament panels. It keeps each panel's selected locale in the session, applies it through panel middleware, and provides a ready-to-use language selector for the Filament profile form.

## Features

- Per-panel session storage.
- Configurable user locale resolution.
- First-visit locale detection from the browser's Accept-Language header.
- Configurable list of available locales.
- Locale selector for the user profile schema.
- Locale names supplied by PHP's Intl extension.
- Native Filament language and text-direction support.
- No database tables or published assets.

## Requirements

- PHP 8.4 or later
- PHP Intl extension
- Laravel 13
- Filament 5

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

The plugin registers `SetLocale` as persistent panel middleware. It resolves the locale from the current panel's session, an optional user locale callback, the browser language, and finally Laravel's configured application locale. Browser and switcher values are limited to the locales passed to `locales()`.

Session and profile state keys include the Filament panel ID, so changing one panel does not affect another. Existing global `locale` session values remain readable as a fallback.

Filament uses the resolved application locale for the document language and its own locale metadata for text direction, including right-to-left languages.

## Resolving a saved user locale

Configure a callback when the application persists locale preferences. The callback receives the authenticated user and the current plugin instance:

```php
use Illuminate\Contracts\Auth\Authenticatable;
use PHPinnacle\Babylon\BabylonPlugin;
use PHPinnacle\Settings\Models\Preference;

BabylonPlugin::make()
    ->locales('en', 'pl', 'ru')
    ->userLocaleUsing(
        fn (Authenticatable $user, BabylonPlugin $plugin) => Preference::get(
            $user,
            $plugin->getPreferenceGroup(),
            BabylonPlugin::PREFERENCE_KEY,
        ),
    );
```

This keeps storage optional; Babylon does not require `phpinnacle/settings`.

## Adding the profile selector

Use the packaged section when composing a custom profile form:

```php
use PHPinnacle\Babylon\BabylonPlugin;

return [
    BabylonPlugin::profile(),
];
```

The selector only contains locales passed to `locales()`. Locale codes should be valid ICU locale identifiers. The containing profile form remains responsible for persistence.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).
