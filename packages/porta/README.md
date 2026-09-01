# Porta for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/porta.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/porta)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/porta.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/porta)

Porta receives JSON webhooks through integrations managed in Filament. Each integration controls authentication, response behavior, and payload transforms before an accepted webhook is dispatched to an application-defined handler through Laravel's queue.

## Features

- Filament resource for configuring and testing webhook integrations.
- Header, query-string, or unauthenticated webhook endpoints.
- JSONPath-based mapping, dropping, moving, casting, trimming, date conversion, renaming, and insertion.
- Configurable empty, identity-only, or webhook-detail responses and HTTP status codes.
- Queued processing with persisted status, error details, retry actions, and delivery history.
- Handler registry for routing transformed payloads into application code.
- Optional tenancy and policy-backed integration management.

## Requirements

- PHP 8.4 or later
- Laravel 13
- Filament 5
- A queue worker when using an asynchronous queue connection

## Installation

```bash
composer require phpinnacle/porta
php artisan vendor:publish --tag="phpinnacle-porta-migrations"
php artisan migrate
```

Publish the configuration when the navigation or tenancy defaults need to change:

```bash
php artisan vendor:publish --tag="phpinnacle-porta-config"
```

## Registering handlers

Register the plugin and every accepted webhook type in the target panel:

```php
use App\Jobs\SyncContact;
use PHPinnacle\Porta\Handler;
use PHPinnacle\Porta\PortaPlugin;

$panel->plugin(
    PortaPlugin::make()->handle(
        new Handler(
            type: 'crm.contact.updated',
            label: 'CRM contact updated',
            invoker: function (array $payload) {
                SyncContact::dispatch($payload);
            },
            example: '{"contact":{"id":"example-id"}}',
        ),
    ),
);
```

The invoker receives the transformed payload. Handler types are stable identifiers: an integration selects one type, and the queued job resolves that type from the registry when processing begins.

## Receiving webhooks

Create an integration in Filament, select a registered handler, and configure its authentication, response, and transforms. The edit page shows the generated endpoint:

```text
POST /webhook/{integration-id}
```

Porta stores the raw body and request headers, then dispatches `ProcessWebhook`. Run the application's queue worker so scheduled webhooks can reach their handlers:

```bash
php artisan queue:work
```

Only JSON payloads are currently supported. Transforms run in their configured order before the handler is invoked. A JSON payload containing `{"test":"test"}` exercises endpoint authentication and returns immediately without creating a webhook record.

Integration access is controlled by `IntegrationPolicy`. Webhook records may contain credentials or personal data from request headers and bodies, so restrict the Filament resource, logs, and database access and define an appropriate retention policy.

## Development

Run the repository checks from the monorepo root:

```bash
composer lint
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
