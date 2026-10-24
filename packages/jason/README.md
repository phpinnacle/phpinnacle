# Jason

Jason provides immutable JSON Schema values, builders, and JSONPath queries for PHP 8.4 or later.
It has no runtime package dependencies.

```bash
composer require phpinnacle/jason
```

## Build a schema

```php
use PHPinnacle\Jason\Schema;

$email = Schema::string()->format('email');
$schema = Schema::object([
    'email' => $email->title('Email address'),
    'tags' => Schema::array()->items(Schema::string())->max(10),
])->required(['email'])->withoutAdditionalProperties();

$definition = $schema->toArray();
$json = $schema->toJson();
```

Every fluent method returns a new schema. Reusing `$email` does not change it.
`required([...])` declares property names on the containing object; it is not a
flag on a property's schema. `default(null)` remains distinct from no default.
`with($keyword, $value)` retains arbitrary keywords and annotations without
interpreting application-specific names such as `x-custom` or `x-metadata`.

Factories include `object()`, `array()`, `string()`, `integer()`, `number()`,
`boolean()`, `null()`, `union([...])`, `anyOf([...])`, `oneOf([...])`, and
`allOf([...])`. Compositions accept lists of schemas. `nullable()` permits null
for the complete schema, including enum and composition constraints;
`nullable(false)` excludes null. When nullability requires wrapping the schema,
local references are expanded first so their JSON Pointer paths remain valid.
`min()` and `max()` set length, item count, or
numeric bounds according to the builder's primitive type. Use `with()` for
constraints on schemas without a single primitive type.

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

Use `keyword()` and `hasKeyword()` to read individual keywords, `keywords()`
for an object's keywords, `properties()` for declared property schemas,
`itemSchema()` for declared array items, and `branches($keyword)` for composition
branches. These are structural queries, not a flattening or validation of the
schema. Boolean schemas have no keywords; `toArray()` retains their boolean value.
Object annotation values are copied at the input and output boundaries so callers
cannot mutate a schema through a nested `stdClass`.

Reference expansion returns a separate schema. It supports local JSON Pointer
references, including percent-encoded and tilde-escaped tokens. Reference siblings
remain conjunctive constraints through `allOf`. Expansion rejects external
references, anchors, and references within embedded `$id` resources. Recursive
references can be stored and serialized; full expansion rejects cycles and limits
expansion to 20,000 fragments. Traversal and codecs recognize the schema positions used by
Draft 2020-12, including `$defs`, compositions, `prefixItems`, conditional schemas,
and additional or unevaluated properties. Legacy tuple-style `items` are outside
this API's supported structural subset.

Jason represents and transforms schemas. It does not validate JSON instances
against a schema.

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

## JSONPath

`PHPinnacle\Jason\JsonPath` formats and parses canonical paths without requiring
a schema:

```php
use PHPinnacle\Jason\JsonPath;

$path = JsonPath::fromSegments(['items', null, 'name']); // $['items'][*]['name']
$segments = JsonPath::parse($path); // ['items', null, 'name']
$property = JsonPath::property('literal.key'); // ['literal.key']
```

String segments are literal property names, integer segments are nonnegative
array indexes, and `null` represents `[*]`. Quoted numeric names remain distinct
from indexes, and quoted asterisks remain distinct from wildcards. Parsing rejects
malformed paths and noncanonical spelling. This is a restricted path codec, not
a full JSONPath query evaluator.

The dependency-free ES module provides the same path syntax:

```js
import { jsonPath, parseJsonPath } from './vendor/phpinnacle/jason/resources/js/json-path.js'

const path = jsonPath(['items', null, 'name'])
const segments = parseJsonPath(path)
```

Consumers own bundling or publishing the JavaScript module.

## Query and transform JSON data

`JsonQuery` selects and transforms PHP arrays representing JSON payloads. It is
separate from the canonical `JsonPath` codec, whose syntax remains unchanged.

```php
use PHPinnacle\Jason\JsonQuery;

$query = new JsonQuery('$.items[?(@.active == true)].name');
$names = $query->find($payload);
$trimmed = $query->update($payload, fn ($name) => trim($name));
$withoutNames = $query->remove($payload);
$withVersion = new JsonQuery('$.metadata.version')->set($payload, 'v1');
```

Queries support dot and bracket property names, escaped quoted names, wildcards,
unions, negative indexes, slices with an optional step, and recursive descent.
Filters support `@` and `$` references, scalar literals, strict equality and
inequality, ordered comparisons, `and`/`or`/`not` (also `&&`/`||`/`!`), membership
with `in [...]`, and regular expression comparisons with `=~ /pattern/flags`.
A filter containing only a regular expression matches child property names.
Dot notation `.length` reads stored properties when present in a selection,
otherwise it computes lengths. Inside filters, a terminal `.length` always
measures an array or string; other types produce `false`. Computed lengths are
read-only. This query syntax preserves the webhook
operations previously provided by Galbar; it does not claim RFC 9535 compliance
or support script evaluation or JSONPath function extensions.

`find()` always returns a list, including `[null]` for a selected null and `[]`
when nothing matches. In filters, a missing reference evaluates to `false`, and
booleans do not compare to other types. Numeric filter operands compare as floats,
as in the previous webhook engine. References use the first selected value.
All mutations return the result without changing the input. `update()` visits
existing nodes; `set()` also creates missing named/indexed destination branches
inside arrays and leaves scalar parents untouched. `remove()` matches paths,
preserves unselected equal values, and compacts lists after deletion. Removing
`$` returns `[]`. Invalid query syntax throws `InvalidArgumentException` when
constructing the query, even if the payload would have no matches.

## Testing

```bash
composer install
composer test
npm ci
npm test
```

PHP and JavaScript tests run independently.

Released under the [MIT License](LICENSE.md).
