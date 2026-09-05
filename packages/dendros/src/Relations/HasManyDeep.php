<?php

namespace PHPinnacle\Dendros\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use LogicException;
use PHPinnacle\Dendros\TreeNode;

/**
 * @extends HasMany<Model, Model>
 *
 * @property Model&TreeNode $parent
 */
class HasManyDeep extends HasMany
{
    protected const string HASH_PATH_COLUMN = 'laravel_reserved_path';

    /**
     * @param class-string<Model> $related
     */
    public static function between(
        Model&TreeNode $parent,
        string $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): self {
        $relatedInstance = self::newRelatedInstance($related, $parent);

        return new self(
            $relatedInstance->newQuery(),
            $parent,
            $foreignKey ?? $relatedInstance->qualifyColumn($parent->getForeignKey()),
            $localKey ?? $relatedInstance->getKeyName(),
        );
    }

    public function addConstraints(): void
    {
        if (static::$constraints && $this->getParentKey()) {
            $this->joinParent();

            $column = $this->parent->getPathColumn();

            $this->query->whereRaw(sprintf('?::ltree <@ %s', $column), [$this->parent->getAttribute($column)]);
            $this->query->select($this->related->qualifyColumn('*'));
        }
    }

    public function addEagerConstraints(array $models): void
    {
        $this->joinParent();

        $this->query->where(function (Builder $query) use ($models) {
            $column = $this->parent->getPathColumn();

            foreach ($models as $model) {
                $query->whereRaw(sprintf('?::ltree <@ %s', $column), [$model->getAttribute($column)], 'or');
            }
        });
    }

    public function get($columns = ['*']): Collection
    {
        if ($columns === ['*']) {
            $columns = ["{$this->related->getTable()}.*"];
        }

        $columns = array_merge($columns, [
            sprintf("{$this->parent->getTable()}.{$this->parent->getPathColumn()} as %s", self::HASH_PATH_COLUMN),
        ]);

        return $this->query->get($columns);
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $hash = $this->getRelationCountHash();

        return $query
            ->select($columns)
            ->join("{$this->parent->getTable()} as {$hash}", function (JoinClause $join) use ($hash) {
                $join->on($this->getForeignKeyName(), "{$hash}.{$this->getLocalKeyName()}");
            })
            ->whereColumn(
                $this->parent->qualifyColumn($this->parent->getPathColumn()),
                '@>',
                "{$hash}.{$this->parent->getPathColumn()}",
            );
    }

    public function match(array $models, Collection $results, $relation): array
    {
        foreach ($models as $model) {
            if (!$model instanceof TreeNode) {
                throw new LogicException('Deep relations require tree node models.');
            }

            $model->setRelation($relation, $results->filter(function (Model $result) use ($model) {
                $path = (string) $result->getAttribute(self::HASH_PATH_COLUMN);

                return collect(explode('.', $path))->contains($model->getPathSource());
            }));
        }

        return $models;
    }

    /**
     * @param class-string<Model> $class
     */
    protected static function newRelatedInstance(string $class, Model $parent): Model
    {
        return tap(new $class, static function ($related) use ($parent) {
            if (!$related->getConnectionName()) {
                $related->setConnection($parent->getConnectionName());
            }
        });
    }

    protected function joinParent(): void
    {
        $this->query->join($this->parent->getTable(), function (JoinClause $join) {
            $join->on($this->getQualifiedForeignKeyName(), $this->getQualifiedParentKeyName());
        });
    }
}
