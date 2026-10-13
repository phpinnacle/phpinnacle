<?php

namespace PHPinnacle\Eidos\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\Concerns\HasJsonSchemaConstraints;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;
use PHPinnacle\Eidos\Validation\ValidPath;

class JsonSchemaPicker extends Field
{
    use HasJsonSchemaConstraints;

    protected string $view = 'phpinnacle-eidos::forms.json-schema-picker';

    /** @var Type|Closure(): Type */
    private Type|Closure $jsonSchema;

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

    /** @return array<string, mixed> */
    public function getExtraViewData(): array
    {
        return [
            ...parent::getExtraViewData(),
            ...SchemaPresentation::render($this->getJsonSchema()->toArray(), $this->areIconsVisible()),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable();
        $this->rule(fn (JsonSchemaPicker $component) => new ValidPath($component->getJsonSchema(...)));
    }
}
