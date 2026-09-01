# Razor for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/razor.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/razor)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/razor.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/razor)

Razor manages reusable document templates in Filament and renders persisted document snapshots from application records. Applications define document sections and context, while Razor provides template editing, numbering, version history, Twig and Handlebars rendering, and related-record document management.

## Features

- Filament resource for creating, previewing, activating, and versioning templates.
- Application-defined template sections with custom variables, forms, previews, and document factories.
- Built-in Twig and Handlebars rendering engines.
- Sequence-backed document numbering through `phpinnacle/sequentia`.
- Persisted document snapshots with parent documents and issue, signature, and expiry dates.
- Reusable `ManageDocuments` page for Eloquent record resources.
- Optional tenancy and policy-backed template management.

## Requirements

- PHP 8.4 or later
- Laravel 13
- Filament 5

## Installation

```bash
composer require phpinnacle/razor
php artisan vendor:publish --tag="phpinnacle-razor-migrations"
php artisan migrate
```

Publish the configuration when the user model, navigation, or tenancy defaults need to change:

```bash
php artisan vendor:publish --tag="phpinnacle-razor-config"
```

## Registering document sections

A section connects templates to an application record and defines how a `Document` is created:

```php
use App\Models\Order;
use PHPinnacle\Razor\Models\Document;
use PHPinnacle\Razor\Models\Section;
use PHPinnacle\Razor\RazorPlugin;

$panel->plugin(
    RazorPlugin::make()->sections(
        Section::make('Orders')
            ->key('orders')
            ->render(function (Order $record, array $data) {
                return new Document([
                    ...$data,
                    'holder_type' => $record->getMorphClass(),
                    'holder_id' => $record->getKey(),
                    'entity_type' => $record->getMorphClass(),
                    'entity_id' => $record->getKey(),
                    'context' => ['order' => $record->toArray()],
                ]);
            }),
    ),
);
```

The section key is stored on each template and must remain stable. Use `form()` to collect section-specific document data, `variables()` to describe editor variables, and `preview()` when template authors should render a preview with sample data.

## Adding documents to a resource

The owner model exposes documents through a polymorphic relationship:

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PHPinnacle\Razor\Models\Document;

public function documents(): MorphMany
{
    return $this->morphMany(Document::class, 'holder');
}
```

Add a resource page by extending `ManageDocuments` and returning the section keys supported by that resource:

```php
use App\Filament\Resources\Orders\OrderResource;
use PHPinnacle\Razor\Pages\ManageDocuments;

final class ManageOrderDocuments extends ManageDocuments
{
    protected static string $resource = OrderResource::class;

    protected function getSections(): array
    {
        return ['orders'];
    }
}
```

Register the page in the resource's `getPages()` array. The page creates a document from an active template, renders it once with the document context, and stores the resulting HTML snapshot.

## Templates and rendering

Registering `RazorPlugin` adds the Templates resource to the panel. Twig templates have access to the document context, the Twig Intl extension, and a `money` filter backed by `phpinnacle/money`. Handlebars templates include a `date` helper. Updating template content creates a version-history record; existing documents keep their previously rendered content.

The bundled `documents.show` route renders stored document HTML without escaping it. Treat template authors as trusted, add appropriate access control for document URLs, and use a Twig sandbox or equivalent restrictions before accepting templates from untrusted users.

## Development

Run the repository checks from the monorepo root:

```bash
composer lint
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
