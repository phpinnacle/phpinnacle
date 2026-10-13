# PHPinnacle Common

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)

Common provides shared Laravel and Filament utilities that have not yet moved into focused PHPinnacle packages.

## Features

- ItemPicker form fields with inline or custom dropdown layouts, grouped trees, server search, scroll loading, multiple selection, badges and images.
- Immutable-style `Range` utilities.
- Range, active and combined table filters.
- Standard active, creator, created, updated and default table columns.
- Reusable creator, slug, page badge and refresh concerns.

## Requirements and installation

- PHP 8.4
- Laravel 13, Livewire 4 and Filament 5

```bash
composer require phpinnacle/common
```

## Filament components

```php
use PHPinnacle\Common\Tables\CreatedColumn;

CreatedColumn::make();
```

### Item picker

```php
use PHPinnacle\Common\Enums\ItemPickerMode;
use PHPinnacle\Common\Forms\Item;
use PHPinnacle\Common\Forms\ItemBadge;
use PHPinnacle\Common\Forms\ItemGroup;
use PHPinnacle\Common\Forms\ItemPicker;

ItemPicker::make('assignees')
    ->mode(ItemPickerMode::Select)
    ->multiple()
    ->compact()
    ->dense()
    ->circular()
    ->branches(false)
    ->items([
        ItemGroup::make('team')->children([
            Item::make('anna')
                ->label('Anna')
                ->description('Administrator')
                ->image('/avatars/anna.jpg')
                ->badges([ItemBadge::make('Available')->color('success')]),
        ]),
    ]);
```

`compact()` puts item and group descriptions on the label line. `dense()` independently reduces spacing and visual sizes. `circular()` rounds images and icon backgrounds. Images take precedence over icons. These options, `multiple()` and `branches()` accept boolean conditions or closures. Multiple dropdowns show complete selected chips and summarize overflow as `+N`.

Item and group factories derive labels with `Str::title()`. Single selection stores a string or `null`; multiple selection stores a list of strings. The field validates availability, disabled items and branch selection. Its Blade views, styles, translations and lazy Alpine component are registered by Common without requiring a panel plugin or frontend build.

Enable server search with `searchable()`. Its boolean or closure controls whether the search input is available. The `items()` callback receives a nullable `$search` and the current `$state`, alongside the usual Filament utility injections. Initial loading and clearing the search pass `null`. The callback owns filtering, ordering and access restrictions; the field does not filter search results automatically.

Set `limit(50)` to enable loading additional portions when scrolling. The default is `null`, with one unbounded result. When a limit is configured, the callback also receives `$offset` (starting at `0`) and `$limit`. Apply these to the source query; the field does not truncate callback results. Each successful portion advances the offset by the limit, and `[]` ends loading. Changing the search starts again at offset `0`. Static arrays are paginated by their top-level items.

Use `resolve()` to retrieve selected items outside the current search portion:

```php
ItemPicker::make('assignees')
    ->mode(ItemPickerMode::Select)
    ->multiple()
    ->searchable()
    ->limit(50)
    ->items(fn (?string $search, int $offset, int $limit) => $directory->searchItems($search, $offset, $limit))
    ->resolve(fn (?array $state, array $items) => $directory->selectedItems($state, $items));
```

The resolver runs after `items()` and receives the current `$state` and the returned `$items`. It returns `list<Item>` in both single and multiple modes, using already loaded items or looking up missing selections. Its results display the selection and determine valid values without being appended to the search list or affecting offsets. Inline selections already loaded in the list are highlighted there. Missing selections are added as selectable cards before the results. These cards remain in the current list after deselection or loading further portions, so choosing an item does not move other cards; duplicate results are hidden. When a branch loads, its full tree row replaces the resolved card so its children remain accessible. Changing the search rebuilds the list using the current selection. This applies to each selected value in multiple mode. Return `[]` for an empty selection and omit unavailable values. Disabled items and forbidden branches remain invalid. Apply the same access restrictions to search and resolution. Validation uses the initial portion with `$search = null` and the current state. Static arrays resolve selections automatically; callbacks without a resolver use their current portion.

Callbacks returning trees must retain the parent context needed to display matches and identify branches. Search results expand their branches to reveal matching descendants.

Publish package assets after installation or changes:

```bash
php artisan filament:assets
```

### JSON Schema picker and entry

```php
use Illuminate\JsonSchema\JsonSchema;
use PHPinnacle\Common\Forms\JsonSchemaPicker;
use PHPinnacle\Common\Infolists\JsonSchemaEntry;

$schema = JsonSchema::object([
    'customer' => JsonSchema::object([
        'email' => JsonSchema::string()->nullable()->description('Delivery address'),
    ]),
]);

JsonSchemaPicker::make('output_path')->jsonSchema($schema);
JsonSchemaEntry::make('schema')->state($schema);
```

Both components require `Illuminate\JsonSchema\Types\Type` objects from the `illuminate/json-schema` dependency, either directly or returned by Filament closures.

The picker stores a nonempty PHP list of exact technical property names: `['customer', 'email']` selects a nested field. The default is `null`, representing an absent selection. An optional picker accepts `null` and shows a clear button when a path is selected. Clearing resets the value to `null` and closes the dropdown. Required and disabled pickers omit the clear button. A required picker requires a nonempty path. The schema root is not selectable and `[]` is invalid. Dots and slashes remain literal within a property name, so `['metadata.version']` differs from `['metadata', 'version']`. Validation requires an available schema path with string segments. Object properties and `anyOf` variants are selectable. Arrays can be selected as whole fields; the picker does not map over array items. Array schemas expand to a readonly `*` row for their item schema, followed by its nested fields. These preview rows cannot be selected.

Keep picker state as a path array. Consumers can format the submitted value with Filament's native `dehydrateStateUsing()` callback when their storage format requires a string:

```php
JsonSchemaPicker::make('output_path')
    ->jsonSchema($schema)
    ->dehydrateStateUsing(fn (?array $state) => $state === null ? null : implode('.', $state));
```

This conversion belongs to the consumer and discards the distinction between dots in property names and segment separators.

The readonly entry uses the same tree template, listing object properties without a root row and array item schemas beneath an expandable `*` row. Click anywhere on a branch row, or focus it and press Enter or Space, to expand or collapse it. Leaf rows remain plain text. Both trees display fields directly below the optional search input, without a repeated field-label header or count. Both components show type and nullable badges and JSON Schema descriptions. The selected picker value uses the same field renderer, including its icon, full technical path, title, type and enabled required, nullable, format and enum markers. Descriptions and value limits appear in the tree only. Descriptions are enabled by default. Use `->descriptions(false)` to hide description text and row hover descriptions, or `->descriptions()` to enable them; the option also accepts a Filament closure. Schema descriptions remain available to local search when hidden. Type names use Filament primary colors, null badges use success colors, and unique-item markers use warning colors, including in dark mode. Format and enum annotations are hidden by default. Enable both with `->format()` or hide them with `->format(false)`; the option also accepts a Filament closure. The picker retains Filament's native PHP enum validation configuration. When enabled, formats replace the type badge (`uuid` instead of `string`, or `uuid[]` for array items); nullable badges remain separate. Enum fields replace their type with `enum` (or `enum[]` for array items), taking precedence over a format. Hover or focus the badge to see its allowed JSON values as individual monospace chips in a titled popover, preserving the distinction between strings, numbers, booleans and null. Constraints on `anyOf` branches are labeled with their scope. Array item constraints and enum values belong to the nested `*` row; array rows show only their own restrictions.

The picker panel matches its trigger width and renders its tree only while open. Both components support `->searchable()` and `->searchable(false)` (also accepting Filament closures). Search is on by default for the picker and off by default for the entry. It runs locally over technical property paths and all schema titles, and includes descriptions, types and enabled format and enum annotations and value constraints. Disabling local search clears its current query. Styles, translations, Heroicons and lazy Alpine components are supplied by Common; no workflow component or application frontend build is required.

Schema titles are always the main label with technical property names beside them in muted text. Missing titles are generated from property names with `Str::apa(Str::headline($name))`, such as `unit_price` → `Unit Price`; explicit schema titles take precedence. Technical field names are shown beside titles by default. Use `->properties(false)` to hide them or `->properties()` to show them; the option also accepts a Filament closure and applies to the tree and selected picker value. Hiding names does not change selection paths or local search. Enable `->requiredMarker()` to mark required properties with a red asterisk beside the main label, and `->constraints()` to display value restrictions. Both are hidden by default and accept a boolean or Filament closure; pass `false` to hide them again. Required markers describe the schema's parent-object `required` list and do not change picker validation (`->required()` still configures the picker itself). A path is marked required only when it is required in every `anyOf` variant. Constraints include `minLength`/`maxLength`, `pattern`, `minimum`/`maximum`, `multipleOf`, `minItems`/`maxItems` and `uniqueItems`, with scopes for union variants and separate rows for array items. Titles are always searchable; constraint values participate in search only when enabled. Selection paths contain exact technical property names as separate segments. Rows use a fixed disclosure slot with a consistent indentation step across objects and array item fields. Icon space is added only to fields with a rendered icon. Descriptions align with that field's label. Value limits and nullable badges precede the type badge, which is always last in the metadata area. Type badges and value limits share the same small monospace typography; uniqueness uses a warning-colored `unique` chip with the same border, background and padding as type badges. Other value limits form a muted group without individual badge borders or repeated label/value prefixes. Numeric bounds use inequalities, multiples use `×`, patterns use `/…/`, string length uses a ruler icon and item count uses a list icon. Inline limits have accessible labels and no popovers; long patterns are truncated visually. Limits popovers are available only in compact mode. Limits on `anyOf` branches retain their scope labels. Value limits wrap within the metadata area; enum values appear in the type badge tooltip. Rows have a single hover and selection background across their full width, with a 40 px minimum height and a fixed 24 px disclosure column. The picker search stays visible while scrolling. Enable `->compact()` for a 32 px minimum row height and descriptions in hover tooltips; field names retain the same font size. Enabled value limits are replaced by a small restrictions icon beside the type badge; hover or focus it to see a titled popover with aligned labels and values, including separate groups for union scopes. Enum values remain in their type badge tooltip in both modes. Compact mode is off by default and accepts a boolean or Filament closure.

Use `PHPinnacle\Common\JsonSchema\Schema::fromArray()` for schemas containing extra `x-*` attributes. It returns a Laravel `Type` adapter and stores these attributes per schema node while Laravel handles the schema, including local references and nullable unions. `toArray()` restores them at their original level with their values unchanged, including arrays, objects, booleans and null. Sibling attributes on a local `$ref` override attributes from its target. Pass the resulting schema directly to the picker or entry:

```php
use PHPinnacle\Common\JsonSchema\Schema;

$schema = Schema::fromArray([
    'type' => 'object',
    'properties' => [
        'email' => [
            'type' => 'string',
            'format' => 'email',
            'x-icon' => 'heroicon-o-envelope',
            'x-color' => 'success',
            'x-display' => ['label' => 'Contact email'],
        ],
    ],
]);
```

`x-icon` is the name of a registered Blade icon and appears beside the field name. It supports Heroicons and custom Blade icon sets, but does not accept HTML, SVG markup or image URLs. Field icons are enabled by default. Use `->icons(false)` to hide them or `->icons()` to enable them again; `icons()` also accepts a Filament closure. Hidden icons are not rendered and reserve no icon column. Extra attributes remain in the serialized schema. `x-color` colors the field icon and main title in both the tree and selected picker value. Colors are enabled by default. Use `->colors(false)` to ignore `x-color` or `->colors()` to enable it again; `colors()` also accepts a Filament closure. Disabling colors restores default icon and title colors without changing schema annotations, selection paths or the icon visibility option. It accepts a Filament palette name (`primary`, `success`, `danger`, or a registered custom name), HEX (`#abc`, `#aabbcc`, including alpha), or numeric RGB/RGBA (`rgba(30, 144, 255, 0.5)`, also space-separated CSS syntax). Palette names use shade 600 in light mode and 400 in dark mode; literal colors retain their exact value and alpha in both themes. Technical names, descriptions, type badges and restriction markers keep their existing colors. A node color does not propagate to child properties or array items. The first defined union color is used unless the node itself defines one. Invalid color syntax fails at the schema parsing boundary. Other extra attributes are preserved without affecting the component display. Laravel's own `JsonSchema::fromArray()` discards `x-*` attributes. When `anyOf` variants share a path, the first defined icon is used. The adapter is a root schema for the components; use Laravel types when composing nested builder schemas.

Client-side consumers can use `<x-phpinnacle-common::json-schema-picker>`, `<x-phpinnacle-common::json-schema-entry>` and the shared `<x-phpinnacle-common::json-schema-tree>` with Alpine expressions. The picker accepts `schema`, `value`, `choose` and `label`; `value` must be a writable Alpine expression for clearing. Its optional boolean `clearable` prop defaults to `true`; set it to `false` for required selections. The entry accepts `schema` and `label`. The tree accepts grouped sources prepared by `schemaGroups()` or `schemaSources()` from `json-schema-tools.js`, selection and choice callbacks, an optional external search expression and readonly display. These adapters share the same assets and renderer as the PHP components.

Schema traversal and source preparation live in `json-schema-definition.js`; popover and color formatting live in `json-schema-presentation.js`. Existing named imports from `json-schema-tools.js` remain available. Publish assets with `php artisan filament:assets` after updating Common so the imported modules are available beside the Alpine components.

At the Alpine boundary, `schema` is the serialized object from `$schema->toArray()` and `value` is a JavaScript array of exact path segments or `null`. Supply the optional `icons` prop as a trusted map of Blade icon names to server-rendered SVG strings to display `x-icon` annotations. PHP components prepare this map automatically. Supply the optional `propertyTitles` prop as a property-name-to-title map to generate fallback titles; `PropertyTitles::render($definition)` prepares it using Laravel string formatting. PHP components supply this map automatically. A choice callback receives that array as `source.value`; `source.key` is the internal tree identifier. Readonly array item previews have `source.value === null` and `source.compatible === false`; selection callbacks ignore them. Their wildcard labels are display text and do not become property path segments.

Both client-side adapters accept a boolean `descriptions` prop, defaulting to `true`, for description text and row hover descriptions; the shared tree accepts an Alpine `descriptions` expression. Both client-side adapters accept a boolean `colors` prop, defaulting to `true`, to control `x-color` display; grouped sources accept the same `colors` option. Both client-side adapters also accept a boolean `properties` prop, defaulting to `true`, to control technical names beside titles. The shared tree accepts an Alpine `properties` expression. Both client-side adapters accept optional boolean `format`, `required`, `constraints` and `compact` props, all defaulting to `false`. The `required` prop controls schema markers only. Both adapters also accept a boolean `searchable` prop, defaulting to `true` for the picker and `false` for the entry.

## Testing

```bash
composer test
npm test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
