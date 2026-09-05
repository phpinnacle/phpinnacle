<?php

namespace PHPinnacle\Rosetta\Tests;

require_once __DIR__ . '/Fixtures/MetadataFixture.php';

use PHPinnacle\Rosetta\Data\MetadataDefinition;
use PHPinnacle\Rosetta\Enums\FieldType;
use PHPinnacle\Rosetta\Enums\MetadataKind;
use PHPinnacle\Rosetta\Fields\ScalarField;
use PHPinnacle\Rosetta\MetadataMapper;
use PHPinnacle\Rosetta\SerializedDataParser;
use PHPinnacle\Rosetta\StorageMap;
use PHPinnacle\Rosetta\Tests\Fixtures\MetadataFixture;
use PHPinnacle\Rosetta\TypeMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MetadataMapperTest extends TestCase
{
    /** @return iterable<string, array{mixed, string}> */
    public static function invalidProperties(): iterable
    {
        yield 'non-array property' => [
            'invalid',
            'Invalid metadata property at position 1',
        ];
        yield 'missing identity' => [
            [],
            'Invalid metadata property identifier or logical name at position 1',
        ];
    }

    /** @return iterable<string, array{MetadataKind, string, non-empty-list<int>}> */
    public static function metadataKinds(): iterable
    {
        yield 'reference' => [MetadataKind::Reference, 'Reference', [2, 10, 2]];
        yield 'document' => [MetadataKind::Document, 'Document', [2, 10, 2]];
        yield 'enumeration' => [MetadataKind::Enumeration, 'Enum', [2, 6, 2]];
        yield 'accumulation register' => [
            MetadataKind::AccumulationRegister,
            'AccumRg',
            [2, 14, 2],
        ];
        yield 'information register' => [
            MetadataKind::InformationRegister,
            'InfoRg',
            [2, 16, 2],
        ];
    }

    #[Test]
    public function it_ignores_metadata_properties_without_a_physical_database_field(): void
    {
        $parser = new SerializedDataParser;
        $storageMap = StorageMap::fromSerialized(
            '{1,{1,{fc59acc3-f1f7-4e3f-96da-e580f2c5a88f,"Reference",259}}}',
            $parser,
        );

        $metadata = new MetadataMapper($storageMap)->map(
            MetadataFixture::structure(),
            MetadataKind::Reference,
        );

        $this->assertSame([], $metadata->properties);
    }

    #[Test]
    public function it_maps_document_tabular_sections(): void
    {
        $parser = new SerializedDataParser;
        $metadata = new MetadataMapper(
            StorageMap::fromSerialized(MetadataFixture::documentStorageMap(), $parser),
        )->map(MetadataFixture::documentWithTabularSection(), MetadataKind::Document);
        $result = json_decode(json_encode($metadata, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($result);
        $this->assertInstanceOf(MetadataDefinition::class, $metadata->sections[0]);
        $this->assertSame(MetadataKind::Section, $metadata->sections[0]->kind);
        $this->assertSame(
            [
                [
                    'id' => 'a70b9b3d-ba08-40c6-aca1-f81b588f4316',
                    'name' => '_document38456_vt38459',
                    'code' => 38459,
                    'kind' => 'section',
                    'label' => 'ТабличнаяЧасть1',
                    'title' => 'Табличная часть 1',
                    'system' => [
                        '_document38456_idrref' => [
                            'type' => 'reference',
                            'target' => '7fc26ce3-6449-4483-81be-1e19faab744d',
                        ],
                        '_keyfield' => ['type' => 'binary'],
                        '_lineno38460' => [
                            'type' => 'number',
                            'precision' => 5,
                            'scale' => 0,
                            'unsigned' => true,
                        ],
                    ],
                    'properties' => [
                        [
                            'id' => '7d4b4c14-85f9-47dd-84dc-67a5d2627b41',
                            'name' => '_fld38461',
                            'code' => 38461,
                            'kind' => 'reference',
                            'label' => 'Номенклатура',
                            'title' => 'Номенклатура',
                            'field' => [
                                'type' => 'reference',
                                'target' => '190a7469-3325-4d33-b5ec-28a63ac83b06',
                            ],
                        ],
                        [
                            'id' => 'a91b4ba8-1d4d-43e7-97cf-bb26e13f3aff',
                            'name' => '_fld38462',
                            'code' => 38462,
                            'kind' => 'field',
                            'label' => 'Цена',
                            'title' => 'Цена',
                            'field' => [
                                'type' => 'number',
                                'precision' => 10,
                                'scale' => 2,
                                'unsigned' => false,
                            ],
                        ],
                    ],
                ],
            ],
            $result['sections'] ?? null,
        );
    }

    /** @param non-empty-list<int> $identityPath */
    #[Test]
    #[DataProvider('metadataKinds')]
    public function it_maps_each_supported_metadata_kind(
        MetadataKind $kind,
        #[\SensitiveParameter]
        string $token,
        array $identityPath,
    ): void {
        $id = 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f';
        $parser = new SerializedDataParser;
        $storageMap = StorageMap::fromSerialized(
            sprintf('{1,{1,{%s,"%s",259}}}', $id, $token),
            $parser,
        );
        $structure = MetadataFixture::metadataStructure(
            $identityPath,
            $id,
            'Объект',
            'Объект метаданных',
            'reference-type-id',
            referencePath: $kind === MetadataKind::Enumeration ? [2, 2] : [2, 4],
        );

        $metadata = new MetadataMapper($storageMap)->map($structure, $kind);

        $this->assertSame($kind, $metadata->kind);
        $this->assertSame(sprintf('_%s259', strtolower($token)), $metadata->name);
        $this->assertSame('Объект метаданных', $metadata->title);
        $this->assertSame(
            $kind->referencePath() === null ? null : 'reference-type-id',
            $metadata->referenceId,
        );

        if (in_array(
            $kind,
            [
                MetadataKind::Reference,
                MetadataKind::Document,
                MetadataKind::Enumeration,
            ],
            true,
        )) {
            $this->assertInstanceOf(ScalarField::class, $metadata->system['_idrref']);
            $this->assertSame(FieldType::Id, $metadata->system['_idrref']->type());
        }
    }

    #[Test]
    public function it_maps_enumeration_values(): void
    {
        $parser = new SerializedDataParser;
        $metadata = new MetadataMapper(StorageMap::fromSerialized(
            '{1,{1,{30825806-865e-4da6-82a0-36fda9773e79,"Enum",259}}}',
            $parser,
        ))->map(MetadataFixture::enumeration(), MetadataKind::Enumeration);
        $result = json_decode(json_encode($metadata, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($result);
        $this->assertSame(
            [
                [
                    'id' => '29dba80c-3a21-44fb-9b76-884de5dc624e',
                    'label' => 'Расчетный',
                    'title' => 'Расчетный',
                ],
                [
                    'id' => '06deb22c-e983-4803-9de7-5c56c6d7c8df',
                    'label' => 'Транзитный',
                    'title' => 'Транзитный счёт',
                ],
            ],
            $result['values'] ?? null,
        );
    }

    #[Test]
    public function it_maps_reference_259_to_value_objects(): void
    {
        $parser = new SerializedDataParser;
        $metadata = new MetadataMapper(
            StorageMap::fromSerialized(MetadataFixture::storageMap(), $parser),
        )->map(MetadataFixture::structure(), MetadataKind::Reference);
        $result = json_decode(json_encode($metadata, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($result);
        $properties = $result['properties'] ?? null;
        $this->assertIsArray($properties);

        $this->assertSame(
            [
                'id' => 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
                'name' => '_reference259',
                'code' => 259,
                'kind' => 'reference',
                'label' => 'Номенклатура',
                'title' => 'Номенклатура',
                'reference_id' => '190a7469-3325-4d33-b5ec-28a63ac83b06',
                'system' => [
                    '_idrref' => [
                        'type' => 'id',
                    ],
                    '_version' => [
                        'type' => 'number',
                        'precision' => 10,
                        'scale' => 0,
                        'unsigned' => true,
                    ],
                    '_marked' => ['type' => 'boolean'],
                    '_predefinedid' => ['type' => 'uuid'],
                    '_parentidrref' => [
                        'type' => 'reference',
                        'target' => 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
                    ],
                    '_folder' => ['type' => 'boolean'],
                    '_code' => ['type' => 'string', 'length' => 11, 'fixed' => true],
                    '_description' => ['type' => 'string', 'length' => 100, 'fixed' => false],
                ],
            ],
            array_diff_key($result, ['properties' => true]),
        );
        $this->assertSame(
            [
                'id' => '3d295926-ac21-4f62-b76e-64c929f1fc0e',
                'name' => '_fld5801',
                'code' => 5801,
                'kind' => 'reference',
                'label' => 'ЕдиницаИзмерения',
                'title' => 'Единица хранения',
                'field' => [
                    'type' => 'reference',
                    'target' => '254a3240-79c0-4a3f-9259-5122c9ddd71b',
                ],
            ],
            $properties[0],
        );
        $this->assertSame(
            [
                'id' => '722c3d13-753d-4b4f-82b9-d41340f96d79',
                'name' => '_fld5802',
                'code' => 5802,
                'kind' => 'field',
                'label' => 'Артикул',
                'title' => 'Артикул ',
                'field' => ['type' => 'string', 'length' => 50, 'fixed' => false],
            ],
            $properties[1],
        );
    }

    #[Test]
    public function it_rejects_a_metadata_property_count_mismatch(): void
    {
        $parser = new SerializedDataParser;
        $storageMap = StorageMap::fromSerialized(MetadataFixture::storageMap(), $parser);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Metadata property count does not match declared count');

        new MetadataMapper($storageMap)->map(
            MetadataFixture::structureWithPropertyCount(3),
            MetadataKind::Reference,
        );
    }

    #[Test]
    public function it_rejects_a_tabular_section_count_mismatch(): void
    {
        $parser = new SerializedDataParser;
        $storageMap = StorageMap::fromSerialized(MetadataFixture::documentStorageMap(), $parser);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Metadata tabular section count does not match declared count');

        new MetadataMapper($storageMap)->map(
            MetadataFixture::documentWithTabularSectionCount(2),
            MetadataKind::Document,
        );
    }

    #[Test]
    #[DataProvider('invalidProperties')]
    public function it_rejects_invalid_metadata_property_structures(mixed $property, string $message): void
    {
        $parser = new SerializedDataParser;
        $storageMap = StorageMap::fromSerialized(MetadataFixture::storageMap(), $parser);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        new MetadataMapper($storageMap)->map(
            MetadataFixture::structureWithFirstProperty($property),
            MetadataKind::Reference,
        );
    }

    #[Test]
    public function it_resolves_defined_types_to_their_physical_field_type(): void
    {
        $parser = new SerializedDataParser;
        $metadata = new MetadataMapper(
            StorageMap::fromSerialized(MetadataFixture::storageMap(), $parser),
            TypeMap::fromArray([
                'defined-type-id' => [['S', 14, 1]],
            ]),
        )->map(MetadataFixture::structureWithDefinedType(), MetadataKind::Reference);

        $this->assertSame('field', $metadata->properties[0]->kind->value);
        $this->assertSame(
            [
                'type' => 'string',
                'length' => 14,
                'fixed' => false,
            ],
            $metadata->properties[0]->field?->jsonSerialize(),
        );
    }
}
