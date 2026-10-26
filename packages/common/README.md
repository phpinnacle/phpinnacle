# PHPinnacle Common

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/common.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/common)

Common provides reusable Filament fields, table filters and columns, range matching, and Laravel model and page utilities.

## Features

- InlineBuilder fields with repeatable blocks, compact chips, modal forms, and constrained drag or keyboard ordering.
- ItemPicker form fields with inline or custom dropdown layouts, grouped trees, server search, scroll loading, multiple selection, badges and images.
- Inclusive, strict and open-ended range matching.
- Range, active and combined table filters.
- Standard active, creator, created, updated and default table columns.
- Record creators, form slugs, relationship badges and page refresh actions.

## Requirements and installation

- PHP 8.4 or later
- Filament 4 or 5

```bash
composer require phpinnacle/common
php artisan filament:assets
```

Publish Filament assets again after package updates. No panel plugin or application frontend build is required.

## Filament components

```php
use PHPinnacle\Common\Tables\CreatedColumn;

CreatedColumn::make();
```

### Inline builder

`InlineBuilder` uses a Builder-style block catalog and stores a list of `['type' => 'message', 'data' => [...]]` items. Data retains the types and nested structures of the configured Filament fields. Blocks may repeat.

```php
use Filament\Forms\Components\TextInput;
use PHPinnacle\Common\Forms\InlineBuilder;
use PHPinnacle\Common\Forms\InlineBuilder\Block;

InlineBuilder::make('steps')
    ->addActionLabel('Add step')
    ->placeholder('Step')
    ->blocks([
        Block::make('message')
            ->label('Send message')
            ->description('Configure the message to send.')
            ->icon('heroicon-o-envelope')
            ->color('success')
            ->schema([
                TextInput::make('message')->required(),
            ]),
        Block::make('stop')->label('Stop'),
    ]);
```

Click the plus button to choose a block. Blocks without a schema are added immediately; other blocks open a native Filament form. Click a chip label to edit, its separate close button to remove, or drag its label to reorder. Alt+Left and Alt+Right provide keyboard ordering. Chips wrap while the add button stays on the first row. When additions are available, an empty field shows a muted plus and placeholder; only those controls open its menu. Free input space also opens the menu when chips are present. Modals contain no deletion action. Chips declare the generic `data-field-interactive` attribute so embedding form components can preserve their interactions without recognizing InlineBuilder-specific markup.

`blocks()` accepts an array or closure. Block schemas accept arrays or deferred closures; schema callbacks receive the nullable `$index` of the edited item (`null` for additions), plus the field's usual Filament utility injections. Forms retain native defaults, hydration, validation and dehydration. Paired `formatStateUsing()` and `dehydrateStateUsing()` callbacks round-trip when loading, adding and editing an instance. Nested fields are cloned independently for each instance. Schema callbacks are not evaluated while rendering catalog menus. `label()`, `description()`, `icon()` and `color()` supply presentation metadata. `addable()`, `deletable()` and `reorderable()` accept boolean conditions or closures on the field. When no blocks are available for addition, the plus button and placeholder are omitted; existing chips remain editable and removable according to their controls. A block's `addable()` condition controls additions of that type while preserving editing and removal of existing instances.

Use `reorderableUsing(fn (array $items) => ...)` for application-specific ordering constraints. Its `$items` argument contains the complete proposed sequence, including each instance's data. The same callback builds allowed positions for the browser and checks the current sequence again before a server mutation. Drag hovering and rejected moves make no request, rejected drops restore the original order, and keyboard moves use the same permissions. Allowed positions refresh on rendering after changes to data or surrounding form state. Submitted orders must be complete permutations without missing, duplicated or unknown items. Application-specific validation of the full value belongs in the consuming field's Laravel rules.

Actions partially render their editor by default; use `partiallyRenderAfterStateUpdated(false)` when the containing form needs a full refresh. No domain registry, JSON Schema definition, panel plugin, or other PHPinnacle package is required.

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

`compact()` puts item and group descriptions on the label line. `dense()` independently reduces spacing and visual sizes. `circular()` rounds images and icon backgrounds. Images take precedence over icons. These options, `multiple()` and `branches()` accept boolean conditions or closures. Multiple dropdowns show complete selected chips and summarize overflow as `+N`. `branches(false)` allows leaf choices while keeping parent branches expandable. Disabled choices cannot be selected.

Item and group names get title-case labels unless an explicit label is supplied. Single selection stores a string or `null`; multiple selection stores a list of strings. The field validates availability, disabled items and branch selection. Both inline and dropdown modes support keyboard navigation. Groups are labels rather than selectable values.
Dropdown panels receive their position and width before becoming visible, including on their first opening.

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

The resolver runs after `items()` and receives the current `$state` and the returned `$items`. It returns `list<Item>` in both single and multiple modes, using already loaded items or looking up missing selections. Its results display the selection and determine valid values without being appended to the search list or affecting offsets. The initial selection is resolved during server rendering, so its label is available without opening the dropdown. Inline selections already loaded in the list are highlighted there. Missing selections are added as selectable cards before the results. These cards remain in the current list after deselection or loading further portions, so choosing an item does not move other cards; duplicate results are hidden. When a branch loads, its full tree row replaces the resolved card so its children remain accessible. Changing the search rebuilds the list using the current selection. This applies to each selected value in multiple mode. Return `[]` for an empty selection and omit unavailable values. Disabled items and forbidden branches remain invalid. Apply the same access restrictions to search and resolution. Validation uses the initial portion with `$search = null` and the current state. Static arrays resolve selections automatically; callbacks without a resolver use their current portion.

Callbacks returning trees must retain the parent context needed to display matches and identify branches. Search results expand their branches to reveal matching descendants.

## Filters and columns

`ActiveFilter` filters the `is_active` column. `HasFilter` filters whether an
Eloquent relationship has records. `RangeFilter` selects an inclusive numeric
interval:

```php
use PHPinnacle\Common\Filters\ActiveFilter;
use PHPinnacle\Common\Filters\HasFilter;
use PHPinnacle\Common\Filters\RangeFilter;

ActiveFilter::make();
HasFilter::make('orders');
RangeFilter::make('quantity')->minValue(0)->maxValue(100)->step(5);
```

`CombinedFilter` groups select filters. Each selection filters its named column
unless an `applyUsing()` callback supplies a custom query. `depends()` disables
a selection until its prerequisites have values, and clears dependent choices
when a prerequisite changes:

```php
use PHPinnacle\Common\Filters\CombinedFilter;
use PHPinnacle\Common\Filters\CombinedFilter\Filter;

CombinedFilter::make()->filters(
    Filter::make('status')->label('Status')->options([
        'draft' => 'Draft',
        'published' => 'Published',
    ]),
);
```

`CreatedColumn`, `UpdatedColumn`, `CreatorColumn`, `ActiveColumn` and
`DefaultColumn` provide standard labels and display for their corresponding
record fields. Applications supply actions when a column should change a record.

## Range matching

```php
use PHPinnacle\Common\Models\Range;

$range = Range::make(10, 20);
$range->match(10);               // true: boundaries are included
$range->match(10, strict: true); // false: boundaries are excluded
Range::make(null, 20)->match(5); // true: no lower boundary
```

`match()` also accepts arrays and `Countable` values, comparing their item count.
A `null` boundary leaves that side of the range open.

## Model and page utilities

Use `HasCreator` on an Eloquent model with a nullable `created_by` column. New
records use the authenticated user's ID unless a creator was explicitly assigned.
The `creator` relationship uses the configured authentication user model.

`FormSlug::make()` can be passed to a name field's `afterStateUpdated()` callback.
It updates the `slug` field while its value still matches the previous name's
slug, preserving manual edits. By default it runs only during creation.

```php
use Filament\Forms\Components\TextInput;
use PHPinnacle\Common\Concerns\FormSlug;

TextInput::make('name')->live(onBlur: true)->afterStateUpdated(FormSlug::make());
TextInput::make('slug');
```

`PageBadge` shows the count of a configured relationship beside a resource page's
navigation item. `CanBeRefreshed` refreshes a Livewire page on `refresh-page`.
Use `Refresh::page($livewire)` to request that update, or `Refresh::nav($livewire)`
to refresh sidebar and topbar navigation.

## Testing

Tests cover native builder forms and nested data, repeated blocks, constrained drag and keyboard ordering, independent package booting, valid and invalid selections, unavailable choices, search and loading, safe rendering, range matching, record creators and page refreshes.

```bash
composer test
npm test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
