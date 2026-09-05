<?php

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

if (!function_exists('clean_up_html')) {
    function clean_up_html(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return trim($html) !== '<p></p>' ? $html : null;
    }
}

if (!function_exists('reset_sort')) {
    /**
     * @param array<string, mixed> $where
     */
    function reset_sort(Model $record, array $where = [], string $field = 'sort'): void
    {
        if ($record->getAttribute($field) !== null) {
            return;
        }

        $query = $record->newModelQuery();

        foreach ($where as $key => $value) {
            if ($value === null) {
                $query->whereNull($key);
            } else {
                $query->where($key, $value);
            }
        }

        $record->setAttribute($field, $query->max($field) + 1);
    }
}

if (!function_exists('reset_default')) {
    /**
     * @param array<string, mixed> $where
     */
    function reset_default(Model $record, array $where = [], string $field = 'is_default'): void
    {
        if (!$record->getAttribute($field)) {
            return;
        }

        $record
            ->newModelQuery()
            ->whereNot($record->getKeyName(), $record->getKey())
            ->where($where)
            ->update([
                $field => false,
            ]);
    }
}

if (!function_exists('refresh_select')) {
    function refresh_select(string ...$keys): callable
    {
        return function (Field $component) use ($keys) {
            foreach ($keys as $key) {
                $select = $component->getContainer()->getComponent($key);

                if (!$select instanceof Select) {
                    continue;
                }

                $select->state(key($select->getOptions()))->callAfterStateUpdated();
            }
        };
    }
}
