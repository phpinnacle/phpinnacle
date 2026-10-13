<?php

namespace PHPinnacle\Eidos\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Cache;

/** @phpstan-import-type Attribute from ModelSchema */
class EloquentIntrospector
{
    /** @param class-string<Model> $modelClass */
    public function schema(string $modelClass): Type
    {
        $model = new $modelClass;
        $key = $this->cacheKey($model);

        /** @var array<string, mixed> $schema */
        $schema = Cache::memo()->rememberForever($key, fn () => new ModelSchema($model)->schema()->toArray());

        return JsonSchema::fromArray($schema);
    }

    /** @param class-string<Model> $modelClass */
    public function warm(string $modelClass): void
    {
        $model = new $modelClass;
        $key = $this->cacheKey($model);

        Cache::memo()->forget($key);
        Cache::memo()->forget($key . ':attributes');

        $this->schema($modelClass);
    }

    /**
     * @param class-string<Model> $modelClass
     * @return list<Attribute>
     */
    public function attributes(string $modelClass): array
    {
        $model = new $modelClass;

        return Cache::memo()->rememberForever(
            $this->cacheKey($model) . ':attributes',
            fn () => new ModelSchema($model)->attributes(),
        );
    }

    private function cacheKey(Model $model): string
    {
        return 'phpinnacle-eidos:eloquent-schema:'
        . hash('sha256', implode('|', [
            $model::class,
            $model->getConnectionName() ?? '',
            $model->getConnection()->getDatabaseName(),
            $model->getTable(),
        ]));
    }
}
