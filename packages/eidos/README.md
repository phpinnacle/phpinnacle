# PHPinnacle Eidos

Eidos generates JSON Schema from Eloquent models, converts schemas into Laravel
validation rules, and provides Filament fields for selecting and displaying
schema properties, connecting fields, and editing bindings.

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
Relationships with concrete return types include related model schemas and their
own `required` lists. `introspect($model, relationshipDepth: 3)` expands three
relationship levels; the default is one, and zero includes only the root columns.
Cyclic and self-referential paths may repeat until the requested depth, where only
columns remain. Larger depths increase the generated schema size and metadata reads.
To omit an unavailable optional relationship, register
`$introspector->excludeRelationship(Media::class, 'temporaryUpload')` at application
startup. Exclusions use the exact model class and method name. They are shared
with cast registrations; exceptions from other relationships still propagate.
Relationship properties are marked `readOnly: true` and are not required on the
parent object. Their `x-relationship` annotation preserves the exact Eloquent
method name even when the property is snake-cased. BelongsTo properties also
include `x-foreign-key` with the exact local foreign key column. `MorphTo` relationships are omitted.
`HasMany`, `MorphMany`, `HasManyThrough`, and `BelongsToMany` (including
`MorphToMany`) relationships are arrays of related objects. Singular
relationships are nullable objects.
Schema generation uses the model's database connection and table.

### Custom casts

Register a schema handler for a cast name with `SchemaIntrospector::registerCast()`.
Configure the singleton introspector in your application's service provider:

```php
use App\Casts\MoneyCast;
use PHPinnacle\Jason\Schema;
use PHPinnacle\Eidos\Services\SchemaIntrospector;

public function boot(SchemaIntrospector $introspector): void
{
    $introspector->registerCast(MoneyCast::class, fn () => Schema::object([
        'amount' => Schema::number(),
        'currency' => Schema::string(),
    ]));
}
```

Handlers receive the model, attribute name, and a list of cast arguments. For
`MoneyCast::class . ':USD,2'`, register `MoneyCast::class`; the arguments are
`['USD', '2']`. A callback that does not need this context may omit its parameters.
Return a `PHPinnacle\Jason\Schema` for each field. Jason schemas are immutable and may be reused.

Registered handlers take precedence over built-in cast mappings and apply to
related models too. Column nullability and inferred `readOnly` still
apply. Boolean schema definitions (`true` or `false`) are wrapped in `allOf` to
preserve their meaning when adding column metadata and defaults. Without a handler,
Eidos uses its built-in cast mapping or database type.

## Laravel validation

```php
use PHPinnacle\Jason\SchemaBuilder;
use PHPinnacle\Eidos\Validation\RuleFactory;

$schema = SchemaBuilder::make()
    ->type('object')
    ->properties([
        'email' => SchemaBuilder::make()->type('string')->format('email')->build(),
        'quantity' => SchemaBuilder::make()->type('integer')->min(1)->max(10)->build(),
    ])
    ->required(['email'])
    ->build();

$rules = app(RuleFactory::class)->make($schema, [
    'email' => ['unique:users,email'],
]);
```

Use the returned rules in a Form Request or Laravel's validator. `make()` accepts
a `PHPinnacle\Jason\Schema`, optional application rules, and an optional
attribute prefix, for example `make($schema, [], 'payload')`. Application rules
must use complete attribute names, including that prefix. They run before
inferred rules. Eidos interprets `x-validation` as additional Laravel rules;
Jason preserves this annotation without interpreting it.

Supported validation includes scalar types, nullable values, scalar enums,
UUID/email/date formats, string and numeric bounds, item counts, required object
properties, and array items. Optional objects may be absent; their required keys
are checked when supplied. A prefixed root remains optional unless application
rules require it.

Bounds apply to their corresponding declared types, including within unions:
numeric limits do not constrain boolean or string branches. Null-only fields
allow null and optional absence, but reject supplied non-null values, including
empty strings and whitespace.

`anyOf` accepts at least one matching branch; `oneOf` requires exactly one.
Alternative validation requires an attribute prefix, including at the root.
`allOf` applies all branch constraints and can be used without a root prefix.
Boolean `true` adds no constraints. Boolean `false` rejects every supplied value,
including null and empty values, while allowing an optional field to be absent;
it requires an attribute prefix when used at the root.

This conversion supports a subset of JSON Schema validation. Add Laravel rules
for unsupported constraints such as patterns, multiples, and unique items.
`readOnly` is a schema annotation; enforce write restrictions in the application.

## Schema composition and annotations

Eidos consumes immutable `PHPinnacle\Jason\Schema` values. Use Jason's mutable
`SchemaBuilder` to configure them; call `build()` before passing them to Eidos:

```php
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

$payment = Schema::fromArray([
    'type' => 'object',
    'properties' => [
        'card' => ['type' => 'object'],
        'token' => ['type' => 'string'],
    ],
    'oneOf' => [['required' => ['card']], ['required' => ['token']]],
]);

$quantity = Schema::allOf([
    SchemaBuilder::make()->type('integer')->min(1)->build(),
    SchemaBuilder::make()->type('integer')->max(10)->build(),
]);
```

`Schema::oneOf()` and `Schema::allOf()` accept lists of Jason schemas.
Use `SchemaBuilder::make($schema)->nullable()->build()` to permit null for the complete composition.
`Schema::fromArray()` preserves definitions and references; Eidos resolves local
references when presenting paths or generating validation rules. `required([...])`
belongs to the containing object. `Schema::union()` accepts several primitive
types; use compositions when branches have independent constraints.

See the [Jason README](https://github.com/phpinnacle/jason#build-a-schema) for
builder methods and typed schema readers. UI annotation checks belong to Eidos
presentation; Jason preserves `x-*` values without Filament-specific interpretation.

## JSON Schema extensions

Eidos generates or interprets the following `x-*` annotations. Add them to the
schema node they describe, including a property, an array's `items` schema or a
composition branch. Jason retains the supplied annotations; Eidos applies their
validation, relationship-metadata or presentation behavior at the relevant boundary.

| Annotation | Value | Consumer and behavior |
| --- | --- | --- |
| `x-mapping` | `item` or `property` | `FieldMapping` destination assembly mode: `item` collects array elements, `property` collects object properties under literal source names. Omission connects one whole value. The annotation applies only to its matching container type. |
| `x-relationship` | String | Generated by `SchemaIntrospector` on relationship properties. Contains the exact Eloquent method name, even when the schema property name is snake-cased. The relationship schema is readonly and optional on its parent. |
| `x-foreign-key` | String | Generated on `BelongsTo` relationship properties, alongside `x-relationship`. Contains the exact foreign key column on the model being introspected. Other relationship kinds omit it. |
| `x-validation` | Laravel rule string or list of rules | `RuleFactory` appends these rules after the node's inferred type and constraint rules. Application rules passed to `make()` still come first. Use a single rule string or a list of rule strings for portable JSON. Attribute paths in rules must include the prefix passed to `make()`, when applicable. An annotation on the root schema requires a nonempty prefix. Omission adds no extra rules. |
| `x-icon` | String | Registered Blade icon name, such as `heroicon-o-envelope`, displayed by picker/entry trees and mapping/binding cards. Eidos resolves the name to decorative SVG; inline SVG markup is not accepted. Omission leaves the field without an icon. |
| `x-color` | String | Filament color name, HEX color with optional alpha, or numeric RGB/RGBA, for example `success`, `#2563eb`, `#2563eb80`, `rgb(37 99 235)` or `rgba(37, 99, 235, 0.5)`. Applies to the node's title and icon when colors are enabled; it does not cascade to child properties. Omission uses the component's normal styling. |
| `x-badges` | List of objects with string `label` and `color` | Extra badges on mapping/binding cards, for example `[{"label": "Imported", "color": "info"}]`. `color` is passed to Filament's badge component. Badges from `allOf` branches are appended in branch order; the type badge follows custom badges. Omission produces no custom badges. |
| `x-enum` | Object mapping enum value keys to string labels | Display labels for values declared in `enum`, used by built-in static selects, mapping cards and picker/entry enum hints. String values use their literal value as a key; other values use their JSON representation, such as `"0"`, `"true"` or `"null"`. Missing labels retain the original value. Extra label keys add no choices; validation, compatibility and stored values continue to use `enum`. |

`x-icon` and `x-color` syntax is checked when Eidos prepares presentation
metadata, including when icon display is disabled. Omit unused annotations
rather than supplying `null`. A field's own icon/color takes precedence over
composition branches; otherwise the first defined branch value is used.
Display switches such as `icons()`, `colors()` and `format()` control which
metadata a component shows.

Example combining presentation, enum labels, additional validation and
relationship metadata:

```json
{
  "type": "object",
  "properties": {
    "status": {
      "type": "string",
      "enum": ["draft", "active"],
      "x-enum": {"draft": "Draft order", "active": "Active order"},
      "x-icon": "heroicon-o-tag",
      "x-color": "info",
      "x-badges": [{"label": "Imported", "color": "success"}]
    },
    "code": {
      "type": "string",
      "x-validation": ["regex:/^[A-Z]+$/"]
    },
    "purchasing_partner": {
      "type": ["object", "null"],
      "readOnly": true,
      "x-relationship": "purchasingPartner",
      "x-foreign-key": "purchasing_partner_id",
      "properties": {"id": {"type": "integer"}}
    }
  }
}
```

Relationship annotations describe the generated schema for consumers.
`x-validation` is consumed by Laravel rule generation, not by mapping
compatibility checks. Unlisted application annotations, such as the `x-domain`
example in [compatibility and output schemas](#compatibility-and-output-schemas),
remain metadata; consumers can interpret them through callbacks without adding
built-in Eidos semantics.

## Filament picker and entry

```php
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;
use PHPinnacle\Eidos\Forms\JsonSchemaPicker;
use PHPinnacle\Eidos\Infolists\JsonSchemaEntry;

$schema = Schema::object([
    'customer' => Schema::object([
        'email' => SchemaBuilder::make()
            ->type('string')
            ->nullable()
            ->description('Delivery address')
            ->build(),
    ]),
]);

JsonSchemaPicker::make('output_path')->jsonSchema($schema);
JsonSchemaEntry::make('output_path')->jsonSchema($schema);
```

Both components accept Jason schemas through `jsonSchema()`, directly or through
Filament callbacks. Both store canonical JSONPath strings, such as
`$['customer']['email']`, or `null`. The picker's default value is `null`.
Optional pickers allow clearing; required pickers need
a selected field. Disabled pickers cannot be cleared. The root `$` is not selectable.
Use `multiple()` to select a list of distinct JSONPath strings instead of one path.
Multiple pickers default to `[]`; clicking a selected field removes it and keeps
the dropdown open. Required multiple pickers need at least one selection.
The dropdown matches the field width and opens on the side with available space,
including on its first opening.

Dots, slashes, and asterisks in quoted property names remain literal:
`$['metadata.version']` differs from `$['metadata']['version']` and `$['*']`
differs from `$[*]`. Validation rejects malformed or unavailable paths.
Properties in `anyOf`, `oneOf`, and `allOf` branches are available, but a complete
path must exist within one branch. Arrays are selectable as whole fields. Expand
an array to inspect its object fields directly, without intermediate `*` rows.
Array item fields are readonly by default. Enable `selectArrayItemFields()` to
select paths such as `$['items'][*]['name']`; the wildcard identifies every item
without choosing a concrete index.
Jason supplies the structural lookup; Eidos applies selection policy. Concrete
array indexes are not selectable, including when wildcard item selection is enabled.
When a tree includes the array root, its item fields are indented beneath it.
The root starts expanded and can be collapsed without introducing `*` rows.

Picker and entry trees resolve local JSON Pointer `$ref` values, including
references in compositions and array items, without changing the supplied
definition. Referenced titles, icons, colors, required markers, and constraints
are available too. Sibling annotations override those on the referenced schema.
External references and circular references are unsupported and raise errors.
Metadata preparation uses Jason's schema visitor, including definitions,
conditional schemas, and positional array schemas. UI annotation validation
remains in Eidos; selectable fields follow properties, compositions, and `items`.

Store the selected JSONPath string, or list of strings in multiple mode, directly.
`PHPinnacle\Jason\JsonPath::fromSegments()` and `PHPinnacle\Jason\JsonPath::parse()`
convert the supported canonical subset:
quoted property names, nonnegative indexes, and `[*]`. Path-segment arrays are
unsupported as picker state.

Eidos consumes Jason's PHP and JavaScript path codecs. Its service provider
publishes JavaScript under `js/phpinnacle/<package>/resources/js/`, preserving
relative imports between the Composer packages. Republish assets with
`php artisan filament:assets` after upgrading.

### Entry modes

The readonly entry normally shows the field identified by its state, with its
title, full path, icon, and enabled badges. The magnifying-glass button opens the
complete schema in a popover. An absent state shows `No field selected`; the
complete schema remains available for inspection.

Use `inline()` to display the complete tree directly:

```php
JsonSchemaEntry::make('output_path')
    ->jsonSchema($schema)
    ->state("$['customer']['email']")
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

Boolean schemas display as `any` for `true` and `never` for `false`, including
local reference targets. Array type badges use compact notation such as `string[]`,
`object[]`, `number[][]`, and `never[]` for `items: false`. Click the badge, or focus
it and press Enter or Space, to inspect the array and each item level separately.
The details include types, nullability,
titles, enabled descriptions, formats, enum values, and value limits. Nested
array counts and scalar limits remain associated with their own levels. Escape
closes the details. Search includes item metadata and enabled restrictions even
though item rows are hidden. Exact paths and selection rules remain unchanged.

See [JSON Schema extensions](#json-schema-extensions) for `x-icon` and `x-color`
formats, validation, and composition precedence.

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

Provide a serialized schema object and a writable JSONPath string or `null` in Alpine.
Set `:clearable="false"` for a required selection. The same display options are
available as boolean props; use `:select-array-item-fields="true"` to enable
array item selection. The required-marker prop is named `required`.
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

## Field mapping

`FieldMapping` connects the named properties of two inline JSON Schema objects. Configuration accepts arrays or Filament-evaluated closures. Property names are stable identifiers, including numeric names and names containing dots.

The `Forms\JsonSchema` adapter uses [Jason](https://github.com/phpinnacle/jason) for schema values and local JSON Pointer `$ref` resolution. Serialized definitions retain their original references; property views resolve them. Read the declared format through `format(): ?string` and effective enum restrictions (including intersected `allOf` values) through `enumValues(): ?array`; other metadata remains available through `annotation()`. Mapping fields require schema object definitions; Boolean schemas are rejected by this adapter.

`JsonSchema::equals($other)` compares serialized definitions using JSON value equality: object key order is ignored, array element order and scalar types are preserved, and numbers compare by value. It does not prove equivalence between differently expressed schemas.

```php
use PHPinnacle\Eidos\Forms\FieldMapping;

FieldMapping::make('mapping')
    ->jsonSchema(
        source: ['type' => 'object', 'properties' => [
            'name' => ['type' => 'string', 'title' => 'Display name'],
            'email' => ['type' => 'string'],
            'phones' => ['type' => 'array', 'items' => ['type' => 'string']],
        ]],
        dest: ['type' => 'object', 'required' => ['contact'], 'properties' => [
            'contact' => ['type' => 'string', 'title' => 'Contact name'],
            'phone_numbers' => ['type' => 'array', 'x-mapping' => 'item', 'items' => ['type' => 'string']],
            'custom' => ['type' => 'object', 'x-mapping' => 'property'],
        ]],
    );
```

Required destinations come from the destination object's `required` list. Collection connections are explicit: set `x-mapping: item` on an array destination or `x-mapping: property` on an object destination. Without the annotation, a destination accepts only one compatible whole value.

`FieldMapping` determines the mode from each destination's type and `x-mapping` annotation. `getTargetModes()` and `getConnectionModes()` return `PHPinnacle\Eidos\Enums\MappingMode` cases (`Whole`, `Item`, `Property`); JavaScript receives their string values.

### State and connections

State is one list of rows, with every source represented exactly once. A source row contains `kind: source`, its literal `source` identifier, a nullable `target`, and an `options` array containing its settings:

```php
[
    ['kind' => 'source', 'source' => 'name', 'target' => 'contact', 'options' => ['transforms' => []]],
    ['kind' => 'source', 'source' => 'phones', 'target' => null, 'options' => []],
    ['kind' => 'source', 'source' => 'email', 'target' => 'phone_numbers', 'options' => []],
]
```

Both mapping editors show a translucent overlay and become inert while their owning Livewire component is processing a request. Mouse and keyboard interaction resume when the request finishes.

The native Filament `getState()` and `state($rows)` methods read and write this list directly. Livewire keeps the same rows; there is no internal mapping format or state cast. Source settings bind to `{rowIndex}.options`, so dots and numeric source names remain ordinary string values.

An array destination annotated with `x-mapping: item` accepts several sources compatible with its `items` schema. Each item is a separate source row naming the same target; list order preserves item order. Items may be strings, numbers, booleans, nulls, objects, arrays, or unions, subject to the declared item schema. An array source becomes one nested item when compatible; arrays are never flattened. To connect an array as a whole value, omit `x-mapping` from the destination.

An object destination annotated with `x-mapping: property` accepts several sources as properties under their literal source names: `_my_field` targets the `_my_field` property inside `custom`. Keys cannot be renamed; dots and numeric names retain their literal identity. Compatibility uses the matching `properties` entry, or `additionalProperties` for other names. Omitted or `true` additional properties are unrestricted, `false` disallows undeclared names, and a schema constrains their values. Eidos stores connection rows, not assembled values; consumers use the destination mode to build arrays or objects. Nested required properties, property patterns, and collection-size constraints are not enforced by connection compatibility.

Each source permits at most one connection. Click a line to remove it, or focus it and press Enter or Space. Changing a destination mode preserves existing rows for correction and rechecks compatibility on submission.

`inverse()` only swaps the columns: destinations appear on the left and sources on the right. State, static editors, cardinality, and source settings stay the same, so switching orientation requires no data conversion.

### Compatibility and output schemas

Schema compatibility is directional: declared source values must fit the destination's supported constraints. Checks include types, nullability, formats, string lengths, alternatives, and nested array items. An omitted type imposes no type restriction; other supported constraints still apply. An `integer` source can connect to a `number` destination without conversion; the reverse requires conversion. This also applies to unions and nested array items, preserving nullability and enum restrictions. Other type names must match. A formatted string can connect to an unrestricted string. A destination with a format requires the same source format: UUID cannot connect to `date` or `date-time`, and `date` cannot connect to `date-time`.

String bounds use JSON Schema's `minLength` and `maxLength`. A source maximum of 255 cannot connect to a destination maximum of 64; 64 can connect to 255 or an unbounded destination. A missing source maximum is unbounded, and a missing minimum is zero. Formats do not imply length bounds. Composed `allOf` bounds intersect; every source alternative must fit a destination alternative.

Both optional checks are enabled by default. Configure them per field with an immutable `SchemaCompatibility` value:

```php
use PHPinnacle\Eidos\Forms\SchemaCompatibility;

FieldMapping::make('mapping')
    ->jsonSchema(source: $sourceSchema, dest: $destinationSchema)
    ->compatibility(
        SchemaCompatibility::make()
            ->formats()
            ->stringLengths(false)
    );
```

`formats(false)` disables format checks; `stringLengths(false)` disables both length checks. Type, nullability, and array-item checks remain active. Add application restrictions with `canConnectUsing()`:

```php
use PHPinnacle\Eidos\Forms\JsonSchema;

->canConnectUsing(fn (JsonSchema $source, JsonSchema $target) =>
    ($source->toArray()['x-domain'] ?? null) === ($target->toArray()['x-domain'] ?? null)
)
```

The callback receives `(JsonSchema $source, JsonSchema $target, string $sourceName, string $targetName)`: the effective source and destination property schemas, followed by their literal property names. For an item connection, `$target` is the destination item schema; for a property connection, it is the matching property or additional-property schema. `$targetName` remains the name of the destination collection. It runs only after the configured checks pass and cannot override their rejection. `toArray()` exposes each inline definition, including application metadata. The same server-computed connection modes drive client availability and form validation.

The component projects `type`, `format`, `minLength`, `maxLength`, `enum`, `items`, `required`, `properties`, `allOf`, `anyOf`, and `oneOf` definitions after resolving local references. An enum destination requires an explicit source enum whose values are a subset of its allowed values; an unrestricted string or date cannot connect to a Status enum. `allOf` intersects enum constraints and types, treating `number` intersected with `integer` as `integer`; array items use the same checks. Enum equality preserves JSON value types, compares numbers by value, and ignores object property order. These are the component’s compatibility rules; they do not validate runtime payloads or prove arbitrary JSON Schema containment. Other constraints such as `pattern`, numeric bounds, item counts, and uniqueness are displayed as metadata but are not enforced by connection compatibility; native static editors remain responsible for their value validation.

Use `outputSchema(array|Closure)` for the effective source object schema after applying source settings. It replaces the input schema for source type chips, constraint hints and compatibility checks. Declare only formats and bounds guaranteed by the effective output: a logical string output alone does not preserve an email/UUID format or establish a length limit. The consumer supplies this schema; the field does not compute output values or infer their guarantees.

See [JSON Schema extensions](#json-schema-extensions) for the `x-enum` label
format. Enum display labels leave compatibility checks and saved values unchanged.

### Static values

`FieldMapping` edits static values inside destination cards through `staticForms()`. Separate static source cards are available only in `FieldBinding`. Static values use rows such as `['kind' => 'static', 'target' => 'contact', 'static' => 'Customer', 'options' => []]`. Without a matching `staticForms()` editor, non-empty scalar values are accepted. With an editor, its native rules and casts define accepted values. Static destination values count as mapped without a source or line in either orientation; the consumer owns domain validation.

Use `withBuiltinStaticForms()` to register standard Filament editors. Destination static forms also render when the source schema has no fields:

```php
FieldMapping::make('mapping')
    ->jsonSchema(source: $sourceSchema, dest: $destinationSchema)
    ->withBuiltinStaticForms();
```

| JSON Schema type or format | Editor |
| --- | --- |
| `string` | `TextInput`; string enums use `Select` |
| `string` with `format: text` | `Textarea` for multiline text |
| `uuid` | `TextInput` with UUID validation |
| `phone`, `email` | Telephone or email `TextInput` |
| `date`, `time`, `date-time` | `DatePicker`, `TimePicker`, `DateTimePicker` |
| `integer`, `number` | Integer or numeric `TextInput` |
| `boolean` | `Toggle`; nullable booleans use a three-state `Select` |
| `array` with string, numeric, or Boolean `items` | Simple `Repeater` with the item's string, numeric, enum, date/time, or Boolean editor |
| Other arrays, nullable arrays, `object` | No built-in editor; register a custom `staticForms()` factory |
| `null` | Read-only null value |

The built-in fields follow destination `required` and nullable types, string length limits, and numeric minimum/maximum bounds. Dates serialize as `Y-m-d`, times as `H:i:s`, and date-times as `Y-m-d\TH:i:sP`. Repeaters use `items` to select and validate each field, apply `minItems` and `maxItems`, and use Filament's distinct validation for string and numeric `uniqueItems`. Boolean items use a Select. Empty arrays are allowed unless `minItems` requires entries. `withBuiltinStaticForms()` sets the factory to the standard editors; a later `staticForms()` call replaces it.

There are no built-in JSON editors. Objects, arrays without a supported scalar item type, and nullable arrays offer connections only unless a custom factory is registered. The installed Filament simple repeater cannot hydrate composite items without changing their values or preserve `null` separately from `[]`.

Use `staticForms()` to select a custom inline Filament editor for each destination:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use PHPinnacle\Eidos\Forms\JsonSchema;

FieldMapping::make('mapping')
    ->jsonSchema(source: $sourceSchema, dest: $destinationSchema)
    ->staticForms(fn (string $target, JsonSchema $property) => match ($target) {
        'name' => TextInput::make('value')->hiddenLabel()->required()->maxLength($property->maxLength()),
        'is_active' => Toggle::make('value')->label('Enabled')->default(false),
        default => null,
    });
```

The factory returns a fresh field named `value`, or `null` to omit the editor. It receives the literal destination identifier as `$target`, its `JsonSchema` as `$property`, and can use other Filament-injected arguments such as the `FieldMapping` as `$component`. It runs for every destination, including untyped properties, objects and unions. Returning `null` controls editor visibility; it does not prohibit submitted scalar values. There is no fallback text editor or static-value checkbox.

Reuse the standard editors for other destinations without repeating their type selection:

```php
use PHPinnacle\Eidos\Forms\BuiltinStaticForms;

->staticForms(fn (FieldMapping $component, string $target, JsonSchema $property) => $target === 'id'
    ? null
    : BuiltinStaticForms::make($property, in_array($target, $component->getRequiredTargets(), true))
)
```

An unconnected destination immediately shows its configured editor and becomes a static value initialized with the field's default. Every visible editor counts as mapped, including an empty one. Its native validation, state casts, and dehydration callbacks run on submission; `required()` can reject an empty editor. Values retain their field's type, including `false`, zero, arrays, and `null` when allowed. Custom factories must declare their own value validation.

Native static editors bind to `{rowIndex}.static`. Connecting a source replaces and hides the destination editor. Removing the destination's final connection restores it with a fresh copy of the configured default, so edits to nested values do not change future resets. Structural changes use a Livewire render when native child forms are present, keeping their row paths and actions current. Native live fields and actions make their usual requests.

### Source settings

Both `FieldMapping` and `FieldBinding` accept `sourceSettings(array|Closure)`: a native Filament schema displayed inside every source card, including unconnected or unbound sources. A closure can receive the literal source identifier as `$source`. Each source has a separate child schema with its own defaults, validation, relative `Get`/`Set`, callbacks, and dehydration. Use `live()` on fields that must recompute other server-rendered configuration.

```php
->sourceSettings(fn (string $source) => [
    TextInput::make('alias')->default($source)->required(),
    Toggle::make('enabled')->default(true),
])
```

Settings are validated and submitted for every source, including sources with `target: null`. Removing a connection preserves its source settings. Configured settings are submitted inside each row’s `options` array; `kind`, `source`, `target`, and `static` describe the row itself. `getSourceSettings()` returns source-keyed settings with native field dehydration applied, so callbacks such as `outputSchema()` receive canonical values, including KeyValue dictionaries. Reading settings leaves editor state unchanged and does not run validation. The components do not interpret the settings.

`FieldBinding` adds and removes static pairs by rendering its static section, preserving other valid bindings and source settings. With array-defined source and destination schemas, `FieldMapping` updates affected source cards and the server-calculated connection matrix. Live source-setting updates can also refresh type headings without rebuilding other editors. Search, visibility filters, mappings and other drafts remain in place. Source or destination schema callbacks retain full component rendering because they can change the set of fields.

### Presentation and assets

Property `title`, `description`, `x-icon`, and `x-color` provide presentation metadata for mapping cards and binding sources. The property key is the technical name shown beside its title and the identifier used in state and connections. Search includes both the title and property key. `x-color` accepts a registered Filament color name or a literal hex/RGB color for the icon and title. Cards display the technical name, required marker, constraint hints, type chips, description and warnings; omitted icons reserve no space. Formats replace the general type label. UUID schemas display their standard `uuid` format; application-specific annotations remain opaque to these fields. Nullable types add a `null` chip, and enum tooltips list allowed values. `x-badges` accepts a list such as `[['label' => 'Imported', 'color' => 'info']]`; the type chip appears last. For framework schema builders, pass their inline `toArray()` result.

Array chevrons expand item schemas and their scoped constraints, one row per nested level. Field constraints remain in the heading. Details start collapsed and work on connected or disabled cards without changing selection; lines follow card resizing. Source actions appear below the metadata. Colors use Filament's `primary` palette for connections, `warning` for required destinations and `danger` for conflicts, in light and dark themes.

Add source actions through `sourceSettings()` with Filament's native `Actions` component. The callback receives the source identifier, including for disconnected sources and inverse mode:

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;

->sourceSettings(fn (string $source) => [
    Actions::make([
        Action::make('inspect')
            ->label('Inspect ' . $source)
            ->action(fn () => Notification::make()->title($source)->send()),
    ]),
])
```

Capture `$source` in your callback or pass it through `arguments()`. Native action visibility, icons and tooltips remain available. Use the same schema for source settings fields and actions; there is no separate source-action registry.

Both fields accept `headerActions([...])` with native Filament actions. Actions appear on the left of the toolbar, with counters, one optional search input, and the visibility switch at the right. Toolbar padding, action defaults, and switch sizing follow `dense()`; an explicit action `size()` takes precedence. Actions receive the owning field as `$component`, including when there are no source cards or `contained(false)` is configured. Updating only `jsonSchema(source: $schema)` preserves the destination schema. For a schema change that must survive later Livewire requests, save it through native `Set` and read that form state in the next schema declaration:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

[
    Hidden::make('schema')->default($sourceSchema),
    FieldMapping::make('mapping')
        ->jsonSchema(source: fn (Get $get) => $get('schema'), dest: $destinationSchema)
        ->headerActions([
            Action::make('addField')
                ->schema([TextInput::make('field')->required()])
                ->action(function (array $data, FieldMapping $component, Set $set) {
                    $schema = $component->getSourceSchema();
                    $schema['properties'][$data['field']] = ['type' => 'string'];
                    $state = $component->getState();
                    $state[] = [
                        'kind' => 'source', 'source' => $data['field'], 'target' => null,
                        'options' => ['transforms' => []],
                    ];
                    $component->state($state);
                    $component->jsonSchema(source: $schema);
                    $set('schema', $schema);
                }),
        ]),
];
```

`FieldBinding` uses the same action and state API; call its existing `jsonSchema($schema)` method to update the source schema.

During selection, compatible candidates highlight with a check; incompatible cards and their editors dim with a cross, and existing lines fade. Compatible destinations can replace an existing connection or static value. Unavailable clicks preserve the selection and mappings without an error. Connection lines reveal a cross and use danger colors on hover or keyboard focus, together with both endpoint dots; activate the line to disconnect. This custom focus feedback replaces the browser's rectangular outline. Empty card space, including the source-settings footer, selects the card; native buttons and editors keep their own actions. Mark custom interactive areas with `data-field-interactive` to exclude their descendants from card selection and connection clicks. Leave surrounding empty space unmarked when it should still select the card. Outside selection, connected cards retain full contrast and existing incompatible connections remain visible for correction.

Required fields retain an asterisk beside their label. Unmapped required fields use the normal card border; the toolbar shows one mapping progress counter, highlighted in the warning color while required destinations remain unmapped.

Both components support these presentation options:

| Option | Default | Behavior |
| --- | --- | --- |
| `labels(source: ..., dest: ...)` | Source / Destination or Binding | Set the column headings. |
| `columnSpans(source: ..., target: ..., gap: ...)` | `5, 5, 2` | Set relative widths for sources, targets, and the connection area between them. |
| `searchable()` | `false` | Show one shared search input in the toolbar before the visibility switch. |
| `contained()` | `true` | Show the outer border, background, and toolbar with its search, filter, and counters. |
| `dense()` | `false` | Reduce padding and gaps; use 56 px minimum cards instead of 80 px. |
| `compact()` | `false` | Hide field descriptions and show them in title tooltips on hover or focus. |

Labels accept strings or closures; the switches accept booleans or closures. Column spans are positive integers. For example, `->columnSpans(source: 4, target: 7, gap: 1)` gives the target more space while narrowing the connection area. In `FieldMapping::inverse()`, each width follows its source or target side. `FieldBinding` uses the same proportions for headings and rows, and still stacks them on narrow screens. Filament's `columnSpan()` continues to control the whole field's span in its parent form.

`dense()` and `compact()` work independently. Dense source cards use a 4 px gap before source settings. Compact descriptions reserve no layout space. `contained(false)` preserves column headings and editors while hiding search, counters, and the visibility switch, even with `searchable(true)`. Header actions can still render their toolbar. All combinations preserve form values. The FieldMapping toolbar has an All / Mapped / Unmapped switch, defaulting to All. It filters both sides, combines with one query shared by both columns, and preserves all mappings and static values. Filtered cards leave no empty rows; visible cards align at the top of each column. Mapped shows only fields with established source-to-destination connections. Static destination editors appear in All and Unmapped, regardless of their values; they still count toward mapping progress. Changing the filter cancels a pending connection and refreshes visible lines; it makes no server request.

Use `warnings(source: [...], dest: [...])` on `FieldMapping` or `warnings([...])` on `FieldBinding` to highlight fields. Both accept closures and retain warnings for unavailable identifiers. Escape cancels mapping selection; keyboard controls and dark mode are supported.

Connections and disconnections in both fields are deferred by default, including when source settings or static destination forms are present. FieldMapping honors explicit `live()` configuration; native child fields in both components retain their own update settings. Mapping keeps native editor row addresses stable while editing; submission omits inactive static destination drafts and restores array-item connection order. Livewire state may carry `draftTarget` and `position` for those editor drafts and ordering; submitted rows omit these editor fields.

Both fields keep their UI translations in `resources/lang/{locale}/schema-forms.php`, under `phpinnacle-eidos::schema-forms.*`.

The fields require no panel plugin. Eidos registers their shared stylesheet in the page head and loads JavaScript on demand, under the `phpinnacle/eidos` asset group. Shared card, toolbar, and density styles live in that stylesheet to keep rendered markup small. Nested custom components own their assets. Publish assets after changes with `php artisan filament:assets`. JavaScript and CSS are distributed as source files and need no package build. Add the views to your application's Filament theme and rebuild it:

```css
@source '../../../../vendor/phpinnacle/eidos/resources/views/**/*.blade.php';
```

The path above assumes the theme lives at `resources/css/filament/admin/theme.css`.

## Field binding

`FieldBinding` displays separate source and destination cards and supports the presentation options above. Destination forms are always visible. Unbound forms are disabled beneath a translucent overlay with a centered **Bind** icon button and excluded from validation and submission. Click **Bind** to enable an editor. Bound cards have a connecting line with endpoint dots styled like `FieldMapping`. Hover or focus the line to reveal a cross and highlight it and both dots in danger; click it or press Enter or Space to unbind. Bind opens the editor and applies its native defaults. A connection exists when its native `target` is non-null; an explicit null default stays unbound until a value is entered. Bind and unbind update the client state without a server request. Native Filament defaults are prepared when the field renders; binding applies a fresh copy, and unbinding resets the editor draft. Child fields retain their own Livewire update settings. Unbind clears `target` and resets its editor draft; the next Bind applies the current defaults.

The FieldBinding toolbar has an All / Bound / Unbound switch, defaulting to All. It filters source/destination pairs, combines with source search, and preserves bindings, source settings, and editor drafts. Static pairs appear in All and Bound. The native target determines connection counts and filtering, including legitimate `false` and zero values.

Unbound sources are submitted with `target: null` and retain their validated source settings. Their disabled destination editor drafts are excluded. Disabled fields disable binding controls and child inputs. Inline editors keep their own Livewire update settings. On narrow screens the line runs vertically between stacked cards. Source cards do not show a direction arrow. Connection controls remain visible on devices without hover. Hover and focus feedback starts after 100 ms and fades over 180 ms; leaving has no delay. Reduced-motion preferences disable the fade while retaining the short activation delay.

Binding state is the same flat row list. Each source row contains `kind: source`, its `source` identifier, the editor data in `target` or null when unbound, and configured source settings in `options`. Use `simple()` with a single Filament field to store its native value in `target`. Bound values must be non-null, including after native dehydration; `false`, `0`, and empty arrays are valid. Child fields retain their own validation and conversion rules. Add `required()` only when those stricter rules are wanted: it also rejects empty arrays and strings. An empty string is allowed if the child preserves it; fields that convert it to `null` fail submission while bound.

Without an explicit field `default()`, each editor defaults to its literal source property key. For other field types, provide a suitable native default, for example `simple(Toggle::make('enabled')->default(false))`. Explicit defaults (including `null`), saved bindings, and edited values retain their native Filament behavior. Defaults do not bind sources automatically; an explicit `null` remains an editor draft until replaced with a non-null bound value. Custom `schema()` forms keep their own defaults.

For example:

```php
use Filament\Forms\Components\TextInput;
use PHPinnacle\Eidos\Forms\FieldBinding;

FieldBinding::make('bindings')
    ->jsonSchema(['type' => 'object', 'properties' => [
        'name' => ['type' => 'string', 'title' => 'Customer name'],
        'email' => ['type' => 'string', 'title' => 'Email address'],
    ]])
    ->simple(TextInput::make('destination')->required());

// [['kind' => 'source', 'source' => 'name', 'target' => 'customer.name', 'options' => []],
//  ['kind' => 'source', 'source' => 'email', 'target' => 'contact.email', 'options' => []]]
```

Use `schema()` to store each binding as an associative array whose keys and values are defined by its child components:

```php
use Filament\Forms\Components\Select;

FieldBinding::make('bindings')
    ->jsonSchema(['properties' => ['name' => ['type' => 'string'], 'email' => ['type' => 'string']]])
    ->schema([
        TextInput::make('destination')->required(),
        Select::make('transform')->options([
            'trim' => 'Trim',
            'lowercase' => 'Lowercase',
        ]),
    ]);

// [['kind' => 'source', 'source' => 'name',
//   'target' => ['destination' => 'customer.name', 'transform' => 'trim'], 'options' => []],
//  ['kind' => 'source', 'source' => 'email', 'target' => null, 'options' => []]]
```

Choose `simple()` or `schema()` for a field. Both accept closures and use native Filament child schemas, including defaults, validation, reactive callbacks, relative state reads, and nested `Repeater` or `Builder` components. `destination` is an example field name, not a reserved key. Destination syntax, duplicate destinations, and transformation behavior belong to the consuming application; the component stores the configured data without interpreting paths or executing transformations.

Use `staticValues()` with `simple()` or `schema()` to enable static pairs. The default is disabled; pass `staticValues(false)` to disable them explicitly. The component supplies a full-width native input with a Value placeholder and an accessible hidden label; it accepts scalar values. **Add static value** creates an immediately connected pair. With `simple()`, it uses the field's explicit default, or the first unused `static_N` destination when no default was configured. The source card shows a readable title (`Static 1`) alongside the technical property (`static_1`), without a separate section heading. Non-string simple targets are displayed as JSON. The right card reuses the configured field or schema without changing child names or rules. Hover or focus the connecting line to reveal a danger trash button, which removes the pair including its value and settings.

```php
FieldBinding::make('bindings')
    ->jsonSchema(['properties' => ['name' => ['type' => 'string'], 'email' => ['type' => 'string']]])
    ->simple(TextInput::make('destination')->required())
    ->staticValues();

// [['kind' => 'source', 'source' => 'name', 'target' => 'customer_name', 'options' => []],
//  ['kind' => 'source', 'source' => 'email', 'target' => null, 'options' => []],
//  ['kind' => 'static', 'target' => 'status', 'static' => 'active', 'options' => []]]
```

With `schema()`, each static pair receives the whole binding form and its own defaults, validation, nested components, and dehydration callbacks. Its source card is titled **Static value**, independently of the form's contents. The whole form is stored in `target`; no field is treated as the destination identifier, and no generated name is inserted into the form. Empty forms are stored as `[]`.

```php
use Filament\Forms\Components\Toggle;

FieldBinding::make('bindings')
    ->jsonSchema(['properties' => ['name' => ['type' => 'string']]])
    ->schema([
        TextInput::make('channel')->required(),
        Toggle::make('enabled')->default(false),
    ])
    ->staticValues();

// [['kind' => 'source', 'source' => 'name', 'target' => null, 'options' => []],
//  ['kind' => 'static', 'target' => ['channel' => 'audit', 'enabled' => false], 'static' => 'active', 'options' => []]]
```

`sourceSettings()` also supplies settings after Value in static cards. Its callback may accept `?Closure $inputSchema = null`: static cards receive a closure returning their current input `JsonSchema`, while ordinary sources leave this argument null. For a static card, `$source` is its internal row key. Custom settings components may read the current input schema through this closure; existing native scalar values retain their types. Native dehydrated settings are included in the static row’s `options` array, alongside `static` and `target`.

Without `staticValues()` the component rejects static rows on submission, and the bound-sources counter never includes them. This replaces the former `constants([...])` schema registration.

Source bindings and static values share one serialized list. With `simple()`, static targets must be unique, compared with their native types. A static target may share a source identifier because their rows have explicit kinds; no value is overwritten. With `schema()`, repeated form values are allowed; the component does not infer uniqueness rules for any child field. Uniqueness of actual destinations across source and static rows remains the consumer's responsibility.

Use `sourceSettings(fn (string $source) => [Actions::make([...])])` for native source actions, as with `FieldMapping`. They remain available on unbound sources.

`jsonSchema(array|Closure)` supplies the source object schema and the same property metadata as `FieldMapping`. Its `required` annotations describe source properties; they do not require the user to bind them. The native `schema()` method continues to describe the per-binding Filament editor. Source identifiers retain their literal identity, including numeric identifiers and dots. `labels(source: 'Source', dest: 'Binding')` sets the headings and accepts closures. Configuration is trusted developer code; source identifiers and binding shapes are validated on submission, while the child fields define the value validation.

`getBindingValues()` reads the current public row list for callbacks before native child dehydration. Submit the parent schema with `getState()` to run validation and return the list with native child values. Livewire stores that same list. Complex child forms bind to `{rowIndex}.target`; a simple field binds its native value directly to that path while retaining its configured name. Fill forms using the public state shown above. Package views, theme setup and asset publishing are shared with `FieldMapping` as described above.

## Testing

From the package root:

```bash
composer test
npm test
```

From the monorepo root, check all Eidos source files at PHPStan level 10:

```bash
composer phpstan:level10
```

Tests cover schema generation, shared cast registrations, validation outcomes,
exact property selection, search, accessible navigation, annotation preservation,
safe rendering, mapping compatibility, static editors, and native binding forms.

## License

Released under the [MIT License](LICENSE.md).
