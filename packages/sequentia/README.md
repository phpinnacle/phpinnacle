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

Every created model increments annual, yearly and monthly counters. Bucketed counters are isolated by the configured scheme; global counters ignore it. Pass a model attribute name as `tenant` to isolate counters by tenant.

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

Rebuild registered counters from existing records when necessary:

```bash
php artisan sequentia:rebuild
```

## Configuration

Set `connection` in `phpinnacle-sequentia.php` to store counters on a dedicated database connection. The current atomic upsert implementation requires PostgreSQL.

## License

The MIT License (MIT). See [License File](LICENSE.md).
