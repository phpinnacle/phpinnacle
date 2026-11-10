# Jason

Jason provides immutable JSON Schema values, builders, and JSONPath queries for PHP 8.4 or later.
It has no runtime package dependencies and does not validate JSON instances against schemas.

```bash
composer require phpinnacle/jason
```

## Build a schema

`Schema` is an immutable value. Configure schemas with `SchemaBuilder`, then call
`build()` to obtain an independent snapshot:

```php
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;

$schema = SchemaBuilder::object([
    'email' => SchemaBuilder::string()
        ->format('email')
        ->title('Email address')
        ->build(),
    'tags' => SchemaBuilder::array()
        ->items(Schema::string())
        ->maxItems(10)
        ->build(),
])
    ->required(['email'])
    ->withoutAdditionalProperties()
    ->build();

$definition = $schema->toArray();
$json = $schema->toJson();
```

Builder methods change that builder and return it. `$schema->builder()` or
`SchemaBuilder::make($schema)` starts from an existing schema without changing it:

```php
$updated = $schema->builder()->title('Customer')->build();
$withoutEnum = $schema->builder()->without('enum')->build();
```

`without(string ...$keywords)` removes keywords; absent keywords are a no-op.
Removal differs from assigning null and leaves the source schema unchanged.

Later builder changes and nested annotation mutations cannot change a schema
already returned by `build()`.
`required([...])` declares property names on the containing object; it is not a
flag on a property's schema. `default(null)` remains distinct from no default.
Use `with($keyword, $value)` for custom keywords and annotations such as `x-custom`.

Both classes provide `empty()`, `object($properties = [])`, `array()`, `string()`,
`integer()`, `number()`, `boolean()`, `null()`, and `union([...])` factories.
`Schema` factories return immutable values; `SchemaBuilder` factories start fluent
configuration. Object properties, schema maps, and composition lists accept built
`Schema` values. Create compositions with `Schema::anyOf([...])`,
`Schema::oneOf([...])`, or `Schema::allOf([...])`, or use the corresponding instance
methods on a builder.

On the builder, `nullable()` permits null for the complete schema, including enum
and composition constraints; `nullable(false)` excludes null. Simple typed or
untyped enums add or remove `null` directly in their `enum` values and, when
present, `type`. Other values and annotations are preserved; removing the last
allowed value produces the false schema. For example, a nullable string enum is
`{"type":["string","null"],"enum":["draft","published",null]}` without an
`anyOf` wrapper. Composition, const, conditional, and reference constraints retain
their outer nullability wrapper. When nullability
requires wrapping the schema, local references are expanded first so their JSON
Pointer paths remain valid. Local JSON Pointer `$dynamicRef` values are relocated
with the wrapped schema; anchors and references within `$id` resources retain
their existing addresses. `min()` and `max()` set length, item count, or numeric
bounds according to a single primitive type. Explicit methods such as
`minimum()`, `maxLength()` and `minItems()` set their named keywords directly.

`readOnly()`, `writeOnly()`, `deprecated()`, and `uniqueItems()` default to `true`;
pass `false` to disable the flag. On a string builder, `uuid()`, `date()`,
`dateTime()`, and `time()` are shortcuts for `format('uuid')`, `format('date')`,
`format('date-time')`, and `format('time')`:

```php
$id = SchemaBuilder::string()->uuid()->readOnly()->build();
$createdAt = SchemaBuilder::string()->dateTime()->readOnly()->build();
```

## Project composed schemas

Structural readers retain the declared keywords. For composition-aware reads, resolve
local references first, then use the projection methods on that separate value:

```php
$view = $schema->resolveReferences();
$properties = $view->effectiveProperties();
$required = $view->effectiveRequired();
$types = $view->types();
$maximum = $view->effectiveMaxLength();
```

`effectiveProperties()` returns the union of declared names from root properties,
then `allOf`, `anyOf`, and `oneOf` branches in first-seen order. Same-name alternatives
use `anyOf`; root and `allOf` constraints remain conjunctive. Each branch applies
the named-property rules below, including matching patterns or additional-property
constraints when the name is undeclared. Open properties contribute unrestricted
schemas. Field annotations stay on their contributing schemas. A prohibited
declared field can project to `false`.
These names do not guarantee presence in data: consult `required` separately.
`effectiveRequired()` merges `allOf` branches.
`types()` intersects sibling and conjunctive types with unions of alternatives,
including `integer` within `number`. Unrestricted alternatives remain unrestricted;
`singleType()` excludes null when identifying one type.
`effectiveItems()` combines positional and tail item constraints; `arrayItems()` returns them only for
an array type, using an empty object schema when items are unrestricted.
`property($name)` projects a named property's constraints across compositions,
combining its declared schema with every matching `patternProperties` schema.
`additionalProperties` applies only when neither a declared property nor a pattern
matches. Unavailable names and schemas excluding objects return null; an explicitly
declared boolean property schema remains a `Schema` value.
Pattern matching is unanchored unless the pattern uses anchors. Jason executes
patterns with PHP PCRE in Unicode mode; use patterns shared by PCRE and ECMA-262,
since it does not translate between regular-expression dialects.
`enumValues()` intersects `allOf` enum restrictions; `allowsEnumValue($value)`
checks those restrictions using JSON value equality.
`effectiveMinimum()`, `effectiveMaximum()`, `effectiveMinItems()` and
`effectiveMaxItems()` intersect their declared bounds with `allOf` branches.
`effectiveUniqueItems()` is true when the root or an `allOf` branch requires
unique items. These numeric and array readers do not combine alternative branches.
`effectiveMinLength()` intersects conjunctive minimum lengths;
`effectiveMaxLength()` also includes the widest string alternative, returning null
for an unbounded alternative. Alternatives that cannot permit strings, including
`false`, do not remove a string length bound. `alternatives()` projects `anyOf` or
`oneOf` branches with their sibling constraints. `annotation($name)` returns a
declared value or the first non-null value from composition branches;
`effectiveFormat()` uses that lookup for `format`. Extension values remain opaque to Jason.

These projections do not validate payloads or prove arbitrary schema equivalence
or containment. UI presentation, connection policy, and application transformations
belong to consumers.

`equals($other, bool $strict = false)` compares stored schema definitions by JSON value: object key
order is ignored, list order and scalar types are preserved, and numbers compare
by value. With `strict: true`, key order and numeric types (`1` versus `1.0`) also
matter. Both modes distinguish absent keywords from explicit null and compare
nested objects structurally, without requiring identical `stdClass` instances.
Neither mode proves the meaning of differently expressed schemas equivalent.

## Read and preserve definitions

```php
$schema = Schema::fromArray([
    '$defs' => ['name' => ['type' => 'string', 'default' => null]],
    'properties' => ['name' => ['$ref' => '#/$defs/name']],
    'x-metadata' => ['enabled' => false],
]);

$original = $schema->toArray();
$view = $schema->resolveReferences();
$name = $view->properties()['name'];
$type = $name->type(); // 'string'
$hasDefault = $name->hasKeyword('default'); // true
$default = $name->default(); // null
```

`fromArray()` accepts a trusted schema object definition or a boolean schema.
It preserves the supplied keywords, references, unknown annotations, and
composition structure. It does not infer undeclared types or rewrite schemas
through a restricted type hierarchy. `fromJson()` parses JSON schema objects or
booleans. `toJson()` and `json_encode($schema)` preserve JSON objects versus
arrays, including empty schema objects, numeric property names, and object
values inside defaults or enums. JSON objects supplied through `fromArray()`
as annotation values use `stdClass`; PHP arrays represent JSON arrays or maps
according to their keys. Schema-valued object positions always encode as objects.
`dependentRequired` and `$vocabulary` use PHP maps internally and always encode
as JSON objects, including empty maps and numeric dependency names. Their typed
readers return arrays after either `fromArray()` or `fromJson()`.

Read standard keywords through typed methods:

| Fields | Methods |
| --- | --- |
| Type and object requirements | `type()`, `required()`, `dependentRequired()` |
| Annotations and values | `title()`, `description()`, `default()`, `const()`, `enum()`, `examples()`, `readOnly()`, `writeOnly()`, `deprecated()` |
| Numeric bounds | `minimum()`, `maximum()`, `exclusiveMinimum()`, `exclusiveMaximum()`, `multipleOf()` |
| String constraints | `minLength()`, `maxLength()`, `pattern()`, `format()` |
| Collection constraints | `minItems()`, `maxItems()`, `uniqueItems()`, `minContains()`, `maxContains()`, `minProperties()`, `maxProperties()` |
| Schema maps | `properties()`, `patternProperties()`, `dependentSchemas()`, `defs()` |
| Nested schemas | `items()`, `prefixItems()`, `contains()`, `additionalProperties()`, `unevaluatedProperties()`, `unevaluatedItems()`, `propertyNames()`, `not()`, `if()`, `then()`, `else()` |
| Identifiers and vocabulary | `schema()`, `id()`, `ref()`, `anchor()`, `dynamicRef()`, `dynamicAnchor()`, `vocabulary()`, `comment()` |
| Content | `contentEncoding()`, `contentMediaType()`, `contentSchema()` |

The builder has corresponding write methods; names for `$` keywords omit the `$`.
Read composition lists with `branches('anyOf')`, `branches('oneOf')` or
`branches('allOf')`; write them with the builder's `anyOf()`, `oneOf()` or `allOf()`.
Maps and lists of nested schemas return `Schema` values. These are structural
queries; they do not flatten compositions, infer types or validate instances.
Absent scalar values return null; maps and lists return empty arrays, except
`enum()` which returns null when absent. `hasKeyword()` distinguishes an absent
keyword from an explicit null, false or empty value. `type()` retains the declared
string or list form.

`allowsAny()` identifies the literal schema `true`; `deniesAny()` identifies the
literal schema `false`. Both return false for object schemas, including `{}` and
`{"type":"boolean"}`. They do not attempt to prove schema equivalence.
`Schema::empty()` creates exactly `{}`; `isEmpty()` checks that the definition has
no keywords. It returns false for boolean schemas, `Schema::null()`, and nonempty
definitions that happen to impose no restrictions.

Use `keyword()` for extension values and `keywords()` for the complete keyword
map. Boolean schemas have no keywords; `toArray()` retains their boolean value.
Object annotation values are copied at input and output boundaries so callers
cannot mutate a schema through a nested `stdClass`.

Reference expansion returns a separate schema. It supports local JSON Pointer
references, including percent-encoded and tilde-escaped tokens. Expanded references
join any existing `allOf` alongside the original sibling keywords, preserving the
evaluation scope of `unevaluatedProperties` and `unevaluatedItems`. Referenced schemas
are appended to `allOf`, retaining existing indexes and definition containers for
preserved dynamic references. Expansion rejects external or anchor references
and references within embedded `$id` resources,
including fragments reached through JSON Pointers. Opaque annotation objects
containing a `$id` property do not establish schema resources. `$dynamicRef` values
are preserved without expansion. Recursive references can be stored and serialized;
full expansion rejects cycles and limits expansion to 20,000 fragments.
Traversal and codecs recognize the schema positions used by Draft 2020-12, including
`$defs`, compositions, `prefixItems`, conditional schemas, and additional or unevaluated
properties. Legacy tuple-style `items` are outside this API's supported structural subset.
`additionalItems` is preserved as an opaque annotation, without schema traversal.

## Schema navigation

`walk($visitor)` visits the root and every nested schema in depth-first order.
The visitor receives the `Schema`, its parent keyword, and its map key or list
index. Map keys are strings, including numeric property names; list indexes are
integers. The root receives `null` for both keyword and key; single-schema
keywords have a `null` key.
Definitions, conditional schemas, positional items, Boolean schemas, and every
other supported schema position participate. Defaults, enum values, and unknown
annotations remain opaque. Walking does not expand references.

```php
$fragments = [];
$schema->walk(function (Schema $fragment) use (&$fragments) {
    $fragments[] = $fragment->toArray();
});
```

`hasDeclaredPath($segments)` checks data paths through declared `properties`,
`anyOf`, `oneOf`, `allOf`, and `items`. Pass canonical segments from
`JsonPath::parse()`: strings select object members, integers select array items,
and null selects every item. An empty segment list identifies the root.
A complete path must exist within one composition branch. Definitions and
conditional schemas do not implicitly declare data paths for this lookup.
This checks structure, not instance existence or constraint satisfiability.
Resolve references explicitly when their target structure is needed:

```php
use PHPinnacle\Jason\JsonPath;

$exists = $schema->resolveReferences()->hasDeclaredPath(JsonPath::parse("$['name']"));
```

`atPath(JsonPath|array $segments)` returns the selected value's schema. Strings
select object fields, integers select positional or tail items, and null selects
all children. Compositions retain sibling constraints. Nonempty paths expand local
references using the same supported subset and errors as `resolveReferences()`.
An empty path returns the original schema, including literal boolean schemas.

```php
use PHPinnacle\Jason\JsonPath;
use PHPinnacle\Jason\JsonQuery;
use PHPinnacle\Jason\Schema;

$schema = Schema::object(['name' => Schema::string()]);
$payload = ['name' => 'Ada'];
$path = new JsonPath("$['name']");
$resultSchema = $schema->atPath($path);
$values = new JsonQuery($path)->find($payload); // ['Ada']
```

Any wildcard wraps the selected schema in one array of matches. Multiple wildcards
produce a flat list; a selected array remains an array inside that list. Wildcards
also select object members. Open additional properties and unrestricted items
contribute unrestricted schemas; prohibited or structurally impossible paths
return null. Object wildcards include `patternProperties` candidates alongside
declared and additional fields, retaining composition constraints. These pattern
candidates are a conservative union; wildcard projection does not solve which
property names satisfy overlapping patterns. A selected JSON null is represented
by `Schema::null()`, and an explicitly declared property's boolean schema remains a
schema value. This is a structural projection, not a validator or a proof of instance
existence or arbitrary schema satisfiability.

## JSONPath

`PHPinnacle\Jason\JsonPath` formats and parses canonical paths without requiring
a schema:

```php
use PHPinnacle\Jason\JsonPath;

$path = JsonPath::fromSegments(['items', null, 'name']); // $['items'][*]['name']
$segments = JsonPath::parse($path); // ['items', null, 'name']
$property = JsonPath::property('literal.key'); // ['literal.key']

$value = new JsonPath($segments); // or new JsonPath($path), using the same parser
$segments = $value->segments; // readonly canonical segments
$path = (string) $value;
```

String segments are literal property names, integer segments are nonnegative
array indexes from `0` through `9007199254740991` (`2^53 - 1`), and `null` represents `[*]`.
The PHP and JavaScript parsers enforce the same range; trusted segment lists passed
directly to constructors or formatters must use that range too. Quoted numeric
names remain distinct from indexes, and quoted asterisks remain distinct from
wildcards. Parsing rejects malformed paths and noncanonical spelling. This is a
restricted path codec, not a full JSONPath query evaluator.

The dependency-free ES module provides the same path syntax:

```js
import { jsonPath, parseJsonPath } from './vendor/phpinnacle/jason/resources/js/json-path.js'

const path = jsonPath(['items', null, 'name'])
const segments = parseJsonPath(path)
```

Consumers own bundling or publishing the JavaScript module.

## Query and transform JSON data

`JsonQuery` selects and transforms PHP arrays representing JSON payloads.
Its constructor accepts `JsonPath|array|string`: use a canonical object or segment
list to share a parsed path with `atPath()`, or a string for the broader query
language below. Payloads remain PHP values throughout execution.

Container types follow `array_is_list()`: lists (including `[]`) represent JSON
arrays; all other PHP arrays represent JSON objects. String segments select only
object properties, and integer segments select only array elements, for every
constructor input and mutation. Thus `$['0']` does not match a list element,
`$[2]` does not match a key in an associative array, and `$['2']` selects property
`2` in an associative array even when PHP stores that key as an integer. Wildcards
select children of either container type.

```php
use PHPinnacle\Jason\JsonQuery;

$payload = [
    'items' => [['name' => ' Ada ', 'active' => true]],
    'metadata' => ['version' => 'v0'],
];

$query = new JsonQuery('$.items[?(@.active == true)].name');
$names = $query->find($payload);
$trimmed = $query->update($payload, fn ($name) => trim($name));
$withoutNames = $query->remove($payload);
$withVersion = new JsonQuery('$.metadata.version')->set($payload, 'v1');
```

Queries support dot and bracket property names, escaped quoted names, wildcards,
unions, negative indexes, slices with an optional step, and recursive descent.
Integer selectors, slice bounds, and slice steps in string queries must be within
`-9007199254740991` through `9007199254740991`; a slice step cannot be zero. This
range does not limit quoted numeric property names or numeric filter literals.
Filters support `@` and `$` references, scalar literals, strict equality and
inequality, ordered comparisons, `and`/`or`/`not` (also `&&`/`||`/`!`), membership
with `in [...]`, and regular expression comparisons with `=~ /pattern/flags`.
A filter containing only a regular expression matches child property names.
Dot notation `.length` reads stored properties when present in a selection,
otherwise it computes lengths. Inside filters, a terminal `.length` always
measures an array or string; other types produce `false`. Computed lengths are
read-only. The query language does not claim RFC 9535 compliance or support script
evaluation or JSONPath function extensions.

`find()` always returns a list, including `[null]` for a selected null and `[]`
when nothing matches. In filters, a missing reference evaluates to `false`, and
booleans do not compare to other types. Numeric filter operands compare as floats.
References use the first selected value. Bare reference filters use PHP truthiness
rather than testing whether the reference exists.
All mutations return the result without changing the input. `update()` visits
existing nodes; `set()` also creates missing properties in objects and missing
indexes in arrays. It skips incompatible containers and leaves scalar parents
untouched. Missing intermediate branches start as `[]`, so subsequent indexes can
extend them, but subsequent property selectors cannot turn them into objects.
For example, `$.version` can add a property to an existing associative array,
while `$.metadata.version` cannot create a missing object parent. `remove()` matches paths,
preserves unselected equal values, and compacts lists after deletion. Removing
`$` returns `[]`. Invalid query syntax throws `InvalidArgumentException` when
constructing the query, even if the payload would have no matches.

## Testing

Run from the package directory:

```bash
composer install
composer test
npm ci
npm test
```

PHP and JavaScript tests run independently.

Released under the [MIT License](LICENSE.md).
