<?php

namespace PHPinnacle\Rosetta\Fields;

use PHPinnacle\Rosetta\Contracts\Field;
use PHPinnacle\Rosetta\Enums\FieldType;

final readonly class StringField implements Field
{
    public function __construct(
        public ?int $length,
        public bool $fixed,
    ) {}

    /** @return array<string, int|bool|string> */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type()->value,
            ...($this->length !== null ? ['length' => $this->length] : []),
            'fixed' => $this->fixed,
        ];
    }

    public function targets(): array
    {
        return [];
    }

    public function type(): FieldType
    {
        return FieldType::String;
    }
}
