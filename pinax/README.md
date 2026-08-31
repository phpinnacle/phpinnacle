# Pinax for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/pinax.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/pinax)

Pinax is a media library for Filament applications. It stores uploaded file metadata in a polymorphic `media` table and provides gallery, upload and infolist components with folders, ordering and semantic marks.

## Features

- Polymorphic media attachments for any Eloquent model.
- Configurable filesystem disk, base directory and database connection.
- `MediaUpload` for a single upload workflow.
- `MediaGallery` for multiple ordered files.
- Named `Mark` values such as cover, primary or featured, including unique/default behavior.
- `MediaEntry` for image display in Filament schemas.
- Optional tenancy and policy-backed media management.

## Installation

```bash
composer require phpinnacle/pinax
php artisan vendor:publish --tag="phpinnacle-pinax-migrations"
php artisan migrate
```

For the default public disk, ensure its storage link exists:

```bash
php artisan storage:link
```

Register `PinaxPlugin::make()` in the Filament panel. Publish `phpinnacle-pinax-config` to change `upload.disk`, `upload.folder`, `connection` or tenancy.

## Gallery usage

```php
use PHPinnacle\Pinax\Forms\MediaGallery;
use PHPinnacle\Pinax\Mark;

MediaGallery::make('gallery')
    ->disk('public')
    ->directory('products')
    ->marks(
        Mark::make('cover')->label('Cover')->unique()->default(),
        Mark::make('featured')->label('Featured'),
    );
```

The `Media` model also exposes `fetch()`, `store()`, `clear()`, `one()`, `single()` and `folder()` for application-level access. Public URLs require a disk capable of generating accessible URLs.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
