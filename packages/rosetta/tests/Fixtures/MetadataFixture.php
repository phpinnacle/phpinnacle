<?php

namespace PHPinnacle\Rosetta\Tests\Fixtures;

use LogicException;

final class MetadataFixture
{
    public static function documentStorageMap(): string
    {
        return <<<'ONEC'
            {38462,
            {5,
            {7fc26ce3-6449-4483-81be-1e19faab744d,"Document",38456},
            {a70b9b3d-ba08-40c6-aca1-f81b588f4316,"VT",38459},
            {a70b9b3d-ba08-40c6-aca1-f81b588f4316,"LineNo",38460},
            {7d4b4c14-85f9-47dd-84dc-67a5d2627b41,"Fld",38461},
            {a91b4ba8-1d4d-43e7-97cf-bb26e13f3aff,"Fld",38462}
            }
            }
            ONEC;
    }

    /** @return array<int, mixed> */
    public static function documentWithTabularSection(): array
    {
        $data = self::metadataStructure(
            [2, 10, 2],
            '7fc26ce3-6449-4483-81be-1e19faab744d',
            'ВводЦен',
            'Ввод цен',
            'e32c4fa2-37ff-4a20-b3b8-c4ecb55a6a37',
        );
        self::set($data, [2, 13], 11);
        self::set($data, [2, 14], 1);
        self::set($data, [6], ['properties', 0]);

        $identity = [];
        self::set($identity, [2, 3], 'a70b9b3d-ba08-40c6-aca1-f81b588f4316');
        self::set($identity, [3], 'ТабличнаяЧасть1');
        self::set($identity, [4], [1, 'ru', 'Табличная часть 1']);

        $section = [];
        self::set($section, [1, 2, 6, 2], $identity);
        self::set(
            $section,
            [3],
            [
                'properties',
                2,
                self::property(
                    id: '7d4b4c14-85f9-47dd-84dc-67a5d2627b41',
                    label: 'Номенклатура',
                    title: 'Номенклатура',
                    descriptor: ['#', '190a7469-3325-4d33-b5ec-28a63ac83b06'],
                ),
                self::property(
                    id: 'a91b4ba8-1d4d-43e7-97cf-bb26e13f3aff',
                    label: 'Цена',
                    title: 'Цена',
                    descriptor: ['N', 10, 2, 0],
                ),
            ],
        );
        self::set($data, [4], ['tabular-sections', 1, $section]);

        return $data;
    }

    /** @return array<int, mixed> */
    public static function documentWithTabularSectionCount(mixed $count): array
    {
        $data = self::documentWithTabularSection();
        self::set($data, [4, 2], $count);

        return $data;
    }

    /** @return array<int, mixed> */
    public static function enumeration(): array
    {
        $data = self::metadataStructure(
            [2, 6, 2],
            '30825806-865e-4da6-82a0-36fda9773e79',
            'ТипыБанковскихСчетов',
            'Типы банковских счетов',
            '42a18a66-1b20-4b82-88fb-d29be8ba5ea0',
            [2, 2],
        );
        self::set(
            $data,
            [7],
            [
                'enum-values',
                2,
                self::enumerationValue(
                    '29dba80c-3a21-44fb-9b76-884de5dc624e',
                    'Расчетный',
                    'Расчетный',
                ),
                self::enumerationValue(
                    '06deb22c-e983-4803-9de7-5c56c6d7c8df',
                    'Транзитный',
                    'Транзитный счёт',
                ),
            ],
        );

        return $data;
    }

    /**
     * @param  non-empty-list<int>  $identityPath
     * @param  non-empty-list<int>  $referencePath
     * @return array<int, mixed>
     */
    public static function metadataStructure(
        array $identityPath,
        string $id,
        string $label,
        string $title,
        ?string $referenceId = null,
        array $referencePath = [2, 4],
    ): array {
        $identity = [];
        self::set($identity, [2, 3], $id);
        self::set($identity, [3], $label);
        self::set($identity, [4], [1, 'ru', $title]);

        $data = [];
        self::set($data, $identityPath, $identity);

        if ($referenceId !== null) {
            self::set($data, $referencePath, $referenceId);
        }

        return $data;
    }

    /** @param list<string> $definedTypes */
    public static function serializedConfigurationRoot(array $definedTypes = ['defined-type-file-id']): string
    {
        $data = [];
        self::set(
            $data,
            [4, 2, 26],
            [
                'c045099e-13b9-4fb6-9d50-fca00202971e',
                count($definedTypes),
                ...$definedTypes,
            ],
        );

        return self::serialize($data);
    }

    public static function serializedDefinedType(): string
    {
        $data = [];
        self::set($data, [2, 2], 'defined-type-id');
        self::set($data, [2, 5], ['Pattern', ['S', 14, 1]]);

        return self::serialize($data);
    }

    public static function serializedDocumentWithTabularSection(): string
    {
        return self::serialize(self::documentWithTabularSection());
    }

    /** @param non-empty-list<int> $identityPath */
    public static function serializedMetadataStructure(
        array $identityPath,
        string $id,
        string $label,
        string $title,
        ?string $referenceId = null,
    ): string {
        return self::serialize(self::metadataStructure(
            $identityPath,
            $id,
            $label,
            $title,
            $referenceId,
        ));
    }

    public static function serializedStructure(): string
    {
        return self::serialize(self::structure());
    }

    public static function storageMap(): string
    {
        return <<<'ONEC'
            {5802,
            {3,
            {fc59acc3-f1f7-4e3f-96da-e580f2c5a88f,"Reference",259},
            {3d295926-ac21-4f62-b76e-64c929f1fc0e,"Fld",5801},
            {722c3d13-753d-4b4f-82b9-d41340f96d79,"Fld",5802}
            }
            }
            ONEC;
    }

    /** @return array<int, mixed> */
    public static function structure(): array
    {
        $data = self::metadataStructure(
            [2, 10, 2],
            'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f',
            'Номенклатура',
            'Номенклатура',
            '190a7469-3325-4d33-b5ec-28a63ac83b06',
        );
        self::set($data, [2, 13], [0, 0]);
        self::set($data, [2, 17], 0);
        self::set($data, [2, 18], 11);
        self::set($data, [2, 20], 100);
        self::set($data, [2, 37], 0);
        self::set($data, [2, 38], 1);
        self::set(
            $data,
            [7],
            [
                'properties',
                2,
                self::property(
                    id: '3d295926-ac21-4f62-b76e-64c929f1fc0e',
                    label: 'ЕдиницаИзмерения',
                    title: 'Единица хранения',
                    descriptor: ['#', '254a3240-79c0-4a3f-9259-5122c9ddd71b'],
                ),
                self::property(
                    id: '722c3d13-753d-4b4f-82b9-d41340f96d79',
                    label: 'Артикул',
                    title: 'Артикул ',
                    descriptor: ['S', 50, 1],
                ),
            ],
        );

        return $data;
    }

    /** @return array<int, mixed> */
    public static function structureWithDefinedType(): array
    {
        $data = self::structure();
        self::set(
            $data,
            [7, 3, 1, 2, 2, 3],
            [
                'Pattern',
                ['#', 'defined-type-id'],
            ],
        );

        return $data;
    }

    /** @return array<int, mixed> */
    public static function structureWithFirstProperty(mixed $property): array
    {
        $data = self::structure();
        self::set($data, [7, 3], $property);

        return $data;
    }

    /** @return array<int, mixed> */
    public static function structureWithPropertyCount(mixed $count): array
    {
        $data = self::structure();
        self::set($data, [7, 2], $count);

        return $data;
    }

    /** @return array<int, mixed> */
    private static function enumerationValue(string $id, string $label, string $title): array
    {
        $identity = [];
        self::set($identity, [2, 3], $id);
        self::set($identity, [3], $label);
        self::set($identity, [4], [1, 'ru', $title]);

        $value = [];
        self::set($value, [1, 2], $identity);
        self::set($value, [2], 0);

        return $value;
    }

    /**
     * @param  list<mixed>  $descriptor
     * @return array<int, mixed>
     */
    private static function property(string $id, string $label, string $title, array $descriptor): array
    {
        $property = [];
        self::set($property, [1, 2, 2, 2, 2, 3], $id);
        self::set($property, [1, 2, 2, 2, 3], $label);
        self::set($property, [1, 2, 2, 2, 4], [1, 'ru', $title]);
        self::set($property, [1, 2, 2, 3], ['Pattern', $descriptor]);

        return $property;
    }

    private static function serialize(mixed $value): string
    {
        if (is_array($value)) {
            return '{' . implode(',', array_map(self::serialize(...), $value)) . '}';
        }

        if ($value === null) {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (!is_string($value)) {
            throw new LogicException('Fixture value must be serializable');
        }

        return sprintf('"%s"', str_replace('"', '""', $value));
    }

    /**
     * @param  array<int, mixed>  $target
     * @param  non-empty-list<int>  $path
     */
    private static function set(array &$target, array $path, mixed $value): void
    {
        $position = array_shift($path);

        if ($position === null) {
            throw new LogicException('Fixture path must not be empty');
        }

        $position--;

        while (count($target) <= $position) {
            $target[] = null;
        }

        if ($path === []) {
            $target[$position] = $value;

            return;
        }

        if (!is_array($target[$position]) || !array_is_list($target[$position])) {
            $target[$position] = [];
        }

        self::set($target[$position], $path, $value);
    }
}
