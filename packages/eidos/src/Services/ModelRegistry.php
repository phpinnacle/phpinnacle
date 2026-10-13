<?php

namespace PHPinnacle\Eidos\Services;

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\Contracts\ProvidesEloquentModels;

class ModelRegistry
{
    /** @param array<string, ProvidesEloquentModels> $sources */
    public function __construct(
        private readonly array $sources,
    ) {}

    /** @return array<string, ProvidesEloquentModels> */
    public function all(): array
    {
        return $this->sources;
    }

    /** @return list<class-string<Model>> */
    public function modelClasses(): array
    {
        $models = [];

        foreach ($this->sources as $source) {
            array_push($models, ...$source->modelClasses());
        }

        return array_values(array_unique($models));
    }
}
