<?php

namespace PHPinnacle\Rosetta\Data;

use JsonSerializable;
use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\MetadataKind;

final readonly class MetadataDefinition implements JsonSerializable
{
    /**
     * @param  array<string, Field>  $system
     * @param  list<MetadataProperty>  $properties
     * @param  list<MetadataDefinition>  $sections
     * @param  list<EnumerationValue>  $values
     */
    public function __construct(
        public string $id,
        public string $name,
        public int $code,
        public MetadataKind $kind,
        public string $label,
        public string $title,
        public array $system,
        public array $properties,
        public array $sections = [],
        public array $values = [],
        public ?string $referenceId = null,
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
            ...($this->referenceId === null ? [] : ['reference_id' => $this->referenceId]),
            'system' => $this->system,
            'properties' => $this->properties,
            ...($this->kind === MetadataKind::Document ? ['sections' => $this->sections] : []),
            ...($this->kind === MetadataKind::Enumeration ? ['values' => $this->values] : []),
        ];
    }
}
