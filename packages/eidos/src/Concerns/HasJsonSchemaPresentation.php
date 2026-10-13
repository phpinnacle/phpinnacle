<?php

namespace PHPinnacle\Eidos\Concerns;

use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\JsonSchema\Icons;
use PHPinnacle\Eidos\JsonSchema\PropertyTitles;

/** @internal */
trait HasJsonSchemaPresentation
{
    use HasJsonSchemaConstraints;

    abstract public function getJsonSchema(): Type;

    /** @return array<string, mixed> */
    public function getExtraViewData(): array
    {
        $data = parent::getExtraViewData();
        $definition = $this->getJsonSchema()->toArray();

        return [
            ...$data,
            'definition' => $definition,
            'propertyTitles' => new PropertyTitles()->render($definition),
            'icons' => $this->areIconsVisible() ? new Icons()->render($definition) : [],
        ];
    }
}
