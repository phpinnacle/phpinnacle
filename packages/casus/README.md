# Casus for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/casus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/casus)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/casus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/casus)

Casus is an application exception recorder and Filament exception browser. It stores reportable failures with request context and gives authorized panel users a searchable interface for inspecting stack traces and request data.

## Features

- Automatic integration with Laravel's exception reporting pipeline.
- Optional endpoint for reporting browser errors, including their original JavaScript stack traces.
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

The initial migration creates the complete exception schema, including fingerprints, occurrence counts and triage fields.

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
    'frontend' => [
        'enabled' => false,
        'rate_limits' => [
            'global' => [
                1 => 20,
                10 => 100,
                60 => 300,
            ],
            'per_ip' => [
                1 => 3,
                10 => 10,
                60 => 30,
            ],
        ],
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

## Reporting browser errors

Enable `frontend.enabled` in `config/phpinnacle-casus.php` to register `POST /casus/errors` (route name `phpinnacle-casus.frontend.report`). Rebuild the application's route cache after changing this setting.

The endpoint accepts guests and authenticated users through Laravel's `web` middleware. Keep request forgery protection enabled and include the page's CSRF token when reporting errors. Configure independent request windows through `frontend.rate_limits.global` (all visitors) and `frontend.rate_limits.per_ip` (each IP address). Each map entry is `window duration in seconds => maximum requests`; both values must be positive integers. You can change the durations and thresholds, or remove individual windows. Every configured window must allow a request; throttled reports are not persisted. Requests with invalid payloads also consume the budget.

The defaults are:

| Scope | 1 second | 10 seconds | 60 seconds |
| --- | ---: | ---: | ---: |
| All visitors | 20 | 100 | 300 |
| Each IP address | 3 | 10 | 30 |

These are Laravel's fixed-duration windows, starting when their counters are first hit and expiring independently. A short-window reset does not clear a longer-window counter. Limits are checked in configuration order, first global and then per-IP windows. When several windows are exhausted, `Retry-After` reflects the first exhausted window; a later request may still be limited by another window.

The limits use Laravel's rate limiter cache store. All application instances must share that store (for example, Redis) for the global budget to cover the whole deployment. Configure Laravel's trusted proxies correctly so the IP limit uses the visitor's address. These limits apply only to frontend ingestion; PHP exception reporting has its existing behavior.

Import the package's JavaScript reporter into `resources/js/app.js` and build the application's frontend assets:

```js
import { reportBrowserError } from '../../vendor/phpinnacle/casus/resources/js/frontend-errors.js';

window.addEventListener('error', (event) => {
    reportBrowserError(event.error || new Error(event.message), {
        file: event.filename,
        line: event.lineno,
        column: event.colno,
    }).catch(() => {});
});

window.addEventListener('unhandledrejection', (event) => {
    reportBrowserError(event.reason || new Error('Unhandled promise rejection')).catch(() => {});
});
```

Render `<meta name="csrf-token" content="{{ csrf_token() }}">` in the application's Blade layout. The example keeps reporting failures from becoming new unhandled promise rejections.

On `429`, the reporter pauses until `Retry-After` expires. It accepts seconds or an HTTP date, and uses a 60-second pause if the header is missing or invalid. Errors occurring during the pause are discarded, without queuing or retrying the rejected report. Later errors are sent normally after the pause. The pause applies to this module instance in the current page; server limits cover other tabs and clients.

`type` and `message` are required strings. Optional fields are `file`, `line`, `column`, `trace`, and `path`. `type`, `file`, and `path` accept up to 255 characters; `message` and `trace` accept up to 16,000 characters. Line and column values must be integers between 0 and 2,147,483,647. The page path must start with `/` and exclude query parameters and fragments. Unknown fields are ignored; the raw report body is not stored.

Successful reports return `204`, invalid payloads return `422`, and throttled requests return `429` with a `Retry-After` header in seconds. Browser failures appear in the existing exception resource with `sapi` set to `browser` and the column in context. Identical type, message, file, line, and column values share one record across pages and preserve its triage state. Existing redaction rules apply to captured request metadata; messages and stack traces are stored as supplied.

## Testing

```bash
composer test
node --test tests/JavaScript/frontend-errors.test.mjs
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).
