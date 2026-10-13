<?php

namespace PHPinnacle\Eidos\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\JsonSchema\Schema;

class SchemaFactory
{
    public function __construct(
        private readonly SchemaIntrospector $introspector,
        private readonly ModelDiscovery $discovery,
        private readonly Repository $cache,
    ) {}

    /** @param class-string<Model>|Model $model */
    public function create(string|Model $model): Schema
    {
        $definition = $this->cache->rememberForever(
            $this->cacheKey($model),
            fn () => $this->introspector->introspect(is_string($model) ? new $model : $model)->toArray(),
        );

        return Schema::fromArray($definition);
    }

    /**
     * @param list<string> $include Model class names and discovery wildcard patterns.
     * @param list<string> $except Discovery wildcard patterns to exclude.
     */
    public function warmup(array $include, array $except): int
    {
        $models = $this->discovery->modelClasses($include, $except);

        foreach ($models as $modelClass) {
            $schema = $this->introspector->introspect(new $modelClass);
            $this->cache->forever($this->cacheKey($modelClass), $schema->toArray());
        }

        return count($models);
    }

    /** @param class-string<Model>|Model $model */
    private function cacheKey(string|Model $model): string
    {
        return 'phpinnacle-eidos:model-schema:' . (is_string($model) ? $model : $model::class);
    }
}
