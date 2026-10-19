<?php

namespace PHPinnacle\Eidos\Services;

use BackedEnum;
use Closure;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @phpstan-type Column array{name: string, type_name: string, nullable: bool, default: string|null, default_generated?: bool, auto_increment: bool, generation: array{type: string|null, expression: string|null}|null}
 */
#[Singleton]
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

    /** @param class-string<Model>|Model $model */
    public function introspect(string|Model $model): Schema
    {
        $model = is_string($model) ? new $model : $model;

        $definition = $this->columnSchema($model);
        $definition['properties'] = array_replace($definition['properties'], $this->relationSchemas($model));
        $definition['title'] = $model::class;

        return Schema::fromArray($definition);
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
            $relatedSchema = $this->columnSchema($relation->getRelated());
            $name = $model::$snakeAttributes ? Str::snake($method->getName()) : $method->getName();

            $properties[$name] =
                $relation instanceof HasMany
                || $relation instanceof MorphMany
                || $relation instanceof BelongsToMany
                || $relation instanceof HasManyThrough
                    ? ['type' => 'array', 'items' => $relatedSchema, 'readOnly' => true]
                    : array_replace($relatedSchema, ['type' => ['object', 'null'], 'readOnly' => true]);
        }

        return $properties;
    }

    /** @return Relation<Model, Model, mixed> */
    private function relation(Model $model, ReflectionMethod $method): Relation
    {
        return Relation::noConstraints(fn () => $method->invoke($model));
    }

    /** @return array{type: string, properties: array<string, array<string, mixed>>, required: list<string>} */
    private function columnSchema(Model $model): array
    {
        $columns = $this->columns($model);
        $properties = [];
        $required = [];
        $casts = $model->getCasts();
        $dates = $model->getDates();
        /** @var array<string, mixed> $defaults */
        $defaults = new ReflectionClass($model)->getDefaultProperties()['attributes'];
        $driver = $model->getConnection()->getDriverName();

        foreach ($columns as $column) {
            $name = $column['name'];
            $cast = $casts[$name] ?? null;
            $type = in_array($name, $dates, true) && $cast === null
                ? JsonSchema::string()->format('date-time')
                : $this->columnType($column['type_name'], $cast, $model, $name);

            $definition = ($column['nullable'] ? $type->nullable() : $type)->toArray();

            if ($column['generation'] !== null || !$model->isFillable($name)) {
                $definition['readOnly'] = true;
            }

            if (
                !($definition['readOnly'] ?? false)
                && !$column['nullable']
                && $column['default'] === null
                && !array_key_exists($name, $defaults)
                && !$column['auto_increment']
            ) {
                $required[] = $name;
            }

            if ($column['generation'] === null) {
                $default = match (true) {
                    array_key_exists($name, $defaults) => ['default' => $defaults[$name]],
                    $column['default_generated'] ?? false => [],
                    default => $this->databaseDefault($column['default'], $driver, $column['type_name']),
                };

                if (array_key_exists('default', $default)) {
                    $definition['default'] = $this->defaultValue($default['default'], $definition);
                }
            }

            $properties[$name] = $definition;
        }

        return ['type' => 'object', 'properties' => $properties, 'required' => $required];
    }

    /** @return list<Column> */
    private function columns(Model $model): array
    {
        $connection = $model->getConnection();
        $builder = $connection->getSchemaBuilder();

        if ($connection->getDriverName() !== 'mysql') {
            /** @var list<Column> */
            return $builder->getColumns($model->getTable());
        }

        // Laravel's MySQL processor discards EXTRA, which distinguishes expressions from literals.
        [$schema, $table] = $builder->parseSchemaAndTable($model->getTable());
        $results = $connection->selectFromWriteConnection(
            $connection->getSchemaGrammar()->compileColumns($schema, $connection->getTablePrefix() . $table),
        );

        /** @var list<Column> */
        $columns = $connection->getPostProcessor()->processColumns($results);

        foreach ($results as $index => $result) {
            $columns[$index]['default_generated'] = str_contains($result->extra, 'DEFAULT_GENERATED');
        }

        return $columns;
    }

    /** @return array{default?: mixed} */
    private function databaseDefault(?string $value, string $driver, string $databaseType): array
    {
        if ($value === null) {
            return [];
        }

        if ($driver === 'mysql') {
            if (
                in_array($databaseType, ['timestamp', 'datetime'], true)
                && preg_match('/^current_timestamp(?:\(\d*\))?$/iD', $value)
            ) {
                return [];
            }

            return ['default' => $value];
        }

        $value = trim($value);

        while (str_starts_with($value, '(') && str_ends_with($value, ')')) {
            $value = trim(substr($value, 1, -1));
        }

        if ($driver === 'pgsql') {
            $value = preg_replace('/::[a-zA-Z_][a-zA-Z0-9_ ."\[\]]*(?:\(\d+(?:,\s*\d+)?\))?$/D', '', $value);
        }

        if (preg_match("/^(?:N)?'((?:[^']|'')*)'$/isD", $value, $matches)) {
            return ['default' => str_replace("''", "'", $matches[1])];
        }

        return match (true) {
            strcasecmp($value, 'null') === 0 => ['default' => null],
            strcasecmp($value, 'true') === 0 => ['default' => true],
            strcasecmp($value, 'false') === 0 => ['default' => false],
            is_numeric($value) => ['default' => $value],
            default => [],
        };
    }

    /** @param array<string, mixed> $definition */
    private function defaultValue(mixed $value, array $definition): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        $types = (array) ($definition['type'] ?? []);

        return match (true) {
            in_array('integer', $types, true) => (int) $value,
            in_array('number', $types, true) => (float) $value,
            in_array('boolean', $types, true) => in_array($value, [true, 1, '1', 'true', 't'], true),
            in_array('string', $types, true) => (string) $value,
            is_string($value) && array_intersect($types, ['object', 'array']) !== [] => json_decode(
                $value,
                associative: false,
                flags: JSON_THROW_ON_ERROR,
            ),
            default => $value,
        };
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
