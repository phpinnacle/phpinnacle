<?php

namespace PHPinnacle\Eidos\Forms;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\StateCasts\OptionStateCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Jason\Schema;

/** @phpstan-type Lookup array{model: class-string<Model>, label?: string, search?: list<string>, key?: string} */
final class ModelLookup
{
    public static function picker(Schema $schema, string $name, int $limit = 20): ?Select
    {
        /** @var Lookup|null $lookup */
        $lookup = $schema->annotation('x-lookup');

        if ($lookup === null) {
            return null;
        }

        $model = new $lookup['model'];
        $key = $lookup['key'] ?? $model->getKeyName();
        $label = $lookup['label'] ?? 'name';
        $searchAttributes = $lookup['search'] ?? [$label];
        $isInteger = in_array('integer', $schema->types(), true);
        $options = fn (?string $search = null) => $model
            ->newQuery()
            ->when($search !== null, fn (Builder $query) => $query->where(function (Builder $query) use (
                $searchAttributes,
                $search,
            ) {
                foreach ($searchAttributes as $attribute) {
                    $query->orWhereLike($query->qualifyColumn($attribute), "%{$search}%");
                }
            }))
            ->orderBy($model->qualifyColumn($label))
            ->orderBy($model->qualifyColumn($key))
            ->limit($limit)
            ->pluck($label, $key)
            ->all();

        return Select::make($name)
            ->searchable()
            ->preload()
            ->optionsLimit($limit)
            ->options($options)
            ->stateCast(new OptionStateCast(shouldCastToInteger: false))
            ->dehydrateStateUsing(fn (?string $state) => $state !== null && $isInteger ? (int) $state : $state)
            ->getSearchResultsUsing($options)
            ->getOptionLabelUsing(fn (?string $value) => blank($value)
                ? null
                : $model->newQuery()->where($model->qualifyColumn($key), $value)->value($label));
    }
}
