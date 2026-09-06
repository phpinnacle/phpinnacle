# Recens for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/recens.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/recens)

Recens records pages visited by each authenticated Filament user and renders a compact recent-items menu. Applications decide which pages are recorded and may customize the stored title, icon, group and URL.

## Features

- Recent page history isolated by user, Filament panel and configured tenant.
- Pinned pages with stable ordering, protected from automatic pruning.
- Menu actions to pin, unpin, remove an entry and clear ordinary history.
- Panel render hook for displaying recent entries.
- Configurable icon, color and number of entries.
- Page-specific recorder callbacks and scoped recording.
- Automatic pruning with configurable retention.
- Optional database connection and tenancy.

## Installation

```bash
composer require phpinnacle/recens
php artisan vendor:publish --tag="phpinnacle-recens-migrations"
php artisan migrate
```

Register and configure the plugin:

```php
use PHPinnacle\Recens\RecensPlugin;

$panel->plugin(
    RecensPlugin::make()
        ->limit(8)
        ->icon('phosphor-clock-counter-clockwise')
        ->color('gray'),
);
```

Use `scopes()` to limit recording to the intended page groups. Each panel owns its `Recorder` registrations, so callbacks remain separate when a long-lived application switches panels or handles another request. The recorder records matching Filament pages after navigation and ignores Livewire update requests. `Recent::list()` returns entries for the authenticated user of the current panel, using that panel's auth guard.

Publish `phpinnacle-recens-config` to set the user model, connection, tenancy and pruning behavior. When pruning is enabled, schedule Laravel's `model:prune` command.

## Pins and history controls

The menu shows pinned pages first, in the order they were pinned. Revisiting or pinning an already pinned page does not move it. The star button pins or unpins a page; the remove button deletes that entry, including a pin. Visiting a removed page records it again.

`limit(8)` limits ordinary recent entries; all pinned pages appear above them. **Clear history** removes all ordinary entries in the current panel and tenant, including entries beyond the visible limit, and keeps pinned pages. Every menu action checks the authenticated user's ownership in the current panel and tenant. Guests cannot read or change history.

The model also exposes these operations for application code:

```php
use PHPinnacle\Recens\Models\Recent;

$entry = Recent::forCurrentContext()->findOrFail($id);
$entry->pin();
$entry->unpin();
$entry->delete();
Recent::clearHistory();
```

Resolve entries through `Recent::forCurrentContext()` before exposing model operations to users. `Recent::forUser($userId)` is available for explicit model queries; it filters only ownership, so callers handling panel or tenant boundaries must add those constraints or use `Recent::forCurrentContext()`.

## Identity, panels and tenancy

`Recent::record(array $data)` remains available. URLs are canonicalized by removing their query string, as before. Entry IDs include the user, panel, configured tenant and canonical URL; a repeat visit updates only that user's matching entry. Outside a current Filament panel, the model uses the default auth guard and the unassigned (`null`) panel.

When `tenancy.model` is configured, the active Filament tenant ID is stored and used for both reads and writes. If there is no active tenant, `tenancy.default` supplies the tenant ID. Configure the actual default identifier, and keep Filament's normal tenant access middleware enabled. The `tenant_id` column must already exist from the package's tenancy-enabled installation. Changing the tenancy schema of an existing installation requires an application migration.

Pruning excludes pinned entries and honors `prune.enabled` and `prune.days`. Schedule `model:prune` to prune old ordinary entries across users and panels.

## Upgrading to 1.2

Publish and run the additional migration before serving requests with the updated package:

```bash
php artisan vendor:publish --tag="phpinnacle-recens-migrations"
php artisan migrate
```

Use the application's normal maintenance deployment process while this migration runs. It adds `panel_id` and `pinned_at` and replaces existing URL-only IDs with IDs containing the stored owner and tenant. Existing records and timestamps are retained, but `panel_id` stays `null`: the old schema cannot identify their original panel. Those entries are not shown inside a panel and age out through ordinary pruning; revisiting a page creates a panel-specific entry. There is no runtime fallback that claims an old row for another user. The configured database connection is used for the migration and model queries.

Rollback removes the new columns; it does not restore the unsafe URL-only IDs. Downgrading code to the old ownership model is not supported by this migration.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
