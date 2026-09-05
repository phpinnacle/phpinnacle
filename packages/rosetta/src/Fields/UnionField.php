<?php

namespace PHPinnacle\Rosetta\Fields;

use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\FieldType;

final readonly class UnionField implements Field
{
    /** @param non-empty-list<Field> $variants */
    public function __construct(
        public array $variants,
    ) {}

    public function type(): FieldType
    {
        return FieldType::Union;
    }

    public function targets(): array
    {
        return array_values(array_unique(array_merge(...array_map(
            fn (Field $variant) => $variant->targets(),
            $this->variants,
        ))));
    }

    /** @return array{type: string, variants: non-empty-list<Field>} */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type()->value,
            'variants' => $this->variants,
        ];
    }
}
