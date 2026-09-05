# Chronica for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/chronica.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/chronica)

Chronica adds an auditable history timeline to Eloquent records in Filament. It builds on `spatie/laravel-activitylog` and provides a packaged activity model, a record concern, timeline descriptors and a Filament action.

## Features

- Polymorphic activity history for Eloquent models.
- `HasHistory` concern for opt-in models.
- `HistoryAction` for Filament record actions.
- Timeline attributes and relations for readable change descriptions.
- Optional custom connection and tenant column.
- Configurable history icon.

## Installation

```bash
composer require phpinnacle/chronica
php artisan vendor:publish --tag="phpinnacle-chronica-migrations"
php artisan migrate
```

Optionally publish configuration:

```bash
php artisan vendor:publish --tag="phpinnacle-chronica-config"
```

## Registering and using history

Chronica is discovered by Laravel automatically, so panel registration is not required. Existing applications may keep their plugin registration for compatibility:

```php
use PHPinnacle\Chronica\ChronicaPlugin;

$panel->plugin(ChronicaPlugin::make());
```

Add the concern to a model that should expose activity history:

```php
use PHPinnacle\Chronica\Concerns\HasHistory;

class Order extends Model
{
    use HasHistory;
}
```

Add the action to a resource page or table using `HistoryAction::make()`. It opens Chronica's native Filament timeline in a slide-over. Chronica stores events in `activity_log`; use `icon`, `connection` and `tenancy` in `phpinnacle-chronica.php` to adapt presentation and persistence.

Call `revertable()` on the timeline to let users with update permission restore the previous values of updated records. Each reversal is recorded as a new activity; created and deleted events cannot be reverted.

Use `Timeline::make(Order::class)->exclude('sort')->hideEmptyValues()` to exclude named attributes and omit null, blank string and empty array values from initial snapshots. False, zero and changes that clear an existing value remain visible.

Format individual values and opt into icons for boolean and null states:

```php
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Chronica\Timeline;
use PHPinnacle\Chronica\Timeline\Attribute;

Timeline::make(Order::class)
    ->revertable()
    ->attribute('country', fn (?string $value) => $value === 'ZZ' ? 'Unknown' : $value)
    ->attributes(
        Attribute::make('published_on')->date(),
        Attribute::make('published_at')->datetime('d.m.Y H:i'),
    )
    ->boolean()
    ->trueIcon(Heroicon::OutlinedCheckBadge)
    ->falseIcon(Heroicon::OutlinedXMark)
    ->trueColor('info')
    ->falseColor('warning')
    ->nullIcon(Heroicon::OutlinedMinusCircle);
```

Boolean icons default to success and danger; the null icon defaults to gray. Calling `trueIcon()`, `falseIcon()`, `trueColor()` or `falseColor()` also enables boolean icons.

Values cast to objects implementing Filament's `HasLabel`, `HasIcon` or `HasColor` contracts are presented automatically.

Calling `date()`, `datetime()` or `time()` without a format uses the corresponding Filament display format. Pass a PHP date format string to override it.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
