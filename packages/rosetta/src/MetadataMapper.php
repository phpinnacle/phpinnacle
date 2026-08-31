<?php

namespace PHPinnacle\Rosetta;

use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Data\EnumerationValue;
use PHPinnacle\Rosetta\Data\MetadataDefinition;
use PHPinnacle\Rosetta\Data\MetadataProperty;
use PHPinnacle\Rosetta\Enums\FieldType;
use PHPinnacle\Rosetta\Enums\MetadataKind;
use PHPinnacle\Rosetta\Enums\PropertyKind;
use PHPinnacle\Rosetta\Fields\NumberField;
use PHPinnacle\Rosetta\Fields\ReferenceField;
use PHPinnacle\Rosetta\Fields\ScalarField;
use PHPinnacle\Rosetta\Fields\StringField;
use PHPinnacle\Rosetta\Fields\UnionField;
use RuntimeException;

final class MetadataMapper
{
    private const VALUE_STORAGE = 'e199ca70-93cf-46ce-a54b-6edc88c3a296';

    private const UNIQUE_IDENTIFIER = 'fc01b5df-97fe-449b-83d4-218a090e681e';

    private readonly SystemMapper $systemMapper;

    private readonly TypeMap $typeMap;

    public function __construct(
        private readonly StorageMap $storageMap,
        ?TypeMap $typeMap = null,
    ) {
        $this->systemMapper = new SystemMapper;
        $this->typeMap = $typeMap ?? TypeMap::empty();
    }

    /** @param array<mixed> $data */
    public function map(array $data, MetadataKind $kind): MetadataDefinition
    {
        if ($kind === MetadataKind::Section) {
            throw new RuntimeException('A tabular section cannot be mapped as a root metadata object');
        }

        $identity = $this->at($data, ...$kind->identityPath());

        if (!is_array($identity)) {
            throw new RuntimeException('Invalid metadata identity');
        }

        $id = $this->at($identity, 2, 3);
        $label = $this->at($identity, 3);
        $code = is_string($id) ? $this->storageMap->code($id, $kind->storageToken()) : null;
        $name = is_string($id) ? $this->storageMap->name($id, $kind->storageToken()) : null;

        if (!is_string($id) || !is_string($label)) {
            throw new RuntimeException('Invalid metadata identifier or logical name');
        }

        if (!is_int($code) || !is_string($name)) {
            throw new RuntimeException(sprintf(
                'DBNames has no %s entry for metadata %s (%s)',
                $kind->storageToken(),
                $id,
                $label,
            ));
        }

        $properties = [];
        $referencePath = $kind->referencePath();
        $referenceId = $referencePath === null
            ? null
            : $this->at($data, ...$referencePath);

        foreach ($kind->propertyRoots() as $root) {
            $properties = [...$properties, ...$this->parseProperties($this->at($data, $root))];
        }

        return new MetadataDefinition(
            id: $id,
            name: strtolower($name),
            code: $code,
            kind: $kind,
            label: $label,
            title: $this->title($this->at($identity, 4), $label),
            system: $this->systemMapper->map($data, $kind, $id),
            properties: $properties,
            sections: $kind === MetadataKind::Document
                ? $this->parseTabularSections($this->at($data, 4), strtolower($name), $id)
                : [],
            values: $kind === MetadataKind::Enumeration
                ? $this->parseEnumerationValues($this->at($data, 7))
                : [],
            referenceId: is_string($referenceId)
                ? $referenceId
                : null,
        );
    }

    /** Read a value using the one-based vectors used by the 1C format. */
    private function at(mixed $value, int ...$path): mixed
    {
        foreach ($path as $position) {
            if (!is_array($value) || !array_key_exists($position - 1, $value)) {
                return null;
            }

            $value = $value[$position - 1];
        }

        return $value;
    }

    /** @return list<mixed> */
    private function collectionItems(mixed $collection, string $subject): array
    {
        if (!is_array($collection)) {
            return [];
        }

        $count = $collection[1] ?? null;
        $items = array_values(array_slice($collection, 2));

        if (!is_int($count) || $count < 0 || count($items) !== $count) {
            throw new RuntimeException(sprintf('%s count does not match declared count', $subject));
        }

        return $items;
    }

    /** @return list<array<mixed>> */
    private function descriptors(mixed $pattern): array
    {
        if (!is_array($pattern) || ($pattern[0] ?? null) !== 'Pattern') {
            return [];
        }

        return array_values(array_filter(
            array_slice($pattern, 1),
            fn ($descriptor) => is_array($descriptor),
        ));
    }

    /** @param list<array<mixed>> $descriptors */
    private function field(array $descriptors): Field
    {
        if ($descriptors === []) {
            return new ScalarField(FieldType::Undefined);
        }

        if (count($descriptors) > 1) {
            return new UnionField(array_map($this->fieldVariant(...), $descriptors));
        }

        return $this->fieldVariant($descriptors[0]);
    }

    /** @param array<mixed> $descriptor */
    private function fieldVariant(array $descriptor): Field
    {
        return match ($descriptor[0] ?? null) {
            'B' => new ScalarField(FieldType::Boolean),
            'N' => new NumberField(
                precision: is_int($descriptor[1] ?? null) ? $descriptor[1] : null,
                scale: is_int($descriptor[2] ?? null) ? $descriptor[2] : null,
                unsigned: ($descriptor[3] ?? 0) === 1,
            ),
            'D' => new ScalarField(match ($descriptor[1] ?? null) {
                'D' => FieldType::Date,
                'T' => FieldType::Time,
                default => FieldType::DateTime,
            }),
            'S' => new StringField(
                length: is_int($descriptor[1] ?? null) ? $descriptor[1] : null,
                fixed: ($descriptor[2] ?? null) === 0,
            ),
            '#' => $this->referencedField($descriptor[1] ?? null),
            default => new ScalarField(FieldType::Unknown),
        };
    }

    /** @return list<EnumerationValue> */
    private function parseEnumerationValues(mixed $collection): array
    {
        $values = [];

        foreach ($this->collectionItems($collection, 'Enumeration value') as $position => $item) {
            $identity = $this->at($item, 1, 2);
            $id = $this->at($identity, 2, 3);
            $label = $this->at($identity, 3);

            if (!is_string($id) || !is_string($label)) {
                throw new RuntimeException(sprintf(
                    'Invalid enumeration value at position %d',
                    $position + 1,
                ));
            }

            $values[] = new EnumerationValue(
                id: $id,
                label: $label,
                title: $this->title($this->at($identity, 4), $label),
            );
        }

        return $values;
    }

    /** @return list<MetadataProperty> */
    private function parseProperties(mixed $collection): array
    {
        $properties = [];

        foreach ($this->collectionItems($collection, 'Metadata property') as $position => $item) {
            if (!is_array($item)) {
                throw new RuntimeException(sprintf(
                    'Invalid metadata property at position %d',
                    $position + 1,
                ));
            }

            $id = $this->at($item, 1, 2, 2, 2, 2, 3);
            $label = $this->at($item, 1, 2, 2, 2, 3);

            if (!is_string($id) || !is_string($label)) {
                throw new RuntimeException(sprintf(
                    'Invalid metadata property identifier or logical name at position %d',
                    $position + 1,
                ));
            }

            $code = $this->storageMap->code($id, 'Fld');
            $name = $this->storageMap->name($id, 'Fld');

            // A metadata property without Fld in DBNames has no physical column.
            if (!is_int($code) || !is_string($name)) {
                continue;
            }

            $title = $this->title($this->at($item, 1, 2, 2, 2, 4), $label);
            $descriptors = $this->descriptors($this->at($item, 1, 2, 2, 3));
            $descriptor = count($descriptors) === 1 ? $descriptors[0] : null;

            if (
                is_array($descriptor)
                && ($descriptor[0] ?? null) === '#'
                && is_string($descriptor[1] ?? null)
                && !in_array($descriptor[1], [self::VALUE_STORAGE, self::UNIQUE_IDENTIFIER], true)
                && $this->typeMap->descriptors($descriptor[1]) === null
            ) {
                $properties[] = new MetadataProperty(
                    id: $id,
                    name: strtolower($name),
                    code: $code,
                    kind: PropertyKind::Reference,
                    label: $label,
                    title: $title,
                    field: new ReferenceField($descriptor[1]),
                );

                continue;
            }

            $properties[] = new MetadataProperty(
                id: $id,
                name: strtolower($name),
                code: $code,
                kind: PropertyKind::Field,
                label: $label,
                title: $title,
                field: $this->field($descriptors),
            );
        }

        return $properties;
    }

    /** @return list<MetadataDefinition> */
    private function parseTabularSections(
        mixed $collection,
        string $documentName,
        string $documentId,
    ): array {
        $sections = [];
        $kind = MetadataKind::Section;

        foreach ($this->collectionItems($collection, 'Metadata tabular section') as $position => $item) {
            if (!is_array($item)) {
                throw new RuntimeException(sprintf(
                    'Invalid metadata tabular section at position %d',
                    $position + 1,
                ));
            }

            $identity = $this->at($item, ...$kind->identityPath());

            if (!is_array($identity)) {
                throw new RuntimeException(sprintf(
                    'Invalid metadata tabular section identity at position %d',
                    $position + 1,
                ));
            }

            $id = $this->at($identity, 2, 3);
            $label = $this->at($identity, 3);
            $code = is_string($id) ? $this->storageMap->code($id, $kind->storageToken()) : null;
            $name = is_string($id) ? $this->storageMap->name($id, $kind->storageToken()) : null;
            $lineName = is_string($id) ? $this->storageMap->name($id, 'LineNo') : null;

            if (!is_string($id) || !is_string($label)) {
                throw new RuntimeException(sprintf(
                    'Invalid metadata tabular section identifier or logical name at position %d',
                    $position + 1,
                ));
            }

            if (!is_int($code) || !is_string($name) || !is_string($lineName)) {
                throw new RuntimeException(sprintf(
                    'DBNames has no %s or LineNo entry for metadata tabular section %s (%s)',
                    $kind->storageToken(),
                    $id,
                    $label,
                ));
            }

            $properties = [];

            foreach ($kind->propertyRoots() as $root) {
                $properties = [...$properties, ...$this->parseProperties($this->at($item, $root))];
            }

            $sections[] = new MetadataDefinition(
                id: $id,
                name: strtolower($documentName . $name),
                code: $code,
                kind: $kind,
                label: $label,
                title: $this->title($this->at($identity, 4), $label),
                system: $this->systemMapper->section(
                    $documentName,
                    $documentId,
                    strtolower($lineName),
                ),
                properties: $properties,
            );
        }

        return $sections;
    }

    private function referencedField(mixed $target): Field
    {
        if ($target === self::VALUE_STORAGE) {
            return new ScalarField(FieldType::Binary);
        }

        if ($target === self::UNIQUE_IDENTIFIER) {
            return new ScalarField(FieldType::Uuid);
        }

        if (is_string($target) && ($descriptors = $this->typeMap->descriptors($target)) !== null) {
            return $this->field($descriptors);
        }

        return new ReferenceField(is_string($target) ? $target : '');
    }

    private function title(mixed $localizations, string $fallback): string
    {
        if (!is_array($localizations) || !is_int($localizations[0] ?? null)) {
            return $fallback;
        }

        $first = null;

        for ($i = 0; $i < $localizations[0]; $i++) {
            $language = $localizations[1 + ($i * 2)] ?? null;
            $value = $localizations[2 + ($i * 2)] ?? null;

            if (!is_string($value)) {
                continue;
            }

            $first ??= $value;

            if ($language === 'ru') {
                return $value;
            }
        }

        return $first ?? $fallback;
    }
}
