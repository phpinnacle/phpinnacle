<?php

namespace PHPinnacle\Eidos\Forms;

use Closure;
use Filament\Forms\Components\Concerns\HasNestedRecursiveValidationRules;
use Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules as HasNestedRecursiveValidationRulesContract;
use Filament\Forms\Components\Field;
use PHPinnacle\Eidos\Concerns\HasJsonSchemaConstraints;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;
use PHPinnacle\Eidos\Validation\ValidPath;
use PHPinnacle\Jason\Schema;

class JsonSchemaPicker extends Field implements HasNestedRecursiveValidationRulesContract
{
    use HasJsonSchemaConstraints;
    use HasNestedRecursiveValidationRules;

    protected string $view = 'phpinnacle-eidos::forms.json-schema-picker';

    /** @var Schema|Closure(): Schema */
    private Schema|Closure $jsonSchema;

    /** @var bool|Closure(): bool */
    private bool|Closure $isMultiple = false;

    /** @var bool|Closure(): bool */
    private bool|Closure $canSelectArrayItemFields = false;

    /** @param bool|Closure(): bool $condition */
    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->evaluate($this->isMultiple);
    }

    /** @param bool|Closure(): bool $condition */
    public function selectArrayItemFields(bool|Closure $condition = true): static
    {
        $this->canSelectArrayItemFields = $condition;

        return $this;
    }

    public function canSelectArrayItemFields(): bool
    {
        return $this->evaluate($this->canSelectArrayItemFields);
    }

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
        $this->default(fn (JsonSchemaPicker $component) => $component->isMultiple() ? [] : null);
        $pathRule = fn (JsonSchemaPicker $component) => new ValidPath(
            $component->getJsonSchema(...),
            $component->canSelectArrayItemFields(),
        );
        $this->rule($pathRule, fn (JsonSchemaPicker $component) => !$component->isMultiple());
        $this->rules(['array', 'list'], fn (JsonSchemaPicker $component) => $component->isMultiple());
        $this->nestedRecursiveRules(
            [$pathRule, 'required', 'distinct:strict'],
            fn (JsonSchemaPicker $component) => $component->isMultiple(),
        );
    }
}
