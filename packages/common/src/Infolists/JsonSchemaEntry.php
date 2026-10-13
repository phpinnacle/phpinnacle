<?php

namespace PHPinnacle\Common\Infolists;

use Filament\Infolists\Components\Entry;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Common\Concerns\HasJsonSchemaConstraints;

class JsonSchemaEntry extends Entry
{
    use HasJsonSchemaConstraints;

    protected string $view = 'phpinnacle-common::infolists.json-schema-entry';

    public function getJsonSchema(): Type
    {
        return $this->getState();
    }
}
