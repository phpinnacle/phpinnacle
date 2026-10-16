# PHPinnacle Eidos

Eidos generates JSON Schema from Eloquent models, converts schemas into Laravel
validation rules, and provides Filament fields for selecting and displaying
schema properties.

## Installation

Requires PHP 8.4 or later, Laravel 13, and Filament 4 or 5.

```bash
composer require phpinnacle/eidos
php artisan filament:assets
```

No panel plugin is required. Publish Filament assets again after package updates.

## Eloquent schemas

```php
use App\Models\Product;
use PHPinnacle\Eidos\Services\SchemaIntrospector;

$introspector = app(SchemaIntrospector::class);
$schema = $introspector->introspect(Product::class);
$schemaFromInstance = $introspector->introspect(new Product);
$definition = $schema->toArray();
```

`introspect()` accepts an Eloquent model class or instance and generates a fresh
schema on every call. Laravel resolves `SchemaIntrospector` as a singleton,
sharing cast registrations across container resolutions without caching schemas.
For an instance, it uses that model's table, connection, casts, and
mass-assignment settings. Model discovery, caching, and cache refresh belong to
the application consuming Eidos.

Replace former `SchemaFactory::create()` calls with
`SchemaIntrospector::introspect()`.

Schemas describe column types, casts, backed enum values, dates, and nullability.
The object-level JSON Schema `required` list contains writable, nonnullable
columns without a database default, a declared model `$attributes` default, or
auto-increment. SQL-expression defaults still make columns optional even when
omitted from the schema's `default`. Current instance values do not affect
requiredness. Readonly and generated columns are never required.
Stored and virtual generated columns are included with `readOnly: true`.
Columns that the model's `isFillable()` excludes from mass assignment are also
marked `readOnly: true`, following `$fillable` and `$guarded`. These columns
remain in the schema with their defaults. Related model columns follow their
own model's mass-assignment settings. Generated columns remain readonly even
when fillable.
Column literals are included as JSON Schema `default` values, with declared
model `$attributes` taking precedence, including explicit `null`, `false`, zero,
and empty strings. Defaults are converted to the schema's scalar or JSON type.
Current values on a supplied model instance are not defaults. SQL expressions
such as `CURRENT_TIMESTAMP` and sequences, and defaults on generated columns,
are omitted. A database metadata `null` means no reported default; Eidos does
not infer a default from nullability.
Relationships with concrete return types include the related model's columns,
including their own `required` list, but do not expand its relationships.
Relationship properties are marked `readOnly: true` and are not required on the
parent object. `MorphTo` relationships are omitted.
Schema generation uses the model's database connection and table.

### Custom casts

Register a schema handler for a cast name with `SchemaIntrospector::registerCast()`.
Configure the singleton introspector in your application's service provider:

```php
use App\Casts\MoneyCast;
use Illuminate\JsonSchema\JsonSchema;
use PHPinnacle\Eidos\Services\SchemaIntrospector;

public function boot(SchemaIntrospector $introspector): void
{
    $introspector->registerCast(MoneyCast::class, fn () => JsonSchema::object([
        'amount' => JsonSchema::number(),
        'currency' => JsonSchema::string(),
    ]));
}
```

Handlers receive the model, attribute name, and a list of cast arguments. For
`MoneyCast::class . ':USD,2'`, register `MoneyCast::class`; the arguments are
`['USD', '2']`. A callback that does not need this context may omit its parameters.
Return a fresh `Illuminate\JsonSchema\Types\Type` or Eidos `Schema` for each field.

Registered handlers take precedence over built-in cast mappings and apply to
related models too. Column nullability and inferred `readOnly` still
apply. Without a handler, Eidos uses its built-in cast mapping or database type.
Container resolutions share these registrations. Each generated schema remains
independent.

## Laravel validation

```php
use Illuminate\JsonSchema\JsonSchema;
use PHPinnacle\Eidos\Validation\RuleFactory;

$schema = JsonSchema::object([
    'email' => JsonSchema::string()->format('email')->required(),
    'quantity' => JsonSchema::integer()->min(1)->max(10),
]);

$rules = app(RuleFactory::class)->make($schema, [
    'email' => ['unique:users,email'],
]);
```

Use the returned rules in a Form Request or Laravel's validator. `make()` accepts
an `Illuminate\JsonSchema\Types\Type`, optional application rules, and an optional
attribute prefix, for example `make($schema, [], 'payload')`. Application rules
must use complete attribute names, including that prefix. They run before
inferred rules. `x-validation` adds rules from schema annotations.

Supported validation includes scalar types, nullable values, scalar enums,
UUID/email/date formats, string and numeric bounds, item counts, required object
properties, and array items. Optional objects may be absent; their required keys
are checked when supplied. A prefixed root remains optional unless application
rules require it.

`anyOf` accepts at least one matching branch; `oneOf` requires exactly one.
Alternative validation requires an attribute prefix, including at the root.
`allOf` applies all branch constraints and can be used without a root prefix.

This conversion supports a subset of JSON Schema validation. Add Laravel rules
for unsupported constraints such as patterns, multiples, and unique items.
`readOnly` is a schema annotation; enforce write restrictions in the application.

## Schema composition and annotations

Use `PHPinnacle\Eidos\JsonSchema\Schema` for `oneOf`, `allOf`, local references,
`readOnly`, and `x-*` annotations:

```php
use Illuminate\JsonSchema\JsonSchema;
use PHPinnacle\Eidos\JsonSchema\Schema;

$payment = Schema::fromArray([
    'type' => 'object',
    'properties' => [
        'card' => ['type' => 'object'],
        'token' => ['type' => 'string'],
    ],
    'oneOf' => [['required' => ['card']], ['required' => ['token']]],
]);

$quantity = Schema::allOf([
    JsonSchema::integer()->min(1),
    JsonSchema::integer()->max(10),
]);
```

`Schema::oneOf()` and `Schema::allOf()` accept lists of schema types. Calling
`nullable()` permits null for the complete composition. Use `Schema::fromArray()`
for nested compositions and annotated properties. Laravel's `JsonSchema::union()`
accepts several primitive types; use compositions when branches have independent
constraints.

## Filament picker and entry

```php
use Illuminate\JsonSchema\JsonSchema;
use PHPinnacle\Eidos\Forms\JsonSchemaPicker;
use PHPinnacle\Eidos\Infolists\JsonSchemaEntry;

$schema = JsonSchema::object([
    'customer' => JsonSchema::object([
        'email' => JsonSchema::string()->nullable()->description('Delivery address'),
    ]),
]);

JsonSchemaPicker::make('output_path')->jsonSchema($schema);
JsonSchemaEntry::make('output_path')->jsonSchema($schema);
```

Both components accept schema types through `jsonSchema()`, directly or through
Filament callbacks. Their state is an exact property path or `null`.
The picker stores exact property names as a path array, such as
`['customer', 'email']`. Its default value is `null`. Optional pickers allow
clearing; required pickers need a nonempty path. Disabled pickers cannot be
cleared. The root and an empty path are not selectable.

Dots and slashes in property names remain literal: `['metadata.version']` differs
from `['metadata', 'version']`. Validation rejects malformed or unavailable paths.
Properties in `anyOf`, `oneOf`, and `allOf` branches are available, but a complete
path must exist within one branch. Arrays are selectable as whole fields. Expand
an array to inspect its object fields directly, without intermediate `*` rows.
Array item fields remain readonly and cannot be selected.
When a tree includes the array root, its item fields are indented beneath it.
The root starts expanded and can be collapsed without introducing `*` rows.

Picker and entry trees resolve local JSON Pointer `$ref` values, including
references in compositions and array items, without changing the supplied
definition. Referenced titles, icons, colors, required markers, and constraints
are available too. Sibling annotations override those on the referenced schema.
External references and circular references are unsupported and raise errors.

Keep paths as arrays when storing them. If an application needs a string, it can
format the submitted value through Filament:

```php
JsonSchemaPicker::make('output_path')
    ->jsonSchema($schema)
    ->dehydrateStateUsing(fn (?array $state) => $state === null ? null : implode('.', $state));
```

A dotted string loses the distinction between literal dots and path separators.

### Entry modes

The readonly entry normally shows the field identified by its state, with its
title, full path, icon, and enabled badges. The magnifying-glass button opens the
complete schema in a popover. An absent state shows `No field selected`; the
complete schema remains available for inspection.

Use `inline()` to display the complete tree directly:

```php
JsonSchemaEntry::make('output_path')
    ->jsonSchema($schema)
    ->state(['customer', 'email'])
    ->inline();
```

`inline()` accepts a boolean or Filament callback and is off by default. Both the
popover and inline tree highlight the current path and expand its parent fields.
Opening branches or searching does not change the entry's state. The entry does
not offer field selection or clearing.

### Display options

The schema trees show titles, type and nullable badges, descriptions, and
expandable object and array fields. Explicit titles take precedence; a name such
as `unit_price` gets the fallback title `Unit Price`. The entry is readonly.
Its branches expand or collapse on click, Enter, or Space.

Each option accepts a boolean or Filament callback:

| Method | Default | Effect |
| --- | --- | --- |
| `searchable()` | Picker: on; entry: off | Search paths, titles, descriptions, types, and enabled restrictions locally. |
| `compact()` | Off | Reduce spacing and show descriptions and limits in tooltips. |
| `properties()` | On | Show technical names beside titles. |
| `descriptions()` | On | Show descriptions; hidden descriptions remain searchable. |
| `format()` | Off | Show formats or enum badges instead of type badges. |
| `constraints()` | Off | Display value limits and other restrictions. |
| `icons()` | On | Show `x-icon` annotations. |
| `colors()` | On | Apply `x-color` to titles and icons. |
| `requiredMarker()` | Off | Mark properties required by their schema. |

`requiredMarker()` controls presentation. Use Filament's `required()` to require
a picker selection. Display options do not change selected paths or validation.
Enum badges show allowed JSON values in a popover. Composition and array-item
restrictions stay associated with their branches or items. The selected value
shows its full path and enabled badges; inline descriptions and limits stay in
the tree.

Array type badges use compact notation such as `string[]`, `object[]`, and
`number[][]`. Click the badge, or focus it and press Enter or Space, to inspect
the array and each item level separately. The details include types, nullability,
titles, enabled descriptions, formats, enum values, and value limits. Nested
array counts and scalar limits remain associated with their own levels. Escape
closes the details. Search includes item metadata and enabled restrictions even
though item rows are hidden. Exact paths and selection rules remain unchanged.

`x-icon` accepts a registered Blade icon name, such as `heroicon-o-envelope`.
`x-color` accepts a Filament palette name, HEX with optional alpha, or numeric
RGB/RGBA. A field's color applies to its title and icon, not its descendants.
For composition variants sharing a path, the first defined icon or color is used
unless the field defines its own. Invalid annotation syntax is rejected when
parsing the schema. Other `x-*` attributes are preserved without changing display.

### Blade use

For a custom Alpine integration, use `<x-phpinnacle-eidos::json-schema-picker>`
or `<x-phpinnacle-eidos::json-schema-entry>`:

```blade
<x-phpinnacle-eidos::json-schema-picker
    schema="schema"
    value="selectedPath"
    choose="selectedPath = source.value"
    label="'Choose a field'"
/>

<x-phpinnacle-eidos::json-schema-entry
    schema="schema"
    value="selectedPath"
    label="'Schema'"
    :inline="true"
/>
```

Provide a serialized schema object and a writable path array or `null` in Alpine.
Set `:clearable="false"` for a required selection. The same display options are
available as boolean props; the required-marker prop is named `required`.
The entry reads `value` without modifying it; omit `inline` for the normal
field summary and popover.

Blade components display `x-icon` names automatically, including names introduced
when the Alpine schema changes. Eidos serves registered icons from
`phpinnacle/eidos/icons/{icon}.svg` with an ETag and browser caching. The icon uses the
field color in light and dark mode. No SVG catalog or Livewire request is needed
from the consuming component. Pass `:icons="false"` to hide schema icons.

For pre-rendered icons and fallback titles, the `icons` and `property-titles`
props still accept the output of `SchemaPresentation::render($schema->toArray())`.
Its result contains `definition`, `icons`, and `propertyTitles`; `withIcons: false`
omits rendered icons. Filament components supply these automatically. Supplied
icon markup must be trusted server output.

## Testing

```bash
composer test
npm test
```

Tests cover schema generation, shared cast registrations, validation outcomes,
exact property selection, search, accessible navigation, annotation preservation,
and safe rendering.

## License

Released under the [MIT License](LICENSE.md).
