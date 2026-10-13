<?php

namespace PHPinnacle\Eidos\Services;

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\JsonSchema\Schema;

class SchemaFactory
{
    public function __construct(
        private readonly SchemaIntrospector $introspector,
    ) {}

    /** @param class-string<Model>|Model $model */
    public function create(string|Model $model): Schema
    {
        return $this->introspector->introspect(is_string($model) ? new $model : $model);
    }
}
