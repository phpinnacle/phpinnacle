<?php

namespace PHPinnacle\Common\Filters;

use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class HasFilter extends TernaryFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->query(function (BaseFilter $filter, Builder $query, array $data) {
            $value = $data['value'] ?? '';

            if ($value === '') {
                return $query;
            }

            return $value
                ? $query->whereHas($filter->getName())
                : $query->whereHas($filter->getName(), operator: '=', count: 0);
        });
    }
}
