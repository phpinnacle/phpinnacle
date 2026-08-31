<?php

namespace PHPinnacle\Rosetta\Data;

use JsonSerializable;
use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\PropertyKind;

final readonly class MetadataProperty implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public int $code,
        public PropertyKind $kind,
        public string $label,
        public string $title,
        public Field $field,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'kind' => $this->kind->value,
            'label' => $this->label,
            'title' => $this->title,
            'field' => $this->field,
        ];
    }
}
