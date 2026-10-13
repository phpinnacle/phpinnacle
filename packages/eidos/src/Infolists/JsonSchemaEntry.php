<?php

namespace PHPinnacle\Eidos\Infolists;

use Closure;
use Filament\Infolists\Components\Entry;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\Concerns\HasJsonSchemaConstraints;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;

class JsonSchemaEntry extends Entry
{
    use HasJsonSchemaConstraints;

    protected string $view = 'phpinnacle-eidos::infolists.json-schema-entry';

    /** @var Type|Closure(): Type */
    private Type|Closure $jsonSchema;

    /** @var bool|Closure(): bool */
    private bool|Closure $isInline = false;

    /** @param Type|Closure(): Type $schema */
    public function jsonSchema(Type|Closure $schema): static
    {
        $this->jsonSchema = $schema;

        return $this;
    }

    public function getJsonSchema(): Type
    {
        return $this->evaluate($this->jsonSchema);
    }

    /** @param bool|Closure(): bool $condition */
    public function inline(bool|Closure $condition = true): static
    {
        $this->isInline = $condition;

        return $this;
    }

    public function isInline(): bool
    {
        return $this->evaluate($this->isInline);
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
