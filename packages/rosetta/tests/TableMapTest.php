<?php

namespace PHPinnacle\Rosetta\Tests;

require_once __DIR__ . '/Fixtures/MetadataFixture.php';

use PHPinnacle\Rosetta\SerializedDataParser;
use PHPinnacle\Rosetta\TableMap;
use PHPinnacle\Rosetta\Tests\Fixtures\MetadataFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TableMapTest extends TestCase
{
    /** @return iterable<string, array{list<object>, string}> */
    public static function invalidStorage(): iterable
    {
        yield 'missing row data' => [[(object) ['schema_id' => 1, 'status' => 100]], 'Invalid SchemaStorage row'];
        yield 'unfinished restructuring' => [
            [(object) ['schema_id' => 1, 'status' => 200, 'data' => '{0,{0}}']],
            'SchemaStorage slot 1 is not ready',
        ];
        yield 'invalid root collection' => [
            [(object) ['schema_id' => 1, 'status' => 100, 'data' => '{0}']],
            'Invalid SchemaStorage table collection',
        ];
        yield 'incorrect count' => [
            [(object) ['schema_id' => 1, 'status' => 100, 'data' => '{0,{1}}']],
            'Invalid SchemaStorage table collection',
        ];
        yield 'invalid table identity' => [
            [(object) ['schema_id' => 1, 'status' => 100, 'data' => '{0,{1,{1,"N",1}}}']],
            'Invalid SchemaStorage table entry',
        ];
        yield 'missing nested collection' => [
            [(object) ['schema_id' => 1, 'status' => 100, 'data' => '{0,{1,{"Document1","N",1}}}']],
            'Invalid SchemaStorage table collection',
        ];
        yield 'conflicting slots' => [
            [
                (object) ['schema_id' => 1, 'status' => 100, 'data' => MetadataFixture::extensionSchema()],
                (object) ['schema_id' => 2, 'status' => 100, 'data' => MetadataFixture::extensionSchema()],
            ],
            'Ambiguous SchemaStorage table _document38456',
        ];
    }

    #[Test]
    public function it_resolves_table_families_from_the_declared_storage_slot(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        fwrite($stream, "\xEF\xBB\xBF" . MetadataFixture::extensionSchema());
        rewind($stream);

        try {
            $map = TableMap::fromSchemaStorage([
                (object) ['schema_id' => 3, 'status' => 100, 'data' => $stream],
            ], new SerializedDataParser);
        } finally {
            fclose($stream);
        }

        $this->assertSame('_document38456x3', $map->name('_Document38456'));
        $this->assertSame('_document38456_vt38459x3', $map->name('_Document38456_VT38459'));
        $this->assertSame('_reference259', $map->name('_Reference259'));
        $this->assertSame($map->toArray(), TableMap::fromArray($map->toArray())->toArray());
    }

    /** @param list<object> $rows */
    #[Test]
    #[DataProvider('invalidStorage')]
    public function it_rejects_invalid_external_storage_descriptions(array $rows, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        TableMap::fromSchemaStorage($rows, new SerializedDataParser);
    }
}
