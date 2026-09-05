<?php

namespace PHPinnacle\Rosetta\Contracts;

use JsonSerializable;
use PHPinnacle\Rosetta\Enums\FieldType;

interface Field extends JsonSerializable
{
    public function type(): FieldType;

    /** @return list<string> */
    public function targets(): array;
}
