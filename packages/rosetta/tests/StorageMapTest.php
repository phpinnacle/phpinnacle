<?php

namespace PHPinnacle\Rosetta\Tests;

use PHPinnacle\Rosetta\SerializedDataParser;
use PHPinnacle\Rosetta\StorageMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageMapTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function invalidStructures(): iterable
    {
        yield 'invalid declared count' => [
            '{1,{2,{fc59acc3-f1f7-4e3f-96da-e580f2c5a88f,"Reference",259}}}',
            'DBNames entry count does not match declared count',
        ];
        yield 'invalid entry shape' => [
            '{1,{1,{fc59acc3-f1f7-4e3f-96da-e580f2c5a88f,"Reference"}}}',
            'Invalid DBNames entry at position 1',
        ];
        yield 'invalid entry values' => [
            '{1,{1,{42,"Reference",259}}}',
            'Invalid DBNames entry at position 1',
        ];
    }

    #[Test]
    #[DataProvider('invalidStructures')]
    public function it_rejects_invalid_database_name_structures(string $serialized, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        StorageMap::fromSerialized($serialized, new SerializedDataParser);
    }

    #[Test]
    public function it_resolves_all_database_names_registered_for_a_uuid(): void
    {
        $serialized = <<<'ONEC'
            {10,
            {3,
            {fc59acc3-f1f7-4e3f-96da-e580f2c5a88f,"Reference",259},
            {fc59acc3-f1f7-4e3f-96da-e580f2c5a88f,"ReferenceChngR",5920},
            {0e7e0970-627b-458f-8d96-ef13c523d2b8,"Fld",5815}
            }
            }
            ONEC;

        $storageMap = StorageMap::fromSerialized($serialized, new SerializedDataParser);

        $this->assertSame('_Reference259', $storageMap->name(
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            'Reference',
        ));
        $this->assertSame(
            [
                'Reference' => 259,
                'ReferenceChngR' => 5920,
            ],
            $storageMap->entries('fc59acc3-f1f7-4e3f-96da-e580f2c5a88f'),
        );
        $this->assertSame('_Fld5815', $storageMap->name(
            '0e7e0970-627b-458f-8d96-ef13c523d2b8',
            'Fld',
        ));
        $this->assertSame(
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            $storageMap->uuid('Reference', 259),
        );
        $this->assertSame(
            [
                259 => 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            ],
            $storageMap->uuids('Reference'),
        );
    }

    #[Test]
    public function it_round_trips_through_a_database_ready_array(): void
    {
        $entries = [
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f' => [
                'Reference' => 259,
                'ReferenceChngR' => 5920,
            ],
        ];

        $storageMap = StorageMap::fromArray($entries);

        $this->assertSame($entries, $storageMap->toArray());
        $this->assertSame('_Reference259', $storageMap->name(
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            'Reference',
        ));
    }
}
