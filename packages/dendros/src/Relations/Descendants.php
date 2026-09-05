<?php

namespace PHPinnacle\Dendros\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use LogicException;
use PHPinnacle\Dendros\TreeNode;

/**
 * @property Model&TreeNode $parent
 * @property Model&TreeNode $related
 */
class Descendants extends Relation
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
                ->whereRaw(sprintf('?::ltree <@ %s', $column), [$this->related->getAttribute($column)])
                ->whereNot($this->related->getKeyName(), $this->related->getKey());
        }
    }

    public function addEagerConstraints(array $models): void
    {
        $column = $this->parent->getPathColumn();

        $this->query->where(function ($q) use ($models, $column) {
            foreach ($models as $model) {
                $q->orWhereRaw('?::ltree <@ ' . $column, [$model->getAttribute($column)]);
            }
        });
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        return $query
            ->select($columns)
            ->from($query->getModel()->getTable(), 'descendants')
            ->whereColumn(
                "descendants.{$this->related->getPathColumn()}",
                '<@',
                $this->related->qualifyColumn($this->related->getPathColumn()),
            )
            ->whereColumn(
                "descendants.{$this->related->getKeyName()}",
                '!=',
                $this->related->getQualifiedKeyName(),
            );
    }

    public function getResults(): Collection
    {
        return $this->query->get();
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
                throw new LogicException('Descendant relations require tree node models.');
            }

            $model->setRelation($relation, $results->filter($model->isDescendantOf(...)));
        }

        return $models;
    }
}
