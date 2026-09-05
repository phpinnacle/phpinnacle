# Comments for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/comments.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/comments)

Comments provides polymorphic discussion threads for Eloquent records. It includes a Livewire thread, Filament action and infolist entry, comment policy, rich-text toolbar configuration and automatic pruning support. Mentions require Filament 4.5 or later.

## Features

- Comments attached to any Eloquent model through a morph relation.
- Livewire list and creation form.
- Comment editing and one-level contextual replies.
- Thread subscriptions and direct mentions.
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

Subscriptions and mentions use Laravel database notifications. The configured user model must use Laravel's `Notifiable` trait and expose a `name` attribute. Run `php artisan make:notifications-table` before migrating when the application does not already have a notifications table.

## Filament usage

```php
use PHPinnacle\Comments\Actions\CommentsAction;
use PHPinnacle\Comments\Infolists\CommentsEntry;

CommentsAction::make();

CommentsEntry::make('comments');
```

Authorization is delegated to `CommentPolicy`. Its `create`, `update`, and `delete` abilities control the corresponding thread actions; use `update` to limit editing to comment authors. Replies quote one root comment rather than forming an arbitrarily deep tree. If pruning is desired, schedule Laravel's `model:prune` command.

The infolist entry is hidden for guests and checks the `viewAny` ability for authenticated users.

## Testing

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
