<?php

namespace PHPinnacle\Midas\Models;

readonly class Source
{
    public function __construct(
        public string $id,
        public string $type,
        public ?string $number = null,
        public ?string $description = null,
    ) {}
}
