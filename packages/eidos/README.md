# PHPinnacle Eidos

Eidos generates JSON Schema from Eloquent models and describes writable model
attributes, converts schemas into Laravel validation rules, and provides Filament
JSON Schema pickers and readonly entries.
It supports Laravel 13, Filament 4 or 5, and PHP 8.4 or later.

## Installation

```bash
composer require phpinnacle/eidos
php artisan vendor:publish --tag=phpinnacle-eidos-config
```

Laravel discovers `EidosServiceProvider` automatically. No panel registration
is required.

## Model introspection

```php
use App\Models\Product;
use PHPinnacle\Eidos\Services\EloquentIntrospector;

$introspector = app(EloquentIntrospector::class);
$schema = $introspector->schema(Product::class)->toArray();
$attributes = $introspector->attributes(Product::class);
```

Schemas include database columns, model casts, backed enum values, date formats,
nullability, and public relationships with concrete return types. Relationships
extend one level to avoid cycles; `MorphTo` relationships are omitted because
their related model depends on a record.

Writable attributes include fillable, non-generated columns with their names,
types, nullability, required status, model defaults, enum values, and formats.
They describe data independently of any form implementation.

Schemas and writable attributes are cached per model, connection, database, and
table. To refresh one model after database or model changes:

```php
$introspector->warm(Product::class);
```

## Model sources and cache warming

Configure sources in `config/phpinnacle-eidos.php`:

```php
return [
    'model_sources' => [
        PHPinnacle\Eidos\Services\DeclaredModels::class,
        App\Eidos\CatalogModels::class,
    ],
    'model_discovery' => [
        'except' => ['Illuminate', 'Filament'],
    ],
];
```

Each source implements
`PHPinnacle\Eidos\Contracts\ProvidesEloquentModels::modelClasses(): array`,
returning a list of Eloquent model class names. Sources resolve through Laravel's
container in `EidosServiceProvider` and must work independently of a panel or
request. `ModelRegistry` receives resolved sources keyed by their class names;
Laravel shares one registry instance across consumers.

`DeclaredModels` discovers already declared, instantiable Eloquent classes,
excluding base classes named `Model` and configured namespace prefixes. It does
not scan files or autoload models. `ModelRegistry` combines sources and removes
duplicate model classes. The default source list is empty.

```bash
php artisan eidos:cache-eloquent-schemas
```

The command refreshes each configured model once. An empty catalog is a
successful no-op. Writable attributes are invalidated and rebuilt on demand.

## Laravel validation rules

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

Return the result from a Form Request or pass it to Laravel's validator. The
factory accepts any Laravel `Type`, including Eidos's `Schema` adapter. The
optional second argument adds application rules; they precede inferred rules,
including on nested properties and across `allOf` branches. The optional third
argument prefixes attribute names, for example `make($schema, [], 'payload')`.
Application rules must use the complete prefixed names.

Supported rules cover scalar types, nullable types, UUID/email/date formats,
scalar enums, string/numeric/item bounds, required object properties and array
item wildcards. Optional parent objects remain optional; their required keys
are checked when the object is supplied. A prefixed schema itself remains
optional unless application rules require it. This is a Laravel rule converter,
not a complete JSON Schema validator. Other constraints, such as patterns,
multiples and item uniqueness, need explicit Laravel rules.

`x-validation` annotations add Laravel rules after inferred rules. Use the
Eidos adapter to preserve these annotations. `anyOf` accepts at least one
matching branch and `oneOf` requires exactly one; branches use their complete
inferred Laravel rules. Alternative validation requires an attribute prefix,
including for a root composition. `allOf` combines branch rules and can be used
at the request root. Failure messages are translated in English and Russian.

## Schema compositions

`PHPinnacle\Eidos\JsonSchema\Schema::fromArray()` supports nested `oneOf` and
`allOf`, local references, sibling properties and required-only object branches:

```php
use PHPinnacle\Eidos\JsonSchema\Schema;

$payment = Schema::fromArray([
    'type' => 'object',
    'properties' => [
        'card' => ['type' => 'object'],
        'token' => ['type' => 'string'],
    ],
    'oneOf' => [['required' => ['card']], ['required' => ['token']]],
]);

$rules = app(RuleFactory::class)->make($payment, [], 'payment');

$quantity = Schema::allOf([
    JsonSchema::integer()->min(1),
    JsonSchema::integer()->max(10),
]);
```

`Schema::oneOf()` and `Schema::allOf()` accept lists of Laravel types and Eidos
adapters. They retain nested annotations without serializing and reparsing the
supplied types. `nullable()` permits null for the complete composition. Use
`Schema::fromArray()` to place compositions inside object properties or array
items; Eidos adapters remain root schemas rather than children of Laravel's
native builders. Picker and entry traversal include all three composition
keywords, with scoped limits and annotations.

## JSON Schema picker and entry

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

The picker panel matches its trigger width and renders its tree only while open. Both components support `->searchable()` and `->searchable(false)` (also accepting Filament closures). Search is on by default for the picker and off by default for the entry. It runs locally over technical property paths and all schema titles, and includes descriptions, types and enabled format and enum annotations and value constraints. Disabling local search clears its current query. Styles, translations, Heroicons and lazy Alpine components are supplied by Eidos; no workflow component or application frontend build is required.

Schema titles are always the main label with technical property names beside them in muted text. Missing titles are generated from property names with `Str::apa(Str::headline($name))`, such as `unit_price` → `Unit Price`; explicit schema titles take precedence. Technical field names are shown beside titles by default. Use `->properties(false)` to hide them or `->properties()` to show them; the option also accepts a Filament closure and applies to the tree and selected picker value. Hiding names does not change selection paths or local search. Enable `->requiredMarker()` to mark required properties with a red asterisk beside the main label, and `->constraints()` to display value restrictions. Both are hidden by default and accept a boolean or Filament closure; pass `false` to hide them again. Required markers describe the schema's parent-object `required` list and do not change picker validation (`->required()` still configures the picker itself). A path is marked required only when it is required in every `anyOf` variant. Constraints include `minLength`/`maxLength`, `pattern`, `minimum`/`maximum`, `multipleOf`, `minItems`/`maxItems` and `uniqueItems`, with scopes for union variants and separate rows for array items. Titles are always searchable; constraint values participate in search only when enabled. Selection paths contain exact technical property names as separate segments. Rows use a fixed disclosure slot with a consistent indentation step across objects and array item fields. Icon space is added only to fields with a rendered icon. Descriptions align with that field's label. Value limits and nullable badges precede the type badge, which is always last in the metadata area. Type badges and value limits share the same small monospace typography; uniqueness uses a warning-colored `unique` chip with the same border, background and padding as type badges. Other value limits form a muted group without individual badge borders or repeated label/value prefixes. Numeric bounds use inequalities, multiples use `×`, patterns use `/…/`, string length uses a ruler icon and item count uses a list icon. Inline limits have accessible labels and no popovers; long patterns are truncated visually. Limits popovers are available only in compact mode. Limits on `anyOf` branches retain their scope labels. Value limits wrap within the metadata area; enum values appear in the type badge tooltip. Rows have a single hover and selection background across their full width, with a 40 px minimum height and a fixed 24 px disclosure column. The picker search stays visible while scrolling. Enable `->compact()` for a 32 px minimum row height and descriptions in hover tooltips; field names retain the same font size. Enabled value limits are replaced by a small restrictions icon beside the type badge; hover or focus it to see a titled popover with aligned labels and values, including separate groups for union scopes. Enum values remain in their type badge tooltip in both modes. Compact mode is off by default and accepts a boolean or Filament closure.

Use `PHPinnacle\Eidos\JsonSchema\Schema::fromArray()` for schemas containing extra `x-*` attributes. It returns a Laravel `Type` adapter and stores these attributes per schema node while Laravel handles the schema, including local references and nullable unions. `toArray()` restores them at their original level with their values unchanged, including arrays, objects, booleans and null. Sibling attributes on a local `$ref` override attributes from its target. Pass the resulting schema directly to the picker or entry:

```php
use PHPinnacle\Eidos\JsonSchema\Schema;

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

Client-side consumers can use `<x-phpinnacle-eidos::json-schema-picker>`, `<x-phpinnacle-eidos::json-schema-entry>` and the shared `<x-phpinnacle-eidos::json-schema-tree>` with Alpine expressions. The picker accepts `schema`, `value`, `choose` and `label`; `value` must be a writable Alpine expression for clearing. Its optional boolean `clearable` prop defaults to `true`; set it to `false` for required selections. The entry accepts `schema` and `label`. The tree accepts grouped sources prepared by `schemaGroups()` or `schemaSources()` from `json-schema-tools.js`, selection and choice callbacks, an optional external search expression and readonly display. These adapters share the same assets and renderer as the PHP components.

Schema traversal and source preparation live in `json-schema-definition.js`; popover and color formatting live in `json-schema-presentation.js`. Existing named imports from `json-schema-tools.js` remain available. Publish assets with `php artisan filament:assets` after updating Eidos so the imported modules are available beside the Alpine components.

At the Alpine boundary, `schema` is the serialized object from `$schema->toArray()` and `value` is a JavaScript array of exact path segments or `null`. Supply the optional `icons` prop as a trusted map of Blade icon names to server-rendered SVG strings to display `x-icon` annotations. PHP components prepare this map automatically. Supply the optional `propertyTitles` prop as a property-name-to-title map to generate fallback titles; `PropertyTitles::render($definition)` prepares it using Laravel string formatting. PHP components supply this map automatically. A choice callback receives that array as `source.value`; `source.key` is the internal tree identifier. Readonly array item previews have `source.value === null` and `source.compatible === false`; selection callbacks ignore them. Their wildcard labels are display text and do not become property path segments.

Both client-side adapters accept a boolean `descriptions` prop, defaulting to `true`, for description text and row hover descriptions; the shared tree accepts an Alpine `descriptions` expression. Both client-side adapters accept a boolean `colors` prop, defaulting to `true`, to control `x-color` display; grouped sources accept the same `colors` option. Both client-side adapters also accept a boolean `properties` prop, defaulting to `true`, to control technical names beside titles. The shared tree accepts an Alpine `properties` expression. Both client-side adapters accept optional boolean `format`, `required`, `constraints` and `compact` props, all defaulting to `false`. The `required` prop controls schema markers only. Both adapters also accept a boolean `searchable` prop, defaulting to `true` for the picker and `false` for the entry.

## Package structure

The layout follows the PHPinnacle skeleton: `src/`, `config/`, `tests/`,
`database/migrations/`, `resources/views/`, `resources/lang/`, and `stubs/`,
with Composer metadata, PHPUnit configuration, a changelog, and an MIT license.
The JSON Schema components include Blade views, translations, CSS and lazy
Alpine modules. Eidos ships no migrations.

## Development

```bash
composer install
composer test
npm test
```

In the host checkout, run integration tests from the application root:

```bash
php artisan test --compact tests/Feature/EidosIntrospectionTest.php tests/Feature/DecimaIntrospectionTest.php
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
