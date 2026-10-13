<?php

namespace PHPinnacle\Eidos\Infolists;

use Filament\Infolists\Components\Entry;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\Concerns\HasJsonSchemaPresentation;

class JsonSchemaEntry extends Entry
{
    use HasJsonSchemaPresentation;

    protected string $view = 'phpinnacle-eidos::infolists.json-schema-entry';

    public function getJsonSchema(): Type
    {
        return $this->getState();
    }
}
