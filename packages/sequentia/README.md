# Sequentia for Laravel

Sequentia generates scoped, bucketed and date-aware sequence numbers for Laravel models. Counter updates use an atomic database upsert and can be isolated by tenant, model scope, bucket and time period.

## Installation

```bash
composer require phpinnacle/sequentia
php artisan phpinnacle-sequentia:install
```

For manual installation:

```bash
php artisan vendor:publish --tag="phpinnacle-sequentia-config"
php artisan vendor:publish --tag="phpinnacle-sequentia-migrations"
php artisan migrate
```

## Registering counters

Register watched Eloquent models from an application service provider:

```php
use App\Models\Order;
use PHPinnacle\Sequentia\SequenceWatcher;

SequenceWatcher::register(Order::class, scheme: ['issuer_type', 'issuer_id']);
```

Every created model increments all-time, yearly and monthly counters. Bucketed counters are isolated by the configured scheme; global counters ignore it and count each record once per tenant and model scope, even when multiple schemes are registered. Pass a model attribute name as `tenant` to isolate counters by tenant. Repeated registration of the same scheme replaces its definition without adding another listener. Registrations belong to the current Eloquent event dispatcher; register them again when booting a new application or replacing that dispatcher.

## Formatting numbers

```php
use PHPinnacle\Sequentia\Sequence;

$number = Sequence::create(
    pattern: 'ORD-[DD][MM][YY]-[SY]',
    scope: Order::class,
    bucket: ['issuer_type' => 'company', 'issuer_id' => 10],
)->forTenant($tenantId)->get();
```

Available counters are `SA`, `SY`, `SM`, `GA`, `GY` and `GM`: scoped/global counters for all time, year and month. Date placeholders are `D`, `DD`, `M`, `MM`, `Y`, `YY` and `W`. Additional context may be passed to `get()`.

`Sequence::get()` reads the stored values plus one. It does not reserve a number; concurrent callers may read the same prospective value.

## Rebuilding counters

Rebuild registered counters from existing records when necessary:

```bash
php artisan sequentia:rebuild
```

Before rebuilding, pause writes to watched models and direct `SequenceWatcher::store()` calls, drain active requests and queue jobs, and prevent another rebuild from starting. Keep writes paused until the command finishes. Rebuild does not acquire locks on source models and does not support concurrent writes, including when counters use a separate connection. Normal creation events still use atomic increments.

Rebuild aggregates records returned by each registered model's query, including its global scopes, then replaces the resulting counter keys in one transaction. Repeating it over unchanged data gives the same values. It preserves keys absent from that result, including other scopes, tenants, buckets and periods; it does not clear counters for deleted records or removed schemes when their keys are no longer represented. Historical hashes do not identify which scheme owns a missing bucket, so the command cannot safely infer those deletions. Aggregate memory use grows with the number of distinct counter keys.

Rebuilding reads the model's `CREATED_AT` attribute as a `DateTimeInterface`. Standard Eloquent timestamps provide this automatically, including renamed timestamp columns. If automatic timestamps are disabled, declare a date cast for that attribute. Models without a creation date should set `CREATED_AT` to `null`; rebuilding uses the current period for those records.

## Configuration

Set `connection` in `phpinnacle-sequentia.php` to store counters on a dedicated database connection. The current atomic upsert implementation requires PostgreSQL.

## Testing

From the monorepo root, run `vendor/bin/pest packages/sequentia/tests` for SQLite coverage. To run the same suite plus concurrent increment verification on PostgreSQL, use a dedicated empty test database:

```bash
SEQUENTIA_PGSQL_URL=postgres://user:password@127.0.0.1:5432/sequentia_test \
    vendor/bin/pest packages/sequentia/tests
```

The suite creates and drops `sequences` and `sequence_records`. In a standalone package checkout, use `vendor/bin/pest tests` instead.

## License

The MIT License (MIT). See [License File](LICENSE.md).
