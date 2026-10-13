<?php

namespace PHPinnacle\Eidos\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\Contracts\ProvidesEloquentModels;

#[Singleton]
class ModelRegistry
{
    /** @var array<string, ProvidesEloquentModels> */
    private array $sources = [];

    /** @param list<class-string<ProvidesEloquentModels>> $sourceClasses */
    public function __construct(
        Container $container,
        #[Config('phpinnacle-eidos.model_sources', [])]
        array $sourceClasses,
    ) {
        foreach ($sourceClasses as $sourceClass) {
            $this->sources[$sourceClass] = $container->make($sourceClass);
        }
    }

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
