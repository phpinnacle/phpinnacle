<?php

namespace PHPinnacle\Rosetta\Fields;

use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\FieldType;

final readonly class ScalarField implements Field
{
    /**
     * @param  FieldType::Boolean|FieldType::Date|FieldType::Time|FieldType::DateTime|FieldType::Binary|FieldType::Id|FieldType::Uuid|FieldType::Unknown|FieldType::Undefined  $type
     */
    public function __construct(
        private FieldType $type,
    ) {}

    public function type(): FieldType
    {
        return $this->type;
    }

    public function targets(): array
    {
        return [];
    }

    /** @return array{type: string} */
    public function jsonSerialize(): array
    {
        return ['type' => $this->type->value];
    }
}
