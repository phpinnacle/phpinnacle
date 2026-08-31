# Comments for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/comments.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/comments)

Comments provides polymorphic discussion threads for Eloquent records. It includes a Livewire thread, Filament action and infolist entry, comment policy, rich-text toolbar configuration and automatic pruning support.

## Features

- Comments attached to any Eloquent model through a morph relation.
- Livewire list and creation form.
- Filament `CommentsAction` modal and `CommentsEntry` infolist component.
- Author association using a configurable user model.
- Policy-backed access and configurable editor toolbar.
- Configurable database connection and retention period.

## Installation

```bash
composer require phpinnacle/comments
php artisan vendor:publish --tag="phpinnacle-comments-migrations"
php artisan migrate
```

Publish configuration or views only when customization is required:

```bash
php artisan vendor:publish --tag="phpinnacle-comments-config"
php artisan vendor:publish --tag="phpinnacle-comments-views"
```

Set `user.model` to the authenticatable model used by the application. `prune` controls retention in days, while `toolbar` controls the enabled formatting buttons.

## Filament usage

```php
use PHPinnacle\Comments\Actions\CommentsAction;
use PHPinnacle\Comments\Infolists\CommentsEntry;

CommentsAction::make();

CommentsEntry::make('comments');
```

Authorization is delegated to `CommentPolicy`. Register or override the policy when the application's access rules differ. If pruning is desired, schedule Laravel's `model:prune` command.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
