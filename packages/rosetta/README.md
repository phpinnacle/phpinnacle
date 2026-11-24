# PHPinnacle Rosetta

`phpinnacle/rosetta` reads the internal metadata storage used by 1C:Enterprise
infobases and translates its positional serialization into typed, immutable PHP
objects. It has no Laravel, Filament, or other framework dependencies.

## Features

- Reads and inflates `Params/DBNames` and `Config` directly from an infobase,
  and reads active extension storage from `SchemaStorage`.
- Parses the positional text serialization used by 1C configuration storage.
- Maps configuration UUIDs to physical database table and column names.
- Describes catalogs, documents, tabular sections, enumerations, accumulation
  registers, and information registers.
- Represents system columns and custom properties with typed field objects.
- Resolves defined types and composite field definitions.
- Supports table filtering, progress callbacks, and bounded chunk reads.
- Provides a native PDO adapter and a small connection contract for other
  database clients.

## Installation

```bash
composer require phpinnacle/rosetta
```

Rosetta requires PHP 8.4, PDO, and zlib. The source database must contain the
standard 1C `Params`, `Config`, and `SchemaStorage` tables.

## Reading metadata with PDO

```php
use PDO;
use PHPinnacle\Rosetta\Connections\PdoConnection;
use PHPinnacle\Rosetta\MetadataLoader;
use PHPinnacle\Rosetta\SerializedDataParser;

$pdo = new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=infobase',
    'username',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$connection = new PdoConnection($pdo);
$loader = new MetadataLoader(new SerializedDataParser);

foreach ($loader->load($connection) as $metadata) {
    echo json_encode(
        $metadata,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    );
}
```

The connection is only used for read queries. Rosetta does not create tables,
persist parsed metadata, or manage the PDO lifecycle.

## Filtering and progress

Pass physical table names to restrict a read. Names are matched
case-insensitively and may also be passed as file paths with an extension.

```php
$metadata = $loader->load(
    $connection,
    only: ['_reference259', '_document638'],
    progress: function (int $processed, int $total) {
        printf("%d of %d\n", $processed, $total);
    },
);
```

The progress callback is invoked once before reading metadata and after every
root object.

## Chunked reads

Storage and defined-type maps can be prepared once and reused across bounded
chunks. This is useful when a consumer wants to coordinate work through its own
queue or batch system.

```php
$storageMap = $loader->storageMap($connection);
$typeMap = $loader->typeMap($connection);
$tableMap = $loader->tableMap($connection);
$total = $loader->count($storageMap);

for ($offset = 0; $offset < $total; $offset += 100) {
    $chunk = $loader->chunk(
        $connection,
        $storageMap,
        $typeMap,
        offset: $offset,
        limit: 100,
        tableMap: $tableMap,
    );
}
```

## Metadata model

Each root object is returned as
`PHPinnacle\Rosetta\Data\MetadataDefinition`. A definition contains its 1C
identifier, physical name and code, metadata kind, logical name, localized
title, system fields, and custom properties. Catalogs and documents may contain tabular
section definitions, while enumerations may contain possible values.

`DBNames` supplies canonical storage identifiers. `SchemaStorage.CurrentSchema`
identifies tables stored in extension slots: a canonical `_document123` in
`SchemaID = 1` resolves to `_document123x1`, and its section resolves to
`_document123_vt456x1`. Names come from the declared schema, without choosing by
row counts, keys, or the existence of similarly named tables. Tables outside
extension storage retain their non-suffixed names. Unfinished extension
restructuring (`Status` other than `100`), malformed schemas, and conflicting
slots fail the read instead of publishing a guessed name.

Filtering uses these resolved physical names. When counting a filtered prepared
map, pass `tableMap: $tableMap` to `count()`. `chunk()` reads the table map itself
if none is supplied. Prepared table maps support `toArray()` and
`TableMap::fromArray()` for consumer-owned serialization.

Tabular sections combine the owner's canonical name with the `VT` name from
`DBNames` before resolving physical storage, for example `_reference396_vt8973`.
Each section includes an owner
reference, `_keyfield`, and its `LineNo` column. JSON includes `sections` for
documents and catalogs with tabular sections; catalogs without sections retain
their existing JSON representation.

Storage suffixes do not change field names, object UUIDs, reference identities,
or section ownership. For example, `_document123_vt456x1` still has the owner
column `_document123_idrref`. This resolves storage for metadata already read
from `Config` and `DBNames`; it does not load extension-owned definitions or
additional extension properties from `ConfigCAS` and `DBNames-Ext-*`.

Catalog codes and document numbers retain their configured numeric or string
type. Numeric codes and numbers expose their precision with zero scale; string
fields retain the configured maximum length and fixed/variable qualifier.
Unbounded strings have no positive length limit: a descriptor without a length
returns `length: null`, omitted from JSON. An explicit zero remains zero.

Accumulation registers expose `_recordertref` as `binary` when the physical
table contains the column. The PDO adapter reads column metadata with a query
that returns zero rows and requires read access to the register table.

Each `MetadataProperty` also exposes an optional `comment`, separate from its
logical name and localized title. Comments are read for catalog and document
attributes, register properties, and tabular-section attributes. Empty or absent
comments become `null`; non-empty text retains whitespace and line breaks.
JSON includes `comment` only when it is non-null. Rosetta returns and serializes
this information; persistence remains the consumer's responsibility.

Fields implement `PHPinnacle\Rosetta\Contracts\Field` and expose their
semantic type and referenced metadata targets. Definitions, properties, values,
and fields implement `JsonSerializable`, so they can be encoded directly.

## Custom database connections

Consumers that do not use PDO can implement
`PHPinnacle\Rosetta\Contracts\Connection`. Rosetta passes SQL and positional
bindings to `select()` and `selectOne()` and expects rows as objects.
`hasColumn()` checks a physical column on the table resolved by the connection.

## Scope

Rosetta intentionally contains no persistence, queue, console, translation, or
UI integration. Consumers remain responsible for those application-level
concerns.

## Testing

```bash
composer install
composer test
```

## License

See [License File](LICENSE.md).
