<?php

namespace PHPinnacle\Eidos\Services;

use BackedEnum;
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
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @internal
 *
 * @phpstan-type Column array{name: string, type_name: string, nullable: bool, default: mixed, auto_increment: bool, generation: array{type: string|null, expression: string|null}|null}
 * @phpstan-type Attribute array{name: string, types: list<string>, nullable: bool, required: bool, default: mixed, enum: list<string|int>, format: string|null}
 */
final readonly class ModelSchema
{
    public function __construct(
        private Model $model,
    ) {}

    public function schema(): Type
    {
        $model = $this->model;
        $properties = $this->columnSchemas($this->columns());

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

            $relation = $this->relation($method);
            $related = new self($relation->getRelated());
            $relatedSchema = JsonSchema::object($related->columnSchemas($related->columns()));
            $name = $model::$snakeAttributes ? Str::snake($method->getName()) : $method->getName();

            $properties[$name] =
                $relation instanceof HasMany
                || $relation instanceof BelongsToMany
                || $relation instanceof HasManyThrough
                    ? JsonSchema::array()->items($relatedSchema)
                    : $relatedSchema->nullable();
        }

        return JsonSchema::object($properties)->title($model::class);
    }

    /** @return list<Attribute> */
    public function attributes(): array
    {
        $columns = $this->columns();
        $properties = $this->columnSchemas($columns);
        $defaults = $this->model->getAttributes();
        $attributes = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (!$this->model->isFillable($name) || $column['generation'] !== null) {
                continue;
            }

            $schema = $properties[$name]->toArray();
            $types = array_values(array_diff((array) $schema['type'], ['null']));

            $attributes[] = [
                'name' => $name,
                'types' => $types,
                'nullable' => $column['nullable'],
                'required' =>
                    !$column['nullable']
                        && $column['default'] === null
                        && !array_key_exists($name, $defaults)
                        && !$column['auto_increment'],
                'default' => $this->defaultValue($defaults[$name] ?? null, $types),
                'enum' => $schema['enum'] ?? [],
                'format' => $schema['format'] ?? null,
            ];
        }

        return $attributes;
    }

    /** @return Relation<Model, Model, mixed> */
    private function relation(ReflectionMethod $method): Relation
    {
        return Relation::noConstraints(fn () => $method->invoke($this->model));
    }

    /** @return list<Column> */
    private function columns(): array
    {
        return $this->model->getConnection()->getSchemaBuilder()->getColumns($this->model->getTable());
    }

    /**
     * @param list<Column> $columns
     * @return array<string, Type>
     */
    private function columnSchemas(array $columns): array
    {
        $properties = [];
        $casts = $this->model->getCasts();
        $dates = $this->model->getDates();

        foreach ($columns as $column) {
            $name = $column['name'];
            $cast = $casts[$name] ?? null;
            $type = in_array($name, $dates, true) && $cast === null
                ? JsonSchema::string()->format('date-time')
                : $this->columnType($column['type_name'], $cast);

            $properties[$name] = $column['nullable'] ? $type->nullable() : $type;
        }

        return $properties;
    }

    private function columnType(string $databaseType, ?string $cast): Type
    {
        return ($cast === null ? null : $this->castType($cast)) ?? $this->databaseType($databaseType);
    }

    private function castType(string $cast): ?Type
    {
        $castName = explode(':', $cast, 2)[0];

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

    /** @param list<string> $types */
    private function defaultValue(mixed $default, array $types): mixed
    {
        return match (true) {
            $default === null => null,
            $default instanceof BackedEnum => $default->value,
            in_array('boolean', $types, true) => (bool) $default,
            in_array('integer', $types, true) => (int) $default,
            in_array('number', $types, true) => (float) $default,
            in_array('string', $types, true) => (string) $default,
            is_string($default) => json_decode($default, associative: true, flags: JSON_THROW_ON_ERROR),
            default => $default,
        };
    }
}
