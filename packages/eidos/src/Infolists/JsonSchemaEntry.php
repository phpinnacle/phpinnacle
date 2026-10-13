<?php

namespace PHPinnacle\Eidos\Infolists;

use Filament\Infolists\Components\Entry;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\Concerns\HasJsonSchemaConstraints;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;

class JsonSchemaEntry extends Entry
{
    use HasJsonSchemaConstraints;

    protected string $view = 'phpinnacle-eidos::infolists.json-schema-entry';

    public function getJsonSchema(): Type
    {
        return $this->getState();
    }

    /** @return array<string, mixed> */
    public function getExtraViewData(): array
    {
        return [
            ...parent::getExtraViewData(),
            ...SchemaPresentation::render($this->getJsonSchema()->toArray(), $this->areIconsVisible()),
        ];
    }
}
