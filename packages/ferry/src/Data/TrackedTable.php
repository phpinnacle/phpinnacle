<?php

namespace PHPinnacle\Ferry\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class TrackedTable
{
    /**
     * @param class-string<Model> $model
     * @param array<string, scalar> $discriminators
     */
    public function __construct(
        public string $model,
        public string $connection,
        public string $table,
        public string $primaryKey = 'id',
        public string $schema = 'public',
        public array $discriminators = [],
    ) {}

    /**
     * @param class-string<Model> $model
     * @param array<string, scalar> $discriminators
     */
    public static function forModel(string $model, array $discriminators = [], string $schema = 'public'): self
    {
        $instance = new $model;

        return new self(
            model: $model,
            connection: $instance->getConnectionName() ?? DB::getDefaultConnection(),
            table: $instance->getTable(),
            primaryKey: $instance->getKeyName(),
            schema: $schema,
            discriminators: $discriminators,
        );
    }

    public function qualifiedTable(): string
    {
        return sprintf('%s.%s', $this->schema, $this->table);
    }

    /** @param array<string, mixed> $attributes */
    public function matches(array $attributes): bool
    {
        foreach ($this->discriminators as $column => $expected) {
            if (($attributes[$column] ?? null) !== $expected) {
                return false;
            }
        }

        return true;
    }
}
