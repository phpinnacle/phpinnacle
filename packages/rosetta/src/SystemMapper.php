<?php

namespace PHPinnacle\Rosetta;

use LogicException;
use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\FieldType;
use PHPinnacle\Rosetta\Enums\MetadataKind;
use PHPinnacle\Rosetta\Fields\NumberField;
use PHPinnacle\Rosetta\Fields\ReferenceField;
use PHPinnacle\Rosetta\Fields\ScalarField;
use PHPinnacle\Rosetta\Fields\StringField;

final class SystemMapper
{
    /**
     * @param  array<mixed>  $data
     * @return array<string, Field>
     */
    public function map(array $data, MetadataKind $kind, string $id): array
    {
        return match ($kind) {
            MetadataKind::Reference => $this->reference($data, $id),
            MetadataKind::Document => $this->document($data),
            MetadataKind::Enumeration => [
                '_idrref' => $this->identifier(),
                '_enumorder' => $this->integer(10),
            ],
            MetadataKind::AccumulationRegister => $this->accumulation($data),
            MetadataKind::InformationRegister => $this->information($data),
            MetadataKind::Section => throw new LogicException('A tabular section requires section system mapping'),
        };
    }

    /** @return array<string, Field> */
    public function section(string $documentName, string $documentId, string $lineName): array
    {
        return [
            sprintf('%s_idrref', $documentName) => new ReferenceField($documentId),
            '_keyfield' => $this->binary(),
            $lineName => $this->integer(5),
        ];
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, Field>
     */
    private function accumulation(array $data): array
    {
        $system = [
            '_period' => new ScalarField(FieldType::DateTime),
        ];

        if ($this->at($data, 2, 21) === 1) {
            $system['_recordertref'] = $this->binary();
        }

        $system['_recorderrref'] = $this->binary();
        $system['_lineno'] = $this->integer(9);
        $system['_active'] = $this->boolean();

        if ($this->at($data, 2, 16) === 0) {
            $system['_recordkind'] = $this->integer(1);
        }

        return $system;
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

    private function binary(): ScalarField
    {
        return new ScalarField(FieldType::Binary);
    }

    private function boolean(): ScalarField
    {
        return new ScalarField(FieldType::Boolean);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, Field>
     */
    private function document(array $data): array
    {
        $system = [
            '_idrref' => $this->identifier(),
            '_version' => $this->integer(10),
            '_marked' => $this->boolean(),
            '_date_time' => new ScalarField(FieldType::DateTime),
        ];
        $numberLength = $this->at($data, 2, 13);

        if ($this->at($data, 2, 14) === 1) {
            $system['_numberprefix'] = new ScalarField(FieldType::DateTime);
        }

        if (is_int($numberLength) && $numberLength > 0) {
            $system['_number'] = new StringField(length: $numberLength, fixed: true);
        }

        $system['_posted'] = $this->boolean();

        return $system;
    }

    private function identifier(): ScalarField
    {
        return new ScalarField(FieldType::Id);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, Field>
     */
    private function information(array $data): array
    {
        $system = [];
        $periodicity = $this->at($data, 2, 19);

        if (is_int($periodicity) && $periodicity > 0) {
            $system['_period'] = new ScalarField(FieldType::DateTime);
        }

        if ($this->at($data, 2, 20) !== 1) {
            return $system;
        }

        $recorderType = $this->at($data, 2, 18);

        if (is_string($recorderType) && $recorderType !== '00000000-0000-0000-0000-000000000000') {
            $system['_recordertref'] = $this->binary();
        }

        $system['_recorderrref'] = $this->binary();
        $system['_lineno'] = $this->integer(9);
        $system['_active'] = $this->boolean();

        return $system;
    }

    private function integer(int $precision): NumberField
    {
        return new NumberField(precision: $precision, scale: 0, unsigned: true);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, Field>
     */
    private function reference(array $data, string $id): array
    {
        $system = [
            '_idrref' => $this->identifier(),
            '_version' => $this->integer(10),
            '_marked' => $this->boolean(),
            '_predefinedid' => new ScalarField(FieldType::Uuid),
        ];
        $owners = $this->at($data, 2, 13);
        $ownerCount = is_array($owners) && is_int($owners[1] ?? null) ? $owners[1] : 0;

        if ($ownerCount === 1) {
            $target = $this->at($owners, 3, 3, 2);
            $system['_owneridrref'] = is_string($target)
                ? new ReferenceField($target)
                : $this->binary();
        } elseif ($ownerCount > 1) {
            $system['_ownerid_type'] = $this->binary();
            $system['_ownerid_rtref'] = $this->binary();
            $system['_ownerid_rrref'] = $this->binary();
        }

        if ($this->at($data, 2, 38) === 1) {
            $system['_parentidrref'] = new ReferenceField($id);
        }

        if ($this->at($data, 2, 38) === 1 && $this->at($data, 2, 37) === 0) {
            $system['_folder'] = $this->boolean();
        }

        $codeLength = $this->at($data, 2, 18);

        if (is_int($codeLength) && $codeLength > 0) {
            $system['_code'] = new StringField(length: $codeLength, fixed: true);
        }

        $descriptionLength = $this->at($data, 2, 20);

        if (is_int($descriptionLength) && $descriptionLength > 0) {
            $system['_description'] = new StringField(length: $descriptionLength, fixed: false);
        }

        return $system;
    }
}
