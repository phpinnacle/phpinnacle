<?php

namespace PHPinnacle\Eidos\Services;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use PHPinnacle\Eidos\JsonSchema\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @phpstan-type Column array{name: string, type_name: string, nullable: bool, generation: array{type: string|null, expression: string|null}|null}
 */
class SchemaIntrospector
{
    /** @var array<string, Closure(Model, string, list<string>): Type> */
    private array $castHandlers = [];

    /** @param Closure(Model, string, list<string>): Type $handler */
    public function registerCast(string $name, Closure $handler): static
    {
        $this->castHandlers[$name] = $handler;

        return $this;
    }

    public function introspect(Model $model): Schema
    {
        return Schema::fromArray([
            'type' => 'object',
            'properties' => array_replace($this->columnSchemas($model), $this->relationSchemas($model)),
            'title' => $model::class,
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function relationSchemas(Model $model): array
    {
        $properties = [];

        foreach (new ReflectionClass($model)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();

            if (
                $method->isStatic()
                || $method->getNumberOfRequiredParameters() !== 0
                || !$returnType instanceof ReflectionNamedType
                || !is_a($returnType->getName(), Relation::class, true)
                || is_a($returnType->getName(), MorphTo::class, true)
            ) {
                continue;
            }

            $relation = $this->relation($model, $method);
            $relatedProperties = $this->columnSchemas($relation->getRelated());
            $name = $model::$snakeAttributes ? Str::snake($method->getName()) : $method->getName();

            $properties[$name] =
                $relation instanceof HasMany
                || $relation instanceof BelongsToMany
                || $relation instanceof HasManyThrough
                    ? ['type' => 'array', 'items' => ['type' => 'object', 'properties' => $relatedProperties]]
                    : ['type' => ['object', 'null'], 'properties' => $relatedProperties];
        }

        return $properties;
    }

    /** @return Relation<Model, Model, mixed> */
    private function relation(Model $model, ReflectionMethod $method): Relation
    {
        return Relation::noConstraints(fn () => $method->invoke($model));
    }

    /** @return array<string, array<string, mixed>> */
    private function columnSchemas(Model $model): array
    {
        /** @var list<Column> $columns */
        $columns = $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());
        $properties = [];
        $casts = $model->getCasts();
        $dates = $model->getDates();

        foreach ($columns as $column) {
            $name = $column['name'];
            $cast = $casts[$name] ?? null;
            $type = in_array($name, $dates, true) && $cast === null
                ? JsonSchema::string()->format('date-time')
                : $this->columnType($column['type_name'], $cast, $model, $name);

            $definition = ($column['nullable'] ? $type->nullable() : $type)->toArray();

            if ($column['generation'] !== null) {
                $definition['readOnly'] = true;
            }

            $properties[$name] = $definition;
        }

        return $properties;
    }

    private function columnType(string $databaseType, ?string $cast, Model $model, string $attribute): Type
    {
        return (
            ($cast === null ? null : $this->castType($cast, $model, $attribute)) ?? $this->databaseType($databaseType)
        );
    }

    private function castType(string $cast, Model $model, string $attribute): ?Type
    {
        $segments = explode(':', $cast, 2);
        $castName = $segments[0];
        $handler = $this->castHandlers[$castName] ?? null;

        if ($handler !== null) {
            $arguments = count($segments) === 2 ? explode(',', $segments[1]) : [];

            return $handler($model, $attribute, $arguments);
        }

        if (is_subclass_of($castName, BackedEnum::class)) {
            $values = array_column($castName::cases(), 'value');

            return (is_int($values[0]) ? JsonSchema::integer() : JsonSchema::string())->enum($values);
        }

        if (is_a($castName, AsArrayObject::class, true) || is_a($castName, AsCollection::class, true)) {
            return JsonSchema::union(['object', 'array']);
        }

        return match ($castName) {
            'int', 'integer' => JsonSchema::integer(),
            'real', 'float', 'double' => JsonSchema::number(),
            'bool', 'boolean' => JsonSchema::boolean(),
            'array', 'json', 'object', 'collection' => JsonSchema::union(['object', 'array']),
            'date', 'immutable_date' => JsonSchema::string()->format('date'),
            'datetime', 'immutable_datetime', 'timestamp' => JsonSchema::string()->format('date-time'),
            'decimal', 'string', 'hashed' => JsonSchema::string(),
            default => null,
        };
    }

    private function databaseType(string $databaseType): Type
    {
        $type = strtolower($databaseType);

        return match (true) {
            str_contains($type, 'bool') => JsonSchema::boolean(),
            str_contains($type, 'int') => JsonSchema::integer(),
            str_contains($type, 'decimal'),
            str_contains($type, 'numeric'),
            str_contains($type, 'float'),
            str_contains($type, 'double'),
            str_contains($type, 'real'),
                => JsonSchema::number(),
            default => JsonSchema::string(),
        };
    }
}
