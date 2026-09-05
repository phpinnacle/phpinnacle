<?php

namespace PHPinnacle\Rosetta\Tests;

use PHPinnacle\Rosetta\Data\EnumerationValue;
use PHPinnacle\Rosetta\Data\MetadataDefinition;
use PHPinnacle\Rosetta\Data\MetadataProperty;
use PHPinnacle\Rosetta\Enums\MetadataKind;
use PHPinnacle\Rosetta\Enums\PropertyKind;
use PHPinnacle\Rosetta\Fields\ReferenceField;
use PHPinnacle\Rosetta\Fields\StringField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MetadataDefinitionTest extends TestCase
{
    /** @return iterable<string, array{MetadataKind, string, list<int>, list<int>}> */
    public static function metadataKindStorage(): iterable
    {
        yield 'reference' => [MetadataKind::Reference, 'Reference', [2, 10, 2], [7]];
        yield 'document' => [MetadataKind::Document, 'Document', [2, 10, 2], [6]];
        yield 'enumeration' => [MetadataKind::Enumeration, 'Enum', [2, 6, 2], []];
        yield 'accumulation register' => [
            MetadataKind::AccumulationRegister,
            'AccumRg',
            [2, 14, 2],
            [6, 7, 8],
        ];
        yield 'information register' => [
            MetadataKind::InformationRegister,
            'InfoRg',
            [2, 16, 2],
            [4, 5, 8],
        ];
        yield 'tabular section' => [
            MetadataKind::Section,
            'VT',
            [1, 2, 6, 2],
            [3],
        ];
    }

    #[Test]
    public function documents_serialize_an_empty_tabular_section_collection(): void
    {
        $metadata = new MetadataDefinition(
            id: 'document-id',
            name: '_document1',
            code: 1,
            kind: MetadataKind::Document,
            label: 'Документ',
            title: 'Документ',
            system: [],
            properties: [],
        );
        $result = json_decode(json_encode($metadata, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($result);
        $this->assertSame([], $result['sections'] ?? null);
    }

    #[Test]
    public function enumerations_serialize_their_values(): void
    {
        $metadata = new MetadataDefinition(
            id: 'enumeration-id',
            name: '_enum1',
            code: 1,
            kind: MetadataKind::Enumeration,
            label: 'ТипыСчетов',
            title: 'Типы счетов',
            system: [],
            properties: [],
            values: [
                new EnumerationValue(
                    id: 'value-id',
                    label: 'Расчетный',
                    title: 'Расчетный',
                ),
            ],
        );
        $result = json_decode(json_encode($metadata, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($result);
        $this->assertSame(
            [
                [
                    'id' => 'value-id',
                    'label' => 'Расчетный',
                    'title' => 'Расчетный',
                ],
            ],
            $result['values'] ?? null,
        );
    }

    #[Test]
    public function it_accepts_non_uuid_identifiers(): void
    {
        $metadata = new MetadataDefinition(
            id: 'reference:nomenclature',
            name: '_reference259',
            code: 259,
            kind: MetadataKind::Reference,
            label: 'Номенклатура',
            title: 'Номенклатура',
            system: [],
            properties: [
                new MetadataProperty(
                    id: 'field:article',
                    name: '_fld5802',
                    code: 5802,
                    kind: PropertyKind::Field,
                    label: 'Артикул',
                    title: 'Артикул',
                    field: new StringField(length: 50, fixed: false),
                ),
            ],
        );

        $this->assertSame('reference:nomenclature', $metadata->id);
        $this->assertSame('field:article', $metadata->properties[0]->id);
    }

    #[Test]
    public function it_serializes_the_metadata_contract(): void
    {
        $metadata = new MetadataDefinition(
            id: 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            name: '_reference259',
            code: 259,
            kind: MetadataKind::Reference,
            label: 'Номенклатура',
            title: 'Номенклатура',
            referenceId: '190a7469-3325-4d33-b5ec-28a63ac83b06',
            system: [
                '_description' => new StringField(length: 100, fixed: false),
            ],
            properties: [
                new MetadataProperty(
                    id: '3d295926-ac21-4f62-b76e-64c929f1fc0e',
                    name: '_fld5801',
                    code: 5801,
                    kind: PropertyKind::Reference,
                    label: 'ЕдиницаИзмерения',
                    title: 'Единица хранения',
                    field: new ReferenceField('254a3240-79c0-4a3f-9259-5122c9ddd71b'),
                ),
                new MetadataProperty(
                    id: '722c3d13-753d-4b4f-82b9-d41340f96d79',
                    name: '_fld5802',
                    code: 5802,
                    kind: PropertyKind::Field,
                    label: 'Артикул',
                    title: 'Артикул ',
                    field: new StringField(length: 50, fixed: false),
                ),
            ],
        );

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
                    '_description' => ['type' => 'string', 'length' => 100, 'fixed' => false],
                ],
                'properties' => [
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
                    [
                        'id' => '722c3d13-753d-4b4f-82b9-d41340f96d79',
                        'name' => '_fld5802',
                        'code' => 5802,
                        'kind' => 'field',
                        'label' => 'Артикул',
                        'title' => 'Артикул ',
                        'field' => ['type' => 'string', 'length' => 50, 'fixed' => false],
                    ],
                ],
            ],
            json_decode(json_encode($metadata, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param  list<int>  $identityPath
     * @param  list<int>  $propertyRoots
     */
    #[Test]
    #[DataProvider('metadataKindStorage')]
    public function metadata_kinds_own_their_storage_layout(
        MetadataKind $kind,
        #[\SensitiveParameter]
        string $token,
        array $identityPath,
        array $propertyRoots,
    ): void {
        $this->assertSame($token, $kind->storageToken());
        $this->assertSame($identityPath, $kind->identityPath());
        $this->assertSame($propertyRoots, $kind->propertyRoots());
    }

    #[Test]
    public function tabular_sections_are_not_root_metadata_kinds(): void
    {
        $this->assertSame(
            [
                MetadataKind::Reference,
                MetadataKind::Document,
                MetadataKind::Enumeration,
                MetadataKind::AccumulationRegister,
                MetadataKind::InformationRegister,
            ],
            MetadataKind::roots(),
        );
    }
}
