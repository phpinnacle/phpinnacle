<?php

namespace PHPinnacle\Rosetta\Fields;

use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\FieldType;

final readonly class ReferenceField implements Field
{
    public function __construct(
        public string $target,
    ) {}

    public function type(): FieldType
    {
        return FieldType::Reference;
    }

    public function targets(): array
    {
        return [$this->target];
    }

    /** @return array{type: string, target: string} */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type()->value,
            'target' => $this->target,
        ];
    }
}
