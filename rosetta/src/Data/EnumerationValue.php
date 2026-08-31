<?php

namespace PHPinnacle\Rosetta\Data;

use JsonSerializable;

final readonly class EnumerationValue implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $label,
        public string $title,
    ) {}

    /** @return array{id: string, label: string, title: string} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'title' => $this->title,
        ];
    }
}
