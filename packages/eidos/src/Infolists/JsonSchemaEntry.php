<?php

namespace PHPinnacle\Eidos\Infolists;

use Closure;
use Filament\Infolists\Components\Entry;
use PHPinnacle\Eidos\Concerns\HasJsonSchemaConstraints;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;
use PHPinnacle\Jason\Schema;

class JsonSchemaEntry extends Entry
{
    use HasJsonSchemaConstraints;

    protected string $view = 'phpinnacle-eidos::infolists.json-schema-entry';

    /** @var Schema|Closure(): Schema */
    private Schema|Closure $jsonSchema;

    /** @var bool|Closure(): bool */
    private bool|Closure $isInline = false;

    /** @param Schema|Closure(): Schema $schema */
    public function jsonSchema(Schema|Closure $schema): static
    {
        $this->jsonSchema = $schema;

        return $this;
    }

    public function getJsonSchema(): Schema
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
