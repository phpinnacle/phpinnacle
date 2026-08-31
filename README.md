# PHPinnacle Packages

This repository is the source of truth for the PHPinnacle package collection. Each top-level directory is an independently releasable Composer package with its own dependencies, tests, and documentation.

## Packages

| Package | Description |
| --- | --- |
| [`phpinnacle/atlas`](atlas/) | Configurable analytics and dashboard builder for Filament with governed datasets, tables, charts, and statistics. |
| [`phpinnacle/babylon`](babylon/) | Filament panel localization with session middleware, locale selection, and profile integration. |
| [`phpinnacle/cabeiro`](cabeiro/) | Visual Filament table builder that stores renderer-independent schemas for columns, filters, groups, and summaries. |
| [`phpinnacle/casus`](casus/) | Application exception recorder and Filament browser for failures, request context, and recurring exceptions. |
| [`phpinnacle/cerber`](cerber/) | Filament authentication and authorization for users, roles, permissions, OAuth, API tokens, and impersonation. |
| [`phpinnacle/chronica`](chronica/) | Auditable Eloquent activity history and Filament timelines built on Spatie Activitylog. |
| [`phpinnacle/comments`](comments/) | Polymorphic discussion threads with Livewire and Filament integrations, policies, and pruning. |
| [`phpinnacle/common`](common/) | Shared Laravel and Filament utilities, including ranges, filters, columns, and reusable concerns. |
| [`phpinnacle/dendros`](dendros/) | Hierarchical Eloquent and Filament building blocks using materialized paths and tree-aware interfaces. |
| [`phpinnacle/iconic`](iconic/) | Searchable Filament icon picker backed by Phosphor icons. |
| [`phpinnacle/intl`](intl/) | Symfony Intl-backed country, language, locale, and timezone dictionaries with Laravel and Filament integration. |
| [`phpinnacle/langcat`](langcat/) | Framework-agnostic PHP client for the LangLion API with typed requests and responses. |
| [`phpinnacle/manuscript`](manuscript/) | Browser-based DOCX editor with a framework-neutral engine and a thin Filament field adapter. |
| [`phpinnacle/minos`](minos/) | Laravel and Filament payment orchestration for methods, plans, cards, intents, adjustments, and providers. |
| [`phpinnacle/money`](money/) | Integer-based money value object with currency metadata, formatting, Eloquent, Livewire, and Filament support. |
| [`phpinnacle/openapi`](openapi/) | Converts OpenAPI schema objects into Laravel validation rules. |
| [`phpinnacle/palette`](palette/) | Color palette utilities and a Filament color picker supporting Tailwind, Filament, and custom sources. |
| [`phpinnacle/pinax`](pinax/) | Polymorphic Filament media library with uploads, galleries, folders, ordering, and marks. |
| [`phpinnacle/porta`](porta/) | Webhook integration management for Filament with transforms, queued processing, retries, and handler registration. |
| [`phpinnacle/razor`](razor/) | Template-driven document generation for Filament with Twig and Handlebars engines, previews, and history. |
| [`phpinnacle/recens`](recens/) | Tracks recently visited Filament pages per user and renders a recent-items menu. |
| [`phpinnacle/rosetta`](rosetta/) | Framework-independent reader and mapper for 1C:Enterprise infobase metadata. |
| [`phpinnacle/sequentia`](sequentia/) | Atomic scoped, bucketed, and date-aware sequence numbers for Laravel models. |
| [`phpinnacle/settings`](settings/) | Registry-driven Filament settings with groups, nested navigation, persistence, and per-user preferences. |
| [`phpinnacle/signum`](signum/) | Persistent record-scoped alerts with severity, actions, policies, and Filament components. |
| [`phpinnacle/skeleton`](skeleton/) | Starter template for PHPinnacle Filament plugins. |
| [`phpinnacle/stylus`](stylus/) | Filament rich editor extensions for semantic HTML, description lists, conditions, and structured Twig templates. |
| [`phpinnacle/tags`](tags/) | Reusable polymorphic tagging with Filament input, column, filter, and bulk assignment components. |
| [`phpinnacle/tempo`](tempo/) | Date and time utilities, Filament pickers and filters, clocks, calendars, and recurrence expansion. |
| [`phpinnacle/vulcan`](vulcan/) | Visual Filament schema builder with a component palette, nested canvas, drag-and-drop, resizing, and native rendering. |

## Licensing

Atlas, Cabeiro, Manuscript, and Vulcan are proprietary commercial packages. Unless an order states otherwise, their licenses grant perpetual use of eligible versions for one production application and include 12 months of updates. Each package contains the complete license terms that apply after it is split from this repository.

All other packages are governed by the license declared in their own package directory.

## Development

Work inside the package that owns the behavior and use its README and `composer.json` for package-specific setup and verification. Consumer applications should install these packages through Composer and must not modify copies under `vendor/`.
