<?php

namespace PHPinnacle\Rosetta\Contracts;

use JsonSerializable;
use PHPinnacle\Rosetta\Enums\FieldType;

interface Field extends JsonSerializable
{
    /** @return list<string> */
    public function targets(): array;

    public function type(): FieldType;
}
