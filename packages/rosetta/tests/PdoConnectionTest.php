<?php

namespace PHPinnacle\Rosetta\Tests;

require_once __DIR__ . '/Fixtures/MetadataFixture.php';

use InvalidArgumentException;
use PDO;
use PHPinnacle\Rosetta\Connections\PdoConnection;
use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\MetadataKind;
use PHPinnacle\Rosetta\MetadataLoader;
use PHPinnacle\Rosetta\SerializedDataParser;
use PHPinnacle\Rosetta\StorageMap;
use PHPinnacle\Rosetta\TableMap;
use PHPinnacle\Rosetta\Tests\Fixtures\MetadataFixture;
use PHPinnacle\Rosetta\TypeMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdoConnectionTest extends TestCase
{
    /** @return iterable<string, array{MetadataKind, int, string, string, array<string, string>, 5?: int}> */
    public static function registerSchemas(): iterable
    {
        yield 'reported accumulation register with one recorder type' => [
            MetadataKind::AccumulationRegister,
            36_841,
            'РасчетыСКлиентамиПоСрокам',
            "_recordertref bytea CHECK (_recordertref = X'0000029a'), _recorderrref bytea, _lineno numeric(9,0)",
            ['_recordertref' => 'binary', '_recorderrref' => 'binary', '_lineno' => 'number'],
        ];
        yield 'accumulation register with several recorder types' => [
            MetadataKind::AccumulationRegister,
            1,
            'MultipleRecorders',
            "_recordertref bytea CHECK (_recordertref IN (X'0000029a', X'0000029b')), _recorderrref bytea, _lineno numeric(9,0)",
            ['_recordertref' => 'binary', '_recorderrref' => 'binary', '_lineno' => 'number'],
        ];
        yield 'accumulation register with only a recorder reference column' => [
            MetadataKind::AccumulationRegister,
            2,
            'RecorderReference',
            '_recorderrref bytea, _lineno numeric(9,0)',
            ['_recorderrref' => 'binary', '_lineno' => 'number'],
        ];
        yield 'extended accumulation register' => [
            MetadataKind::AccumulationRegister,
            4,
            'ExtendedRegister',
            '_recordertref bytea, _recorderrref bytea, _lineno numeric(9,0)',
            ['_recordertref' => 'binary', '_recorderrref' => 'binary', '_lineno' => 'number'],
            1,
        ];
        yield 'independent information register' => [
            MetadataKind::InformationRegister,
            3,
            'IndependentRegister',
            '_fld1 integer',
            [],
        ];
    }

    #[Test]
    public function it_reads_rows_through_a_native_pdo_connection(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE Config (filename TEXT PRIMARY KEY, binarydata BLOB NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO Config (filename, binarydata) VALUES (?, ?)');
        $statement->execute(['first', 'first-data']);
        $statement->execute(['second', 'second-data']);

        $connection = new PdoConnection($pdo);
        $row = $connection->selectOne(
            'SELECT binarydata AS data FROM Config WHERE filename = ?',
            ['first'],
        );
        $rows = $connection->select(
            'SELECT filename, binarydata AS data FROM Config WHERE filename IN (?, ?)',
            ['first', 'second'],
        );

        $this->assertSame('first-data', $row === null ? null : get_object_vars($row)['data'] ?? null);
        $this->assertSame(['first', 'second'], array_column($rows, 'filename'));
        $this->assertNull($connection->selectOne(
            'SELECT binarydata AS data FROM Config WHERE filename = ?',
            ['missing'],
        ));
    }

    #[Test]
    public function it_loads_metadata_from_a_native_pdo_connection(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE Params (filename TEXT PRIMARY KEY, binarydata BLOB NOT NULL)');
        $pdo->exec('CREATE TABLE Config (filename TEXT PRIMARY KEY, binarydata BLOB NOT NULL)');
        $pdo->exec('CREATE TABLE SchemaStorage (SchemaID INTEGER, Status INTEGER, CurrentSchema BLOB)');

        $params = $pdo->prepare('INSERT INTO Params (filename, binarydata) VALUES (?, ?)');
        $params->execute(['DBNames', gzdeflate(MetadataFixture::storageMap())]);

        $config = $pdo->prepare('INSERT INTO Config (filename, binarydata) VALUES (?, ?)');
        $config->execute(['root', gzdeflate('{2,"configuration-id"}')]);
        $config->execute([
            'configuration-id',
            gzdeflate(MetadataFixture::serializedConfigurationRoot([])),
        ]);
        $config->execute([
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            gzdeflate(MetadataFixture::serializedStructure()),
        ]);

        $metadata = iterator_to_array(new MetadataLoader(new SerializedDataParser)->load(
            new PdoConnection($pdo),
        ));

        $this->assertCount(1, $metadata);
        $this->assertSame('_reference259', $metadata[0]->name);
        $this->assertSame('_fld5801', $metadata[0]->properties[0]->name);
    }

    #[Test]
    public function it_loads_active_storage_tables_without_changing_columns_or_reference_identities(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE Config (filename TEXT, binarydata BLOB)');
        $pdo->exec('CREATE TABLE SchemaStorage (SchemaID INTEGER, Status INTEGER, CurrentSchema BLOB)');
        $pdo->exec('CREATE TABLE _document38456 (_idrref BLOB)');
        $pdo->exec('CREATE TABLE _document38456x1 (_idrref BLOB PRIMARY KEY)');
        $pdo->exec('CREATE TABLE _document38456x2 (_idrref BLOB PRIMARY KEY)');
        $pdo->exec('CREATE TABLE _document38456_vt38459 (_document38456_idrref BLOB)');
        $pdo->exec(
            'CREATE TABLE _document38456_vt38459x1 (_document38456_idrref BLOB, _keyfield BLOB, _lineno38460 INTEGER, PRIMARY KEY (_document38456_idrref, _keyfield))',
        );
        $pdo->exec('CREATE TABLE _reference259 (_idrref BLOB PRIMARY KEY)');
        $pdo->exec('CREATE TABLE _reference259x1 (_idrref BLOB PRIMARY KEY)');
        $pdo->prepare('INSERT INTO SchemaStorage VALUES (1, 100, ?)')->execute([MetadataFixture::extensionSchema()]);
        $storageMap = StorageMap::fromArray([
            ...StorageMap::fromSerialized(MetadataFixture::storageMap(), new SerializedDataParser)->toArray(),
            ...StorageMap::fromSerialized(MetadataFixture::documentStorageMap(), new SerializedDataParser)->toArray(),
        ]);
        $config = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');

        foreach ([
            'root' => '{2,"configuration-id"}',
            'configuration-id' => MetadataFixture::serializedConfigurationRoot([]),
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f' => MetadataFixture::serializedStructure(),
            '7fc26ce3-6449-4483-81be-1e19faab744d' => MetadataFixture::serializedDocumentWithTabularSection(),
        ] as $filename => $contents) {
            $config->execute([$filename, gzdeflate($contents)]);
        }

        $connection = new PdoConnection($pdo);
        $loader = new MetadataLoader(new SerializedDataParser);
        $tableMap = TableMap::fromArray($loader->tableMap($connection)->toArray());

        [$reference, $document] = $loader->chunk($connection, $storageMap, TypeMap::empty(), 0, 2, $tableMap);

        $this->assertSame('_reference259', $reference->name);
        $this->assertSame('_document38456x1', $document->name);
        $this->assertSame('e32c4fa2-37ff-4a20-b3b8-c4ecb55a6a37', $document->referenceId);
        $this->assertSame('_document38456_vt38459x1', $document->sections[0]->name);
        $this->assertArrayHasKey('_document38456_idrref', $document->sections[0]->system);
        $this->assertSame(
            ['7fc26ce3-6449-4483-81be-1e19faab744d'],
            $document->sections[0]->system['_document38456_idrref']->targets(),
        );
        $this->assertSame('_fld38461', $document->sections[0]->properties[0]->name);
        $this->assertSame('_fld38462', $document->sections[0]->properties[1]->name);
        $this->assertTrue($connection->hasColumn($document->sections[0]->name, '_document38456_idrref'));
        $this->assertSame(1, $loader->count($storageMap, ['_document38456x1'], $tableMap));
    }

    /** @param array<string, string> $expected */
    #[Test]
    #[DataProvider('registerSchemas')]
    // @mago-expect lint:excessive-parameter-list
    public function it_describes_recorder_columns_from_the_register_table(
        MetadataKind $kind,
        int $code,
        string $label,
        string $columns,
        array $expected,
        int $slot = 0,
    ): void {
        $id = 'register-id';
        $token = $kind->storageToken();
        $table = strtolower(sprintf('_%s%d', $token, $code)) . ($slot === 0 ? '' : 'x' . $slot);
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE Config (filename TEXT PRIMARY KEY, binarydata BLOB NOT NULL)');
        $pdo->exec('CREATE TABLE SchemaStorage (SchemaID INTEGER, Status INTEGER, CurrentSchema BLOB)');

        if ($slot !== 0) {
            $pdo->prepare('INSERT INTO SchemaStorage VALUES (?, 100, ?)')->execute([
                $slot,
                sprintf('{0,{1,{"%s%d","N",%d,"",{0},{0},{0},1,"R",{0},{0},"",0,0}}}', $token, $code, $code),
            ]);
        }

        $pdo->exec(sprintf('CREATE TABLE %s (%s)', $table, $columns));
        $pdo->prepare('INSERT INTO Config (filename, binarydata) VALUES (?, ?)')->execute([
            $id,
            gzdeflate(MetadataFixture::serializedMetadataStructure($kind->identityPath(), $id, $label, $label)),
        ]);

        [$metadata] = new MetadataLoader(new SerializedDataParser)->chunk(
            new PdoConnection($pdo),
            StorageMap::fromArray([$id => [$token => $code]]),
            TypeMap::empty(),
            0,
            1,
        );

        $this->assertSame($table, $metadata->name);
        $this->assertSame($expected, array_map(
            fn (Field $field) => $field->type()->value,
            array_intersect_key($metadata->system, array_flip(['_recordertref', '_recorderrref', '_lineno'])),
        ));
    }

    #[Test]
    public function it_rejects_invalid_table_names_when_checking_columns(): void
    {
        $connection = new PdoConnection(new PDO('sqlite::memory:'));

        $this->expectException(InvalidArgumentException::class);
        $connection->hasColumn('invalid table', '_recordertref');
    }
}
