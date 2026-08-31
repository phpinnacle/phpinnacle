<?php

namespace PHPinnacle\Rosetta\Tests;

require_once __DIR__ . '/Fixtures/MetadataFixture.php';

use PDO;
use PHPinnacle\Rosetta\Connections\PdoConnection;
use PHPinnacle\Rosetta\MetadataLoader;
use PHPinnacle\Rosetta\SerializedDataParser;
use PHPinnacle\Rosetta\Tests\Fixtures\MetadataFixture;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdoConnectionTest extends TestCase
{
    #[Test]
    public function it_loads_metadata_from_a_native_pdo_connection(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE Params (filename TEXT PRIMARY KEY, binarydata BLOB NOT NULL)');
        $pdo->exec('CREATE TABLE Config (filename TEXT PRIMARY KEY, binarydata BLOB NOT NULL)');

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
}
