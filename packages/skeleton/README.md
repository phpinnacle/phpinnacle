# PHPinnacle Filament Plugin Skeleton

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/skeleton.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/skeleton)

Skeleton is the starting template used to build PHPinnacle Filament plugins. It demonstrates the repository layout, automatic Laravel package discovery, Spatie Package Tools configuration and a minimal Filament plugin implementation.

## Included structure

- `SkeletonServiceProvider` for package configuration.
- `SkeletonPlugin` implementing Filament's `Plugin` contract.
- Placeholders for configuration, migrations, views, stubs and tests.
- Package metadata, changelog and MIT license.

## Starting a new package

Copy the package into its own repository, then replace all occurrences of:

- `skeleton` with the package's kebab-case name;
- `Skeleton` with the package's StudlyCase name;
- `PHPinnacle\\Skeleton` with the final PHP namespace.

Update `composer.json`, the service provider, the plugin ID, Packagist badges and repository links before publishing the package.

## Plugin registration

The generated plugin follows the standard Filament registration pattern:

```php
use Filament\Panel;
use PHPinnacle\Skeleton\SkeletonPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(SkeletonPlugin::make());
}
```

Add resources, pages, widgets and panel assets in `SkeletonPlugin::register()`. Add application boot logic in `boot()`. Package-level views, configuration and migrations belong in `SkeletonServiceProvider::configurePackage()`.

## Development

Install dependencies and run the package test suite:

```bash
composer install
composer test
```

Format PHP changes with Mago according to the consuming repository's conventions.

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).
