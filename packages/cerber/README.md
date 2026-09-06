# Cerber for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/cerber.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/cerber)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/cerber.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/cerber)

Cerber is the authentication and authorization layer for PHPinnacle Filament applications. It provides users, roles and permissions, OAuth providers, API tokens, impersonation, profile management and Filament resources as one configurable panel plugin.

## Features

- User, role and OAuth provider Filament resources.
- Database-backed roles and permissions with grouped Filament resource, page and widget permissions.
- Google, Yandex, GitHub and Facebook Socialite provider definitions.
- OAuth account linking and optional domain-based auto-registration.
- Optional default role for newly registered OAuth users.
- Laravel Sanctum token support.
- User impersonation via `laravel-impersonate`.
- Configurable profile form, developer identities, guards and permission scopes.
- Optional tenancy and custom database connection.

## Requirements and installation

- PHP 8.4 or later
- Laravel 13, Filament 5, Sanctum 4 and Socialite 5
- `phpinnacle/common`

```bash
composer require phpinnacle/cerber
php artisan vendor:publish --tag="phpinnacle-cerber-migrations"
php artisan migrate
```

Publish configuration when customizing navigation, permissions, OAuth registration or storage:

```bash
php artisan vendor:publish --tag="phpinnacle-cerber-config"
```

## Registering the plugin

```php
use PHPinnacle\Cerber\AuthProvider;
use PHPinnacle\Cerber\CerberPlugin;

$panel->plugin(
    CerberPlugin::make()
        ->authProviders(
            AuthProvider::google(),
            AuthProvider::github(),
        )
        ->scopes(Order::class)
        ->developers(['developer@example.com']),
);
```

Use `withoutProviders()`, `withoutRoles()`, `withoutUsers()` or `withoutResources()` when the application supplies that UI itself. `modifyProfileForm()` receives the Filament schema and profile page for application-specific fields.

`developers()` accepts a list of email addresses or an email-to-label map; `getDevelopers()` returns the configured map. Developer login is available only outside production and requires an existing user with access to the panel and requested tenant. The `DeveloperLogin` service handles authentication and session regeneration; `CerberPlugin::auth()` delegates to it.

## Roles and permissions

```php
use PHPinnacle\Cerber\Models\Permission;

$permission = Permission::register('orders.view');
$role->grant($permission);

if ($role->able('orders.view')) {
    // The role grants this permission.
}
```

Applications may implement `HasCustomPermissions` to contribute permission definitions. The `exclude` configuration removes selected Filament pages, widgets or resources from generated permission groups; `permissions` and `translations` customize the resulting catalog.

## OAuth configuration

Create enabled provider records through the Providers resource and configure the corresponding Socialite credentials. `allowed_domains` controls which email domains may auto-register, and `default_role` assigns an initial role. Provider settings are cached using `cache.enabled`, `cache.ttl` and `cache.key`.

Treat provider secrets and access tokens as credentials. Store environment-specific values securely, restrict provider administration and verify callback URLs in each provider console.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
