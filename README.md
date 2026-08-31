# PHPinnacle Packages

This repository is the source of truth for the PHPinnacle package collection. Each directory under `packages/` is an independently releasable Composer package with its own dependencies, tests, and documentation.

## Packages

| Package | Description |
| --- | --- |
| [`phpinnacle/atlas`](packages/atlas/) | Configurable analytics and dashboard builder for Filament with governed datasets, tables, charts, and statistics. |
| [`phpinnacle/babylon`](packages/babylon/) | Filament panel localization with session middleware, locale selection, and profile integration. |
| [`phpinnacle/cabeiro`](packages/cabeiro/) | Visual Filament table builder that stores renderer-independent schemas for columns, filters, groups, and summaries. |
| [`phpinnacle/casus`](packages/casus/) | Application exception recorder and Filament browser for failures, request context, and recurring exceptions. |
| [`phpinnacle/cerber`](packages/cerber/) | Filament authentication and authorization for users, roles, permissions, OAuth, API tokens, and impersonation. |
| [`phpinnacle/chronica`](packages/chronica/) | Auditable Eloquent activity history and Filament timelines built on Spatie Activitylog. |
| [`phpinnacle/comments`](packages/comments/) | Polymorphic discussion threads with Livewire and Filament integrations, policies, and pruning. |
| [`phpinnacle/common`](packages/common/) | Shared Laravel and Filament utilities, including ranges, filters, columns, and reusable concerns. |
| [`phpinnacle/dendros`](packages/dendros/) | Hierarchical Eloquent and Filament building blocks using materialized paths and tree-aware interfaces. |
| [`phpinnacle/iconic`](packages/iconic/) | Searchable Filament icon picker backed by Phosphor icons. |
| [`phpinnacle/intl`](packages/intl/) | Symfony Intl-backed country, language, locale, and timezone dictionaries with Laravel and Filament integration. |
| [`phpinnacle/langcat`](packages/langcat/) | Framework-agnostic PHP client for the LangLion API with typed requests and responses. |
| [`phpinnacle/manuscript`](packages/manuscript/) | Browser-based DOCX editor with a framework-neutral engine and a thin Filament field adapter. |
| [`phpinnacle/minos`](packages/minos/) | Laravel and Filament payment orchestration for methods, plans, cards, intents, adjustments, and providers. |
| [`phpinnacle/money`](packages/money/) | Integer-based money value object with currency metadata, formatting, Eloquent, Livewire, and Filament support. |
| [`phpinnacle/openapi`](packages/openapi/) | Converts OpenAPI schema objects into Laravel validation rules. |
| [`phpinnacle/palette`](packages/palette/) | Color palette utilities and a Filament color picker supporting Tailwind, Filament, and custom sources. |
| [`phpinnacle/pinax`](packages/pinax/) | Polymorphic Filament media library with uploads, galleries, folders, ordering, and marks. |
| [`phpinnacle/porta`](packages/porta/) | Webhook integration management for Filament with transforms, queued processing, retries, and handler registration. |
| [`phpinnacle/razor`](packages/razor/) | Template-driven document generation for Filament with Twig and Handlebars engines, previews, and history. |
| [`phpinnacle/recens`](packages/recens/) | Tracks recently visited Filament pages per user and renders a recent-items menu. |
| [`phpinnacle/rosetta`](packages/rosetta/) | Framework-independent reader and mapper for 1C:Enterprise infobase metadata. |
| [`phpinnacle/sequentia`](packages/sequentia/) | Atomic scoped, bucketed, and date-aware sequence numbers for Laravel models. |
| [`phpinnacle/settings`](packages/settings/) | Registry-driven Filament settings with groups, nested navigation, persistence, and per-user preferences. |
| [`phpinnacle/signum`](packages/signum/) | Persistent record-scoped alerts with severity, actions, policies, and Filament components. |
| [`phpinnacle/skeleton`](packages/skeleton/) | Starter template for PHPinnacle Filament plugins. |
| [`phpinnacle/stylus`](packages/stylus/) | Filament rich editor extensions for semantic HTML, description lists, conditions, and structured Twig templates. |
| [`phpinnacle/tags`](packages/tags/) | Reusable polymorphic tagging with Filament input, column, filter, and bulk assignment components. |
| [`phpinnacle/tempo`](packages/tempo/) | Date and time utilities, Filament pickers and filters, clocks, calendars, and recurrence expansion. |
| [`phpinnacle/vulcan`](packages/vulcan/) | Visual Filament schema builder with a component palette, nested canvas, drag-and-drop, resizing, and native rendering. |

## Licensing

Atlas, Cabeiro, Manuscript, and Vulcan are proprietary commercial packages. Unless an order states otherwise, their licenses grant perpetual use of eligible versions for one production application and include 12 months of updates. Each package contains the complete license terms that apply after it is split from this repository.

All other packages are governed by the license declared in their own directory under `packages/`.

## Development

Work inside the package that owns the behavior and use its README and `composer.json` for package-specific setup and verification. Consumer applications should install these packages through Composer and must not modify copies under `vendor/`.

Install the monorepo development tools and run the PHP checks from the repository root:

```bash
composer install
composer lint
composer test
```

`composer test` runs all package PHPUnit and Pest suites.

Run `composer format` to apply the repository's Mago formatting rules.
