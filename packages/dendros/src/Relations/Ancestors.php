<?php

namespace PHPinnacle\Dendros\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use LogicException;
use PHPinnacle\Dendros\TreeNode;

/**
 * @property Model&TreeNode $related
 */
class Ancestors extends Relation
{
    public static function of(Model&TreeNode $model): self
    {
        return new self($model->newQuery(), $model);
    }

    public function addConstraints(): void
    {
        if (static::$constraints) {
            $column = $this->related->getPathColumn();

            $this->query
                ->whereRaw(sprintf('?::ltree @> %s', $column), [$this->related->getAttribute($column)])
                ->whereNot($this->related->getKeyName(), $this->related->getKey());
        }
    }

    public function addEagerConstraints(array $models): void
    {
        $column = $this->related->getPathColumn();

        $this->query->where(function (Builder $builder) use ($column, $models) {
            foreach ($models as $model) {
                $builder->orWhereRaw('?::ltree @> ' . $column, [$model->getAttribute($column)]);
            }
        });
    }

    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation): array
    {
        foreach ($models as $model) {
            if (!$model instanceof TreeNode) {
                throw new LogicException('Ancestor relations require tree node models.');
            }

            $model->setRelation($relation, $results->filter($model->isAncestorOf(...)));
        }

        return $models;
    }

    public function getResults(): Collection
    {
        return $this->related->isRoot() ? $this->query->get() : $this->related->newCollection();
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        return $query
            ->select($columns)
            ->from($query->getModel()->getTable(), 'ancestors')
            ->whereColumn(
                "ancestors.{$this->related->getPathColumn()}",
                '@>',
                $this->related->qualifyColumn($this->related->getPathColumn()),
            )
            ->whereColumn(
                "ancestors.{$this->related->getKeyName()}",
                '!=',
                $this->related->getQualifiedKeyName(),
            );
    }
}
