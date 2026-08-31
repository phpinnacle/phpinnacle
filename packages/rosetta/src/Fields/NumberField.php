<?php

namespace PHPinnacle\Rosetta\Fields;

use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\FieldType;

final readonly class NumberField implements Field
{
    public function __construct(
        public ?int $precision,
        public ?int $scale,
        public bool $unsigned,
    ) {}

    /** @return array<string, int|bool|string> */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type()->value,
            ...($this->precision !== null ? ['precision' => $this->precision] : []),
            ...($this->scale !== null ? ['scale' => $this->scale] : []),
            'unsigned' => $this->unsigned,
        ];
    }

    public function targets(): array
    {
        return [];
    }

    public function type(): FieldType
    {
        return FieldType::Number;
    }
}
