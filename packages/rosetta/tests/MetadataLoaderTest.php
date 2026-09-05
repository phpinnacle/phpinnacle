<?php

namespace PHPinnacle\Rosetta\Tests;

require_once __DIR__ . '/Fixtures/MetadataFixture.php';

use Generator;
use PHPinnacle\Rosetta\Contracts\Connection;
use PHPinnacle\Rosetta\MetadataLoader;
use PHPinnacle\Rosetta\SerializedDataParser;
use PHPinnacle\Rosetta\StorageMap;
use PHPinnacle\Rosetta\Tests\Fixtures\MetadataFixture;
use PHPinnacle\Rosetta\TypeMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MetadataLoaderTest extends TestCase
{
    #[Test]
    public function it_loads_defined_types_from_the_configuration_root(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('selectOne')
            ->willReturnCallback(fn (string $query, array $bindings) => match ($bindings) {
                ['root'] => (object) ['data' => gzdeflate('{2,"configuration-id"}')],
                ['configuration-id'] => (object) ['data' => gzdeflate(MetadataFixture::serializedConfigurationRoot())],
                default => throw new \LogicException('Unexpected Config query'),
            });
        $connection
            ->expects($this->once())
            ->method('select')
            ->with(
                'SELECT filename, binarydata AS data FROM Config WHERE filename IN (?)',
                ['defined-type-file-id'],
            )
            ->willReturn([
                (object) [
                    'filename' => 'defined-type-file-id',
                    'data' => gzdeflate(MetadataFixture::serializedDefinedType()),
                ],
            ]);

        $typeMap = new MetadataLoader(new SerializedDataParser)->typeMap($connection);

        $this->assertSame(
            [
                ['S', 14, 1],
            ],
            $typeMap->descriptors('defined-type-id'),
        );
    }

    #[Test]
    public function it_loads_large_defined_type_maps_in_bounded_queries(): void
    {
        $fileNames = array_map(
            fn (int $index) => sprintf('defined-type-file-%d', $index),
            range(1, 501),
        );
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('selectOne')
            ->willReturnCallback(fn (string $query, array $bindings) => match ($bindings) {
                ['root'] => (object) ['data' => gzdeflate('{2,"configuration-id"}')],
                ['configuration-id'] => (object) [
                    'data' => gzdeflate(MetadataFixture::serializedConfigurationRoot($fileNames)),
                ],
                default => throw new \LogicException('Unexpected Config query'),
            });
        $connection
            ->expects($this->exactly(2))
            ->method('select')
            ->willReturnCallback(function (string $query, array $bindings) {
                $this->assertLessThanOrEqual(500, count($bindings));
                $this->assertSame(
                    sprintf(
                        'SELECT filename, binarydata AS data FROM Config WHERE filename IN (%s)',
                        implode(', ', array_fill(0, count($bindings), '?')),
                    ),
                    $query,
                );

                $rows = [];

                foreach ($bindings as $fileName) {
                    $this->assertIsString($fileName);
                    $rows[] = (object) [
                        'filename' => $fileName,
                        'data' => gzdeflate(MetadataFixture::serializedDefinedType()),
                    ];
                }

                return $rows;
            });

        $typeMap = new MetadataLoader(new SerializedDataParser)->typeMap($connection);

        $this->assertSame(
            [
                ['S', 14, 1],
            ],
            $typeMap->descriptors('defined-type-id'),
        );
    }

    /** @param list<string> $only */
    #[Test]
    #[DataProvider('onlyFilters')]
    public function it_loads_metadata_directly_from_compressed_database_rows(array $only): void
    {
        $serializedMap = MetadataFixture::storageMap();
        $structure = MetadataFixture::serializedStructure();

        $storageMapStream = fopen('php://memory', 'r+');
        $compressedMap = gzdeflate($serializedMap);
        $this->assertIsResource($storageMapStream);
        $this->assertIsString($compressedMap);
        fwrite($storageMapStream, $compressedMap);
        rewind($storageMapStream);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(3))
            ->method('selectOne')
            ->willReturnCallback(fn (string $query, array $bindings) => match ([$query, $bindings]) {
                ['SELECT binarydata AS data FROM Params WHERE filename = ?', ['DBNames']] => (object) [
                    'data' => $storageMapStream,
                ],
                ['SELECT binarydata AS data FROM Config WHERE filename = ?', ['root']] => (object) [
                    'data' => gzdeflate('{2,"configuration-id"}'),
                ],
                ['SELECT binarydata AS data FROM Config WHERE filename = ?', ['configuration-id']]
                    => (object) ['data' => gzdeflate(MetadataFixture::serializedConfigurationRoot([]))],
                default => throw new \LogicException('Unexpected metadata query'),
            });
        $connection
            ->expects($this->once())
            ->method('select')
            ->with(
                'SELECT filename, binarydata AS data FROM Config WHERE filename IN (?)',
                ['fc59acc3-f1f7-4e3f-96da-e580f2c5a88f'],
            )
            ->willReturn([
                (object) [
                    'filename' => 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
                    'data' => gzdeflate($structure),
                ],
            ]);

        try {
            $progress = [];
            $generator = new MetadataLoader(new SerializedDataParser)
                ->load(
                    $connection,
                    $only,
                    function (int $processed, int $total) use (&$progress) {
                        $progress[] = [$processed, $total];
                    },
                );
            $this->assertInstanceOf(Generator::class, $generator);
            $metadata = iterator_to_array($generator);
        } finally {
            fclose($storageMapStream);
        }

        $this->assertCount(1, $metadata);
        $this->assertSame('fc59acc3-f1f7-4e3f-96da-e580f2c5a88f', $metadata[0]->id);
        $this->assertSame('_reference259', $metadata[0]->name);
        $this->assertSame('_fld5801', $metadata[0]->properties[0]->name);
        $this->assertSame('_fld5802', $metadata[0]->properties[1]->name);
        $this->assertSame([[0, 1], [1, 1]], $progress);
    }

    /** @return iterable<string, array{list<string>}> */
    public static function onlyFilters(): iterable
    {
        yield 'all tables by default' => [[]];
        yield 'limited by physical name' => [['_reference259']];
    }

    #[Test]
    public function it_loads_a_chunk_from_an_existing_storage_map(): void
    {
        $firstId = 'first-metadata-id';
        $secondId = 'second-metadata-id';
        $storageMap = StorageMap::fromArray([
            $firstId => ['Reference' => 1],
            $secondId => ['Reference' => 2],
        ]);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('select')
            ->with(
                'SELECT filename, binarydata AS data FROM Config WHERE filename IN (?, ?)',
                [$firstId, $secondId],
            )
            ->willReturn([
                (object) [
                    'filename' => $secondId,
                    'data' => gzdeflate(MetadataFixture::serializedMetadataStructure(
                        [2, 10, 2],
                        $secondId,
                        'ВторойОбъект',
                        'Второй объект',
                        'second-reference-id',
                    )),
                ],
                (object) [
                    'filename' => $firstId,
                    'data' => gzdeflate(MetadataFixture::serializedMetadataStructure(
                        [2, 10, 2],
                        $firstId,
                        'ПервыйОбъект',
                        'Первый объект',
                        'first-reference-id',
                    )),
                ],
            ]);

        $metadata = new MetadataLoader(new SerializedDataParser)
            ->chunk($connection, $storageMap, TypeMap::empty(), 0, 20);

        $this->assertCount(2, $metadata);
        $this->assertSame([$firstId, $secondId], array_column($metadata, 'id'));
        $this->assertSame(['_reference1', '_reference2'], array_column($metadata, 'name'));
    }
}
